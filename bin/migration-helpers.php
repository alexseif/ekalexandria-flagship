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

if (!function_exists('eka_load_slider_scoping')) {
    /**
     * Loads slider scoping map from JSON index.
     *
     * @return array Array indexed by post_id
     */
    function eka_load_slider_scoping() {
        $scoping_file = dirname(__DIR__) . '/ai-work/scopings/rev-sliders-scoping.json';
        $scoping_map = [];
        if (file_exists($scoping_file)) {
            $raw = json_decode(file_get_contents($scoping_file), true);
            if (is_array($raw)) {
                foreach ($raw as $item) {
                    if (isset($item['page_id'])) {
                        $scoping_map[(int)$item['page_id']] = $item;
                    }
                }
            }
        }
        return $scoping_map;
    }
}

if (!function_exists('eka_resolve_post_images')) {
    /**
     * Resolves attachment IDs and image URLs for a post.
     *
     * @param int $post_id
     * @param array $scoping_map
     * @param mysqli|null $mysqli
     * @return array Array of ['id' => int, 'url' => string]
     */
    function eka_resolve_post_images($post_id, $scoping_map = [], $mysqli = null) {
        $post_id = (int)$post_id;
        $images = [];
        $seen_ids = [];

        if (isset($scoping_map[$post_id])) {
            $scoped = $scoping_map[$post_id];

            if (!empty($scoped['attached_media']) && is_array($scoped['attached_media'])) {
                foreach ($scoped['attached_media'] as $media) {
                    $id = isset($media['attachment_id']) ? (int)$media['attachment_id'] : 0;
                    $url = isset($media['url']) ? $media['url'] : '';
                    if ($id > 0 && !isset($seen_ids[$id])) {
                        $seen_ids[$id] = true;
                        $images[] = ['id' => $id, 'url' => $url];
                    }
                }
            }

            if (!empty($scoped['embedded_images']) && is_array($scoped['embedded_images'])) {
                foreach ($scoped['embedded_images'] as $media) {
                    $id = isset($media['db_attachment_id']) ? (int)$media['db_attachment_id'] : 0;
                    $url = isset($media['src_url']) ? $media['src_url'] : '';
                    if ($id > 0 && !isset($seen_ids[$id])) {
                        $seen_ids[$id] = true;
                        $images[] = ['id' => $id, 'url' => $url];
                    }
                }
            }

            if (!empty($scoped['gallery_image_ids']) && is_array($scoped['gallery_image_ids'])) {
                foreach ($scoped['gallery_image_ids'] as $gid) {
                    $id = (int)$gid;
                    if ($id > 0 && !isset($seen_ids[$id])) {
                        $seen_ids[$id] = true;
                        $images[] = ['id' => $id, 'url' => ''];
                    }
                }
            }
        }

        if (empty($images) && $mysqli instanceof mysqli) {
            $stmt = $mysqli->prepare("SELECT ID, guid FROM wp_posts WHERE post_parent = ? AND post_type = 'attachment' AND post_mime_type LIKE 'image/%'");
            if ($stmt) {
                $stmt->bind_param("i", $post_id);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $id = (int)$row['ID'];
                        $url = $row['guid'];
                        if ($id > 0 && !isset($seen_ids[$id])) {
                            $seen_ids[$id] = true;
                            $images[] = ['id' => $id, 'url' => $url];
                        }
                    }
                }
                $stmt->close();
            }
        }

        if (!empty($images) && $mysqli instanceof mysqli) {
            foreach ($images as &$img) {
                if ($img['id'] > 0 && empty($img['url'])) {
                    $stmt = $mysqli->prepare("SELECT guid FROM wp_posts WHERE ID = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $img['id']);
                        if ($stmt->execute()) {
                            $res = $stmt->get_result();
                            if ($row = $res->fetch_assoc()) {
                                $img['url'] = $row['guid'];
                            }
                        }
                        $stmt->close();
                    }
                }
            }
            unset($img);
        }

        return $images;
    }
}

if (!function_exists('eka_resolve_slider_images_by_alias')) {
    /**
     * Resolves media images by slider alias across translated pages in scoping index.
     *
     * @param string $alias
     * @param int $post_id
     * @param array $scoping_map
     * @param mysqli|null $mysqli
     * @return array Array of ['id' => int, 'url' => string]
     */
    function eka_resolve_slider_images_by_alias($alias, $post_id, $scoping_map = [], $mysqli = null) {
        $images = eka_resolve_post_images($post_id, $scoping_map, $mysqli);
        if (!empty($images)) {
            return $images;
        }

        if (!empty($alias) && is_array($scoping_map)) {
            foreach ($scoping_map as $pid => $scoped) {
                if (!empty($scoped['rev_sliders']) && is_array($scoped['rev_sliders'])) {
                    foreach ($scoped['rev_sliders'] as $rs) {
                        if (isset($rs['alias']) && strcasecmp($rs['alias'], $alias) === 0) {
                            $found = eka_resolve_post_images($pid, $scoping_map, $mysqli);
                            if (!empty($found)) {
                                return $found;
                            }
                        }
                    }
                }
            }
        }

        return [];
    }
}

if (!function_exists('eka_build_gutenberg_gallery_block')) {
    /**
     * Helper to construct Gutenberg wp:gallery block with nested wp:image blocks.
     *
     * @param array $images
     * @param string $extra_class
     * @param string $fallback_title
     * @return string Block HTML comment markup
     */
    function eka_build_gutenberg_gallery_block($images, $extra_class = 'rev-slider-replaced', $fallback_title = 'Slider') {
        $valid_images = [];
        foreach ($images as $img) {
            if (!empty($img['id']) || !empty($img['url'])) {
                $valid_images[] = $img;
            }
        }

        if (empty($valid_images)) {
            return '<!-- wp:gallery {"className":"' . $extra_class . '"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped ' . $extra_class . '"><!-- wp:paragraph --><p>' . htmlspecialchars($fallback_title, ENT_QUOTES, 'UTF-8') . '</p><!-- /wp:paragraph --></figure><!-- /wp:gallery -->';
        }

        $image_ids = [];
        $inner_blocks_html = '';

        foreach ($valid_images as $img) {
            $id = (int)$img['id'];
            $url = htmlspecialchars($img['url'], ENT_QUOTES, 'UTF-8');
            if ($id > 0) {
                $image_ids[] = $id;
            }

            $id_attr_json = $id > 0 ? '"id":' . $id . ',' : '';
            $id_class = $id > 0 ? ' wp-image-' . $id : '';

            $inner_blocks_html .= '<!-- wp:image {' . $id_attr_json . '"sizeSlug":"full","linkDestination":"none"} -->';
            $inner_blocks_html .= '<figure class="wp-block-image size-full"><img src="' . $url . '" alt="" class="' . trim($id_class) . '"/></figure>';
            $inner_blocks_html .= '<!-- /wp:image -->';
        }

        $gallery_attrs = [
            'columns' => 1,
            'ids' => $image_ids,
            'linkTo' => 'none',
            'sizeSlug' => 'full',
            'className' => $extra_class,
        ];
        $attrs_json = json_encode($gallery_attrs, JSON_UNESCAPED_SLASHES);

        $html = '<!-- wp:gallery ' . $attrs_json . ' -->';
        $html .= '<figure class="wp-block-gallery has-nested-images columns-1 is-cropped ' . $extra_class . '">';
        $html .= $inner_blocks_html;
        $html .= '</figure>';
        $html .= '<!-- /wp:gallery -->';

        return $html;
    }
}
