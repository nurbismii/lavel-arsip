<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class RichText
{
    private const ALLOWED_TAGS = [
        'a',
        'blockquote',
        'br',
        'em',
        'i',
        'li',
        'ol',
        'p',
        's',
        'strong',
        'u',
        'ul',
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

        if ($tag === 'a') {
            $href = trim((string) $element->getAttribute('href'));
            $href = self::safeUrl($href) ? $href : null;
        }

        while ($element->attributes->length > 0) {
            $element->removeAttribute($element->attributes->item(0)->name);
        }

        if ($tag === 'a' && $href) {
            $element->setAttribute('href', $href);
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
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

    private static function containsHtml(string $value): bool
    {
        return (bool) preg_match('/<\/?[a-z][\s\S]*>/i', $value);
    }

    private static function hasVisibleText(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\xC2\xA0/u', ' ', $text);

        return trim((string) $text) !== '';
    }
}
