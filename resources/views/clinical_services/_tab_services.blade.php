<div class="service-toolbar-card">
    <div class="service-toolbar-main">
        <div class="service-toolbar-filters">
            <div class="service-filter-field service-filter-search">
                <div class="search-input-wrapper">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" class="form-control" id="service-search-input"
                           placeholder="{{ __('clinical_services.search_placeholder') }}">
                </div>
            </div>
            <div class="service-filter-field service-filter-status">
                <select id="service-status-filter" class="form-control">
                    <option value="">{{ __('common.status') }}</option>
                    <option value="1">{{ __('common.active') }}</option>
                    <option value="0">{{ __('common.inactive') }}</option>
                </select>
            </div>
        </div>
        @can('manage-medical-services')
        <div class="service-toolbar-actions">
            <button class="btn btn-success" id="btn-add-service">
                <i class="fa fa-plus"></i> {{ __('clinical_services.add_service_item') }}
            </button>
            <div class="btn-group service-more-actions">
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-ellipsis-h"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    <li>
                        <a href="#" id="btn-import-menu">
                            <i class="fa fa-upload"></i> {{ __('common.import') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('clinic-services.export') }}" id="btn-export-menu">
                            <i class="fa fa-download"></i> {{ __('common.export') }}
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="#" id="btn-batch-price-menu">
                            <i class="fa fa-line-chart"></i> {{ __('clinical_services.batch_update_price') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        @endcan
    </div>
</div>

<div class="service-category-card">
    <div class="category-filter-bar" style="display:flex;align-items:center;gap:6px;">
        <ul class="nav nav-pills category-pills" id="category-list">
            <li class="active" data-id="0" data-name="{{ __('common.all') }}" style="cursor:pointer;">
                <a href="#">{{ __('common.all') }}</a>
            </li>
        </ul>
        @can('manage-service-categories')
        <button class="btn btn-circle btn-icon-only btn-success btn-sm" id="btn-add-category"
                title="{{ __('common.add') }}">
            <i class="fa fa-plus"></i>
        </button>
        @endcan
    </div>
</div>

<div class="service-table-card">
    <table id="services-datatable" class="table list-table service-table" style="width:100%;">
        <thead>
            <tr>
                <th>{{ __('common.id') }}</th>
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
</div>
