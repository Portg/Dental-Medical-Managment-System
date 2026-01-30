<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'ends_with' => 'The :attribute must end with one of the following: :values',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal :value.',
        'file' => 'The :attribute must be greater than or equal :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal :value.',
        'file' => 'The :attribute must be less than or equal :value kilobytes.',
        'string' => 'The :attribute must be less than or equal :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'password' => 'The password is incorrect.',
    'present' => 'The :attribute field must be present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobytes.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid zone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute format is invalid.',
    'uuid' => 'The :attribute must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        // Common field validations
        'name' => [
            'required' => 'The name field is required.',
        ],
        'amount' => [
            'required' => 'The amount field is required.',
            'numeric' => 'The amount must be a number.',
        ],
        'date' => [
            'required' => 'The date field is required.',
        ],
        'email' => [
            'required' => 'The email field is required.',
            'email' => 'Please enter a valid email address.',
        ],
        'phone' => [
            'required' => 'The phone number is required.',
        ],
        // Accounting validations
        'accounting_equation_id' => [
            'required' => 'Please select an accounting equation.',
        ],
        // Salary validations
        'deduction' => [
            'required' => 'The deduction type is required.',
        ],
        'allowance' => [
            'required' => 'The allowance type is required.',
        ],
        // Medical validations
        'body_reaction' => [
            'required' => 'Please describe the body reaction.',
        ],
        'patient_id' => [
            'required' => 'Please select a patient.',
        ],
        'doctor_id' => [
            'required' => 'Please select a doctor.',
        ],
        // Appointment validations
        'appointment_date' => [
            'required' => 'The appointment date is required.',
        ],
        'appointment_time' => [
            'required' => 'The appointment time is required.',
        ],
        // Invoice validations
        'invoice_no' => [
            'required' => 'The invoice number is required.',
        ],
        'payment_method' => [
            'required' => 'Please select a payment method.',
        ],
        // Expense validations
        'expense_category_id' => [
            'required' => 'Please select an expense category.',
        ],
        'description' => [
            'required' => 'The description is required.',
        ],
        // User validations
        'password' => [
            'required' => 'The password is required.',
            'min' => 'The password must be at least :min characters.',
            'confirmed' => 'The password confirmation does not match.',
        ],
        'role_id' => [
            'required' => 'Please select a role.',
        ],
        'branch_id' => [
            'required' => 'Please select a branch.',
        ],
        // Chronic disease validations
        'disease' => [
            'required' => 'The disease field is required.',
        ],
        'status' => [
            'required' => 'The status field is required.',
        ],
        // Surgery validations
        'surgery' => [
            'required' => 'The surgery field is required.',
        ],
        'surgery_date' => [
            'required' => 'The surgery date is required.',
        ],
        // Treatment validations
        'clinical_notes' => [
            'required' => 'The clinical notes are required.',
        ],
        'treatment' => [
            'required' => 'The treatment field is required.',
        ],
        'appointment_id' => [
            'required' => 'Please select an appointment.',
        ],
        // Accounting validations
        'sort_by' => [
            'required' => 'The sort order is required.',
            'integer' => 'The sort order must be an integer.',
        ],
        // Leave type validations
        'max_days' => [
            'required' => 'The maximum days is required.',
        ],
        // Message validations
        'message' => [
            'required' => 'The message field is required.',
        ],
        // Medical case validations
        'title' => [
            'required' => 'The title field is required.',
        ],
        'case_date' => [
            'required' => 'The case date is required.',
        ],
        'diagnosis_name' => [
            'required' => 'The diagnosis name is required.',
        ],
        'diagnosis_date' => [
            'required' => 'The diagnosis date is required.',
        ],
        'note_date' => [
            'required' => 'The note date is required.',
        ],
        'plan_name' => [
            'required' => 'The plan name is required.',
        ],
        'recorded_at' => [
            'required' => 'The recording time is required.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */
    // Custom attribute names
    'attributes' => [
        // Patient information
        'surname' => 'Surname',
        'othername' => 'Other Name',
        'gender' => 'Gender',
        'dob' => 'Date of Birth',
        'phone_no' => 'Phone Number',
        'alternative_no' => 'Alternative Phone',
        'address' => 'Address',
        'nin' => 'National ID',
        'profession' => 'Profession',
        'next_of_kin' => 'Next of Kin',
        'next_of_kin_no' => 'Next of Kin Phone',
        'next_of_kin_address' => 'Next of Kin Address',
        'has_insurance' => 'Has Insurance',
        'insurance_company_id' => 'Insurance Company',

        // User information
        'username' => 'Username',
        'password' => 'Password',
        'email' => 'Email Address',
        'role_id' => 'Role',
        'branch_id' => 'Branch',

        // Employee contracts
        'employee' => 'Employee',
        'contract_type' => 'Contract Type',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'contract_length' => 'Contract Length',
        'contract_period' => 'Contract Period',
        'payroll_type' => 'Payroll Type',
        'gross_salary' => 'Gross Salary',
        'commission_percentage' => 'Commission Percentage',

        // Expense related
        'name' => 'Name',
        'expense_account' => 'Expense Account',
        'expense_category_id' => 'Expense Category',
        'item' => 'Item',
        'qty' => 'Quantity',
        'price' => 'Price',
        'amount' => 'Amount',
        'description' => 'Description',

        // Payment related
        'payment_date' => 'Payment Date',
        'payment_method' => 'Payment Method',
        'payment_account' => 'Payment Account',

        // Purchase related
        'purchase_date' => 'Purchase Date',
        'supplier_name' => 'Supplier Name',

        // Holiday related
        'holiday_name' => 'Holiday Name',
        'holiday_date' => 'Holiday Date',
        'repeat_date' => 'Repeat Date',
        'leave_type' => 'Leave Type',
        'duration' => 'Duration',
        'max_days' => 'Maximum Days',

        // Insurance related
        'insurance_company_name' => 'Insurance Company Name',

        // Invoice related
        'invoice_id' => 'Invoice',
        'invoice_no' => 'Invoice Number',

        // Appointment related
        'appointment_date' => 'Appointment Date',
        'appointment_time' => 'Appointment Time',
        'patient_id' => 'Patient',
        'doctor_id' => 'Doctor',

        // Medical related
        'disease' => 'Disease',
        'surgery' => 'Surgery',
        'surgery_date' => 'Surgery Date',
        'treatment' => 'Treatment',
        'clinical_notes' => 'Clinical Notes',
        'body_reaction' => 'Body Reaction',

        // Accounting related
        'accounting_equation_id' => 'Accounting Equation',
        'sort_by' => 'Sort Order',

        // Salary related
        'deduction' => 'Deduction',
        'allowance' => 'Allowance',

        // Other
        'status' => 'Status',
        'message' => 'Message',
    ],

];