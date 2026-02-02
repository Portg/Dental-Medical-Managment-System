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
                    <span class="caption-subject"> {{ __('salary_advances.payroll_management') }}/ {{ __('salary_advances.page_title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('salary_advances.add_new') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_1">
                    <thead>
                    <tr>
                        <th>{{ __('salary_advances.id') }}</th>
                        <th>{{ __('salary_advances.employee') }}</th>
                        <th>{{ __('salary_advances.classification') }}</th>
                        <th>{{ __('salary_advances.payslip_month') }}</th>
                        <th>{{ __('salary_advances.amount') }}</th>
                        <th>{{ __('salary_advances.payment_method') }}</th>
                        <th>{{ __('salary_advances.payment_date') }}</th>
                        <th>{{ __('salary_advances.added_by') }}</th>
                        <th>{{ __('salary_advances.edit') }}</th>
                        <th>{{ __('salary_advances.delete') }}</th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('salary_advances.loading') }}</span>
</div>
@include('salary_advances.create')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {
            // 批量加载
            LanguageManager.loadAllFromPHP({
                'salary_advances': @json(__('salary_advances'))
            });

            let table = $('#sample_1').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/salary-advances/') }}",
                    data: function (d) {
                    }
                },
                dom: 'Bfrtip',
                buttons: {
                    buttons: [
                        // {extend: 'pdfHtml5', className: 'pdfButton'},
                        // {extend: 'excelHtml5', className: 'excelButton'},

                    ]
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'employee', name: 'employee'},
                    {data: 'payment_classification', name: 'payment_classification'},
                    {data: 'advance_month', name: 'advance_month'},
                    {data: 'amount', name: 'amount'},
                    {data: 'payment_method', name: 'payment_method'},
                    {data: 'payment_date', name: 'payment_date'},
                    {data: 'addedBy', name: 'addedBy'},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });


        });

        function createRecord() {
            $("#scale-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __('salary_advances.save_changes') }}');
            $('#scale-modal').modal('show');
        }

        //filter employee
        $('#employee').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('salary_advances.choose_employee') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-employee',
                dataType: 'json',
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    console.log(data);
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });


        function save_data() {
            //check save method
            var id = $('#id').val();
            if (id == "") {
                save_new_record();
            } else {
                update_record();
            }
        }

        function save_new_record() {
            $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text('{{ __('salary_advances.processing') }}');
            $.ajax({
                type: 'POST',
                data: $('#scale-form').serialize(),
                url: "/salary-advances",
                success: function (data) {
                    $('#scale-modal').modal('hide');
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
                    $('#btn-save').text('{{ __('salary_advances.save_changes') }}');
                    $('#scale-modal').modal('show');

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
            $("#scale-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "salary-advances/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    $('[name="amount"]').val(data.advance_amount);
                    $('[name="advance_month"]').val(data.advance_month);
                    $('[name="payment_date"]').val(data.payment_date);
                    let employee_data = {
                        id: data.employee_id,
                        text: LanguageManager.joinName(data.surname, data.othername)
                    };
                    let newOption = new Option(employee_data.text, employee_data.id, true, true);
                    $('#employee').append(newOption).trigger('change');
                    $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __('salary_advances.update_record') }}')
                    $('#scale-modal').modal('show');

                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        }

        function update_record() {
            $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text('{{ __('salary_advances.updating') }}');
            $.ajax({
                type: 'PUT',
                data: $('#scale-form').serialize(),
                url: "/salary-advances/" + $('#id').val(),
                success: function (data) {
                    $('#scale-modal').modal('hide');
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
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
                    title: "{{ __('salary_advances.are_you_sure') }}",
                    text: "{{ __('salary_advances.cannot_recover_advance') }}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "{{ __('salary_advances.yes_delete') }}",
                    closeOnConfirm: false
                },
                function () {

                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                    $.LoadingOverlay("show");
                    $.ajax({
                        type: 'delete',
                        data: {
                            _token: CSRF_TOKEN
                        },
                        url: "/salary-advances/" + id,
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
            swal("{{ __('salary_advances.alert') }}", message, status);
            if (status) {
                let oTable = $('#sample_1').dataTable();
                oTable.fnDraw(false);
            }
        }


    </script>
@endsection





