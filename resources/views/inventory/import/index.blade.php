@extends('layouts.app')

@section('page_title', __('inventory.bulk_import'))

@section('css')
    <link rel="stylesheet" href="{{ asset('css/inventory-import.css') }}">
@endsection

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">{{ __('inventory.bulk_import') }}</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('inventory-items') }}">{{ __('inventory.items') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('inventory.bulk_import') }}</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- 步骤提示区 --}}
    <div class="row import-steps mb-4">
        <div class="col-md-4">
            <div class="step-card step-1">
                <div class="step-number">1</div>
                <div class="step-body">
                    <div class="step-title">{{ __('inventory.step1_download') }}</div>
                    <p class="step-desc">{{ __('inventory.step1_download_desc') }}</p>
                    <a href="{{ url('inventory-import/template') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-download"></i> {{ __('inventory.download_template') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="step-card step-2">
                <div class="step-number">2</div>
                <div class="step-body">
                    <div class="step-title">{{ __('inventory.step2_fill') }}</div>
                    <p class="step-desc">{{ __('inventory.step2_fill_desc') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="step-card step-3">
                <div class="step-number">3</div>
                <div class="step-body">
                    <div class="step-title">{{ __('inventory.step3_upload') }}</div>
                    <p class="step-desc">{{ __('inventory.step3_upload_desc') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 上传区域 --}}
    <div class="card import-upload-card">
        <div class="card-body">
            <h5 class="card-title mb-4">{{ __('inventory.import_items') }}</h5>

            <div id="drop-zone" class="drop-zone">
                <div class="drop-zone-inner">
                    <i class="fa fa-cloud-upload drop-icon"></i>
                    <p class="drop-text">{{ __('inventory.drop_or_click') }}</p>
                    <p class="drop-hint">{{ __('inventory.file_hint') }}</p>
                    <input type="file" id="import-file" accept=".xlsx,.xls" class="drop-file-input">
                </div>
            </div>

            <div id="file-info" class="file-info d-none">
                <i class="fa fa-file-excel-o text-success"></i>
                <span id="file-name" class="ml-2"></span>
                <button type="button" class="btn btn-link btn-sm text-danger ml-2" id="btn-clear-file">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-primary" id="btn-import" disabled>
                    <i class="fa fa-upload"></i> {{ __('inventory.start_import') }}
                </button>
                <button type="button" class="btn btn-default ml-2" id="btn-reset" style="display:none;">
                    {{ __('common.reset') }}
                </button>
            </div>

            {{-- 进度条 --}}
            <div id="import-progress" class="mt-3 d-none">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width: 100%">
                        {{ __('inventory.importing') }}...
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 结果区域 --}}
    <div id="import-result" class="mt-4 d-none">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('inventory.import_result') }}</h5>
                <div id="result-summary" class="result-summary mb-3"></div>

                <div id="error-section" class="d-none">
                    <h6 class="text-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        {{ __('inventory.import_errors') }}
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered error-table">
                            <thead>
                                <tr>
                                    <th width="100">{{ __('inventory.import_row_no') }}</th>
                                    <th>{{ __('inventory.import_error_reason') }}</th>
                                </tr>
                            </thead>
                            <tbody id="error-list"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script type="text/javascript">
        LanguageManager.loadAllFromPHP({
            'inventory': @json(__('inventory')),
            'common': @json(__('common'))
        });
    </script>
    <script src="{{ asset('include_js/inventory_import.js') }}?v={{ filemtime(public_path('include_js/inventory_import.js')) }}" type="text/javascript"></script>
    <script type="text/javascript">
        var csrfToken = "{{ csrf_token() }}";
        var importUrl = "{{ url('inventory-import') }}";
    </script>
@endsection
