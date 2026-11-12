<?php

namespace VentureDrake\LaravelCrm\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans the HTML of a logged email before it is stored and again before it
 * is shown.
 *
 * It is an allowlist: only the formatting tags below survive, every
 * attribute is dropped except a link's `href`, and that only for http, https
 * and mailto. Scripts, styles, images, frames and forms are removed with
 * their contents, so a pasted email can neither run code nor load anything
 * from a remote server.
 */
class EmailHtmlSanitizer
{
    /** Tags kept as they are (without attributes). */
    protected const ALLOWED = [
        'p', 'br', 'div', 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'hr', 'a',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
    ];

    /** Tags removed together with everything inside them. */
    protected const DROPPED = [
        'script', 'style', 'head', 'title', 'meta', 'link', 'base', 'iframe', 'frame', 'frameset',
        'object', 'embed', 'applet', 'form', 'input', 'button', 'select', 'option', 'textarea',
        'img', 'picture', 'source', 'svg', 'math', 'video', 'audio', 'canvas', 'template', 'noscript',
    ];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="crm-email-root">'.$html.'</div>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('crm-email-root');

        if (! $root) {
            return e(strip_tags($html));
        }

        $this->cleanChildren($root);
        $this->dropLeadingRules($root);

        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    /**
     * The email as plain text, with line breaks where blocks ended.
     */
    public function toText(?string $html): string
    {
        $html = $this->clean($html);

        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/(p|div|li|tr|h[1-6]|blockquote|pre)>/i', "\n", $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Plain text as safe HTML, one paragraph per blank-line-separated block.
     */
    public function fromText(?string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));

        if ($text === '') {
            return '';
        }

        return collect(preg_split("/\n{2,}/", $text))
            ->map(fn ($block) => '<p>'.nl2br(e($block), false).'</p>')
            ->implode('');
    }

    /**
     * Remove a horizontal rule that opens the body. A mail client puts one
     * above the message a reply quotes, so a pasted reply starts with it,
     * and a rule at the very start of an editable box cannot be selected to
     * delete it by hand.
     */
    protected function dropLeadingRules(DOMNode $root): void
    {
        $node = $root;

        while ($node) {
            $first = null;

            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE && trim($child->nodeValue, " \t\n\r\u{00A0}") === '') {
                    continue;
                }

                $first = $child;

                break;
            }

            if (! $first instanceof DOMElement) {
                return;
            }

            if (strtolower($first->nodeName) === 'hr') {
                $first->parentNode->removeChild($first);
                $node = $root;

                continue;
            }

            $node = $first;
        }
    }

    protected function cleanChildren(DOMNode $node): void
    {
        // Snapshot first: the live list shifts as nodes are removed.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                // Comments, processing instructions, CDATA.
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROPPED, true)) {
                $node->removeChild($child);

                continue;
            }

            $this->cleanChildren($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                // Unknown wrapper: keep what is inside it, drop the tag.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->nodeName);
            }

            if ($tag === 'a' && preg_match('/^(https?:|mailto:)/i', $href)) {
                $child->setAttribute('href', $href);
                $child->setAttribute('target', '_blank');
                $child->setAttribute('rel', 'noopener noreferrer nofollow');
            }
        }
    }
}
