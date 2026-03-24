<div class="filter-area-l2 sterilization-filter-panel">
    <div class="row filter-row">
        <div class="col-md-3">
            <label class="filter-label">器械包</label>
        <select class="form-control select2" id="filter-kit-id">
            <option value="">{{ __('common.all') }}（器械包）</option>
            @foreach($kits as $kit)
            <option value="{{ $kit->id }}">{{ $kit->kit_no }} - {{ $kit->name }}</option>
            @endforeach
        </select>
        </div>
        <div class="col-md-2">
            <label class="filter-label">状态</label>
            <select class="form-control" id="filter-status">
                <option value="">{{ __('common.all') }}（状态）</option>
                <option value="valid">{{ __('sterilization.status_valid') }}</option>
                <option value="used">{{ __('sterilization.status_used') }}</option>
                <option value="expired">{{ __('sterilization.status_expired') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="filter-label">{{ __('common.start_date') }}</label>
            <input type="date" class="form-control" id="filter-date-from" placeholder="{{ __('common.start_date') }}">
        </div>
        <div class="col-md-2">
            <label class="filter-label">{{ __('common.end_date') }}</label>
            <input type="date" class="form-control" id="filter-date-to" placeholder="{{ __('common.end_date') }}">
        </div>
        <div class="col-md-3 text-right sterilization-filter-actions">
            <button class="btn btn-default" id="btn-reset-records">{{ __('common.reset') }}</button>
            <button class="btn btn-primary" id="btn-filter-records">{{ __('common.search') }}</button>
            @can('manage-sterilization')
            <button class="btn btn-success ml-1" id="btn-add-record">{{ __('common.add') }}</button>
            <a class="btn btn-secondary ml-1" href="{{ route('sterilization.export') }}">{{ __('common.export') }}</a>
            @endcan
        </div>
    </div>
</div>

<table id="records-datatable" class="table table-hover list-table sterilization-table w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('sterilization.batch_no') }}</th>
            <th>器械包</th>
            <th>{{ __('sterilization.method') }}</th>
            <th>{{ __('sterilization.sterilized_at') }}</th>
            <th>{{ __('sterilization.expires_at') }}</th>
            <th>{{ __('sterilization.operator') }}</th>
            <th>状态</th>
            <th>{{ __('common.action') }}</th>
        </tr>
    </thead>
</table>
