const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// The Blade controller needs the actual Bootstrap export on window.
const bootstrapExports = { Collapse: {} };
const assetWindow = {};
vm.runInNewContext(fs.readFileSync('resources/js/bootstrap.js', 'utf8'), {
    window: assetWindow,
    require: (name) => name === 'bootstrap' ? bootstrapExports
        : name === 'axios' ? { defaults: { headers: { common: {} } } } : {},
});
assert.equal(assetWindow.bootstrap, bootstrapExports);

const view = fs.readFileSync('resources/views/pekerjaan/index.blade.php', 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace("{{route('login')}}", '/login');

async function check(openAll) {
    const handlers = {};
    const requests = [];
    const visible = [];
    const button = () => ({ innerHTML: 'Buka Semua', addEventListener(type, callback) { this[type] = callback; } });
    const expand = button();
    const collapse = button();
    function folder(name, children = []) {
        return {
            name, children, isConnected: true,
            dataset: { treeUrl: name, treeLoaded: 'false' },
            classList: { contains: (value) => value === 'tree-folder-collapse' },
            querySelector: () => null,
            querySelectorAll(selector) { return selector === '.tree-folder-collapse' && this.dataset.treeLoaded === 'true' ? this.children : []; },
        };
    }
    const child = folder('child');
    const root = folder('root', [child]);
    const document = {
        addEventListener(type, callback) { handlers[type] = callback; },
        getElementById: (id) => id === 'expand-all-tree' ? expand : collapse,
        querySelectorAll: () => [root],
    };
    const bootstrap = { Collapse: { getOrCreateInstance(element) {
        return { show() {
            let prevented = false;
            handlers['show.bs.collapse']({ target: element, preventDefault() { prevented = true; } });
            if (!prevented) {
                // Bootstrap measures content here: the document link must already exist.
                assert.match(element.innerHTML, /<a href=/);
                visible.push(element.name);
            }
        } };
    } } };
    vm.runInNewContext(script, {
        document, bootstrap, setTimeout, window: {},
        fetch: async (url) => {
            requests.push(url);
            await new Promise((resolve) => setTimeout(resolve, 5));
            return { ok: true, status: 200, json: async () => ({ html: '<a href="/dokumen/1/lihat">Dokumen.pdf</a>' }) };
        },
    });
    handlers.DOMContentLoaded();
    if (openAll) expand.click();
    else bootstrap.Collapse.getOrCreateInstance(root).show();
    for (let attempt = 0; attempt < 100 && (openAll ? expand.disabled !== false : visible.length === 0); attempt++) {
        await new Promise((resolve) => setTimeout(resolve, 5));
    }
    assert.deepEqual(visible, openAll ? ['root', 'child'] : ['root']);
    assert.deepEqual(requests, openAll ? ['root', 'child'] : ['root']);
    if (openAll) assert.equal(expand.innerHTML, 'Buka Semua');
}

(async () => {
    await check(true);
    await check(false);
    console.log('PASS: tautan dimuat sebelum folder dibuka, termasuk Buka Semua dan subfolder.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
