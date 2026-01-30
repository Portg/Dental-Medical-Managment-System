@extends('layouts.app')
@section('head')
    <title>{{ __('invoices.pending_discount_approvals') }} | {{ config('app.name') }}</title>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption font-dark">
                        <i class="icon-check font-dark"></i>
                        <span class="caption-subject bold uppercase">{{ __('invoices.pending_discount_approvals') }}</span>
                    </div>
                </div>
                <div class="portlet-body">
                    <table class="table table-striped table-bordered table-hover" id="pending-discounts-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('invoices.invoice_no') }}</th>
                                <th>{{ __('invoices.patient_name') }}</th>
                                <th>{{ __('invoices.sub_total') }}</th>
                                <th>{{ __('invoices.discount_amount') }}</th>
                                <th>{{ __('invoices.total_amount') }}</th>
                                <th>{{ __('invoices.added_by') }}</th>
                                <th>{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
                    <h4 class="modal-title">{{ __('invoices.reject_discount') }}</h4>
                </div>
                <form id="rejectForm">
                    @csrf
                    <input type="hidden" id="reject_invoice_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ __('invoices.rejection_reason') }} <span class="text-danger">*</span></label>
                            <textarea name="reason" id="rejection_reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('invoices.reject') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    var table;

    $(document).ready(function() {
        table = $('#pending-discounts-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ url('invoices/pending-discount-approvals') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'patient_name', name: 'patient_name', orderable: false, searchable: false },
                { data: 'subtotal', name: 'subtotal' },
                { data: 'discount_amount', name: 'discount_amount' },
                { data: 'total_amount', name: 'total_amount' },
                { data: 'added_by', name: 'added_by', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: LanguageManager.getDataTableLang()
        });

        $('#rejectForm').on('submit', function(e) {
            e.preventDefault();
            var invoiceId = $('#reject_invoice_id').val();
            var reason = $('#rejection_reason').val();

            $.ajax({
                url: "{{ url('invoices') }}/" + invoiceId + "/reject-discount",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reason: reason
                },
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message);
                        $('#rejectModal').modal('hide');
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON.message || '{{ __('messages.error_occurred') }}');
                }
            });
        });
    });

    function approveDiscount(invoiceId) {
        swal({
            title: "{{ __('invoices.approve_discount') }}",
            text: "{{ __('common.are_you_sure') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-success",
            confirmButtonText: "{{ __('invoices.approve') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            closeOnConfirm: false
        }, function() {
            $.ajax({
                url: "{{ url('invoices') }}/" + invoiceId + "/approve-discount",
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.status) {
                        swal("{{ __('common.success') }}", response.message, "success");
                        table.ajax.reload();
                    } else {
                        swal("{{ __('common.error') }}", response.message, "error");
                    }
                },
                error: function(xhr) {
                    swal("{{ __('common.error') }}", xhr.responseJSON.message || '{{ __('messages.error_occurred') }}', "error");
                }
            });
        });
    }

    function rejectDiscount(invoiceId) {
        $('#reject_invoice_id').val(invoiceId);
        $('#rejection_reason').val('');
        $('#rejectModal').modal('show');
    }
</script>
@endsection
