<script>
document.addEventListener('DOMContentLoaded', function () {
    function systemRow(prefix, index) {
        return `
            <div class="structured-row system-row draggable-structured-row border rounded-3 p-3 mb-2 bg-white" draggable="true">
                <input type="hidden" name="${prefix}[${index}][urutan]" value="1" data-order-input>
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light border structured-drag-handle" title="Geser untuk mengubah urutan" aria-label="Geser Sistem / Aplikasi">Drag</button>
                        <strong><span data-order-label>Sistem 1</span> / Aplikasi</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-structured-row">Hapus</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Nama Sistem</label>
                        <input type="text" name="${prefix}[${index}][nama_sistem]" class="form-control" placeholder="Contoh: HRIS, Email, Google Form">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Akun yang Digunakan</label>
                        <input type="text" name="${prefix}[${index}][akun]" class="form-control" placeholder="Contoh: recruitment@company.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fungsi</label>
                        <textarea name="${prefix}[${index}][fungsi]" rows="2" class="form-control" placeholder="Dipakai untuk apa pada tahap ini"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">URL / Lokasi Akses</label>
                        <input type="text" name="${prefix}[${index}][url]" class="form-control" placeholder="https://... atau lokasi aplikasi">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan Akses</label>
                        <textarea name="${prefix}[${index}][catatan]" rows="2" class="form-control" placeholder="Hak akses, batasan, atau prosedur login"></textarea>
                    </div>
                </div>
            </div>
        `;
    }

    function picRow(prefix, index) {
        return `
            <div class="structured-row pic-row draggable-structured-row border rounded-3 p-3 mb-2 bg-white" draggable="true">
                <input type="hidden" name="${prefix}[${index}][urutan]" value="1" data-order-input data-pic-order-input>
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light border structured-drag-handle pic-drag-handle" title="Geser untuk mengubah urutan" aria-label="Geser PIC">Drag</button>
                        <strong><span data-order-label data-pic-order-label>PIC 1</span> / Orang Terkait</strong>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-structured-row">Hapus</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Nama PIC</label>
                        <input type="text" name="${prefix}[${index}][nama]" class="form-control" placeholder="Nama orang atau tim">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Peran</label>
                        <input type="text" name="${prefix}[${index}][peran]" class="form-control" placeholder="Contoh: HR Recruitment, Kepala Departemen">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kontak</label>
                        <input type="text" name="${prefix}[${index}][kontak]" class="form-control" placeholder="Email, nomor HP, extension">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kapan Dihubungi</label>
                        <input type="text" name="${prefix}[${index}][waktu_dihubungi]" class="form-control" placeholder="Contoh: setelah verifikasi online selesai">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="${prefix}[${index}][catatan]" rows="2" class="form-control" placeholder="Hal khusus terkait PIC ini"></textarea>
                    </div>
                </div>
            </div>
        `;
    }

    function optionalSectionHasValue(section) {
        return Array.from(section.querySelectorAll('[data-optional-body] input, [data-optional-body] textarea, [data-optional-body] select'))
            .some(function (field) {
                if (field.type === 'hidden') {
                    return false;
                }

                return String(field.value || '').trim() !== '';
            });
    }

    function setOptionalSectionState(section, enabled) {
        const body = section.querySelector('[data-optional-body]');
        const toggle = section.querySelector('[data-optional-toggle]');
        const toggleText = section.querySelector('[data-optional-toggle-text]');

        section.dataset.optionalEnabled = enabled ? '1' : '0';
        section.classList.toggle('is-on', enabled);
        section.classList.toggle('is-off', !enabled);

        if (body) {
            body.classList.toggle('d-none', !enabled);
            body.querySelectorAll('input, textarea, select').forEach(function (field) {
                field.disabled = !enabled;
            });
        }

        if (toggle) {
            toggle.classList.toggle('is-on', enabled);
            toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        }

        if (toggleText) {
            toggleText.textContent = enabled ? 'ON' : 'OFF';
        }

        section.querySelectorAll('[data-add-system-row], [data-add-pic-row]').forEach(function (button) {
            button.disabled = !enabled;
        });
    }

    function ensureFloatingAddButton(section) {
        if (section.querySelector('[data-floating-add-row]')) {
            return;
        }

        const list = section.querySelector('[data-structured-list]');

        if (!list || !['sistem', 'pic'].includes(list.dataset.structuredList)) {
            return;
        }

        const type = list.dataset.structuredList;
        const wrapper = document.createElement('div');
        const button = document.createElement('button');

        wrapper.className = 'workflow-floating-actions d-none d-lg-flex';
        button.type = 'button';
        button.className = 'btn btn-primary btn-sm workflow-floating-add';
        button.setAttribute('data-floating-add-row', '1');

        if (type === 'sistem') {
            button.setAttribute('data-add-system-row', '');
            button.textContent = '+ Tambah Sistem';
        } else {
            button.setAttribute('data-add-pic-row', '');
            button.textContent = '+ Tambah PIC';
        }

        wrapper.appendChild(button);
        section.appendChild(wrapper);
    }

    function refreshOptionalSections(root) {
        const scope = root || document;

        scope.querySelectorAll('[data-optional-section]').forEach(function (section) {
            ensureFloatingAddButton(section);

            const enabled = section.dataset.optionalEnabled === '1'
                || (section.dataset.optionalEnabled !== '0' && optionalSectionHasValue(section));

            setOptionalSectionState(section, enabled);
        });
    }

    window.VOpsOptionalSections = {
        refresh: refreshOptionalSections,
        setState: setOptionalSectionState,
    };

    function addRow(button, type) {
        const scope = button.closest('[data-structured-scope]');
        const list = scope ? scope.querySelector(`[data-structured-list="${type}"]`) : null;

        if (!list) {
            return;
        }

        const prefix = list.dataset.namePrefix;
        const index = Date.now();
        list.insertAdjacentHTML('beforeend', type === 'sistem' ? systemRow(prefix, index) : picRow(prefix, index));
        updateStructuredOrder(list);

        const newRow = list.querySelector('.structured-row:last-child');
        if (newRow) {
            newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function clearRow(row) {
        row.querySelectorAll('input, textarea').forEach(function (field) {
            if (field.type === 'hidden') {
                return;
            }

            if (field.matches('textarea') && window.VOpsRichTextEditor) {
                window.VOpsRichTextEditor.clear(field);
                return;
            }

            field.value = '';
        });
    }

    document.addEventListener('click', function (event) {
        const addSystem = event.target.closest('[data-add-system-row]');
        const addPic = event.target.closest('[data-add-pic-row]');
        const remove = event.target.closest('.remove-structured-row');
        const optionalToggle = event.target.closest('[data-optional-toggle]');

        if (optionalToggle) {
            const section = optionalToggle.closest('[data-optional-section]');

            if (section) {
                setOptionalSectionState(section, section.dataset.optionalEnabled !== '1');
            }

            return;
        }

        if (addSystem) {
            addRow(addSystem, 'sistem');
            return;
        }

        if (addPic) {
            addRow(addPic, 'pic');
            return;
        }

        if (!remove) {
            return;
        }

        const row = remove.closest('.structured-row');
        const list = row ? row.parentElement : null;

        if (!row || !list) {
            return;
        }

        if (list.querySelectorAll('.structured-row').length === 1) {
            clearRow(row);
            updateStructuredOrder(list);
            return;
        }

        row.remove();
        updateStructuredOrder(list);
    });

    let draggedStructuredRow = null;
    let draggedStructuredList = null;

    function structuredLabelPrefix(list) {
        if (!list) {
            return '';
        }

        return list.dataset.structuredList === 'sistem' ? 'Sistem' : 'PIC';
    }

    function updateStructuredOrder(list) {
        if (!list || !['sistem', 'pic'].includes(list.dataset.structuredList)) {
            return;
        }

        const prefix = structuredLabelPrefix(list);

        list.querySelectorAll('.structured-row').forEach(function (row, index) {
            const order = index + 1;
            const input = row.querySelector('[data-order-input]');
            const label = row.querySelector('[data-order-label]');

            if (input) {
                input.value = order;
            }

            if (label) {
                label.textContent = prefix + ' ' + order;
            }
        });
    }

    function rowAfterPointer(list, y) {
        const rows = Array.from(list.querySelectorAll('.draggable-structured-row:not(.is-dragging)'));

        return rows.reduce(function (closest, row) {
            const box = row.getBoundingClientRect();
            const offset = y - box.top - (box.height / 2);

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: row };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    document.addEventListener('dragstart', function (event) {
        const row = event.target.closest('.draggable-structured-row');

        if (!row) {
            return;
        }

        draggedStructuredRow = row;
        draggedStructuredList = row.closest('[data-structured-list]');
        row.classList.add('is-dragging');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', 'structured-row');
        }
    });

    document.addEventListener('dragover', function (event) {
        if (!draggedStructuredRow || !draggedStructuredList) {
            return;
        }

        const list = event.target.closest('[data-structured-list]');

        if (list !== draggedStructuredList) {
            return;
        }

        event.preventDefault();

        const nextRow = rowAfterPointer(list, event.clientY);

        if (nextRow) {
            list.insertBefore(draggedStructuredRow, nextRow);
        } else {
            list.appendChild(draggedStructuredRow);
        }
    });

    document.addEventListener('drop', function (event) {
        if (!draggedStructuredList) {
            return;
        }

        event.preventDefault();
        updateStructuredOrder(draggedStructuredList);
    });

    document.addEventListener('dragend', function () {
        if (draggedStructuredRow) {
            draggedStructuredRow.classList.remove('is-dragging');
        }

        if (draggedStructuredList) {
            updateStructuredOrder(draggedStructuredList);
        }

        draggedStructuredRow = null;
        draggedStructuredList = null;
    });

    document.querySelectorAll('[data-structured-list]').forEach(updateStructuredOrder);
    refreshOptionalSections(document);

    const optionalSectionObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (!(node instanceof Element)) {
                    return;
                }

                if (node.matches('[data-optional-section]')) {
                    refreshOptionalSections(node.parentElement || document);
                    return;
                }

                if (node.querySelector('[data-optional-section]')) {
                    refreshOptionalSections(node);
                }
            });
        });
    });

    optionalSectionObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
});
</script>
