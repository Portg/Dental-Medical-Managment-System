{{-- 诊断段：牙位 + 诊断名 + ICD 编码。

     与检查/治疗不同，诊断**不走 medical_case_items**，直接写 diagnoses 表 ——
     那张表本来就是一行一条诊断，还带 ICD 编码、严重程度、转归状态，
     是 medical_case_items 存不了的（ICD 是医保与病案质控要的）。
     同一条诊断不在两张表里各存一份。见 2026_08_25_100000 迁移的说明。

     diagnoses 此前一直是 0 条 —— 它只能从病历详情页的「诊断记录」Tab 单独添加，
     而医生的动作是「新建病历 → 一屏填完 → 提交」，不会填完再拐过去点一次。
     这个段落就是把它接进主流程。 --}}
<div class="soap-section case-items-section diagnosis-section" data-section="diagnosis">
    {{-- 段落头一行放完，与其他段落一致（见 case_items_section 的说明） --}}
    <div class="soap-section-header">
        <button type="button" class="section-toggle js-toggle-section" data-section="diagnosis"
                title="{{ __('medical_cases.collapse') }}">
            <i class="fa fa-chevron-down"></i>
        </button>
        <div class="soap-section-title">
            {{ __('medical_cases.diagnosis_section') }}
            <span class="required">*</span>
        </div>
        <button type="button" class="btn btn-xs btn-link js-add-diagnosis"
                title="{{ __('medical_cases.item_row_hint') }}">
            <i class="fa fa-plus"></i> {{ __('medical_cases.add_item_row') }}
        </button>
        <button type="button" class="btn btn-xs btn-link js-copy-teeth" data-section="diagnosis"
                title="{{ __('medical_cases.copy_teeth_hint') }}">
            <i class="fa fa-clone"></i> {{ __('medical_cases.copy_teeth') }}
        </button>
    </div>
    <div class="soap-section-body">
        <div class="case-items-rows" id="rows-diagnosis"></div>

        {{-- 由诊断行渲染出来的整段文字。没有 name，不进请求；只给既有的客户端
             必填校验读（那些代码读 $('#diagnosis').val()）。服务端由行自己派生。 --}}
        <textarea id="diagnosis" class="case-items-derived" readonly
                  data-derived="1" tabindex="-1" aria-hidden="true" style="display:none"></textarea>

        <script type="application/json" class="js-diagnosis-seed">
            {!! json_encode($diagnosisRows ?? [], JSON_UNESCAPED_UNICODE) !!}
        </script>
    </div>
</div>
