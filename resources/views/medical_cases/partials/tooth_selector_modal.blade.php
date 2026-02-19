{{-- Tooth Selector Modal --}}
@php
    // Auto-detect default tab by patient age: ≤12 → deciduous, else permanent
    $modalDefaultTab = 'permanent';
    if (isset($case) && $case->patient) {
        $p = $case->patient;
        $patientAge = null;
        if ($p->date_of_birth) {
            $patientAge = \Carbon\Carbon::parse($p->date_of_birth)->age;
        } elseif ($p->dob) {
            $patientAge = \Carbon\Carbon::parse($p->dob)->age;
        } elseif ($p->age !== null && $p->age !== '') {
            $patientAge = (int) $p->age;
        }
        if ($patientAge !== null && $patientAge <= 12) {
            $modalDefaultTab = 'deciduous';
        }
    }
@endphp
<div class="modal fade modal-form modal-form-lg" id="tooth_selector_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('medical_cases.select_teeth') }}</h4>
            </div>
            <div class="modal-body">
                {{-- CSS: public/css/tooth-selector.css (loaded by parent page) --}}

                {{-- Tab Switcher --}}
                <div class="modal-tooth-tabs">
                    <button type="button" class="modal-tooth-tab {{ $modalDefaultTab === 'permanent' ? 'active' : '' }}" data-target="modal-permanent">
                        {{ __('odontogram.permanent') }}
                    </button>
                    <button type="button" class="modal-tooth-tab {{ $modalDefaultTab === 'deciduous' ? 'active' : '' }}" data-target="modal-deciduous">
                        {{ __('odontogram.decidua') }}
                    </button>
                </div>

                {{-- Permanent Teeth Panel --}}
                <div class="tooth-selector-container modal-tooth-panel" id="modal-permanent" @if($modalDefaultTab === 'deciduous') style="display: none;" @endif>
                    <div class="quadrant-label">{{ __('odontogram.upper_right') }} | {{ __('odontogram.upper_left') }}</div>
                    <div class="tooth-row">
                        @for($i = 18; $i >= 11; $i--)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                        <div class="teeth-separator-vertical"></div>
                        @for($i = 21; $i <= 28; $i++)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                    </div>
                    <div class="teeth-separator"></div>
                    <div class="tooth-row">
                        @for($i = 48; $i >= 41; $i--)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                        <div class="teeth-separator-vertical"></div>
                        @for($i = 31; $i <= 38; $i++)
                            <div class="tooth-cell" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                    </div>
                    <div class="quadrant-label">{{ __('odontogram.lower_right') }} | {{ __('odontogram.lower_left') }}</div>
                </div>

                {{-- Deciduous Teeth Panel --}}
                <div class="tooth-selector-container modal-tooth-panel" id="modal-deciduous" @if($modalDefaultTab === 'permanent') style="display: none;" @endif>
                    <div class="quadrant-label">{{ __('odontogram.upper_right') }} | {{ __('odontogram.upper_left') }}</div>
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
                    <div class="tooth-row">
                        @for($i = 85; $i >= 81; $i--)
                            <div class="tooth-cell deciduous" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                        <div class="teeth-separator-vertical"></div>
                        @for($i = 71; $i <= 75; $i++)
                            <div class="tooth-cell deciduous" data-tooth="{{ $i }}" onclick="toggleTooth({{ $i }})">{{ $i }}</div>
                        @endfor
                    </div>
                    <div class="quadrant-label">{{ __('odontogram.lower_right') }} | {{ __('odontogram.lower_left') }}</div>
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
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="confirmToothSelection()">
                    {{ __('common.confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var selectedTeethInModal = [];
var teethBeforeModal = [];

document.addEventListener('DOMContentLoaded', function() {
    // Modal tab switching
    $(document).on('click', '.modal-tooth-tab', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        $(this).siblings('.modal-tooth-tab').removeClass('active');
        $(this).addClass('active');
        $('.modal-tooth-panel').hide();
        $('#' + target).show();
        // Update tab dot badges
        updateModalTabDots();
    });

    $('#tooth_selector_modal').on('show.bs.modal', function() {
        // Load teeth from the field that opened the modal
        var field = currentToothField || 'examination';
        var inputId = (field === 'related') ? '#related_teeth' : '#examination_teeth';
        var teeth = JSON.parse($(inputId).val() || '[]');
        selectedTeethInModal = teeth.slice();
        teethBeforeModal = teeth.slice(); // snapshot for diff
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
            var isDeciduous = parseInt(t) >= 51;
            var bg = isDeciduous ? '#fffbe6' : '#e6f7ff';
            var border = isDeciduous ? '#ffe58f' : '#91d5ff';
            var color = isDeciduous ? '#d48806' : '#1890ff';
            return '<span class="tooth-tag" style="display: inline-block; margin: 2px; padding: 4px 8px; background: ' + bg + '; border: 1px solid ' + border + '; border-radius: 3px; font-size: 12px; color: ' + color + ';">' + t + '</span>';
        }).join('');
        $display.html(html);
    } else {
        $display.html('<span class="text-muted">{{ __("common.none_selected") }}</span>');
    }

    updateModalTabDots();
}

/**
 * Show dot badge on inactive modal tab if that panel has selections
 */
function updateModalTabDots() {
    var hasPermanent = false;
    var hasDeciduous = false;
    selectedTeethInModal.forEach(function(t) {
        if (parseInt(t) >= 51) {
            hasDeciduous = true;
        } else {
            hasPermanent = true;
        }
    });

    $('.modal-tooth-tab').each(function() {
        var target = $(this).data('target');
        var hasSelection = (target === 'modal-permanent') ? hasPermanent : hasDeciduous;
        if (hasSelection && !$(this).hasClass('active')) {
            if (!$(this).find('.tab-dot').length) {
                $(this).append('<span class="tab-dot"></span>');
            }
        } else {
            $(this).find('.tab-dot').remove();
        }
    });
}

function confirmToothSelection() {
    var field = currentToothField || 'examination';

    // Diff: find added and removed teeth
    var added = selectedTeethInModal.filter(function(t) {
        return teethBeforeModal.indexOf(t) === -1;
    });
    var removed = teethBeforeModal.filter(function(t) {
        return selectedTeethInModal.indexOf(t) === -1;
    });

    // Apply changes through field-aware sync (respects one-way downstream)
    removed.forEach(function(tooth) {
        removeToothFromField(field, tooth);
    });
    added.forEach(function(tooth) {
        addToothToField(field, tooth);
    });

    updateMiniChartHighlights();
}
</script>
