@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection

<div class="page-content-wrapper">
    <div class="page-content">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-wallet font-dark"></i>
                    <span class="caption-subject font-dark bold uppercase">{{ __('invoices.refunds') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('refunds/create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> {{ __('invoices.new_refund') }}
                    </a>
                    <a href="{{ url('refunds/pending-approvals') }}" class="btn btn-warning">
                        <i class="fa fa-clock-o"></i> {{ __('invoices.pending_approvals') }}
                        <span class="badge badge-danger" id="pending-count"></span>
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
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
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" onclick="filterTable()">
                                <i class="fa fa-search"></i> {{ __('common.filter') }}
                            </button>
                        </div>
                    </div>
                </div>

                <table class="table table-striped table-bordered table-hover" id="refunds_table">
                    <thead>
                        <tr>
                            <th>{{ __('common.hash') }}</th>
                            <th>{{ __('invoices.refund_no') }}</th>
                            <th>{{ __('invoices.invoice_no') }}</th>
                            <th>{{ __('patient.name') }}</th>
                            <th>{{ __('invoices.refund_amount') }}</th>
                            <th>{{ __('invoices.refund_date') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}"></script>
<script>
var table;
$(document).ready(function() {
    table = $('#refunds_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ url('refunds') }}",
            data: function(d) {
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
        order: [[1, 'desc']],
        language: {
            url: "{{ asset('backend/assets/global/plugins/datatables/lang/' . app()->getLocale() . '.json') }}"
        }
    });

    // Load pending count
    loadPendingCount();

    // Initialize datepickers
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});

function filterTable() {
    table.ajax.reload();
}

function loadPendingCount() {
    $.get("{{ url('refunds/pending-count') }}", function(data) {
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
        success: function(response) {
            $('#approveModal').modal('hide');
            if (response.status) {
                toastr.success(response.message);
                table.ajax.reload();
                loadPendingCount();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
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
        success: function(response) {
            $('#rejectModal').modal('hide');
            if (response.status) {
                toastr.success(response.message);
                table.ajax.reload();
                loadPendingCount();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error("{{ __('messages.error_occurred') }}");
        }
    });
}

function deleteRefund(id) {
    if (!confirm("{{ __('invoices.confirm_delete_refund') }}")) return;
    $.ajax({
        url: "{{ url('refunds') }}/" + id,
        type: 'DELETE',
        data: {_token: '{{ csrf_token() }}'},
        success: function(response) {
            if (response.status) {
                toastr.success(response.message);
                table.ajax.reload();
            } else {
                toastr.error(response.message);
            }
        }
    });
}
</script>
@endsection
