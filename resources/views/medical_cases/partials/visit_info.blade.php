{{-- Visit Information Section --}}
<div class="visit-info-section">
    <div class="visit-info-item">
        <label>{{ __('medical_cases.case_date') }}</label>
        <input type="date" name="case_date" id="case_date" class="form-control"
               value="{{ $case->case_date ?? date('Y-m-d') }}">
    </div>
    <div class="visit-info-item">
        <label>{{ __('medical_cases.attending_doctor') }}</label>
        <select name="doctor_id" id="doctor_id" class="form-control">
            <option value="">{{ __('medical_cases.select_doctor') }}</option>
            @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}"
                    {{ (isset($case) && $case->doctor_id == $doctor->id) ? 'selected' : '' }}>
                    {{ $doctor->surname }} {{ $doctor->othername }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="visit-info-item">
        <label>{{ __('medical_cases.visit_type') }}</label>
        <div class="visit-type-radio-group">
            <label class="visit-type-radio">
                <input type="radio" name="visit_type" value="initial"
                    {{ (!isset($case) || $case->visit_type == 'initial') ? 'checked' : '' }}>
                {{ __('medical_cases.visit_type_initial') }}
            </label>
            <label class="visit-type-radio">
                <input type="radio" name="visit_type" value="follow_up"
                    {{ (isset($case) && $case->visit_type == 'follow_up') ? 'checked' : '' }}>
                {{ __('medical_cases.visit_type_follow_up') }}
            </label>
            <label class="visit-type-radio">
                <input type="radio" name="visit_type" value="emergency"
                    {{ (isset($case) && $case->visit_type == 'emergency') ? 'checked' : '' }}>
                {{ __('medical_cases.visit_type_emergency') }}
            </label>
        </div>
    </div>
</div>
