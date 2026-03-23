<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('common.import') }} — {{ __('clinical_services.name') }}</h4>
            </div>
            <div class="modal-body">
                <p>
                    <a href="{{ route('clinic-services.export') }}" class="btn btn-sm btn-default">
                        <i class="fa fa-download"></i> {{ __('clinical_services.download_import_template') }}
                    </a>
                </p>
                <p class="text-muted" style="font-size: 12px;">
                    {{ __('clinical_services.import_template_hint') }}
                </p>
                <form id="import-form" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('clinical_services.select_file') }} <span class="required">*</span></label>
                        <input type="file" id="import-file" name="file" accept=".xlsx,.xls">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-import">{{ __('clinical_services.start_import') }}</button>
            </div>
        </div>
    </div>
</div>
