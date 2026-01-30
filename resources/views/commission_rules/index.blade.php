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
                    <i class="icon-calculator"></i>
                    <span class="caption-subject">{{ __('commission_rules.title') }}</span>
                </div>
                <div class="actions">
                    <a class="btn blue btn-outline sbold" href="#" onclick="createRecord()">
                        {{ __('common.add_new') }} <i class="fa fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover" id="commission_table">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('commission_rules.rule_name') }}</th>
                        <th>{{ __('commission_rules.mode') }}</th>
                        <th>{{ __('commission_rules.rate') }}</th>
                        <th>{{ __('commission_rules.service') }}</th>
                        <th>{{ __('commission_rules.branch') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.edit') }}</th>
                        <th>{{ __('common.delete') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('commission_rules.create')
@endsection
@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script type="text/javascript">
$(function () {
    var table = $('#commission_table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "{{ url('/commission-rules/') }}"
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'rule_name', name: 'rule_name'},
            {data: 'mode_label', name: 'mode_label'},
            {data: 'rate_display', name: 'rate_display'},
            {data: 'service_name', name: 'service_name'},
            {data: 'branch_name', name: 'branch_name'},
            {data: 'status', name: 'status'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
});

function toggleMode(mode) {
    $('.mode-options').hide();
    if (mode === 'fixed_percentage') {
        $('#fixed_percentage_options').show();
    } else if (mode === 'fixed_amount') {
        $('#fixed_amount_options').show();
    } else if (mode === 'tiered' || mode === 'mixed') {
        if (mode === 'mixed') {
            $('#fixed_percentage_options').show();
        }
        $('#tiered_options').show();
    }
}

function createRecord() {
    $("#commission-form")[0].reset();
    $('#id').val('');
    $('.mode-options').hide();
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text('{{ __("common.save_record") }}');
    $('#commission-modal').modal('show');
}

function save_data() {
    var id = $('#id').val();
    if (id === "") {
        save_new_record();
    } else {
        update_record();
    }
}

function save_new_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text('{{ __("common.processing") }}');
    $.ajax({
        type: 'POST',
        data: $('#commission-form').serialize(),
        url: "/commission-rules",
        success: function (data) {
            $('#commission-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_record") }}');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#commission-form")[0].reset();
    $('#id').val('');
    $('.mode-options').hide();
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: "commission-rules/" + id + "/edit",
        success: function (data) {
            $('#id').val(id);
            $('[name="rule_name"]').val(data.rule_name);
            $('[name="commission_mode"]').val(data.commission_mode);
            toggleMode(data.commission_mode);
            $('[name="target_service_type"]').val(data.target_service_type);
            $('[name="medical_service_id"]').val(data.medical_service_id);
            $('[name="base_commission_rate"]').val(data.base_commission_rate);
            $('[name="tier1_threshold"]').val(data.tier1_threshold);
            $('[name="tier1_rate"]').val(data.tier1_rate);
            $('[name="tier2_threshold"]').val(data.tier2_threshold);
            $('[name="tier2_rate"]').val(data.tier2_rate);
            $('[name="tier3_threshold"]').val(data.tier3_threshold);
            $('[name="tier3_rate"]').val(data.tier3_rate);
            $('[name="bonus_amount"]').val(data.bonus_amount);
            $('[name="branch_id"]').val(data.branch_id);
            if (data.is_active) {
                $('[name="is_active"]').prop('checked', true);
            }

            $.LoadingOverlay("hide");
            $('#btn-save').text('{{ __("common.update_record") }}');
            $('#commission-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text('{{ __("common.processing") }}');
    $.ajax({
        type: 'PUT',
        data: $('#commission-form').serialize(),
        url: "/commission-rules/" + $('#id').val(),
        success: function (data) {
            $('#commission-modal').modal('hide');
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
            $.LoadingOverlay("hide");
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.update_record") }}');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
        title: "{{ __('common.are_you_sure') }}",
        text: "{{ __('commission_rules.delete_confirm') }}",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "{{ __('common.yes_delete_it') }}",
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: { _token: CSRF_TOKEN },
            url: "commission-rules/" + id,
            success: function (data) {
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
                $.LoadingOverlay("hide");
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    });
}

function alert_dialog(message, status) {
    swal("{{ __('common.alert') }}", message, status);
    if (status) {
        let oTable = $('#commission_table').dataTable();
        oTable.fnDraw(false);
    }
}
</script>
@endsection
