{{-- Quick Phrases —— 从 quick_phrases 表取，按分类分组。

     原来这里是 Blade 里写死的 8 条（走 medical_cases.phrase_* 语言键），而库里有
     30 条、分三类。结果是诊所在「快捷短语」页维护的短语，医生在病历页根本看不到，
     看到的是另外一套改不了的。和主诉那三个硬编码模板按钮是同一个毛病。

     $phrasesByCategory 由 AppServiceProvider 的 View Composer 注入 —— 病历编辑页
     有三个入口都渲染 medical_cases.edit，在 Controller 里逐个传必定漏一个。 --}}
<div class="portlet light bordered sidebar-tool-panel @if($needPatientSelection ?? false) disabled @endif">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.quick_phrases') }}</span>
        </div>
    </div>
    <div class="portlet-body">
        <div style="background: #f8f9fa; border-radius: 4px; padding: 8px 10px; margin-bottom: 10px; font-size: 12px; color: #666; line-height: 1.8;">
            <div>💡 {{ __('medical_cases.hint_template_picker', ['key' => '/']) }}</div>
            <div>💡 {{ __('medical_cases.hint_phrase_picker', ['key' => ';']) }}</div>
        </div>

        @forelse(($phrasesByCategory ?? collect()) as $category => $phrases)
            <div class="phrase-category-group" style="margin-bottom: 10px;">
                <div style="font-size: 12px; color: #999; margin-bottom: 5px;">
                    {{ __('medical_cases.phrase_category_' . $category) }}
                </div>
                <div class="quick-phrases-grid" style="display: flex; flex-wrap: wrap; gap: 6px;">
                    @foreach($phrases as $p)
                        {{-- title 里带上简写：; 触发的选择器认这个简写，
                             把它露出来才有人知道可以直接打 --}}
                        <span class="quick-phrase btn btn-xs btn-default"
                              data-phrase="{{ $p->phrase }}"
                              title="{{ $p->shortcut ? $p->shortcut . ' → ' . $p->phrase : $p->phrase }}">
                            {{ $p->phrase }}
                        </span>
                    @endforeach
                </div>
            </div>
        @empty
            <div style="font-size: 12px; color: #999;">
                {{ __('medical_cases.no_quick_phrases') }}
            </div>
        @endforelse
    </div>
</div>
