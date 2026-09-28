{{-- 病历模板的学科分类树管理（最多三级）。

     与模板表单里的「分类」（system / department / personal，归属范围）不是一回事，
     见 App\TemplateCategory。脚本在 public/include_js/template_categories.js。 --}}
<div class="modal fade modal-form" id="category-manager-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('templates.category_tree') }}</h4>
            </div>
            <div class="modal-body">
                <div class="tc-toolbar">
                    <button type="button" class="btn btn-sm btn-primary" id="tc-add-root">
                        <i class="fa fa-plus"></i> {{ __('templates.category_add_root') }}
                    </button>
                    <span class="tc-hint text-muted">{{ __('templates.category_too_deep', ['max' => 3]) }}</span>
                </div>
                <div class="tc-tree" id="tc-tree"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
