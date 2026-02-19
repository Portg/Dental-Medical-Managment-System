@extends('layouts.list-page')

@section('page_title', __('leaves.leave_requests_approval.title'))
@section('table_id', 'leave-requests_table')

@section('header_actions')
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('leaves.employee') }}</th>
    <th>{{ __('leaves.request_date') }}</th>
    <th>{{ __('leaves.leave_type') }}</th>
    <th>{{ __('leaves.start_date') }}</th>
    <th>{{ __('leaves.duration') }}</th>
    <th>{{ __('leaves.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {

            dataTable = $('#leave-requests_table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/leave-requests-approval/') }}",
                    data: function (d) {
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'addedBy', name: 'addedBy'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'name', name: 'name'},
                    {data: 'start_date', name: 'start_date'},
                    {data: 'duration', name: 'duration'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();

        });

        function approveRequest(id) {
            swal({
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('leaves.confirm_approve_request') }}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-warning",
                    confirmButtonText: "{{ __('leaves.yes_approve_request') }}",
                    closeOnConfirm: false
                },
                function () {
                    $.LoadingOverlay("show");
                    $.ajax({
                        type: 'get',
                        url: "approve-leave-request/" + id,
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

        function rejectRequest(id) {
            swal({
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('leaves.reject_approve_request') }}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "{{ __('leaves.yes_reject_request') }}",
                    closeOnConfirm: false
                },
                function () {
                    $.LoadingOverlay("show");
                    $.ajax({
                        type: 'get',
                        url: "reject-leave-request/" + id,
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
                let oTable = $('#leave-requests_table').dataTable();
                oTable.fnDraw(false);
            }
        }

    </script>
@endsection
