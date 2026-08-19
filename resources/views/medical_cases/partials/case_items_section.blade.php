{{-- 分段明细：可重复的「牙位 + 文字」行。检查/其他检查/诊断/治疗四段共用。

     参数：
       $section   段落 key（examination / auxiliary_examination / diagnosis / treatment）
       $title     段落标题
       $hint      右上角提示
       $rows      已有行 [['tooth_no'=>..,'content'=>..], ...]（由 getCaseItemsForEdit 给）
       $required  是否必填
       $placeholder 行内文字的占位提示

     每一段原来是一个 textarea + 整段共享的牙位标签列表，文字和牙位对不上号
     （「45 缺失」和「36 龋坏」揉在一段话里）。现在一行一个牙位，牙位选择器
     绑到行而不是段落。

     #{{ $section }} 那个 textarea 保留但**不提交**（没有 name）：它由行实时渲染，
     只供既有的客户端必填校验与质量检查读取（那些代码读 $('#examination').val()）。
     提交只带 case_items，服务端由行派生文本 —— 请求里只有一份真相。 --}}
<div class="soap-section case-items-section" data-section="{{ $section }}">
    <div class="soap-section-header">
        <div class="soap-section-title">
            {{ $title }}
            @if($required ?? false)<span class="required">*</span>@endif
        </div>
        @if(isset($hint))
            <div class="soap-section-hint">{{ $hint }}</div>
        @endif
    </div>
    <div class="soap-section-body">
        {{-- 段落工具条。参考视频里那条「特殊符号 | 复制牙位 | 全部展开 | 全部折叠」：
             复制牙位是真省事的一个 —— 检查写了 45，诊断、治疗多半也是 45，
             不用再去牙位图上点三遍。 --}}
        <div class="case-items-toolbar">
            <button type="button" class="btn btn-xs btn-default js-add-case-item" data-section="{{ $section }}">
                <i class="fa fa-plus"></i> {{ __('medical_cases.add_item_row') }}
            </button>
            @if($section !== 'examination')
                <button type="button" class="btn btn-xs btn-link js-copy-teeth" data-section="{{ $section }}"
                        title="{{ __('medical_cases.copy_teeth_hint') }}">
                    <i class="fa fa-clone"></i> {{ __('medical_cases.copy_teeth') }}
                </button>
            @endif
            <button type="button" class="btn btn-xs btn-link js-toggle-section" data-section="{{ $section }}">
                <i class="fa fa-chevron-up"></i> <span class="toggle-text">{{ __('medical_cases.collapse') }}</span>
            </button>
            <span class="help-block case-items-hint">{{ __('medical_cases.item_row_hint') }}</span>
        </div>

        <div class="case-items-rows" id="rows-{{ $section }}"></div>

        {{-- 由行渲染出来的整段文字。没有 name，不进请求；只给校验代码读。 --}}
        <textarea id="{{ $section }}" class="case-items-derived" readonly
                  data-derived="1" tabindex="-1"
                  aria-hidden="true" style="display:none"></textarea>

        {{-- 初始行数据，交给 CaseItems 模块渲染 --}}
        <script type="application/json" class="js-case-items-seed" data-section="{{ $section }}">
            {!! json_encode($rows ?? [], JSON_UNESCAPED_UNICODE) !!}
        </script>
    </div>
</div>
