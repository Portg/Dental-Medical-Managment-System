/**
 * Interaction matrix for dental chart editor state linkage.
 * Run: node tests/js/dental_chart_editor_interactions.test.js
 */
'use strict';

var fs = require('fs');
var path = require('path');
var vm = require('vm');

var handlers = {};
var fake$ = function () {
    var api = {
        length: 0,
        on: function (evt, sel, fn) {
            if (typeof sel === 'function') { fn = sel; sel = evt; }
            handlers[sel] = fn;
            return api;
        },
        each: function () { return api; },
        toggleClass: function () { return api; },
        addClass: function () { return api; },
        removeClass: function () { return api; },
        hasClass: function () { return false; },
        css: function () { return api; },
        find: function () { return api; },
        text: function () { return api; },
        html: function () { return api; },
        attr: function () { return ''; },
        val: function () { return '1'; },
        data: function () { return null; }
    };
    return api;
};
fake$.getJSON = function () { return { fail: function () {} }; };
fake$.ajax = function () {};

var sandbox = {
    window: {},
    jQuery: fake$,
    $: fake$,
    console: console,
    setTimeout: setTimeout
};
sandbox.window = sandbox;

var code = fs.readFileSync(
    path.join(__dirname, '../../public/include_js/dental_chart_editor.js'),
    'utf8'
);
vm.runInNewContext(code, sandbox);

var api = sandbox.window.__dceTestApi;
if (!api) {
    console.error('FAIL: __dceTestApi not exported');
    process.exit(1);
}

var failed = 0;
function assert(cond, msg) {
    if (!cond) {
        failed++;
        console.error('FAIL:', msg);
    } else {
        console.log('OK:', msg);
    }
}

function eq(a, b) {
    return JSON.stringify(a) === JSON.stringify(b);
}

// 1) Idle
api.reset();
assert(api.getState().summaryKind === 'empty', 'idle → summary empty (尚未标记)');

// 2) Select only — not "尚未标记", awaiting status
api.toggleTooth('12');
assert(eq(api.getState().selected, ['12']), 'select 12');
assert(api.getState().summaryKind === 'awaiting', 'selected unmarked → awaiting, not empty');
assert(eq(api.getState().marks, {}), 'select does not create marks');

// 3) Deselect — back to empty, still no marks
api.toggleTooth('12');
assert(eq(api.getState().selected, []), 'deselect 12');
assert(api.getState().summaryKind === 'empty', 'deselect with no marks → empty');
assert(eq(api.getState().marks, {}), 'deselect does not invent marks');

// 4) Select → apply → marks stay, selection clears
api.toggleTooth('12');
api.applyStatus('crown');
assert(eq(api.getState().marks, { '12': 'crown' }), 'apply crown marks 12');
assert(eq(api.getState().selected, []), 'apply clears selection');
assert(api.getState().summaryKind === 'marked', 'after apply → marked summary');

// 5) Select/deselect after mark — marks MUST remain
api.toggleTooth('12');
assert(eq(api.getState().selected, ['12']), 'reselect marked tooth');
assert(eq(api.getState().marks, { '12': 'crown' }), 'reselect keeps mark');
api.toggleTooth('12');
assert(eq(api.getState().selected, []), 'deselect marked tooth');
assert(eq(api.getState().marks, { '12': 'crown' }), 'deselect does NOT unmark');
assert(api.getState().summaryKind === 'marked', 'deselect keeps marked summary');

// 6) Multi select → apply
api.reset();
api.toggleTooth('11');
api.toggleTooth('21');
api.applyStatus('caries');
assert(eq(api.getState().marks, { '11': 'caries', '21': 'caries' }), 'multi apply');
assert(api.getState().summaryKind === 'marked', 'multi apply → marked');

// 7) Clear via selection
api.toggleTooth('11');
api.clearOrToggleErase();
assert(eq(api.getState().marks, { '21': 'caries' }), 'clear selection only clears those marks');
assert(eq(api.getState().selected, []), 'clear selection empties selection');
assert(api.getState().summaryKind === 'marked', 'remaining marks still marked');

// 8) Chip × unmark
api.unmark('21');
assert(eq(api.getState().marks, {}), 'unmark last → no marks');
assert(api.getState().summaryKind === 'empty', 'unmark last → empty');

// 9) Erase mode
api.reset();
api.toggleTooth('16');
api.applyStatus('rct');
api.clearOrToggleErase(); // no selection → erase on
assert(api.getState().eraseMode === true, 'erase mode on');
api.toggleTooth('16'); // erase click
assert(eq(api.getState().marks, {}), 'erase mode click unmarks');
assert(api.getState().summaryKind === 'empty', 'after erase → empty');

// 10) Change status on already-marked tooth
api.reset();
api.toggleTooth('12');
api.applyStatus('crown');
api.toggleTooth('12');
api.applyStatus('caries');
assert(eq(api.getState().marks, { '12': 'caries' }), 're-apply changes status');
assert(api.getState().summaryKind === 'marked', 'status change stays marked');

if (failed) {
    console.error('\n' + failed + ' failed');
    process.exit(1);
}
console.log('\nAll interaction checks passed');
