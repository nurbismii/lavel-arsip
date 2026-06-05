@once
    <script src="{{ asset('vendor/quill/quill.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Quill === 'undefined') {
            return;
        }

        const editors = new WeakMap();
        const allowedTags = new Set(['A', 'BLOCKQUOTE', 'BR', 'EM', 'I', 'LI', 'OL', 'P', 'S', 'STRONG', 'U', 'UL']);
        const removeWithContent = new Set(['IFRAME', 'SCRIPT', 'STYLE']);
        const toolbarOptions = [
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'link'],
            ['clean']
        ];

        function isSafeUrl(url) {
            if (!url) {
                return false;
            }

            if (url.charAt(0) === '#' || url.charAt(0) === '/') {
                return true;
            }

            return /^(https?:|mailto:|tel:)/i.test(url);
        }

        function unwrap(node) {
            const parent = node.parentNode;

            if (!parent) {
                return;
            }

            while (node.firstChild) {
                parent.insertBefore(node.firstChild, node);
            }

            parent.removeChild(node);
        }

        function sanitizeNode(node) {
            Array.from(node.childNodes).forEach(function (child) {
                if (child.nodeType === Node.TEXT_NODE) {
                    return;
                }

                if (child.nodeType !== Node.ELEMENT_NODE) {
                    child.remove();
                    return;
                }

                const tagName = child.tagName;

                if (removeWithContent.has(tagName)) {
                    child.remove();
                    return;
                }

                sanitizeNode(child);

                if (!allowedTags.has(tagName)) {
                    unwrap(child);
                    return;
                }

                const href = tagName === 'A' && isSafeUrl(child.getAttribute('href'))
                    ? child.getAttribute('href')
                    : null;

                Array.from(child.attributes).forEach(function (attribute) {
                    child.removeAttribute(attribute.name);
                });

                if (tagName === 'A' && href) {
                    child.setAttribute('href', href);
                    child.setAttribute('target', '_blank');
                    child.setAttribute('rel', 'noopener noreferrer');
                }
            });
        }

        function sanitizeHtml(html) {
            const template = document.createElement('template');

            template.innerHTML = html || '';
            sanitizeNode(template.content);

            return template.innerHTML.trim();
        }

        function hasHtml(value) {
            return /<\/?[a-z][\s\S]*>/i.test(value || '');
        }

        function editorHtml(quill) {
            if (typeof quill.getSemanticHTML === 'function') {
                return quill.getSemanticHTML();
            }

            return quill.root.innerHTML;
        }

        function syncTextarea(textarea) {
            const instance = editors.get(textarea);

            if (!instance) {
                return;
            }

            const plainText = instance.quill.getText().replace(/\s/g, '');
            textarea.value = plainText === '' ? '' : sanitizeHtml(editorHtml(instance.quill));
        }

        function clearTextarea(textarea) {
            const instance = editors.get(textarea);

            if (!instance) {
                textarea.value = '';
                return;
            }

            instance.quill.setText('', 'silent');
            syncTextarea(textarea);
        }

        function isSweetAlertElement(element) {
            return element
                && (element.classList.contains('swal2-container')
                    || element.classList.contains('swal2-popup')
                    || element.closest('.swal2-container, .swal2-popup'));
        }

        function shouldInitTextarea(textarea) {
            return textarea
                && textarea.dataset.richTextReady !== '1'
                && !textarea.classList.contains('swal2-textarea')
                && !isSweetAlertElement(textarea);
        }

        function initTextarea(textarea) {
            if (!shouldInitTextarea(textarea)) {
                return;
            }

            const wrapper = document.createElement('div');
            const editorElement = document.createElement('div');
            const initialValue = textarea.value || '';

            wrapper.className = 'rich-text-field';
            wrapper.classList.toggle('is-invalid', textarea.classList.contains('is-invalid'));
            editorElement.className = 'workflow-rich-editor';

            textarea.classList.add('rich-text-source');
            textarea.setAttribute('aria-hidden', 'true');
            textarea.setAttribute('tabindex', '-1');
            textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);
            wrapper.appendChild(editorElement);

            const quill = new Quill(editorElement, {
                bounds: wrapper,
                modules: {
                    toolbar: toolbarOptions
                },
                placeholder: textarea.getAttribute('placeholder') || 'Tulis detail alur kerja...',
                theme: 'snow'
            });

            if (initialValue.trim() !== '') {
                if (hasHtml(initialValue)) {
                    quill.clipboard.dangerouslyPasteHTML(sanitizeHtml(initialValue), 'silent');
                } else {
                    quill.setText(initialValue, 'silent');
                }
            }

            editors.set(textarea, { quill: quill });
            textarea.dataset.richTextReady = '1';

            quill.on('text-change', function () {
                syncTextarea(textarea);
            });

            syncTextarea(textarea);
        }

        function refresh(root) {
            if (root instanceof Element && isSweetAlertElement(root)) {
                return;
            }

            (root || document).querySelectorAll('textarea:not(.swal2-textarea)').forEach(initTextarea);
        }

        document.addEventListener('submit', function (event) {
            event.target.querySelectorAll('textarea[data-rich-text-ready="1"]').forEach(syncTextarea);
        }, true);

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!(node instanceof Element)) {
                        return;
                    }

                    if (isSweetAlertElement(node)) {
                        return;
                    }

                    if (node.matches('textarea')) {
                        initTextarea(node);
                        return;
                    }

                    if (node.querySelector('textarea')) {
                        refresh(node);
                    }
                });
            });
        });

        window.VOpsRichTextEditor = {
            clear: clearTextarea,
            refresh: refresh,
            sync: syncTextarea
        };

        refresh(document);
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    </script>
@endonce
