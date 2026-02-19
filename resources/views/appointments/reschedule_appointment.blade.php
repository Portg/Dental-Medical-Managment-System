{{-- Reschedule Appointment Modal - Design spec: 400px width, required asterisks --}}
<style>
    #reschedule-appointment-modal .modal-dialog {
        width: 400px;
        max-width: 95%;
    }
    #reschedule-appointment-modal .required-asterisk {
        color: #E74C3C;
        margin-right: 4px;
    }
    #reschedule-appointment-modal .form-group-spacing {
        margin-bottom: 15px;
    }
</style>

<div class="modal fade modal-form" id="reschedule-appointment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('appointment.reschedule_appointment') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="reschedule-appointment-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="reschedule_appointment_id" name="id">

                    <div class="form-group form-group-spacing">
                        <label>{{ __('appointment.patient') }}</label>
                        <input type="text" class="form-control" name="patient" id="reschedule_patient" readonly/>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group form-group-spacing">
                                <label><span class="required-asterisk">*</span>{{ __('appointment.appointment_date') }}</label>
                                <input class="form-control" placeholder="{{ __('datetime.format_date') }}" type="text"
                                       id="datepicker2" name="appointment_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group form-group-spacing">
                                <label><span class="required-asterisk">*</span>{{ __('appointment.appointment_time') }}</label>
                                <input class="form-control" id="start_time" data-format="hh:mm A"
                                       placeholder="{{ __('datetime.format_time') }}"
                                       type="text" name="appointment_time">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                {{-- Design spec: Cancel button on left, Primary action on right --}}
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="BtnSave" onclick="save_scheduler()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
