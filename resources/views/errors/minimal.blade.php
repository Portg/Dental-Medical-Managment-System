<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>
    <link href="{{ asset('fonts/noto-sans-sc/font-face.css') }}" rel="stylesheet">
    <link href="{{ asset('css/typography.css') }}" rel="stylesheet">
    <link href="{{ asset('css/error-page.css') }}" rel="stylesheet">
</head>
<body class="error-page">
<main class="error-page__content" role="main">
    <p class="error-page__code">@yield('code')</p>
    <p class="error-page__message">@yield('message')</p>
</main>
</body>
</html>

