@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">Manage Permissions for: {{ $role->name }}</span>
                </div>
            </div>
            <div class="portlet-body form">
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        <button class="close" data-dismiss="alert"></button>
                        {{ session()->get('success') }}
                    </div>
                @endif

                <form action="{{ route('role-permissions.update', $role->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            Select the permissions you want to grant to the <strong>{{ $role->name }}</strong> role.
                        </div>

                        <div class="row">
                            @php
                                $groupedPermissions = $permissions->groupBy(function($permission) {
                                    return explode(' ', $permission->name)[1] ?? 'Other';
                                });
                            @endphp

                            @foreach($groupedPermissions as $group => $groupPermissions)
                                <div class="col-md-6">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <h4 class="panel-title">{{ $group }}</h4>
                                        </div>
                                        <div class="panel-body">
                                            @foreach($groupPermissions as $permission)
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox"
                                                               name="permissions[]"
                                                               value="{{ $permission->id }}"
                                                               {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                                        {{ $permission->name }}
                                                        @if($permission->description)
                                                            <br><small class="text-muted">{{ $permission->description }}</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-actions">
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn blue">
                                    <i class="fa fa-check"></i> Update Role Permissions
                                </button>
                                <a href="{{ route('roles.index') }}" class="btn default">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .panel {
        margin-bottom: 20px;
        background-color: #fff;
        border: 1px solid transparent;
        border-radius: 4px;
        box-shadow: 0 1px 1px rgba(0,0,0,.05);
    }
    .panel-default {
        border-color: #ddd;
    }
    .panel-heading {
        padding: 10px 15px;
        border-bottom: 1px solid transparent;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
        background-color: #f5f5f5;
        border-color: #ddd;
    }
    .panel-title {
        margin-top: 0;
        margin-bottom: 0;
        font-size: 16px;
        color: inherit;
    }
    .panel-body {
        padding: 15px;
    }
    .checkbox {
        margin-bottom: 10px;
    }
</style>
@endsection
