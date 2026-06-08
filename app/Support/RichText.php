<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class RichText
{
    private const ALLOWED_TAGS = [
        'a',
        'blockquote',
        'br',
        'defs',
        'div',
        'ellipse',
        'em',
        'g',
        'h1',
        'h2',
        'h3',
        'i',
        'img',
        'li',
        'marker',
        'ol',
        'p',
        'path',
        'polygon',
        'rect',
        's',
        'span',
        'strong',
        'svg',
        'table',
        'tbody',
        'td',
        'text',
        'th',
        'thead',
        'tr',
        'tspan',
        'u',
        'ul',
    ];

    private const SVG_TAGS = [
        'defs',
        'ellipse',
        'g',
        'marker',
        'path',
        'polygon',
        'rect',
        'svg',
        'text',
        'tspan',
    ];

    private const REMOVE_WITH_CONTENT = [
        'iframe',
        'script',
        'style',
    ];

    public static function sanitize($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (!self::containsHtml($value)) {
            return $value;
        }

        if (!class_exists(\DOMDocument::class)) {
            $fallback = trim(strip_tags($value));

            return self::hasVisibleText($fallback) ? $fallback : null;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rich-text-root">' . $value . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('rich-text-root');

        if (!$root) {
            return null;
        }

        self::sanitizeChildren($root);

        $html = '';

        foreach ($root->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        $html = trim($html);

        return self::hasVisibleText($html) ? $html : null;
    }

    public static function sanitizeFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::sanitize($data[$field]);
            }
        }

        return $data;
    }

    public static function render($value): HtmlString
    {
        $value = trim((string) $value);

        if ($value === '') {
            return new HtmlString('');
        }

        $html = self::sanitize($value);

        if ($html === null) {
            return new HtmlString('');
        }

        if (!self::containsHtml($html)) {
            return new HtmlString(nl2br(e($html), false));
        }

        return new HtmlString($html);
    }

    private static function sanitizeChildren(\DOMNode $node): void
    {
        for ($child = $node->firstChild; $child !== null;) {
            $next = $child->nextSibling;

            if ($child instanceof \DOMElement) {
                self::sanitizeElement($child);
            } elseif (!($child instanceof \DOMText)) {
                $node->removeChild($child);
            }

            $child = $next;
        }
    }

    private static function sanitizeElement(\DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
            if ($element->parentNode) {
                $element->parentNode->removeChild($element);
            }

            return;
        }

        self::sanitizeChildren($element);

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            self::unwrap($element);

            return;
        }

        self::sanitizeAttributes($element, $tag);
    }

    private static function sanitizeAttributes(\DOMElement $element, string $tag): void
    {
        $href = null;
        $spanSymbol = null;
        $spanClasses = [];
        $divClasses = [];
        $kopAttrs = [];
        $kopPageValue = false;
        $diagramAttrs = [];
        $svgAttrs = [];
        $colspan = null;
        $rowspan = null;

        if ($tag === 'a') {
            $href = trim((string) $element->getAttribute('href'));
            $href = self::safeUrl($href) ? $href : null;
        }

        $imgAttrs = [];

        if ($tag === 'img') {
            $src = trim((string) $element->getAttribute('src'));

            if (self::safeUrl($src)) {
                $imgAttrs = [
                    'src' => $src,
                    'alt' => self::safeShortText((string) $element->getAttribute('alt')),
                    'title' => self::safeShortText((string) $element->getAttribute('title')),
                ];
            }
        }

        if ($tag === 'span') {
            $symbol = trim((string) $element->getAttribute('data-sop-symbol'));
            $spanSymbol = self::safeSopSymbol($symbol) ? $symbol : null;
            $spanClasses = array_values(array_filter(
                preg_split('/\s+/', trim((string) $element->getAttribute('class'))) ?: [],
                function (string $className) {
                    return self::safeSopSymbolClass($className);
                }
            ));
        }

        if ($tag === 'div') {
            $classes = preg_split('/\s+/', trim((string) $element->getAttribute('class'))) ?: [];
            $kopPageValue = $element->hasAttribute('data-sop-kop-page-value');
            $divClasses = array_values(array_filter($classes, function (string $className) {
                return self::safeSopKopClass($className);
            }));

            if (in_array('sop-kop-block', $divClasses, true)) {
                $kopAttrs = [
                    'data-title' => self::safeShortText((string) $element->getAttribute('data-title')),
                    'data-code' => self::safeShortText((string) $element->getAttribute('data-code')),
                    'data-revision' => self::safeShortText((string) $element->getAttribute('data-revision')),
                    'data-effective-date' => self::safeShortText((string) $element->getAttribute('data-effective-date')),
                    'data-page' => self::safeShortText((string) $element->getAttribute('data-page')),
                ];
            }

            if (in_array('sop-diagram-block', $divClasses, true)) {
                $diagramAttrs = [
                    'data-diagram' => self::safeLongText((string) $element->getAttribute('data-diagram')),
                ];
            }
        }

        if (in_array($tag, ['td', 'th'], true)) {
            $colspan = self::safeCellSpan((string) $element->getAttribute('colspan'));
            $rowspan = self::safeCellSpan((string) $element->getAttribute('rowspan'));
        }

        if (in_array($tag, self::SVG_TAGS, true)) {
            $svgAttrs = self::safeSvgAttributes($element, $tag);
        }

        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $attributes[] = [
                'localName' => $attribute->localName,
                'name' => $attribute->name,
                'namespaceURI' => $attribute->namespaceURI,
            ];
        }

        foreach ($attributes as $attribute) {
            if ($attribute['namespaceURI']) {
                $element->removeAttributeNS($attribute['namespaceURI'], $attribute['localName']);
                continue;
            }

            $element->removeAttribute($attribute['name']);
        }

        if ($tag === 'a' && $href) {
            $element->setAttribute('href', $href);
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        if ($tag === 'img') {
            if (empty($imgAttrs)) {
                if ($element->parentNode) {
                    $element->parentNode->removeChild($element);
                }

                return;
            }

            $element->setAttribute('src', $imgAttrs['src']);
            $element->setAttribute('alt', $imgAttrs['alt'] ?: 'Diagram Prosedur Pelaksanaan');

            if ($imgAttrs['title'] !== '') {
                $element->setAttribute('title', $imgAttrs['title']);
            }
        }

        if ($tag === 'span' && !empty($spanClasses)) {
            $element->setAttribute('class', implode(' ', $spanClasses));

            if ($spanSymbol) {
                $element->setAttribute('data-sop-symbol', $spanSymbol);
            }
        }

        if ($tag === 'div' && !empty($divClasses)) {
            $element->setAttribute('class', implode(' ', $divClasses));

            if (in_array('sop-kop-block', $divClasses, true)) {
                $element->setAttribute('data-sop-kop', '1');

                foreach ($kopAttrs as $name => $value) {
                    $element->setAttribute($name, $value);
                }
            }

            if ($kopPageValue) {
                $element->setAttribute('data-sop-kop-page-value', '');
            }

            if (!empty($diagramAttrs)) {
                $element->setAttribute('data-sop-diagram', '1');
                $element->setAttribute('data-diagram', $diagramAttrs['data-diagram']);
            }
        }

        foreach ($svgAttrs as $name => $value) {
            $element->setAttribute($name, $value);
        }

        if (($tag === 'path' && !$element->hasAttribute('d')) || ($tag === 'polygon' && !$element->hasAttribute('points'))) {
            if ($element->parentNode) {
                $element->parentNode->removeChild($element);
            }
        }

        if (in_array($tag, ['td', 'th'], true) && $colspan) {
            $element->setAttribute('colspan', $colspan);
        }

        if (in_array($tag, ['td', 'th'], true) && $rowspan) {
            $element->setAttribute('rowspan', $rowspan);
        }
    }

    private static function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (!$parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function safeUrl(string $url): bool
    {
        if ($url === '' || substr($url, 0, 1) === '#' || substr($url, 0, 1) === '/') {
            return $url !== '';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https', 'mailto', 'tel'], true);
    }

    private static function safeSopSymbol(string $symbol): bool
    {
        return in_array($symbol, [
            'terminator',
            'aktivitas',
            'decision',
            'dokumen',
            'connector_halaman',
            'connector_internal',
        ], true);
    }

    private static function safeSopSymbolClass(string $className): bool
    {
        return $className === 'sop-symbol-token'
            || (bool) preg_match('/^sop-symbol-token--(terminator|aktivitas|decision|dokumen|connector_halaman|connector_internal)$/', $className);
    }

    private static function safeSopKopClass(string $className): bool
    {
        return $className === 'sop-diagram-block'
            || (bool) preg_match('/^sop-kop-(block|top|company|name|address|meta|meta-label|meta-separator|meta-value|title)$/', $className);
    }

    private static function safeShortText(string $value): string
    {
        return mb_substr(trim(strip_tags($value)), 0, 255);
    }

    private static function safeLongText(string $value): string
    {
        return mb_substr(trim(strip_tags($value)), 0, 50000);
    }

    private static function safeSvgAttributes(\DOMElement $element, string $tag): array
    {
        $attrs = [];
        $className = self::safeSvgClass((string) $element->getAttribute('class'));

        if ($className !== '') {
            $attrs['class'] = $className;
        }

        if ($tag === 'svg') {
            $width = self::safeSvgNumber((string) $element->getAttribute('width'), 1, 3000) ?: '760';
            $height = self::safeSvgNumber((string) $element->getAttribute('height'), 1, 2400) ?: '280';

            $attrs['xmlns'] = 'http://www.w3.org/2000/svg';
            $attrs['width'] = $width;
            $attrs['height'] = $height;
            $attrs['viewBox'] = self::safeSvgViewBox((string) ($element->getAttribute('viewBox') ?: $element->getAttribute('viewbox')))
                ?: '0 0 ' . $width . ' ' . $height;
            $attrs['role'] = 'img';
            $attrs['aria-label'] = 'Diagram Prosedur Pelaksanaan';
        }

        if ($tag === 'marker') {
            $attrs['id'] = self::safeSvgId((string) $element->getAttribute('id')) ?: 'sopDiagramArrow';
            foreach (['markerWidth', 'markerHeight', 'refX', 'refY'] as $name) {
                $value = self::safeSvgNumber((string) ($element->getAttribute($name) ?: $element->getAttribute(strtolower($name))), 0, 100);

                if ($value !== null) {
                    $attrs[$name] = $value;
                }
            }
            $attrs['orient'] = 'auto';
            $attrs['markerUnits'] = 'strokeWidth';
        }

        if ($tag === 'path') {
            $path = self::safeSvgPath((string) $element->getAttribute('d'));

            if ($path !== null) {
                $attrs['d'] = $path;
            }
        }

        if ($tag === 'polygon') {
            $points = self::safeSvgPoints((string) $element->getAttribute('points'));

            if ($points !== null) {
                $attrs['points'] = $points;
            }
        }

        if ($tag === 'rect') {
            foreach (['x', 'y', 'width', 'height', 'rx', 'ry'] as $name) {
                $value = self::safeSvgNumber((string) $element->getAttribute($name), 0, 3000);

                if ($value !== null) {
                    $attrs[$name] = $value;
                }
            }
        }

        if ($tag === 'ellipse') {
            foreach (['cx', 'cy', 'rx', 'ry'] as $name) {
                $value = self::safeSvgNumber((string) $element->getAttribute($name), 0, 3000);

                if ($value !== null) {
                    $attrs[$name] = $value;
                }
            }
        }

        if (in_array($tag, ['text', 'tspan'], true)) {
            foreach (['x', 'y', 'dy'] as $name) {
                $value = self::safeSvgNumber((string) $element->getAttribute($name), -3000, 3000);

                if ($value !== null) {
                    $attrs[$name] = $value;
                }
            }

            if (in_array($element->getAttribute('text-anchor'), ['start', 'middle', 'end'], true)) {
                $attrs['text-anchor'] = $element->getAttribute('text-anchor');
            }

            if (in_array($element->getAttribute('font-weight'), ['normal', '600', '700', '800', 'bold'], true)) {
                $attrs['font-weight'] = $element->getAttribute('font-weight');
            }
        }

        foreach (['fill', 'stroke'] as $name) {
            $color = self::safeSvgColor((string) $element->getAttribute($name));

            if ($color !== null) {
                $attrs[$name] = $color;
            }
        }

        foreach (['stroke-width', 'font-size'] as $name) {
            $value = self::safeSvgNumber((string) $element->getAttribute($name), 0, 80);

            if ($value !== null) {
                $attrs[$name] = $value;
            }
        }

        $markerEnd = self::safeMarkerEnd((string) $element->getAttribute('marker-end'));

        if ($markerEnd !== null) {
            $attrs['marker-end'] = $markerEnd;
        }

        return $attrs;
    }

    private static function safeSvgClass(string $className): string
    {
        $classes = preg_split('/\s+/', trim($className)) ?: [];
        $classes = array_values(array_filter($classes, function (string $className) {
            return (bool) preg_match('/^sop-diagram-(svg|connector|node)$/', $className);
        }));

        return implode(' ', $classes);
    }

    private static function safeSvgId(string $value): ?string
    {
        $value = trim($value);

        return preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,80}$/', $value) ? $value : null;
    }

    private static function safeSvgNumber(string $value, float $min, float $max): ?string
    {
        if (!is_numeric($value)) {
            return null;
        }

        $numericValue = (float) $value;

        if ($numericValue < $min || $numericValue > $max) {
            return null;
        }

        return (string) round($numericValue, 2);
    }

    private static function safeSvgColor(string $value): ?string
    {
        $value = trim($value);

        if ($value === 'none' || preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $value)) {
            return $value;
        }

        return null;
    }

    private static function safeSvgPath(string $value): ?string
    {
        $value = trim($value);

        return strlen($value) <= 6000 && preg_match('/^[MmLlHhVvCcSsQqTtAaZz0-9,.\-\s]+$/', $value)
            ? $value
            : null;
    }

    private static function safeSvgPoints(string $value): ?string
    {
        $value = trim($value);

        return strlen($value) <= 3000 && preg_match('/^[0-9,.\-\s]+$/', $value) ? $value : null;
    }

    private static function safeSvgViewBox(string $value): ?string
    {
        $value = trim($value);

        return preg_match('/^-?\d+(\.\d+)?\s+-?\d+(\.\d+)?\s+\d+(\.\d+)?\s+\d+(\.\d+)?$/', $value)
            ? $value
            : null;
    }

    private static function safeMarkerEnd(string $value): ?string
    {
        $value = trim($value);

        return preg_match('/^url\(#[-_a-zA-Z0-9]+\)$/', $value) ? $value : null;
    }

    private static function safeCellSpan(string $value): ?string
    {
        $value = (int) $value;

        return $value > 1 && $value <= 12 ? (string) $value : null;
    }

    private static function containsHtml(string $value): bool
    {
        return (bool) preg_match('/<\/?[a-z][\s\S]*>/i', $value);
    }

    private static function hasVisibleText(string $html): bool
    {
        if (preg_match('/<(img|svg)\b/i', $html)) {
            return true;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\xC2\xA0/u', ' ', $text);

        return trim((string) $text) !== '';
    }
}
