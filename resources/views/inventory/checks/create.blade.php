@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link href="{{ asset('backend/assets/global/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('css/inventory-check.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('inventory.create_check') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('inventory-checks') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="alert alert-danger" id="error-alert" style="display:none">
                    <ul id="error-list"></ul>
                </div>

                <form id="check-form" autocomplete="off">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.category') }} <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-control select2" style="width:100%" required>
                                    <option value="">{{ __('inventory.select_category') }}</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.check_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="check_date" id="check_date" class="form-control datepicker"
                                       value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.notes') }}</label>
                                <input type="text" name="notes" id="notes" class="form-control"
                                       placeholder="{{ __('inventory.notes') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-20">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                {{ __('inventory.check_create_hint') }}
                            </div>
                        </div>
                    </div>

                    <div class="row mt-10">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" onclick="submitCheck()">
                                <i class="fa fa-plus"></i> {{ __('inventory.create_check') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/select2/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script>
    LanguageManager.loadAllFromPHP({
        'inventory': @json(__('inventory')),
        'common':    @json(__('common'))
    });
    window.InventoryCheckConfig = { csrfToken: '{{ csrf_token() }}' };
    </script>
    <script src="{{ asset('include_js/inventory_check.js') }}?v={{ filemtime(public_path('include_js/inventory_check.js')) }}" type="text/javascript"></script>
    <script>
    $(function () { initCheckCreate(); });
    </script>
@endsection
