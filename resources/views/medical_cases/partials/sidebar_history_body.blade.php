{{-- 病例历史的列表本体。
     单独成文件是为了两处复用：整页渲染时由 sidebar_history 包一层外壳，
     选完患者后由 medical_record_edit.js 拉这一段替换进去。
     写两份会漂移 —— 侧栏改了样式而 AJAX 那份没改，是这类问题的常见结局。 --}}
@if(count($historyRecords) > 0)
    @foreach($historyRecords as $record)
        <div class="history-item" style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 12px; color: #999;">{{ $record->case_date?->format('Y-m-d') }}</div>
                    <div style="font-size: 13px; color: #333;">{{ $record->title ?? __('medical_cases.visit_record') }}</div>
                </div>
                <div style="white-space: nowrap;">
                    {{-- 带入本次：把上次的检查/诊断/治疗按行复制到当前表单。

                         复诊是新建一份病历（一次就诊一份），而复诊的内容多半是在上次
                         基础上改几个字 —— 牙位一样、诊断一样、治疗接着上次做。
                         没有这个按钮就得对着侧栏一行行重打。

                         只带临床内容，不带主诉/现病史：那是患者这次怎么说的，
                         每次都不同，带过来反而要删。 --}}
                    @if(!($readonlyHistory ?? false))
                        <span class="history-item-copy" data-case-id="{{ $record->id }}"
                              style="font-size: 12px; color: #52c41a; cursor: pointer; margin-right: 8px;">
                            {{ __('medical_cases.copy_into_current') }}
                        </span>
                    @endif
                    <span class="history-item-expand" onclick="toggleHistoryItem(this)" style="font-size: 12px; color: #4472C4; cursor: pointer;">
                        {{ __('medical_cases.expand') }}
                    </span>
                </div>
            </div>
            <div class="history-item-content" style="font-size: 12px; color: #666; margin-top: 8px; display: none;">
                @if($record->chief_complaint)
                    <p><strong>{{ __('medical_cases.chief_complaint') }}:</strong> {{ Str::limit($record->chief_complaint, 100) }}</p>
                @endif
                @if($record->diagnosis)
                    <p><strong>{{ __('medical_cases.diagnosis') }}:</strong> {{ Str::limit($record->diagnosis, 100) }}</p>
                @endif
            </div>
        </div>
    @endforeach
@else
    <div class="text-muted text-center" style="padding: 20px;">
        {{ __('medical_cases.no_history_records') }}
    </div>
@endif
