@can('manage-sterilization')
<button class="btn btn-success mb-2" id="btn-add-kit">{{ __('common.add') }}</button>
@endcan

<table id="kits-datatable" class="table table-bordered table-hover w-100">
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
