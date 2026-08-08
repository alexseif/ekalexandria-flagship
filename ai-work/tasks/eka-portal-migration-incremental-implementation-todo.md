# TASK LIST: EKA PORTAL INCREMENTAL MODERNIZATION

**Topic Name:** `eka-portal-migration`  
**Issue Name:** `incremental-implementation`  

- [ ] **Phase 1: Theme & FSE Foundation Standardization**
  - [ ] Task 1.1: Standardize `theme.json` with `customTemplates` array (`front-page-en`, `front-page-ar`, `index-en`, `index-ar`, `single-en`, `single-ar`, `archive-alx_tachydromos`, `single-alx_tachydromos`, `archive-board_member`, `page-parent-sidebar`) and `templateParts` area definitions (`header`, `header-en`, `header-ar`, `footer`, `footer-en`, `footer-ar`, `sidebar-news`, `sidebar-child-pages`)
  - [ ] Task 1.2: Research & evaluate lightweight plugins vs custom code for Social Share buttons (AddToAny vs SVG shortcode `[eka_social_share]`) and Polylang FSE integration (Polylang Pro vs filter hooks `pre_get_block_template` / `render_block_data`)

- [ ] **Phase 2: CPT & Newsletter Architecture Fixes**
  - [ ] Task 2.1: Register custom PDF upload metabox (`_eka_pdf_attachment_id`) for `alx_tachydromos` in `inc/custom-features.php`, verify ImageMagick save hook (`save_post_alx_tachydromos`), and fix Gutenberg `core/file` AST block format in `templates/single-alx_tachydromos.html` (strip invalid `aria-label` attributes)
  - [ ] Task 2.2: Refine 3-column team grid in `templates/archive-board_member.html` & `templates/board-members.html` for `board_member` CPT sorted by `menu_order` and submit for human revision checkpoint

- [ ] **Phase 3: Content Engine & Pipeline Script Refactoring**
  - [ ] Task 3.1: Update `bin/migration-content-engine.php` with LayerSlider Exception List (`13236`, `16894`, `16892`, `18`, `16920`, `16923`), stripping shortcodes & wrapper containers on homepage/news pages, and implementing explicit handlers for the 7 shortcode categories:
    - 1. WPBakery Structural: `[vc_row]`, `[vc_column]` $\rightarrow$ `core/columns` & `core/column` (`flex-basis: X%`); `[vc_column_text]` $\rightarrow$ `core/paragraph`; `[vc_single_image]` $\rightarrow$ `core/image`; `[vc_raw_html]` $\rightarrow$ `core/html`
    - 2. BeTheme / Muffin: `[mfn_button]` $\rightarrow$ `core/buttons`; `[items_list]`, `[content_box]` $\rightarrow$ unwrapped into `core/group` or `core/paragraph`
    - 3. Sliders (Non-Exception inner pages): `[rev_slider]`, `[layerslider]` $\rightarrow$ `core/gallery` (`is-style-legacy-slider`) with media IDs
    - 4. Team & Board Query: `[our_team]`, `[our_team_list]`, `[testimonials]` $\rightarrow$ `core/group` member cards or `core/query` block targeting `board_member` CPT sorted by `menu_order`
    - 5. Core & Media Embeds: `[embed]` $\rightarrow$ `core/embed`; `[caption]` $\rightarrow$ `core/image` with `<figcaption>`; `[gallery]` $\rightarrow$ `core/gallery`; `[video]` $\rightarrow$ `core/video`; `[audio]` $\rightarrow$ `core/audio`
    - 6. Plugin Integrations: `[gview file="...pdf"]` $\rightarrow$ `core/file` block (`displayPreview: true`); `[mc4wp_form]` $\rightarrow$ `[eka_mailchimp_form]`
    - 7. Numeric References / Brackets: `[14]`, `[7837, 8088]` $\rightarrow$ `eka/homepage-services-grid` or `eka/child-pages-sidebar`; bracketed text (`[Sigma]`, `[during World War II]`) $\rightarrow$ ignored by regex
  - [ ] Task 3.2: Rename pipeline scripts (`bin/03-migrate-content.sh` $\rightarrow$ `bin/02-migrate-content.sh`, `bin/06-assign-templates-and-menus.sh` $\rightarrow$ `bin/03-assign-templates.sh`), remove automated menu assignments, update FSE page ID template mappings (`assign-page-templates.php`), and delete deprecated legacy scripts (`03-surgical-migrations.php`, `04-shortcode-migrations.php`, `05-classic-editor-migrations.php`, `inject-sidebar-menus.php`, `remediate-shortcodes-to-blocks.php`)

- [ ] **Phase 4: Verification, Testing & Final Sign-Off**
  - [ ] Task 4.1: Execute end-to-end 3-stage pipeline dry-run (`01-reset-and-setup.sh`, `02-migrate-content.sh`, `03-assign-templates.sh`), verify AST block validity (`eka_validate_blocks_ast()`), audit log files (`ai-work/logs/`), and present manual admin checklist
