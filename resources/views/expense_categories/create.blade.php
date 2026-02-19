<div class="modal fade modal-form" id="category-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('expense_categories.expense_item_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="category-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">

                    @include('components.form.text-field', [
                        'name' => 'name',
                        'label' => __('expense_categories.expense_item'),
                        'placeholder' => __('expense_categories.enter_name_here'),
                    ])

                    @include('components.form.select-field', [
                        'name' => 'expense_account',
                        'label' => __('expense_categories.expense_account'),
                        'placeholder' => __('expense_categories.please_choose_account'),
                        'options' => $expense_accounts->map(fn($a) => ['value' => $a->id, 'text' => $a->name])->toArray(),
                    ])
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
