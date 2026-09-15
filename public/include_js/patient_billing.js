/**
 * BillingModule — 划价收费模块
 * Handles service category tree, billing items table, payment, and submission.
 */
var BillingModule = (function() {
    'use strict';

    var patientId = null;
    // 诊疗页划价时带上：账单要挂到这次就诊上，否则诊疗页「本次已划价」表看不到
    // （该表按 invoices.appointment_id 过滤）。患者页划价无就诊上下文，保持 null。
    var appointmentId = null;
    var onSaved = null;
    var categoryData = {};
    var billingItems = [];
    var itemCounter = 0;
    var doctors = [];
    var initialized = false;
    var invoicesTableLoaded = false;
    var receiptsTableLoaded = false;
    var panelOpen = false;
    var panelType = null; // 'invoice' | 'payment'
    var PAYMENT_METHODS = [
        { code: 'Cash',         label: LanguageManager.trans('invoices.cash', '现金') },
        { code: 'WeChat',       label: LanguageManager.trans('invoices.wechat_pay', '微信') },
        { code: 'Alipay',       label: LanguageManager.trans('invoices.alipay', '支付宝') },
        { code: 'BankCard',     label: LanguageManager.trans('invoices.bank_card', '银行卡') },
        { code: 'Insurance',    label: LanguageManager.trans('invoices.insurance', '保险') },
        { code: 'Cheque',       label: LanguageManager.trans('invoices.cheque', '支票') },
        { code: 'StoredValue',  label: LanguageManager.trans('invoices.stored_value', '储值') },
        { code: 'Self Account', label: LanguageManager.trans('invoices.self_account', '自费账户') },
    ];

    // ─── Init ──────────────────────────────────────────────────────
    /**
     * @param {number} pid        患者 id
     * @param {Array}  doctorList 在职医生 [{id, name}]
     * @param {Object} [options]  {appointmentId, onSaved}
     *   appointmentId —— 诊疗页传；onSaved —— 划价成功后的回调（诊疗页用它刷明细表）
     */
    function init(pid, doctorList, options) {
        if (initialized) return;
        patientId = pid;
        doctors = doctorList || [];
        options = options || {};
        appointmentId = options.appointmentId || null;
        onSaved = typeof options.onSaved === 'function' ? options.onSaved : null;
        initialized = true;

        loadServiceCategories();
        loadPatientContext();
        bindEvents();
        bindPanelEvents();
    }

    // ─── 面板顶栏患者上下文 ────────────────────────────────────────
    function moneyFmt(v) {
        return '¥' + (parseFloat(v) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function loadPatientContext() {
        if (!patientId) return;
        $.getJSON('/patients/' + patientId + '/billing-summary', function (res) {
            if (!res || !res.status || !res.data) return;
            applyPatientContext(res.data);
        });
    }

    function applyPatientContext(data) {
        $('#billingCtxName').text(data.full_name || '—');
        $('#billingCtxNo').text(data.patient_no ? ('#' + data.patient_no) : '');
        $('#billingCtxLevel').text(data.member_level || '');
        $('#billingCtxOutstanding').text(moneyFmt(data.total_outstanding));
        $('#billingCtxBalance').text(moneyFmt(data.member_balance));
        $('#billingCtxSpending').text(moneyFmt(data.total_spending));
        renderOutstandingList(data.open_invoices || []);
    }

    // ─── 历史欠费勾选（并入本次收款） ──────────────────────────────
    function renderOutstandingList(invoices) {
        var $section = $('#billingOutstandingSection');
        var $list = $('#billingOutstandingList');
        if (!$section.length || !$list.length) {
            return;
        }

        // 保留用户已勾选的 id，刷新列表后尽量还原
        var previouslyChecked = {};
        $list.find('.outstanding-check:checked').each(function () {
            previouslyChecked[String($(this).val())] = true;
        });

        $list.empty();
        if (!invoices || invoices.length === 0) {
            $section.hide();
            $('#billingOutstandingSelectAll').prop('checked', false);
            updateOutstandingSelectedSum();
            return;
        }

        invoices.forEach(function (inv) {
            var id = String(inv.id);
            var checked = previouslyChecked[id] ? ' checked' : '';
            var amt = parseFloat(inv.outstanding_amount) || 0;
            $list.append(
                '<li>' +
                '<label>' +
                '<input type="checkbox" class="outstanding-check" value="' + id + '"' +
                ' data-amount="' + amt.toFixed(2) + '"' + checked + '>' +
                '<span class="inv-no">' + escapeHtml(inv.invoice_no || ('#' + id)) + '</span>' +
                '<span class="inv-date">' + escapeHtml(inv.date || '') + '</span>' +
                '<span class="inv-amt">' + moneyFmt(amt) + '</span>' +
                '</label>' +
                '</li>'
            );
        });

        $section.show();
        syncOutstandingSelectAll();
        updateOutstandingSelectedSum();
    }

    function getSelectedOutstanding() {
        var selected = [];
        $('#billingOutstandingList .outstanding-check:checked').each(function () {
            selected.push({
                id: parseInt($(this).val(), 10),
                amount: parseFloat($(this).data('amount')) || 0
            });
        });
        return selected;
    }

    function getSelectedOutstandingSum() {
        return getSelectedOutstanding().reduce(function (sum, row) {
            return sum + row.amount;
        }, 0);
    }

    function updateOutstandingSelectedSum() {
        var sum = getSelectedOutstandingSum();
        $('#billingOutstandingSelectedSum').text(moneyFmt(sum));
        // 勾选变化时，若首行付款未手改，重新带入「本次实收 + 勾选欠费」
        recalculateTotals();
    }

    function syncOutstandingSelectAll() {
        var $checks = $('#billingOutstandingList .outstanding-check');
        var $all = $('#billingOutstandingSelectAll');
        if (!$checks.length) {
            $all.prop('checked', false);
            return;
        }
        $all.prop('checked', $checks.length === $checks.filter(':checked').length);
    }

    /** 从混合支付里按顺序切出不超过 target 的部分，供本次新单收款 */
    function allocatePayments(payments, target) {
        var remaining = Math.round((target || 0) * 100) / 100;
        var allocated = [];
        if (remaining <= 0) {
            return allocated;
        }
        payments.forEach(function (p) {
            if (remaining <= 0) return;
            var amt = Math.round((parseFloat(p.amount) || 0) * 100) / 100;
            if (amt <= 0) return;
            var take = Math.min(amt, remaining);
            var copy = $.extend({}, p);
            copy.amount = take;
            allocated.push(copy);
            remaining = Math.round((remaining - take) * 100) / 100;
        });
        return allocated;
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
        var favorites = [];
        var seenFav = {};

        categories.forEach(function(cat) {
            (data[cat] || []).forEach(function(svc) {
                if (svc.is_favorite && !seenFav[svc.id]) {
                    seenFav[svc.id] = true;
                    favorites.push(svc);
                }
            });
        });

        if (categories.length === 0 && favorites.length === 0) {
            html = '<div class="billing-empty-state">' +
                   '<i class="fa fa-inbox"></i> ' +
                   LanguageManager.trans('invoices.no_services_found') +
                   '</div>';
            $('#billingCategoryTree').html(html);
            return;
        }

        function renderGroup(catLabel, services, extraClass) {
            var block = '<div class="billing-cat-group' + (extraClass ? ' ' + extraClass : '') + '">';
            block += '<div class="billing-cat-header" data-cat="' + escapeHtml(catLabel) + '">';
            if (extraClass === 'is-favorites') {
                block += '<span><i class="fa fa-star"></i> ' + escapeHtml(catLabel) + ' (' + services.length + ')</span>';
            } else {
                block += '<span>' + escapeHtml(catLabel) + ' (' + services.length + ')</span>';
            }
            block += '<i class="fa fa-chevron-down cat-toggle"></i>';
            block += '</div>';
            block += '<ul class="billing-cat-items">';
            services.forEach(function(svc) {
                block += '<li data-svc-id="' + svc.id + '" data-name="' + escapeHtml(svc.name) + '" ' +
                        'data-price="' + svc.price + '" data-unit="' + escapeHtml(svc.unit) + '">';
                block += '<span class="svc-name">' + escapeHtml(svc.name) + '</span>';
                block += '<span class="svc-price">&yen;' + parseFloat(svc.price).toFixed(2) + '</span>';
                block += '</li>';
            });
            block += '</ul></div>';
            return block;
        }

        // 常用置顶（对齐轻松牙医左侧常用）
        if (favorites.length > 0) {
            html += renderGroup(
                LanguageManager.trans('invoices.favorite_services', '常用项目'),
                favorites,
                'is-favorites'
            );
        }

        categories.forEach(function(cat) {
            html += renderGroup(cat, data[cat], '');
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
            '<td class="item-discount-cell"><input type="number" class="form-control input-sm input-discount item-discount" value="' + item.discount_rate + '" min="0" max="100" step="1">%</td>' +
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

        // 抹零：从折后/实收合计里扣减
        var roundOff = parseFloat($('#billingRoundOff').val());
        if (isNaN(roundOff) || roundOff < 0) roundOff = 0;
        if (roundOff > totalDiscounted) {
            roundOff = totalDiscounted;
            $('#billingRoundOff').val(roundOff.toFixed(2));
        }
        if (roundOff > 0) {
            totalDiscounted = Math.max(0, totalDiscounted - roundOff);
            totalActual = Math.max(0, totalActual - roundOff);
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

        // Auto-fill first payment amount = 本次实收 + 勾选历史欠费
        var dueNow = totalActual + getSelectedOutstandingSum();
        var $firstPayAmount = $('#paymentRows .payment-row:first .payment-amount-input');
        if ($firstPayAmount.length && !$firstPayAmount.data('manual')) {
            $firstPayAmount.val(dueNow.toFixed(2));
        }
    }

    /** 抹角分：把当前折后合计向下取整到元，差额写入抹零 */
    function applyQuickRoundOff() {
        var totalDiscounted = 0;
        billingItems.forEach(function(item) {
            totalDiscounted += item.discounted_price;
        });
        var orderRate = parseFloat($('#orderDiscountRate').val());
        if (isNaN(orderRate)) orderRate = 100;
        if (orderRate < 100) {
            totalDiscounted = totalDiscounted * (orderRate / 100);
        }
        var floored = Math.floor(totalDiscounted);
        var diff = Math.round((totalDiscounted - floored) * 100) / 100;
        $('#billingRoundOff').val(diff > 0 ? diff.toFixed(2) : '0');
        recalculateTotals();
    }

    // ─── Payment rows ──────────────────────────────────────────────
    var paymentCounter = 0;

    function addPaymentRow() {
        paymentCounter++;
        var idx = paymentCounter;
        var html = '<div class="payment-row" data-index="' + idx + '">' +
            '<select class="form-control input-sm payment-method-select" data-index="' + idx + '">' +
            '<option value="Cash">' + LanguageManager.trans('invoices.cash') + '</option>' +
            '<option value="WeChat">' + LanguageManager.trans('invoices.wechat_pay') + '</option>' +
            '<option value="Alipay">' + LanguageManager.trans('invoices.alipay') + '</option>' +
            '<option value="BankCard">' + LanguageManager.trans('invoices.bank_card') + '</option>' +
            '<option value="Insurance">' + LanguageManager.trans('invoices.insurance') + '</option>' +
            '<option value="Cheque">' + LanguageManager.trans('invoices.cheque') + '</option>' +
            '<option value="StoredValue">' + LanguageManager.trans('invoices.stored_value') + '</option>' +
            '<option value="Self Account">' + LanguageManager.trans('invoices.self_account') + '</option>' +
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
        } else if (method === 'Self Account') {
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
            } else if (method === 'Self Account') {
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

        var chargeActual = parseFloat($('#summaryActual').text()) || 0;
        var selectedOutstanding = (mode === 'direct') ? getSelectedOutstanding() : [];
        var allPayments = (mode === 'direct') ? collectPayments() : [];
        // 新单只吃「本次实收」对应的支付份额；多出来的留给勾选欠费补收
        var payments = (mode === 'direct') ? allocatePayments(allPayments, chargeActual) : [];
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
            round_off: parseFloat($('#billingRoundOff').val()) || 0,
            payment_date: paymentDate,
            billing_mode: mode,
            appointment_id: appointmentId
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
                if (resp.status) {
                    var afterSettle = function (failures) {
                        $('.loading').hide();

                        var okText = resp.message;
                        if (failures && failures.length) {
                            okText = LanguageManager.trans(
                                'invoices.outstanding_settle_partial',
                                '本次划价已收费；部分历史欠费补收失败：:detail'
                            ).replace(':detail', failures.join('；'));
                        }

                        swal({
                            title: LanguageManager.trans('messages.success'),
                            text: okText,
                            type: (failures && failures.length) ? 'warning' : 'success'
                        });

                        resetBillingForm();
                        reloadInvoicesTable();
                        reloadReceiptsTable();
                        if (onSaved) { onSaved(resp); }

                        if (printAfter && resp.invoice_id) {
                            window.open('/print-receipt/' + resp.invoice_id, '_blank');
                        }
                    };

                    if (mode === 'direct' && selectedOutstanding.length) {
                        settleSelectedOutstanding(
                            selectedOutstanding,
                            allPayments,
                            chargeActual,
                            paymentDate,
                            afterSettle
                        );
                    } else {
                        afterSettle([]);
                    }
                } else {
                    $('.loading').hide();
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

    /**
     * 划价收款成功后，用支付总额里多出「本次实收」的部分，按勾选顺序补收历史欠费。
     * 支付方式沿用第一笔收款的渠道（与前台「一笔钱收多张单」操作一致）。
     */
    function settleSelectedOutstanding(selected, allPayments, chargeActual, paymentDate, done) {
        var paidTotal = allPayments.reduce(function (sum, p) {
            return sum + (parseFloat(p.amount) || 0);
        }, 0);
        var remaining = Math.round((paidTotal - chargeActual) * 100) / 100;
        var template = allPayments[0] || { payment_method: 'Cash' };
        var failures = [];
        var chain = $.Deferred().resolve();

        selected.forEach(function (inv) {
            chain = chain.then(function () {
                if (remaining <= 0.001) {
                    return;
                }
                var amt = Math.min(remaining, inv.amount);
                amt = Math.round(amt * 100) / 100;
                if (amt <= 0) {
                    return;
                }

                var payload = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    amount: amt,
                    payment_method: template.payment_method || 'Cash',
                    payment_date: paymentDate || null
                };
                if (template.cheque_no) payload.cheque_no = template.cheque_no;
                if (template.bank_name) payload.bank_name = template.bank_name;
                if (template.insurance_company_id) payload.insurance_company_id = template.insurance_company_id;
                if (template.self_account_id) payload.self_account_id = template.self_account_id;

                return $.ajax({
                    url: '/invoices/' + inv.id + '/add-overdue-payment',
                    type: 'POST',
                    data: payload
                }).then(function (res) {
                    if (res && (res.status === 1 || res.status === true)) {
                        remaining = Math.round((remaining - amt) * 100) / 100;
                    } else {
                        failures.push((inv.id ? ('#' + inv.id) : '') + ' ' + (res && res.message ? res.message : ''));
                    }
                }, function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : LanguageManager.trans('messages.error_occurred');
                    failures.push('#' + inv.id + ' ' + msg);
                });
            });
        });

        chain.always(function () {
            done(failures);
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
        $('#billingRoundOff').val(0);
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
            '<option value="WeChat">' + LanguageManager.trans('invoices.wechat_pay') + '</option>' +
            '<option value="Alipay">' + LanguageManager.trans('invoices.alipay') + '</option>' +
            '<option value="BankCard">' + LanguageManager.trans('invoices.bank_card') + '</option>' +
            '<option value="Insurance">' + LanguageManager.trans('invoices.insurance') + '</option>' +
            '<option value="Cheque">' + LanguageManager.trans('invoices.cheque') + '</option>' +
            '<option value="StoredValue">' + LanguageManager.trans('invoices.stored_value') + '</option>' +
            '<option value="Self Account">' + LanguageManager.trans('invoices.self_account') + '</option>' +
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
            createdRow: function (row, data) {
                $(row).attr('data-invoice-id', data.id)
                      .attr('data-outstanding', data.outstanding_amount || '0');
            },
            language: LanguageManager.getDataTableLang(),
            initComplete: function () {
                $('#patient_invoices_table tbody').on('click', 'tr', function (e) {
                    if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) return;
                    var invoiceId = $(this).data('invoice-id');
                    if (invoiceId) openInvoicePanel(invoiceId);
                });
            }
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
            createdRow: function (row, data) {
                $(row).attr('data-payment-id', data.id);
            },
            language: LanguageManager.getDataTableLang(),
            initComplete: function () {
                $('#patient_receipts_table tbody').on('click', 'tr', function (e) {
                    if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) return;
                    var paymentId = $(this).data('payment-id');
                    if (paymentId) openPaymentPanel(paymentId);
                });
            }
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

        $('#billingRoundOff').on('change input', function() {
            recalculateTotals();
        });

        $('#btnQuickRoundOff').on('click', function() {
            applyQuickRoundOff();
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

        // 历史区折叠展开时：默认激活「账单」页签不会再触发 shown.bs.tab，这里补一次
        $('#billingHistoryCollapse').on('shown.bs.collapse', function () {
            var activeHref = $('#billingSubTabs li.active a').attr('href');
            if (activeHref === '#billing_sub_bills') {
                initInvoicesTable();
            } else if (activeHref === '#billing_sub_receipts') {
                initReceiptsTable();
            }
        });

        // 勾选历史欠费 → 重算本次应收合计
        $(document).on('change', '#billingOutstandingList .outstanding-check', function () {
            syncOutstandingSelectAll();
            updateOutstandingSelectedSum();
        });
        $('#billingOutstandingSelectAll').on('change', function () {
            var checked = $(this).is(':checked');
            $('#billingOutstandingList .outstanding-check').prop('checked', checked);
            updateOutstandingSelectedSum();
        });
    }

    // ─── Panel Core ────────────────────────────────────────────────

    function openPanel(title) {
        panelOpen = true;
        $('#billingPanelTitle').text(title);
        $('#billingPanelBody').html(
            '<div style="text-align:center;padding:40px"><i class="fa fa-spinner fa-spin fa-2x"></i></div>'
        );
        $('#billingPanelOverlay').addClass('active');
        $('#billingSidePanel').addClass('open');
        setTimeout(function () {
            $('#billingPanelClose').focus();
        }, 260);
    }

    function closePanel() {
        panelOpen = false;
        $('#billingPanelOverlay').removeClass('active');
        $('#billingSidePanel').removeClass('open');
    }

    function bindPanelEvents() {
        $(document).on('click', '#billingPanelClose', function () {
            closePanel();
        });
        $(document).on('click', '#billingPanelOverlay', function () {
            closePanel();
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && panelOpen) {
                closePanel();
            }
        });
        bindPanelSaveEvents();
    }

    function buildPaymentMethodSelect(name, selectedCode, cssClass) {
        var html = '<select name="' + name + '" class="form-control ' + (cssClass || '') + '">';
        $.each(PAYMENT_METHODS, function (_, m) {
            html += '<option value="' + m.code + '"' + (m.code === selectedCode ? ' selected' : '') + '>'
                 + m.label + '</option>';
        });
        html += '</select>';
        return html;
    }

    function buildExtraPaymentFields(container, code, data) {
        var $extra = container.find('.payment-extra-fields').empty();
        data = data || {};
        if (code === 'Cheque') {
            $extra.html(
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.cheque_no', '支票号') + '</label>' +
                '<input type="text" name="cheque_no" class="form-control input-sm" value="' + escapeHtml(data.cheque_no || '') + '"></div>' +
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.bank_name', '银行') + '</label>' +
                '<input type="text" name="bank_name" class="form-control input-sm" value="' + escapeHtml(data.bank_name || '') + '"></div>'
            );
        } else if (code === 'Insurance') {
            var insuranceOption = data.insurance_company_id
                ? '<option value="' + escapeHtml(data.insurance_company_id) + '" selected>' + escapeHtml(data.name || data.insurance_company_name || '') + '</option>'
                : '<option value=""></option>';
            $extra.html(
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.insurance_company', '保险公司') + '</label>' +
                '<select name="insurance_company_id" class="form-control input-sm billing-insurance-select">' +
                insuranceOption +
                '</select></div>'
            );
            initInsuranceSelect2($extra.find('.billing-insurance-select'));
        } else if (code === 'Self Account') {
            var selfAccountOption = data.self_account_id
                ? '<option value="' + escapeHtml(data.self_account_id) + '" selected>' + escapeHtml(data.self_account_name || '') + '</option>'
                : '<option value=""></option>';
            $extra.html(
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.self_account', '自费账户') + '</label>' +
                '<select name="self_account_id" class="form-control input-sm billing-self-account-select">' +
                selfAccountOption +
                '</select></div>'
            );
            initSelfAccountSelect2($extra.find('.billing-self-account-select'));
        }
    }

    function openInvoicePanel(invoiceId) {
        panelType = 'invoice';
        openPanel(LanguageManager.trans('invoices.panel_invoice_detail', '账单详情'));

        $.get('/invoices/' + invoiceId + '/billing-detail', function (res) {
            if (!res || res.status !== 1) {
                $('#billingPanelBody').html(
                    '<div class="alert alert-danger">' +
                    LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
                );
                return;
            }
            var d = res.data;
            var isOverdue = parseFloat(d.outstanding_amount) > 0;

            var userOptions = '<option value=""></option>';
            $.each(d.users, function (_, u) {
                userOptions += '<option value="' + escapeHtml(u.id) + '">' + escapeHtml(u.name || '') + '</option>';
            });

            function staffSelect(name, selectedId) {
                return '<select name="' + name + '" class="form-control input-sm">' +
                       userOptions.replace('value="' + selectedId + '"', 'value="' + selectedId + '" selected') +
                       '</select>';
            }

            var html =
                '<div class="billing-panel-meta">' +
                '<div class="billing-panel-meta-row"><span class="label">#</span><span class="value">' + escapeHtml(d.invoice_no || d.id) + '</span></div>' +
                '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.amount', '金额') + '</span><span class="value">¥' + parseFloat(d.total_amount).toFixed(2) + '</span></div>' +
                (isOverdue ? '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.overdue_payment', '欠费') + '</span><span class="value overdue">¥' + parseFloat(d.outstanding_amount).toFixed(2) + '</span></div>' : '') +
                '</div>' +

                '<div class="billing-panel-section">' +
                '<div class="billing-panel-section-title">' + LanguageManager.trans('invoices.modify_staff', '修改人员') + '</div>' +
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.doctor', '医生') + '</label>' + staffSelect('doctor_id', d.doctor_id) + '</div>' +
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.nurse', '护士') + '</label>' + staffSelect('nurse_id', d.nurse_id) + '</div>' +
                '<div class="form-group"><label>' + LanguageManager.trans('invoices.assistant', '助理') + '</label>' + staffSelect('assistant_id', d.assistant_id) + '</div>' +
                '<button class="btn btn-primary billing-panel-save" id="panelSaveStaff" data-invoice-id="' + d.id + '">' +
                LanguageManager.trans('common.save', '保存') + '</button>' +
                '</div>';

            if (isOverdue) {
                html +=
                    '<div class="billing-panel-section">' +
                    '<div class="billing-panel-section-title overdue">' + LanguageManager.trans('invoices.overdue_payment', '欠费处理') + '</div>' +
                    '<div class="form-group"><label>' + LanguageManager.trans('invoices.supplement_amount', '补收金额') + '</label>' +
                    '<input type="number" name="overdue_amount" class="form-control input-sm" min="0" step="0.01" placeholder="0.00"></div>' +
                    '<div class="form-group"><label>' + LanguageManager.trans('invoices.additional_discount', '再优惠金额') + '</label>' +
                    '<input type="number" name="additional_discount" class="form-control input-sm" min="0" step="0.01" placeholder="0.00">' +
                    '<span class="form-text">' + LanguageManager.trans('invoices.additional_discount_tip', '填写后从欠费中直接减免') + '</span></div>' +
                    '<div class="form-group"><label>' + LanguageManager.trans('invoices.modify_payment_method', '收款方式') + '</label>' +
                    buildPaymentMethodSelect('overdue_payment_method', 'Cash') + '</div>' +
                    '<div class="payment-extra-fields"></div>' +
                    '<button class="btn btn-warning billing-panel-save" id="panelSaveOverdue" data-invoice-id="' + d.id + '" data-outstanding="' + d.outstanding_amount + '">' +
                    LanguageManager.trans('common.save', '保存') + '</button>' +
                    '</div>';
            }

            $('#billingPanelBody').html(html);

            $('#billingPanelBody').off('change.billingPanel', 'select[name="overdue_payment_method"]')
                .on('change.billingPanel', 'select[name="overdue_payment_method"]', function () {
                buildExtraPaymentFields($('#billingPanelBody'), $(this).val());
            });
        }).fail(function () {
            $('#billingPanelBody').html(
                '<div class="alert alert-danger">' +
                LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
            );
        });
    }

    function openPaymentPanel(paymentId) {
        panelType = 'payment';
        openPanel(LanguageManager.trans('invoices.panel_receipt_detail', '收费单详情'));

        $.get('/payments/' + paymentId + '/edit', function (res) {
            if (!res) {
                $('#billingPanelBody').html(
                    '<div class="alert alert-danger">' +
                    LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
                );
                return;
            }
            var d = res;
            var html =
                '<div class="billing-panel-meta">' +
                '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.receipt_amount_readonly', '金额') + '</span>' +
                '<span class="value">¥' + parseFloat(d.amount).toFixed(2) + '</span></div>' +
                '<div class="billing-panel-meta-row"><span class="label">' + LanguageManager.trans('invoices.receipt_date', '日期') + '</span>' +
                '<span class="value">' + escapeHtml(d.payment_date || '-') + '</span></div>' +
                '</div>' +
                '<div class="billing-panel-section">' +
                '<div class="billing-panel-section-title">' + LanguageManager.trans('invoices.modify_payment_method', '修改收款方式') + '</div>' +
                '<div class="form-group">' + buildPaymentMethodSelect('payment_method', d.payment_method) + '</div>' +
                '<div class="payment-extra-fields"></div>' +
                '<button class="btn btn-primary billing-panel-save" id="panelSavePayment" data-payment-id="' + d.id + '">' +
                LanguageManager.trans('common.save', '保存') + '</button>' +
                '</div>';

            $('#billingPanelBody').html(html);
            buildExtraPaymentFields($('#billingPanelBody'), d.payment_method, d);

            $('#billingPanelBody').off('change.billingPanel', 'select[name="payment_method"]')
                .on('change.billingPanel', 'select[name="payment_method"]', function () {
                buildExtraPaymentFields($('#billingPanelBody'), $(this).val());
            });
        }).fail(function () {
            $('#billingPanelBody').html(
                '<div class="alert alert-danger">' +
                LanguageManager.trans('invoices.panel_load_failed', '加载失败') + '</div>'
            );
        });
    }

    function bindPanelSaveEvents() {
        $(document).on('click', '#panelSaveStaff', function () {
            var $btn = $(this);
            var invoiceId = $btn.data('invoice-id');
            $btn.prop('disabled', true).text(LanguageManager.trans('common.saving', '保存中...'));

            $.ajax({
                url: '/invoices/' + invoiceId,
                method: 'PATCH',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    doctor_id:    $('#billingPanelBody select[name="doctor_id"]').val() || null,
                    nurse_id:     $('#billingPanelBody select[name="nurse_id"]').val() || null,
                    assistant_id: $('#billingPanelBody select[name="assistant_id"]').val() || null,
                },
                success: function (res) {
                    if (res.status === 1) {
                        toastr.success(res.message);
                        closePanel();
                        reloadInvoicesTable();
                    } else {
                        toastr.error(res.message);
                        $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
                    }
                },
                error: function (xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : LanguageManager.trans('invoices.panel_load_failed', '保存失败');
                    toastr.error(msg);
                    $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
                }
            });
        });

        $(document).on('click', '#panelSaveOverdue', function () {
            var $btn = $(this);
            var invoiceId = $btn.data('invoice-id');
            var outstanding = parseFloat($btn.data('outstanding'));
            var amount = parseFloat($('#billingPanelBody input[name="overdue_amount"]').val()) || 0;
            var discount = parseFloat($('#billingPanelBody input[name="additional_discount"]').val()) || 0;

            if (amount + discount <= 0) {
                toastr.warning(LanguageManager.trans('invoices.overdue_amount_required', '金额不能为零'));
                return;
            }
            if (amount + discount > outstanding + 0.001) {
                toastr.warning(LanguageManager.trans('invoices.overdue_amount_exceeds', '超过欠费金额'));
                return;
            }

            $btn.prop('disabled', true).text(LanguageManager.trans('common.saving', '保存中...'));

            var data = {
                _token:              $('meta[name="csrf-token"]').attr('content'),
                amount:              amount > 0 ? amount.toFixed(2) : null,
                additional_discount: discount > 0 ? discount.toFixed(2) : null,
                payment_method:      $('#billingPanelBody select[name="overdue_payment_method"]').val(),
                cheque_no:           $('#billingPanelBody input[name="cheque_no"]').val() || null,
                bank_name:           $('#billingPanelBody input[name="bank_name"]').val() || null,
                insurance_company_id: $('#billingPanelBody select[name="insurance_company_id"]').val() || null,
                self_account_id:     $('#billingPanelBody select[name="self_account_id"]').val() || null,
            };

            $.post('/invoices/' + invoiceId + '/add-overdue-payment', data, function (res) {
                if (res.status === 1) {
                    toastr.success(res.message);
                    closePanel();
                    reloadInvoicesTable();
                    reloadReceiptsTable();
                } else {
                    toastr.error(res.message);
                    $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
                }
            }).fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : LanguageManager.trans('invoices.panel_load_failed', '保存失败');
                toastr.error(msg);
                $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
            });
        });

        $(document).on('click', '#panelSavePayment', function () {
            var $btn = $(this);
            var paymentId = $btn.data('payment-id');
            $btn.prop('disabled', true).text(LanguageManager.trans('common.saving', '保存中...'));

            var data = {
                _token:         $('meta[name="csrf-token"]').attr('content'),
                _method:        'PUT',
                payment_method: $('#billingPanelBody select[name="payment_method"]').val(),
                cheque_no:      $('#billingPanelBody input[name="cheque_no"]').val() || null,
                bank_name:      $('#billingPanelBody input[name="bank_name"]').val() || null,
                insurance_company_id: $('#billingPanelBody select[name="insurance_company_id"]').val() || null,
                self_account_id: $('#billingPanelBody select[name="self_account_id"]').val() || null,
            };

            $.post('/payments/' + paymentId, data, function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    closePanel();
                    reloadReceiptsTable();
                } else {
                    toastr.error(res.message);
                    $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
                }
            }).fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : LanguageManager.trans('invoices.panel_load_failed', '保存失败');
                toastr.error(msg);
                $btn.prop('disabled', false).text(LanguageManager.trans('common.save', '保存'));
            });
        });
    }

    function reloadInvoicesTable() {
        if ($.fn.DataTable.isDataTable('#patient_invoices_table')) {
            $('#patient_invoices_table').DataTable().ajax.reload(null, false);
        }
        // 开账单同样会改变消费总额与未付余额
        reloadBillingSummary();
    }

    function reloadReceiptsTable() {
        if ($.fn.DataTable.isDataTable('#patient_receipts_table')) {
            $('#patient_receipts_table').DataTable().ajax.reload(null, false);
        }
        // 收款/退款改变的是钱，顶部汇总栏也得跟着走
        reloadBillingSummary();
    }

    /**
     * 刷新页面顶部那条汇总栏（消费总额 / 未付余额 / 储值余额）。
     *
     * 那三个数是服务端渲染进 patients/show.blade.php 的，只重载 DataTable 的话
     * 它们会一直停在打开页面时的旧值 —— 前台收完款看不到新的未付金额，只能
     * 强制刷新整页。这里按 id 就地改文本，不动页面其它部分。
     */
    var summaryPending = false;

    function reloadBillingSummary() {
        if (!patientId || summaryPending) {
            return;
        }

        // 开账单/收款那几处会连着调 reloadInvoicesTable + reloadReceiptsTable，
        // 同一拍里去重，别为一次操作发两遍请求
        summaryPending = true;
        setTimeout(function () { summaryPending = false; }, 0);

        $.getJSON('/patients/' + patientId + '/billing-summary', function (res) {
            if (!res || !res.status || !res.data) {
                return;
            }

            var money = function (v) {
                return '¥' + (parseFloat(v) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            };

            $('#summaryTotalSpending').text(money(res.data.total_spending));
            $('#summaryTotalOutstanding').text(money(res.data.total_outstanding));
            // 非会员患者页面上没有这一项，选择器落空是正常的
            $('#summaryMemberBalance').text(money(res.data.member_balance));

            // 划价面板顶栏与患者页汇总栏同源刷新
            applyPatientContext(res.data);
        });
    }

    // ─── Public API ────────────────────────────────────────────────
    return {
        init: init,
        initInvoicesTable: initInvoicesTable,
        initReceiptsTable: initReceiptsTable,
        openInvoicePanel: openInvoicePanel,
        openPaymentPanel: openPaymentPanel,
    };
})();
