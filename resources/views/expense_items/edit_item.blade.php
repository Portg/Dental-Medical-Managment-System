<div class="modal fade modal-form modal-form-lg" id="expense-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('expense_items.expense_item_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="expense-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="item_id" name="id">
                    <input type="hidden" id="item_expense_id" name="expense_id">

                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.item') }} </label>
                        <input type="text" name="item" id="item" placeholder="" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.quantity') }} </label>
                        <input type="number" id="qty" name="qty" placeholder="" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.unit_price') }}</label>
                        <input type="number" id="price-single-unit" name="price" placeholder="" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.total_amount') }}</label>
                        <input type="text" id="total_amount" readonly placeholder="" class="form-control">
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_item()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>


