@extends('layouts.list-page')

@section('page_title', __('expense_categories.title'))
@section('table_id', 'expense-categories-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('expense_categories.item_name') }}</th>
    <th>{{ __('expense_categories.expense_account') }}</th>
    <th>{{ __('expense_categories.added_by') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('expense_categories.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        var dtm = new DataTableManager({
            tableId: '#expense-categories-table',
            ajaxUrl: "{{ url('/expense-categories/') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'name', name: 'name'},
                {data: 'expense_account', name: 'expense_account'},
                {data: 'addedBy', name: 'addedBy'},
                {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
            ],
            modal: {
                formId: '#category-form',
                modalId: '#category-modal',
                btnId: '#btn-save',
                resourceUrl: '/expense-categories'
            },
            onEditLoad: function(data) {
                $('[name="name"]').val(data.name);
                $('#expense_account').val(data.chart_of_account_item_id);
            }
        });

        window.save_data = function() { dtm.saveOrUpdate(); };
    });
</script>
@endsection
