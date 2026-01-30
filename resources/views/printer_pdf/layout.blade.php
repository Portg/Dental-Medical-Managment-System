<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <title>{{ config('app.name', 'Laravel') }}</title>

    <style type="text/css">
        .container {

        }

        .logo {
            width: 140px;
            height: 89px;
            margin-top: 10px;
            padding: 0px;
        }

        .header_text {
            font-size: 15px;
            font-family: sans-serif !important;
        }

        .footer_text {
            position: relative;
        }

    </style>
</head>
<body>
<div class="container" style="position: relative !important;top: -20px !important;">
    <header style="position: relative !important;top: -2px !important;">

        <h2 style="position: relative !important;top: -2px !important;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo"/>
        </h2>
        <br><br>
        <table width="100%">
            <tr>
                <td align="left" style="width: 40%;">
                    <p class="header_text">{{env("CompanyAddress",null)}}<br>
                        {{ __('print.tel') }}: {{env("companyMobile",null)}}; {{env("companyMobileOther",null)}} <br>
                        {{ __('print.email') }}:{{env("companyInfoEmail",null)}}
                        {{ __('print.tin_no') }}: {{ env("companyTinNo",null)}}
                    </p>

                </td>
                <td align="center">

                </td>
                <td align="right" style="width: 40%;">
                    <p class="header_text">
                       {{ env("MainDoctorName",null)}}<br>
                        {{ __('print.tel') }} {{env("mainDoctorContacts",null)}}
                        {{ __('print.email') }}:{{ env("companyOfficalEmail",null)}}

                    </p>
                </td>
            </tr>
        </table>
        <hr style="border: 1px solid #b0b0b0">
    </header>
    @yield('content')


</div>
<footer class="footer">
    <div class="footer_text">
{{--        <br><br><br>--}}
        <center style="font-size: 13px;font-weight: bold;"><span>{{ __('print.company_slogan') }}</span><br>
            {{ __('print.payment_info_message') }}<br>
            {{ __('print.thank_you_business') }}
        </center>
        <hr>
    </div>
</footer>
</body>
</html>
