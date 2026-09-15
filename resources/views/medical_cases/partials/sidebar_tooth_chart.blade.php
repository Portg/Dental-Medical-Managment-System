{{-- 侧栏牙位图。

     恒牙与乳牙同屏，不再分 tab（原来还按年龄自动选：≤12 岁默认乳牙）——
     6-12 岁替牙期一张嘴里两种牙并存，分 tab 会让「55 乳牙滞留，15 阻生」
     这条最常见的替牙期记录写不出来，而自动选 tab 恰好在替牙期把人送到错误的
     那一面。网格本身与选牙位弹窗共用同一个 partial。

     点一颗牙 = 给当前聚焦行加/减这颗牙（medical_record_edit.js 里的委托），
     不弹窗、不用确认 —— 这条快路径原来就有，保留。 --}}
<div class="portlet light bordered sidebar-tool-panel @if($needPatientSelection ?? false) disabled @endif">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.tooth_chart') }}</span>
        </div>
    </div>
    <div class="portlet-body" style="text-align: center;">
        @include('medical_cases.partials.tooth_grid', [
            'idPrefix' => 'sidebar-tooth-grid',
            'compact'  => true,
            'onclick'  => null,
        ])
        <div class="text-muted" style="font-size: 11px; margin-top: 8px;">
            {{ __('medical_cases.tooth_chart_hint') }}
        </div>
    </div>
</div>
