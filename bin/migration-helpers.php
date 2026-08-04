<?php
/**
 * Shared Helpers for Gutenberg Migration & FSE Style Sanitization
 */

if (!function_exists('sanitize_inline_styles_fse')) {
    /**
     * Filters inline CSS against a strict FSE Property Allowlist.
     * Retains layout, grid, alignment, and sizing properties.
     * Discards legacy font, color, margin, and padding properties.
     *
     * @param string $style_string
     * @return string Filtered CSS style string.
     */
    function sanitize_inline_styles_fse($style_string) {
        if (empty(trim($style_string))) {
            return '';
        }

        $allowlist = [
            'flex-basis',
            'flex-grow',
            'flex-shrink',
            'flex-direction',
            'grid-template-columns',
            'width',
            'height',
            'min-height',
            'max-width',
            'aspect-ratio',
            'object-fit',
            'vertical-align',
            'text-align'
        ];

        $declarations = explode(';', $style_string);
        $retained = [];

        foreach ($declarations as $decl) {
            $decl = trim($decl);
            if (empty($decl)) {
                continue;
            }

            $parts = explode(':', $decl, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $prop = strtolower(trim($parts[0]));
            $val = trim($parts[1]);

            if (in_array($prop, $allowlist, true)) {
                $retained[] = "{$prop}: {$val}";
            }
        }

        if (empty($retained)) {
            return '';
        }

        return implode('; ', $retained) . ';';
    }
}

if (!function_exists('eka_init_log_file')) {
    /**
     * Initializes and truncates log file on script startup.
     *
     * @param string $log_path
     * @return void
     */
    function eka_init_log_file($log_path) {
        $dir = dirname($log_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($log_path, '');
    }
}

if (!function_exists('eka_validate_blocks_ast')) {
    /**
     * Validates block content structure using WordPress parse_blocks() if available,
     * or a fallback AST balanced block check.
     *
     * @param string $content
     * @return bool True if valid, false if invalid or malformed AST.
     */
    function eka_validate_blocks_ast($content) {
        if (empty(trim($content))) {
            return true;
        }

        if (function_exists('parse_blocks')) {
            $blocks = parse_blocks($content);
            if (empty($blocks)) {
                return false;
            }
            return true;
        }

        // Lightweight AST balanced block validation
        $stack = [];
        preg_match_all('/<!--\s+(?<type>\/)?wp:(?<name>[a-z0-9\/-]+)(?:\s+(?<attrs>\{.*?\}))?\s+(?<selfclosing>\/)?-->/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $is_close = !empty($match['type']);
            $block_name = $match['name'];
            $is_self_closing = isset($match['selfclosing']) && !empty($match['selfclosing']);

            if ($is_self_closing) {
                continue;
            }

            if (!$is_close) {
                // Opening block tag
                $stack[] = $block_name;
            } else {
                // Closing block tag
                if (empty($stack)) {
                    return false; // Unexpected close tag
                }
                $last = array_pop($stack);
                if ($last !== $block_name) {
                    return false; // Mismatched block closing tag
                }
            }
        }

        return empty($stack);
    }
}
