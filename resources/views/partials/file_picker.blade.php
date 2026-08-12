{{--
    统一的文件选择控件。

    原生 <input type="file"> 的按钮文案由浏览器渲染，跟着浏览器界面语言走，
    英文版 Chrome/Edge 上就是「Choose File / No file chosen」，夹在满页中文里。
    这个 partial 把 input 透明铺在 label 上，文案走 __('common.choose_file')。

    用法：
        @include('partials.file_picker', ['name' => 'file', 'id' => 'import-file', 'accept' => '.xlsx,.xls'])

    参数：
        name      必填，表单字段名
        id        必填，元素 id（JS 一般要按 id 取 files[0]）
        accept    选填，透传给 input 的 accept
        multiple  选填，true 时允许多选，文件名位置改显示「已选 N 个文件」

    CSS 与 JS 用 @once 自带，页面不用额外引；filemtime 做缓存击穿。
--}}
@once
    <link rel="stylesheet" href="{{ asset('css/file-picker.css') }}?v={{ filemtime(public_path('css/file-picker.css')) }}">
    <script src="{{ asset('include_js/file_picker.js') }}?v={{ filemtime(public_path('include_js/file_picker.js')) }}"></script>
@endonce

<div class="file-picker" data-file-picker>
    <label class="file-picker__btn">
        <input type="file" name="{{ $name }}" id="{{ $id }}"
               @if(!empty($accept)) accept="{{ $accept }}" @endif
               @if(!empty($multiple)) multiple @endif>
        <i class="fa fa-folder-open-o"></i> {{ __('common.choose_file') }}
    </label>
    <span class="file-picker__name"
          data-file-picker-name
          data-empty-text="{{ __('common.no_file_chosen') }}"
          data-many-text="{{ __('common.n_files_chosen', ['count' => ':count']) }}">{{ __('common.no_file_chosen') }}</span>
</div>
