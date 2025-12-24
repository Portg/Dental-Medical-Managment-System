<?php

return [
    // Page titles
    'title' => 'Invoicing / Billing',
    'quotations' => 'Billing / Quotations',

    // Table headers
    'table' => [
        'invoice_no' => 'Invoice No',
        'quotation_no' => 'Quotation No',
        'date' => 'Date',
        'customer' => 'Customer',
        'total_amount' => 'Total Amount',
        'paid_amount' => 'Paid Amount',
        'outstanding' => 'Outstanding',
    ],

    // Buttons
    'buttons' => [
        'filter_invoices' => 'Filter Invoices',
        'filter_quotations' => 'Filter Quotations',
        'share_invoice' => 'Share Invoice',
        'share_quotation' => 'Share Quotation',
        'print' => 'Print',
        'preview' => 'Preview',
    ],

    // Payment Form
    'payment' => [
        'title' => 'Record a payment for this invoice',
        'payment_date' => 'Payment Date',
        'amount' => 'Amount',
        'payment_method' => 'Payment Method',
        'methods' => [
            'cash' => 'Cash',
            'insurance' => 'Insurance',
            'online_wallet' => 'Online Wallet',
            'mobile_money' => 'Mobile Money',
            'cheque' => 'Cheque',
            'self_account' => 'Self Account',
        ],
        'cheque_no' => 'Cheque No',
        'account_name' => 'Account Name',
        'bank_name' => 'Bank Name',
    ],

    // Dashboard
    'dashboard' => [
        'todays_cash' => "Today's Cash (Amount)",
        'todays_insurance' => "Today's Insurance (Amount)",
    ],

    // Notifications
    'notifications' => [
        'email_sent' => 'Email Sent Invoice/Quotations',
    ],

    // Alerts
    'alerts' => [
        'delete_confirm' => 'Your will not be able to recover this invoice!',
    ],
];
