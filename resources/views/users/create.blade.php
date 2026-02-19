<div class="modal fade modal-form" id="users-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('users.system_user') }} </h4>
            </div>
            <div class="modal-body">

                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="users-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            @if(app()->getLocale() === 'zh-CN')
                                <div class="form-group">
                                    <label class="text-primary">{{ __('users.full_name') }} </label>
                                    <input type="text" name="full_name" placeholder="{{ __('users.enter_full_name') }}" class="form-control">
                                </div>
                            @else
                                <div class="form-group">
                                    <label class="text-primary">{{ __('users.surname') }} </label>
                                    <input type="text" name="surname" placeholder="{{ __('users.enter_surname') }}" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="text-primary">{{ __('users.other_name') }} </label>
                                    <input type="text" name="othername" placeholder="{{ __('users.enter_other_name') }}" class="form-control">
                                </div>
                            @endif
                            <div class="form-group">
                                <label class="text-primary">{{ __('users.email') }} </label>
                                <input type="text" name="email" placeholder="{{ __('users.enter_email') }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('users.phone_no') }} </label>
                                <input type="text" name="phone_no" placeholder="{{ __('users.enter_phone') }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('users.alternative_phone_no') }}: <span
                                            class="text-danger">({{ __('common.optional') }})</span></label>
                                <input type="text" name="alternative_no" placeholder="{{ __('users.enter_alternative_no') }}"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('users.national_id_no') }} <span class="text-danger">({{ __('common.optional') }})</span>
                                </label>
                                <input type="text" name="nin" placeholder="{{ __('users.enter_id_no') }}" class="form-control">
                            </div>
                            <div class="password_config">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('users.password_preferred') }} </label>
                                    <input type="text" name="password" placeholder="" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="text-primary">{{ __('users.confirm_password') }} </label>
                                    <input type="text" name="password_confirmation" placeholder="" class="form-control">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="text-primary">{{ __('users.system_user_role') }} ({{ __('users.required') }}) </label>
                                <select id="role" name="role_id" class="form-control" style="width: 100%;"></select>
                            </div>
                            <div class="form-group" id="branch_block">
                                <label class="text-primary">{{ __('users.branch') }} </label>
                                <select id="branch_id" name="branch_id" class="form-control"
                                        style="width: 100%;"></select>
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('users.is_doctor') }} </label><span class="text-danger">({{ __('users.please_specify_if_doctor') }})</span><br>
                                <input type="radio" name="is_doctor" value="1">{{ __('common.yes') }} &nbsp; &nbsp;
                                <input type="radio" name="is_doctor" value="0">{{ __('common.no') }}

                            </div>
                        </div>
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


