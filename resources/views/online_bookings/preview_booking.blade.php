<div class="modal fade modal-form" id="booking-preview-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-full">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('online_bookings.online_booking_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="booking-preview-form" autocomplete="off" readonly="">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('online_bookings.name') }} <span>*</span></label>
                                <input type="text" class="form-control" name="full_name" placeholder="{{ __('online_bookings.full_name') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('online_bookings.phone_number') }} <span>*</span></label>
                                <input type="text" class="form-control" name="phone_number" placeholder="{{ __('online_bookings.phone_number') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('online_bookings.email') }} <span>*</span></label>
                                <input type="text" class="form-control" name="email"
                                       placeholder="{{ __('online_bookings.email_address') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('online_bookings.appointment_date') }} <span>*</span></label>
                                <input type="text" class="form-control" readonly id="datepicker" name="appointment_date"
                                       placeholder="dd/mm/yyyy">
                            </div>
                            <div class="form-group">
                                <label>{{ __('online_bookings.appointment_time') }} <span>*</span></label>
                                <input type="text" class="form-control" id="appointment_time" name="appointment_time"
                                       placeholder="{{ __('online_bookings.appointment_time') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('online_bookings.medical_insurance_provider') }}</label>
                                <select id="company" name="insurance_company_id" class="form-control"
                                        style="width: 100%;"></select>
                            </div>
                        </div>
                        <div class="col-md-6">

                            <div class="form-group"><br>
                                <label>{{ __('online_bookings.have_you_visited') }} {{ env('CompanyName',null) }} <span>*</span></label><br>
                                <input type="radio" name="visit_history" value="1"> {{ __('online_bookings.yes') }}<br>
                                <input type="radio" name="visit_history" value="0"> {{ __('online_bookings.no') }}<br>
                            </div>
                            <div class="form-group">
                                <label>{{ __('online_bookings.reason_for_visit') }} <span>*</span></label>
                                <textarea class="form-control" name="visit_reason" rows="7"></textarea>
                            </div>
                            <div class="form-group doctor_id_field">
                                <label>{{ __('online_bookings.doctor') }}<span class="text-danger"> {{ __('online_bookings.choose_doctor_approve') }}</span></label>
                                <select id="doctor" name="doctor_id" class="form-control" style="width: 100%;"></select>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
            <div class="modal-footer">
                <div class="action_btns">
                    <button type="button" id="acceptBtn" class="btn btn-primary" onclick="AcceptBooking();">{{ __('online_bookings.accept_booking') }}
                    </button>
                    <button type="button" id="rejectBtn" class="btn btn-danger" onclick="RejectBooking();">{{ __('online_bookings.reject_booking') }}
                    </button>
                </div>
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('online_bookings.close') }}</button>
            </div>
        </div>
    </div>
</div>


