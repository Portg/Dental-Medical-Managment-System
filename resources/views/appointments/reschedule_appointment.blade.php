<div class="modal fade" id="reschedule-appointment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">{{ __('appointments.reschedule_appointment') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="reschedule-appointment-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="reschedule_appointment_id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('appointments.patient') }}</label>
                        <input type="text" class="form-control" name="patient" id="reschedule_patient" readonly/>
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('appointments.appointment_date') }}</label>
                        <input class="form-control" placeholder="{{ __('datepickers.format_date') }}" type="text"
                               id="datepicker2"
                               name="appointment_date">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('appointments.appointment_time') }}</label>
                        <input class="form-control" id="start_time" data-format="hh:mm A"
                               placeholder="{{ __('datepickers.format_time') }}"
                               type="text" name="appointment_time">
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="BtnSave" onclick="save_scheduler()">{{ __('common.save_changes') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


