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
                    <span class="caption-subject">{{ __('charts_of_accounts.title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('common.add_new') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <div class="row">
                    <div class="tabbable-line col-md-12">
                        <ul class="nav nav-tabs">
                            @if(isset($AccountingEquations))
                                @foreach($AccountingEquations as $row)
                                    <li class="@if($row->active_tab == 'yes') active @endif">
                                        <a href="#{{ $row->id . '_tab' }}" data-toggle="tab">{{ $row->name }}</a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                        <div class="tab-content">
                            @if(isset($AccountingEquations))
                                @foreach($AccountingEquations as $row)
                                    <div class="tab-pane @if($row->active_tab == 'yes') active @endif" id="{{ $row->id . '_tab' }}">
                                        @foreach($row->Categories as $cat)
                                            <div class="portlet">
                                                <div class="portlet-body">
                                                    <div class="mt-element-list">
                                                        <div class="mt-list-head list-default ext-1 bg-grey">
                                                            <div class="row">
                                                                <div class="col-xs-12">
                                                                    <div class="list-head-title-container">
                                                                        <h3 class="list-title">{{ $cat->name }}</h3>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-list-container list-default ext-1">
                                                            <ul>
                                                                @foreach($cat->Items as $item)
                                                                    <li class="mt-list-item done">
                                                                        <div class="list-icon-container">
                                                                            <a href="javascript:;">
                                                                                <i class="icon-check"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="list-datetime">
                                                                            <a href="javascript:;" onclick="editRecord('{{ $item->id }}')">{{ __('common.edit') }}</a>
                                                                        </div>
                                                                        <div class="list-item-content">
                                                                            <h3 class="uppercase">
                                                                                <a href="javascript:;">{{ $item->name }}</a>
                                                                            </h3>
                                                                            <p>{{ $item->description }}</p>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
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
@include('charts_of_accounts.create')
@endsection
@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'charts_of_accounts': @json(__('charts_of_accounts')),
            'common': @json(__('common'))
        });
    });

    function createRecord() {
        $("#chart_of_accounts-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#chart_of_accounts-modal').modal('show');
    }

    function save_data() {
        if ($('#id').val() === "") {
            save_new_record();
        } else {
            update_record();
        }
    }

    function save_new_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.processing") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'POST',
            data: $('#chart_of_accounts-form').serialize(),
            url: "{{ url('/charts-of-accounts-items') }}",
            success: function (data) {
                $('#chart_of_accounts-modal').modal('hide');
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
        $("#chart_of_accounts-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);

        $.ajax({
            type: 'get',
            url: "{{ url('/charts-of-accounts-items') }}/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                $('[name="name"]').val(data.name);
                $('[name="description"]').val(data.description);

                $(".account_type").find("option").each(function () {
                    if ($(this).val() === data.chart_of_account_category_id) {
                        $(this).prop("selected", "selected");
                    }
                });

                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#chart_of_accounts-modal').modal('show');
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    }

    function update_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.updating") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'PUT',
            data: $('#chart_of_accounts-form').serialize(),
            url: "{{ url('/charts-of-accounts-items') }}/" + $('#id').val(),
            success: function (data) {
                $('#chart_of_accounts-modal').modal('hide');
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
            text: "{{ __('charts_of_accounts.delete_confirm_message') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            closeOnConfirm: false,
            cancelButtonText: "{{ __('common.cancel') }}"
        }, function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
                url: "{{ url('/charts-of-accounts-items') }}/" + id,
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
        setTimeout(function () {
            location.reload();
        }, 1900);
    }
</script>
@endsection
