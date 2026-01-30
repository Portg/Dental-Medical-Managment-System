<?php

return [

    /**
     * SMS Language Lines
     * --------------------------------------------------------------------------
     * The following language lines are used for SMS management.
     * You are free to modify these language lines according to your application's requirements.
     */

    // Page Titles
    'sms' => 'SMS',
    'sms_management' => 'SMS Management',
    'send_sms' => 'Send SMS',
    'sms_history' => 'SMS History',
    'sms_logs' => 'SMS Logs',
    'sms_templates' => 'SMS Templates',
    'sms_settings' => 'SMS Settings',
    'bulk_sms' => 'Bulk SMS',
    'compose_sms' => 'Compose SMS',

    // Form Labels
    'recipient' => 'Recipient',
    'recipients' => 'Recipients',
    'phone_number' => 'Phone Number',
    'message' => 'Message',
    'message_text' => 'Message Text',
    'sender_id' => 'Sender ID',
    'sender_name' => 'Sender Name',
    'template' => 'Template',
    'template_name' => 'Template Name',
    'select_template' => 'Select Template',
    'use_template' => 'Use Template',

    // SMS Types
    'appointment_reminder' => 'Appointment Reminder',
    'payment_reminder' => 'Payment Reminder',
    'birthday_wishes' => 'Birthday Wishes',
    'follow_up' => 'Follow-up',
    'notification' => 'Notification',
    'promotional' => 'Promotional',
    'custom' => 'Custom',

    // Recipients Selection
    'select_recipients' => 'Select Recipients',
    'all_patients' => 'All Patients',
    'patients_with_appointments' => 'Patients with Appointments',
    'patients_with_outstanding_balance' => 'Patients with Outstanding Balance',
    'birthday_today' => 'Birthday Today',
    'custom_selection' => 'Custom Selection',
    'select_patients' => 'Select Patients',
    'add_recipient' => 'Add Recipient',
    'remove_recipient' => 'Remove Recipient',

    // Message Details
    'characters' => 'Characters',
    'character_count' => 'Character Count',
    'message_parts' => 'Message Parts',
    'estimated_cost' => 'Estimated Cost',
    'total_recipients' => 'Total Recipients',
    'message_length' => 'Message Length',

    // Status
    'status' => 'Status',
    'sent' => 'Sent',
    'pending' => 'Pending',
    'failed' => 'Failed',
    'delivered' => 'Delivered',
    'undelivered' => 'Undelivered',
    'queued' => 'Queued',

    // Table Headers
    'id' => 'ID',
    'date' => 'Date',
    'time' => 'Time',
    'recipient_name' => 'Recipient Name',
    'recipient_number' => 'Recipient Number',
    'message_preview' => 'Message Preview',
    'sent_by' => 'Sent By',
    'actions' => 'Actions',

    // Actions
    'send' => 'Send',
    'send_now' => 'Send Now',
    'schedule' => 'Schedule',
    'schedule_sms' => 'Schedule SMS',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'view_details' => 'View Details',
    'resend' => 'Resend',
    'copy_message' => 'Copy Message',

    // Schedule
    'schedule_date' => 'Schedule Date',
    'schedule_time' => 'Schedule Time',
    'send_at' => 'Send At',
    'scheduled_for' => 'Scheduled For',

    // Templates
    'create_template' => 'Create Template',
    'edit_template' => 'Edit Template',
    'delete_template' => 'Delete Template',
    'template_variables' => 'Template Variables',
    'available_variables' => 'Available Variables',
    'patient_name' => 'Patient Name',
    'appointment_date' => 'Appointment Date',
    'appointment_time' => 'Appointment Time',
    'doctor_name' => 'Doctor Name',
    'clinic_name' => 'Clinic Name',
    'amount_due' => 'Amount Due',

    // Placeholders
    'enter_message' => 'Enter message',
    'enter_phone_number' => 'Enter phone number',
    'enter_template_name' => 'Enter template name',
    'type_your_message' => 'Type your message here...',
    'search_patients' => 'Search patients',

    // Messages
    'appointment_scheduled' => 'Hello, :name Your appointment at :company has been scheduled for :date at :time',
    'sms_sent_successfully' => 'SMS sent successfully',
    'sms_scheduled_successfully' => 'SMS scheduled successfully',
    'sms_failed' => 'Failed to send SMS',
    'template_created_successfully' => 'Template created successfully',
    'template_updated_successfully' => 'Template updated successfully',
    'template_deleted_successfully' => 'Template deleted successfully',
    'confirm_send_sms' => 'Are you sure you want to send this SMS?',
    'confirm_delete_template' => 'Are you sure you want to delete this template?',
    'no_recipients_selected' => 'No recipients selected',
    'invalid_phone_number' => 'Invalid phone number',
    'message_too_long' => 'Message is too long',
    'message_empty' => 'Message cannot be empty',
    'insufficient_balance' => 'Insufficient SMS balance',
    'no_sms_found' => 'No SMS records found',

    // Balance & Credits
    'sms_balance' => 'SMS Balance',
    'credits' => 'Credits',
    'credits_remaining' => 'Credits Remaining',
    'buy_credits' => 'Buy Credits',
    'topup' => 'Top Up',
    'cost_per_sms' => 'Cost per SMS',

    // Settings
    'sms_gateway' => 'SMS Gateway',
    'api_key' => 'API Key',
    'api_secret' => 'API Secret',
    'sender_id_default' => 'Default Sender ID',
    'enable_sms' => 'Enable SMS',
    'disable_sms' => 'Disable SMS',
    'test_connection' => 'Test Connection',

    // Reports & Statistics
    'sms_report' => 'SMS Report',
    'total_sent' => 'Total Sent',
    'total_delivered' => 'Total Delivered',
    'total_failed' => 'Total Failed',
    'delivery_rate' => 'Delivery Rate',
    'sms_sent_today' => 'SMS Sent Today',
    'sms_sent_this_month' => 'SMS Sent This Month',

    // Filters
    'filter_by_status' => 'Filter by Status',
    'filter_by_date' => 'Filter by Date',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'search' => 'Search',

    // Additional for outbox_sms/index page
    'sms_manager_outbox' => 'SMS Manager/ Outbox',
    'download_excel_report' => 'Download Excel Report',
    'period' => 'Period',
    'all' => 'All',
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'this_week' => 'This week',
    'last_week' => 'Last week',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'filter_sms' => 'Filter SMS',
    'clear' => 'Clear',
    'sent_date' => 'Sent Date',
    'phone_no' => 'Phone No',
    'message_type' => 'Message Type',
    'message_price' => 'Message Price (UGX)',
    'message_status' => 'Message Status',
    'loading' => 'Loading',
    'alert' => 'Alert!',

    // SMS Transactions
    'sms_transactions' => 'SMS Transactions',
    'sms_credit_loading' => 'SMS Credit Loading',
    'load_sms_credit' => 'Load SMS Credit',
    'credit_amount' => 'Credit Amount',
    'enter_credit_amount' => 'Enter credit amount',
    'transaction_reference' => 'Transaction Reference',
    'enter_reference' => 'Enter reference',
    'transaction_date' => 'Transaction Date',
    'loaded_by' => 'Loaded By',
    'save_transaction' => 'Save Transaction',
    'close' => 'Close',
    'processing' => 'Processing...',
    'save_changes' => 'Save changes',

];
