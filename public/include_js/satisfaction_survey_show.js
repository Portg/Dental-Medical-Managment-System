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

    /**
     * 回填派发时间。
     *
     * 建问卷时不写 sent_at —— 那时只是生成了链接，没人把它发出去。复制成功是这套
     * 人工流程里唯一能观测到的派发动作，所以在这里回填，「已派发/未派发」才有意义。
     * 回填失败不影响复制本身：链接已经在剪贴板里了，没必要弹错吓人，
     * 只把状态留在「未派发」，前台下次复制会再试一次。
     */
    function markDispatched(url, onFailure) {
        if (!url) {
            return;
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            dataType: 'json'
        }).done(function (res) {
            if (res && res.status === 1 && res.sent_at) {
                $('#dispatchStatus').html(
                    $('<span class="text-success">').text(
                        LanguageManager.trans('satisfaction.dispatched_at', { time: res.sent_at })
                    )
                );
                // 记上了，人工确认按钮就没用了（没露出来时是空操作）
                $('#markDispatchedBtn').hide();
                return;
            }

            if (onFailure) {
                onFailure();
            }
        }).fail(function () {
            // 403（只有 view-surveys）、掉线、500 —— 状态没写进去，
            // 调用方据此决定要不要把确认按钮留着让人重试
            if (onFailure) {
                onFailure();
            }
        });
    }

    $('#copyLinkBtn').on('click', function () {
        var url = $(this).data('url');
        var text = $url.val();
        if (!text) {
            toastr.warning(LanguageManager.trans('satisfaction.no_link_yet'));
            return;
        }

        copyToClipboard(text).then(function () {
            // 复制真的成了，才算链接到了前台手上
            toastr.success(LanguageManager.trans('satisfaction.link_copied'));
            markDispatched(url);
        }).catch(function () {
            // 诊所内网多为 http，navigator.clipboard 在非安全上下文不可用，
            // 回退的 execCommand 也可能被拒。这时 copyToClipboard 已经把链接选中，
            // 前台会手动 Ctrl+C —— 但发没发出去只有他知道，系统不替他断言。
            // 露出确认按钮，由人点一下再记派发。
            toastr.warning(LanguageManager.trans('satisfaction.copy_failed'));
            $('#markDispatchedBtn').show();
        });
    });

    // 复制失败后的人工确认：这条路径上「已派发」是前台自己声明的，不是系统猜的。
    // 按钮先禁用不隐藏 —— 请求可能 403/掉线，那时状态并没写进去，
    // 直接隐藏会让人以为记上了，且再没有第二次机会。
    $('#markDispatchedBtn').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);

        markDispatched($btn.data('url'), function () {
            toastr.error(LanguageManager.trans('satisfaction.network_error'));
            $btn.prop('disabled', false);
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
                // 换了新链接，旧的立刻失效——在把新链接发出去之前是「未派发」
                $('#dispatchStatus').html(
                    $('<span class="text-warning">').text(
                        LanguageManager.trans('satisfaction.not_dispatched')
                    )
                );
                // 上一条链接的人工确认按钮跟着作废，别让它把新链接标成已派发
                $('#markDispatchedBtn').hide();
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
