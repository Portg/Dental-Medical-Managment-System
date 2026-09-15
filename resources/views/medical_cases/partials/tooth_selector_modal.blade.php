{{-- Tooth Selector Modal --}}
@php
    // Auto-detect default tab by patient age: ≤12 → deciduous, else permanent
    $modalDefaultTab = 'permanent';
    if (isset($case) && $case->patient) {
        $p = $case->patient;
        $patientAge = null;
        if ($p->date_of_birth) {
            $patientAge = \Carbon\Carbon::parse($p->date_of_birth)->age;
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

                {{-- 恒牙与乳牙同屏，不再分 tab。

                     分 tab 不是慢一点的问题，是写不出来：6-12 岁替牙期一张嘴里
                     两种牙并存，「55 乳牙滞留，15 阻生」这条最常见的替牙期记录
                     必须中途切 tab；而原先「按年龄自动选 tab（≤12 岁默认乳牙）」
                     的贴心设计，恰好在替牙期把人送到错误的那一面。 --}}
                @include('medical_cases.partials.tooth_grid', [
                    'idPrefix' => 'modal-tooth-grid',
                    'compact'  => false,
                    'onclick'  => 'toggleTooth',
                ])

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
    // 「全」：整象限一键选 / 再点取消。整区记录（某区牙周治疗、半口洁治）
    // 原来要一颗一颗点 8 下。
    $(document).on('click', '#modal-tooth-grid .tg-all', function () {
        var teeth = String($(this).data('teeth')).split(',');
        var allOn = teeth.every(function (t) { return selectedTeethInModal.indexOf(t) !== -1; });

        teeth.forEach(function (t) {
            var i = selectedTeethInModal.indexOf(t);
            if (allOn) { if (i !== -1) selectedTeethInModal.splice(i, 1); }
            else if (i === -1) { selectedTeethInModal.push(t); }
        });
        updateToothSelectorUI();
    });

    $('#tooth_selector_modal').on('show.bs.modal', function() {
        // 分行录入下，牙位属于**某一行**而不是整段：预选当前聚焦行的那一颗。
        // 旧结构里牙位是整段共享的标签集合，所以这里读的是段落的隐藏 json。
        var teeth = [];
        if (typeof CaseItems !== 'undefined' && CaseItems.getFocusedRow()) {
            // 一行可能有多颗，全部预选上
            teeth = CaseItems.splitTeeth(CaseItems.getFocusedRow().find('.case-item-tooth-value').val());
        } else {
            var field = currentToothField || 'examination';
            var inputId = (field === 'related') ? '#related_teeth' : '#examination_teeth';
            teeth = JSON.parse($(inputId).val() || '[]');
        }
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
    $('#tooth_selector_modal .tg-t').each(function() {
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

}

/**
 * Show dot badge on inactive modal tab if that panel has selections
 */

function confirmToothSelection() {
    var field = currentToothField || 'examination';

    // 分行录入：一行一个牙位。选了多颗时第一颗给当前行、其余各新建一行 ——
    // 医生常要给几颗牙各写一条，这样比强制单选顺手，也不违反「一行一牙」。
    if (typeof CaseItems !== 'undefined' && $('.case-items-section').length) {
        var picked = selectedTeethInModal.slice();
        var $row = CaseItems.getFocusedRow();

        // 一行可以带多颗：选中的牙位整体写进这一行，同象限的会合并在一格里
        // （16、17 → 右上格「76」）。原来是第一颗给当前行、其余各建一行，
        // 同一个区的牙硬拆成两行不符合部位记录法的写法。
        if ($row) {
            CaseItems.setRowTooth($row, picked);
        } else if (picked.length) {
            CaseItems.addRow(field, picked.join(','), '', false);
        }

        updateMiniChartHighlights();
        return;
    }

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
