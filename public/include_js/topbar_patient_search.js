/**
 * 顶栏全局患者搜索（信息卡下拉）
 * =================================
 * 对齐轻松牙医视频：下拉是「信息速览」不是办事按钮栏。
 * 任意页：输入姓名/首拼/手机/病历号 → AJAX 信息卡（识别 + 上次就诊/欠费）；
 * 点卡/Enter → 详情或工作台抽屉；无结果或「新增患者」→ 建档。
 * 挂号/写病历/收费等办事入口在工作台「就诊流程」列，不在这里自创一排按钮。
 *
 * Depends: jQuery, LanguageManager, csrf meta
 */
(function (window, $) {
    'use strict';

    var MIN_CHARS = 1;
    var DEBOUNCE_MS = 280;
    var SEARCH_URL = '/search-patient';
    var timer = null;
    var lastQuery = '';
    var activeIndex = -1;
    var $wrap, $input, $dropdown, $form;

    function t(key, fallback) {
        if (typeof LanguageManager !== 'undefined' && LanguageManager.trans) {
            var v = LanguageManager.trans('patient.' + key);
            if (v && v !== 'patient.' + key) return v;
        }
        return fallback || key;
    }

    function esc(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function displayName(item) {
        if (item.full_name) return item.full_name;
        if (typeof LanguageManager !== 'undefined' && LanguageManager.joinName) {
            return LanguageManager.joinName(item.surname, item.othername) || '';
        }
        return [item.surname, item.othername].filter(Boolean).join('') || '';
    }

    function canCreate() {
        return $wrap && $wrap.data('can-create') === 1;
    }

    function formatMoney(amount) {
        var n = parseFloat(amount);
        if (isNaN(n)) return '0.00';
        return n.toFixed(2);
    }

    function selectableItems() {
        if (!$dropdown) return $();
        // 卡片 + 底部建档行，都可被上下键选中
        return $dropdown.find('.tw-topbar-card, .tw-topbar-create');
    }

    function setActive(index) {
        var $items = selectableItems();
        if (!$items.length) {
            activeIndex = -1;
            return;
        }
        if (index < 0) index = $items.length - 1;
        if (index >= $items.length) index = 0;
        activeIndex = index;
        $items.removeClass('is-active');
        var $active = $items.eq(activeIndex).addClass('is-active');
        if ($active.length && $active[0].scrollIntoView) {
            $active[0].scrollIntoView({ block: 'nearest' });
        }
    }

    function activateCurrent() {
        var $items = selectableItems();
        if (!$items.length || activeIndex < 0) return false;
        var $cur = $items.eq(activeIndex);
        if ($cur.hasClass('tw-topbar-create')) {
            openCreate($cur.data('name') || $input.val());
            return true;
        }
        goDetail($cur.data('id'));
        return true;
    }

    function openCreate(prefillName) {
        var name = (prefillName || '').trim();
        var $modal = $('#patients-modal');

        // 有患者建档弹窗时就地打开。不要调用页面上的 createRecord ——
        // 今日工作等页会把它覆写成「新建预约」，会误开预约抽屉。
        if ($modal.length && typeof window.resetPatientFormToCreateMode === 'function') {
            window.resetPatientFormToCreateMode();
            if (typeof window.clearHealthInfo === 'function') {
                window.clearHealthInfo();
            }
            $modal.modal('show');
            if (name) {
                var $full = $modal.find('[name="full_name"], #full_name').first();
                if ($full.length) {
                    $full.val(name).trigger('change');
                } else {
                    var $sur = $modal.find('[name="surname"], #surname').first();
                    if ($sur.length) $sur.val(name).trigger('change');
                }
            }
            hideDropdown();
            return;
        }

        var url = '/patients?new=1';
        if (name) url += '&name=' + encodeURIComponent(name);
        window.location.href = url;
    }

    function hideDropdown() {
        activeIndex = -1;
        if ($dropdown) {
            $dropdown.hide().empty().css({ left: '', right: '', transform: '' });
        }
    }

    function showDropdown(html) {
        $dropdown.html(html).show();
        positionDropdown();
    }

    /**
     * 把下拉卡钳在视口内：优先贴搜索框左边，超出右缘则改贴右边，
     * 仍超出左缘再用 translateX 推进可视区（小分辨率常见）。
     */
    function positionDropdown() {
        if (!$dropdown || !$dropdown.is(':visible')) return;

        var el = $dropdown[0];
        var margin = 8;
        var vw = window.innerWidth || document.documentElement.clientWidth;
        var maxW = Math.min(400, Math.max(240, vw - margin * 2));

        $dropdown.css({
            width: maxW + 'px',
            maxWidth: maxW + 'px',
            left: '0',
            right: 'auto',
            transform: ''
        });

        var rect = el.getBoundingClientRect();

        if (rect.right > vw - margin) {
            $dropdown.css({ left: 'auto', right: '0' });
            rect = el.getBoundingClientRect();
        }
        if (rect.left < margin) {
            $dropdown.css('transform', 'translateX(' + (margin - rect.left) + 'px)');
        }
    }

    function goDetail(id) {
        if (!id) return;
        // 今日工作等页已有右侧患者抽屉时优先打开，避免为了「看一眼」整页跳详情。
        // 其他页没有 openPatientDrawer，仍走 /patients/{id}。
        if (typeof window.openPatientDrawer === 'function'
            && document.getElementById('patient-drawer')) {
            window.openPatientDrawer(id);
            hideDropdown();
            return;
        }
        window.location.href = '/patients/' + id;
    }

    function renderIdentity(item) {
        var name = displayName(item);
        var who = esc(name);
        var bits = [];
        if (item.gender) bits.push(esc(item.gender));
        if (item.age != null && item.age !== '') bits.push(esc(item.age) + t('topbar_age_unit', '岁'));
        var whoMeta = bits.length ? '<span class="tw-topbar-card-who-meta">' + bits.join(' · ') + '</span>' : '';

        var right = item.patient_no
            ? '<span class="tw-topbar-card-no">' + esc(t('topbar_patient_no', '病历号')) + ' ' + esc(item.patient_no) + '</span>'
            : '';

        return '<div class="tw-topbar-card-row tw-topbar-card-identity">'
            + '<div class="tw-topbar-card-who"><span class="tw-topbar-card-name">' + who + '</span>' + whoMeta + '</div>'
            + right
            + '</div>';
    }

    function renderContact(item) {
        var parts = [];
        if (item.phone_masked) {
            parts.push(esc(t('topbar_phone', '手机')) + ' ' + esc(item.phone_masked));
        }
        var tags = Array.isArray(item.tags) ? item.tags : [];
        var tagHtml = tags.map(function (tag) {
            return '<span class="tw-topbar-card-tag">' + esc(tag) + '</span>';
        }).join('');

        if (!parts.length && !tagHtml) return '';

        return '<div class="tw-topbar-card-row tw-topbar-card-contact">'
            + (parts.length ? '<span class="tw-topbar-card-muted">' + parts.join(' · ') + '</span>' : '')
            + (tagHtml ? '<span class="tw-topbar-card-tags">' + tagHtml + '</span>' : '')
            + '</div>';
    }

    function renderContext(item) {
        var lines = [];
        var visit = item.last_visit;
        if (visit && (visit.date || visit.doctor || visit.service)) {
            var visitBits = [esc(t('topbar_last_visit', '上次'))];
            if (visit.date) visitBits.push(esc(visit.date));
            if (visit.doctor) visitBits.push(esc(visit.doctor));
            if (visit.service) visitBits.push(esc(visit.service));
            lines.push('<div class="tw-topbar-card-muted">' + visitBits.join(' · ') + '</div>');
        } else {
            lines.push('<div class="tw-topbar-card-muted">' + esc(t('topbar_no_visit', '暂无就诊记录')) + '</div>');
        }

        if (parseFloat(item.balance_due) > 0) {
            lines.push('<div class="tw-topbar-card-due">'
                + esc(t('topbar_balance_due', '欠费')) + ' ¥' + esc(formatMoney(item.balance_due))
                + '</div>');
        }

        if (item.member_balance != null && parseFloat(item.member_balance) > 0) {
            lines.push('<div class="tw-topbar-card-muted">'
                + esc(t('topbar_member_balance', '储值')) + ' ¥' + esc(formatMoney(item.member_balance))
                + '</div>');
        }

        return '<div class="tw-topbar-card-context">' + lines.join('') + '</div>';
    }

    function renderCard(item) {
        return '<div class="tw-topbar-card" data-id="' + esc(item.id) + '" tabindex="0">'
            + '<div class="tw-topbar-card-body">'
            + renderIdentity(item)
            + renderContact(item)
            + renderContext(item)
            + '</div>'
            + '</div>';
    }

    function renderResults(query, items) {
        var html = '';
        if (items.length) {
            items.forEach(function (item) {
                html += renderCard(item);
            });
        } else {
            html += '<div class="tw-topbar-empty">' + t('topbar_no_match', '未找到匹配患者') + '</div>';
        }

        if (canCreate()) {
            var label = items.length
                ? t('add_new_patient', '添加新患者')
                : (t('topbar_create_named', '新建患者') + (query ? '「' + esc(query) + '」' : ''));
            html += '<a href="javascript:;" class="tw-topbar-create" data-name="' + esc(query) + '">'
                + '<i class="fa fa-plus"></i> ' + label
                + '</a>';
        }

        showDropdown(html);
        // 默认高亮第一张卡（或无结果时的建档行），方便直接 Enter
        if (selectableItems().length) {
            setActive(0);
        } else {
            activeIndex = -1;
        }
    }

    function search(query) {
        lastQuery = query;
        $.getJSON(SEARCH_URL, { q: query, card: 1 })
            .done(function (data) {
                if (query !== lastQuery) return;
                var list = Array.isArray(data) ? data : [];
                renderResults(query, list);
            })
            .fail(function () {
                if (query !== lastQuery) return;
                showDropdown('<div class="tw-topbar-empty">' + t('topbar_search_failed', '搜索失败，请重试') + '</div>');
            });
    }

    function onInput() {
        var q = ($input.val() || '').trim();
        clearTimeout(timer);
        if (q.length < MIN_CHARS) {
            hideDropdown();
            return;
        }
        timer = setTimeout(function () { search(q); }, DEBOUNCE_MS);
    }

    function bind() {
        $wrap = $('.tw-topbar-search');
        if (!$wrap.length) return;

        $form = $wrap.find('.topbar-search-form');
        $input = $wrap.find('.tw-topbar-search-input');
        $dropdown = $wrap.find('.tw-topbar-search-dropdown');

        if (!$input.length || !$dropdown.length) return;

        $input.on('input', onInput);
        $input.on('focus', function () {
            var q = ($input.val() || '').trim();
            if (q.length >= MIN_CHARS && !$dropdown.is(':visible')) onInput();
        });

        // Enter：激活当前高亮项；无下拉可选时再走列表搜索
        $form.on('submit', function (e) {
            e.preventDefault();
            if ($dropdown.is(':visible') && activateCurrent()) {
                return;
            }
            var q = ($input.val() || '').trim();
            if (q && canCreate() && $dropdown.is(':visible') && $dropdown.find('.tw-topbar-empty').length) {
                openCreate(q);
                return;
            }
            if (q) {
                window.location.href = '/patients?search=' + encodeURIComponent(q);
            }
        });

        $dropdown.on('click', '.tw-topbar-card', function (e) {
            e.preventDefault();
            var $card = $(this);
            setActive(selectableItems().index($card));
            goDetail($card.data('id'));
        });

        $dropdown.on('mouseenter', '.tw-topbar-card, .tw-topbar-create', function () {
            setActive(selectableItems().index(this));
        });

        $dropdown.on('click', '.tw-topbar-create', function (e) {
            e.preventDefault();
            openCreate($(this).data('name') || $input.val());
        });

        $wrap.find('.tw-topbar-new-patient').on('click', function (e) {
            e.preventDefault();
            openCreate(($input.val() || '').trim());
        });

        $(document).on('click.twTopbarSearch', function (e) {
            if (!$(e.target).closest('.tw-topbar-search').length) {
                hideDropdown();
            }
        });

        $input.on('keydown', function (e) {
            var key = e.key || e.keyCode;
            var open = $dropdown.is(':visible') && selectableItems().length;

            if (key === 'Escape' || key === 27) {
                hideDropdown();
                return;
            }

            if (!open) return;

            if (key === 'ArrowDown' || key === 40) {
                e.preventDefault();
                setActive(activeIndex < 0 ? 0 : activeIndex + 1);
                return;
            }
            if (key === 'ArrowUp' || key === 38) {
                e.preventDefault();
                setActive(activeIndex < 0 ? selectableItems().length - 1 : activeIndex - 1);
                return;
            }
            // Enter 由 form submit 处理，这里只挡一下避免个别浏览器双触发
            if (key === 'Enter' || key === 13) {
                e.preventDefault();
                activateCurrent();
            }
        });

        $(window).on('resize.twTopbarSearch', function () {
            if ($dropdown && $dropdown.is(':visible')) {
                positionDropdown();
            }
        });
    }

    $(bind);

})(window, jQuery);
