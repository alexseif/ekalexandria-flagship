# TOPIC NAME: SHORTCODE-MIGRATION
# ISSUE NAME: REV-SLIDER-IMAGE-IMPORT

# Spec: RevSlider & LayerSlider Gutenberg Image Import Remediation

## 1. Objective
Enable fully automated transformation of legacy `[rev_slider]`, `[rev_slider_vc]`, and `[layerslider]` shortcodes into valid, native Gutenberg `wp:gallery` blocks containing real `wp:image` inner blocks. Image IDs and URLs are dynamically resolved using scoping data (`ai-work/scopings/rev-sliders-scoping.json`) combined with direct MySQL queries (`wp_posts` attachment records) without any manual or runtime AI intervention.

Target Users: WordPress Migration Pipeline Orchestrator & Site Administrators.

## 2. Execution & Verification Commands
- **Execution Command**: 
  `php public/wp-content/themes/ekalexandria-flagship/bin/04-shortcode-migrations.php`
- **Orchestration Execution**: 
  `bash public/wp-content/themes/ekalexandria-flagship/bin/03-migrate-content.sh`
- **Verification Command**:
  `wp post get 32 --field=post_content --path=public/wordpress`
- **Log Verification**:
  `cat public/wp-content/themes/ekalexandria-flagship/ai-work/logs/04-shortcode-migrations.log`

## 3. Project Structure
- `public/wp-content/themes/ekalexandria-flagship/bin/04-shortcode-migrations.php` (Target script to update)
- `public/wp-content/themes/ekalexandria-flagship/ai-work/scopings/rev-sliders-scoping.json` (Read-only scoping data source)
- `public/wp-content/themes/ekalexandria-flagship/ai-work/logs/04-shortcode-migrations.log` (Execution log destination)

## 4. Code Style & Implementation Details
- **Automated Execution**: The script operates 100% programmatically and autonomously when executed via CLI.
- **Scoping Pre-Load**: At startup, load `rev-sliders-scoping.json` into an in-memory index map (`$scoping_map`) keyed by `page_id`.
- **Media Resolution Strategy**:
  1. For post ID `$post_id`, inspect `$scoping_map[$post_id]` for `attached_media`, `embedded_images`, and `gallery_image_ids`.
  2. Query `mysqli` database connection for `wp_posts` records where `post_parent = $post_id` AND `post_type = 'attachment'` AND `post_mime_type LIKE 'image/%'`.
  3. Query attachment details (attachment ID and `guid` URL) for all resolved IDs.
- **Gutenberg Block Structure**:
  - Construct native gallery markup:
    ```html
    <!-- wp:gallery {"ids":[13369],"linkTo":"none","className":"rev-slider-replaced"} -->
    <figure class="wp-block-gallery has-nested-images columns-default is-cropped rev-slider-replaced">
    <!-- wp:image {"id":13369,"sizeSlug":"full","linkDestination":"none"} -->
    <figure class="wp-block-image size-full"><img src="https://backstage.ekalexandria.org/wp-content/uploads/2015/05/HR.png" alt="" class="wp-image-13369"/></figure>
    <!-- /wp:image -->
    </figure>
    <!-- /wp:gallery -->
    ```
  - If no images are resolved for a slider, output a valid Gutenberg gallery block with an informative notice text that passes AST validation.

## 5. Testing & Validation Strategy
- **AST Validation**: Verify all converted post content passes `eka_validate_blocks_ast()`.
- **Sample Verification**: Validate Post ID 32 (`Στελέχωση`) content contains attachment ID `13369` (`HR.png`) inside `wp:gallery` -> `wp:image` blocks.
- **Metrics Summary**: Ensure zero AST validation failures in Stage 04 summary report.

## 6. Known Boundaries
- **Always Do**: Pass `$post_id` into shortcode transformation functions; ensure AST block balance; write execution metrics to log file.
- **Never Do**: Modify existing database schemas; hardcode post-specific image URLs; introduce interactive CLI prompts.
