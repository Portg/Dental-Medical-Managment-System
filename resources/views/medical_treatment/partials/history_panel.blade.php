{{-- 治疗史 —— 「这个人到底做过什么」。

     这是原来整页最缺的一块：页面通篇只讲「今天」，而医生真正要知道的是累计，
     且不该每次都跑到别处查一遍。

     两态，由牙位图驱动：
       没选牙位 → 全口历次就诊，一次一行
       选了牙位 → 那颗牙的时间线（治疗段 + 诊断段）

     挂在牙齿图表页签内的右侧，因为选牙位这个动作就发生在那儿；切到别的页签
     时它跟着一起走，不占地方。 --}}
@php($csPlan = $chairside['plan'] ?? [])
<div class="cs-history" id="csHistory"
     data-patient="{{ $chairside['bar']['id'] ?? '' }}"
     data-url="{{ url('medical-treatment/tooth-history/' . ($chairside['bar']['id'] ?? 0)) }}">

    @if(!empty($csPlan))
        <div class="cs-panel">
            <div class="cs-panel-h">{{ __('medical_treatment.cs_plan_pending') }}</div>
            <ul class="cs-plan">
                @foreach($csPlan as $item)
                    <li>
                        @if($item['teeth'])<span class="cs-tno">{{ $item['teeth'] }}</span>@endif
                        <span>{{ $item['name'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="cs-panel">
        <div class="cs-panel-h">
            <span id="csHistTitle">{{ __('medical_treatment.cs_visit_history') }}</span>
            {{-- 选了牙位后出现，点它回到全口 --}}
            <a href="javascript:;" class="cs-hist-all" id="csHistAll" style="display:none">
                {{ __('medical_treatment.cs_back_to_all') }}
            </a>
        </div>
        <div id="csHistBody">
            @forelse($chairside['visits'] ?? [] as $visit)
                <div class="cs-visit">
                    <span class="cs-date">{{ $visit['date'] }}</span>
                    <span class="cs-what">{{ $visit['summary'] ?: __('today_work.no_records') }}</span>
                    <span class="cs-who">{{ $visit['doctor_name'] }}</span>
                </div>
            @empty
                <div class="cs-empty">{{ __('medical_treatment.cs_no_history') }}</div>
            @endforelse
        </div>
    </div>
</div>
