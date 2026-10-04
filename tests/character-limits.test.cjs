// Run with: node tests/character-limits.test.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const listeners = {};
const alerts = [];
let observe;
function element(limit, value = '', tagName = 'TEXTAREA') {
    const classes = new Set();
    return {
        nodeType: 1, tagName, dataset: { characterLimit: limit }, value, attributes: {},
        classList: {
            contains: name => classes.has(name),
            add: name => classes.add(name), remove: name => classes.delete(name),
            toggle: (name, active) => active ? classes.add(name) : classes.delete(name)
        },
        matches: selector => selector.startsWith('input['),
        querySelectorAll: () => [],
        setAttribute(name, value) { this.attributes[name] = value; },
        getAttribute(name) { return this.attributes[name]; },
        removeAttribute(name) { delete this.attributes[name]; },
        after(node) { this.hint = node; node.previousElementSibling = this; },
        focus() { this.focused = true; }, scrollIntoView() {}
    };
}
const field = element('3');
const serverInvalid = element('3');
serverInvalid.classList.add('is-invalid');
const unlimited = element('none', 'Teks panjang');
const fields = [field, serverInvalid, unlimited];
const document = {
    body: {}, querySelectorAll: () => fields,
    createElement: () => element('none'),
    addEventListener(name, listener) { listeners[name] = listener; }
};
const context = {
    document, setTimeout: callback => callback(),
    MutationObserver: class { constructor(callback) { observe = callback; } observe() {} },
    Swal: { fire(options) { alerts.push(options); return Promise.resolve(); } }
};
context.window = context;
vm.runInNewContext(fs.readFileSync('public/js/character-limits.js', 'utf8'), context);
listeners.DOMContentLoaded();
assert.match(field.hint.textContent, /0 \/ 3 karakter/);
const hint = field.hint;
observe([{ addedNodes: [field] }]);
assert.equal(field.hint, hint, 'Reinitialization must not duplicate counters');
assert.match(unlimited.hint.textContent, /Batas karakter belum ditetapkan/);

function input(field, value) {
    field.value = value;
    listeners.input({ target: field });
}
input(field, '😀ab');
assert.match(field.hint.textContent, /3 \/ 3 karakter/);
assert.equal(alerts.length, 0, 'Exactly at the limit is valid, including Unicode');
input(field, '😀abc');
assert.match(field.hint.textContent, /Kurangi 1 karakter/);
assert.equal(field.attributes['aria-invalid'], 'true');
assert.equal(alerts.length, 1);
input(field, '😀abcd');
assert.equal(alerts.length, 1, 'Do not repeat the alert on every keystroke');
let prevented = false;
let stopped = false;
const form = { querySelectorAll: () => [field] };
listeners.submit({ target: form, preventDefault() { prevented = true; }, stopImmediatePropagation() { stopped = true; } });
assert.ok(prevented && stopped, 'Block excess input before loading or AJAX handlers');
input(field, 'ab');
assert.equal(field.attributes['aria-invalid'], 'false');
assert.ok(!field.classList.contains('is-invalid'));
input(serverInvalid, 'abcd');
input(serverInvalid, 'ab');
assert.ok(serverInvalid.classList.contains('is-invalid'), 'Preserve existing server errors');
input(field, 'abcd');
field.disabled = true;
listeners.submit({ target: form, preventDefault() { assert.fail('Disabled fields must not block submit'); } });

const dynamic = element('2', 'abc');
observe([{ addedNodes: [dynamic] }]);
assert.match(dynamic.hint.textContent, /Batas 2 karakter terlampaui/);
const rich = element('3', '<p>abc</p>');
rich.dataset.richTextMaxlength = '3';
rich.__pekerjaanRichText = { quill: { getText: () => ' a b \n' } };
observe([{ addedNodes: [rich] }]);
listeners.input({ target: rich });
assert.match(rich.hint.textContent, /3 \/ 3 karakter/, 'Rich-text limits count normalized text, not HTML');
const textInput = element('3', 'ab', 'INPUT');
observe([{ addedNodes: [textInput] }]);
assert.equal(textInput.hint, undefined, 'Ordinary inputs must not display character hints');
assert.equal(textInput.attributes['aria-describedby'], undefined);
input(textInput, 'abcd');
assert.equal(textInput.attributes['aria-invalid'], 'true', 'Input validation must remain active without a hint');
console.log('Character limit checks passed: boundaries, Unicode, warnings, submit guard, dynamic fields, and rich text.');
