<div class="row">
    {{-- 左：大类树 --}}
    <div class="col-md-3" id="category-tree-panel">
        <div class="portlet light bordered" style="margin-bottom: 0;">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('clinical_services.service_categories') }}</span>
                </div>
                @can('manage-service-categories')
                <div class="actions">
                    <button class="btn btn-circle btn-icon-only btn-success" id="btn-add-category" title="{{ __('common.add') }}">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                @endcan
            </div>
            <div class="portlet-body" style="padding: 5px 0;">
                <ul class="list-group" id="category-list" style="margin-bottom: 0;">
                    <li class="list-group-item active" data-id="0" style="cursor:pointer;">
                        {{ __('common.all') }}
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- 右：项目 DataTable --}}
    <div class="col-md-9">
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

        <table id="services-datatable" class="table table-striped table-bordered table-hover" style="width:100%;">
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
    </div>
</div>
