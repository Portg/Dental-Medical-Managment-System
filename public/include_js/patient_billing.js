/**
 * BillingModule — 划价收费模块
 * Handles service category tree, billing items table, payment, and submission.
 */
var BillingModule = (function() {
    'use strict';

    var patientId = null;
    var categoryData = {};
    var billingItems = [];
    var itemCounter = 0;
    var doctors = [];
    var initialized = false;
    var invoicesTableLoaded = false;
    var receiptsTableLoaded = false;

    // ─── Init ──────────────────────────────────────────────────────
    function init(pid, doctorList) {
        if (initialized) return;
        patientId = pid;
        doctors = doctorList || [];
        initialized = true;

        loadServiceCategories();
        bindEvents();
    }

    // ─── Load service categories ───────────────────────────────────
    function loadServiceCategories() {
        $.ajax({
            url: '/billing/service-categories/' + patientId,
            type: 'GET',
            success: function(resp) {
                if (resp.status && resp.data) {
                    categoryData = resp.data;
                    renderCategoryTree(categoryData);
                }
            },
            error: function() {
                $('#billingCategoryTree').html(
                    '<div class="billing-empty-state">' +
                    '<i class="fa fa-exclamation-circle"></i> ' +
                    LanguageManager.trans('messages.error_occurred') +
                    '</div>'
                );
            }
        });
    }

    // ─── Render category tree ──────────────────────────────────────
    function renderCategoryTree(data) {
        var html = '';
        var categories = Object.keys(data);

        if (categories.length === 0) {
            html = '<div class="billing-empty-state">' +
                   '<i class="fa fa-inbox"></i> ' +
                   LanguageManager.trans('invoices.no_services_found') +
                   '</div>';
            $('#billingCategoryTree').html(html);
            return;
        }

        categories.forEach(function(cat) {
            var services = data[cat];
            html += '<div class="billing-cat-group">';
            html += '<div class="billing-cat-header" data-cat="' + escapeHtml(cat) + '">';
            html += '<span>' + escapeHtml(cat) + ' (' + services.length + ')</span>';
            html += '<i class="fa fa-chevron-down cat-toggle"></i>';
            html += '</div>';
            html += '<ul class="billing-cat-items">';
            services.forEach(function(svc) {
                html += '<li data-svc-id="' + svc.id + '" data-name="' + escapeHtml(svc.name) + '" ' +
                        'data-price="' + svc.price + '" data-unit="' + escapeHtml(svc.unit) + '">';
                html += '<span class="svc-name">' + escapeHtml(svc.name) + '</span>';
                html += '<span class="svc-price">&yen;' + parseFloat(svc.price).toFixed(2) + '</span>';
                html += '</li>';
            });
            html += '</ul></div>';
        });

        $('#billingCategoryTree').html(html);
    }

    // ─── Filter services ───────────────────────────────────────────
    function filterServices(keyword) {
        keyword = keyword.toLowerCase().trim();
        $('#billingCategoryTree .billing-cat-items li').each(function() {
            var name = $(this).data('name').toString().toLowerCase();
            if (!keyword || name.indexOf(keyword) !== -1) {
                $(this).removeClass('hidden-svc');
            } else {
                $(this).addClass('hidden-svc');
            }
        });

        // Show/hide category groups based on visible items
        $('#billingCategoryTree .billing-cat-group').each(function() {
            var visibleItems = $(this).find('.billing-cat-items li:not(.hidden-svc)').length;
            $(this).toggle(visibleItems > 0);
        });
    }

    // ─── Add billing item ──────────────────────────────────────────
    function addBillingItem(svcId, name, price, unit) {
        itemCounter++;
        var idx = itemCounter;

        var item = {
            idx: idx,
            medical_service_id: svcId,
            name: name,
            unit: unit,
            price: price,
            qty: 1,
            discount_rate: 100,
            discounted_price: price,
            actual_paid: price,
            arrears: 0,
            tooth_no: '',
            doctor_id: ''
        };

        billingItems.push(item);
        renderItemRow(item);
        toggleEmptyState();
        recalculateTotals();
    }

    // ─── Render item row ───────────────────────────────────────────
    function renderItemRow(item) {
        var doctorOptions = '<option value="">-</option>';
        doctors.forEach(function(doc) {
            doctorOptions += '<option value="' + doc.id + '">' + escapeHtml(doc.name) + '</option>';
        });

        var tr = '<tr data-idx="' + item.idx + '">' +
            '<td>' + item.idx + '</td>' +
            '<td>' + escapeHtml(item.name) + '<input type="hidden" class="item-svc-id" value="' + item.medical_service_id + '"></td>' +
            '<td>' + escapeHtml(item.unit) + '</td>' +
            '<td><input type="number" class="form-control input-sm input-price item-price" value="' + item.price.toFixed(2) + '" step="0.01" min="0"></td>' +
            '<td><input type="number" class="form-control input-sm input-qty item-qty" value="' + item.qty + '" min="1" step="1"></td>' +
            '<td class="item-total">' + (item.price * item.qty).toFixed(2) + '</td>' +
            '<td><input type="number" class="form-control input-sm input-discount item-discount" value="' + item.discount_rate + '" min="0" max="100" step="1">%</td>' +
            '<td><input type="number" class="form-control input-sm input-price item-discounted" value="' + item.discounted_price.toFixed(2) + '" step="0.01" min="0"></td>' +
            '<td><input type="number" class="form-control input-sm input-price item-actual" value="' + item.actual_paid.toFixed(2) + '" step="0.01" min="0"></td>' +
            '<td class="item-arrears">' + item.arrears.toFixed(2) + '</td>' +
            '<td><select class="form-control input-sm select-doctor item-doctor">' + doctorOptions + '</select></td>' +
            '<td><input type="text" class="form-control input-sm item-tooth" value="" placeholder="" style="width:55px"></td>' +
            '<td><span class="btn-remove-row" data-idx="' + item.idx + '"><i class="fa fa-times"></i></span></td>' +
            '</tr>';

        $('#billingItemsBody').append(tr);
    }

    // ─── Recalculate row ───────────────────────────────────────────
    function recalculateRow($tr) {
        var price = parseFloat($tr.find('.item-price').val()) || 0;
        var qty = parseInt($tr.find('.item-qty').val()) || 1;
        var total = price * qty;
        var discountRate = parseFloat($tr.find('.item-discount').val());
        if (isNaN(discountRate)) discountRate = 100;

        var discounted = total * discountRate / 100;
        var actualPaid = parseFloat($tr.find('.item-actual').val());
        if (isNaN(actualPaid)) actualPaid = discounted;

        // If discount changed, update discounted & actual
        var prevDiscounted = parseFloat($tr.find('.item-discounted').val()) || 0;
        var discountChanged = Math.abs(discounted - prevDiscounted) > 0.001;

        $tr.find('.item-total').text(total.toFixed(2));
        $tr.find('.item-discounted').val(discounted.toFixed(2));

        if (discountChanged) {
            $tr.find('.item-actual').val(discounted.toFixed(2));
            actualPaid = discounted;
        }

        var arrears = discounted - actualPaid;
        if (arrears < 0) arrears = 0;
        $tr.find('.item-arrears').text(arrears.toFixed(2));

        // Update internal item data
        var idx = parseInt($tr.data('idx'));
        var item = findItem(idx);
        if (item) {
            item.price = price;
            item.qty = qty;
            item.discount_rate = discountRate;
            item.discounted_price = discounted;
            item.actual_paid = actualPaid;
            item.arrears = arrears;
        }
    }

    // ─── Recalculate on actual_paid change ─────────────────────────
    function recalculateActual($tr) {
        var discounted = parseFloat($tr.find('.item-discounted').val()) || 0;
        var actualPaid = parseFloat($tr.find('.item-actual').val()) || 0;
        var arrears = discounted - actualPaid;
        if (arrears < 0) arrears = 0;
        $tr.find('.item-arrears').text(arrears.toFixed(2));

        var idx = parseInt($tr.data('idx'));
        var item = findItem(idx);
        if (item) {
            item.actual_paid = actualPaid;
            item.arrears = arrears;
        }
    }

    // ─── Recalculate totals ────────────────────────────────────────
    function recalculateTotals() {
        var totalOriginal = 0;
        var totalDiscounted = 0;
        var totalActual = 0;
        var totalArrears = 0;

        billingItems.forEach(function(item) {
            totalOriginal += item.price * item.qty;
            totalDiscounted += item.discounted_price;
            totalActual += item.actual_paid;
            totalArrears += item.arrears;
        });

        // Apply order discount
        var orderRate = parseFloat($('#orderDiscountRate').val());
        if (isNaN(orderRate)) orderRate = 100;
        if (orderRate < 100) {
            var factor = orderRate / 100;
            totalDiscounted = totalDiscounted * factor;
            totalActual = totalActual * factor;
            totalArrears = totalDiscounted - totalActual;
            if (totalArrears < 0) totalArrears = 0;
        }

        $('#summaryOriginal').text(totalOriginal.toFixed(2));
        $('#summaryDiscounted').text(totalDiscounted.toFixed(2));
        $('#summaryActual').text(totalActual.toFixed(2));
        $('#summaryArrears').text(totalArrears.toFixed(2));

        // BR-035: Check discount approval threshold (500)
        var discountAmount = totalOriginal - totalDiscounted;
        var DISCOUNT_THRESHOLD = 500;
        if (discountAmount > DISCOUNT_THRESHOLD) {
            $('#discountApprovalWarning').show();
            $('#btnCharge').prop('disabled', true).addClass('disabled');
            $('#btnChargeAndPrint').prop('disabled', true).addClass('disabled');
        } else {
            $('#discountApprovalWarning').hide();
            $('#btnCharge').prop('disabled', false).removeClass('disabled');
            $('#btnChargeAndPrint').prop('disabled', false).removeClass('disabled');
        }

        // Auto-fill first payment amount
        var $firstPayAmount = $('#paymentRows .payment-row:first .payment-amount-input');
        if ($firstPayAmount.length && !$firstPayAmount.data('manual')) {
            $firstPayAmount.val(totalActual.toFixed(2));
        }
    }

    // ─── Payment rows ──────────────────────────────────────────────
    var paymentCounter = 0;

    function addPaymentRow() {
        paymentCounter++;
        var idx = paymentCounter;
        var html = '<div class="payment-row" data-index="' + idx + '">' +
            '<select class="form-control input-sm payment-method-select" data-index="' + idx + '">' +
            '<option value="Cash">' + LanguageManager.trans('invoices.cash') + '</option>' +
            '<option value="WechatPay">' + LanguageManager.trans('invoices.wechat_pay') + '</option>' +
            '<option value="Alipay">' + LanguageManager.trans('invoices.alipay') + '</option>' +
            '<option value="BankCard">' + LanguageManager.trans('invoices.bank_card') + '</option>' +
            '<option value="Insurance">' + LanguageManager.trans('invoices.insurance') + '</option>' +
            '<option value="Cheque">' + LanguageManager.trans('invoices.cheque') + '</option>' +
            '<option value="StoredValue">' + LanguageManager.trans('invoices.stored_value') + '</option>' +
            '<option value="SelfAccount">' + LanguageManager.trans('invoices.self_account') + '</option>' +
            '</select>' +
            '<input type="number" class="form-control input-sm payment-amount-input" data-index="' + idx + '" placeholder="' + LanguageManager.trans('invoices.amount') + '" step="0.01" min="0">' +
            '<span class="payment-extra cheque-fields" data-index="' + idx + '">' +
            '<input type="text" class="form-control input-sm" data-field="cheque_no" placeholder="' + LanguageManager.trans('invoices.cheque_no') + '">' +
            '<input type="text" class="form-control input-sm" data-field="bank_name" placeholder="' + LanguageManager.trans('invoices.bank_name') + '">' +
            '</span>' +
            '<span class="payment-extra insurance-fields" data-index="' + idx + '">' +
            '<select class="form-control input-sm billing-insurance-select" data-field="insurance_company_id">' +
            '<option value="">' + LanguageManager.trans('invoices.choose_insurance_company') + '</option>' +
            '</select>' +
            '</span>' +
            '<span class="payment-extra self-account-fields" data-index="' + idx + '">' +
            '<select class="form-control input-sm billing-self-account-select" data-field="self_account_id">' +
            '<option value="">' + LanguageManager.trans('invoices.choose_self_account') + '</option>' +
            '</select>' +
            '</span>' +
            '<button type="button" class="btn btn-xs btn-danger btn-remove-payment" data-index="' + idx + '">' +
            '<i class="fa fa-times"></i>' +
            '</button>' +
            '</div>';

        $('#paymentRows').append(html);
    }

    function showPaymentExtraFields($row) {
        var method = $row.find('.payment-method-select').val();
        $row.find('.payment-extra').hide();

        if (method === 'Cheque') {
            $row.find('.cheque-fields').show();
        } else if (method === 'Insurance') {
            $row.find('.insurance-fields').show();
            initInsuranceSelect2($row.find('.billing-insurance-select'));
        } else if (method === 'SelfAccount') {
            $row.find('.self-account-fields').show();
            initSelfAccountSelect2($row.find('.billing-self-account-select'));
        }
    }

    function initInsuranceSelect2($el) {
        if ($el.data('select2')) return; // already initialized
        $el.select2({
            placeholder: LanguageManager.trans('invoices.choose_insurance_company'),
            minimumInputLength: 1,
            allowClear: true,
            ajax: {
                url: '/search-insurance-company',
                dataType: 'json',
                delay: 300,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return { id: item.id, text: item.name || item.text };
                        })
                    };
                }
            }
        });
    }

    function initSelfAccountSelect2($el) {
        if ($el.data('select2')) return;
        $el.select2({
            placeholder: LanguageManager.trans('invoices.choose_self_account'),
            minimumInputLength: 1,
            allowClear: true,
            ajax: {
                url: '/search-self-account',
                dataType: 'json',
                delay: 300,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return { id: item.id, text: item.name || item.text };
                        })
                    };
                }
            }
        });
    }

    // ─── Collect payment data ──────────────────────────────────────
    function collectPayments() {
        var payments = [];
        $('#paymentRows .payment-row').each(function() {
            var $row = $(this);
            var method = $row.find('.payment-method-select').val();
            var amount = parseFloat($row.find('.payment-amount-input').val()) || 0;
            if (amount <= 0) return;

            var payment = {
                payment_method: method,
                amount: amount
            };

            if (method === 'Cheque') {
                payment.cheque_no = $row.find('[data-field="cheque_no"]').val();
                payment.bank_name = $row.find('[data-field="bank_name"]').val();
            } else if (method === 'Insurance') {
                payment.insurance_company_id = $row.find('[data-field="insurance_company_id"]').val();
            } else if (method === 'SelfAccount') {
                payment.self_account_id = $row.find('[data-field="self_account_id"]').val();
            }

            payments.push(payment);
        });
        return payments;
    }

    // ─── Collect items data ────────────────────────────────────────
    function collectItems() {
        var items = [];
        billingItems.forEach(function(item) {
            var $tr = $('#billingItemsBody tr[data-idx="' + item.idx + '"]');
            items.push({
                medical_service_id: item.medical_service_id,
                qty: item.qty,
                price: item.price,
                discount_rate: item.discount_rate,
                discounted_price: item.discounted_price,
                actual_paid: item.actual_paid,
                arrears: item.arrears,
                tooth_no: $tr.find('.item-tooth').val() || null,
                doctor_id: $tr.find('.item-doctor').val() || null
            });
        });
        return items;
    }

    // ─── Submit billing ────────────────────────────────────────────
    function submitBilling(mode, printAfter) {
        var items = collectItems();
        if (items.length === 0) {
            swal({
                title: LanguageManager.trans('messages.error'),
                text: LanguageManager.trans('invoices.no_billing_items'),
                type: 'warning'
            });
            return;
        }

        var payments = (mode === 'direct') ? collectPayments() : [];
        var paymentDate = null;
        if ($('#backEntryCheck').is(':checked')) {
            paymentDate = $('#backEntryDate').val();
        }

        var postData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            patient_id: patientId,
            items: items,
            payments: payments,
            order_discount_rate: parseFloat($('#orderDiscountRate').val()) || 100,
            payment_date: paymentDate,
            billing_mode: mode
        };

        $('.loading').show();

        $.ajax({
            url: '/billing/create',
            type: 'POST',
            data: JSON.stringify(postData),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(resp) {
                $('.loading').hide();
                if (resp.status) {
                    swal({
                        title: LanguageManager.trans('messages.success'),
                        text: resp.message,
                        type: 'success'
                    });

                    // Clear billing form
                    resetBillingForm();

                    // Reload invoices table if loaded
                    if (invoicesTableLoaded && $.fn.DataTable.isDataTable('#patient_invoices_table')) {
                        $('#patient_invoices_table').DataTable().ajax.reload();
                    }

                    // Print if requested
                    if (printAfter && resp.invoice_id) {
                        window.open('/print-receipt/' + resp.invoice_id, '_blank');
                    }
                } else {
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: resp.message,
                        type: 'error'
                    });
                }
            },
            error: function(xhr) {
                $('.loading').hide();
                var msg = LanguageManager.trans('messages.error_occurred');
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: msg,
                    type: 'error'
                });
            }
        });
    }

    // ─── Reset form ────────────────────────────────────────────────
    function resetBillingForm() {
        billingItems = [];
        itemCounter = 0;
        $('#billingItemsBody').empty();
        toggleEmptyState();
        recalculateTotals();
        $('#orderDiscountRate').val(100);
        $('#backEntryCheck').prop('checked', false);
        $('#backEntryDate').hide();

        // Reset payments to single Cash row
        $('#paymentRows').html('');
        paymentCounter = 0;
        addDefaultPaymentRow();
    }

    function addDefaultPaymentRow() {
        var html = '<div class="payment-row" data-index="0">' +
            '<select class="form-control input-sm payment-method-select" data-index="0">' +
            '<option value="Cash">' + LanguageManager.trans('invoices.cash') + '</option>' +
            '<option value="WechatPay">' + LanguageManager.trans('invoices.wechat_pay') + '</option>' +
            '<option value="Alipay">' + LanguageManager.trans('invoices.alipay') + '</option>' +
            '<option value="BankCard">' + LanguageManager.trans('invoices.bank_card') + '</option>' +
            '<option value="Insurance">' + LanguageManager.trans('invoices.insurance') + '</option>' +
            '<option value="Cheque">' + LanguageManager.trans('invoices.cheque') + '</option>' +
            '<option value="StoredValue">' + LanguageManager.trans('invoices.stored_value') + '</option>' +
            '<option value="SelfAccount">' + LanguageManager.trans('invoices.self_account') + '</option>' +
            '</select>' +
            '<input type="number" class="form-control input-sm payment-amount-input" data-index="0" placeholder="' + LanguageManager.trans('invoices.amount') + '" step="0.01" min="0">' +
            '<span class="payment-extra cheque-fields" data-index="0">' +
            '<input type="text" class="form-control input-sm" data-field="cheque_no" placeholder="' + LanguageManager.trans('invoices.cheque_no') + '">' +
            '<input type="text" class="form-control input-sm" data-field="bank_name" placeholder="' + LanguageManager.trans('invoices.bank_name') + '">' +
            '</span>' +
            '<span class="payment-extra insurance-fields" data-index="0">' +
            '<select class="form-control input-sm billing-insurance-select" data-field="insurance_company_id">' +
            '<option value="">' + LanguageManager.trans('invoices.choose_insurance_company') + '</option>' +
            '</select>' +
            '</span>' +
            '<span class="payment-extra self-account-fields" data-index="0">' +
            '<select class="form-control input-sm billing-self-account-select" data-field="self_account_id">' +
            '<option value="">' + LanguageManager.trans('invoices.choose_self_account') + '</option>' +
            '</select>' +
            '</span>' +
            '</div>';
        $('#paymentRows').html(html);
    }

    // ─── Lazy-load invoices table ──────────────────────────────────
    function initInvoicesTable() {
        if (invoicesTableLoaded) return;
        invoicesTableLoaded = true;

        $('#patient_invoices_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/patient-invoices/' + patientId,
                type: 'GET'
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'invoice_no', name: 'invoice_no'},
                {data: 'created_at', name: 'created_at'},
                {data: 'amount', name: 'amount'},
                {data: 'paid_amount', name: 'paid_amount', defaultContent: '0'},
                {data: 'statusBadge', name: 'statusBadge'},
                {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false}
            ],
            order: [[2, 'desc']],
            language: LanguageManager.getDataTableLang()
        });
    }

    // ─── Lazy-load receipts table ──────────────────────────────────
    function initReceiptsTable() {
        if (receiptsTableLoaded) return;
        receiptsTableLoaded = true;

        $('#patient_receipts_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/patient-receipts/' + patientId,
                type: 'GET'
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'invoice_no', name: 'invoice_no'},
                {data: 'payment_date', name: 'payment_date'},
                {data: 'method_label', name: 'method_label'},
                {data: 'amount_formatted', name: 'amount_formatted'},
                {data: 'added_by_name', name: 'added_by_name', defaultContent: '-'}
            ],
            order: [[2, 'desc']],
            language: LanguageManager.getDataTableLang()
        });
    }

    // ─── Toggle empty state ────────────────────────────────────────
    function toggleEmptyState() {
        if (billingItems.length === 0) {
            $('#billingEmptyState').show();
            $('#billingItemsTable thead').hide();
        } else {
            $('#billingEmptyState').hide();
            $('#billingItemsTable thead').show();
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────
    function findItem(idx) {
        for (var i = 0; i < billingItems.length; i++) {
            if (billingItems[i].idx === idx) return billingItems[i];
        }
        return null;
    }

    function removeItem(idx) {
        billingItems = billingItems.filter(function(item) { return item.idx !== idx; });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ─── Bind events ──────────────────────────────────────────────
    function bindEvents() {
        // Search
        $('#billingServiceSearch').on('input', function() {
            filterServices($(this).val());
        });

        // Category header toggle
        $(document).on('click', '.billing-cat-header', function() {
            $(this).toggleClass('collapsed');
            $(this).next('.billing-cat-items').slideToggle(150);
        });

        // Click service to add
        $(document).on('click', '.billing-cat-items li', function() {
            var svcId = $(this).data('svc-id');
            var name = $(this).data('name');
            var price = parseFloat($(this).data('price')) || 0;
            var unit = $(this).data('unit') || '次';
            addBillingItem(svcId, name, price, unit);
        });

        // Remove billing item
        $(document).on('click', '.btn-remove-row', function() {
            var idx = parseInt($(this).data('idx'));
            removeItem(idx);
            $('#billingItemsBody tr[data-idx="' + idx + '"]').remove();
            toggleEmptyState();
            recalculateTotals();
        });

        // Recalculate on price/qty/discount change
        $(document).on('change input', '#billingItemsBody .item-price, #billingItemsBody .item-qty, #billingItemsBody .item-discount', function() {
            var $tr = $(this).closest('tr');
            recalculateRow($tr);
            recalculateTotals();
        });

        // Recalculate on actual_paid change
        $(document).on('change input', '#billingItemsBody .item-actual', function() {
            var $tr = $(this).closest('tr');
            recalculateActual($tr);
            recalculateTotals();
        });

        // Recalculate on discounted_price manual change
        $(document).on('change', '#billingItemsBody .item-discounted', function() {
            var $tr = $(this).closest('tr');
            var discounted = parseFloat($(this).val()) || 0;
            var idx = parseInt($tr.data('idx'));
            var item = findItem(idx);
            if (item) {
                item.discounted_price = discounted;
                item.actual_paid = discounted;
                $tr.find('.item-actual').val(discounted.toFixed(2));
                item.arrears = 0;
                $tr.find('.item-arrears').text('0.00');
            }
            recalculateTotals();
        });

        // Order discount
        $('#orderDiscountRate').on('change input', function() {
            recalculateTotals();
        });

        // Add payment row
        $('#btnAddPayment').on('click', function() {
            addPaymentRow();
        });

        // Remove payment row
        $(document).on('click', '.btn-remove-payment', function() {
            $(this).closest('.payment-row').remove();
        });

        // Payment method change — show/hide extra fields
        $(document).on('change', '.payment-method-select', function() {
            showPaymentExtraFields($(this).closest('.payment-row'));
        });

        // Mark payment amount as manually edited
        $(document).on('input', '.payment-amount-input', function() {
            $(this).data('manual', true);
        });

        // Charge button
        $('#btnCharge').on('click', function() {
            submitBilling('direct', false);
        });

        // Charge & Print
        $('#btnChargeAndPrint').on('click', function() {
            submitBilling('direct', true);
        });

        // Front desk billing
        $('#btnFrontDesk').on('click', function() {
            submitBilling('front_desk', false);
        });

        // Back entry toggle
        $('#backEntryCheck').on('change', function() {
            if ($(this).is(':checked')) {
                $('#backEntryDate').show();
            } else {
                $('#backEntryDate').hide();
            }
        });

        // Sub-tab lazy loading
        $('#billingSubTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = $(e.target).attr('href');
            if (target === '#billing_sub_bills') {
                initInvoicesTable();
            } else if (target === '#billing_sub_receipts') {
                initReceiptsTable();
            }
        });
    }

    // ─── Public API ────────────────────────────────────────────────
    return {
        init: init,
        initInvoicesTable: initInvoicesTable,
        initReceiptsTable: initReceiptsTable
    };
})();
