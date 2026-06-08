@once
    <script src="{{ asset('vendor/quill/quill.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Quill === 'undefined') {
            return;
        }

        const editors = new WeakMap();
        const sopPageHeightPx = 930;
        const sopSymbolNames = new Set(['terminator', 'aktivitas', 'decision', 'dokumen', 'connector_halaman', 'connector_internal']);
        const allowedTags = new Set(['A', 'BLOCKQUOTE', 'BR', 'DEFS', 'DIV', 'ELLIPSE', 'EM', 'G', 'H1', 'H2', 'H3', 'I', 'IMG', 'LI', 'MARKER', 'OL', 'P', 'PATH', 'POLYGON', 'RECT', 'S', 'SPAN', 'STRONG', 'SVG', 'TABLE', 'TBODY', 'TD', 'TEXT', 'TH', 'THEAD', 'TR', 'TSPAN', 'U', 'UL']);
        const removeWithContent = new Set(['IFRAME', 'SCRIPT', 'STYLE']);
        const toolbarOptions = [
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'link'],
            ['clean']
        ];
        const documentToolbarOptions = [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'link'],
            ['clean']
        ];

        const Embed = Quill.import('blots/embed');
        const BlockEmbed = Quill.import('blots/block/embed');

        class SopSymbolBlot extends Embed {
            static create(value) {
                const node = super.create();
                const symbol = value && value.symbol && sopSymbolNames.has(value.symbol)
                    ? value.symbol
                    : 'aktivitas';
                const labels = window.VOpsSopSymbolLabels || {};
                const label = value && value.label
                    ? value.label
                    : (labels[symbol] || 'Aktivitas - Langkah Kerja');

                node.setAttribute('data-sop-symbol', symbol);
                node.setAttribute('contenteditable', 'false');
                node.classList.add('sop-symbol-token--' + symbol);
                node.textContent = label;

                return node;
            }

            static value(node) {
                const symbol = node.getAttribute('data-sop-symbol') || 'aktivitas';

                return {
                    symbol: sopSymbolNames.has(symbol) ? symbol : 'aktivitas',
                    label: node.textContent.trim() || 'Aktivitas - Langkah Kerja'
                };
            }
        }

        SopSymbolBlot.blotName = 'sopSymbol';
        SopSymbolBlot.tagName = 'span';
        SopSymbolBlot.className = 'sop-symbol-token';

        Quill.register(SopSymbolBlot, true);

        class SopKopBlot extends BlockEmbed {
            static create(value) {
                const node = super.create();
                const kopData = normalizeKopData(value || {});

                node.setAttribute('contenteditable', 'false');
                node.setAttribute('data-sop-kop', '1');
                node.setAttribute('data-title', kopData.title);
                node.setAttribute('data-code', kopData.code);
                node.setAttribute('data-revision', kopData.revision);
                node.setAttribute('data-effective-date', kopData.effectiveDate);
                node.setAttribute('data-page', kopData.page);
                node.innerHTML = renderKopHtml(kopData);

                return node;
            }

            static value(node) {
                return normalizeKopData({
                    title: node.getAttribute('data-title'),
                    code: node.getAttribute('data-code'),
                    revision: node.getAttribute('data-revision'),
                    effectiveDate: node.getAttribute('data-effective-date'),
                    page: node.getAttribute('data-page')
                });
            }
        }

        SopKopBlot.blotName = 'sopKop';
        SopKopBlot.tagName = 'div';
        SopKopBlot.className = 'sop-kop-block';

        Quill.register(SopKopBlot, true);

        class SopDiagramBlot extends BlockEmbed {
            static create(value) {
                const node = super.create();
                const diagramData = normalizeDiagramData(value || {});

                node.setAttribute('contenteditable', 'false');
                node.setAttribute('data-sop-diagram', '1');
                node.setAttribute('data-diagram', JSON.stringify(diagramData));
                node.innerHTML = renderSopDiagramHtml(diagramData);

                return node;
            }

            static value(node) {
                try {
                    return normalizeDiagramData(JSON.parse(node.getAttribute('data-diagram') || '{}'));
                } catch (error) {
                    return normalizeDiagramData({});
                }
            }
        }

        SopDiagramBlot.blotName = 'sopDiagram';
        SopDiagramBlot.tagName = 'div';
        SopDiagramBlot.className = 'sop-diagram-block';

        Quill.register(SopDiagramBlot, true);

        function normalizeKopData(value) {
            return {
                title: String(value.title || 'JUDUL SOP').trim() || 'JUDUL SOP',
                code: String(value.code || '-').trim() || '-',
                revision: String(value.revision || '000').trim() || '000',
                effectiveDate: String(value.effectiveDate || '-').trim() || '-',
                page: String(value.page || '1 dari 1').trim() || '1 dari 1'
            };
        }

        function escapeHtml(value) {
            const wrapper = document.createElement('div');
            wrapper.textContent = value || '';

            return wrapper.innerHTML;
        }

        function renderKopHtml(value) {
            const kopData = normalizeKopData(value);

            return [
                '<div class="sop-kop-top">',
                '<div class="sop-kop-company">',
                '<div class="sop-kop-name">PT VIRTUE DRAGON NICKEL INDUSTRY</div>',
                '<div class="sop-kop-address">Kecamatan Morosi, Kabupaten Konawe, Sulawesi Tenggara</div>',
                '</div>',
                '<div class="sop-kop-meta">',
                '<div class="sop-kop-meta-label">No. Dokumen</div>',
                '<div class="sop-kop-meta-separator">:</div>',
                '<div class="sop-kop-meta-value">' + escapeHtml(kopData.code) + '</div>',
                '<div class="sop-kop-meta-label">No. Revisi</div>',
                '<div class="sop-kop-meta-separator">:</div>',
                '<div class="sop-kop-meta-value">' + escapeHtml(kopData.revision) + '</div>',
                '<div class="sop-kop-meta-label">Tanggal Berlaku</div>',
                '<div class="sop-kop-meta-separator">:</div>',
                '<div class="sop-kop-meta-value">' + escapeHtml(kopData.effectiveDate) + '</div>',
                '<div class="sop-kop-meta-label">Halaman</div>',
                '<div class="sop-kop-meta-separator">:</div>',
                '<div class="sop-kop-meta-value" data-sop-kop-page-value>' + escapeHtml(kopData.page) + '</div>',
                '</div>',
                '</div>',
                '<div class="sop-kop-title">',
                '<div>STANDAR OPERASIONAL PROSEDUR</div>',
                '<div>' + escapeHtml(kopData.title).toUpperCase() + '</div>',
                '</div>'
            ].join('');
        }

        function normalizeDiagramData(value) {
            const labels = window.VOpsSopSymbolLabels || {};
            const nodes = [];
            const nodeIds = new Set();

            (Array.isArray(value.nodes) ? value.nodes : []).slice(0, 120).forEach(function (node, index) {
                const id = String(node && node.id ? node.id : ('node-' + index)).replace(/[^a-zA-Z0-9_-]/g, '');
                const type = node && sopSymbolNames.has(node.type) ? node.type : 'aktivitas';

                if (!id || nodeIds.has(id)) {
                    return;
                }

                nodes.push({
                    id: id,
                    type: type,
                    label: String((node && node.label) || labels[type] || labels.aktivitas || 'Aktivitas - Langkah Kerja').trim().slice(0, 160),
                    x: Math.max(0, Math.min(1800, parseFloat(node && node.x) || 0)),
                    y: Math.max(0, Math.min(1200, parseFloat(node && node.y) || 0))
                });
                nodeIds.add(id);
            });

            const connectors = [];

            (Array.isArray(value.connectors) ? value.connectors : []).slice(0, 180).forEach(function (connector, index) {
                const id = String(connector && connector.id ? connector.id : ('line-' + index)).replace(/[^a-zA-Z0-9_-]/g, '');
                const from = String(connector && connector.from ? connector.from : '').replace(/[^a-zA-Z0-9_-]/g, '');
                const to = String(connector && connector.to ? connector.to : '').replace(/[^a-zA-Z0-9_-]/g, '');

                if (!id || !from || !to || from === to || !nodeIds.has(from) || !nodeIds.has(to)) {
                    return;
                }

                connectors.push({
                    id: id,
                    from: from,
                    to: to
                });
            });

            return {
                connectors: connectors,
                nodes: nodes
            };
        }

        function diagramNodeSize(node) {
            return {
                height: node.type === 'decision' ? 92 : 74,
                width: 180
            };
        }

        function diagramNodeCenter(node) {
            const size = diagramNodeSize(node);

            return {
                x: node.x + (size.width / 2),
                y: node.y + (size.height / 2)
            };
        }

        function diagramNodeEdgePoint(node, targetPoint) {
            const size = diagramNodeSize(node);
            const center = diagramNodeCenter(node);
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

        function diagramConnectorPath(fromNode, toNode) {
            const fromCenter = diagramNodeCenter(fromNode);
            const toCenter = diagramNodeCenter(toNode);
            const start = diagramNodeEdgePoint(fromNode, toCenter);
            const end = diagramNodeEdgePoint(toNode, fromCenter);
            const midX = start.x + ((end.x - start.x) / 2);

            return 'M ' + start.x + ' ' + start.y + ' C ' + midX + ' ' + start.y + ', ' + midX + ' ' + end.y + ', ' + end.x + ' ' + end.y;
        }

        function diagramBounds(diagramData) {
            let width = 760;
            let height = 280;

            diagramData.nodes.forEach(function (node) {
                const size = diagramNodeSize(node);
                width = Math.max(width, node.x + size.width + 36);
                height = Math.max(height, node.y + size.height + 36);
            });

            return {
                height: Math.ceil(height),
                width: Math.ceil(width)
            };
        }

        function diagramTextLines(value) {
            const words = String(value || '').replace(/\s+/g, ' ').trim().split(' ').filter(Boolean);
            const lines = [];
            let current = '';

            words.forEach(function (word) {
                const next = current ? current + ' ' + word : word;

                if (next.length > 24 && current) {
                    lines.push(current);
                    current = word;
                    return;
                }

                current = next;
            });

            if (current) {
                lines.push(current);
            }

            return (lines.length ? lines : ['Aktivitas']).slice(0, 3);
        }

        function renderDiagramShape(node) {
            const size = diagramNodeSize(node);
            const x = node.x;
            const y = node.y;
            const w = size.width;
            const h = size.height;

            if (node.type === 'terminator') {
                return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="' + (h / 2) + '" ry="' + (h / 2) + '" fill="#ffffff" stroke="#2563eb" stroke-width="2"></rect>';
            }

            if (node.type === 'decision') {
                return '<polygon points="' + (x + (w / 2)) + ',' + y + ' ' + (x + w) + ',' + (y + (h / 2)) + ' ' + (x + (w / 2)) + ',' + (y + h) + ' ' + x + ',' + (y + (h / 2)) + '" fill="#ffffff" stroke="#2563eb" stroke-width="2"></polygon>';
            }

            if (node.type === 'connector_internal') {
                return '<ellipse cx="' + (x + (w / 2)) + '" cy="' + (y + (h / 2)) + '" rx="' + (w / 2) + '" ry="' + (h / 2) + '" fill="#ffffff" stroke="#2563eb" stroke-width="2"></ellipse>';
            }

            if (node.type === 'connector_halaman') {
                return '<polygon points="' + (x + (w / 2)) + ',' + y + ' ' + (x + w) + ',' + (y + 26) + ' ' + (x + (w - 26)) + ',' + (y + h) + ' ' + (x + 26) + ',' + (y + h) + ' ' + x + ',' + (y + 26) + '" fill="#ffffff" stroke="#2563eb" stroke-width="2"></polygon>';
            }

            return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="10" ry="10" fill="#ffffff" stroke="#2563eb" stroke-width="2"></rect>';
        }

        function renderDiagramText(node) {
            const size = diagramNodeSize(node);
            const lines = diagramTextLines(node.label);
            const lineHeight = 15;
            const startY = node.y + (size.height / 2) - (((lines.length - 1) * lineHeight) / 2);

            return '<text x="' + (node.x + (size.width / 2)) + '" y="' + startY + '" text-anchor="middle" font-size="12" font-weight="700" fill="#0f172a">' +
                lines.map(function (line, index) {
                    return '<tspan x="' + (node.x + (size.width / 2)) + '" dy="' + (index === 0 ? 0 : lineHeight) + '">' + escapeHtml(line) + '</tspan>';
                }).join('') +
                '</text>';
        }

        function renderSopDiagramHtml(value) {
            const diagramData = normalizeDiagramData(value);
            const bounds = diagramBounds(diagramData);
            const nodeMap = {};

            diagramData.nodes.forEach(function (node) {
                nodeMap[node.id] = node;
            });

            const connectors = diagramData.connectors.map(function (connector) {
                const fromNode = nodeMap[connector.from];
                const toNode = nodeMap[connector.to];

                if (!fromNode || !toNode) {
                    return '';
                }

                return '<path class="sop-diagram-connector" d="' + diagramConnectorPath(fromNode, toNode) + '" fill="none" stroke="#2563eb" stroke-width="2.4" marker-end="url(#sopDiagramArrow)"></path>';
            }).join('');

            const nodes = diagramData.nodes.map(function (node) {
                return '<g class="sop-diagram-node">' + renderDiagramShape(node) + renderDiagramText(node) + '</g>';
            }).join('');

            return [
                '<svg class="sop-diagram-svg" xmlns="http://www.w3.org/2000/svg" width="' + bounds.width + '" height="' + bounds.height + '" viewBox="0 0 ' + bounds.width + ' ' + bounds.height + '" role="img" aria-label="Diagram Prosedur Pelaksanaan">',
                '<defs>',
                '<marker id="sopDiagramArrow" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto" markerUnits="strokeWidth">',
                '<path d="M0,0 L0,6 L9,3 z" fill="#2563eb"></path>',
                '</marker>',
                '</defs>',
                connectors,
                nodes,
                '</svg>'
            ].join('');
        }

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

        function safeCellSpan(value) {
            const numericValue = parseInt(value, 10);

            return Number.isInteger(numericValue) && numericValue > 1 && numericValue <= 12
                ? numericValue.toString()
                : null;
        }

        function safeDivClasses(element) {
            return Array.from(element.classList).filter(function (className) {
                return className === 'sop-diagram-block'
                    || /^sop-kop-(block|top|company|name|address|meta|meta-label|meta-separator|meta-value|title)$/.test(className);
            });
        }

        function safeSvgClass(element) {
            return Array.from(element.classList).filter(function (className) {
                return /^sop-diagram-(svg|connector|node)$/.test(className);
            }).join(' ');
        }

        function safeSvgId(value) {
            value = String(value || '').trim();

            return /^[a-zA-Z][a-zA-Z0-9_-]{0,80}$/.test(value) ? value : null;
        }

        function safeSvgNumber(value, min, max) {
            const numericValue = parseFloat(value);

            if (!Number.isFinite(numericValue) || numericValue < min || numericValue > max) {
                return null;
            }

            return String(Math.round(numericValue * 100) / 100);
        }

        function safeSvgColor(value) {
            value = String(value || '').trim();

            if (value === 'none' || /^#[0-9a-f]{3}([0-9a-f]{3})?$/i.test(value)) {
                return value;
            }

            return null;
        }

        function safeSvgPath(value) {
            value = String(value || '').trim();

            return value.length <= 6000 && /^[MmLlHhVvCcSsQqTtAaZz0-9,.\-\s]+$/.test(value)
                ? value
                : null;
        }

        function safeSvgPoints(value) {
            value = String(value || '').trim();

            return value.length <= 3000 && /^[0-9,.\-\s]+$/.test(value) ? value : null;
        }

        function safeSvgViewBox(value) {
            value = String(value || '').trim();

            return /^-?\d+(\.\d+)?\s+-?\d+(\.\d+)?\s+\d+(\.\d+)?\s+\d+(\.\d+)?$/.test(value)
                ? value
                : null;
        }

        function safeMarkerEnd(value) {
            value = String(value || '').trim();

            return /^url\(#[-_a-zA-Z0-9]+\)$/.test(value) ? value : null;
        }

        function safeSvgAttributes(element, tagName) {
            const attrs = {};
            const className = safeSvgClass(element);

            if (className) {
                attrs.class = className;
            }

            if (tagName === 'SVG') {
                attrs.xmlns = 'http://www.w3.org/2000/svg';
                attrs.width = safeSvgNumber(element.getAttribute('width'), 1, 3000) || '760';
                attrs.height = safeSvgNumber(element.getAttribute('height'), 1, 2400) || '280';
                attrs.viewBox = safeSvgViewBox(element.getAttribute('viewBox') || element.getAttribute('viewbox')) || ('0 0 ' + attrs.width + ' ' + attrs.height);
                attrs.role = 'img';
                attrs['aria-label'] = 'Diagram Prosedur Pelaksanaan';
            }

            if (tagName === 'MARKER') {
                attrs.id = safeSvgId(element.getAttribute('id')) || 'sopDiagramArrow';
                ['markerWidth', 'markerHeight', 'refX', 'refY'].forEach(function (name) {
                    const safeValue = safeSvgNumber(element.getAttribute(name) || element.getAttribute(name.toLowerCase()), 0, 100);

                    if (safeValue !== null) {
                        attrs[name] = safeValue;
                    }
                });
                attrs.orient = element.getAttribute('orient') === 'auto' ? 'auto' : 'auto';
                attrs.markerUnits = element.getAttribute('markerUnits') === 'strokeWidth' || element.getAttribute('markerunits') === 'strokeWidth'
                    ? 'strokeWidth'
                    : 'strokeWidth';
            }

            if (tagName === 'PATH') {
                const path = safeSvgPath(element.getAttribute('d'));

                if (path) {
                    attrs.d = path;
                }
            }

            if (tagName === 'POLYGON') {
                const points = safeSvgPoints(element.getAttribute('points'));

                if (points) {
                    attrs.points = points;
                }
            }

            if (tagName === 'RECT') {
                ['x', 'y', 'width', 'height', 'rx', 'ry'].forEach(function (name) {
                    const safeValue = safeSvgNumber(element.getAttribute(name), 0, 3000);

                    if (safeValue !== null) {
                        attrs[name] = safeValue;
                    }
                });
            }

            if (tagName === 'ELLIPSE') {
                ['cx', 'cy', 'rx', 'ry'].forEach(function (name) {
                    const safeValue = safeSvgNumber(element.getAttribute(name), 0, 3000);

                    if (safeValue !== null) {
                        attrs[name] = safeValue;
                    }
                });
            }

            if (tagName === 'TEXT' || tagName === 'TSPAN') {
                ['x', 'y', 'dy'].forEach(function (name) {
                    const safeValue = safeSvgNumber(element.getAttribute(name), -3000, 3000);

                    if (safeValue !== null) {
                        attrs[name] = safeValue;
                    }
                });

                if (['start', 'middle', 'end'].includes(element.getAttribute('text-anchor'))) {
                    attrs['text-anchor'] = element.getAttribute('text-anchor');
                }

                if (['normal', '600', '700', '800', 'bold'].includes(element.getAttribute('font-weight'))) {
                    attrs['font-weight'] = element.getAttribute('font-weight');
                }
            }

            ['fill', 'stroke'].forEach(function (name) {
                const color = safeSvgColor(element.getAttribute(name));

                if (color !== null) {
                    attrs[name] = color;
                }
            });

            ['stroke-width', 'font-size'].forEach(function (name) {
                const safeValue = safeSvgNumber(element.getAttribute(name), 0, 80);

                if (safeValue !== null) {
                    attrs[name] = safeValue;
                }
            });

            const markerEnd = safeMarkerEnd(element.getAttribute('marker-end'));

            if (markerEnd) {
                attrs['marker-end'] = markerEnd;
            }

            return attrs;
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

                const tagName = child.tagName.toUpperCase();

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
                const imgAttrs = tagName === 'IMG' && isSafeUrl(child.getAttribute('src'))
                    ? {
                        alt: String(child.getAttribute('alt') || '').slice(0, 255),
                        src: child.getAttribute('src'),
                        title: String(child.getAttribute('title') || '').slice(0, 255)
                    }
                    : null;
                const spanSymbol = tagName === 'SPAN' && sopSymbolNames.has(child.getAttribute('data-sop-symbol'))
                    ? child.getAttribute('data-sop-symbol')
                    : null;
                const spanClasses = tagName === 'SPAN'
                    ? Array.from(child.classList).filter(function (className) {
                        return className === 'sop-symbol-token'
                            || /^sop-symbol-token--(terminator|aktivitas|decision|dokumen|connector_halaman|connector_internal)$/.test(className);
                    })
                    : [];
                const colspan = (tagName === 'TD' || tagName === 'TH')
                    ? safeCellSpan(child.getAttribute('colspan'))
                    : null;
                const rowspan = (tagName === 'TD' || tagName === 'TH')
                    ? safeCellSpan(child.getAttribute('rowspan'))
                    : null;
                const divClasses = tagName === 'DIV' ? safeDivClasses(child) : [];
                const kopPageValue = tagName === 'DIV' && child.hasAttribute('data-sop-kop-page-value');
                const kopAttrs = tagName === 'DIV' && child.classList.contains('sop-kop-block')
                    ? {
                        title: child.getAttribute('data-title') || '',
                        code: child.getAttribute('data-code') || '',
                        revision: child.getAttribute('data-revision') || '',
                        effectiveDate: child.getAttribute('data-effective-date') || '',
                        page: child.getAttribute('data-page') || ''
                    }
                    : null;
                const diagramAttrs = tagName === 'DIV' && child.classList.contains('sop-diagram-block')
                    ? {
                        diagram: child.getAttribute('data-diagram') || ''
                    }
                    : null;
                const svgAttrs = ['SVG', 'DEFS', 'MARKER', 'PATH', 'RECT', 'POLYGON', 'ELLIPSE', 'TEXT', 'TSPAN', 'G'].includes(tagName)
                    ? safeSvgAttributes(child, tagName)
                    : {};

                Array.from(child.attributes).forEach(function (attribute) {
                    child.removeAttribute(attribute.name);
                });

                if (tagName === 'A' && href) {
                    child.setAttribute('href', href);
                    child.setAttribute('target', '_blank');
                    child.setAttribute('rel', 'noopener noreferrer');
                }

                if (tagName === 'IMG') {
                    if (!imgAttrs) {
                        child.remove();
                        return;
                    }

                    child.setAttribute('src', imgAttrs.src);
                    child.setAttribute('alt', imgAttrs.alt || 'Diagram Prosedur Pelaksanaan');

                    if (imgAttrs.title) {
                        child.setAttribute('title', imgAttrs.title);
                    }
                }

                if (tagName === 'SPAN' && spanClasses.length) {
                    child.setAttribute('class', spanClasses.join(' '));

                    if (spanSymbol) {
                        child.setAttribute('data-sop-symbol', spanSymbol);
                    }
                }

                if ((tagName === 'TD' || tagName === 'TH') && colspan) {
                    child.setAttribute('colspan', colspan);
                }

                if ((tagName === 'TD' || tagName === 'TH') && rowspan) {
                    child.setAttribute('rowspan', rowspan);
                }

                if (tagName === 'DIV' && divClasses.length) {
                    child.setAttribute('class', divClasses.join(' '));

                    if (kopAttrs) {
                        child.setAttribute('data-sop-kop', '1');
                        child.setAttribute('data-title', kopAttrs.title);
                        child.setAttribute('data-code', kopAttrs.code);
                        child.setAttribute('data-revision', kopAttrs.revision);
                        child.setAttribute('data-effective-date', kopAttrs.effectiveDate);
                        child.setAttribute('data-page', kopAttrs.page);
                    }

                    if (kopPageValue) {
                        child.setAttribute('data-sop-kop-page-value', '');
                    }

                    if (diagramAttrs) {
                        child.setAttribute('data-sop-diagram', '1');
                        child.setAttribute('data-diagram', diagramAttrs.diagram.slice(0, 50000));
                    }
                }

                Object.keys(svgAttrs).forEach(function (name) {
                    child.setAttribute(name, svgAttrs[name]);
                });

                if ((tagName === 'PATH' && !child.getAttribute('d')) || (tagName === 'POLYGON' && !child.getAttribute('points'))) {
                    child.remove();
                }
            });
        }

        function sanitizeHtml(html) {
            const template = document.createElement('template');

            template.innerHTML = html || '';
            sanitizeNode(template.content);

            return template.innerHTML.trim();
        }

        function hasVisibleHtml(html) {
            const template = document.createElement('template');

            template.innerHTML = html || '';

            if (template.content.querySelector('img, .sop-diagram-block, .sop-kop-block')) {
                return true;
            }

            return template.content.textContent.replace(/\s/g, '') !== '';
        }

        function hasHtml(value) {
            return /<\/?[a-z][\s\S]*>/i.test(value || '');
        }

        function editorHtml(instance) {
            if (instance.mode === 'document') {
                return instance.quill.root.innerHTML;
            }

            if (typeof instance.quill.getSemanticHTML === 'function') {
                return instance.quill.getSemanticHTML();
            }

            return instance.quill.root.innerHTML;
        }

        function updateKopPageCount(instance) {
            if (!instance || instance.mode !== 'document') {
                return;
            }

            const editor = instance.quill.root;
            const pageCount = Math.max(1, Math.ceil(editor.scrollHeight / sopPageHeightPx));
            const pageLabel = '1 dari ' + pageCount;

            editor.querySelectorAll('.sop-kop-block').forEach(function (kopBlock) {
                kopBlock.setAttribute('data-page', pageLabel);

                const pageValue = kopBlock.querySelector('[data-sop-kop-page-value]')
                    || kopBlock.querySelector('.sop-kop-meta-value:last-child');

                if (pageValue) {
                    pageValue.textContent = pageLabel;
                }
            });
        }

        function syncTextarea(textarea) {
            const instance = editors.get(textarea);

            if (!instance) {
                return;
            }

            updateKopPageCount(instance);

            const plainText = instance.quill.getText().replace(/\s/g, '');
            const html = sanitizeHtml(editorHtml(instance));

            textarea.value = plainText === '' && !hasVisibleHtml(html) ? '' : html;
        }

        function insertHtml(textarea, html) {
            const instance = editors.get(textarea);

            if (!instance) {
                return false;
            }

            const quill = instance.quill;
            const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };

            quill.clipboard.dangerouslyPasteHTML(range.index, sanitizeHtml(html), 'user');
            syncTextarea(textarea);

            return true;
        }

        function insertSopSymbol(textarea, symbol, label) {
            const instance = editors.get(textarea);

            if (!instance || !sopSymbolNames.has(symbol)) {
                return false;
            }

            const quill = instance.quill;
            const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };

            quill.insertEmbed(range.index, 'sopSymbol', {
                symbol: symbol,
                label: label
            }, 'user');
            quill.insertText(range.index + 1, ' ', 'user');
            quill.setSelection(range.index + 2, 0, 'silent');
            syncTextarea(textarea);

            return true;
        }

        function insertSopKop(textarea, value) {
            const instance = editors.get(textarea);

            if (!instance) {
                return false;
            }

            const quill = instance.quill;
            const range = quill.getSelection(true) || { index: 0, length: 0 };

            quill.insertEmbed(range.index, 'sopKop', normalizeKopData(value || {}), 'user');
            quill.insertText(range.index + 1, '\n', 'user');
            quill.setSelection(range.index + 2, 0, 'silent');
            updateKopPageCount(instance);
            syncTextarea(textarea);

            return true;
        }

        function existingDiagramBlot(quill) {
            const diagramBlock = quill.root.querySelector('.sop-diagram-block');

            return diagramBlock ? Quill.find(diagramBlock) : null;
        }

        function procedureHeadingIndex(quill) {
            const heading = Array.from(quill.root.querySelectorAll('h1, h2, h3')).find(function (element) {
                return element.textContent.trim().toLowerCase() === 'prosedur pelaksanaan';
            });

            if (!heading) {
                return null;
            }

            const blot = Quill.find(heading);

            return blot ? quill.getIndex(blot) + blot.length() : null;
        }

        function ensureProcedureHeading(quill) {
            const existingIndex = procedureHeadingIndex(quill);

            if (existingIndex !== null) {
                return existingIndex;
            }

            const title = 'Prosedur Pelaksanaan';
            const insertIndex = Math.max(0, quill.getLength() - 1);

            quill.insertText(insertIndex, title + '\n', 'user');
            quill.formatLine(insertIndex, title.length, 'header', 2, 'user');

            return insertIndex + title.length + 1;
        }

        function insertOrReplaceSopDiagram(textarea, value) {
            const instance = editors.get(textarea);

            if (!instance) {
                return false;
            }

            const quill = instance.quill;
            const diagramData = normalizeDiagramData(value || {});
            const existingBlot = existingDiagramBlot(quill);

            if (existingBlot) {
                const existingIndex = quill.getIndex(existingBlot);

                quill.deleteText(existingIndex, 1, 'user');

                if (diagramData.nodes.length) {
                    quill.insertEmbed(existingIndex, 'sopDiagram', diagramData, 'user');
                    quill.insertText(existingIndex + 1, '\n', 'user');
                }

                syncTextarea(textarea);

                return true;
            }

            if (!diagramData.nodes.length) {
                return true;
            }

            const insertIndex = ensureProcedureHeading(quill);

            quill.insertEmbed(insertIndex, 'sopDiagram', diagramData, 'user');
            quill.insertText(insertIndex + 1, '\n', 'user');
            quill.setSelection(insertIndex + 2, 0, 'silent');
            syncTextarea(textarea);

            return true;
        }

        function insertSopImage(textarea, url, alt) {
            const instance = editors.get(textarea);

            if (!instance || !isSafeUrl(url)) {
                return false;
            }

            const quill = instance.quill;
            const insertIndex = ensureProcedureHeading(quill);
            const imageHtml = '<p><img src="' + escapeHtml(url) + '" alt="' + escapeHtml(alt || 'Diagram Prosedur Pelaksanaan') + '"></p>';

            quill.clipboard.dangerouslyPasteHTML(insertIndex, sanitizeHtml(imageHtml), 'user');
            quill.setSelection(insertIndex + 1, 0, 'silent');
            syncTextarea(textarea);

            return true;
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
            const explicitScope = textarea
                ? textarea.closest('[data-rich-text-scope="explicit"]')
                : null;

            return textarea
                && textarea.dataset.richTextReady !== '1'
                && textarea.dataset.richText !== 'plain'
                && (!explicitScope || textarea.dataset.richText === 'editor')
                && !textarea.closest('[data-rich-text-scope="off"]')
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
            wrapper.classList.toggle('rich-text-field--document', textarea.dataset.richTextMode === 'document');
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
                    toolbar: textarea.dataset.richTextMode === 'document'
                        ? documentToolbarOptions
                        : toolbarOptions
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

            editors.set(textarea, {
                mode: textarea.dataset.richTextMode === 'document' ? 'document' : 'default',
                quill: quill
            });
            textarea.dataset.richTextReady = '1';

            quill.on('text-change', function () {
                updateKopPageCount(editors.get(textarea));
                syncTextarea(textarea);
            });

            if (textarea.matches('[data-sop-document-editor]')) {
                editorElement.addEventListener('dragover', function (event) {
                    if (event.dataTransfer && sopSymbolNames.has(event.dataTransfer.getData('text/plain'))) {
                        event.preventDefault();
                        event.dataTransfer.dropEffect = 'copy';
                    }
                });

                editorElement.addEventListener('drop', function (event) {
                    if (!event.dataTransfer) {
                        return;
                    }

                    const symbol = event.dataTransfer.getData('text/plain');

                    if (!sopSymbolNames.has(symbol)) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    insertSopSymbol(textarea, symbol, (window.VOpsSopSymbolLabels || {})[symbol]);
                });
            }

            updateKopPageCount(editors.get(textarea));
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
            insertHtml: insertHtml,
            insertOrReplaceSopDiagram: insertOrReplaceSopDiagram,
            insertSopImage: insertSopImage,
            insertSopKop: insertSopKop,
            insertSopSymbol: insertSopSymbol,
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
