{{-- Tooth Selector Modal --}}
<div class="modal fade" id="tooth_selector_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('medical_cases.select_teeth') }}</h4>
            </div>
            <div class="modal-body">
                <style>
                    .tooth-selector-container {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        padding: 20px;
                    }
                    .tooth-row {
                        display: flex;
                        margin-bottom: 4px;
                    }
                    .tooth-cell {
                        width: 36px;
                        height: 36px;
                        border: 1px solid #d9d9d9;
                        border-radius: 4px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 12px;
                        cursor: pointer;
                        margin: 2px;
                        transition: all 0.2s;
                        background: #fff;
                    }
                    .tooth-cell:hover {
                        border-color: #4472C4;
                        background: #f0f5ff;
                    }
                    .tooth-cell.selected {
                        background: #4472C4;
                        border-color: #2B579A;
                        color: #fff;
                    }
                    .quadrant-label {
                        font-size: 11px;
                        color: #999;
                        margin: 10px 0 5px 0;
                        width: 100%;
                        text-align: center;
                    }
                    .teeth-separator {
                        width: 100%;
                        height: 2px;
                        background: #e8e8e8;
                        margin: 10px 0;
                    }
                    .teeth-separator-vertical {
                        width: 2px;
                        height: 100%;
                        background: #e8e8e8;
                        margin: 0 8px;
                    }
                    .deciduous-section {
                        margin-top: 30px;
                        padding-top: 20px;
                        border-top: 1px dashed #e8e8e8;
                    }
                    .deciduous-label {
                        font-size: 13px;
                        color: #666;
                        margin-bottom: 10px;
                        text-align: center;
                    }
                    .tooth-cell.deciduous {
                        background: #fffbe6;
                        border-color: #ffe58f;
                    }
                    .tooth-cell.deciduous.selected {
                        background: #faad14;
                        border-color: #d48806;
                        color: #fff;
                    }
                </style>

                <div class="tooth-selector-container">
                    {{-- Permanent Teeth - Upper --}}
                    <div class="quadrant-label">{{ __('odontogram.upper_right') }} | {{ __('odontogram.upper_left') }}</div>
                    <div class="tooth-row">
                        {{-- Upper Right (18-11) --}}
                        @for($i = 18; $i >= 11; $i--)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                        <div class="teeth-separator-vertical"></div>
                        {{-- Upper Left (21-28) --}}
                        @for($i = 21; $i <= 28; $i++)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                    </div>

                    <div class="teeth-separator"></div>

                    {{-- Permanent Teeth - Lower --}}
                    <div class="tooth-row">
                        {{-- Lower Right (48-41) --}}
                        @for($i = 48; $i >= 41; $i--)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                        <div class="teeth-separator-vertical"></div>
                        {{-- Lower Left (31-38) --}}
                        @for($i = 31; $i <= 38; $i++)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                    </div>
                    <div class="quadrant-label">{{ __('odontogram.lower_right') }} | {{ __('odontogram.lower_left') }}</div>

                    {{-- Deciduous Teeth --}}
                    <div class="deciduous-section">
                        <div class="deciduous-label">{{ __('odontogram.deciduous_teeth') }}</div>
                        {{-- Upper Deciduous --}}
                        <div class="tooth-row">
                            @for($i = 55; $i >= 51; $i--)
                                <div class="tooth-cell deciduous" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                            @endfor
                            <div class="teeth-separator-vertical"></div>
                            @for($i = 61; $i <= 65; $i++)
                                <div class="tooth-cell deciduous" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                            @endfor
                        </div>
                        <div class="teeth-separator" style="width: 60%; margin-left: auto; margin-right: auto;"></div>
                        {{-- Lower Deciduous --}}
                        <div class="tooth-row">
                            @for($i = 85; $i >= 81; $i--)
                                <div class="tooth-cell deciduous" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                            @endfor
                            <div class="teeth-separator-vertical"></div>
                            @for($i = 71; $i <= 75; $i++)
                                <div class="tooth-cell deciduous" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Selected Teeth Display --}}
                <div style="margin-top: 20px; padding: 15px; background: #f5f7fa; border-radius: 4px;">
                    <div style="font-size: 13px; color: #666; margin-bottom: 8px;">{{ __('medical_cases.related_teeth') }}:</div>
                    <div id="selected-teeth-display" style="min-height: 30px;">
                        <span class="text-muted" id="no-teeth-selected">{{ __('common.none_selected') }}</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="confirmToothSelection()">
                    {{ __('common.confirm') }}
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var selectedTeethInModal = [];

document.addEventListener('DOMContentLoaded', function() {
    $('#tooth_selector_modal').on('show.bs.modal', function() {
        // Load current selected teeth based on currentToothField
        var inputId = currentToothField === 'examination' ? 'examination_teeth' : 'related_teeth';
        selectedTeethInModal = JSON.parse($('#' + inputId).val() || '[]');
        updateToothSelectorUI();
    });
});

function toggleTooth(tooth) {
    var toothStr = tooth.toString();
    var index = selectedTeethInModal.indexOf(toothStr);
    if (index === -1) {
        selectedTeethInModal.push(toothStr);
    } else {
        selectedTeethInModal.splice(index, 1);
    }
    updateToothSelectorUI();
}

function updateToothSelectorUI() {
    // Update cell styles
    $('#tooth_selector_modal .tooth-cell').each(function() {
        var tooth = $(this).data('tooth').toString();
        if (selectedTeethInModal.indexOf(tooth) !== -1) {
            $(this).addClass('selected');
        } else {
            $(this).removeClass('selected');
        }
    });

    // Update display
    var $display = $('#selected-teeth-display');
    if (selectedTeethInModal.length > 0) {
        var html = selectedTeethInModal.map(function(t) {
            return '<span class="tooth-tag" style="display: inline-block; margin: 2px; padding: 4px 8px; background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 3px; font-size: 12px; color: #1890ff;">' + t + '</span>';
        }).join('');
        $display.html(html);
    } else {
        $display.html('<span class="text-muted">{{ __("common.none_selected") }}</span>');
    }
}

function confirmToothSelection() {
    var inputId = currentToothField === 'examination' ? 'examination_teeth' : 'related_teeth';
    var tagContainer = currentToothField === 'examination' ? '#examination-teeth-tags' : '#related-teeth-tags';

    // Update hidden input
    $('#' + inputId).val(JSON.stringify(selectedTeethInModal));

    // Rebuild tags
    $(tagContainer).find('.tooth-tag').remove();
    selectedTeethInModal.forEach(function(tooth) {
        var tag = '<span class="tooth-tag" data-tooth="' + tooth + '">' +
                  tooth +
                  '<span class="remove-tooth" onclick="removeTooth(\'' + currentToothField + '\', \'' + tooth + '\')">&times;</span>' +
                  '</span>';
        $(tagContainer).find('.add-teeth-btn').before(tag);
    });

    updateMiniChartHighlights();
}
</script>
