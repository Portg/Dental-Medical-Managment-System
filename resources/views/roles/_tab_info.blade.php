{{-- Tab 1: 基本信息 --}}
<table class="table role-info-table">
    <tr>
        <td>{{ __('roles.name') }}</td>
        <td>{{ $role->name }}</td>
    </tr>
    <tr>
        <td>{{ __('roles.slug') }}</td>
        <td><code>{{ $role->slug }}</code></td>
    </tr>
    <tr>
        <td>{{ __('roles.users_count') }}</td>
        <td>{{ $role->users_count }}</td>
    </tr>
    <tr>
        <td>{{ __('roles.created_at') }}</td>
        <td>{{ $role->created_at ? $role->created_at->format('Y-m-d H:i') : '-' }}</td>
    </tr>
</table>
