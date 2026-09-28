<?php

return [
    // Panel
    'title'              => 'Remaining items',
    'subtitle'           => 'Charged but not yet delivered',
    'empty'              => 'This patient has no outstanding prepaid items',
    'loading'            => 'Loading remaining items…',

    // Table
    'service'            => 'Item',
    'invoice_no'         => 'Invoice',
    'invoice_date'       => 'Charged on',
    'tooth_no'           => 'Tooth',
    'total_qty'          => 'Bought',
    'used_qty'           => 'Used',
    'remaining_qty'      => 'Left',
    'remaining_value'    => 'Value left',
    'prepaid_value'      => 'Prepaid unearned',
    'arrears'            => 'Row arrears',
    'action'             => 'Action',

    // Summary
    'summary_items'      => 'Open items',
    'summary_qty'        => 'Sessions left',
    'summary_value'      => 'Value left',
    'summary_prepaid'    => 'Prepaid unearned',
    'summary_hint'       => '"Prepaid unearned" is money already collected for services still owed.',

    // Consumption
    'consume'            => 'Use one',
    'consume_n'          => 'Redeem',
    'consume_title'      => 'Redeem prepaid item',
    'consume_qty'        => 'Quantity to redeem',
    'consume_notes'      => 'Notes',
    'consume_submit'     => 'Confirm',
    'consume_success'    => 'Redeemed',
    'revoke'             => 'Undo',
    'revoke_success'     => 'Redemption undone',
    'revoke_confirm'     => 'Undo this redemption? The balance will be added back.',

    // History
    'history'            => 'Redemption history',
    'history_empty'      => 'No redemptions yet',
    'history_date'       => 'Date',
    'history_qty'        => 'Qty',
    'history_doctor'     => 'Doctor',
    'history_notes'      => 'Notes',

    // Errors
    'qty_must_be_positive' => 'Quantity must be greater than 0',
    'item_not_trackable'   => 'This charge is not a per-session tracked item',
    'exceeds_remaining'    => 'Exceeds the balance; at most :remaining left',
    'usage_not_found'      => 'Redemption record not found',

    // Service maintenance
    'track_delivery'       => 'Track per session',
    'track_delivery_hint'  => 'When enabled, charging this item adds it to "Remaining items" and each delivery is redeemed against it. Use for session cards, courses of treatment and full orthodontic plans.',
];
