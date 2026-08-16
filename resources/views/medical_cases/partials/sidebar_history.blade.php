{{-- History Records --}}
<div class="portlet light bordered">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.history_records') }}</span>
        </div>
    </div>
    {{-- id 供选完患者后局部替换：走「新建病历」通用入口时患者是后选的，
         历史记录是服务端渲染的，不换掉这块就永远停在「暂无历史记录」。
         见 medical_record_edit.js 的 loadCaseHistory()。 --}}
    <div class="portlet-body" id="caseHistorySidebarBody">
        @include('medical_cases.partials.sidebar_history_body', ['historyRecords' => $historyRecords])
    </div>
</div>
