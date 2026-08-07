# TODO List: Migration Edge Cases & FSE Refinements

**TOPIC NAME**: `migration`  
**ISSUE NAME**: `edge-cases-fse-refinement`  
**SPEC**: `public/wp-content/themes/ekalexandria-flagship/ai-work/migration-edge-cases-fse-refinement-SPEC.md`  

---

- [x] **Task 1: Outer Semantic Tag Clean-up (`tagName` Deduplication)**
  - [x] Audit `templates/*.html` and remove `"tagName":"header"` / `"tagName":"footer"` from `wp:template-part` block calls.
  - [x] Retain `"tagName":"header"` on top-level group block in `parts/header.html`, `parts/header-en.html`, `parts/header-ar.html`.
  - [x] Retain `"tagName":"footer"` on top-level group block in `parts/footer.html`, `parts/footer-en.html`, `parts/footer-ar.html`.
  - [x] Verify single `<header>` and `<footer>` tag rendering in DOM output.
  - [x] Commit: `refactor(fse): deduplicate outer semantic header and footer tags in templates and parts`

- [x] **Task 2: Smart `[vc_row]` / `[vc_column]` & Sub-grid Shortcode Unwrapping**
  - [x] Update `step_4a_transform_wpbakery_and_caption` in `bin/04-shortcode-migrations.php` to unwrap single 1/1 column rows.
  - [x] Convert inner `[vc_column_text]` directly to root `<!-- wp:paragraph -->` / heading blocks.
  - [x] Wrap sub-grids (`[vc_posts_grid]`) in clean `wp:group` + `wp:query` blocks without outer `wp:columns` wrappers.
  - [x] Preserve multi-column row wrapping with calculated flex percentages for 1/2, 1/3, 2/3 layouts.
  - [x] Run AST validation (`eka_validate_blocks_ast()`) and PHP syntax check (`php -l bin/04-shortcode-migrations.php`).
  - [x] Commit: `fix(migration): implement smart 1/1 column unwrapping in shortcode migration engine`

- [x] **Task 3: MFN Left Sidebar Page Layout Support**
  - [x] Implement MFN left sidebar detection in migration pipeline (`mfn-post-sidebar`, `_mfn-post-sidebar`, or `mfn_layout`).
  - [x] Exclude right sidebar pages and news/posts page (`index` / `page_for_posts`).
  - [x] Wrap post content in 30/70 Gutenberg columns (`wp:columns` with `eka-has-sidebar-left` class).
  - [x] Ensure left column (`30%`) is reserved for sidebar content and right column (`70%`) contains main content.
  - [x] Do NOT assign dedicated template parts or template files (`_wp_page_template`) for sidebars at this stage.
  - [x] Verify AST validity of converted 30/70 sidebar pages.
  - [x] Commit: `feat(migration): add 30/70 Gutenberg column layout for legacy MFN left sidebar pages`

- [x] **Task 4: Deferred Language & Template Assignment with Cache/Transient Flushes**
  - [x] Add transient and cache flushes (`delete_transient('wp_theme_files_')`, `wp_cache_flush()`, Polylang invalidation) to `bin/assign-page-templates.php`.
  - [x] Ensure language assignment script runs AFTER all post content migrations complete.
  - [x] Verify templates are properly assigned for translated pages (`page-ar`, `page-en`, `news-ar`, `news-en`, `index-ar`, `index-en`).
  - [x] Test script execution idempotency.
  - [x] Commit: `feat(migration): add cache invalidation and deferred template assignment script`

- [x] **Task 5: Final Checkpoint & End-to-End Migration Pipeline Review**
  - [x] Run full migration pipeline end-to-end.
  - [x] Run `bin/test-fse-sanitizer.php` and verify unit tests pass cleanly.
  - [x] Perform AST validation (`eka_validate_blocks_ast()`) across all converted pages.
  - [x] Inspect residual shortcodes log (`ai-work/logs/missed-shortcodes.json`) and verify zero unintended residual shortcodes.
  - [x] Check template tag deduplication (`tagName="header"` / `tagName="footer"`).
  - [x] Commit: `chore(migration): finalize FSE edge-case refinement pipeline and verification suite`.
