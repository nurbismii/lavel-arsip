<script>
document.addEventListener('DOMContentLoaded', function () {
    const defaultSymbol = 'aktivitas';
    const symbolLabels = window.VOpsSopSymbolLabels || {};

    function nextIndex(list) {
        return Date.now().toString() + list.querySelectorAll('[data-repeatable-row]').length.toString();
    }

    function labelForSymbol(symbol) {
        return symbolLabels[symbol] || symbolLabels[defaultSymbol] || 'Aktivitas - Langkah Kerja';
    }

    function escapeHtml(value) {
        const wrapper = document.createElement('div');
        wrapper.textContent = value || '';

        return wrapper.innerHTML;
    }

    function documentTextarea() {
        return document.querySelector('[data-sop-document-editor]');
    }

    function selectedOptionText(selector, fallback) {
        const element = document.querySelector(selector);

        if (!element || !element.options || element.selectedIndex < 0) {
            return fallback;
        }

        return element.options[element.selectedIndex].text.trim() || fallback;
    }

    function fieldValue(selector, fallback) {
        const element = document.querySelector(selector);

        return element && element.value ? element.value.trim() : fallback;
    }

    function formatIndonesianDate(value) {
        if (!value) {
            return '-';
        }

        const parts = value.split('-');

        if (parts.length !== 3) {
            return value;
        }

        const monthNames = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];
        const monthIndex = parseInt(parts[1], 10) - 1;

        if (monthIndex < 0 || monthIndex > 11) {
            return value;
        }

        return parseInt(parts[2], 10) + ' ' + monthNames[monthIndex] + ' ' + parts[0];
    }

    function insertIntoDocument(html) {
        const textarea = documentTextarea();

        if (!textarea || !window.VOpsRichTextEditor || typeof window.VOpsRichTextEditor.insertHtml !== 'function') {
            return false;
        }

        return window.VOpsRichTextEditor.insertHtml(textarea, html);
    }

    function insertKopIntoDocument(value) {
        const textarea = documentTextarea();

        if (!textarea || !window.VOpsRichTextEditor || typeof window.VOpsRichTextEditor.insertSopKop !== 'function') {
            return false;
        }

        return window.VOpsRichTextEditor.insertSopKop(textarea, value);
    }

    function setInlineButtonLoading(button, isLoading, text) {
        if (!button) {
            return;
        }

        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (text || 'Memproses...');
            return;
        }

        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }

    function initDiagramImageUploader() {
        const root = document.querySelector('[data-sop-diagram-uploader]');

        if (!root) {
            return;
        }

        const input = root.querySelector('[data-sop-diagram-image-input]');
        const uploadButton = root.querySelector('[data-sop-diagram-upload-button]');
        const emptyState = root.querySelector('[data-sop-diagram-empty]');
        const preview = root.querySelector('[data-sop-diagram-preview]');
        const previewList = root.querySelector('[data-sop-diagram-preview-list]');
        const uploadUrl = root.dataset.uploadUrl;
        let previewUrls = [];

        function selectedFiles() {
            return input && input.files && input.files.length ? Array.from(input.files) : [];
        }

        function showError(message) {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal mengupload gambar',
                    text: message || 'Periksa kembali file gambar lalu coba lagi.'
                });
            }
        }

        function validateFile(file) {
            const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];

            if (!file) {
                return 'Pilih gambar diagram terlebih dahulu.';
            }

            if (!allowedTypes.includes(file.type)) {
                return 'Format gambar harus PNG, JPG, JPEG, atau WEBP.';
            }

            if (file.size > (5 * 1024 * 1024)) {
                return 'Ukuran gambar maksimal 5 MB.';
            }

            return null;
        }

        function clearPreviewUrls() {
            previewUrls.forEach(function (url) {
                URL.revokeObjectURL(url);
            });
            previewUrls = [];
        }

        function updatePreview() {
            const files = selectedFiles();

            clearPreviewUrls();

            if (previewList) {
                previewList.innerHTML = '';
            }

            if (!files.length) {
                if (emptyState) {
                    emptyState.classList.remove('d-none');
                }

                if (preview) {
                    preview.classList.add('d-none');
                }

                return;
            }

            const invalidMessage = files.map(validateFile).find(Boolean);

            if (invalidMessage) {
                showError(invalidMessage);
                input.value = '';
                updatePreview();
                return;
            }

            files.forEach(function (file, index) {
                const item = document.createElement('div');
                const image = document.createElement('img');
                const caption = document.createElement('div');
                const url = URL.createObjectURL(file);

                previewUrls.push(url);
                item.className = 'sop-diagram-uploader__item';
                image.className = 'sop-diagram-uploader__image';
                image.src = url;
                image.alt = 'Preview diagram prosedur ' + (index + 1);
                caption.className = 'sop-diagram-uploader__file mt-2';
                caption.textContent = (index + 1) + '. ' + file.name + ' - ' + Math.round(file.size / 1024) + ' KB';

                item.appendChild(image);
                item.appendChild(caption);

                if (previewList) {
                    previewList.appendChild(item);
                }
            });

            if (emptyState) {
                emptyState.classList.add('d-none');
            }

            if (preview) {
                preview.classList.remove('d-none');
            }
        }

        async function uploadAndInsert() {
            const files = selectedFiles();
            const error = files.length ? files.map(validateFile).find(Boolean) : 'Pilih gambar diagram terlebih dahulu.';

            if (error) {
                showError(error);
                return;
            }

            const textarea = documentTextarea();
            const editor = window.VOpsRichTextEditor;

            if (!textarea || !editor || typeof editor.insertSopImage !== 'function') {
                showError('Editor dokumen belum siap. Muat ulang halaman lalu coba lagi.');
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]');
            const insertedNames = [];

            setInlineButtonLoading(uploadButton, true, files.length > 1 ? 'Mengupload 1/' + files.length + '...' : (uploadButton ? uploadButton.dataset.loadingText : 'Mengupload...'));

            try {
                for (let index = 0; index < files.length; index++) {
                    const file = files[index];
                    const formData = new FormData();

                    if (files.length > 1) {
                        setInlineButtonLoading(uploadButton, true, 'Mengupload ' + (index + 1) + '/' + files.length + '...');
                    }

                    formData.append('diagram_image', file);

                    const response = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : ''
                        },
                        body: formData
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        const message = payload && payload.errors
                            ? Object.values(payload.errors).flat().join(' ')
                            : (payload.message || 'Upload gambar gagal diproses.');

                        throw new Error(message);
                    }

                    editor.insertSopImage(textarea, payload.url, payload.name || file.name || 'Diagram Prosedur Pelaksanaan');
                    insertedNames.push(payload.name || file.name);
                }

                if (window.Swal) {
                    window.Swal.fire({
                        icon: 'success',
                        title: files.length > 1 ? 'Semua diagram disisipkan' : 'Diagram disisipkan',
                        text: files.length + ' gambar diagram sudah masuk ke bagian Prosedur Pelaksanaan.',
                        timer: 1900,
                        showConfirmButton: false
                    });
                }
            } catch (error) {
                showError(error.message);
            } finally {
                setInlineButtonLoading(uploadButton, false);
            }
        }

        if (input) {
            input.addEventListener('change', updatePreview);
        }

        if (uploadButton) {
            uploadButton.addEventListener('click', uploadAndInsert);
        }
    }

    function kopData() {
        const title = fieldValue('[data-sop-title-input]', 'Judul SOP');
        const code = fieldValue('[data-sop-code-input]', '-');
        const revision = fieldValue('[data-sop-revision-input]', '000');
        const effectiveDate = formatIndonesianDate(fieldValue('[data-sop-effective-date-input]', ''));

        return {
            title: title,
            code: code,
            revision: revision,
            effectiveDate: effectiveDate,
            page: '1 dari 1'
        };
    }

    function structureTemplate() {
        return [
            '<h2>Tujuan</h2>',
            '<p>Tulis tujuan SOP dengan bahasa singkat dan jelas.</p>',
            '<h2>Ruang Lingkup</h2>',
            '<p>Jelaskan proses, unit, dan pihak yang termasuk dalam SOP ini.</p>',
            '<h2>Definisi</h2>',
            '<p>Tulis istilah penting jika ada.</p>',
            '<h2>Prosedur Pelaksanaan</h2>',
            '<p>Tulis langkah kerja atau sisipkan gambar diagram prosedur dari hasil upload.</p>',
            '<ol>',
            '<li>Tulis aktivitas kerja pertama.</li>',
            '<li>Tulis keputusan atau kondisi jika ada percabangan.</li>',
            '<li>Tulis dokumen atau formulir yang digunakan.</li>',
            '<li>Tulis aktivitas penutup atau hasil akhir proses.</li>',
            '</ol>',
            '<h2>Lampiran</h2>',
            '<p>Tulis daftar lampiran atau formulir pendukung.</p>',
            '<h2>Catatan Revisi</h2>',
            '<p>Tulis riwayat revisi jika dokumen sudah pernah diperbarui.</p>'
        ].join('');
    }

    function refreshRichText(row) {
        if (window.VOpsRichTextEditor && typeof window.VOpsRichTextEditor.refresh === 'function') {
            window.VOpsRichTextEditor.refresh(row);
        }
    }

    function updateProcedureSymbol(row, symbol) {
        if (!row) {
            return;
        }

        const normalizedSymbol = symbol || defaultSymbol;
        const label = labelForSymbol(normalizedSymbol);
        const select = row.querySelector('[data-sop-row-symbol-select]');
        const pill = row.querySelector('[data-sop-row-symbol-label]');

        row.dataset.sopSymbol = normalizedSymbol;

        if (select && select.value !== normalizedSymbol) {
            select.value = normalizedSymbol;
        }

        if (!pill) {
            return;
        }

        pill.textContent = '';

        const icon = document.createElement('span');
        icon.className = 'sop-symbol-icon sop-symbol-icon--' + normalizedSymbol;
        icon.setAttribute('aria-hidden', 'true');

        pill.appendChild(icon);
        pill.appendChild(document.createTextNode(label));
    }

    function addRepeatableRow(key, options) {
        const list = document.querySelector('[data-repeatable-list="' + key + '"]');
        const template = document.querySelector('[data-repeatable-template="' + key + '"]');

        if (!list || !template) {
            return null;
        }

        const order = list.querySelectorAll('[data-repeatable-row]').length + 1;
        const symbol = options && options.symbol ? options.symbol : defaultSymbol;
        const wrapper = document.createElement('div');

        wrapper.innerHTML = template.innerHTML
            .replace(/__INDEX__/g, nextIndex(list))
            .replace(/__ORDER__/g, order)
            .replace(/__SYMBOL__/g, symbol)
            .replace(/__SYMBOL_LABEL__/g, labelForSymbol(symbol));

        const row = wrapper.firstElementChild;
        list.appendChild(row);

        if (key === 'prosedur') {
            updateProcedureSymbol(row, symbol);
        }

        refreshRichText(row);
        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        return row;
    }

    function initFlowchartEditor() {
        const input = document.querySelector('[data-sop-flowchart-input]');
        const root = document.querySelector('[data-sop-flowchart]');
        const canvas = document.querySelector('[data-sop-flow-canvas]');
        const svg = document.querySelector('[data-sop-flow-lines]');
        const emptyState = document.querySelector('[data-sop-flow-empty]');
        const help = document.querySelector('[data-sop-flow-help]');

        if (!input || !root || !canvas || !svg) {
            return;
        }

        const nodeWidth = 168;
        const nodeDefaultHeight = 72;
        const nodeDecisionHeight = 92;
        const syncStatus = document.querySelector('[data-sop-flow-sync-status]');
        let hasUserInteracted = false;
        let hasUnsyncedDiagram = false;

        const state = {
            connectors: [],
            mode: 'move',
            nodes: [],
            selectedConnectorId: null,
            selectedNodeId: null,
            sourceNodeId: null
        };

        function uid(prefix) {
            return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
        }

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function nodeById(id) {
            return state.nodes.find(function (node) {
                return node.id === id;
            });
        }

        function connectorById(id) {
            return state.connectors.find(function (connector) {
                return connector.id === id;
            });
        }

        function hasFlowSymbolData(event) {
            if (!event.dataTransfer || !event.dataTransfer.types) {
                return false;
            }

            return Array.prototype.indexOf.call(event.dataTransfer.types, 'application/x-sop-flow-symbol') !== -1;
        }

        function canvasPoint(event) {
            const rect = canvas.getBoundingClientRect();

            return {
                x: event.clientX - rect.left + canvas.scrollLeft,
                y: event.clientY - rect.top + canvas.scrollTop
            };
        }

        function nodeSize(node) {
            return {
                height: node.type === 'decision' ? nodeDecisionHeight : nodeDefaultHeight,
                width: nodeWidth
            };
        }

        function nodeCenter(node) {
            const size = nodeSize(node);

            return {
                x: node.x + (size.width / 2),
                y: node.y + (size.height / 2)
            };
        }

        function nodeEdgePoint(node, targetPoint) {
            const size = nodeSize(node);
            const center = nodeCenter(node);
            const dx = targetPoint.x - center.x;
            const dy = targetPoint.y - center.y;
            const safeDx = Math.max(Math.abs(dx), 0.001);
            const safeDy = Math.max(Math.abs(dy), 0.001);
            const scale = Math.min((size.width / 2) / safeDx, (size.height / 2) / safeDy);

            return {
                x: center.x + (dx * scale),
                y: center.y + (dy * scale)
            };
        }

        function connectorPath(fromNode, toNode) {
            const fromCenter = nodeCenter(fromNode);
            const toCenter = nodeCenter(toNode);
            const start = nodeEdgePoint(fromNode, toCenter);
            const end = nodeEdgePoint(toNode, fromCenter);
            const midX = start.x + ((end.x - start.x) / 2);

            return 'M ' + start.x + ' ' + start.y + ' C ' + midX + ' ' + start.y + ', ' + midX + ' ' + end.y + ', ' + end.x + ' ' + end.y;
        }

        function maxNodeX() {
            return Math.max(8, canvas.offsetWidth - (nodeWidth + 12));
        }

        function maxNodeY() {
            return Math.max(8, canvas.offsetHeight - (nodeDecisionHeight + 12));
        }

        function diagramPayload() {
            return {
                nodes: state.nodes.map(function (node) {
                    return {
                        id: node.id,
                        type: node.type,
                        label: node.label,
                        x: Math.round(node.x),
                        y: Math.round(node.y)
                    };
                }),
                connectors: state.connectors.map(function (connector) {
                    return {
                        id: connector.id,
                        from: connector.from,
                        to: connector.to
                    };
                })
            };
        }

        function updateSyncStatus(message) {
            if (syncStatus) {
                syncStatus.textContent = message;
            }
        }

        function markDiagramDirty() {
            if (!hasUserInteracted) {
                return;
            }

            hasUnsyncedDiagram = true;
            updateSyncStatus('Perubahan diagram belum ditempel ke Prosedur Pelaksanaan. Klik Tempelkan ke Prosedur atau simpan SOP.');
        }

        function syncDiagramToDocument(showFeedback) {
            const payload = diagramPayload();
            const textarea = documentTextarea();
            const editor = window.VOpsRichTextEditor;

            if (!payload.nodes.length && showFeedback && window.Swal) {
                window.Swal.fire({
                    icon: 'info',
                    title: 'Flowchart masih kosong',
                    text: 'Tambahkan minimal satu simbol sebelum menempelkan diagram ke Prosedur Pelaksanaan.'
                });

                return false;
            }

            if (!textarea || !editor || typeof editor.insertOrReplaceSopDiagram !== 'function') {
                updateSyncStatus('Diagram sudah tersimpan sebagai data flowchart, tetapi editor dokumen belum siap untuk ditempeli.');
                return false;
            }

            const isSynced = editor.insertOrReplaceSopDiagram(textarea, payload);

            if (isSynced) {
                hasUnsyncedDiagram = false;
                updateSyncStatus(payload.nodes.length
                    ? 'Diagram sudah tertempel di bagian Prosedur Pelaksanaan pada editor dokumen.'
                    : 'Diagram di Prosedur Pelaksanaan sudah dikosongkan.');
            }

            if (showFeedback && window.Swal && isSynced && payload.nodes.length) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Diagram tertempel',
                    text: 'Flowchart sudah diperbarui pada bagian Prosedur Pelaksanaan.',
                    timer: 1600,
                    showConfirmButton: false
                });
            }

            return isSynced;
        }

        function syncInput(markDirty) {
            input.value = JSON.stringify(diagramPayload());

            if (markDirty !== false) {
                markDiagramDirty();
            }
        }

        function setMode(mode) {
            state.mode = mode;
            state.sourceNodeId = null;

            root.querySelectorAll('[data-sop-flow-mode]').forEach(function (button) {
                const isActive = button.dataset.sopFlowMode === mode;
                button.classList.toggle('btn-primary', isActive);
                button.classList.toggle('btn-outline-primary', !isActive);
            });

            if (help) {
                help.textContent = mode === 'connect'
                    ? 'Mode Hubungkan aktif. Klik simbol sumber, lalu klik simbol tujuan untuk membuat garis panah.'
                    : 'Mode Geser aktif. Drag simbol ke kanvas, lalu geser sesuai posisi yang diinginkan.';
            }

            render();
        }

        function selectNode(id) {
            state.selectedNodeId = id;
            state.selectedConnectorId = null;
            render();
        }

        function selectConnector(id) {
            state.selectedConnectorId = id;
            state.selectedNodeId = null;
            render();
        }

        function addNode(type, x, y) {
            hasUserInteracted = true;

            const safeType = symbolLabels[type] ? type : defaultSymbol;
            const node = {
                id: uid('node'),
                type: safeType,
                label: labelForSymbol(safeType),
                x: clamp(x, 8, maxNodeX()),
                y: clamp(y, 8, maxNodeY())
            };

            state.nodes.push(node);
            selectNode(node.id);
            syncInput();
            render();
        }

        function addConnector(from, to) {
            if (!from || !to || from === to) {
                return;
            }

            const exists = state.connectors.some(function (connector) {
                return connector.from === from && connector.to === to;
            });

            if (exists) {
                return;
            }

            hasUserInteracted = true;
            state.connectors.push({
                id: uid('line'),
                from: from,
                to: to
            });
            state.sourceNodeId = null;
            syncInput();
            render();
        }

        function deleteSelection() {
            hasUserInteracted = true;

            if (state.selectedNodeId) {
                const deletedNodeId = state.selectedNodeId;
                state.nodes = state.nodes.filter(function (node) {
                    return node.id !== deletedNodeId;
                });
                state.connectors = state.connectors.filter(function (connector) {
                    return connector.from !== deletedNodeId && connector.to !== deletedNodeId;
                });
                state.selectedNodeId = null;
            } else if (state.selectedConnectorId) {
                const deletedConnectorId = state.selectedConnectorId;
                state.connectors = state.connectors.filter(function (connector) {
                    return connector.id !== deletedConnectorId;
                });
                state.selectedConnectorId = null;
            }

            syncInput();
            render();
        }

        function updateEmptyState() {
            if (emptyState) {
                emptyState.classList.toggle('d-none', state.nodes.length > 0);
            }
        }

        function renderLines() {
            svg.querySelectorAll('[data-sop-flow-connector]').forEach(function (line) {
                line.remove();
            });

            state.connectors.forEach(function (connector) {
                const fromNode = nodeById(connector.from);
                const toNode = nodeById(connector.to);

                if (!fromNode || !toNode) {
                    return;
                }

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

                path.setAttribute('d', connectorPath(fromNode, toNode));
                path.setAttribute('class', 'sop-flow-connector' + (connector.id === state.selectedConnectorId ? ' is-selected' : ''));
                path.setAttribute('marker-end', 'url(#sopFlowArrow)');
                path.setAttribute('data-sop-flow-connector', connector.id);
                path.addEventListener('click', function (event) {
                    event.stopPropagation();
                    selectConnector(connector.id);
                });

                svg.appendChild(path);
            });
        }

        function createNodeElement(node) {
            const element = document.createElement('div');
            element.className = 'sop-flow-node sop-flow-node--' + node.type;
            element.classList.toggle('is-selected', node.id === state.selectedNodeId);
            element.classList.toggle('is-source', node.id === state.sourceNodeId);
            element.style.left = node.x + 'px';
            element.style.top = node.y + 'px';
            element.dataset.sopFlowNode = node.id;

            const symbol = document.createElement('span');
            symbol.className = 'sop-symbol-icon sop-symbol-icon--' + node.type;
            symbol.setAttribute('aria-hidden', 'true');

            const label = document.createElement('textarea');
            label.className = 'sop-flow-node__label';
            label.value = node.label;
            label.rows = 2;
            label.addEventListener('input', function () {
                hasUserInteracted = true;
                node.label = label.value.trim().slice(0, 160) || labelForSymbol(node.type);
                syncInput();
            });
            label.addEventListener('pointerdown', function (event) {
                event.stopPropagation();
            });

            element.appendChild(symbol);
            element.appendChild(label);

            element.addEventListener('click', function (event) {
                event.stopPropagation();

                if (state.mode === 'connect') {
                    if (!state.sourceNodeId) {
                        state.sourceNodeId = node.id;
                        selectNode(node.id);
                        return;
                    }

                    addConnector(state.sourceNodeId, node.id);
                    return;
                }

                selectNode(node.id);
            });

            element.addEventListener('pointerdown', function (event) {
                if (state.mode !== 'move' || event.target === label) {
                    return;
                }

                event.preventDefault();
                selectNode(node.id);

                const startPoint = canvasPoint(event);
                const startX = node.x;
                const startY = node.y;

                function move(moveEvent) {
                    hasUserInteracted = true;
                    const currentPoint = canvasPoint(moveEvent);
                    node.x = clamp(startX + currentPoint.x - startPoint.x, 8, maxNodeX());
                    node.y = clamp(startY + currentPoint.y - startPoint.y, 8, maxNodeY());
                    element.style.left = node.x + 'px';
                    element.style.top = node.y + 'px';
                    renderLines();
                }

                function stop() {
                    syncInput();
                    document.removeEventListener('pointermove', move);
                    document.removeEventListener('pointerup', stop);
                }

                document.addEventListener('pointermove', move);
                document.addEventListener('pointerup', stop);
            });

            return element;
        }

        function render() {
            canvas.querySelectorAll('[data-sop-flow-node]').forEach(function (node) {
                node.remove();
            });

            state.nodes.forEach(function (node) {
                canvas.appendChild(createNodeElement(node));
            });

            renderLines();
            updateEmptyState();
        }

        function loadInitialState() {
            let payload = {};

            try {
                payload = JSON.parse(input.value || '{}') || {};
            } catch (error) {
                payload = {};
            }

            state.nodes = Array.isArray(payload.nodes) ? payload.nodes.map(function (node) {
                const type = symbolLabels[node.type] ? node.type : defaultSymbol;

                return {
                    id: String(node.id || uid('node')).replace(/[^a-zA-Z0-9_-]/g, ''),
                    type: type,
                    label: String(node.label || labelForSymbol(type)).slice(0, 160),
                    x: clamp(parseFloat(node.x) || 32, 8, maxNodeX()),
                    y: clamp(parseFloat(node.y) || 32, 8, maxNodeY())
                };
            }) : [];

            const ids = new Set(state.nodes.map(function (node) {
                return node.id;
            }));

            state.connectors = Array.isArray(payload.connectors) ? payload.connectors.filter(function (connector) {
                return connector && ids.has(connector.from) && ids.has(connector.to) && connector.from !== connector.to;
            }).map(function (connector) {
                return {
                    id: String(connector.id || uid('line')).replace(/[^a-zA-Z0-9_-]/g, ''),
                    from: connector.from,
                    to: connector.to
                };
            }) : [];
        }

        root.querySelectorAll('[data-sop-flow-symbol]').forEach(function (button, index) {
            button.addEventListener('click', function () {
                addNode(button.dataset.sopFlowSymbol, 48 + ((index % 3) * 210), 48 + (Math.floor(index / 3) * 130));
            });

            button.addEventListener('dragstart', function (event) {
                if (!event.dataTransfer) {
                    return;
                }

                event.dataTransfer.effectAllowed = 'copy';
                event.dataTransfer.setData('application/x-sop-flow-symbol', button.dataset.sopFlowSymbol);
                button.classList.add('is-dragging');
            });

            button.addEventListener('dragend', function () {
                button.classList.remove('is-dragging');
            });
        });

        canvas.addEventListener('dragover', function (event) {
            if (!hasFlowSymbolData(event)) {
                return;
            }

            event.preventDefault();
            canvas.classList.add('is-drag-over');
        });

        canvas.addEventListener('dragleave', function (event) {
            if (!event.relatedTarget || !canvas.contains(event.relatedTarget)) {
                canvas.classList.remove('is-drag-over');
            }
        });

        canvas.addEventListener('drop', function (event) {
            if (!event.dataTransfer) {
                return;
            }

            const type = event.dataTransfer.getData('application/x-sop-flow-symbol');

            if (!type) {
                return;
            }

            event.preventDefault();
            canvas.classList.remove('is-drag-over');

            const point = canvasPoint(event);
            addNode(type, point.x - 84, point.y - 36);
        });

        canvas.addEventListener('click', function () {
            state.selectedNodeId = null;
            state.selectedConnectorId = null;
            state.sourceNodeId = null;
            render();
        });

        root.querySelectorAll('[data-sop-flow-mode]').forEach(function (button) {
            button.addEventListener('click', function () {
                setMode(button.dataset.sopFlowMode);
            });
        });

        const deleteButton = root.querySelector('[data-sop-flow-delete]');

        if (deleteButton) {
            deleteButton.addEventListener('click', deleteSelection);
        }

        const clearButton = root.querySelector('[data-sop-flow-clear]');

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                function clearCanvas() {
                    hasUserInteracted = true;
                    state.nodes = [];
                    state.connectors = [];
                    state.selectedConnectorId = null;
                    state.selectedNodeId = null;
                    state.sourceNodeId = null;
                    syncInput();
                    render();
                }

                if (window.Swal && state.nodes.length > 0) {
                    window.Swal.fire({
                        title: 'Bersihkan flowchart?',
                        text: 'Semua simbol dan garis penghubung pada kanvas akan dihapus.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, bersihkan',
                        cancelButtonText: 'Batal',
                        reverseButtons: true
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            clearCanvas();
                        }
                    });

                    return;
                }

                clearCanvas();
            });
        }

        const insertButton = root.querySelector('[data-sop-flow-insert]');

        if (insertButton) {
            insertButton.addEventListener('click', function () {
                hasUserInteracted = true;
                syncDiagramToDocument(true);
            });
        }

        document.addEventListener('submit', function (event) {
            if (!event.target.contains(input)) {
                return;
            }

            syncInput(false);

            if (hasUnsyncedDiagram || state.nodes.length) {
                syncDiagramToDocument(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!root.contains(document.activeElement) && !canvas.contains(document.activeElement)) {
                return;
            }

            if (event.key !== 'Delete' && event.key !== 'Backspace') {
                return;
            }

            if (document.activeElement && document.activeElement.matches('textarea, input, select')) {
                return;
            }

            if (!state.selectedNodeId && !state.selectedConnectorId) {
                return;
            }

            event.preventDefault();
            deleteSelection();
        });

        loadInitialState();
        syncInput(false);
        setMode('move');
    }

    initFlowchartEditor();
    initDiagramImageUploader();

    document.addEventListener('change', function (event) {
        const symbolSelect = event.target.closest('[data-sop-row-symbol-select]');

        if (!symbolSelect) {
            return;
        }

        updateProcedureSymbol(symbolSelect.closest('[data-repeatable-row]'), symbolSelect.value);
    });

    document.addEventListener('click', function (event) {
        const kopButton = event.target.closest('[data-sop-insert-kop]');

        if (kopButton) {
            insertKopIntoDocument(kopData());
            return;
        }

        const structureButton = event.target.closest('[data-sop-insert-structure]');

        if (structureButton) {
            insertIntoDocument(structureTemplate());
            return;
        }

        const addButton = event.target.closest('[data-repeatable-add]');

        if (addButton) {
            addRepeatableRow(addButton.dataset.repeatableAdd);
            return;
        }

        const removeButton = event.target.closest('[data-repeatable-remove]');

        if (!removeButton) {
            return;
        }

        const row = removeButton.closest('[data-repeatable-row]');
        const list = row ? row.parentElement : null;

        if (!row || !list) {
            return;
        }

        if (list.querySelectorAll('[data-repeatable-row]').length === 1) {
            row.querySelectorAll('input, textarea').forEach(function (field) {
                field.value = '';
            });

            row.querySelectorAll('select').forEach(function (field) {
                if (field.matches('[data-sop-row-symbol-select]')) {
                    field.value = defaultSymbol;
                    return;
                }

                field.selectedIndex = 0;
            });

            row.querySelectorAll('[data-sop-order-input]').forEach(function (field) {
                field.value = '1';
            });

            if (window.VOpsRichTextEditor) {
                row.querySelectorAll('textarea[data-rich-text-ready="1"]').forEach(function (textarea) {
                    window.VOpsRichTextEditor.clear(textarea);
                });
            }

            if (list.dataset.repeatableList === 'prosedur') {
                updateProcedureSymbol(row, defaultSymbol);
            }

            return;
        }

        row.remove();
    });
});
</script>
