<div class="modal fade modal-form" id="service-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('clinical_services.procedure_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="service-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('clinical_services.procedure') }}</label>
                        <input type="text" name="name" placeholder="{{ __('clinical_services.enter_procedure') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('clinical_services.price') }}</label>
                        <input type="number" name="price" placeholder="{{ __('clinical_services.enter_price') }}" class="form-control">
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


