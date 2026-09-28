{{-- 患者条 —— 接诊时必须常驻的身份、警示与两个方向的钱。

     替掉原来那条只写了「查看预约 / 姓名(病历号)」的青色栏：那条占了一整行
     却只给了面包屑，而药物过敏这种用药安全信息反倒藏在第三个页签里。

     警示位只收药物过敏与系统病（见 ChairsideService::alerts）—— 什么都往里放，
     医生就不再看它了，而这一条的全部价值就是扎眼。整页也只有这一处用警示色。 --}}
@php($cs = $chairside['bar'] ?? null)
@if($cs)
<div class="cs-bar">
    <div class="cs-who">
        <a href="{{ url('patients/' . $cs['id']) }}" class="cs-name">{{ $cs['name'] }}</a>
        <span class="cs-meta">
            @if($cs['gender']){{ __('patient.' . strtolower($cs['gender'])) }}@endif
            @if($cs['age'])· {{ $cs['age'] }}@endif
            · {{ $cs['patient_no'] }}
        </span>
    </div>

    @if(!empty($cs['alerts']))
        <div class="cs-alerts">
            @foreach($cs['alerts'] as $alert)
                <span class="cs-alert cs-alert-{{ $alert['type'] }}">{{ $alert['text'] }}</span>
            @endforeach
        </div>
    @endif

    {{-- 欠费＝患者欠诊所，预收未兑现＝诊所欠患者。两者处理动作相反，不同色。 --}}
    <div class="cs-money">
        @if($cs['outstanding'] > 0)
            <a href="{{ url('patients/' . $cs['id']) }}#billing_tab" class="cs-m cs-m-owed">
                <em>{{ __('today_work.kpi_outstanding') }}</em>
                <b>&yen;{{ number_format($cs['outstanding'], 2) }}</b>
                <i>{{ $cs['open_invoices'] }} {{ __('today_work.paid_count_unit') }}</i>
            </a>
        @endif
        @if($cs['prepaid_value'] > 0)
            <a href="{{ url('patients/' . $cs['id']) }}#billing_tab" class="cs-m cs-m-prepaid">
                <em>{{ __('prepaid.summary_prepaid') }}</em>
                <b>&yen;{{ number_format($cs['prepaid_value'], 2) }}</b>
                <i>{{ __('prepaid.remaining_qty') }} {{ $cs['prepaid_qty'] }}</i>
            </a>
        @endif
    </div>

    {{-- 就诊状态：原先在青色栏右端，位置保留，形态不变 --}}
    <div class="cs-actions">
        <form action="#" id="appointment-status-form" autocomplete="off" class="cs-status-form">
            @csrf
            <select name="appointment_status" class="form-control input-sm">
                <option value="null">{{ __('medical_treatment.select_appointment_action') }}</option>
                <option value="Treatment Complete">{{ __('medical_treatment.treatment_complete') }}</option>
                <option value="Treatment Incomplete">{{ __('medical_treatment.treatment_incomplete') }}</option>
            </select>
            <input type="hidden" name="appointment_id" value="{{ $appointment_id }}">
            <button type="button" class="btn btn-sm btn-primary" id="btn-appointment-status"
                    onclick="save_appointment_status();">{{ __('medical_treatment.save') }}</button>
        </form>
    </div>
</div>
@endif
