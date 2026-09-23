<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a'];

    public static function clean(?string $html): ?string
    {
        if (blank($html)) return null;

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        self::sanitizeNode($document->documentElement);

        $result = '';
        foreach ($document->documentElement->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                if (! in_array(strtolower($child->tagName), self::ALLOWED_TAGS, true)) {
                    $node->replaceChild($node->ownerDocument->createTextNode($child->textContent), $child);
                    continue;
                }

                foreach (iterator_to_array($child->attributes) as $attribute) {
                    $allowedLink = strtolower($child->tagName) === 'a' && in_array(strtolower($attribute->name), ['href', 'title'], true);
                    if (! $allowedLink || ($attribute->name === 'href' && ! preg_match('/^(https?:|mailto:|tel:|\/|#)/i', $attribute->value))) {
                        $child->removeAttribute($attribute->name);
                    }
                }
            }
            self::sanitizeNode($child);
        }
    }
}
