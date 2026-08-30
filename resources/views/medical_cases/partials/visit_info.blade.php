{{-- Visit Information Section --}}
<div class="visit-info-section">
    <div class="visit-info-item">
        <label>{{ __('medical_cases.case_date') }}</label>
        <input type="text" name="case_date" id="case_date" class="form-control js-date" autocomplete="off"
               value="{{ isset($case) && $case->case_date ? $case->case_date->format('Y-m-d') : date('Y-m-d') }}">
    </div>
    {{-- 从工作台「开病历」进来时，接诊医生与就诊类型跟着这次挂号走
         （$prefillDoctorId / $prefillVisitType）。挂号时刚选过一次医生，
         到病历页再让医生自己选一遍，选错了病历就挂在别人名下。 --}}
    <div class="visit-info-item">
        <label>{{ __('medical_cases.attending_doctor') }}</label>
        <select name="doctor_id" id="doctor_id" class="form-control">
            <option value="">{{ __('medical_cases.select_doctor') }}</option>
            @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}"
                    {{ (isset($case) && $case->doctor_id == $doctor->id)
                        || (!isset($case) && ($prefillDoctorId ?? null) == $doctor->id) ? 'selected' : '' }}>
                    {{ $doctor->full_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="visit-info-item">
        <label>{{ __('medical_cases.visit_type') }}</label>
        {{-- 只有初诊/复诊两种：数据库 enum 就是 ('initial','revisit')。

             此前这里还有「随访」(follow_up) 和「急诊」(emergency) 两个选项，
             而 enum 里没有这两个值 —— MySQL 非严格模式下会**静默写成空字符串**，
             选了等于没选，打印页的 visit_type_ 翻译也会落空。实测确认过。

             市场上的做法也是初诊/复诊两种（参考的那套桌面软件病历页底部就是
             「新增【初诊病历】」「新增【复诊病历】」并列）。急诊属于就诊性质，
             真要区分该走预约类型，不该塞在病历的就诊类型里。 --}}
        <div class="visit-type-radio-group">
            @php
                $currentVisitType = isset($case)
                    ? $case->visit_type
                    : ($prefillVisitType ?? 'initial');
            @endphp
            <label class="visit-type-radio">
                <input type="radio" name="visit_type" value="initial"
                    {{ $currentVisitType !== 'revisit' ? 'checked' : '' }}>
                {{ __('medical_cases.visit_type_initial') }}
            </label>
            <label class="visit-type-radio">
                <input type="radio" name="visit_type" value="revisit"
                    {{ $currentVisitType === 'revisit' ? 'checked' : '' }}>
                {{ __('medical_cases.visit_type_revisit') }}
            </label>
        </div>
    </div>

    {{-- 就诊次数：这是该患者的第几次就诊。参考视频顶部的「就诊次数 2」——
         一个疗程要来好几次，医生一眼要知道这是第几次。
         算出来而不是存字段：存了就要维护，删一份病历还得回填。 --}}
    @isset($visitSequence)
        <div class="visit-info-item visit-seq">
            <label>{{ __('medical_cases.visit_sequence') }}</label>
            <div class="visit-seq-value">{{ $visitSequence }}</div>
        </div>
    @endisset
</div>
