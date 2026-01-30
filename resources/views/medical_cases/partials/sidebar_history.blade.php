{{-- History Records --}}
<div class="portlet light bordered">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.history_records') }}</span>
        </div>
    </div>
    <div class="portlet-body">
        @if(count($historyRecords) > 0)
            @foreach($historyRecords as $record)
                <div class="history-item" style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 12px; color: #999;">{{ $record->case_date }}</div>
                            <div style="font-size: 13px; color: #333;">{{ $record->title ?? __('medical_cases.visit_record') }}</div>
                        </div>
                        <span class="history-item-expand" onclick="toggleHistoryItem(this)" style="font-size: 12px; color: #4472C4; cursor: pointer;">
                            {{ __('medical_cases.expand') }}
                        </span>
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
    </div>
</div>
