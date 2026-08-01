<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('satisfaction.fill_title') }} - {{ config('app.name') }}</title>

    {{-- 只用本地资源：目标机为离线内网部署，任何 CDN 依赖都会加载失败 --}}
    <link href="{{ asset('backend/assets/global/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/survey-fill.css') }}?v={{ file_exists(public_path('css/survey-fill.css')) ? filemtime(public_path('css/survey-fill.css')) : time() }}" rel="stylesheet">
</head>
<body>
<div class="survey-wrap">

    <div class="survey-header">
        <h1>{{ __('satisfaction.fill_title') }}</h1>
        <p class="survey-sub">{{ __('satisfaction.fill_intro') }}</p>
    </div>

    <div class="survey-meta">
        @if($survey->doctor)
            <span><b>{{ __('satisfaction.doctor') }}：</b>{{ $survey->doctor->full_name ?? $survey->doctor->name }}</span>
        @endif
        @if($survey->appointment)
            <span><b>{{ __('satisfaction.appointment_date') }}：</b>{{ optional($survey->appointment->start_date)->format('Y-m-d') ?? $survey->appointment->start_date }}</span>
        @endif
    </div>

    <form id="surveyForm" novalidate>
        @csrf

        @php
            // 复用已有的 satisfaction.ratings.* 文案，避免同义键重复维护
            $ratingFields = [
                'overall_rating'     => __('satisfaction.ratings.overall'),
                'service_rating'     => __('satisfaction.ratings.service'),
                'environment_rating' => __('satisfaction.ratings.environment'),
                'wait_time_rating'   => __('satisfaction.ratings.wait_time'),
                'doctor_rating'      => __('satisfaction.ratings.doctor'),
            ];
        @endphp

        @foreach($ratingFields as $field => $label)
            <div class="survey-block">
                <label class="survey-label">
                    {{ $label }}
                    @if($field === 'overall_rating')<span class="required">*</span>@endif
                </label>
                <div class="star-group" data-field="{{ $field }}">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" class="star" data-value="{{ $i }}"
                                aria-label="{{ $i }}">★</button>
                    @endfor
                    <span class="star-hint"></span>
                </div>
                <input type="hidden" name="{{ $field }}" value="">
            </div>
        @endforeach

        <div class="survey-block">
            <label class="survey-label">{{ __('satisfaction.would_recommend') }}</label>
            <p class="survey-help">{{ __('satisfaction.nps_help') }}</p>
            <div class="nps-group" data-field="would_recommend">
                @for($i = 0; $i <= 10; $i++)
                    <button type="button" class="nps" data-value="{{ $i }}">{{ $i }}</button>
                @endfor
            </div>
            <input type="hidden" name="would_recommend" value="">
        </div>

        <div class="survey-block">
            <label class="survey-label" for="feedback">{{ __('satisfaction.feedback') }}</label>
            <textarea id="feedback" name="feedback" rows="3" maxlength="1000"
                      placeholder="{{ __('satisfaction.feedback_placeholder') }}"></textarea>
        </div>

        <div class="survey-block">
            <label class="survey-label" for="suggestions">{{ __('satisfaction.suggestions') }}</label>
            <textarea id="suggestions" name="suggestions" rows="3" maxlength="1000"
                      placeholder="{{ __('satisfaction.suggestions_placeholder') }}"></textarea>
        </div>

        <div class="survey-block survey-anon">
            <label>
                <input type="checkbox" name="is_anonymous" value="1">
                {{ __('satisfaction.submit_anonymously') }}
            </label>
        </div>

        <div id="surveyError" class="survey-error" style="display:none;"></div>

        <button type="submit" class="survey-submit" id="submitBtn">
            {{ __('satisfaction.submit') }}
        </button>
    </form>

    <div id="surveyDone" class="survey-done" style="display:none;">
        <div class="done-icon">✓</div>
        <h2>{{ __('satisfaction.thank_you') }}</h2>
        <p>{{ __('satisfaction.thank_you_sub') }}</p>
    </div>

</div>

<script>
    window.SURVEY_CONFIG = {
        submitUrl: @json(route('survey.submit', ['token' => request()->route('token')])),
        messages: {
            ratingRequired: @json(__('satisfaction.overall_rating_required')),
            submitting:     @json(__('satisfaction.submitting')),
            submit:         @json(__('satisfaction.submit')),
            networkError:   @json(__('satisfaction.network_error'))
        },
        starHints: @json(__('satisfaction.star_hints'))
    };
</script>
<script src="{{ asset('backend/assets/global/plugins/jquery.min.js') }}"></script>
<script src="{{ asset('include_js/survey_fill.js') }}?v={{ file_exists(public_path('include_js/survey_fill.js')) ? filemtime(public_path('include_js/survey_fill.js')) : time() }}"></script>
</body>
</html>
