@extends('layouts.list-page')

@section('page_title', __('lab_cases.lab_case_list'))
@section('table_id', 'lab-cases-table')

@section('header_actions')
    <a href="#" onclick="createLabCase()" class="btn btn-primary">
        {{__('lab_cases.add_lab_case') }}
    </a>
    <a href="{{ url('labs') }}" class="btn btn-default">
        {{__('lab_cases.lab_management') }}
    </a>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('lab_cases.status') }}</div>
            <select class="form-control" id="filter_status">
                <option value="">{{ __('lab_cases.all_statuses') }}</option>
                @foreach(\App\LabCase::statusOptions() as $key => $label)
                    <option value="{{ $key }}">{{ __('lab_cases.status_' . $key) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('lab_cases.lab_name_header') }}</div>
            <select class="form-control" id="filter_lab">
                <option value="">{{ __('lab_cases.all_labs') }}</option>
                @foreach($labs as $lab)
                    <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">
                {{ __('common.reset') }}
            </button>
            <button type="button" id="filterBtn" class="btn btn-primary">
                {{ __('common.search') }}
            </button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('lab_cases.id') }}</th>
    <th>{{ __('lab_cases.lab_case_no') }}</th>
    <th>{{ __('lab_cases.patient_name') }}</th>
    <th>{{ __('lab_cases.doctor_name') }}</th>
    <th>{{ __('lab_cases.lab_name_header') }}</th>
    <th>{{ __('lab_cases.prosthesis_type') }}</th>
    <th>{{ __('lab_cases.status') }}</th>
    <th>{{ __('lab_cases.expected_return_date') }}</th>
    <th></th>
    <th>{{ __('lab_cases.actions') }}</th>
@endsection

@section('modals')
    @include('lab_cases.create_modal')
    @include('lab_cases.edit_modal')
    @include('lab_cases.status_modal')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('lab_cases')), 'lab_cases');
    window.labCaseUrls = {
        index:        "{{ url('lab-cases') }}",
        store:        "{{ url('lab-cases') }}",
        update:       "{{ url('lab-cases') }}",
        destroy:      "{{ url('lab-cases') }}",
        apiGet:       "{{ url('api/lab-case') }}",
        updateStatus: "{{ url('lab-cases') }}/__ID__/update-status",
        locale:       "{{ app()->getLocale() }}"
    };
    window.labCaseData = {
        prosthesisTypes: @json(\App\LabCase::prosthesisTypeOptions()),
        materials:       @json(\App\LabCase::materialOptions())
    };
</script>
<script src="{{ asset('include_js/lab_case_list.js') }}?v={{ filemtime(public_path('include_js/lab_case_list.js')) }}"></script>

{{-- 诊疗页「开加工单」跳过来时带着上下文：自动开弹窗并预填。
     入口在诊疗页是因为医生是在那儿决定要做修复体的；加工单表上本来就有
     appointment_id / medical_case_id 两列，此前没有任何入口会带上。

     必须是独立的 <script>：上面那个带 src 的标签，内容体会被浏览器忽略。 --}}
@if(request()->filled('patient_id'))
<script>
    $(function () {
        openLabCaseWithContext({
            patient_id:      @json(request('patient_id')),
            patient_text:    @json(request('patient_text')),
            doctor_id:       @json(request('doctor_id')),
            doctor_text:     @json(request('doctor_text')),
            appointment_id:  @json(request('appointment_id')),
            medical_case_id: @json(request('medical_case_id')),
            teeth:           @json(request('teeth'))
        });
    });
</script>
@endif

@endsection
