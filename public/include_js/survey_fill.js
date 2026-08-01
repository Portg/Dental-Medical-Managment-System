/**
 * 患者满意度问卷 — 公开填写页
 *
 * 依赖 window.SURVEY_CONFIG（由 fill.blade.php 注入）。
 * 本页面对未登录患者开放，不引用 LanguageManager —— 后台的翻译包
 * 需要登录态才加载，这里的文案统一由 Blade 侧注入到 SURVEY_CONFIG。
 */
(function ($) {
    'use strict';

    var cfg = window.SURVEY_CONFIG || {};
    var messages = cfg.messages || {};
    var starHints = cfg.starHints || {};

    // ── 星级评分 ────────────────────────────────────────────────────
    $('.star-group').each(function () {
        var $group = $(this);
        var $input = $group.siblings('input[type=hidden]');
        var $hint = $group.find('.star-hint');

        $group.on('click', '.star', function () {
            var value = parseInt($(this).data('value'), 10);
            $input.val(value);

            $group.find('.star').each(function () {
                $(this).toggleClass('is-on', parseInt($(this).data('value'), 10) <= value);
            });

            $hint.text(starHints[value] || '');
        });
    });

    // ── NPS 0-10 ────────────────────────────────────────────────────
    $('.nps-group').each(function () {
        var $group = $(this);
        var $input = $group.siblings('input[type=hidden]');

        $group.on('click', '.nps', function () {
            var value = $(this).data('value');
            $input.val(value);
            $group.find('.nps').removeClass('is-on');
            $(this).addClass('is-on');
        });
    });

    // ── 提交 ────────────────────────────────────────────────────────
    var $form = $('#surveyForm');
    var $btn = $('#submitBtn');
    var $error = $('#surveyError');

    function showError(text) {
        $error.text(text).show();
        $('html, body').animate({ scrollTop: $error.offset().top - 80 }, 200);
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        $error.hide();

        // 总体评分是唯一必填项，前端先挡一次，避免白跑一趟请求
        if (!$form.find('input[name=overall_rating]').val()) {
            showError(messages.ratingRequired);
            return;
        }

        $btn.prop('disabled', true).text(messages.submitting);

        $.ajax({
            url: cfg.submitUrl,
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function (res) {
            if (res && res.status === 1) {
                $form.hide();
                $('#surveyDone').show();
                $('html, body').animate({ scrollTop: 0 }, 200);
            } else {
                showError((res && res.message) || messages.networkError);
                $btn.prop('disabled', false).text(messages.submit);
            }
        }).fail(function (xhr) {
            var msg = messages.networkError;
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showError(msg);
            $btn.prop('disabled', false).text(messages.submit);
        });
    });

})(jQuery);
