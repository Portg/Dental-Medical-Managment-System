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

    'accepted' => ':attribute 必须接受。',
    'active_url' => ':attribute 不是一个有效的网址。',
    'after' => ':attribute 必须是一个在 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是一个在 :date 之后或相同的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、破折号和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'array' => ':attribute 必须是一个数组。',
    'before' => ':attribute 必须是一个在 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是一个在 :date 之前或相同的日期。',
    'between' => [
        'numeric' => ':attribute 必须在 :min 和 :max 之间。',
        'file' => ':attribute 必须在 :min 和 :max KB 之间。',
        'string' => ':attribute 必须在 :min 和 :max 个字符之间。',
        'array' => ':attribute 必须有 :min 到 :max 个项目。',
    ],
    'boolean' => ':attribute 字段必须为真或假。',
    'confirmed' => ':attribute 确认不匹配。',
    'date' => ':attribute 不是一个有效的日期。',
    'date_equals' => ':attribute 必须是等于 :date 的日期。',
    'date_format' => ':attribute 不匹配格式 :format。',
    'different' => ':attribute 和 :other 必须不同。',
    'digits' => ':attribute 必须是 :digits 位数字。',
    'digits_between' => ':attribute 必须在 :min 和 :max 位数字之间。',
    'dimensions' => ':attribute 图片尺寸不符合要求。',
    'distinct' => ':attribute 字段有重复值。',
    'email' => ':attribute 必须是一个有效的邮箱地址。',
    'ends_with' => ':attribute 必须以以下之一结尾: :values。',
    'exists' => '选择的 :attribute 无效。',
    'file' => ':attribute 必须是一个文件。',
    'filled' => ':attribute 字段必须有值。',
    'gt' => [
        'numeric' => ':attribute 必须大于 :value。',
        'file' => ':attribute 必须大于 :value KB。',
        'string' => ':attribute 必须多于 :value 个字符。',
        'array' => ':attribute 必须多于 :value 个项目。',
    ],
    'gte' => [
        'numeric' => ':attribute 必须大于或等于 :value。',
        'file' => ':attribute 必须大于或等于 :value KB。',
        'string' => ':attribute 必须多于或等于 :value 个字符。',
        'array' => ':attribute 必须有 :value 个或更多项目。',
    ],
    'image' => ':attribute 必须是图片。',
    'in' => '选择的 :attribute 无效。',
    'in_array' => ':attribute 字段不存在于 :other 中。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是有效的 IP 地址。',
    'ipv4' => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必须是有效的 IPv6 地址。',
    'json' => ':attribute 必须是有效的 JSON 字符串。',
    'lt' => [
        'numeric' => ':attribute 必须小于 :value。',
        'file' => ':attribute 必须小于 :value KB。',
        'string' => ':attribute 必须少于 :value 个字符。',
        'array' => ':attribute 必须少于 :value 个项目。',
    ],
    'lte' => [
        'numeric' => ':attribute 必须小于或等于 :value。',
        'file' => ':attribute 必须小于或等于 :value KB。',
        'string' => ':attribute 必须少于或等于 :value 个字符。',
        'array' => ':attribute 必须不多于 :value 个项目。',
    ],
    'max' => [
        'numeric' => ':attribute 不能大于 :max。',
        'file' => ':attribute 不能大于 :max KB。',
        'string' => ':attribute 不能多于 :max 个字符。',
        'array' => ':attribute 不能多于 :max 个项目。',
    ],
    'mimes' => ':attribute 必须是 :values 类型的文件。',
    'mimetypes' => ':attribute 必须是 :values 类型的文件。',
    'min' => [
        'numeric' => ':attribute 必须至少为 :min。',
        'file' => ':attribute 必须至少为 :min KB。',
        'string' => ':attribute 必须至少为 :min 个字符。',
        'array' => ':attribute 必须至少有 :min 个项目。',
    ],
    'not_in' => '选择的 :attribute 无效。',
    'not_regex' => ':attribute 格式无效。',
    'numeric' => ':attribute 必须是数字。',
    'password' => '密码不正确。',
    'present' => ':attribute 字段必须存在。',
    'regex' => ':attribute 格式无效。',
    'required' => ':attribute 字段是必填的。',
    'required_if' => '当 :other 为 :value 时，:attribute 字段是必填的。',
    'required_unless' => '除非 :other 在 :values 中，否则 :attribute 字段是必填的。',
    'required_with' => '当 :values 存在时，:attribute 字段是必填的。',
    'required_with_all' => '当 :values 全部存在时，:attribute 字段是必填的。',
    'required_without' => '当 :values 不存在时，:attribute 字段是必填的。',
    'required_without_all' => '当 :values 全部不存在时，:attribute 字段是必填的。',
    'same' => ':attribute 和 :other 必须匹配。',
    'size' => [
        'numeric' => ':attribute 必须为 :size。',
        'file' => ':attribute 必须为 :size KB。',
        'string' => ':attribute 必须为 :size 个字符。',
        'array' => ':attribute 必须包含 :size 个项目。',
    ],
    'starts_with' => ':attribute 必须以以下之一开头: :values。',
    'string' => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是有效的时区。',
    'unique' => ':attribute 已经被占用。',
    'uploaded' => ':attribute 上传失败。',
    'url' => ':attribute 格式无效。',
    'uuid' => ':attribute 必须是有效的 UUID。',

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
            'rule-name' => '自定义消息',
        ],
        // 通用字段验证
        'name' => [
            'required' => '名称字段是必填的。',
        ],
        'amount' => [
            'required' => '金额字段是必填的。',
            'numeric' => '金额必须是数字。',
        ],
        'date' => [
            'required' => '日期字段是必填的。',
        ],
        'email' => [
            'required' => '邮箱字段是必填的。',
            'email' => '请输入有效的邮箱地址。',
        ],
        'phone' => [
            'required' => '电话号码是必填的。',
        ],
        // 会计验证
        'accounting_equation_id' => [
            'required' => '请选择会计等式。',
        ],
        // 薪资验证
        'deduction' => [
            'required' => '扣除类型是必填的。',
        ],
        'allowance' => [
            'required' => '津贴类型是必填的。',
        ],
        // 医疗验证
        'body_reaction' => [
            'required' => '请描述身体反应。',
        ],
        'patient_id' => [
            'required' => '请选择患者。',
        ],
        'doctor_id' => [
            'required' => '请选择医生。',
        ],
        // 预约验证
        'appointment_date' => [
            'required' => '预约日期是必填的。',
        ],
        'appointment_time' => [
            'required' => '预约时间是必填的。',
        ],
        // 发票验证
        'invoice_no' => [
            'required' => '发票号码是必填的。',
        ],
        'payment_method' => [
            'required' => '请选择付款方式。',
        ],
        // 支出验证
        'expense_category_id' => [
            'required' => '请选择支出类别。',
        ],
        'description' => [
            'required' => '描述是必填的。',
        ],
        // 用户验证
        'password' => [
            'required' => '密码是必填的。',
            'min' => '密码必须至少 :min 个字符。',
            'confirmed' => '密码确认不匹配。',
        ],
        'role_id' => [
            'required' => '请选择角色。',
        ],
        'branch_id' => [
            'required' => '请选择分店。',
        ],
        // 慢性病验证
        'disease' => [
            'required' => '疾病字段是必填的。',
        ],
        'status' => [
            'required' => '状态字段是必填的。',
        ],
        // 手术验证
        'surgery' => [
            'required' => '手术字段是必填的。',
        ],
        'surgery_date' => [
            'required' => '手术日期是必填的。',
        ],
        // 治疗验证
        'clinical_notes' => [
            'required' => '临床记录是必填的。',
        ],
        'treatment' => [
            'required' => '治疗字段是必填的。',
        ],
        'appointment_id' => [
            'required' => '请选择预约。',
        ],
        // 会计验证
        'sort_by' => [
            'required' => '排序顺序是必填的。',
            'integer' => '排序顺序必须是整数。',
        ],
        // 假期类型验证
        'max_days' => [
            'required' => '最大天数是必填的。',
        ],
        // 消息验证
        'message' => [
            'required' => '消息字段是必填的。',
        ],
        // 病例管理验证
        'title' => [
            'required' => '标题字段是必填的。',
        ],
        'case_date' => [
            'required' => '就诊日期是必填的。',
        ],
        'diagnosis_name' => [
            'required' => '诊断名称是必填的。',
        ],
        'diagnosis_date' => [
            'required' => '诊断日期是必填的。',
        ],
        'note_date' => [
            'required' => '记录日期是必填的。',
        ],
        'plan_name' => [
            'required' => '计划名称是必填的。',
        ],
        'recorded_at' => [
            'required' => '记录时间是必填的。',
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

    // 自定义属性名称
    'attributes' => [
        // 患者信息
        'surname' => '姓氏',
        'othername' => '名字',
        'gender' => '性别',
        'dob' => '出生日期',
        'phone_no' => '电话号码',
        'alternative_no' => '备用电话',
        'address' => '地址',
        'nin' => '身份证号',
        'profession' => '职业',
        'next_of_kin' => '紧急联系人',
        'next_of_kin_no' => '紧急联系人电话',
        'next_of_kin_address' => '紧急联系人地址',
        'has_insurance' => '是否有保险',
        'insurance_company_id' => '保险公司',

        // 用户信息
        'username' => '用户名',
        'password' => '密码',
        'email' => '邮箱地址',
        'role_id' => '角色',
        'branch_id' => '分店',

        // 员工合同
        'employee' => '员工',
        'contract_type' => '合同类型',
        'start_date' => '开始日期',
        'end_date' => '结束日期',
        'contract_length' => '合同期限',
        'contract_period' => '合同周期',
        'payroll_type' => '薪资类型',
        'gross_salary' => '基本工资',
        'commission_percentage' => '佣金百分比',

        // 费用相关
        'name' => '名称',
        'expense_account' => '费用账户',
        'expense_category_id' => '费用分类',
        'item' => '项目',
        'qty' => '数量',
        'price' => '单价',
        'amount' => '金额',
        'description' => '描述',

        // 支付相关
        'payment_date' => '支付日期',
        'payment_method' => '支付方式',
        'payment_account' => '支付账户',

        // 采购相关
        'purchase_date' => '采购日期',
        'supplier_name' => '供应商名称',

        // 假期相关
        'holiday_name' => '假期名称',
        'holiday_date' => '假期日期',
        'repeat_date' => '重复日期',
        'leave_type' => '假期类型',
        'duration' => '持续时间',
        'max_days' => '最大天数',

        // 保险相关
        'insurance_company_name' => '保险公司名称',

        // 发票相关
        'invoice_id' => '发票',
        'invoice_no' => '发票号',

        // 预约相关
        'appointment_date' => '预约日期',
        'appointment_time' => '预约时间',
        'patient_id' => '患者',
        'doctor_id' => '医生',

        // 医疗相关
        'disease' => '疾病',
        'surgery' => '手术',
        'surgery_date' => '手术日期',
        'treatment' => '治疗',
        'clinical_notes' => '临床记录',
        'body_reaction' => '身体反应',

        // 会计相关
        'accounting_equation_id' => '会计等式',
        'sort_by' => '排序顺序',

        // 薪资相关
        'deduction' => '扣除项',
        'allowance' => '津贴项',

        // 其他
        'status' => '状态',
        'message' => '消息',
    ],
];