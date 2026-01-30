<select id="select2-single-input-group-sm"
        class="form-control select2">
    <option value="null">{{ __('expenses.choose_expense_category') }}</option>
    @foreach($chart_of_accts as $cat)
        <option value="{{$cat->id}}">{{$cat->name}}</option>
    @endforeach
</select>