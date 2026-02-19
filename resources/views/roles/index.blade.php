@extends('layouts.list-page')

@section('page_title', __('roles.title'))

@section('table_id', 'roles_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.date') }}</th>
    <th>{{ __('roles.name') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
@include('roles.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'roles': @json(__('roles')),
            'common': @json(__('common'))
        });

        var dtm = new DataTableManager({
            tableId: '#roles_table',
            ajaxUrl: "{{ url('/roles') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true, width: '50px'},
                {data: 'created_at', name: 'created_at', width: '160px'},
                {data: 'name', name: 'name'},
                {data: 'action', name: 'action', orderable: false, searchable: false, width: '90px'}
            ],
            modal: {
                formId: '#roles-form',
                modalId: '#roles-modal',
                btnId: '#btn-save',
                resourceUrl: "{{ url('/roles') }}"
            },
            onEditLoad: function(data) {
                $('[name="name"]').val(data.name);
            }
        });

        window.save_data = function() { dtm.saveOrUpdate(); };
    });
</script>
@endsection
