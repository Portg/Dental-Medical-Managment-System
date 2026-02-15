<?php

return [

    /**
     * Lab Case Management Language Lines (English)
     * --------------------------------------------------------------------------
     * The following language lines are used for lab case management.
     */

    // ─── Page Titles ────────────────────────────────────────────
    'lab_case_management' => 'Lab Case Management',
    'lab_cases'           => 'Lab Cases',
    'lab_case'            => 'Lab Case',
    'lab_case_list'       => 'Lab Case List',
    'lab_case_details'    => 'Lab Case Details',
    'add_lab_case'        => 'New Lab Case',
    'create_lab_case'     => 'Create Lab Case',
    'edit_lab_case'       => 'Edit Lab Case',
    'view_lab_case'       => 'View Lab Case',
    'lab_management'      => 'Lab Management',
    'labs'                => 'Labs',
    'lab'                 => 'Lab',
    'lab_list'            => 'Lab List',
    'add_lab'             => 'Add Lab',
    'edit_lab'            => 'Edit Lab',

    // ─── Lab Case Form ──────────────────────────────────────────
    'lab_case_no'          => 'Lab Case No.',
    'patient'              => 'Patient',
    'doctor'               => 'Doctor',
    'select_patient'       => 'Select Patient',
    'select_doctor'        => 'Select Doctor',
    'select_lab'           => 'Select Lab',
    'prosthesis_type'      => 'Prosthesis Type',
    'material'             => 'Material',
    'color_shade'          => 'Color/Shade',
    'teeth_positions'      => 'Teeth Positions',
    'special_requirements' => 'Special Requirements',
    'notes'                => 'Notes',
    'appointment'          => 'Linked Appointment',
    'medical_case'         => 'Linked Case',

    // ─── Prosthesis Types ───────────────────────────────────────
    'type_crown'           => 'Crown',
    'type_bridge'          => 'Bridge',
    'type_removable'       => 'Removable Denture',
    'type_implant'         => 'Implant',
    'type_veneer'          => 'Veneer',
    'type_inlay_onlay'     => 'Inlay/Onlay',
    'type_denture'         => 'Full Denture',
    'type_orthodontic'     => 'Orthodontic Appliance',
    'type_night_guard'     => 'Night Guard',
    'type_surgical_guide'  => 'Surgical Guide',
    'type_other'           => 'Other',

    // ─── Materials ──────────────────────────────────────────────
    'material_zirconia'    => 'Zirconia',
    'material_pfm'         => 'PFM (Porcelain-Fused-to-Metal)',
    'material_all_ceramic' => 'All-Ceramic',
    'material_emax'        => 'E.max',
    'material_composite'   => 'Composite',
    'material_metal'       => 'Metal',
    'material_acrylic'     => 'Acrylic',
    'material_titanium'    => 'Titanium',
    'material_peek'        => 'PEEK',
    'material_other'       => 'Other',

    // ─── Statuses ───────────────────────────────────────────────
    'status'               => 'Status',
    'status_pending'       => 'Pending',
    'status_sent'          => 'Sent to Lab',
    'status_in_production' => 'In Production',
    'status_returned'      => 'Returned',
    'status_try_in'        => 'Try-in',
    'status_completed'     => 'Completed',
    'status_rework'        => 'Rework',
    'update_status'        => 'Update Status',

    // ─── Dates & Fees ───────────────────────────────────────────
    'sent_date'            => 'Sent Date',
    'expected_return_date' => 'Expected Return Date',
    'actual_return_date'   => 'Actual Return Date',
    'lab_fee'              => 'Lab Fee',
    'patient_charge'       => 'Patient Charge',
    'profit'               => 'Profit',
    'overdue'              => 'Overdue',
    'overdue_cases'        => 'Overdue Cases',
    'days_overdue'         => ':days days overdue',

    // ─── Quality & Rework ───────────────────────────────────────
    'quality_rating'       => 'Quality Rating',
    'rework_count'         => 'Rework Count',
    'rework_reason'        => 'Rework Reason',
    'enter_rework_reason'  => 'Enter rework reason',

    // ─── Lab Form ───────────────────────────────────────────────
    'lab_name'             => 'Lab Name',
    'contact'              => 'Contact Person',
    'phone'                => 'Phone',
    'address'              => 'Address',
    'specialties'          => 'Specialties',
    'avg_turnaround_days'  => 'Avg. Turnaround Days',
    'is_active'            => 'Active',

    // ─── Table Headers ──────────────────────────────────────────
    'id'                   => 'ID',
    'actions'              => 'Actions',
    'created_at'           => 'Created At',
    'added_by'             => 'Added By',
    'lab_name_header'      => 'Lab',
    'patient_name'         => 'Patient',
    'doctor_name'          => 'Doctor',

    // ─── Statistics ─────────────────────────────────────────────
    'statistics'           => 'Statistics',
    'total_cases'          => 'Total Cases',
    'active_cases'         => 'Active',
    'completed_cases'      => 'Completed',
    'rework_cases'         => 'Rework',
    'overdue_count'        => 'Overdue',
    'total_lab_fee'        => 'Total Lab Fees',
    'total_patient_charge' => 'Total Patient Charges',
    'total_profit'         => 'Total Profit',

    // ─── Action Messages ────────────────────────────────────────
    'case_created'         => 'Lab case created successfully',
    'case_updated'         => 'Lab case updated successfully',
    'case_deleted'         => 'Lab case deleted successfully',
    'status_updated'       => 'Status updated successfully',
    'case_not_found'       => 'Lab case not found',
    'error_creating_case'  => 'Error creating lab case',
    'error_updating_case'  => 'Error updating lab case',
    'error_deleting_case'  => 'Error deleting lab case',
    'error_updating_status' => 'Error updating status',
    'lab_created'          => 'Lab added successfully',
    'lab_updated'          => 'Lab updated successfully',
    'lab_deleted'          => 'Lab deleted successfully',
    'lab_not_found'        => 'Lab not found',
    'error_updating_lab'   => 'Error updating lab',
    'lab_has_active_cases' => 'Cannot delete lab with active cases',
    'confirm_delete_case'  => 'Are you sure you want to delete this lab case?',
    'confirm_delete_lab'   => 'Are you sure you want to delete this lab?',

    // ─── Print & Export ─────────────────────────────────────────
    'print_lab_case'       => 'Print Lab Case',
    'export_lab_cases'     => 'Export Lab Cases',
    'export_excel'         => 'Export Excel',
    'export_pdf'           => 'Export PDF',
    'export_csv'           => 'Export CSV',
    'export_success'       => 'Export successful',
    'export_failed'        => 'Export failed',
    'exported_by'          => 'Exported By',
    'exported_at'          => 'Exported At',
    'export_filters'       => 'Export Filters',
    'export_all'           => 'Export All',
    'export_current_page'  => 'Export Current Page',
    'export_selected'      => 'Export Selected',

    // ─── Search & Filter ────────────────────────────────────────
    'search_lab_cases'     => 'Search Lab Cases',
    'filter_by_status'     => 'Filter by Status',
    'filter_by_lab'        => 'Filter by Lab',
    'filter_by_doctor'     => 'Filter by Doctor',
    'all_statuses'         => 'All Statuses',
    'all_labs'             => 'All Labs',
    'all_doctors'          => 'All Doctors',

    // ─── Breadcrumbs ────────────────────────────────────────────
    'breadcrumb_lab_cases' => 'Lab Case Management',
    'breadcrumb_labs'      => 'Lab Management',

    // ─── Common ─────────────────────────────────────────────────
    'save'                 => 'Save',
    'cancel'               => 'Cancel',
    'close'                => 'Close',
    'edit'                 => 'Edit',
    'delete'               => 'Delete',
    'view'                 => 'View',
    'print'                => 'Print',
    'loading'              => 'Loading',
    'processing'           => 'Processing...',
    'are_you_sure'         => 'Are you sure?',
    'yes_delete_it'        => 'Yes, delete it!',
    'no_records'           => 'No records found',

];
