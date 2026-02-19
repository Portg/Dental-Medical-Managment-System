<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta http-equiv="content-type" content="text/html;charset=UTF-8"/>
<head>
    <meta charset="utf-8"/>
    <title>{{ config('app.name','laravel') }}</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <meta content="Thrust Dental Systems " name="description"/>
    <meta content="" name="author"/>
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=all" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/simple-line-icons/simple-line-icons.min.css') }}"
          rel="stylesheet" type="text/css"/>

    <link href="{{ asset('backend/assets/global/plugins/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/css/components.min.css')}}" rel="stylesheet" id="style_components"
          type="text/css"/>
    <link href="{{ asset('backend/assets/pages/css/login.min.css')}}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/bootstrap-sweetalert/sweetalert.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/css/bootstrap-datepicker.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/bootstrap-timepicker/css/bootstrap-timepicker.min.css')}}"
          rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="{{ asset('backend/assets/global/css/intlTelInput.css') }}" media="screen">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}"/>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}"/>
    <style>
        .login .content .form-title {
            color: #fff !important;
        }

        label {
            font-weight: 600;
            color: #e0e0e0;
            font-size: 18px;
            font-family: "Open Sans", sans-serif;
        }

        .radio_ {
            color: #fff;
            /*font-size: 18px;*/
            /*font-family: Roboto;*/
        }

        input {
            color: #000 !important;
        }

        textarea {
            color: #000 !important;
        }

        select {
            color: #000 !important;
        }

        .login {
            background-color: #000 !important;
            background-image: url({{asset('images/booking_bg.png')}});
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
        }

        .login .content {
            opacity: 0.9;
            background-color: #696666;
            -webkit-border-radius: 7px;
            -moz-border-radius: 7px;
            -ms-border-radius: 7px;
            -o-border-radius: 7px;
            border-radius: 7px;
            width: 920px;
            margin: 40px auto 10px;
            padding: 10px 30px 30px;
            overflow: hidden;
            position: relative;
        }

        .login .content .form-control {
            color: #676767;
        }

        textarea {
            min-height: 100px;
        }
    </style>
</head>

<body class=" login">

<div class="content">

    <form class="appointment-form" action="#">
        @csrf
        <h3 class="form-title font-green">{{ __('frontend.appointment_booking_form') }}</h3>
        <div class="alert alert-danger" style="display: none;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <div class="form-group" style="display: none;">
            <label for="faxonly">{{ __('frontend.fax_only') }}
                <input type="checkbox" name="contact_me_by_fax_only" value="1" style="display:none !important"
                       tabindex="-1" autocomplete="off">
            </label>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ __('frontend.full_name') }} <span>*</span></label>
                    <input type="text" class="form-control" name="full_name" placeholder="{{ __('frontend.enter_full_name') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('frontend.phone_number') }} <span>*</span></label><br>
                    <input type="text" id="telephone" name="telephone" class="form-control" style="width: 370px">
                    <input type="hidden" id="phone_number" name="phone_number" class="form-control">
                </div>
                <div class="form-group">
                    <label>{{ __('frontend.preferred_appointment_date') }} <span>*</span></label>
                    <input type="text" class="form-control" readonly id="datepicker" name="appointment_date"
                           placeholder="{{ __('datetime.format_date') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('frontend.preferred_appointment_time') }} <span>*</span></label>
                    <input type="text" class="form-control" id="appointment_time" name="appointment_time"
                           placeholder="{{ __('frontend.visit_time') }}">
                </div>

            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ __('frontend.email') }} <span>*</span></label>
                    <input type="text" class="form-control" name="email"
                           placeholder="{{ __('frontend.email_address') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('frontend.have_you_ever_visited') }} {{ env('CompanyName',null)}} <span>*</span></label><br>
                    <input type="radio" name="visit_history" value="1"> <span class="radio_">{{ __('frontend.yes') }}</span><br>
                    <input type="radio" name="visit_history" value="0"> <span class="radio_">{{ __('frontend.no') }}</span><br>
                </div>
                <div class="form-group">
                    <label>{{ __('frontend.do_you_have_medical_insurance') }} <span class="text-danger">({{ __('frontend.optional_field') }})</span></label>
                    <select class="form-control" name="insurance_provider">
                        <option value="">{{ __('frontend.select_your_insurance_provider') }}</option>
                        @foreach($insurance_providers as $provider)
                            <option value="{{ $provider->id }}">{{$provider->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('frontend.reason_for_visit') }} <span>*</span></label>
                    <textarea class="form-control" name="visit_reason" rows="14"></textarea>
                </div>
                <button type="button" class="btn btn-primary" id="BookBtn" onclick="sendBooking()">{{ __('frontend.book_appointment') }}
                </button>
            </div>
            <br>

        </div>
    </form>
</div>
<script src="{{ asset('backend/assets/global/plugins/jquery.min.js')}}" type="text/javascript"></script>
<script src="{{ asset('backend/assets/global/plugins/bootstrap/js/bootstrap.min.js')}}" type="text/javascript"></script>
<script src="{{ asset('backend/assets/global/plugins/bootstrap-datepicker.js') }}" type="text/javascript"></script>
<script src="{{ asset('backend/assets/global/plugins/bootstrap-timepicker/js/bootstrap-timepicker.min.js') }}"
        type="text/javascript"></script>
<script src="{{ asset('backend/assets/global/plugins/bootstrap-sweetalert/sweetalert.min.js') }}"
        type="text/javascript"></script>
<script src="{{ asset('backend/assets/global/scripts/loadingoverlay.js') }}"></script>
<script src="{{ asset('backend/assets/global/scripts/loadingoverlay.min.js') }}"></script>
<script src="{{ asset('backend/assets/global/scripts/intlTelInput.min.js') }}"></script>
<script>
    let input = document.querySelector("#telephone");
    window.intlTelInput(input, {
        preferredCountries: ["ug", "us"],
        autoPlaceholder: "off",
        utilsScript: "{{ asset('backend/assets/global/scripts/utils.js') }}",
    });
    var iti = window.intlTelInputGlobals.getInstance(input);

    function sendBooking() {
        $.LoadingOverlay("show");
        $('#BookBtn').attr('disabled', true);
        $('#BookBtn').text('{{ __("common.processing") }}');
        //update the country code phone number
        let number = iti.getNumber();
        $('#phone_number').val(number);
        $.ajax({
            type: 'POST',
            data: $('.appointment-form').serialize(),
            url: "/request-appointment",
            success: function (data) {
                $.LoadingOverlay("hide");
                $(".appointment-form")[0].reset();
                $('#BookBtn').attr('disabled', false);
                $('#BookBtn').text('{{ __("frontend.book_appointment") }}');

                swal("{{ __('common.alert') }}", data.message, "success");
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
                $('#BookBtn').attr('disabled', false);
                $('#BookBtn').text('{{ __("frontend.book_appointment") }}');
                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });

                $(".alert-danger").fadeTo(2000, 500).slideUp(500, function () {
                    $(".alert-danger").slideUp(500);
                });

            }
        });
    }

    $(document).ready(function () {
        $.LoadingOverlay("show"); // Show full page LoadingOverlay

        $(window).load(function (e) {
            $.LoadingOverlay("hide"); // after page loading hide the progress bar
        });

    });
    $('#datepicker').datepicker({
        language: '{{ app()->getLocale() }}',
        autoclose: true,
        todayHighlight: true
    });
    $('#appointment_time').timepicker();
</script>


</body>
</html>
