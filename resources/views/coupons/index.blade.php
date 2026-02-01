@extends('layouts.list-page')

@section('page_title', __('invoices.coupons'))

@section('table_id', 'coupons-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('invoices.new_coupon') }}
    </button>
@endsection

@section('table_headers')
    <th>#</th>
    <th>{{ __('invoices.coupon_code') }}</th>
    <th>{{ __('invoices.coupon_name') }}</th>
    <th>{{ __('invoices.coupon_type') }}</th>
    <th>{{ __('invoices.coupon_usage') }}</th>
    <th>{{ __('invoices.coupon_validity') }}</th>
    <th>{{ __('invoices.coupon_status') }}</th>
    <th>{{ __('common.actions') }}</th>
@endsection

@section('modals')
<!-- Coupon Modal -->
<div class="modal fade" id="couponModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modalTitle">{{ __('invoices.new_coupon') }}</h4>
            </div>
            <form id="couponForm">
                @csrf
                <input type="hidden" id="coupon_id" name="coupon_id">
                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none;">
                        <ul></ul>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control" style="text-transform: uppercase;" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_type') }} <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-control" required>
                                    <option value="fixed">{{ __('invoices.coupon_fixed') }}</option>
                                    <option value="percent">{{ __('invoices.coupon_percent') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_value') }} <span class="text-danger">*</span></label>
                                <input type="number" name="value" id="value" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_min_order') }}</label>
                                <input type="number" name="min_order_amount" id="min_order_amount" class="form-control" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_max_discount') }}</label>
                                <input type="number" name="max_discount" id="max_discount" class="form-control" step="0.01" min="0">
                                <small class="text-muted">{{ __('invoices.coupon_percent') }}{{ __('common.only') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_max_uses') }}</label>
                                <input type="number" name="max_uses" id="max_uses" class="form-control" min="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_max_per_user') }}</label>
                                <input type="number" name="max_uses_per_user" id="max_uses_per_user" class="form-control" min="1" value="1">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_start_date') }}</label>
                                <input type="date" name="starts_at" id="starts_at" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_end_date') }}</label>
                                <input type="date" name="expires_at" id="expires_at" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{ __('invoices.coupon_description') }}</label>
                                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                                    {{ __('common.active') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page_js')
<script type="text/javascript">
    var isEdit = false;

    $(function () {
        LanguageManager.loadAllFromPHP({
            'invoices': @json(__('invoices')),
            'common': @json(__('common'))
        });

        dataTable = $('#coupons-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: "{{ url('coupons') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'code', name: 'code'},
                {data: 'name', name: 'name'},
                {data: 'type_display', name: 'type_display', orderable: false, searchable: false},
                {data: 'usage_display', name: 'usage_display', orderable: false, searchable: false},
                {data: 'validity_display', name: 'validity_display', orderable: false, searchable: false},
                {data: 'status_display', name: 'status_display', orderable: false, searchable: false},
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();

        $('#couponForm').on('submit', function (e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var url = isEdit ? "{{ url('coupons') }}/" + $('#coupon_id').val() : "{{ url('coupons') }}";
            var method = isEdit ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: method,
                data: formData,
                success: function (response) {
                    if (response.status) {
                        toastr.success(response.message);
                        $('#couponModal').modal('hide');
                        dataTable.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function (xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorList = '';
                    $.each(errors, function (key, value) {
                        errorList += '<li>' + value[0] + '</li>';
                    });
                    $('.alert-danger ul').html(errorList);
                    $('.alert-danger').show();
                }
            });
        });
    });

    function createRecord() {
        isEdit = false;
        $('#modalTitle').text(LanguageManager.trans('invoices.new_coupon'));
        $('#couponForm')[0].reset();
        $('#coupon_id').val('');
        $('#is_active').prop('checked', true);
        $('.alert-danger').hide();
        $('#couponModal').modal('show');
    }

    function editCoupon(id) {
        isEdit = true;
        $('#modalTitle').text(LanguageManager.trans('invoices.edit_coupon'));
        $('.alert-danger').hide();

        $.get("{{ url('coupons') }}/" + id + "/edit", function (data) {
            $('#coupon_id').val(data.id);
            $('#code').val(data.code);
            $('#name').val(data.name);
            $('#type').val(data.type);
            $('#value').val(data.value);
            $('#min_order_amount').val(data.min_order_amount);
            $('#max_discount').val(data.max_discount);
            $('#max_uses').val(data.max_uses);
            $('#max_uses_per_user').val(data.max_uses_per_user);
            $('#starts_at').val(data.starts_at ? data.starts_at.substring(0, 10) : '');
            $('#expires_at').val(data.expires_at ? data.expires_at.substring(0, 10) : '');
            $('#description').val(data.description);
            $('#is_active').prop('checked', data.is_active == 1);
            $('#couponModal').modal('show');
        });
    }

    function deleteCoupon(id) {
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('invoices.confirm_delete_coupon') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: "{{ url('coupons') }}/" + id,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function (response) {
                    if (response.status) {
                        swal("{{ __('common.deleted') }}", response.message, "success");
                        dataTable.ajax.reload();
                    } else {
                        swal("{{ __('common.error') }}", response.message, "error");
                    }
                }
            });
        });
    }
</script>
@endsection
