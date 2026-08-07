# TODO List: Migration Edge Cases & FSE Refinements

**TOPIC NAME**: `migration`  
**ISSUE NAME**: `edge-cases-fse-refinement`  
**SPEC**: `public/wp-content/themes/ekalexandria-flagship/ai-work/migration-edge-cases-fse-refinement-SPEC.md`  

---

- [ ] **Task 1: Outer Semantic Tag Clean-up (`tagName` Deduplication)**
  - [ ] Audit `templates/*.html` and remove `"tagName":"header"` / `"tagName":"footer"` from `wp:template-part` block calls.
  - [ ] Retain `"tagName":"header"` on top-level group block in `parts/header.html`, `parts/header-en.html`, `parts/header-ar.html`.
  - [ ] Retain `"tagName":"footer"` on top-level group block in `parts/footer.html`, `parts/footer-en.html`, `parts/footer-ar.html`.
  - [ ] Verify single `<header>` and `<footer>` tag rendering in DOM output.
  - [ ] Commit: `refactor(fse): deduplicate outer semantic header and footer tags in templates and parts`

- [ ] **Task 2: Smart `[vc_row]` / `[vc_column]` & Sub-grid Shortcode Unwrapping**
  - [ ] Update `step_4a_transform_wpbakery_and_caption` in `bin/04-shortcode-migrations.php` to unwrap single 1/1 column rows.
  - [ ] Convert inner `[vc_column_text]` directly to root `<!-- wp:paragraph -->` / heading blocks.
  - [ ] Wrap sub-grids (`[vc_posts_grid]`) in clean `wp:group` + `wp:query` blocks without outer `wp:columns` wrappers.
  - [ ] Preserve multi-column row wrapping with calculated flex percentages for 1/2, 1/3, 2/3 layouts.
  - [ ] Run AST validation (`eka_validate_blocks_ast()`) and PHP syntax check (`php -l bin/04-shortcode-migrations.php`).
  - [ ] Commit: `fix(migration): implement smart 1/1 column unwrapping in shortcode migration engine`

- [ ] **Task 3: MFN Left Sidebar Page Layout Support**
  - [ ] Implement MFN left sidebar detection in migration pipeline (`mfn-post-sidebar`, `_mfn-post-sidebar`, or `mfn_layout`).
  - [ ] Exclude right sidebar pages and news/posts page (`index` / `page_for_posts`).
  - [ ] Wrap post content in 30/70 Gutenberg columns (`wp:columns` with `eka-has-sidebar-left` class).
  - [ ] Verify AST validity of converted 30/70 sidebar pages.
  - [ ] Commit: `feat(migration): add 30/70 Gutenberg column layout for legacy MFN left sidebar pages`

- [ ] **Task 4: Deferred Language & Template Assignment with Cache/Transient Flushes**
  - [ ] Add transient and cache flushes (`delete_transient('wp_theme_files_')`, `wp_cache_flush()`, Polylang invalidation) to `bin/assign-page-templates.php`.
  - [ ] Implement strict multilingual page template mapping rules (`front-page`/`front-page-en`/`front-page-ar` and `page`/`page-en`/`page-ar`).
  - [ ] Verify script execution ordering in `bin/06-assign-templates-and-menus.sh`.
  - [ ] Verify postmeta updates via WP-CLI / MySQL queries.
  - [ ] Commit: `feat(migration): add cache flushing and deferred multilingual page template assignment`

- [ ] **Task 5: Final Checkpoint & End-to-End Migration Pipeline Review**
  - [ ] Run full migration pipeline end-to-end.
  - [ ] Verify 0 AST validation failures and zero duplicate header/footer tags in DOM.
  - [ ] Perform final code review against SPEC acceptance criteria.
