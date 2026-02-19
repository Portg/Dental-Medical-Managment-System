<div class="modal fade modal-form" id="company-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('insurance_companies.insurance_company_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="company-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('insurance_companies.company_name') }}</label>
                        <input type="text" name="name" placeholder="{{ __('insurance_companies.enter_company_name') }}" class="form-control">
                    </div>
                    <div class="form-group hidden">
                        <label class="text-primary">{{ __('insurance_companies.email') }} </label>
                        <input type="text" name="email" placeholder="{{ __('insurance_companies.enter_email') }}" class="form-control">
                    </div>
                    <div class="form-group hidden">
                        <label class="text-primary">{{ __('insurance_companies.phone_no') }} </label>
                        <input type="text" name="phone_no" placeholder="" class="form-control">
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


