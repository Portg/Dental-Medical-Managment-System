<?php

return [
    // Page titles and navigation
    'page_title' => 'Medical Cases',
    'all_cases' => 'All Medical Cases',
    'add_case' => 'Add Medical Case',
    'edit_case' => 'Edit Medical Case',
    'view_case' => 'View Medical Case',
    'case_information' => 'Case Information',
    'search_placeholder' => 'Search case no., title or patient...',
    'no_cases_found' => 'No medical cases found',
    'click_add_case_to_start' => 'Click "Add New" button to create your first case',

    // Case fields
    'case_no' => 'Case No.',
    'title' => 'Title',
    'patient' => 'Patient',
    'doctor' => 'Doctor',
    'case_date' => 'Case Date',
    'status' => 'Status',
    'chief_complaint' => 'Chief Complaint',
    'history_of_present_illness' => 'History of Present Illness',
    'closing_notes' => 'Closing Notes',
    'added_by' => 'Added By',

    // Case status
    'status_open' => 'Open',
    'status_in_progress' => 'In Progress',
    'status_closed' => 'Closed',
    'status_follow_up' => 'Follow-up',

    // Select options
    'select_patient' => 'Select Patient',
    'select_doctor' => 'Select Doctor',

    // Diagnoses section
    'diagnoses' => 'Diagnoses',
    'add_diagnosis' => 'Add Diagnosis',
    'edit_diagnosis' => 'Edit Diagnosis',
    'diagnosis_name' => 'Diagnosis Name',
    'icd_code' => 'ICD Code',
    'diagnosis_date' => 'Diagnosis Date',
    'severity' => 'Severity',
    'resolved_date' => 'Resolved Date',
    'notes' => 'Notes',

    // Diagnosis status
    'diagnosis_status_active' => 'Active',
    'diagnosis_status_resolved' => 'Resolved',
    'diagnosis_status_chronic' => 'Chronic',

    // Severity levels
    'select_severity' => 'Select Severity',
    'severity_mild' => 'Mild',
    'severity_moderate' => 'Moderate',
    'severity_severe' => 'Severe',

    // Progress Notes section
    'progress_notes' => 'Progress Notes (SOAP)',
    'add_progress_note' => 'Add Progress Note',
    'edit_progress_note' => 'Edit Progress Note',
    'view_progress_note' => 'View Progress Note',
    'note_date' => 'Note Date',
    'note_type' => 'Note Type',

    // Note types
    'note_type_soap' => 'SOAP',
    'note_type_general' => 'General',
    'note_type_follow_up' => 'Follow-up',

    // SOAP fields
    'soap_explanation' => 'SOAP Notes Format',
    'soap_description' => 'SOAP is a structured documentation format: Subjective (patient complaints), Objective (clinical findings), Assessment (diagnosis/evaluation), Plan (treatment plan).',
    'subjective' => 'Subjective',
    'objective' => 'Objective',
    'assessment' => 'Assessment',
    'plan' => 'Plan',
    'subjective_help' => 'Patient\'s symptoms, complaints, and history as reported by the patient',
    'objective_help' => 'Measurable, observable clinical findings from examination',
    'assessment_help' => 'Clinical diagnosis or evaluation based on S and O',
    'plan_help' => 'Treatment plan, medications, procedures, follow-up',
    'subjective_placeholder' => 'Patient reports...',
    'objective_placeholder' => 'Physical examination reveals...',
    'assessment_placeholder' => 'Assessment/Diagnosis...',
    'plan_placeholder' => 'Treatment plan...',

    // Treatment Plans section
    'treatment_plans' => 'Treatment Plans',
    'add_treatment_plan' => 'Add Treatment Plan',
    'edit_treatment_plan' => 'Edit Treatment Plan',
    'view_treatment_plan' => 'View Treatment Plan',
    'plan_name' => 'Plan Name',
    'description' => 'Description',
    'planned_procedures' => 'Planned Procedures',
    'planned_procedures_placeholder' => 'List procedures to be performed...',
    'estimated_cost' => 'Estimated Cost',
    'actual_cost' => 'Actual Cost',
    'start_date' => 'Start Date',
    'target_completion_date' => 'Target Completion',
    'actual_completion_date' => 'Actual Completion',
    'completion_notes' => 'Completion Notes',
    'priority' => 'Priority',

    // Plan status
    'plan_status_planned' => 'Planned',
    'plan_status_in_progress' => 'In Progress',
    'plan_status_completed' => 'Completed',
    'plan_status_cancelled' => 'Cancelled',

    // Priority levels
    'priority_low' => 'Low',
    'priority_medium' => 'Medium',
    'priority_high' => 'High',
    'priority_urgent' => 'Urgent',

    // Vital Signs section
    'vital_signs' => 'Vital Signs',
    'add_vital_sign' => 'Add Vital Signs',
    'edit_vital_sign' => 'Edit Vital Signs',
    'recorded_at' => 'Recorded At',
    'blood_pressure' => 'Blood Pressure',
    'systolic' => 'Systolic',
    'diastolic' => 'Diastolic',
    'heart_rate' => 'Heart Rate',
    'temperature' => 'Temperature',
    'respiratory_rate' => 'Respiratory Rate',
    'oxygen_saturation' => 'Oxygen Saturation',
    'weight' => 'Weight',
    'height' => 'Height',
    'cardiovascular' => 'Cardiovascular',
    'general_measurements' => 'General Measurements',

    // Related appointments
    'related_appointments' => 'Related Appointments',
    'appointment_no' => 'Appointment No.',
    'appointment_date' => 'Appointment Date',

    // Success messages
    'case_created_successfully' => 'Medical case created successfully',
    'case_updated_successfully' => 'Medical case updated successfully',
    'case_deleted_successfully' => 'Medical case deleted successfully',
    'diagnosis_added_successfully' => 'Diagnosis added successfully',
    'diagnosis_updated_successfully' => 'Diagnosis updated successfully',
    'diagnosis_deleted_successfully' => 'Diagnosis deleted successfully',
    'progress_note_added_successfully' => 'Progress note added successfully',
    'progress_note_updated_successfully' => 'Progress note updated successfully',
    'progress_note_deleted_successfully' => 'Progress note deleted successfully',
    'treatment_plan_added_successfully' => 'Treatment plan added successfully',
    'treatment_plan_updated_successfully' => 'Treatment plan updated successfully',
    'treatment_plan_deleted_successfully' => 'Treatment plan deleted successfully',
    'vital_sign_added_successfully' => 'Vital signs recorded successfully',
    'vital_sign_updated_successfully' => 'Vital signs updated successfully',
    'vital_sign_deleted_successfully' => 'Vital signs deleted successfully',

    // Confirmation messages
    'confirm_delete' => 'Are you sure?',
    'confirm_delete_message' => 'You will not be able to recover this medical case!',
    'confirm_delete_diagnosis' => 'You will not be able to recover this diagnosis!',
    'confirm_delete_progress_note' => 'You will not be able to recover this progress note!',
    'confirm_delete_treatment_plan' => 'You will not be able to recover this treatment plan!',
    'confirm_delete_vital_sign' => 'You will not be able to recover this vital sign record!',

    // F-MED-001: Medical Record Edit Form
    'medical_record_edit' => 'Medical Record',
    'visit_information' => 'Visit Information',
    'visit_date' => 'Visit Date',
    'visit_type' => 'Visit Type',
    'initial_visit' => 'Initial Visit',
    'revisit' => 'Follow-up Visit',
    'attending_doctor' => 'Attending Doctor',

    // SOAP Form Sections
    'chief_complaint_section' => 'Chief Complaint',
    'chief_complaint_hint' => 'Describe patient\'s subjective symptoms, 10-500 characters',
    'present_illness_section' => 'History of Present Illness',
    'present_illness_hint' => 'Disease onset and progression',
    'examination_section' => 'Examination',
    'examination_hint' => 'Objective clinical findings',
    'related_teeth' => 'Related Teeth',
    'select_teeth' => 'Select Teeth',
    'auxiliary_exam_section' => 'Auxiliary Examination',
    'auxiliary_exam_hint' => 'X-ray, CT scan results, etc.',
    'select_images' => 'Select Images',
    'diagnosis_section' => 'Diagnosis',
    'diagnosis_hint' => 'Diagnostic conclusion',
    'icd10_code' => 'ICD-10 Code',
    'search_icd10' => 'Search ICD-10 diagnosis code',
    'treatment_section' => 'Treatment',
    'treatment_hint' => 'Treatment procedure record',
    'treatment_services' => 'Treatment Services',
    'add_service' => 'Add Service',
    'medical_orders_section' => 'Medical Orders',
    'medical_orders_hint' => 'Post-operative instructions',

    // Follow-up Section
    'followup_section' => 'Follow-up Schedule',
    'next_visit_date' => 'Next Visit Date',
    'next_visit_note' => 'Follow-up Notes',
    'auto_create_followup' => 'Auto-create follow-up reminder',

    // Right Panel
    'patient_info' => 'Patient Information',
    'patient_allergy' => 'Allergies',
    'patient_medical_history' => 'Medical History',
    'patient_medication' => 'Medications',
    'tooth_chart' => 'Tooth Chart',
    'quick_tooth_select' => 'Quick View',
    'click_to_select_tooth' => 'Click to select tooth',
    'history_records' => 'History Records',
    'expand' => 'Expand',
    'collapse' => 'Collapse',
    'quick_phrases' => 'Quick Phrases',

    // Template
    'insert_template' => 'Insert Template',
    'system_templates' => 'System Templates',
    'department_templates' => 'Department Templates',
    'my_templates' => 'My Templates',
    'type_slash_for_template' => 'Type / to trigger template menu',

    // Actions
    'save_draft' => 'Save Draft',
    'submit_record' => 'Submit',
    'draft_status' => 'Draft',
    'draft_saved' => 'Draft saved',
    'record_submitted' => 'Record submitted',

    // Quality Control
    'quality_check' => 'Quality Control',
    'qc_chief_complaint' => 'Chief Complaint Completeness',
    'qc_diagnosis_standard' => 'Diagnosis Standards',
    'qc_teeth_clarity' => 'Tooth Position Clarity',
    'qc_treatment_link' => 'Treatment Linkage',
    'qc_signature' => 'Signature Complete',
    'qc_error' => 'Error',
    'qc_warning' => 'Warning',
    'qc_chief_complaint_rule' => 'Chief complaint cannot be empty and must be ≥10 characters',
    'qc_diagnosis_rule' => 'Diagnosis must be linked to ICD-10 code',
    'qc_teeth_rule' => 'Dental treatments must specify tooth positions',
    'qc_treatment_rule' => 'Treatment must be linked to billable services',
    'qc_signature_rule' => 'Doctor\'s electronic signature required',

    // Edit Permissions & Amendments
    'edit_within_24h' => 'Can edit within 24 hours after submission',
    'edit_requires_approval' => 'Record is locked. Modifications require a reason and approval.',
    'modification_reason' => 'Modification Reason',
    'record_locked' => 'Record Locked',
    'amendment_submitted' => 'Amendment request submitted, pending approval',
    'amendment_approved' => 'Amendment request approved',
    'amendment_rejected' => 'Amendment request rejected',
    'amendment_already_reviewed' => 'This amendment has already been reviewed',
    'amendments' => 'Amendments',
    'amendment_reason' => 'Amendment Reason',
    'amendment_status' => 'Approval Status',
    'amendment_pending' => 'Pending',
    'amendment_review_notes' => 'Review Notes',
    'amendment_requested_by' => 'Requested By',
    'amendment_approved_by' => 'Approved By',
    'amendment_reviewed_at' => 'Reviewed At',
    'approve_amendment' => 'Approve',
    'reject_amendment' => 'Reject',
    'reject_reason_required' => 'Review notes are required when rejecting',
    'version_history' => 'Version History',
    'version_number' => 'Version',
    'signature_hint' => 'Please sign in the area below',
    'signature_required' => 'Doctor signature is required to submit',
    'signature_saved' => 'Signature saved',
    'export_pdf' => 'Export PDF',
    'signed' => 'Signed',
    'pdf_watermark' => 'Electronic Medical Record - For Medical Use Only',
    'pdf_archived' => 'PDF archived successfully',

    // Validation
    'chief_complaint_required' => 'Please enter the chief complaint',
    'chief_complaint_min' => 'Chief complaint must be at least 10 characters',
    'examination_required' => 'Please enter examination findings',
    'diagnosis_required' => 'Please enter diagnosis',
    'treatment_required' => 'Please enter treatment',

    // Patient Selection (Create Mode)
    'select_patient_first' => 'Select Patient',
    'search_and_select_patient' => 'Search and select a patient...',
    'continue_to_record' => 'Continue to Medical Record',
    'create_new_patient' => 'New Patient',
    'create_patient_hint' => 'Opens patient management in new tab, return here after creating',
    'no_patient_selected' => 'No patient selected',
    'please_select_patient' => 'Please select a patient first',
    'select_patient_hint' => 'Please select a patient from the right sidebar to start filling in the medical record',

    // Print related
    'medical_record' => 'Medical Record',
    'soap_section' => 'SOAP Notes',
    'examination' => 'Examination',
    'diagnosis' => 'Diagnosis',
    'treatment' => 'Treatment',
    'examination_teeth' => 'Examination Teeth',
    'auxiliary_examination' => 'Auxiliary Examination',
    'medical_orders' => 'Medical Orders',
    'next_visit' => 'Next Visit',
    'doctor_signature' => 'Doctor Signature',
    'date' => 'Date',
    'bmi' => 'BMI',
    'visit_type_initial' => 'Initial Visit',
    'visit_type_revisit' => 'Revisit',
    'visit_type_follow_up' => 'Follow-up',
    'plan_status_planned' => 'Planned',
    'plan_status_in_progress' => 'In Progress',
    'plan_status_completed' => 'Completed',
    'plan_status_cancelled' => 'Cancelled',

    // Related appointments
    'related_appointments' => 'Related Appointments',
    'appointment_no' => 'Appointment No.',
    'appointment_date' => 'Appointment Date',

    // Teeth management
    'add_teeth' => 'Add Teeth',
    'click_tooth_to_select' => 'Click to select tooth',
    'tooth_chart_hint' => 'Click to toggle tooth selection',
    'tooth_target_related' => 'Target: Related Teeth',
    'tooth_target_examination' => 'Target: Examination Teeth',
    'no_history_records' => 'No history records',
    'visit_record' => 'Visit Record',
    'chronic_diseases' => 'Chronic Diseases',

    // Auxiliary section
    'auxiliary_section' => 'Auxiliary Examination',
    'auxiliary_hint' => 'X-ray, CT scan results, etc.',
    'auxiliary_placeholder' => 'Describe auxiliary examination results...',
    'attach_images' => 'Attach Images',

    // Examination section
    'examination_placeholder' => 'Describe physical and oral examination findings...',

    // Diagnosis section
    'diagnosis_placeholder' => 'Enter diagnosis...',

    // Treatment section
    'treatment_placeholder' => 'Describe treatment procedure...',

    // Follow-up section
    'followup_date' => 'Follow-up Date',
    'followup_type' => 'Follow-up Type',
    'followup_notes' => 'Follow-up Notes',
    'followup_notes_placeholder' => 'Enter follow-up instructions...',
    'followup_phone' => 'Phone Call',
    'followup_sms' => 'SMS Reminder',
    'followup_visit' => 'Clinic Visit',
    'send_reminder' => 'Send reminder notification',

    // Visit type
    'visit_type_emergency' => 'Emergency',

    // Template buttons
    'template_cleaning' => 'Cleaning Template',
    'template_extraction' => 'Extraction Template',
    'template_filling' => 'Filling Template',

    // Quick phrases
    'phrase_probe_normal' => 'Probing normal, pocket depth within 3mm',
    'phrase_probe_normal_short' => 'Probe Normal',
    'phrase_gum_bleeding' => 'Gingival swelling, bleeding on probing',
    'phrase_gum_bleeding_short' => 'Gum Bleeding',
    'phrase_calculus' => 'Visible supragingival/subgingival calculus',
    'phrase_calculus_short' => 'Calculus',
    'phrase_cavity' => 'Visible cavity, sensitive to probing',
    'phrase_cavity_short' => 'Cavity',
    'phrase_sensitivity' => 'Sensitive to hot/cold stimuli',
    'phrase_sensitivity_short' => 'Sensitivity',
    'phrase_mobility' => 'Tooth mobility grade I/II/III',
    'phrase_mobility_short' => 'Mobility',
    'phrase_percussion_pain' => 'Percussion pain (+)',
    'phrase_percussion_short' => 'Percussion Pain',
    'phrase_xray_normal' => 'X-ray shows normal periapical area',
    'phrase_xray_normal_short' => 'X-ray Normal',

    // Picker hints
    'hint_template_picker' => 'Type :key in a text field to select a case template',
    'hint_phrase_picker' => 'Type :key in a text field to select a common phrase',

    // Slash command menu
    'select_template' => 'Select template (type to filter)',
    'navigate' => 'Navigate',
    'select' => 'Select',
    'close' => 'Close',
    'no_matching_template' => 'No matching template',

    // Template: Cleaning
    'tpl_cleaning_title' => 'Cleaning',
    'tpl_cleaning_desc' => 'Routine cleaning/periodontal care',
    'tpl_cleaning_chief' => 'Patient requests dental cleaning, reports gum bleeding and bad breath',
    'tpl_cleaning_exam' => 'Gingival swelling, bleeding on probing (+), visible supragingival calculus and staining, pocket depth 3-4mm',
    'tpl_cleaning_diag' => 'Chronic gingivitis',
    'tpl_cleaning_treat' => 'Ultrasonic supragingival scaling, polishing, irrigation and medication',
    'tpl_cleaning_orders' => '1. Avoid very hot or cold food for 24 hours\\n2. Temporary tooth sensitivity may occur, this is normal\\n3. Recommend cleaning every 6 months',

    // Template: Extraction
    'tpl_extraction_title' => 'Extraction',
    'tpl_extraction_desc' => 'Tooth extraction procedure',
    'tpl_extraction_chief' => 'Patient requests extraction of tooth ___, recurrent pain/mobile/non-restorable',
    'tpl_extraction_exam' => 'Tooth ___ residual root/crown/mobility grade III, percussion pain (+), gingival swelling',
    'tpl_extraction_diag' => 'Tooth ___ residual root/crown/chronic periapical periodontitis',
    'tpl_extraction_treat' => 'Extraction of tooth ___ under local anesthesia, socket curettage, gelatin sponge placement, gauze bite for hemostasis',
    'tpl_extraction_orders' => '1. Bite gauze firmly for 30-60 minutes\\n2. Do not brush extraction site or rinse forcefully for 24 hours\\n3. Soft lukewarm food after 2 hours\\n4. Return if persistent bleeding occurs',

    // Template: Filling
    'tpl_filling_title' => 'Filling',
    'tpl_filling_desc' => 'Cavity filling treatment',
    'tpl_filling_chief' => 'Patient reports cavity in tooth ___, food impaction/sensitivity to hot/cold',
    'tpl_filling_exam' => 'Tooth ___ visible cavity, sensitive to probing, percussion (-), thermal test sensitive/normal',
    'tpl_filling_diag' => 'Tooth ___ moderate/deep caries',
    'tpl_filling_treat' => 'Caries removal, cavity preparation, liner/direct filling, composite/GIC restoration, occlusal adjustment and polishing',
    'tpl_filling_orders' => '1. Do not eat for 2 hours\\n2. Avoid biting hard objects on treated side\\n3. Return if persistent pain occurs',

    // Template: Root Canal
    'tpl_rootcanal_title' => 'Root Canal',
    'tpl_rootcanal_desc' => 'Pulpitis/periapical treatment',
    'tpl_rootcanal_chief' => 'Patient reports spontaneous pain/night pain/biting pain in tooth ___ for ___ days',
    'tpl_rootcanal_exam' => 'Tooth ___ visible caries/restoration, pulp exposure on probing, percussion (+), thermal test delayed/no response, apical tenderness (+/-)',
    'tpl_rootcanal_diag' => 'Tooth ___ acute/chronic pulpitis/periapical periodontitis',
    'tpl_rootcanal_treat' => 'Access opening under local anesthesia, pulp extirpation, canal preparation, irrigation, intracanal medication/obturation',
    'tpl_rootcanal_orders' => '1. Post-operative pain may occur, usually subsides in 2-3 days\\n2. Avoid biting hard objects on treated side\\n3. Return on schedule for follow-up treatment\\n4. Crown restoration recommended after root canal therapy',

    // Template: Periodontal
    'tpl_periodontal_title' => 'Periodontal',
    'tpl_periodontal_desc' => 'Periodontal disease treatment',
    'tpl_periodontal_chief' => 'Patient reports gum bleeding, tooth mobility, bad breath',
    'tpl_periodontal_exam' => 'Gingival swelling and recession, bleeding on probing (+), pocket depth ___mm, visible subgingival calculus, tooth mobility grade I-II',
    'tpl_periodontal_diag' => 'Chronic periodontitis (mild/moderate/severe)',
    'tpl_periodontal_treat' => 'Supragingival scaling, subgingival scaling (___ quadrant), root planing, periodontal irrigation and medication',
    'tpl_periodontal_orders' => '1. Tooth sensitivity may occur, this is normal\\n2. Use soft bristle toothbrush, learn proper brushing technique\\n3. Use dental floss/interdental brush\\n4. Return for follow-up, regular maintenance',
];
