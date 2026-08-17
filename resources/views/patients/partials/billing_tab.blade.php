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
        @include('billing.partials.charge_panel')
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

{{-- ══ Right Side Panel (账单详情 / 收费单详情) ══ --}}
<div class="billing-panel-overlay" id="billingPanelOverlay"></div>

<div class="billing-side-panel" id="billingSidePanel" role="dialog" aria-modal="true">
    <div class="billing-panel-header">
        <h4 id="billingPanelTitle">{{ __('invoices.panel_invoice_detail') }}</h4>
        <button class="billing-panel-close" id="billingPanelClose" aria-label="Close">&#x2715;</button>
    </div>
    <div class="billing-panel-body" id="billingPanelBody">
        {{-- Content rendered by JS --}}
    </div>
</div>
