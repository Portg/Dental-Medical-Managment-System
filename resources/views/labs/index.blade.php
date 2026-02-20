@extends('layouts.list-page')

@section('page_title', __('lab_cases.lab_list'))
@section('table_id', 'labs-table')

@section('header_actions')
    <a href="#" onclick="createLab()" class="btn btn-primary">
        {{ __('lab_cases.add_lab') }}
    </a>
    <a href="{{ url('lab-cases') }}" class="btn btn-default">
        {{ __('lab_cases.lab_cases') }}
    </a>
@endsection

@section('table_headers')
    <th>{{ __('lab_cases.id') }}</th>
    <th>{{ __('lab_cases.lab_name') }}</th>
    <th>{{ __('lab_cases.contact') }}</th>
    <th>{{ __('lab_cases.phone') }}</th>
    <th>{{ __('lab_cases.specialties') }}</th>
    <th>{{ __('lab_cases.avg_turnaround_days') }}</th>
    <th>{{ __('lab_cases.is_active') }}</th>
    <th>{{ __('lab_cases.actions') }}</th>
@endsection

@section('modals')
    @include('labs.create_modal')
    @include('labs.edit_modal')
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'lab_cases': @json(__('lab_cases'))
            });

            dataTable = $('#labs-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/labs/') }}"
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'name', name: 'name'},
                    {data: 'contact', name: 'contact'},
                    {data: 'phone', name: 'phone'},
                    {data: 'specialties', name: 'specialties'},
                    {data: 'avg_turnaround_days', name: 'avg_turnaround_days'},
                    {data: 'status_label', name: 'is_active', orderable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        function createLab() {
            $("#create-lab-form")[0].reset();
            $('.alert-danger').hide().find('ul').html('');
            $('#create-lab-modal').modal('show');
        }

        function saveLab() {
            $.LoadingOverlay("show");
            $('#btn-create-lab').attr('disabled', true).text('{{ __("common.processing") }}');
            $.ajax({
                type: 'POST',
                data: $('#create-lab-form').serialize(),
                url: "{{ url('labs') }}",
                success: function (data) {
                    $.LoadingOverlay("hide");
                    $('#create-lab-modal').modal('hide');
                    swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
                    if (data.status) {
                        $('#labs-table').DataTable().draw(false);
                    }
                },
                error: function (request) {
                    $.LoadingOverlay("hide");
                    $('#btn-create-lab').attr('disabled', false).text('{{ __("common.save_changes") }}');
                    var json = $.parseJSON(request.responseText);
                    var errors = '';
                    $.each(json.errors || {}, function (key, value) {
                        errors += '<li>' + value + '</li>';
                    });
                    $('.alert-danger').show().find('ul').html(errors);
                }
            });
        }

        function editLab(id) {
            $.LoadingOverlay("show");
            $.ajax({
                type: 'GET',
                url: "{{ url('labs') }}/" + id,
                success: function (data) {
                    $.LoadingOverlay("hide");
                    $('#edit_lab_id').val(data.id);
                    $('#edit_lab_name').val(data.name);
                    $('#edit_lab_contact').val(data.contact);
                    $('#edit_lab_phone').val(data.phone);
                    $('#edit_lab_address').val(data.address);
                    $('#edit_lab_specialties').val(data.specialties);
                    $('#edit_lab_avg_turnaround_days').val(data.avg_turnaround_days);
                    $('#edit_lab_is_active').prop('checked', data.is_active);
                    $('.alert-danger').hide().find('ul').html('');
                    $('#edit-lab-modal').modal('show');
                },
                error: function () {
                    $.LoadingOverlay("hide");
                }
            });
        }

        function updateLab() {
            var id = $('#edit_lab_id').val();
            $.LoadingOverlay("show");
            $('#btn-update-lab').attr('disabled', true).text('{{ __("common.processing") }}');
            $.ajax({
                type: 'PUT',
                data: $('#edit-lab-form').serialize(),
                url: "{{ url('labs') }}/" + id,
                success: function (data) {
                    $.LoadingOverlay("hide");
                    $('#edit-lab-modal').modal('hide');
                    swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
                    if (data.status) {
                        $('#labs-table').DataTable().draw(false);
                    }
                },
                error: function (request) {
                    $.LoadingOverlay("hide");
                    $('#btn-update-lab').attr('disabled', false).text('{{ __("common.save_changes") }}');
                    var json = $.parseJSON(request.responseText);
                    var errors = '';
                    $.each(json.errors || {}, function (key, value) {
                        errors += '<li>' + value + '</li>';
                    });
                    $('.alert-danger').show().find('ul').html(errors);
                }
            });
        }

        function deleteLab(id) {
            swal({
                title: "{{ __('lab_cases.are_you_sure') }}",
                text: "{{ __('lab_cases.confirm_delete_lab') }}",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "{{ __('lab_cases.yes_delete_it') }}",
                cancelButtonText: "{{ __('lab_cases.cancel') }}",
                closeOnConfirm: false
            }, function () {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.LoadingOverlay("show");
                $.ajax({
                    type: 'DELETE',
                    data: {_token: CSRF_TOKEN},
                    url: "{{ url('labs') }}/" + id,
                    success: function (data) {
                        $.LoadingOverlay("hide");
                        swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
                        if (data.status) {
                            $('#labs-table').DataTable().draw(false);
                        }
                    },
                    error: function () {
                        $.LoadingOverlay("hide");
                    }
                });
            });
        }
    </script>
@endsection
