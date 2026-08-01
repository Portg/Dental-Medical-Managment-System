<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('satisfaction.link_invalid') }} - {{ config('app.name') }}</title>
    <link href="{{ asset('backend/assets/global/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/survey-fill.css') }}?v={{ file_exists(public_path('css/survey-fill.css')) ? filemtime(public_path('css/survey-fill.css')) : time() }}" rel="stylesheet">
</head>
<body>
<div class="survey-wrap">
    <div class="survey-done">
        <div class="done-icon done-icon--muted">!</div>
        <h2>{{ __('satisfaction.link_invalid') }}</h2>
        {{-- 不区分「不存在 / 已填写 / 已过期」，避免被用来探测 token --}}
        <p>{{ __('satisfaction.link_invalid_hint') }}</p>
    </div>
</div>
</body>
</html>
