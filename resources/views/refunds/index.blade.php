@extends('layouts.list-page')

@section('page_title', __('invoices.refunds'))

@section('table_id', 'refunds_table')

@section('header_actions')
    <a href="{{ url('refunds/create') }}" class="btn btn-primary">
        {{ __('invoices.new_refund') }}
    </a>
    <a href="{{ url('refunds/pending-approvals') }}" class="btn btn-warning">
        {{ __('invoices.pending_approvals') }}
        <span class="badge badge-danger" id="pending-count"></span>
    </a>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('common.start_date') }}</label>
                <input type="text" class="form-control datepicker" id="start_date" name="start_date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('common.end_date') }}</label>
                <input type="text" class="form-control datepicker" id="end_date" name="end_date">
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>{{ __('common.status') }}</label>
                <select class="form-control" id="status_filter">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="pending">{{ __('invoices.refund_pending') }}</option>
                    <option value="approved">{{ __('invoices.refund_approved') }}</option>
                    <option value="rejected">{{ __('invoices.refund_rejected') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>{{ __('common.search') }}</label>
                <input type="text" class="form-control" id="search_filter" placeholder="{{ __('invoices.search_refund') }}">
            </div>
        </div>
        <div class="col-md-2 text-right filter-actions">
            <div class="form-group">
                <label>&nbsp;</label>
                <div>
                    <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
                    <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('invoices.refund_no') }}</th>
    <th>{{ __('invoices.invoice_no') }}</th>
    <th>{{ __('patient.name') }}</th>
    <th>{{ __('invoices.refund_amount') }}</th>
    <th>{{ __('invoices.refund_date') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
<!-- Approve Modal -->
<div class="modal fade modal-form" id="approveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('invoices.approve_refund') }}</h4>
            </div>
            <div class="modal-body">
                <p>{{ __('invoices.confirm_approve_refund') }}</p>
                <input type="hidden" id="approve_refund_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-success" onclick="confirmApprove()">{{ __('invoices.approve') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade modal-form" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('invoices.reject_refund') }}</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject_refund_id">
                <div class="form-group">
                    <label>{{ __('invoices.rejection_reason') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejection_reason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">{{ __('invoices.reject') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'invoices': @json(__('invoices')),
            'common': @json(__('common'))
        });

        dataTable = $('#refunds_table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('refunds') }}",
                data: function (d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.status = $('#status_filter').val();
                    d.search = $('#search_filter').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'refund_no', name: 'refund_no'},
                {data: 'invoice_no', name: 'invoice_no'},
                {data: 'patient_name', name: 'patient_name'},
                {data: 'refund_amount', name: 'refund_amount'},
                {data: 'refund_date', name: 'refund_date'},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[1, 'desc']]
        });

        setupEmptyStateHandler();
        loadPendingCount();

        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    });

    function loadPendingCount() {
        $.get("{{ url('refunds/pending-count') }}", function (data) {
            if (data.count > 0) {
                $('#pending-count').text(data.count).show();
            } else {
                $('#pending-count').hide();
            }
        });
    }

    function approveRefund(id) {
        $('#approve_refund_id').val(id);
        $('#approveModal').modal('show');
    }

    function confirmApprove() {
        var id = $('#approve_refund_id').val();
        $.ajax({
            url: "{{ url('refunds') }}/" + id + "/approve",
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function (response) {
                $('#approveModal').modal('hide');
                if (response.status) {
                    toastr.success(response.message);
                    dataTable.ajax.reload();
                    loadPendingCount();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function () {
                toastr.error("{{ __('messages.error_occurred') }}");
            }
        });
    }

    function rejectRefund(id) {
        $('#reject_refund_id').val(id);
        $('#rejection_reason').val('');
        $('#rejectModal').modal('show');
    }

    function confirmReject() {
        var id = $('#reject_refund_id').val();
        var reason = $('#rejection_reason').val();
        if (!reason) {
            toastr.error("{{ __('invoices.rejection_reason_required') }}");
            return;
        }
        $.ajax({
            url: "{{ url('refunds') }}/" + id + "/reject",
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', rejection_reason: reason},
            success: function (response) {
                $('#rejectModal').modal('hide');
                if (response.status) {
                    toastr.success(response.message);
                    dataTable.ajax.reload();
                    loadPendingCount();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function () {
                toastr.error("{{ __('messages.error_occurred') }}");
            }
        });
    }

    function deleteRefund(id) {
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('invoices.confirm_delete_refund') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            closeOnConfirm: false,
            cancelButtonText: "{{ __('common.cancel') }}"
        }, function () {
            $.ajax({
                url: "{{ url('refunds') }}/" + id,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function (response) {
                    if (response.status) {
                        swal("{{ __('common.alert') }}", response.message, "success");
                        dataTable.ajax.reload();
                    } else {
                        swal("{{ __('common.alert') }}", response.message, "error");
                    }
                }
            });
        });
    }
</script>
@endsection
