<div class="service-toolbar-card">
    <div class="service-toolbar-main">
        <div class="service-toolbar-filters">
            <div class="service-filter-field service-filter-search">
                <div class="search-input-wrapper">
                    <i class="fa fa-search search-icon"></i>
                    <input type="text" class="form-control" id="package-search-input"
                           placeholder="{{ __('clinical_services.package_search_placeholder') }}">
                </div>
            </div>
            <div class="service-filter-field service-filter-status">
                <select id="package-status-filter" class="form-control">
                    <option value="">{{ __('common.status') }}</option>
                    <option value="1">{{ __('common.active') }}</option>
                    <option value="0">{{ __('common.inactive') }}</option>
                </select>
            </div>
        </div>
        @can('manage-service-packages')
        <div class="service-toolbar-actions">
            <button class="btn btn-success" id="btn-add-package">
                <i class="fa fa-plus"></i> {{ __('clinical_services.add_service_package') }}
            </button>
        </div>
        @endcan
    </div>
</div>

<div class="service-table-card">
    <table id="packages-datatable" class="table list-table service-table" style="width:100%;">
        <thead>
            <tr>
                <th>{{ __('common.id') }}</th>
                <th>{{ __('clinical_services.package_name') }}</th>
                <th>{{ __('clinical_services.package_total_price') }}</th>
                <th>{{ __('clinical_services.package_description') }}</th>
                <th>{{ __('clinical_services.is_active') }}</th>
                <th>{{ __('common.action') }}</th>
            </tr>
        </thead>
    </table>
</div>
