<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichTextSanitizer
{
    private const ALLOWED_TAGS = [
        'p','br','div','span','font','strong','b','em','i','u','s','strike','h1','h2','h3','h4','h5','h6',
        'ul','ol','li','a','img','video','source','iframe','blockquote','pre','code','hr',
        'table','thead','tbody','tfoot','tr','th','td','figure','figcaption','sup','sub',
    ];

    private const DROP_WITH_CONTENT = ['script','style','object','applet','form','input','button','textarea','select','option','meta','link','base'];

    private const GLOBAL_ATTRIBUTES = ['class','style','title','dir','lang','align'];

    private const TAG_ATTRIBUTES = [
        'a' => ['href','target','rel'],
        'font' => ['face','size','color'],
        'img' => ['src','alt','width','height','loading'],
        'video' => ['src','controls','preload','poster','width','height','playsinline'],
        'source' => ['src','type'],
        'iframe' => ['src','width','height','loading','allow','allowfullscreen','frameborder','referrerpolicy'],
        'td' => ['colspan','rowspan'],
        'th' => ['colspan','rowspan','scope'],
    ];

    private const STYLE_PROPERTIES = [
        'font-family','font-size','font-weight','font-style','text-decoration','text-decoration-line',
        'color','background-color','text-align','line-height','letter-spacing','word-spacing',
        'margin','margin-top','margin-right','margin-bottom','margin-left',
        'padding','padding-top','padding-right','padding-bottom','padding-left',
        'width','max-width','min-width','height','max-height','min-height',
        'border','border-width','border-style','border-color','border-radius',
        'display','vertical-align','white-space','overflow-wrap','list-style-type',
    ];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        if (! class_exists(DOMDocument::class)) {
            return self::fallback($html);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="utf-8" ?><div id="rich-root">'.$html.'</div>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('rich-root');
        if (! $root) {
            return self::fallback($html);
        }

        self::sanitizeChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output) ?: null;
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            /** @var DOMElement $child */
            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::sanitizeChildren($child);
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::sanitizeChildren($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(self::GLOBAL_ATTRIBUTES, self::TAG_ATTRIBUTES[$tag] ?? []);
        $remove = [];

        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (str_starts_with($name, 'on') || ! in_array($name, $allowed, true)) {
                $remove[] = $attribute->name;
                continue;
            }

            if ($name === 'style') {
                $cleanStyle = self::cleanStyle($value);
                if ($cleanStyle === '') {
                    $remove[] = $attribute->name;
                } else {
                    $element->setAttribute('style', $cleanStyle);
                }
                continue;
            }

            if (in_array($name, ['href','src','poster'], true) && ! self::safeUrl($value, $tag === 'a')) {
                $remove[] = $attribute->name;
            }
        }

        foreach ($remove as $attribute) {
            $element->removeAttribute($attribute);
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $href = $element->getAttribute('href');
            if (preg_match('/^https?:\/\//i', $href)) {
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }

        if ($tag === 'iframe') {
            $src = $element->getAttribute('src');
            if ($src === '' || ! preg_match('/^https:\/\//i', $src)) {
                $element->parentNode?->removeChild($element);
                return;
            }
            $element->setAttribute('loading', 'lazy');
            $element->setAttribute('allowfullscreen', 'allowfullscreen');
        }

        if ($tag === 'img') {
            $element->setAttribute('loading', 'lazy');
        }

        if ($tag === 'video') {
            $element->setAttribute('controls', 'controls');
            $element->setAttribute('preload', 'metadata');
        }
    }

    private static function cleanStyle(string $style): string
    {
        if ($style === '' || preg_match('/expression\s*\(|javascript\s*:|url\s*\(/i', $style)) {
            return '';
        }

        $clean = [];
        foreach (explode(';', $style) as $rule) {
            if (! str_contains($rule, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $rule, 2));
            $property = strtolower($property);

            if (! in_array($property, self::STYLE_PROPERTIES, true) || $value === '') {
                continue;
            }

            if (preg_match('/[<>]|javascript\s*:|expression\s*\(|url\s*\(/i', $value)) {
                continue;
            }

            $clean[] = $property.': '.$value;
        }

        return implode('; ', $clean);
    }

    private static function safeUrl(string $url, bool $allowLinkSchemes = false): bool
    {
        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        if ($allowLinkSchemes && preg_match('/^(mailto:|tel:)/i', $url)) {
            return true;
        }

        return (bool) preg_match('/^https?:\/\//i', $url);
    }

    private static function fallback(string $html): ?string
    {
        $html = preg_replace('#<(script|style|object|applet|form|input|button|textarea|select|option|meta|link|base)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? '';
        $allowed = '<p><br><div><span><font><strong><b><em><i><u><s><strike><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><video><source><iframe><blockquote><pre><code><hr><table><thead><tbody><tfoot><tr><th><td><figure><figcaption><sup><sub>';
        $html = strip_tags($html, $allowed);

        return trim($html) ?: null;
    }
}
