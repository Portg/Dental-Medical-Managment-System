<?php

return [

    /**
     * Deposits Language Lines
     * --------------------------------------------------------------------------
     * The following language lines are used for deposit management.
     * You are free to modify these language lines according to your application's requirements.
     */

    // Page Titles
    'deposits' => 'Deposits',
    'deposit' => 'Deposit',
    'deposit_management' => 'Deposit Management',
    'deposit_list' => 'Deposit List',
    'deposit_details' => 'Deposit Details',
    'add_deposit' => 'Add Deposit',
    'new_deposit' => 'New Deposit',
    'create_deposit' => 'Create Deposit',
    'record_deposit' => 'Record Deposit',
    'edit_deposit' => 'Edit Deposit',
    'view_deposit' => 'View Deposit',
    'deposit_history' => 'Deposit History',
    'deposit_form' => 'Self Account Deposit Form',

    // Form Labels
    'deposit_date' => 'Deposit Date',
    'deposit_amount' => 'Deposit Amount',
    'amount' => 'Amount',
    'depositor' => 'Depositor',
    'depositor_name' => 'Depositor Name',
    'patient_name' => 'Patient Name',
    'patient_id' => 'Patient ID',
    'account_number' => 'Account Number',
    'reference_number' => 'Reference Number',
    'transaction_id' => 'Transaction ID',
    'deposit_method' => 'Deposit Method',
    'payment_method' => 'Payment Method',
    'description' => 'Description',
    'notes' => 'Notes',
    'remarks' => 'Remarks',
    'purpose' => 'Purpose',

    // Deposit Methods
    'cash' => 'Cash',
    'bank_transfer' => 'Bank Transfer',
    'cheque' => 'Cheque',
    'credit_card' => 'Credit Card',
    'debit_card' => 'Debit Card',
    'mobile_money' => 'Mobile Money',
    'online_payment' => 'Online Payment',
    'wire_transfer' => 'Wire Transfer',

    // Deposit Types
    'deposit_type' => 'Deposit Type',
    'treatment_deposit' => 'Treatment Deposit',
    'advance_payment' => 'Advance Payment',
    'security_deposit' => 'Security Deposit',
    'refundable_deposit' => 'Refundable Deposit',
    'non_refundable_deposit' => 'Non-Refundable Deposit',
    'package_deposit' => 'Package Deposit',

    // Bank Details
    'bank_details' => 'Bank Details',
    'bank_name' => 'Bank Name',
    'account_name' => 'Account Name',
    'cheque_number' => 'Cheque Number',
    'cheque_date' => 'Cheque Date',
    'branch_name' => 'Branch Name',

    // Status
    'status' => 'Status',
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'cleared' => 'Cleared',
    'failed' => 'Failed',
    'cancelled' => 'Cancelled',
    'refunded' => 'Refunded',
    'partially_refunded' => 'Partially Refunded',

    // Table Headers
    'id' => 'ID',
    'date' => 'Date',
    'patient' => 'Patient',
    'method' => 'Method',
    'actions' => 'Actions',

    // Actions
    'view_details' => 'View Details',
    'confirm_deposit' => 'Confirm Deposit',
    'cancel_deposit' => 'Cancel Deposit',
    'refund_deposit' => 'Refund Deposit',
    'print_receipt' => 'Print Receipt',
    'download_receipt' => 'Download Receipt',
    'email_receipt' => 'Email Receipt',
    'delete_deposit' => 'Delete Deposit',

    // Placeholders
    'enter_amount' => 'Enter amount',
    'enter_patient_name' => 'Enter patient name',
    'enter_reference_number' => 'Enter reference number',
    'enter_description' => 'Enter description',
    'select_patient' => 'Select patient',
    'select_deposit_method' => 'Select deposit method',
    'select_account' => 'Select account',
    'choose_patient' => 'Choose patient...',

    // Messages
    'deposit_recorded_successfully' => 'Deposit recorded successfully',
    'deposit_updated_successfully' => 'Deposit updated successfully',
    'deposit_deleted_successfully' => 'Deposit deleted successfully',
    'deposit_confirmed_successfully' => 'Deposit confirmed successfully',
    'deposit_cancelled_successfully' => 'Deposit cancelled successfully',
    'deposit_refunded_successfully' => 'Deposit refunded successfully',
    'receipt_sent_successfully' => 'Receipt sent successfully',
    'confirm_delete_deposit' => 'Are you sure you want to delete this deposit?',
    'confirm_cancel_deposit' => 'Are you sure you want to cancel this deposit?',
    'confirm_refund_deposit' => 'Are you sure you want to refund this deposit?',
    'deposit_not_found' => 'Deposit not found',
    'error_recording_deposit' => 'Error recording deposit',
    'error_updating_deposit' => 'Error updating deposit',
    'error_deleting_deposit' => 'Error deleting deposit',
    'no_deposits_found' => 'No deposits found',
    'insufficient_balance_for_refund' => 'Insufficient balance for refund',

    // Search & Filter
    'search_deposits' => 'Search Deposits',
    'filter_deposits' => 'Filter Deposits',
    'filter_by_patient' => 'Filter by Patient',
    'filter_by_status' => 'Filter by Status',
    'filter_by_method' => 'Filter by Method',
    'filter_by_date' => 'Filter by Date',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'show_all' => 'Show All',

    // Reports & Statistics
    'total_deposits' => 'Total Deposits',
    'total_amount' => 'Total Amount',
    'deposits_today' => 'Deposits Today',
    'deposits_this_week' => 'Deposits This Week',
    'deposits_this_month' => 'Deposits This Month',
    'deposits_report' => 'Deposits Report',
    'deposits_by_method' => 'Deposits by Method',

    // Receipt
    'deposit_receipt' => 'Deposit Receipt',
    'receipt_number' => 'Receipt Number',
    'received_from' => 'Received From',
    'received_by' => 'Received By',
    'payment_for' => 'Payment For',
    'thank_you' => 'Thank You',

    // Refund
    'refund' => 'Refund',
    'refund_amount' => 'Refund Amount',
    'refund_date' => 'Refund Date',
    'refund_reason' => 'Refund Reason',
    'partial_refund' => 'Partial Refund',
    'full_refund' => 'Full Refund',
    'refund_method' => 'Refund Method',
    'refunded_amount' => 'Refunded Amount',
    'remaining_balance' => 'Remaining Balance',

    // Validation
    'amount_required' => 'Amount is required',
    'patient_required' => 'Patient is required',
    'method_required' => 'Deposit method is required',
    'invalid_amount' => 'Invalid amount',
    'amount_must_be_positive' => 'Amount must be positive',
    'refund_amount_exceeds_deposit' => 'Refund amount exceeds deposit amount',

    // Controller messages
    'deposit_success' => 'Money has been deposited successfully on the self account',

];
