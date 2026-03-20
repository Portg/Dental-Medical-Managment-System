{{-- Billing Tab: 划价 / 账单 / 收费单 --}}
<ul class="nav nav-tabs billing-sub-tabs" id="billingSubTabs">
    <li class="active">
        <a href="#billing_sub_billing" data-toggle="tab">{{ __('invoices.billing_tab') }}</a>
    </li>
    <li>
        <a href="#billing_sub_bills" data-toggle="tab">{{ __('invoices.bills_tab') }}</a>
    </li>
    <li>
        <a href="#billing_sub_receipts" data-toggle="tab">{{ __('invoices.receipts_tab') }}</a>
    </li>
</ul>

<div class="tab-content">
    {{-- ══ Sub-tab 1: 划价 ══ --}}
    <div class="tab-pane active" id="billing_sub_billing">
        <div class="row">
            {{-- Left: Category tree --}}
            <div class="col-md-3">
                <div class="billing-category-panel">
                    <div class="billing-search-box">
                        <input type="text" id="billingServiceSearch"
                               placeholder="{{ __('invoices.search_service') }}"
                               class="form-control input-sm">
                    </div>
                    <div class="billing-category-tree" id="billingCategoryTree">
                        <div class="billing-empty-state">
                            <i class="fa fa-spinner fa-spin"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Items table + summary + payment --}}
            <div class="col-md-9">
                <div class="billing-items-panel">
                    <div class="billing-table-wrapper">
                        <table class="billing-table" id="billingItemsTable">
                            <thead>
                                <tr>
                                    <th style="width:30px">#</th>
                                    <th>{{ __('invoices.procedure') }}</th>
                                    <th style="width:50px">{{ __('invoices.unit') }}</th>
                                    <th style="width:60px">{{ __('invoices.unit_price') }}</th>
                                    <th style="width:55px">{{ __('invoices.qty') }}</th>
                                    <th style="width:80px">{{ __('invoices.total_amount') }}</th>
                                    <th style="width:60px">{{ __('invoices.discount_rate') }}</th>
                                    <th style="width:80px">{{ __('invoices.discounted_price') }}</th>
                                    <th style="width:80px">{{ __('invoices.actual_paid') }}</th>
                                    <th style="width:80px">{{ __('invoices.arrears') }}</th>
                                    <th style="width:100px">{{ __('invoices.procedure_doctor') }}</th>
                                    <th style="width:60px">{{ __('invoices.tooth_no') }}</th>
                                    <th style="width:30px"></th>
                                </tr>
                            </thead>
                            <tbody id="billingItemsBody">
                            </tbody>
                        </table>
                        <div class="billing-empty-state" id="billingEmptyState">
                            <i class="fa fa-shopping-cart"></i>
                            {{ __('invoices.no_billing_items') }}
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="billing-summary">
                        <div class="summary-row">
                            <span class="summary-label">{{ __('invoices.total_original') }}:</span>
                            <span class="summary-value" id="summaryOriginal">0.00</span>
                            <span class="summary-label">{{ __('invoices.total_discounted') }}:</span>
                            <span class="summary-value" id="summaryDiscounted">0.00</span>
                            <span class="summary-label">{{ __('invoices.total_actual') }}:</span>
                            <span class="summary-value summary-total" id="summaryActual">0.00</span>
                            <span class="summary-label">{{ __('invoices.total_arrears') }}:</span>
                            <span class="summary-value text-danger" id="summaryArrears">0.00</span>
                        </div>
                        <div class="order-discount-row">
                            <span class="summary-label">{{ __('invoices.order_discount_label') }}:</span>
                            <input type="number" id="orderDiscountRate" class="form-control input-sm"
                                   value="100" min="0" max="100" step="1">
                            <span>%</span>
                        </div>
                        <div class="discount-approval-warning" id="discountApprovalWarning" style="display:none">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span>{{ __('invoices.discount_approval_required') }}</span>
                        </div>
                    </div>

                    {{-- Payment --}}
                    <div class="billing-payment">
                        <div class="payment-rows" id="paymentRows">
                            <div class="payment-row" data-index="0">
                                <select class="form-control input-sm payment-method-select" data-index="0">
                                    <option value="Cash">{{ __('invoices.cash') }}</option>
                                    <option value="WechatPay">{{ __('invoices.wechat_pay') }}</option>
                                    <option value="Alipay">{{ __('invoices.alipay') }}</option>
                                    <option value="BankCard">{{ __('invoices.bank_card') }}</option>
                                    <option value="Insurance">{{ __('invoices.insurance') }}</option>
                                    <option value="Cheque">{{ __('invoices.cheque') }}</option>
                                    <option value="StoredValue">{{ __('invoices.stored_value') }}</option>
                                    <option value="SelfAccount">{{ __('invoices.self_account') }}</option>
                                </select>
                                <input type="number" class="form-control input-sm payment-amount-input"
                                       data-index="0" placeholder="{{ __('invoices.amount') }}"
                                       step="0.01" min="0">
                                {{-- Conditional fields for Cheque/Insurance/SelfAccount --}}
                                <span class="payment-extra cheque-fields" data-index="0">
                                    <input type="text" class="form-control input-sm" data-field="cheque_no"
                                           placeholder="{{ __('invoices.cheque_no') }}">
                                    <input type="text" class="form-control input-sm" data-field="bank_name"
                                           placeholder="{{ __('invoices.bank_name') }}">
                                </span>
                                <span class="payment-extra insurance-fields" data-index="0">
                                    <select class="form-control input-sm billing-insurance-select" data-field="insurance_company_id">
                                        <option value="">{{ __('invoices.choose_insurance_company') }}</option>
                                    </select>
                                </span>
                                <span class="payment-extra self-account-fields" data-index="0">
                                    <select class="form-control input-sm billing-self-account-select" data-field="self_account_id">
                                        <option value="">{{ __('invoices.choose_self_account') }}</option>
                                    </select>
                                </span>
                                <button type="button" class="btn btn-xs btn-danger btn-remove-payment"
                                        data-index="0" style="display:none">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-default btn-xs btn-add-payment" id="btnAddPayment">
                            <i class="fa fa-plus"></i> {{ __('invoices.add_payment_method') }}
                        </button>
                    </div>

                    {{-- Actions --}}
                    <div class="billing-actions">
                        <button type="button" class="btn btn-primary" id="btnCharge">
                            <i class="fa fa-check"></i> {{ __('invoices.charge') }}
                        </button>
                        <button type="button" class="btn btn-success" id="btnChargeAndPrint">
                            <i class="fa fa-print"></i> {{ __('invoices.charge_and_print') }}
                        </button>
                        <button type="button" class="btn btn-warning" id="btnFrontDesk">
                            <i class="fa fa-clock-o"></i> {{ __('invoices.front_desk_billing') }}
                        </button>
                        <div class="back-entry-area">
                            <label>
                                <input type="checkbox" id="backEntryCheck"> {{ __('invoices.back_entry') }}
                            </label>
                            <input type="date" id="backEntryDate" class="form-control input-sm"
                                   style="display:none" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Sub-tab 2: 账单 ══ --}}
    <div class="tab-pane" id="billing_sub_bills">
        <br>
        <table class="table table-striped table-bordered table-hover table-checkable order-column"
               id="patient_invoices_table">
            <thead>
            <tr>
                <th>{{ __('common.id') }}</th>
                <th>{{ __('invoices.invoice_no') }}</th>
                <th>{{ __('invoices.date') }}</th>
                <th>{{ __('invoices.amount') }}</th>
                <th>{{ __('invoices.paid_amount') }}</th>
                <th>{{ __('invoices.status') }}</th>
                <th>{{ __('common.view') }}</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    {{-- ══ Sub-tab 3: 收费单 ══ --}}
    <div class="tab-pane" id="billing_sub_receipts">
        <br>
        <table class="table table-striped table-bordered table-hover table-checkable order-column"
               id="patient_receipts_table">
            <thead>
            <tr>
                <th>{{ __('common.id') }}</th>
                <th>{{ __('invoices.invoice_no') }}</th>
                <th>{{ __('invoices.payment_date') }}</th>
                <th>{{ __('invoices.payment_method') }}</th>
                <th>{{ __('invoices.amount') }}</th>
                <th>{{ __('invoices.added_by') }}</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
