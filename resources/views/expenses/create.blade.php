<div class="modal fade" id="purchase-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-full">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('expenses.purchases_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="purchase-form" autocomplete="off">

                    @csrf
                    <input type="hidden" name="id" id="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('expenses.purchase_date') }} </label>
                        <input type="text" name="purchase_date" id="datepicker" placeholder="{{ __('datetime.format_date') }}
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expenses.supplier_name') }}</label>
                        <input type="text" name="supplier" id="supplier" placeholder="{{ __('expenses.enter_supplier_name') }}"
                               class="form-control">
                    </div>
                    <table class="table table-bordered" id="purchasesTable">
                        <tr>
                            <th class="text-primary">{{ __('expenses.item') }}</th>
                            <th class="text-primary">{{ __('expenses.description') }}</th>
                            <th class="text-primary">{{ __('expenses.expense_category') }}</th>
                            <th class="text-primary">{{ __('expenses.quantity') }}</th>
                            <th class="text-primary">{{ __('expenses.unit_price') }}</th>
                            <th class="text-primary">{{ __('expenses.total_amount') }}</th>
                            <th class="text-primary">{{ __('common.action') }}</th>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" id="item" class="form-control" name="addmore[0][item]"
                                       placeholder="{{ __('expenses.item') }}"/>
                            </td>
                             <td>
                                <input type="text" id="description" class="form-control" name="addmore[0][description]"
                                       placeholder="{{ __('expenses.enter_description') }}"/>
                            </td>
                            <td>
                                <select id="select2-single-input-group-sm"
                                        class="form-control select2 expense_categories" name="addmore[0][expense_category]">
                                    <option value="null">{{ __('expenses.choose_expense_category') }}</option>
                                    @foreach($chart_of_accts as $cat)
                                        <option value="{{$cat->id}}">{{$cat->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" id="qty" class="form-control" name="addmore[0][qty]"
                                       placeholder="{{ __('expenses.enter_quantity') }}"/>
                            </td>
                            <td>
                                <input type="number" id="price-single-unit" class="form-control"
                                       name="addmore[0][price]"
                                       placeholder="{{ __('expenses.enter_unit_price') }}"/>
                            </td>
                            <td>
                                <input type="text" id="total_amount" class="form-control"
                                       placeholder="{{ __('expenses.enter_total_amount') }}" readonly/>
                            </td>
                            <td>
                                <button type="button" name="add" id="add" class="btn btn-info">{{ __('common.add') }}</button>
                            </td>
                        </tr>

                    </table>
                    <br><br>

                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-success" id="btn-save" onclick="save_purchase()">{{ __('expenses.save_purchase') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


