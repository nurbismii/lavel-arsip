<script src="{{ asset('vendor/quill/quill.js') }}"></script>

<script>
    (function() {
        'use strict';

        const toolbarOptions = [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline'],
            ['blockquote'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link'],
            ['clean'],
        ];

        function containsHtml(value) {
            return /<\/?[a-z][\s\S]*>/i.test(value || '');
        }

        function associatedLabel(textarea) {
            if (!textarea.id) {
                return null;
            }

            return document.querySelector(`label[for="${CSS.escape(textarea.id)}"]`);
        }

        function plainText(instance) {
            return instance.quill.getText().replace(/\s+/g, ' ').trim();
        }

        function editorHtml(instance) {
            if (!plainText(instance)) {
                return '';
            }

            if (typeof instance.quill.getSemanticHTML === 'function') {
                return instance.quill.getSemanticHTML();
            }

            return instance.quill.root.innerHTML;
        }

        function syncTextarea(textarea) {
            const instance = textarea.__pekerjaanRichText;

            if (instance) {
                textarea.value = editorHtml(instance);
            }
        }

        function validationMessage(textarea, textLength) {
            const required = textarea.dataset.richTextRequired === 'true';
            const maxLength = Number.parseInt(textarea.dataset.richTextMaxlength || '', 10);

            if (required && textLength === 0) {
                return 'Keterangan penyelesaian wajib diisi saat status dokumen sudah selesai.';
            }

            if (Number.isFinite(maxLength) && maxLength > 0 && textLength > maxLength) {
                return `Isi editor maksimal ${maxLength.toLocaleString('id-ID')} karakter.`;
            }

            return '';
        }

        function validateTextarea(textarea, shouldFocus) {
            const instance = textarea.__pekerjaanRichText;

            if (!instance) {
                return textarea.checkValidity();
            }

            syncTextarea(textarea);

            const message = validationMessage(textarea, plainText(instance).length);
            const isValid = message === '';

            instance.wrapper.classList.toggle('is-invalid', !isValid);
            instance.editor.setAttribute('aria-invalid', isValid ? 'false' : 'true');
            instance.error.textContent = message;
            instance.error.classList.toggle('d-none', isValid);

            if (!isValid && shouldFocus) {
                instance.quill.focus();
            }

            return isValid;
        }

        function bindForm(form) {
            if (!form || form.dataset.richTextFormReady === 'true') {
                return;
            }

            form.dataset.richTextFormReady = 'true';
            form.addEventListener('submit', function(event) {
                const textareas = Array.from(form.querySelectorAll('textarea[data-rich-text][data-rich-text-ready="true"]'));
                let firstInvalid = null;

                textareas.forEach(function(textarea) {
                    syncTextarea(textarea);

                    if (!validateTextarea(textarea, false) && !firstInvalid) {
                        firstInvalid = textarea;
                    }
                });

                if (firstInvalid) {
                    event.preventDefault();
                    validateTextarea(firstInvalid, true);
                }
            });
        }

        function initializeTextarea(textarea) {
            if (textarea.__pekerjaanRichText || typeof window.Quill === 'undefined') {
                return;
            }

            if (!textarea.id) {
                textarea.id = `pekerjaan-rich-text-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            }

            if (!textarea.dataset.richTextRequired) {
                textarea.dataset.richTextRequired = textarea.required ? 'true' : 'false';
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'pekerjaan-rich-text';

            if (textarea.dataset.richTextCompact === 'true') {
                wrapper.classList.add('is-compact');
            }

            const editor = document.createElement('div');
            wrapper.appendChild(editor);

            const error = document.createElement('div');
            error.className = 'pekerjaan-rich-text-error d-none';
            error.id = `${textarea.id}-rich-text-error`;
            error.setAttribute('role', 'alert');

            textarea.insertAdjacentElement('afterend', wrapper);
            wrapper.insertAdjacentElement('afterend', error);

            const quill = new Quill(editor, {
                theme: 'snow',
                modules: { toolbar: toolbarOptions },
                placeholder: textarea.getAttribute('placeholder') || '',
            });

            const initialValue = textarea.value.trim();

            if (initialValue) {
                if (containsHtml(initialValue)) {
                    quill.clipboard.dangerouslyPasteHTML(initialValue, 'silent');
                } else {
                    quill.setText(initialValue, 'silent');
                }
            }

            const label = associatedLabel(textarea);
            quill.root.setAttribute('aria-label', label ? label.textContent.trim() : 'Editor teks');
            quill.root.setAttribute('aria-describedby', [
                textarea.getAttribute('aria-describedby'),
                error.id,
            ].filter(Boolean).join(' '));
            quill.root.setAttribute('aria-invalid', 'false');

            textarea.__pekerjaanRichText = {
                editor: editor,
                error: error,
                quill: quill,
                wrapper: wrapper,
            };
            textarea.dataset.richTextReady = 'true';
            textarea.required = false;
            textarea.classList.add('d-none');

            quill.on('text-change', function() {
                syncTextarea(textarea);
                validateTextarea(textarea, false);
            });

            syncTextarea(textarea);
            bindForm(textarea.closest('form'));
        }

        function init(container) {
            const root = container || document;

            if (root.matches && root.matches('textarea[data-rich-text]')) {
                initializeTextarea(root);
            }

            root.querySelectorAll('textarea[data-rich-text]').forEach(initializeTextarea);
        }

        function destroy(container) {
            const root = container || document;
            const textareas = [];

            if (root.matches && root.matches('textarea[data-rich-text]')) {
                textareas.push(root);
            }

            root.querySelectorAll('textarea[data-rich-text]').forEach(function(textarea) {
                textareas.push(textarea);
            });

            textareas.forEach(function(textarea) {
                const instance = textarea.__pekerjaanRichText;

                if (!instance) {
                    return;
                }

                syncTextarea(textarea);
                instance.wrapper.remove();
                instance.error.remove();
                textarea.classList.remove('d-none');
                textarea.required = textarea.dataset.richTextRequired === 'true';
                delete textarea.__pekerjaanRichText;
                delete textarea.dataset.richTextReady;
            });
        }

        window.PekerjaanRichText = {
            destroy: destroy,
            init: init,
            sync: syncTextarea,
            validate: validateTextarea,
        };

        document.addEventListener('DOMContentLoaded', function() {
            init(document);
        });
    })();
</script>
