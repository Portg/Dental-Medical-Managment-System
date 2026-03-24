<div class="sterilization-section-header">
    <div>
        <div class="sterilization-section-title">{{ __('sterilization.kits_tab') }}</div>
        <div class="sterilization-section-desc">{{ __('sterilization.kits_desc') }}</div>
    </div>
    <div class="sterilization-tab-toolbar">
        @can('manage-sterilization')
        <button class="btn btn-success" id="btn-add-kit">{{ __('common.add') }}</button>
        @endcan
    </div>
</div>

<div class="sterilization-table-panel">
    <table id="kits-datatable" class="table table-hover list-table sterilization-table w-100">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('sterilization.kit_no') }}</th>
                <th>{{ __('sterilization.kit_name') }}</th>
                <th>器械数量</th>
                <th>状态</th>
                <th>{{ __('common.action') }}</th>
            </tr>
        </thead>
    </table>
</div>
