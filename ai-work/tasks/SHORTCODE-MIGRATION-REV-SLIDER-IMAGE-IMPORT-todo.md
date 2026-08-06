# TOPIC NAME: SHORTCODE-MIGRATION
# ISSUE NAME: REV-SLIDER-IMAGE-IMPORT

# Task List: RevSlider & LayerSlider Gutenberg Image Import Remediation

- [x] **Task 1: Implement Scoping Loader & Media Resolver Functions**
  - [x] Implement `eka_load_slider_scoping()` to load `rev-sliders-scoping.json` into `$scoping_map`.
  - [x] Implement `eka_resolve_post_images($post_id, $scoping_map, $mysqli)` to return image objects `{id, url}` by querying scoping data and `wp_posts` attachments.
  - Verification: Execute test snippet or dry run to ensure Post ID 32 resolves to attachment 13369 with URL `https://backstage.ekalexandria.org/wp-content/uploads/2015/05/HR.png`.

- [ ] **Task 2: Refactor Slider Shortcode Conversion to Gutenberg Block Structure**
  - [ ] Update signature of `step_4a_transform_wpbakery_and_caption($content, $post_id, $scoping_map, $mysqli)` to accept post ID and media context.
  - [ ] Update `rev_slider`, `rev_slider_vc`, and `layerslider` callbacks to generate valid `<!-- wp:gallery -->` and `<!-- wp:image -->` block structures.
  - [ ] Ensure fallback markup passes `eka_validate_blocks_ast()`.
  - Verification: Test block conversion output against AST validator.

- [ ] **Task 3: Execute Migration Pipeline & Audit Post ID 32**
  - [ ] Run `php public/wp-content/themes/ekalexandria-flagship/bin/04-shortcode-migrations.php`.
  - [ ] Run `wp post get 32 --field=post_content --path=public/wordpress` to verify attachment `13369` (`HR.png`) is in `wp:gallery` -> `wp:image`.
  - [ ] Inspect `public/wp-content/themes/ekalexandria-flagship/ai-work/logs/04-shortcode-migrations.log` to confirm 0 AST validation failures.
  - Verification: Clean pipeline execution with zero errors and accurate block output.
