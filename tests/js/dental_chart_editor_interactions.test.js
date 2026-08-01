/**
 * Dental chart: tool → paint interaction tests
 * Run: node tests/js/dental_chart_editor_interactions.test.js
 */
'use strict';

var fs = require('fs');
var path = require('path');
var vm = require('vm');

var fake$ = function () {
    var api = {
        length: 0,
        on: function () { return api; },
        each: function () { return api; },
        toggleClass: function () { return api; },
        addClass: function () { return api; },
        removeClass: function () { return api; },
        filter: function () { return api; },
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

var sandbox = { window: {}, jQuery: fake$, $: fake$, console: console, setTimeout: setTimeout };
sandbox.window = sandbox;
vm.runInNewContext(
    fs.readFileSync(path.join(__dirname, '../../public/include_js/dental_chart_editor.js'), 'utf8'),
    sandbox
);

var api = sandbox.window.__dceTestApi;
var failed = 0;
function assert(cond, msg) {
    if (!cond) { failed++; console.error('FAIL:', msg); }
    else console.log('OK:', msg);
}
function eq(a, b) { return JSON.stringify(a) === JSON.stringify(b); }

api.reset();
assert(api.getState().summaryKind === 'empty', 'idle → 尚未标记');
assert(api.getState().tool === null, 'idle → no tool');

// paint without tool: no change
api.paint('12');
assert(eq(api.getState().marks, {}), 'no tool → paint ignored');

// pick crown → paint 12,21
api.setTool('crown');
assert(api.getState().tool === 'crown', 'tool = crown');
api.paint('12');
api.paint('21');
assert(eq(api.getState().marks, { '12': 'crown', '21': 'crown' }), 'paint two crowns');
assert(api.getState().summaryKind === 'marked', 'summary marked');

// same tool on same tooth toggles off
api.paint('12');
assert(eq(api.getState().marks, { '21': 'crown' }), 'repaint same status → unmark');

// switch tool to caries on 21
api.setTool('caries');
api.paint('21');
assert(eq(api.getState().marks, { '21': 'caries' }), 'switch status on tooth');

// clear tool
api.setTool('clear');
api.paint('21');
assert(eq(api.getState().marks, {}), 'clear tool erases mark');
assert(api.getState().summaryKind === 'empty', 'after clear → empty');

// chip × 
api.setTool('rct');
api.paint('16');
api.unmark('16');
assert(eq(api.getState().marks, {}), 'summary × unmarks');

// toggle tool off
api.setTool('crown');
api.setTool('crown');
assert(api.getState().tool === null, 'click same tool again → deselect tool');

if (failed) { console.error('\n' + failed + ' failed'); process.exit(1); }
console.log('\nAll interaction checks passed');
