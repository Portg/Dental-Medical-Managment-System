<div class="modal fade" id="rate-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('claim_rates.claim_rates_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="rate-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('claim_rates.doctor') }} </label>
                        <select id="doctor" name="doctor_id" class="form-control"
                                style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('claim_rates.cash_rate') }}</label>
                        <input type="number" name="cash_rate" placeholder="" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('claim_rates.insurance_rate') }}</label>
                        <input type="number" name="insurance_rate" placeholder="" class="form-control">
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


