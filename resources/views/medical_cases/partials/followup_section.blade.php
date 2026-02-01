{{-- Follow-up Section --}}
<div class="followup-section">
    <div class="followup-section-title">{{ __('medical_cases.followup_section') }}</div>
    <div class="followup-row">
        <div class="followup-item">
            <label>{{ __('medical_cases.next_visit_date') }}</label>
            <input type="date" name="next_visit_date" id="next_visit_date" class="form-control"
                   value="{{ isset($case) && $case->next_visit_date ? $case->next_visit_date->format('Y-m-d') : '' }}">
        </div>
        <div class="followup-item" style="flex: 2;">
            <label>{{ __('medical_cases.next_visit_note') }}</label>
            <input type="text" name="next_visit_note" id="next_visit_note" class="form-control"
                   placeholder="{{ __('medical_cases.followup_notes_placeholder') }}"
                   value="{{ $case->next_visit_note ?? '' }}">
        </div>
    </div>
    <div class="followup-checkbox">
        <input type="checkbox" name="auto_create_followup" id="auto_create_followup" value="1"
               {{ (isset($case) && $case->auto_create_followup) ? 'checked' : '' }}>
        <label for="auto_create_followup">{{ __('medical_cases.auto_create_followup') }}</label>
    </div>
</div>
