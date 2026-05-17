@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    <link rel="stylesheet" href="{{ asset('css/work-log.css') }}?v={{ filemtime(public_path('css/work-log.css')) }}">
@endsection

@section('content')
<div class="container-fluid work-log-page">
    {{-- Phase 1: Upload --}}
    <div id="upload-section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">{{ __('work_log.page_title') }}</h4>
                <a href="{{ url('work-logs') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-list"></i> {{ __('work_log.view_saved') }}
                </a>
            </div>
            <div class="card-body">
                <div id="drop-zone" class="drop-zone">
                    <div class="drop-zone-content">
                        <i class="fa fa-table fa-3x text-muted"></i>
                        <p class="mt-3">{{ __('work_log.upload_hint') }}</p>
                        <p class="text-muted small">{{ __('work_log.supported_formats') }}</p>
                        <input type="file" id="image-input" accept="image/jpeg,image/png,image/jpg,image/bmp" style="display:none">
                    </div>
                    <div id="image-preview-upload" style="display:none">
                        <img id="preview-img-upload" src="" alt="preview">
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="btn-clear-image">
                            <i class="fa fa-times"></i> {{ __('common.remove') }}
                        </button>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-primary btn-lg" id="btn-recognize" disabled>
                        <i class="fa fa-search"></i> {{ __('work_log.start_recognize') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div id="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">{{ __('work_log.recognizing') }}</p>
        </div>
    </div>

    {{-- Phase 2: Editable grid --}}
    <div id="result-section" style="display:none">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('work_log.original_image') }}</h5>
            </div>
            <div class="card-body text-center">
                <img id="result-image" src="" alt="original" class="img-fluid result-image">
            </div>
        </div>

        <div id="confidence-alert" class="alert" role="alert">
            <i class="fa fa-info-circle"></i>
            <span id="confidence-text"></span>
            <span id="confidence-value" class="badge"></span>
        </div>

        <div id="no-header-warning" class="alert alert-warning" style="display:none">
            <i class="fa fa-exclamation-triangle"></i> {{ __('work_log.no_header_detected') }}
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('work_log.review_title') }}</h5>
                <div class="form-inline">
                    <label class="mr-2">{{ __('work_log.year') }}</label>
                    <input type="number" id="opt-year" class="form-control form-control-sm mr-3"
                           style="width:90px" min="2000" max="2100" value="{{ date('Y') }}">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="opt-link-patients" checked>
                        <label class="form-check-label" for="opt-link-patients">{{ __('work_log.opt_link_patients') }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="opt-generate-invoices" checked>
                        <label class="form-check-label" for="opt-generate-invoices">{{ __('work_log.opt_generate_invoices') }}</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm work-log-grid" id="work-log-grid">
                        <thead>
                            <tr>
                                <th style="width:36px">#</th>
                                <th>{{ __('work_log.col_date') }}</th>
                                <th>{{ __('work_log.col_name') }}</th>
                                <th>{{ __('work_log.col_gender') }}</th>
                                <th>{{ __('work_log.col_age') }}</th>
                                <th>{{ __('work_log.col_visit_type') }}</th>
                                <th>{{ __('work_log.col_phone') }}</th>
                                <th>{{ __('work_log.col_tooth') }}</th>
                                <th>{{ __('work_log.col_diagnosis') }}</th>
                                <th>{{ __('work_log.col_prescription') }}</th>
                                <th>{{ __('work_log.col_doctor') }}</th>
                                <th>{{ __('work_log.col_amount') }}</th>
                                <th style="width:46px"></th>
                            </tr>
                        </thead>
                        <tbody id="work-log-grid-body"></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-row">
                    <i class="fa fa-plus"></i> {{ __('work_log.add_row') }}
                </button>
            </div>
        </div>

        <div class="text-center mb-4">
            <button type="button" class="btn btn-secondary mr-2" id="btn-back-upload">
                <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
            </button>
            <button type="button" class="btn btn-primary btn-lg" id="btn-save">
                <i class="fa fa-check"></i> {{ __('work_log.save_all') }}
            </button>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    LanguageManager.loadAllFromPHP({
        'work_log': @json(__('work_log'))
    });

    var recognizeUrl = '{{ url("work-log-ocr/recognize") }}';
    var storeUrl = '{{ url("work-log-ocr/store") }}';
    var workLogsUrl = '{{ url("work-logs") }}';
</script>
<script src="{{ asset('include_js/work_log_recognize.js') }}?v={{ filemtime(public_path('include_js/work_log_recognize.js')) }}" type="text/javascript"></script>
@endsection
