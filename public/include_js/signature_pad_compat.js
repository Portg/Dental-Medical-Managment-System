/**
 * Compatibility helpers for SignaturePad v4 on legacy Windows browsers.
 *
 * Some Win7 Chromium/WebView builds expose window.PointerEvent but still send
 * mouse events for the actual device. SignaturePad v4 then subscribes only to
 * pointer events, so the canvas opens normally but never receives a stroke.
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.SignaturePadCompat = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function ensureCustomEvent(root, doc) {
        try {
            if (typeof root.CustomEvent === 'function') {
                new root.CustomEvent('signature-pad-compat-test');
                return;
            }
        } catch (ignore) {}

        function CustomEvent(event, params) {
            var options = params || { bubbles: false, cancelable: false, detail: null };
            var customEvent = doc.createEvent('CustomEvent');
            customEvent.initCustomEvent(
                event,
                !!options.bubbles,
                !!options.cancelable,
                options.detail || null
            );
            return customEvent;
        }

        CustomEvent.prototype = root.Event ? root.Event.prototype : {};
        root.CustomEvent = CustomEvent;
    }

    function isLegacyWindows(userAgent) {
        return /Windows NT 6\.[01](?:[;\)\s]|$)/.test(userAgent || '');
    }

    function preventDefault(event) {
        if (event && event.cancelable !== false && event.preventDefault) {
            event.preventDefault();
        }
    }

    function bindLegacyInput(signaturePad, canvas) {
        var doc = canvas.ownerDocument || document;
        var drawing = false;
        var ignoreMouseUntil = 0;

        // Remove SignaturePad's pointer-only binding before installing the
        // mouse/touch fallback. Its drawing methods are retained deliberately.
        signaturePad.off();
        canvas.style.touchAction = 'none';
        canvas.style.msTouchAction = 'none';
        canvas.style.userSelect = 'none';

        function begin(point, originalEvent) {
            preventDefault(originalEvent);
            drawing = true;
            signaturePad._strokeBegin(point);
        }

        function move(point, originalEvent) {
            if (!drawing) return;
            preventDefault(originalEvent);
            signaturePad._strokeMoveUpdate(point);
        }

        function end(point, originalEvent) {
            if (!drawing) return;
            preventDefault(originalEvent);
            drawing = false;
            signaturePad._strokeEnd(point);
        }

        function mouseDown(event) {
            if (Date.now() < ignoreMouseUntil) return;
            if (event.button !== 0 && event.which !== 1) return;
            begin(event, event);
        }

        function mouseMove(event) {
            if (Date.now() < ignoreMouseUntil) return;
            move(event, event);
        }

        function mouseUp(event) {
            if (Date.now() < ignoreMouseUntil) return;
            end(event, event);
        }

        function firstTouch(event) {
            var touches = event.targetTouches && event.targetTouches.length
                ? event.targetTouches
                : event.changedTouches;
            return touches && touches.length ? touches[0] : null;
        }

        function touchStart(event) {
            var point = firstTouch(event);
            if (!point) return;
            ignoreMouseUntil = Date.now() + 800;
            begin(point, event);
        }

        function touchMove(event) {
            var point = firstTouch(event);
            if (!point) return;
            move(point, event);
        }

        function touchEnd(event) {
            var point = firstTouch(event);
            if (!point) return;
            ignoreMouseUntil = Date.now() + 800;
            end(point, event);
        }

        canvas.addEventListener('mousedown', mouseDown, false);
        canvas.addEventListener('mousemove', mouseMove, false);
        doc.addEventListener('mouseup', mouseUp, false);
        canvas.addEventListener('touchstart', touchStart, false);
        canvas.addEventListener('touchmove', touchMove, false);
        canvas.addEventListener('touchend', touchEnd, false);
    }

    function resizeCanvas(canvas, signaturePad, root) {
        var rect = canvas.getBoundingClientRect();
        var logicalWidth = Math.round(rect.width || canvas.offsetWidth || 460);
        var logicalHeight = Math.round(rect.height || canvas.offsetHeight || 200);
        var ratio = Math.max(root.devicePixelRatio || 1, 1);

        // Lock the CSS size before changing the backing store. Without this,
        // width/height attributes also enlarge the visible canvas on HiDPI PCs.
        canvas.style.width = logicalWidth + 'px';
        canvas.style.height = logicalHeight + 'px';
        canvas.width = Math.round(logicalWidth * ratio);
        canvas.height = Math.round(logicalHeight * ratio);
        canvas.getContext('2d').scale(ratio, ratio);
        signaturePad.clear();
    }

    return {
        ensureCustomEvent: ensureCustomEvent,
        isLegacyWindows: isLegacyWindows,
        bindLegacyInput: bindLegacyInput,
        resizeCanvas: resizeCanvas
    };
}));
