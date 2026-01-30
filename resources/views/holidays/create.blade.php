<div class="modal fade" id="holidays-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('holidays.holidays_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="holidays-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('holidays.holiday') }} </label>
                        <input type="text" name="name" placeholder="{{ __('holidays.enter_holiday_name') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('holidays.date_of_the_year') }} </label>
                        <input type="text" name="holiday_date" placeholder="{{ __('holidays.enter_date_of_the_year') }}" class="form-control"
                               id="datepicker">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('holidays.is_this_the_same_date_every_year') }}</label><br>
                        <input type="radio" name="repeat_date" value="Yes"> {{ __('common.yes') }}
                        <input type="radio" name="repeat_date" value="No"> {{ __('common.no') }}
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


