/**
 * Signature pad compatibility regression for legacy Chrome on Windows 7.
 * Run: node tests/js/signature_pad_win7_compat.test.js
 */
'use strict';

var assert = require('assert');
var path = require('path');

function FakeEventTarget() {
    this.listeners = {};
}
FakeEventTarget.prototype.addEventListener = function (type, listener) {
    (this.listeners[type] = this.listeners[type] || []).push(listener);
};
FakeEventTarget.prototype.removeEventListener = function (type, listener) {
    this.listeners[type] = (this.listeners[type] || []).filter(function (item) {
        return item !== listener;
    });
};
FakeEventTarget.prototype.dispatchEvent = function (event) {
    (this.listeners[event.type] || []).slice().forEach(function (listener) {
        listener(event);
    });
    return !event.defaultPrevented;
};

function FakeCustomEvent(type, options) {
    this.type = type;
    this.detail = options && options.detail;
    this.cancelable = !!(options && options.cancelable);
    this.defaultPrevented = false;
}
FakeCustomEvent.prototype.preventDefault = function () {
    if (this.cancelable) this.defaultPrevented = true;
};

function makeInputEvent(type, x, y) {
    return {
        type: type,
        button: 0,
        which: 1,
        // Old Chrome/embedded kernels may omit `buttons` even during a drag.
        buttons: undefined,
        clientX: x,
        clientY: y,
        pressure: 0.5,
        cancelable: true,
        preventDefault: function () { this.defaultPrevented = true; }
    };
}

var context = {
    clearRect: function () {},
    fillRect: function () {},
    beginPath: function () {},
    moveTo: function () {},
    arc: function () {},
    closePath: function () {},
    fill: function () {},
    scale: function (x, y) { this.lastScale = [x, y]; },
    fillStyle: '',
    globalCompositeOperation: ''
};

var documentTarget = new FakeEventTarget();
var canvas = new FakeEventTarget();
canvas.ownerDocument = documentTarget;
canvas.style = {};
canvas.width = 460;
canvas.height = 200;
canvas.offsetWidth = 460;
canvas.offsetHeight = 200;
canvas.getContext = function () { return context; };
canvas.getBoundingClientRect = function () {
    return { left: 0, top: 0, width: 460, height: 200 };
};

global.EventTarget = FakeEventTarget;
global.CustomEvent = FakeCustomEvent;
global.document = documentTarget;
Object.defineProperty(global, 'navigator', {
    configurable: true,
    value: {
        userAgent: 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 Chrome/49.0'
    }
});
global.window = {
    PointerEvent: function PointerEvent() {},
    devicePixelRatio: 1.5,
    setTimeout: setTimeout,
    clearTimeout: clearTimeout
};

var SignaturePad = require(path.join(__dirname, '../../public/include_js/signature_pad.umd.min.js'));
var compat = require(path.join(__dirname, '../../public/include_js/signature_pad_compat.js'));
var pad = new SignaturePad(canvas, { throttle: 0 });

// SignaturePad v4 chooses PointerEvent exclusively when the symbol exists.
// A partially implemented legacy kernel can then deliver mouse events that are ignored.
canvas.dispatchEvent(makeInputEvent('mousedown', 10, 10));
canvas.dispatchEvent(makeInputEvent('mousemove', 40, 40));
documentTarget.dispatchEvent(makeInputEvent('mouseup', 40, 40));
assert.strictEqual(pad.isEmpty(), true, 'unfixed pointer-only binding must reproduce the Win7 failure');

assert.strictEqual(compat.isLegacyWindows(global.navigator.userAgent), true);
assert.strictEqual(compat.isLegacyWindows('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'), false);

compat.bindLegacyInput(pad, canvas);
canvas.dispatchEvent(makeInputEvent('mousedown', 10, 10));
canvas.dispatchEvent(makeInputEvent('mousemove', 40, 40));
documentTarget.dispatchEvent(makeInputEvent('mouseup', 40, 40));
assert.strictEqual(pad.isEmpty(), false, 'legacy mouse drag must produce a signature');

compat.resizeCanvas(canvas, pad, global.window);
assert.strictEqual(canvas.style.width, '460px', 'logical canvas width must remain stable');
assert.strictEqual(canvas.style.height, '200px', 'logical canvas height must remain stable');
assert.strictEqual(canvas.width, 690, 'backing width must account for devicePixelRatio');
assert.strictEqual(canvas.height, 300, 'backing height must account for devicePixelRatio');
assert.deepStrictEqual(context.lastScale, [1.5, 1.5]);

console.log('All Win7 signature compatibility checks passed');
