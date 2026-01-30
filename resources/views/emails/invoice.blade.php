<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('emails.invoice_subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
        <h2 style="color: #007bff; text-align: center;">{{ __('emails.invoice_subject') }}</h2>

        <p>{{ __('emails.dear_patient', ['surname' => $user_info['surname'], 'othername' => $user_info['othername']]) }}</p>

        <p>{{ __('emails.thank_you_message', [
                'company_name' => env('CompanyName', 'Dental Clinic'),
                    'document_type' => __('emails.invoice_attached')
                    ]) }}</p>

        <p>{{ $user_info['message'] }}</p>

        <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">

        <p style="text-align: center;">
            <strong>{{ __('emails.sincerely') }}</strong><br>
            {{ __('emails.company_team', ['company_name' => env('CompanyName', 'Dental Clinic')]) }}
        </p>
    </div>
</div>
</body>
</html>