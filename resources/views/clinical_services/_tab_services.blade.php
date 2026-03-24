{{-- Action Buttons --}}
<div class="row" style="margin-bottom: 10px;">
    <div class="col-md-12">
        @can('manage-medical-services')
        <button class="btn btn-success" id="btn-add-service">
            <i class="fa fa-plus"></i> {{ __('common.add') }}
        </button>
        <button class="btn btn-warning" id="btn-batch-price" style="margin-left: 5px;">
            {{ __('clinical_services.batch_update_price') }}
        </button>
        <button class="btn btn-info" id="btn-import" style="margin-left: 5px;">
            <i class="fa fa-upload"></i> {{ __('common.import') }}
        </button>
        <a class="btn btn-default" id="btn-export" style="margin-left: 5px;"
           href="{{ route('clinic-services.export') }}">
            <i class="fa fa-download"></i> {{ __('common.export') }}
        </a>
        @endcan
    </div>
</div>

{{-- Category Filter Pills --}}
<div class="category-filter-bar">
    @can('manage-service-categories')
    <button class="btn btn-circle btn-icon-only btn-success btn-sm" id="btn-add-category"
            title="{{ __('common.add') }}" style="margin-right: 8px; flex-shrink: 0;">
        <i class="fa fa-plus"></i>
    </button>
    @endcan
    <ul class="nav nav-pills category-pills" id="category-list">
        <li class="active" data-id="0" style="cursor:pointer;">
            <a href="#">{{ __('common.all') }}</a>
        </li>
    </ul>
</div>

{{-- DataTable --}}
<table id="services-datatable" class="table table-striped table-bordered table-hover" style="width:100%; margin-top: 10px;">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('clinical_services.name') }}</th>
            <th>{{ __('clinical_services.service_categories') }}</th>
            <th>{{ __('clinical_services.unit') }}</th>
            <th>{{ __('clinical_services.price') }}</th>
            <th>{{ __('clinical_services.is_discountable') }}</th>
            <th>{{ __('clinical_services.is_favorite') }}</th>
            <th>{{ __('clinical_services.is_active') }}</th>
            <th>{{ __('common.action') }}</th>
        </tr>
    </thead>
</table>
