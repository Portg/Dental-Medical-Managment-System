{{-- 划价 / 收费面板 —— 患者页与诊疗页共用。

     抽出来是为了让划价只有一套 UI。原先诊疗页走的是另一条路（AddInvoice 弹窗 →
     POST /invoices），患者页走这个面板（POST /billing/create）：两条路各自实现折扣、
     牙位、医生归属，改一处漏一处已经出过好几次问题。

     依赖 BillingModule（public/include_js/patient_billing.js）与 css/patient-billing.css，
     由宿主页面负责引入并调用 BillingModule.init()。面板内的 id 是全局唯一的，
     同一个页面不要 include 两次。 --}}

{{-- 患者上下文条：对齐轻松牙医收费窗始终显示当前患者与账户概况 --}}
<div class="billing-patient-context" id="billingPatientContext">
    <div class="billing-ctx-main">
        <span class="billing-ctx-name" id="billingCtxName">—</span>
        <span class="billing-ctx-no" id="billingCtxNo"></span>
        <span class="billing-ctx-level" id="billingCtxLevel"></span>
    </div>
    <div class="billing-ctx-stats">
        <span class="billing-ctx-stat">
            <em>{{ __('invoices.ctx_outstanding') }}</em>
            <strong class="text-danger" id="billingCtxOutstanding">¥0.00</strong>
        </span>
        <span class="billing-ctx-stat">
            <em>{{ __('invoices.ctx_member_balance') }}</em>
            <strong id="billingCtxBalance">¥0.00</strong>
        </span>
        <span class="billing-ctx-stat">
            <em>{{ __('invoices.ctx_total_spending') }}</em>
            <strong id="billingCtxSpending">¥0.00</strong>
        </span>
    </div>
</div>

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
                            <th style="width:100px">{{ __('invoices.unit_price') }}</th>
                            <th style="width:55px">{{ __('invoices.qty') }}</th>
                            <th style="width:85px">{{ __('invoices.total_amount') }}</th>
                            <th style="width:85px">{{ __('invoices.discount_rate') }}</th>
                            <th style="width:100px">{{ __('invoices.discounted_price') }}</th>
                            <th style="width:100px">{{ __('invoices.actual_paid') }}</th>
                            <th style="width:85px">{{ __('invoices.arrears') }}</th>
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
                    <div class="summary-item">
                        <span class="summary-label">{{ __('invoices.total_original') }}:</span>
                        <span class="summary-value" id="summaryOriginal">0.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">{{ __('invoices.total_discounted') }}:</span>
                        <span class="summary-value" id="summaryDiscounted">0.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">{{ __('invoices.total_actual') }}:</span>
                        <span class="summary-value summary-total" id="summaryActual">0.00</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">{{ __('invoices.total_arrears') }}:</span>
                        <span class="summary-value text-danger" id="summaryArrears">0.00</span>
                    </div>
                    <div class="summary-item order-discount-item">
                        <span class="summary-label">{{ __('invoices.order_discount_label') }}:</span>
                        <input type="number" id="orderDiscountRate" class="form-control input-sm"
                               value="100" min="0" max="100" step="1">
                        <span>%</span>
                    </div>
                    <div class="summary-item round-off-item">
                        <span class="summary-label">{{ __('invoices.round_off') }}:</span>
                        <input type="number" id="billingRoundOff" class="form-control input-sm"
                               value="0" min="0" step="0.01">
                        <button type="button" class="btn btn-xs btn-default" id="btnQuickRoundOff"
                                title="{{ __('invoices.round_off_to_yuan') }}">
                            {{ __('invoices.round_off_to_yuan') }}
                        </button>
                    </div>
                </div>
                <div class="discount-approval-warning" id="discountApprovalWarning" style="display:none">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span>{{ __('invoices.discount_approval_required') }}</span>
                </div>
            </div>

            {{-- 剩余项目：已收费、还没做完的部分。
                 放在历史欠费上面、收款按钮上方，是因为前台每次收钱前最该先看一眼
                 「这个人是不是还有买过没做完的次数」—— 不然会把已经收过钱的项目
                 再收一遍。核销按钮就地给，省得再跳一个页面。 --}}
            @can('view-invoices')
                {{-- 核销要 edit-invoices（把服务兑现掉＝减少诊所负债）。
                     只看得见余量的人不该有核销按钮 —— 后端也会拦，但按钮先别给。 --}}
                <div class="billing-prepaid" id="billingPrepaidSection" style="display:none"
                     data-can-redeem="{{ auth()->user()->can('edit-invoices') ? 1 : 0 }}">
                    <div class="billing-prepaid-header">
                        <span class="billing-prepaid-title">
                            <i class="fa fa-ticket"></i>
                            {{ __('prepaid.title') }}
                            <em class="billing-prepaid-count" id="billingPrepaidCount"></em>
                        </span>
                        <span class="billing-prepaid-sum">
                            <em>{{ __('prepaid.summary_prepaid') }}</em>
                            <strong class="text-danger" id="billingPrepaidUnearned">¥0.00</strong>
                        </span>
                        <button type="button" class="btn btn-xs btn-default billing-prepaid-toggle"
                                id="billingPrepaidToggle"><i class="fa fa-chevron-up"></i></button>
                    </div>
                    <div class="billing-prepaid-body" id="billingPrepaidBody">
                        <table class="billing-prepaid-table">
                            <thead>
                                <tr>
                                    <th>{{ __('prepaid.service') }}</th>
                                    <th style="width:150px">{{ __('prepaid.invoice_no') }}</th>
                                    <th style="width:60px">{{ __('prepaid.total_qty') }}</th>
                                    <th style="width:60px">{{ __('prepaid.used_qty') }}</th>
                                    <th style="width:60px">{{ __('prepaid.remaining_qty') }}</th>
                                    <th style="width:100px">{{ __('prepaid.prepaid_value') }}</th>
                                    <th style="width:150px">{{ __('prepaid.action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="billingPrepaidBd"></tbody>
                        </table>
                        <div class="billing-prepaid-hint text-muted">
                            <i class="fa fa-info-circle"></i> {{ __('prepaid.summary_hint') }}
                        </div>
                    </div>
                </div>
            @endcan

            {{-- 历史欠费：可勾选并入本次收款（收费成功后再按勾选项补收） --}}
            @can('collect-payments')
                <div class="billing-outstanding" id="billingOutstandingSection" style="display:none">
                    <div class="billing-outstanding-header">
                        <label class="billing-outstanding-select-all">
                            <input type="checkbox" id="billingOutstandingSelectAll">
                            <span>{{ __('invoices.include_outstanding') }}</span>
                        </label>
                        <span class="billing-outstanding-sum">
                            <em>{{ __('invoices.selected_outstanding') }}</em>
                            <strong id="billingOutstandingSelectedSum">¥0.00</strong>
                        </span>
                    </div>
                    <ul class="billing-outstanding-list" id="billingOutstandingList"></ul>
                    <div class="billing-outstanding-hint text-muted">
                        <i class="fa fa-info-circle"></i> {{ __('invoices.include_outstanding_hint') }}
                    </div>
                </div>
            @endcan

            {{-- Payment —— 只给有收款权限的人。
                 医生（只有 create-invoices）看到的是一个没有收款输入的划价面板，
                 配下面的「转前台收费」用。后端 createBilling 会再判一次，
                 隐藏这块只是别让人填了半天再吃 403。 --}}
            @can('collect-payments')
                <div class="billing-payment">
                    <div class="payment-rows" id="paymentRows">
                        <div class="payment-row" data-index="0">
                            <select class="form-control input-sm payment-method-select" data-index="0">
                                <option value="Cash">{{ __('invoices.cash') }}</option>
                                <option value="WeChat">{{ __('invoices.wechat_pay') }}</option>
                                <option value="Alipay">{{ __('invoices.alipay') }}</option>
                                <option value="BankCard">{{ __('invoices.bank_card') }}</option>
                                <option value="Insurance">{{ __('invoices.insurance') }}</option>
                                <option value="Cheque">{{ __('invoices.cheque') }}</option>
                                <option value="StoredValue">{{ __('invoices.stored_value') }}</option>
                                <option value="Self Account">{{ __('invoices.self_account') }}</option>
                            </select>
                            <input type="number" class="form-control input-sm payment-amount-input"
                                   data-index="0" placeholder="{{ __('invoices.amount') }}"
                                   step="0.01" min="0">
                            {{-- Conditional fields for Cheque/Insurance/Self Account --}}
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
            @endcan

            {{-- Actions --}}
            <div class="billing-actions">
                {{-- 收费 / 收费并打印 = 当场收钱，走 collect-payments。
                     转前台收费只是把账单开出来（billing_mode=front_desk，paid_amount 0），
                     属于划价的一部分，谁能开单谁就能转。 --}}
                @can('collect-payments')
                    <button type="button" class="btn btn-primary" id="btnCharge">
                        <i class="fa fa-check"></i> {{ __('invoices.charge') }}
                    </button>
                    <button type="button" class="btn btn-success" id="btnChargeAndPrint">
                        <i class="fa fa-print"></i> {{ __('invoices.charge_and_print') }}
                    </button>
                @endcan
                <button type="button" class="btn btn-warning" id="btnFrontDesk">
                    <i class="fa fa-clock-o"></i> {{ __('invoices.front_desk_billing') }}
                </button>
                @cannot('collect-payments')
                    {{-- 没有收款权限时页面上只剩一个按钮，说明一句为什么，
                         否则医生会以为「收费」功能坏了。 --}}
                    <span class="help-block billing-no-collect-hint">
                        <i class="fa fa-info-circle"></i> {{ __('invoices.no_collect_permission_hint') }}
                    </span>
                @endcannot
                <div class="back-entry-area">
                    <label>
                        <input type="checkbox" id="backEntryCheck"> {{ __('invoices.back_entry') }}
                    </label>
                    <input type="text" id="backEntryDate" class="form-control input-sm js-date"
                           style="display:none" value="{{ date('Y-m-d') }}" autocomplete="off">
                </div>
            </div>
        </div>
    </div>
</div>
