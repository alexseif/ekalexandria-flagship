<?php
$legacy_db = new mysqli('localhost', 'root', '0024', 'db207080_eka');
if ($legacy_db->connect_error) {
    die("Connection failed: " . $legacy_db->connect_error);
}
$result = $legacy_db->query("SELECT post_content FROM wp_posts WHERE post_title LIKE '%Ταχυδρόμος%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
$scoping = [];
$seen_pdfs = [];

if ($row = $result->fetch_assoc()) {
    $content = $row['post_content'];

    $process_item = function($pdf_url, $img_url, $raw_title, $raw_html) use (&$scoping, &$seen_pdfs) {
        if (!$pdf_url) return;
        $pdf_filename = basename(parse_url($pdf_url, PHP_URL_PATH));
        if (empty($pdf_filename)) return;
        if ($pdf_filename === 'Alex-10.pdf' && strpos($pdf_url, 'http://') === 0) return; // ignore malformed typo link

        $key = strtolower($pdf_filename);

        $title = $raw_title ? trim(strip_tags(html_entity_decode($raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) : '';
        $title = preg_replace('/\s+/', ' ', $title);

        $unscaled_img_url = $img_url ? preg_replace('/-\d+x\d+(\.[a-zA-Z0-9]+)$/i', '$1', $img_url) : null;

        if (isset($seen_pdfs[$key])) {
            $idx = $seen_pdfs[$key];
            if (empty($scoping[$idx]['img_url']) && $img_url) {
                $scoping[$idx]['img_url'] = $img_url;
                $scoping[$idx]['unscaled_img_url'] = $unscaled_img_url;
            }
            if (empty($scoping[$idx]['extracted_title']) && $title) {
                $scoping[$idx]['extracted_title'] = $title;
            }
            return;
        }

        $seen_pdfs[$key] = count($scoping);
        $scoping[] = [
            'pdf_url' => $pdf_url,
            'img_url' => $img_url,
            'extracted_title' => $title,
            'raw_html' => $raw_html,
            'unscaled_img_url' => $unscaled_img_url
        ];
    };

    // 1. Scan figure elements
    preg_match_all('/<figure[^>]*>(.*?)<\/figure>/is', $content, $figures, PREG_SET_ORDER);
    foreach ($figures as $fig) {
        $inner = $fig[1];
        $pdf_url = null;
        $img_url = null;
        $title = null;
        if (preg_match('/<a[^>]+href=["\']([^"\']+\.pdf)["\']/i', $inner, $m)) $pdf_url = $m[1];
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $inner, $m)) $img_url = $m[1];
        if (preg_match('/<figcaption[^>]*>(.*?)<\/figcaption>/is', $inner, $m)) $title = $m[1];
        $process_item($pdf_url, $img_url, $title, trim($fig[0]));
    }

    // 2. Scan caption shortcodes
    preg_match_all('/\[caption[^\]]*\](.*?)\[\/caption\]/is', $content, $captions, PREG_SET_ORDER);
    foreach ($captions as $cap) {
        $inner = $cap[1];
        $pdf_url = null;
        $img_url = null;
        if (preg_match('/<a[^>]+href=["\']([^"\']+\.pdf)["\']/i', $inner, $m)) $pdf_url = $m[1];
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $inner, $m)) $img_url = $m[1];
        
        $text = preg_replace('/<a[^>]*>.*?<\/a>/is', '', $inner);
        $text = preg_replace('/<img[^>]*>/is', '', $text);
        $title = $text;
        
        $process_item($pdf_url, $img_url, $title, trim($cap[0]));
    }

    // 3. Scan remaining standalone <a> tags with .pdf
    preg_match_all('/<a[^>]+href=["\']([^"\']+\.pdf)["\'][^>]*>(.*?)<\/a>/is', $content, $a_tags, PREG_SET_ORDER);
    foreach ($a_tags as $a) {
        $pdf_url = $a[1];
        $inner = $a[2];
        $img_url = null;
        $title = null;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $inner, $m)) $img_url = $m[1];
        $title = $inner;
        $process_item($pdf_url, $img_url, $title, trim($a[0]));
    }
}

file_put_contents('/var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/ai-work/scopings/tachydromos-scoping.json', json_encode($scoping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$legacy_db->close();
echo "Scoping saved to tachydromos-scoping.json. Found " . count($scoping) . " items.\n";
