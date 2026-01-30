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
                    <span class="caption-subject"> {{ __('leaves.leave_requests_approval.title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="leave-requests_table">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('leaves.employee') }}</th>
                        <th>{{ __('leaves.request_date') }}</th>
                        <th>{{ __('leaves.leave_type') }}</th>
                        <th>{{ __('leaves.start_date') }}</th>
                        <th>{{ __('leaves.duration') }}</th>
                        <th>{{ __('leaves.status') }}</th>
                        <th>{{ __('common.action') }}</th>
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
    <span>{{ __('common.loading') }}</span>
</div>
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {

            var table = $('#leave-requests_table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language : LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/leave-requests-approval/') }}",
                    data: function (d) {
                        // d.email = $('.searchEmail').val(),
                        //     d.search = $('input[type="search"]').val()
                    }
                },
                dom: 'Bfrtip',
                buttons: {
                    buttons: []
                },
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





