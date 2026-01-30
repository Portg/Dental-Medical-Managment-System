<div class="modal fade" id="expense-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('expense_items.expense_item_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="expense-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.expense_category') }} </label>
                        <select id="expense_category" name="expense_category" class="form-control"
                                style="width: 100%;"></select>
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.purchase_date') }} </label>
                        <input type="text" id="datepicker" name="purchase_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.invoice_no') }}</label>
                        <input type="text" name="invoice_no" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.item') }} </label>
                        <input type="text" name="item" placeholder="" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.quantity') }} </label>
                        <input type="text" name="qty" placeholder="" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.amount') }} </label>
                        <input type="text" name="price" placeholder="" class="form-control">
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


