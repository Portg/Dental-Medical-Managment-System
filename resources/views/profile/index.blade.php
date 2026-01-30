@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('css')
    @include('layouts.page_loader')
@endsection
@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="portlet light profile-sidebar-portlet bordered">
            <div class="profile-userpic">
                @if(\Illuminate\Support\Facades\Auth::User()->photo !=null)
                    <img src="{{ asset('uploads/users/'.\Illuminate\Support\Facades\Auth::User()->photo) }}"
                         class="img-responsive" style="height: 200px !important; width: 200px !important; margin: 0 auto; border-radius: 50%;"
                         alt="">
                @else
                    <img src="{{ asset('backend/assets/pages/media/profile/profile_user.jpg') }}"
                         class="img-responsive" style="margin: 0 auto; border-radius: 50%;"
                         alt="">
                @endif
            </div>
            <div class="profile-usertitle">
                <div class="profile-usertitle-name"> {{ $user->surname." ".$user->othername }} </div>
                <div class="profile-usertitle-job"> {{ __('profile.profile') }}</div>
            </div>

            <div class="profile-usermenu">
                <ul class="nav">
                    <li class="active">
                        <a href="#"> {{ __('profile.account_settings') }} </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="portlet light bordered">
            <div class="portlet-title tabbable-line">
                <div class="caption caption-md">
                    <i class="icon-globe theme-font hide"></i>
                    <span class="caption-subject font-blue-madison bold uppercase">{{ __('profile.profile') }}</span>
                </div>
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#tab_1_1" data-toggle="tab">{{ __('profile.personal_information') }}</a>
                    </li>
                    <li>
                        <a href="#tab_1_2" data-toggle="tab">{{ __('profile.change_picture') }}</a>
                    </li>
                    <li>
                        <a href="#tab_1_3" data-toggle="tab">{{ __('profile.change_password') }}</a>
                    </li>
                </ul>
            </div>
            <div class="portlet-body">
                <div class="tab-content">
                    <div class="alert alert-danger" style="display:none">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="tab-pane active" id="tab_1_1">
                        <form role="form" action="#" id="bio_data">
                            @csrf
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.first_name') }}</label>
                                <input type="text" placeholder="{{ __('profile.enter_first_name') }}" class="form-control"
                                       name="surname" value="{{ $user->surname }}"/>
                            </div>
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.last_name') }}</label>
                                <input type="text" name="othername" placeholder="{{ __('profile.enter_last_name') }}"
                                       value="{{ $user->othername }}" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.email') }}</label>
                                <input type="text" name="email" placeholder="{{ __('profile.enter_email') }}"
                                       class="form-control" value="{{ $user->email }}"/>
                            </div>
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.phone_number') }}</label>
                                <input type="text" name="phone_number" placeholder="{{ __('profile.enter_phone') }}"
                                       class="form-control" value="{{ $user->phone_no }}"/>
                            </div>
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.phone_number') }} ({{ __('common.alternative') }})</label>
                                <input type="text" name="alternative_no" placeholder="{{ __('profile.enter_phone') }}"
                                       class="form-control" value="{{ $user->alternative_no }}"/>
                            </div>
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.id_number') }}</label>
                                <input type="text" name="national_id" placeholder="{{ __('profile.id_number') }}"
                                       class="form-control" value="{{ $user->nin }}"/>
                            </div>
                            <div class="margin-top-10">
                                <a href="#" onclick="Update_Biodata();" class="btn green"> {{ __('profile.save_changes') }} </a>
                                <a href="javascript:;" class="btn default"> {{ __('profile.cancel') }} </a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane" id="tab_1_2">
                        <p>{{ __('profile.picture_upload_instructions', ['default' => 'If you want to change the current profile photo, please attach a new photo and click submit']) }}</p>
                        <form action="{{ url('update-avatar') }}" method="post" role="form" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <div class="fileinput fileinput-new" data-provides="fileinput">
                                    <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                                        <div class="placeholder-image" style="width: 200px; height: 150px; background: #EFEFEF; display: flex; align-items: center; justify-content: center; color: #AAAAAA;">
                                            {{ __('common.no_image', ['default' => 'no image']) }}
                                        </div>
                                    </div>
                                    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px;"></div>
                                    <div>
                                        <span class="btn default btn-file">
                                            <span class="fileinput-new"> {{ __('common.select_image', ['default' => 'Select image']) }} </span>
                                            <span class="fileinput-exists"> {{ __('common.change', ['default' => 'Change']) }} </span>
                                            <input type="file" name="avatar">
                                        </span>
                                        <a href="javascript:;" class="btn default fileinput-exists" data-dismiss="fileinput"> {{ __('payslips.remove') }} </a>
                                    </div>
                                </div>
                            </div>
                            <div class="margin-top-10">
                                <input type="submit" value="{{ __('profile.upload_picture') }}" class="btn btn-primary">
                                <a href="javascript:;" class="btn default"> {{ __('profile.cancel') }} </a>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane" id="tab_1_3">
                        <div class="alert alert-danger" style="display:none">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <form action="#" id="passwords_form">
                            @csrf
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.current_password') }}</label>
                                <input type="password" name="old_password"
                                       placeholder="{{ __('profile.current_password') }}" class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.new_password') }}</label>
                                <input type="password" name="new_password" placeholder="{{ __('profile.new_password') }}"
                                       class="form-control"/>
                            </div>
                            <div class="form-group">
                                <label class="control-label">{{ __('profile.confirm_password') }}</label>
                                <input type="password" name="confirm_password"
                                       placeholder="{{ __('profile.confirm_password') }}" class="form-control"/>
                            </div>
                            <div class="margin-top-10">
                                <a href="#" onclick="Change_Password();" class="btn green"> {{ __('profile.change_password') }} </a>
                                <a href="javascript:;" class="btn default"> {{ __('profile.cancel') }} </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading', ['default' => 'Loading']) }}</span>
</div>
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        //update the bio data of the user
        function Update_Biodata() {
            if (confirm(LanguageManager.trans('messages.confirm_save_changes', "{{ __('messages.confirm_save_changes') }}"))) {

                $.LoadingOverlay("show");
                $.ajax({
                    type: 'POST',
                    data: $('#bio_data').serialize(),
                    url: "update-bio",
                    success: function (data) {
                        if (data.status) {
                            alert_dialog(data.message, "success");
                        } else {
                            alert_dialog(data.message, "danger");
                        }
                        $.LoadingOverlay("hide");
                    },
                    error: function (request, status, error) {
                        $.LoadingOverlay("hide");
                        json = $.parseJSON(request.responseText);
                        $.each(json.errors, function (key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<p>' + value + '</p>');
                        });
                    }
                });
            }
        }

        //update profile picture
        function Update_Avatar() {
            if (confirm(LanguageManager.trans('messages.confirm_save_changes', "{{ __('messages.confirm_save_changes') }}"))) {

                $.LoadingOverlay("show");
                $.ajax({
                    type: 'POST',
                    data: $('#avatar_form').serialize(),
                    url: "update-avatar",
                    success: function (data) {
                        if (data.status) {
                            alert_dialog(data.message, "success");
                        } else {
                            alert_dialog(data.message, "success");
                        }
                        $.LoadingOverlay("hide");
                    },
                    error: function (request, status, error) {
                        $.LoadingOverlay("hide");
                        json = $.parseJSON(request.responseText);
                        $.each(json.errors, function (key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<p>' + value + '</p>');
                        });
                    }
                });
            }
        }

        //update password
        function Change_Password() {
            if (confirm(LanguageManager.trans('messages.confirm_save_changes', "{{ __('messages.confirm_save_changes') }}"))) {

                $.LoadingOverlay("show");
                $.ajax({
                    type: 'POST',
                    data: $('#passwords_form').serialize(),
                    url: "update-password",
                    success: function (data) {
                        if (data.status) {
                            alert_dialog(data.message, "success");
                        } else {
                            alert_dialog(data.message, "danger");
                        }
                        $.LoadingOverlay("hide");
                    },
                    error: function (request, status, error) {
                        $.LoadingOverlay("hide");
                        json = $.parseJSON(request.responseText);
                        $.each(json.errors, function (key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<p>' + value + '</p>');
                        });
                    }
                });
            }
        }

        //general alert dialog
        function alert_dialog(message, status) {
            toastr[status](message);
            toastr.options = {
                "closeButton": false,
                "debug": false,
                "newestOnTop": false,
                "progressBar": false,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "7000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            }
            setTimeout(function () {
                location.reload();
            }, 1500);
        }
    </script>
@endsection
