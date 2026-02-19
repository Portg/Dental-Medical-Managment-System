<div class="modal fade modal-form" id="chart_of_accounts-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('charts_of_accounts.chart_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="chart_of_accounts-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('charts_of_accounts.account_type') }} </label>
                        <select id="select2-single-input-group-sm"
                                class="form-control select2 account_type" name="account_type">
                            <option value="null">{{ __('charts_of_accounts.choose_one') }}</option>
                            @foreach($AccountingEquations as $row)
                                <optgroup label="{{$row->name}}">
                                    @foreach($row->Categories as $cat)
                                        <option value="{{$cat->id}}">{{$cat->name}}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('charts_of_accounts.account_name') }} </label>
                        <input type="text" name="name" class="form-control" placeholder="{{ __('charts_of_accounts.enter_account_name') }}">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('charts_of_accounts.description') }}</label>
                        <textarea class="form-control" name="description" rows="7"></textarea>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>


