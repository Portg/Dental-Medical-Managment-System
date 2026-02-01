@extends('layouts.list-page')

@section('page_title', __('invoices.pending_approvals'))

@section('table_id', 'pending-refunds-table')

@section('header_actions')
    <a href="{{ url('refunds') }}" class="btn btn-default">
        {{ __('invoices.refunds') }}
    </a>
@endsection

@section('table_headers')
    <th>#</th>
    <th>{{ __('invoices.refund_no') }}</th>
    <th>{{ __('patient.name') }}</th>
    <th>{{ __('invoices.refund_amount') }}</th>
    <th>{{ __('invoices.refund_reason') }}</th>
    <th>{{ __('invoices.requested_by') }}</th>
    <th>{{ __('invoices.requested_at') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
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
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
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

        dataTable = $('#pending-refunds-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: "{{ url('refunds/pending-approvals') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'refund_no', name: 'refund_no'},
                {data: 'patient_name', name: 'patient_name'},
                {data: 'refund_amount', name: 'refund_amount'},
                {data: 'refund_reason', name: 'refund_reason'},
                {data: 'requested_by', name: 'requested_by'},
                {data: 'requested_at', name: 'requested_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[6, 'asc']]
        });

        setupEmptyStateHandler();
    });

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
                } else {
                    toastr.error(response.message);
                }
            },
            error: function () {
                toastr.error("{{ __('messages.error_occurred') }}");
            }
        });
    }
</script>
@endsection
