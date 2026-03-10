<?php

/**
 * Utility class for parsing HTML and extracting/injecting links
 *
 * @package    AI_Internal_Linker
 * @subpackage AI_Internal_Linker/includes
 */

class AIL_HTMLParser
{
    /**
     * Safely replace a phrase with a link without breaking HTML or existing links.
     *
     * @param string $content The HTML content
     * @param string $phrase The exact phrase to replace
     * @param string $url The target URL
     * @param array $args Optional arguments (link_once, etc)
     * @return string The modified HTML content
     */
    public static function replace_phrase($content, $phrase, $url, $args = array())
    {
        $nofollow = get_option('ail_nofollow', false) ? ' rel="nofollow"' : '';
        $target_blank = get_option('ail_target_blank', false) ? ' target="_blank"' : '';

        // Safely split content into HTML tags (including multi-line comments) and text nodes
        // <!--.*?--> matches comments robustly including Gutenberg blocks with JSON containing >
        // <[^>]+> matches regular HTML tags
        $parts = preg_split('/(<!--.*?-->|<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        // Tags where we MUST NOT inject links
        $forbidden_tags = array('a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'script', 'style', 'pre', 'code', 'textarea', 'figcaption');
        $tag_stack = array(); // Keep track of open tags
        $replaced = false;

        $phrase = trim($phrase);
        // Normalize phrase by replacing non-breaking spaces with standard space
        $phrase = str_replace(array('&nbsp;', "\xC2\xA0"), ' ', $phrase);
        $escaped_phrase = preg_quote($phrase, '/');

        // Match phrase with word boundaries, supporting unicode/vietnamese
        $pattern = '/(?<![\p{L}\p{N}])(' . $escaped_phrase . ')(?![\p{L}\p{N}])/ui';

        $link_once = isset($args['link_once']) ? $args['link_once'] : true;

        foreach ($parts as $i => $part) {
            // Is it an HTML tag or comment?
            if (preg_match('/^(<!--|<)/', $part)) {
                // If it's a comment, just skip.
                if (strpos($part, '<!--') === 0) {
                    continue;
                }

                // Track opening/closing of tags
                if (preg_match('/^<(\/)?([a-zA-Z0-9\-]+)([^>]*)>$/', $part, $matches)) {
                    $is_closing = ($matches[1] === '/');
                    $tag_name = strtolower($matches[2]);

                    if (in_array($tag_name, $forbidden_tags)) {
                        if (!$is_closing) {
                            $tag_stack[] = $tag_name;
                        } else {
                            // Pop from stack if closing
                            $idx = array_search($tag_name, array_reverse($tag_stack, true));
                            if ($idx !== false) {
                                unset($tag_stack[$idx]);
                            }
                        }
                    }
                }
                continue;
            }

            // It's a text node. Check if we are inside any forbidden tag
            if (!empty($tag_stack)) {
                continue;
            }

            if ($link_once && $replaced) {
                continue; // Skip if we only want to link once per post and we already did
            }

            // Normalize text node for matching, but we need to keep original format for valid replacement
            // Workaround: We match on original $part, but if there are multiple whitespaces mapping to one space,
            // we might miss it. For now, matching standard spaces.
            if (preg_match($pattern, $part)) {
                $link_tag = '<a href="' . esc_url($url) . '"' . $nofollow . $target_blank . ' data-ail-tracked="1">$1</a>';
                $parts[$i] = preg_replace($pattern, $link_tag, $part, ($link_once ? 1 : -1));
                $replaced = true;
            }
        }

        return implode('', $parts);
    }
}
