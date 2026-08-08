# TASK LIST: EKA PORTAL INCREMENTAL MODERNIZATION

**Topic Name:** `eka-portal-migration`  
**Issue Name:** `incremental-implementation`  
**Git Branch:** `eka-portal-migration-incremental-implementation`  

- [ ] **Phase 1: Theme & FSE Foundation Standardization**
  - [ ] Task 1.1: Standardize `theme.json` with `customTemplates` array (`front-page-en`, `front-page-ar`, `index-en`, `index-ar`, `single-en`, `single-ar`, `archive-alx_tachydromos`, `single-alx_tachydromos`, `archive-board_member`, `page-parent-sidebar`) and `templateParts` area definitions (`header`, `header-en`, `header-ar`, `footer`, `footer-en`, `footer-ar`, `sidebar-news`)

- [ ] **Phase 2: CPT & Newsletter Architecture Fixes**
  - [ ] Task 2.1: Register custom PDF upload metabox (`_eka_pdf_attachment_id`) for `alx_tachydromos` in `inc/custom-features.php`, verify ImageMagick save hook (`save_post_alx_tachydromos`), and fix Gutenberg `core/file` AST block format in `templates/single-alx_tachydromos.html` (strip invalid `aria-label` attributes)
  - [ ] Task 2.2: Refine 3-column team grid in `templates/archive-board_member.html` & `templates/board-members.html` for `board_member` CPT sorted by `menu_order` (decoupled from BeTheme `our_team` staff shortcodes) and submit for human revision checkpoint

- [ ] **Phase 3: Content Engine & Pipeline Script Refactoring**
  - [ ] Task 3.1: Implement LayerSlider Exception Engine in `bin/migration-content-engine.php` for page IDs `13236`, `16894`, `16892`, `18`, `16920`, `16923` (strip shortcode & wrapper container)
  - [ ] Task 3.2: Implement Media Embed & Map Shortcodes Migration (`[embed]` $\rightarrow$ `core/embed`, `[video]` $\rightarrow$ `core/video`, `[map]` $\rightarrow$ Google Maps Embed iframe)
  - [ ] Task 3.3: Implement Plugin Integration & Removal Tasks (`[gview]` $\rightarrow$ `core/file`, `[mc4wp_form]` $\rightarrow$ `[eka_mailchimp_form]`, `[our_team_list]` $\rightarrow$ **Remove completely from content**)
  - [ ] Task 3.4: Implement Subpages Query Loop Shortcodes & Gutenberg Block Comment Isolation (`<!-- wp:... -->` scanner bypass for JSON attributes like `"include":[...]`; convert un-converted numeric shortcodes `[16933]`, `[14]` $\rightarrow$ subpages `core/query` block; ignore bracketed regular text `[Sigma]`)
  - [ ] Task 3.5: Rename pipeline scripts (`bin/03-migrate-content.sh` $\rightarrow$ `bin/02-migrate-content.sh`, `bin/06-assign-templates-and-menus.sh` $\rightarrow$ `bin/03-assign-templates.sh`), remove automated menu assignments, update FSE page ID template mappings (`assign-page-templates.php`), and delete deprecated legacy scripts (`03-surgical-migrations.php`, `04-shortcode-migrations.php`, `05-classic-editor-migrations.php`, `inject-sidebar-menus.php`, `remediate-shortcodes-to-blocks.php`)

- [ ] **Phase 4: Verification, Testing & Final Sign-Off**
  - [ ] Task 4.1: Execute end-to-end 3-stage pipeline dry-run on branch `eka-portal-migration-incremental-implementation` (`01-reset-and-setup.sh`, `02-migrate-content.sh`, `03-assign-templates.sh`), verify AST block validity (`eka_validate_blocks_ast()`), audit log files (`ai-work/logs/`), and present manual admin checklist
