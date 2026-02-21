@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">
                        <a href="{{ url('medical-cases') }}" class="text-primary">{{ __('medical_cases.page_title') }}</a>
                        / {{ $case->case_no }} - {{ $case->title }}
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ url('medical-cases/' . $case->id . '/export-pdf') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-file-pdf-o"></i> {{ __('medical_cases.export_pdf') }}
                    </a>
                    <a href="{{ url('print-medical-case/' . $case->id) }}" target="_blank" class="btn btn-default btn-sm">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    <a href="{{ url('medical-cases/' . $case->id . '/edit') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-edit"></i> {{ __('common.edit') }}
                    </a>
                    @if($case->status == 'Open')
                        <span class="label label-success">{{ __('medical_cases.status_open') }}</span>
                    @elseif($case->status == 'Closed')
                        <span class="label label-danger">{{ __('medical_cases.status_closed') }}</span>
                    @else
                        <span class="label label-warning">{{ __('medical_cases.status_follow_up') }}</span>
                    @endif
                    <span class="label label-default">v{{ $case->version_number ?? 1 }}</span>
                    @if($case->locked_at)
                        <span class="label label-warning"><i class="fa fa-lock"></i> {{ __('medical_cases.record_locked') }}</span>
                    @endif
                    @if($case->signed_at)
                        <span class="label label-info"><i class="fa fa-pencil"></i> {{ __('medical_cases.signed') }}</span>
                    @endif
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.patient') }}:</strong><br>
                            {{ $case->patient->full_name }}
                            ({{ $case->patient->patient_no }})
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.doctor') }}:</strong><br>
                            @if($case->doctor)
                                {{ $case->doctor->full_name }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.case_date') }}:</strong><br>
                            {{ $case->case_date }}
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.added_by') }}:</strong><br>
                            @if($case->addedBy)
                                {{ $case->addedBy->full_name }}
                            @endif
                        </p>
                    </div>
                </div>
                @if($case->chief_complaint)
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>{{ __('medical_cases.chief_complaint') }}:</strong><br>
                                {{ $case->chief_complaint }}
                            </p>
                        </div>
                    </div>
                @endif
                @if($case->history_of_present_illness)
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>{{ __('medical_cases.history_of_present_illness') }}:</strong><br>
                                {{ $case->history_of_present_illness }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="global_case_id" value="{{ $case->id }}">
<input type="hidden" id="global_patient_id" value="{{ $case->patient_id }}">

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-body">
                <div class="tabbable-line">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#diagnoses_tab" data-toggle="tab">{{ __('medical_cases.diagnoses') }}</a>
                        </li>
                        <li>
                            <a href="#progress_notes_tab" data-toggle="tab">{{ __('medical_cases.progress_notes') }}</a>
                        </li>
                        <li>
                            <a href="#treatment_plans_tab" data-toggle="tab">{{ __('medical_cases.treatment_plans') }}</a>
                        </li>
                        <li>
                            <a href="#vital_signs_tab" data-toggle="tab">{{ __('medical_cases.vital_signs') }}</a>
                        </li>
                        <li>
                            <a href="#appointments_tab" data-toggle="tab">{{ __('medical_cases.related_appointments') }}</a>
                        </li>
                        <li>
                            <a href="#version_history_tab" data-toggle="tab">{{ __('medical_cases.version_history') }}</a>
                        </li>
                        <li>
                            <a href="#amendments_tab" data-toggle="tab">
                                {{ __('medical_cases.amendments') }}
                                @if($case->hasPendingAmendment())
                                    <span class="badge badge-warning">!</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Diagnoses Tab -->
                        <div class="tab-pane active" id="diagnoses_tab">
                            <div class="table-toolbar">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group">
                                            <button type="button" class="btn blue btn-outline sbold" onclick="addDiagnosis()">
                                                {{ __('common.add_new') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="diagnoses_table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('medical_cases.diagnosis_name') }}</th>
                                    <th>{{ __('medical_cases.icd_code') }}</th>
                                    <th>{{ __('medical_cases.diagnosis_date') }}</th>
                                    <th>{{ __('medical_cases.severity') }}</th>
                                    <th>{{ __('medical_cases.status') }}</th>
                                    <th>{{ __('common.edit') }}</th>
                                    <th>{{ __('common.delete') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Progress Notes Tab -->
                        <div class="tab-pane" id="progress_notes_tab">
                            <div class="table-toolbar">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group">
                                            <button type="button" class="btn blue btn-outline sbold" onclick="addProgressNote()">
                                                {{ __('common.add_new') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="progress_notes_table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('medical_cases.note_date') }}</th>
                                    <th>{{ __('medical_cases.note_type') }}</th>
                                    <th>{{ __('medical_cases.added_by') }}</th>
                                    <th>{{ __('common.view') }}</th>
                                    <th>{{ __('common.edit') }}</th>
                                    <th>{{ __('common.delete') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Treatment Plans Tab -->
                        <div class="tab-pane" id="treatment_plans_tab">
                            <div class="table-toolbar">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group">
                                            <button type="button" class="btn blue btn-outline sbold" onclick="addTreatmentPlan()">
                                                {{ __('common.add_new') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="treatment_plans_table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('medical_cases.plan_name') }}</th>
                                    <th>{{ __('medical_cases.priority') }}</th>
                                    <th>{{ __('medical_cases.estimated_cost') }}</th>
                                    <th>{{ __('medical_cases.status') }}</th>
                                    <th>{{ __('common.view') }}</th>
                                    <th>{{ __('common.edit') }}</th>
                                    <th>{{ __('common.delete') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Vital Signs Tab -->
                        <div class="tab-pane" id="vital_signs_tab">
                            <div class="table-toolbar">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group">
                                            <button type="button" class="btn blue btn-outline sbold" onclick="addVitalSign()">
                                                {{ __('common.add_new') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="vital_signs_table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('medical_cases.recorded_at') }}</th>
                                    <th>{{ __('medical_cases.blood_pressure') }}</th>
                                    <th>{{ __('medical_cases.heart_rate') }}</th>
                                    <th>{{ __('medical_cases.temperature') }}</th>
                                    <th>{{ __('medical_cases.added_by') }}</th>
                                    <th>{{ __('common.edit') }}</th>
                                    <th>{{ __('common.delete') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Related Appointments Tab -->
                        <div class="tab-pane" id="appointments_tab">
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="case_appointments_table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('medical_cases.appointment_no') }}</th>
                                    <th>{{ __('medical_cases.appointment_date') }}</th>
                                    <th>{{ __('medical_cases.status') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Version History Tab -->
                        <div class="tab-pane" id="version_history_tab">
                            <br>
                            <div id="version_history_content">
                                <p class="text-muted">{{ __('common.loading') }}...</p>
                            </div>
                        </div>

                        <!-- Amendments Tab -->
                        <div class="tab-pane" id="amendments_tab">
                            <br>
                            <div id="amendments_content">
                                <p class="text-muted">{{ __('common.loading') }}...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>

@include('medical_cases.diagnoses.create')
@include('medical_cases.progress_notes.create')
@include('medical_cases.progress_notes.view')
@include('medical_cases.treatment_plans.create')
@include('medical_cases.treatment_plans.view')
@include('medical_cases.vital_signs.create')

@endsection
@section('js')
    <script>
        var global_case_id = {{ $case->id }};
        var global_patient_id = {{ $case->patient_id }};
        var doctors = @json($doctors);
        var canApproveAmendment = {{ auth()->user()->can('approve-medical-case-amendment') ? 'true' : 'false' }};

        // Load medical_cases and messages translations for JavaScript
        LanguageManager.loadAllFromPHP({
            'medical_cases': @json(__('medical_cases')),
            'messages': @json(__('messages')),
            'common': @json(__('common'))
        });

        // Lazy-load version history and amendments tabs
        $(document).ready(function() {
            var versionLoaded = false, amendmentsLoaded = false;

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');

                if (target === '#version_history_tab' && !versionLoaded) {
                    versionLoaded = true;
                    loadVersionHistory();
                }
                if (target === '#amendments_tab' && !amendmentsLoaded) {
                    amendmentsLoaded = true;
                    loadAmendments();
                }
            });
        });

        function loadVersionHistory() {
            $.getJSON('/medical-cases/' + global_case_id + '/version-history', function(res) {
                if (!res.status || !res.data.length) {
                    $('#version_history_content').html('<p class="text-muted">' + LanguageManager.trans('common.no_data_found') + '</p>');
                    return;
                }
                var html = '<table class="table table-striped table-bordered"><thead><tr>'
                    + '<th>' + LanguageManager.trans('common.time') + '</th>'
                    + '<th>' + LanguageManager.trans('common.operator') + '</th>'
                    + '<th>' + LanguageManager.trans('common.action') + '</th>'
                    + '<th>' + LanguageManager.trans('medical_cases.modification_reason') + '</th>'
                    + '</tr></thead><tbody>';
                $.each(res.data, function(i, audit) {
                    var userName = audit.user ? audit.user.full_name : '-';
                    var reason = (audit.new_values && audit.new_values.modification_reason) || '-';
                    html += '<tr><td>' + audit.created_at + '</td><td>' + userName
                        + '</td><td>' + LanguageManager.trans('common.audit_event_' + audit.event)
                        + '</td><td>' + reason + '</td></tr>';
                });
                html += '</tbody></table>';
                $('#version_history_content').html(html);
            });
        }

        function loadAmendments() {
            $.getJSON('/medical-cases/' + global_case_id + '/amendments', function(res) {
                if (!res.status || !res.data.length) {
                    $('#amendments_content').html('<p class="text-muted">' + LanguageManager.trans('common.no_data_found') + '</p>');
                    return;
                }
                var html = '<table class="table table-striped table-bordered"><thead><tr>'
                    + '<th>' + LanguageManager.trans('common.time') + '</th>'
                    + '<th>' + LanguageManager.trans('medical_cases.amendment_requested_by') + '</th>'
                    + '<th>' + LanguageManager.trans('medical_cases.amendment_reason') + '</th>'
                    + '<th>' + LanguageManager.trans('medical_cases.amendment_status') + '</th>'
                    + '<th>' + LanguageManager.trans('common.actions') + '</th>'
                    + '</tr></thead><tbody>';
                $.each(res.data, function(i, a) {
                    var requester = a.requested_by_user ? a.requested_by_user.full_name : '-';
                    var statusLabel = '';
                    if (a.status === 'pending') statusLabel = '<span class="label label-warning">' + LanguageManager.trans('medical_cases.amendment_pending') + '</span>';
                    else if (a.status === 'approved') statusLabel = '<span class="label label-success">' + LanguageManager.trans('medical_cases.amendment_approved') + '</span>';
                    else statusLabel = '<span class="label label-danger">' + LanguageManager.trans('medical_cases.amendment_rejected') + '</span>';

                    var actions = '-';
                    if (a.status === 'pending' && canApproveAmendment) {
                        actions = '<button class="btn btn-xs btn-success" onclick="approveAmendment(' + a.id + ')">' + LanguageManager.trans('medical_cases.approve_amendment') + '</button> '
                            + '<button class="btn btn-xs btn-danger" onclick="rejectAmendment(' + a.id + ')">' + LanguageManager.trans('medical_cases.reject_amendment') + '</button>';
                    } else if (a.status !== 'pending' && a.review_notes) {
                        actions = a.review_notes;
                    }

                    html += '<tr><td>' + a.created_at + '</td><td>' + requester
                        + '</td><td>' + a.amendment_reason
                        + '</td><td>' + statusLabel
                        + '</td><td>' + actions + '</td></tr>';
                });
                html += '</tbody></table>';
                $('#amendments_content').html(html);
            });
        }

        function approveAmendment(id) {
            var notes = prompt(LanguageManager.trans('medical_cases.amendment_review_notes'));
            if (notes === null) return;
            $.post('/medical-case-amendments/' + id + '/approve', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                review_notes: notes
            }, function(res) {
                swal(res.message, '', res.status ? 'success' : 'error');
                if (res.status) { loadAmendments(); }
            });
        }

        function rejectAmendment(id) {
            var notes = prompt(LanguageManager.trans('medical_cases.reject_reason_required'));
            if (!notes || notes.length < 5) {
                swal(LanguageManager.trans('medical_cases.reject_reason_required'), '', 'warning');
                return;
            }
            $.post('/medical-case-amendments/' + id + '/reject', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                review_notes: notes
            }, function(res) {
                swal(res.message, '', res.status ? 'success' : 'error');
                if (res.status) { loadAmendments(); }
            });
        }
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/diagnoses.js') }}"></script>
    <script src="{{ asset('include_js/progress_notes.js') }}"></script>
    <script src="{{ asset('include_js/treatment_plans.js') }}"></script>
    <script src="{{ asset('include_js/vital_signs.js') }}"></script>
@endsection
