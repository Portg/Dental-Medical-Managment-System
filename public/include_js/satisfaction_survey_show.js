/**
 * 满意度调查详情页 — 填写链接的复制与重新生成。
 *
 * 短信通道未接入，「重新发送」在这里的实际语义是：换发一条新的填写链接，
 * 由前台复制后经微信等渠道人工发给患者。界面文案与实际行为保持一致，
 * 不出现「已发送」这类做不到的提示。
 */
$(document).ready(function () {
    'use strict';

    var $url = $('#fillUrl');

    function copyToClipboard(text) {
        // navigator.clipboard 需要 HTTPS 或 localhost；诊所内网多为 http，
        // 因此保留 execCommand 回退路径。
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            try {
                $url.select();
                $url[0].setSelectionRange(0, 99999); // iOS
                document.execCommand('copy') ? resolve() : reject();
            } catch (e) {
                reject(e);
            }
        });
    }

    $('#copyLinkBtn').on('click', function () {
        var text = $url.val();
        if (!text) {
            toastr.warning(LanguageManager.trans('satisfaction.no_link_yet'));
            return;
        }

        copyToClipboard(text).then(function () {
            toastr.success(LanguageManager.trans('satisfaction.link_copied'));
        }).catch(function () {
            toastr.warning(LanguageManager.trans('satisfaction.copy_failed'));
        });
    });

    $('#resendBtn').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json'
        }).done(function (res) {
            if (res && res.status === 1) {
                $url.val(res.fill_url);
                if (res.expires_at) {
                    $('#expiresAt').text(
                        LanguageManager.trans('satisfaction.link_expires_at', { time: res.expires_at })
                    );
                }
                toastr.success(res.message);
            } else {
                toastr.error((res && res.message) || LanguageManager.trans('satisfaction.network_error'));
            }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                || LanguageManager.trans('satisfaction.network_error');
            toastr.error(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
