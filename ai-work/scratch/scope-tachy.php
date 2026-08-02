<?php
$legacy_db = new mysqli('localhost', 'root', '0024', 'db207080_eka');
if ($legacy_db->connect_error) {
    die("Connection failed: " . $legacy_db->connect_error);
}
$result = $legacy_db->query("SELECT post_content FROM wp_posts WHERE post_title LIKE '%Ταχυδρόμος%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1");
$scoping = [];

if ($row = $result->fetch_assoc()) {
    $content = $row['post_content'];
    
    // Use regex to find block-image or figures that contain PDF links
    preg_match_all('/<figure[^>]*>(.*?)<\/figure>/is', $content, $figures, PREG_SET_ORDER);
    
    foreach ($figures as $fig) {
        $inner = $fig[1];
        
        $pdf_url = null;
        $img_url = null;
        $title = null;
        
        // Find PDF link
        if (preg_match('/<a[^>]+href=["\']([^"\']+\.pdf)["\'][^>]*>/i', $inner, $m)) {
            $pdf_url = $m[1];
        } else if (preg_match('/\[tachydromos_pdf_button[^\]]*url=["\']([^"\']+\.pdf)["\']/i', $inner, $m)) {
            $pdf_url = $m[1];
        }
        
        // Find image
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $inner, $m)) {
            $img_url = $m[1];
        }
        
        // Find title (caption)
        if (preg_match('/<figcaption[^>]*>(.*?)<\/figcaption>/is', $inner, $m)) {
            $title = trim(strip_tags($m[1]));
        }
        
        if ($pdf_url || $img_url) {
            $scoping[] = [
                'pdf_url' => $pdf_url,
                'img_url' => $img_url,
                'extracted_title' => $title,
                'raw_html' => trim($fig[0])
            ];
        }
    }
}

file_put_contents('/var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/ai-work/scopings/tachydromos-scoping.json', json_encode($scoping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$legacy_db->close();
echo "Scoping saved to tachydromos-scoping.json. Found " . count($scoping) . " items.\n";
