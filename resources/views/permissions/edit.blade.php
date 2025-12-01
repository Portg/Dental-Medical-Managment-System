@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">Edit Permission</span>
                </div>
            </div>
            <div class="portlet-body form">
                <form action="{{ route('permissions.update', $permission->id) }}" method="POST" class="form-horizontal">
                    @csrf
                    @method('PUT')
                    <div class="form-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="col-md-3 control-label">Permission Name <span class="text-danger">*</span></label>
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" value="{{ old('name', $permission->name) }}" required>
                                <span class="help-block">Enter a descriptive name for this permission</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3 control-label">Slug <span class="text-danger">*</span></label>
                            <div class="col-md-6">
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $permission->slug) }}" required>
                                <span class="help-block">Unique identifier for this permission</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3 control-label">Description</label>
                            <div class="col-md-6">
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $permission->description) }}</textarea>
                                <span class="help-block">Describe what this permission allows</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <div class="row">
                            <div class="col-md-offset-3 col-md-6">
                                <button type="submit" class="btn blue">
                                    <i class="fa fa-check"></i> Update Permission
                                </button>
                                <a href="{{ route('permissions.index') }}" class="btn default">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
