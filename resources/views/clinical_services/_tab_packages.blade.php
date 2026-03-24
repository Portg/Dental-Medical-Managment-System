<div class="row" style="margin-bottom: 10px;">
    <div class="col-md-12">
        @can('manage-service-packages')
        <button class="btn btn-success" id="btn-add-package">
            <i class="fa fa-plus"></i> {{ __('common.add') }}
        </button>
        @endcan
    </div>
</div>

<table id="packages-datatable" class="table table-striped table-bordered table-hover" style="width:100%;">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('clinical_services.package_name') }}</th>
            <th>{{ __('clinical_services.package_total_price') }}</th>
            <th>{{ __('clinical_services.package_description') }}</th>
            <th>{{ __('clinical_services.is_active') }}</th>
            <th>{{ __('common.action') }}</th>
        </tr>
    </thead>
</table>
