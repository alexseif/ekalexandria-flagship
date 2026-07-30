<?php
$db = new mysqli('localhost', 'root', '0024', 'db207080_eka');
if ($db->connect_error) die("DB Error");

// 1. Find all posts with layerslider shortcode
$res = $db->query("SELECT ID, post_title, post_content FROM wp_posts WHERE post_content LIKE '%[layerslider%' AND post_status = 'publish'");

$scoping = [];

while ($row = $res->fetch_assoc()) {
    $content = $row['post_content'];
    
    // Find shortcodes
    if (preg_match_all('/\[layerslider\s+id=["\']?(\d+)["\']?\]/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $slider_id = $m[1];
            
            // Query slider data
            $slider_res = $db->query("SELECT name, data FROM wp_layerslider WHERE id = " . intval($slider_id));
            if ($slider_row = $slider_res->fetch_assoc()) {
                $slider_name = $slider_row['name'];
                $data = json_decode($slider_row['data'], true);
                
                $slides = [];
                if (isset($data['layers']) && is_array($data['layers'])) {
                    foreach ($data['layers'] as $layer) {
                        $slide_data = [
                            'bg_image_id' => null,
                            'bg_image_url' => null,
                            'layers_text' => []
                        ];
                        
                        // Extract background
                        if (!empty($layer['properties']['backgroundimageId'])) {
                            $slide_data['bg_image_id'] = $layer['properties']['backgroundimageId'];
                        }
                        if (!empty($layer['properties']['backgroundimage'])) {
                            $slide_data['bg_image_url'] = $layer['properties']['backgroundimage'];
                        }
                        
                        // Extract text layers
                        if (isset($layer['sublayers']) && is_array($layer['sublayers'])) {
                            foreach ($layer['sublayers'] as $sub) {
                                if (isset($sub['html']) && strip_tags($sub['html']) !== '') {
                                    $slide_data['layers_text'][] = strip_tags($sub['html']);
                                }
                            }
                        }
                        $slides[] = $slide_data;
                    }
                }
                
                $scoping[] = [
                    'page_id' => $row['ID'],
                    'page_title' => $row['post_title'],
                    'slider_id' => $slider_id,
                    'slider_name' => $slider_name,
                    'slides_summary' => $slides
                ];
            }
        }
    }
}

file_put_contents('/var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/ai-work/scopings/layer-sliders-scoping.json', json_encode($scoping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Scoped " . count($scoping) . " slider usages.\n";
