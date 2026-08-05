<?php
/**
 * Shared Helpers for Gutenberg Migration & FSE Style Sanitization
 */

if (!function_exists('eka_get_db_config')) {
    /**
     * Dynamically extracts database credentials from WordPress environment constants,
     * environment variables, or wp-config.php without hardcoded values.
     *
     * @return array Array with keys 'host', 'user', 'pass', 'name'.
     */
    function eka_get_db_config() {
        if (defined('DB_NAME')) {
            return [
                'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
                'user' => defined('DB_USER') ? DB_USER : 'root',
                'pass' => defined('DB_PASSWORD') ? DB_PASSWORD : '',
                'name' => DB_NAME,
            ];
        }

        if (getenv('DB_NAME')) {
            return [
                'host' => getenv('DB_HOST') ?: 'localhost',
                'user' => getenv('DB_USER') ?: 'root',
                'pass' => getenv('DB_PASSWORD') ?: '',
                'name' => getenv('DB_NAME'),
            ];
        }

        $possible_paths = [
            dirname(__DIR__, 3) . '/wp-config.php',
            '/var/www/backstage.ekalexandria.org/public/wp-config.php',
        ];

        $config = ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'name' => 'backstage_eka'];

        foreach ($possible_paths as $wp_config_path) {
            if (!file_exists($wp_config_path)) {
                continue;
            }
            $content = file_get_contents($wp_config_path);
            $keys = ['name' => 'DB_NAME', 'user' => 'DB_USER', 'pass' => 'DB_PASSWORD', 'host' => 'DB_HOST'];
            foreach ($keys as $config_key => $const_name) {
                if (preg_match("/define\(\s*['\"]" . $const_name . "['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $m)) {
                    $config[$config_key] = $m[1];
                }
            }
            break;
        }

        return $config;
    }
}

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
            'flex-basis', 'flex-grow', 'flex-shrink', 'flex-direction',
            'grid-template-columns', 'width', 'height', 'min-height',
            'max-width', 'aspect-ratio', 'object-fit', 'vertical-align', 'text-align'
        ];

        $declarations = array_filter(array_map('trim', explode(';', $style_string)));
        $retained = [];

        foreach ($declarations as $decl) {
            $parts = array_map('trim', explode(':', $decl, 2));
            if (count($parts) === 2 && in_array(strtolower($parts[0]), $allowlist, true)) {
                $retained[] = strtolower($parts[0]) . ": {$parts[1]}";
            }
        }

        return empty($retained) ? '' : implode('; ', $retained) . ';';
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
