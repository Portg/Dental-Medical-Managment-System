<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Controller Messages Language Lines
    |--------------------------------------------------------------------------
    |
    | Messages returned by controllers for various operations
    |
    */
    // Success Messages
    'operation_successful' => 'Operation successful!',
    'data_saved_successfully' => 'Data saved successfully!',
    'data_updated_successfully' => 'Data updated successfully!',
    'data_deleted_successfully' => 'Data deleted successfully!',
    'data_deleted' => 'Data Deleted',
    'record_created_successfully' => 'Record created successfully!',
    'record_updated_successfully' => 'Record updated successfully!',
    'record_deleted_successfully' => 'Record deleted successfully!',

    // Message Messages
    'message_added_successfully' => 'Message added successfully',
    'message_updated_successfully' => 'Message updated successfully',
    'message_deleted_successfully' => 'Message deleted successfully',

    // Patient Messages
    'patient_added_successfully' => 'Patient added successfully!',
    'patient_updated_successfully' => 'Patient updated successfully!',
    'patient_deleted_successfully' => 'Patient deleted successfully!',

    // Appointment Messages
    'appointment_created_successfully' => 'Appointment created successfully!',
    'appointment_updated_successfully' => 'Appointment updated successfully!',
    'appointment_deleted_successfully' => 'Appointment deleted successfully!',
    'appointment_confirmed_successfully' => 'Appointment confirmed successfully!',
    'appointment_cancelled_successfully' => 'Appointment cancelled successfully!',
    'appointment_status_updated' => 'Appointment has been saved as :status',

    // Invoice Messages
    'invoice_created_successfully' => 'Invoice created successfully!',
    'invoice_updated_successfully' => 'Invoice updated successfully!',
    'invoice_deleted_successfully' => 'Invoice deleted successfully!',
    'invoice_sent_successfully' => 'Invoice sent successfully!',

    // Payment Messages
    'payment_recorded_successfully' => 'Payment recorded successfully!',
    'payment_updated_successfully' => 'Payment updated successfully!',
    'payment_deleted_successfully' => 'Payment deleted successfully!',
    'payment_received_successfully' => 'Payment received successfully!',

    // Prescription Messages
    'prescription_created_successfully' => 'Prescription created successfully!',
    'prescription_updated_successfully' => 'Prescription updated successfully!',
    'prescription_deleted_successfully' => 'Prescription deleted successfully!',

    // User Messages
    'user_created_successfully' => 'User created successfully!',
    'user_updated_successfully' => 'User updated successfully!',
    'user_deleted_successfully' => 'User deleted successfully!',
    'password_changed_successfully' => 'Password changed successfully!',
    'profile_updated_successfully' => 'Profile updated successfully!',

    // SMS Messages
    'sms_sent_successfully' => 'SMS sent successfully!',
    'sms_scheduled_successfully' => 'SMS scheduled successfully!',

    // Email Messages
    'email_sent_successfully' => 'Email sent successfully!',

    // Error Messages
    'operation_failed' => 'Operation failed!',
    'error_occurred' => 'Oops error has occurred, please try again',
    'error_occurred_later' => 'Oops error has occurred, please try again later',

    'oops_error_occurred' => 'Oops! An error occurred. Please try again.',
    'something_went_wrong' => 'Something went wrong. Please try again.',
    'could_not_process_request' => 'Could not process request.',
    'invalid_request' => 'Invalid request.',
    'unauthorized_access' => 'Unauthorized access.',
    'permission_denied' => 'Permission denied to perform this action.',
    'access_denied' => 'Access denied.',
    'not_found' => 'The requested resource was not found.',
    'record_not_found' => 'Record not found.',
    'data_not_found' => 'Data not found.',

    // Validation Messages
    'validation_error' => 'Validation error.',
    'please_check_input' => 'Please check your input.',
    'required_fields_missing' => 'Required fields are missing.',
    'invalid_input' => 'Invalid input.',
    'invalid_data' => 'Invalid data.',
    'duplicate_entry' => 'Duplicate entry.',
    'already_exists' => 'Already exists.',

    // Specific Error Messages
    'patient_not_found' => 'Patient not found.',
    'appointment_not_found' => 'Appointment not found.',
    'invoice_not_found' => 'Invoice not found.',
    'user_not_found' => 'User not found.',
    'record_has_dependencies' => 'This record has associated data and cannot be deleted.',
    'cannot_delete_record' => 'Cannot delete record.',

    // Database Errors
    'database_error' => 'Database error.',
    'connection_error' => 'Connection error.',
    'query_failed' => 'Query failed.',

    // File Upload Messages
    'file_uploaded_successfully' => 'File uploaded successfully!',
    'file_upload_failed' => 'File upload failed.',
    'invalid_file_type' => 'Invalid file type.',
    'file_too_large' => 'File is too large.',
    'file_not_found' => 'File not found.',

    // Authentication Messages
    'login_successful' => 'Login successful!',
    'logout_successful' => 'Logout successful!',
    'invalid_credentials' => 'Invalid credentials.',
    'account_disabled' => 'Account is disabled.',
    'account_locked' => 'Account is locked.',
    'session_expired' => 'Session has expired.',

    // Confirmation Messages
    'are_you_sure' => 'Are you sure?',
    'confirm_delete' => 'Are you sure you want to delete?',
    'confirm_action' => 'Are you sure you want to perform this action?',
    'action_cannot_be_undone' => 'This action cannot be undone!',
    'please_confirm' => 'Please confirm.',

    // Status Messages
    'status_updated_successfully' => 'Status updated successfully!',
    'activated_successfully' => 'Activated successfully!',
    'deactivated_successfully' => 'Deactivated successfully!',
    'enabled_successfully' => 'Enabled successfully!',
    'disabled_successfully' => 'Disabled successfully!',

    // Notification Messages
    'notification_sent' => 'Notification sent.',
    'reminder_sent' => 'Reminder sent.',
    'alert_sent' => 'Alert sent.',

    // No Data Messages
    'no_data_available' => 'No data available.',
    'no_records_found' => 'No records found.',
    'no_results_found' => 'No results found.',
    'empty_list' => 'List is empty.',

    // Process Messages
    'processing' => 'Processing...',
    'please_wait' => 'Please wait...',
    'loading' => 'Loading...',
    'saving' => 'Saving...',
    'updating' => 'Updating...',
    'deleting' => 'Deleting...',
    'sending' => 'Sending...',

    // Completion Messages
    'process_completed' => 'Process completed.',
    'task_completed' => 'Task completed.',
    'action_completed' => 'Action completed.',

    // Warning Messages
    'warning' => 'Warning',
    'caution' => 'Caution',
    'please_note' => 'Please note',
    'important' => 'Important',

    // Info Messages
    'info' => 'Info',
    'note' => 'Note',
    'tip' => 'Tip',
    'help' => 'Help',

    // Specific Module Messages
    // Appointment Messages (additional)
    'appointment_rescheduled_successfully' => 'Patient Appointment has been rescheduled successfully',
    'no_invoice_yet' => 'No Invoice Yet',
    'invoice_already_generated' => 'Invoice Already Generated',

    // User Messages (additional)
    'user_registered_successfully' => 'User has been registered successfully',

    // Accounting Messages
    'accounting_equation_added_successfully' => 'Accounting equation has been added successfully',
    'accounting_equation_updated_successfully' => 'Accounting equation has been updated successfully',
    'accounting_equation_deleted_successfully' => 'Accounting equation has been deleted successfully',

    // Salary Messages
    'salary_deduction_added_successfully' => 'Employee salary deduction has been added successfully',
    'salary_deduction_updated_successfully' => 'Employee salary deduction has been updated successfully',
    'salary_deduction_deleted_successfully' => 'Employee salary deduction has been deleted successfully',
    'salary_allowance_added_successfully' => 'Employee allowance has been added successfully',
    'salary_allowance_updated_successfully' => 'Employee allowance has been updated successfully',
    'salary_allowance_deleted_successfully' => 'Employee allowance has been deleted successfully',

    // Leave Type Messages
    'leave_type_added_successfully' => 'Leave type has been added successfully',
    'leave_type_updated_successfully' => 'Leave type has been updated successfully',
    'leave_type_deleted_successfully' => 'Leave type has been deleted successfully',

    // Online Booking Messages
    'booking_approved_successfully' => 'Appointment booking has been approved successfully',
    'booking_rejected_successfully' => 'Booking has been rejected successfully',
    'booking_request_sent_successfully' => 'Your appointment request has been sent successfully',

    // Chart of Account Messages
    'chart_account_category_added_successfully' => 'Chart account category has been added successfully',
    'chart_account_category_updated_successfully' => 'Chart account category has been updated successfully',
    'chart_account_category_deleted_successfully' => 'Chart account category has been deleted successfully',

    // Chronic Disease Messages
    'chronic_disease_added_successfully' => 'Chronic Disease has been added successfully',
    'chronic_disease_updated_successfully' => 'Chronic Disease has been updated successfully',
    'chronic_disease_deleted_successfully' => 'Chronic Disease has been deleted successfully',

    // Allergy Messages
    'allergy_added_successfully' => 'Allergy has been added successfully',
    'allergy_updated_successfully' => 'Allergy has been updated successfully',
    'allergy_deleted_successfully' => 'Allergy has been deleted successfully',

    // Surgery Messages
    'surgery_added_successfully' => 'Surgery has been added successfully',
    'surgery_updated_successfully' => 'Surgery has been updated successfully',
    'surgery_deleted_successfully' => 'Surgery has been deleted successfully',

    // Branch Messages
    'branch_added_successfully' => 'Branch has been added successfully',
    'branch_updated_successfully' => 'Branch has been updated successfully',
    'branch_deleted_successfully' => 'Branch has been deleted successfully',

    // Expense Messages
    'expense_added_successfully' => 'Expense has been added successfully',
    'expense_updated_successfully' => 'Expense has been updated successfully',
    'expense_deleted_successfully' => 'Expense has been deleted successfully',
    'expense_category_added_successfully' => 'Expense category has been added successfully',
    'expense_category_updated_successfully' => 'Expense category has been updated successfully',
    'expense_category_deleted_successfully' => 'Expense category has been deleted successfully',
    'expense_item_added_successfully' => 'Expense item has been added successfully',
    'expense_item_updated_successfully' => 'Expense item has been updated successfully',
    'expense_item_deleted_successfully' => 'Expense item has been deleted successfully',

    // Quotation Messages
    'quotation_added_successfully' => 'Quotation has been added successfully',
    'quotation_updated_successfully' => 'Quotation has been updated successfully',
    'quotation_deleted_successfully' => 'Quotation has been deleted successfully',
    'quotation_sent_successfully' => 'Quotation sent successfully',

    // Permission/Role Messages
    'permission_added_successfully' => 'Permission has been added successfully',
    'permission_updated_successfully' => 'Permission has been updated successfully',
    'permission_deleted_successfully' => 'Permission has been deleted successfully',
    'role_permission_added_successfully' => 'Role permission has been added successfully',
    'role_permission_updated_successfully' => 'Role permission has been updated successfully',
    'role_permission_deleted_successfully' => 'Role permission has been deleted successfully',

    // Confirmation Messages (additional)
    'confirm_save_changes' => 'Are you sure you want to save the changes?',
    'confirm_delete_permission' => 'Are you sure you want to delete this permission?',
    'confirm_delete_role_permission' => 'Are you sure you want to delete this role permission?',
    'confirm_delete_data' => 'Are you sure you want to delete this data?',
    'please_select_checkbox' => 'Please select at least one checkbox',
    'cannot_recover_expense' => 'You will not be able to recover this Expense!',

    // Medical Card Messages
    'medical_card_added_successfully' => 'Medical card has been added successfully',
    'medical_card_updated_successfully' => 'Medical card has been updated successfully',
    'medical_card_deleted_successfully' => 'Medical card has been deleted successfully',

    // Treatment Messages
    'treatment_added_successfully' => 'Treatment has been added successfully',
    'treatment_updated_successfully' => 'Treatment has been updated successfully',
    'treatment_deleted_successfully' => 'Treatment has been deleted successfully',

    // Supplier Messages
    'supplier_added_successfully' => 'Supplier has been added successfully',
    'supplier_updated_successfully' => 'Supplier has been updated successfully',
    'supplier_deleted_successfully' => 'Supplier has been deleted successfully',

    // Role Messages
    'role_added_successfully' => 'Role has been added successfully',
    'role_updated_successfully' => 'Role has been updated successfully',
    'role_deleted_successfully' => 'Role has been deleted successfully',

    // Holiday Messages
    'holiday_added_successfully' => 'Holiday has been added successfully',
    'holiday_updated_successfully' => 'Holiday has been updated successfully',
    'holiday_deleted_successfully' => 'Holiday has been deleted successfully',

    // Insurance Messages
    'insurance_company_added_successfully' => 'Insurance company has been added successfully',
    'insurance_company_updated_successfully' => 'Insurance company has been updated successfully',
    'insurance_company_deleted_successfully' => 'Insurance company has been deleted successfully',

    // Contract Messages
    'contract_added_successfully' => 'Contract has been added successfully',
    'contract_updated_successfully' => 'Contract has been updated successfully',
    'contract_deleted_successfully' => 'Contract has been deleted successfully',

    // Leave Request Messages
    'leave_request_submitted_successfully' => 'Leave request has been submitted successfully',
    'leave_request_approved_successfully' => 'Leave request has been approved successfully',
    'leave_request_rejected_successfully' => 'Leave request has been rejected successfully',

    // Claim Messages
    'claim_added_successfully' => 'Claim has been added successfully',
    'claim_updated_successfully' => 'Claim has been updated successfully',
    'claim_deleted_successfully' => 'Claim has been deleted successfully',
    'claim_rate_added_successfully' => 'Claim rate has been added successfully',
    'claim_rate_updated_successfully' => 'Claim rate has been updated successfully',
    'claim_rate_deleted_successfully' => 'Claim rate has been deleted successfully',

    // Service Messages
    'service_added_successfully' => 'Service has been added successfully',
    'service_updated_successfully' => 'Service has been updated successfully',
    'service_deleted_successfully' => 'Service has been deleted successfully',

    // Self Account Messages
    'deposit_added_successfully' => 'Deposit has been added successfully',
    'deposit_updated_successfully' => 'Deposit has been updated successfully',
    'deposit_deleted_successfully' => 'Deposit has been deleted successfully',
    'self_account_added_successfully' => 'Self account has been added successfully',
    'self_account_updated_successfully' => 'Self account has been updated successfully',
    'self_account_deleted_successfully' => 'Self account has been deleted successfully',

    // Salary Advance Messages
    'salary_advance_added_successfully' => 'Salary advance has been added successfully',
    'salary_advance_updated_successfully' => 'Salary advance has been updated successfully',
    'salary_advance_deleted_successfully' => 'Salary advance has been deleted successfully',

    // Payslip Messages
    'payslip_added_successfully' => 'Payslip has been added successfully',
    'payslip_updated_successfully' => 'Payslip has been updated successfully',
    'payslip_deleted_successfully' => 'Payslip has been deleted successfully',

    // Generic record messages (for controllers)
    'record_updated' => 'Record has been updated successfully',
    'record_deleted' => 'Record has been deleted successfully',
    'error_try_again' => 'Oops, an error has occurred. Please try again later',

    // Medical Template Messages
    'template_created_successfully' => 'Template has been created successfully',
    'template_updated_successfully' => 'Template has been updated successfully',
    'template_deleted_successfully' => 'Template has been deleted successfully',

    // Quick Phrase Messages
    'phrase_created_successfully' => 'Phrase has been created successfully',
    'phrase_updated_successfully' => 'Phrase has been updated successfully',
    'phrase_deleted_successfully' => 'Phrase has been deleted successfully',

    // Patient Tag Messages
    'tag_created_successfully' => 'Tag has been created successfully',
    'tag_updated_successfully' => 'Tag has been updated successfully',
    'tag_deleted_successfully' => 'Tag has been deleted successfully',

    // Patient Source Messages
    'source_created_successfully' => 'Source has been created successfully',
    'source_updated_successfully' => 'Source has been updated successfully',
    'source_deleted_successfully' => 'Source has been deleted successfully',
    'source_in_use' => 'Cannot delete this source as it is being used by patients',

];