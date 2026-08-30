{{-- 挂号弹窗。

     参考视频的前台动线：新增患者 → **挂号** → 写病历。挂号只问两件事
     ——「找哪位医生」「初诊还是复诊」——因为患者已经站在台前了；
     让前台先去预约页选日期、选时间段，是在问一个已经没有意义的问题。

     被 today_work/index 与 patients/index 两个页面 @include，脚本在
     public/include_js/registration_modal.js，两处共用一份。 --}}
<div class="modal fade modal-form" id="registration-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('today_work.register_title') }}</h4>
            </div>
            <div class="modal-body">
                <form action="#" id="registration-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <div class="form-body">
                        <div class="form-group">
                            <label class="control-label col-md-3 text-primary">
                                {{ __('today_work.register_patient') }} <span class="text-danger">*</span>
                            </label>
                            <div class="col-md-9">
                                <select id="reg_patient_id" name="patient_id" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3 text-primary">
                                {{ __('today_work.register_doctor') }} <span class="text-danger">*</span>
                            </label>
                            <div class="col-md-9">
                                <select id="reg_doctor_id" name="doctor_id" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3 text-primary">{{ __('today_work.register_visit_type') }}</label>
                            <div class="col-md-9">
                                <label class="mt-radio" style="margin-right:18px;">
                                    <input type="radio" name="appointment_type" value="first_visit" checked>
                                    {{ __('today_work.first_visit') }}
                                </label>
                                <label class="mt-radio">
                                    <input type="radio" name="appointment_type" value="revisit">
                                    {{ __('today_work.revisit') }}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3 text-primary">{{ __('today_work.register_service') }}</label>
                            <div class="col-md-9">
                                <select id="reg_service_id" name="service_id" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3 text-primary">{{ __('today_work.register_note') }}</label>
                            <div class="col-md-9">
                                <input type="text" id="reg_notes" name="notes" class="form-control" maxlength="255"
                                       placeholder="{{ __('today_work.register_note_hint') }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-success" id="btn-register-submit" onclick="submitRegistration()">
                    <i class="fa fa-sign-in"></i> {{ __('today_work.register_submit') }}
                </button>
            </div>
        </div>
    </div>
</div>
