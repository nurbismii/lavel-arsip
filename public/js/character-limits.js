(() => {
    'use strict';

    const selector = 'input[data-character-limit], textarea[data-character-limit]';
    const states = new WeakMap();
    let nextId = 0;

    function editorFor(field) {
        const sibling = field.nextElementSibling;
        return sibling && sibling.matches('.pekerjaan-rich-text, .rich-text-field') ? sibling : null;
    }

    function update(field, notify = false) {
        const state = states.get(field);
        if (!state) return false;
        const editor = editorFor(field);
        const editorRoot = editor && editor.querySelector('.ql-editor');
        let value = editorRoot ? editorRoot.innerText : field.value;
        if (field.dataset.richTextMaxlength) {
            const instance = field.__pekerjaanRichText;
            if (instance) value = instance.quill.getText();
            value = value.replace(/\s+/g, ' ').trim();
        }
        const count = Array.from(value.replace(/\r\n/g, '\n')).length;
        const exceeded = state.limit !== null && count > state.limit;
        const message = exceeded
            ? `Batas ${state.limit} karakter terlampaui (${count} karakter). Kurangi ${count - state.limit} karakter sebelum menyimpan.`
            : state.limit !== null
                ? `${count} / ${state.limit} karakter (maksimal ${state.limit} karakter).`
                : `${count} karakter · Batas karakter belum ditetapkan.`;

        if (state.hint) {
            if (state.hint.textContent !== message) state.hint.textContent = message;
            state.hint.classList.toggle('text-danger', exceeded);
            state.hint.classList.toggle('text-muted', !exceeded);
            state.hint.setAttribute('role', exceeded ? 'alert' : 'status');
        }
        if (exceeded) {
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
        } else if (state.exceeded) {
            if (!state.serverInvalid) field.classList.remove('is-invalid');
            field.setAttribute('aria-invalid', state.serverInvalid ? 'true' : 'false');
        }
        if (editor && state.hint && state.hint.previousElementSibling !== editor) editor.after(state.hint);
        if (notify && exceeded && !state.exceeded && window.Swal) {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'warning',
                title: 'Batas karakter terlampaui', text: message,
                timer: 4500, showConfirmButton: false
            });
        }
        state.exceeded = exceeded;
        return exceeded;
    }

    function init(root) {
        const fields = root.matches && root.matches(selector) ? [root] : [];
        if (root.querySelectorAll) fields.push(...root.querySelectorAll(selector));
        fields.forEach(field => {
            if (states.has(field)) return;
            const maximum = Number(field.dataset.characterLimit);
            let hint = null;
            if (field.tagName === 'TEXTAREA') {
                hint = document.createElement('small');
                hint.id = `character-hint-${++nextId}`;
                hint.className = 'character-hint d-block mt-1 small text-muted';
                hint.setAttribute('aria-live', 'polite');
                field.after(hint);
                field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), hint.id].filter(Boolean).join(' '));
            }
            // Allow users to see and correct excess input rather than silently truncate pasted text.
            field.removeAttribute('maxlength');
            states.set(field, {
                hint, limit: maximum > 0 ? maximum : null,
                exceeded: false, serverInvalid: field.classList.contains('is-invalid')
            });
            update(field);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        init(document);
        new MutationObserver(records => {
            records.forEach(record => record.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                init(node);
                if (node.matches('.pekerjaan-rich-text, .rich-text-field')) {
                    const field = node.previousElementSibling;
                    if (field && field.matches(selector)) update(field);
                }
            }));
        }).observe(document.body, { childList: true, subtree: true });
    });

    document.addEventListener('input', event => {
        if (event.target.matches(selector)) update(event.target, !event.isComposing);
    });
    document.addEventListener('compositionend', event => {
        if (event.target.matches(selector)) update(event.target, true);
    });
    document.addEventListener('reset', event => {
        setTimeout(() => event.target.querySelectorAll(selector).forEach(field => update(field)), 0);
    });
    document.addEventListener('submit', event => {
        const fields = Array.from(event.target.querySelectorAll(selector)).filter(field => !field.disabled);
        const invalid = fields.filter(field => update(field));
        if (!invalid.length) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const field = invalid[0];
        const focusField = () => {
            const editor = editorFor(field);
            const target = editor ? editor.querySelector('.ql-editor') : field;
            target.scrollIntoView({ block: 'center', behavior: 'smooth' });
            target.focus({ preventScroll: true });
        };
        if (window.Swal) {
            Swal.fire({
                icon: 'warning', title: 'Periksa batas karakter',
                text: `${invalid.length} field melebihi batas karakter. Kurangi teks pada field yang ditandai sebelum menyimpan.`,
                confirmButtonText: 'Perbaiki isian'
            }).then(focusField);
        } else {
            focusField();
        }
    }, true);
})();
