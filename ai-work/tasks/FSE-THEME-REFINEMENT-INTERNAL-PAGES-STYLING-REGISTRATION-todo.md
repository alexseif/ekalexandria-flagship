# TODO List: FSE Theme Refinement - Internal Pages Styling, Multilingual Templates & Block Registration

**TOPIC NAME**: `FSE-THEME-REFINEMENT`  
**ISSUE NAME**: `INTERNAL-PAGES-STYLING-REGISTRATION`  
**SPEC**: `ai-work/specs/PHASE-7-FSE-INTERNAL-PAGES-STYLING-SPEC.md`  

---

- [x] **Task 1: Add core/group and core/image block border settings to `theme.json`**
  - [x] Add `settings.blocks.core/group.border` (`radius`, `style`, `width`).
  - [x] Add `settings.blocks.core/image.border` (`radius`, `style`, `width`).
  - [x] Verify JSON validity.
  - [x] Commit: `feat(theme.json): add core/group and core/image border settings`

- [x] **Task 2: Register JS block editor script for `eka/homepage-services-grid`**
  - [x] Create `assets/js/blocks-editor.js` using `@wordpress/server-side-render`.
  - [x] Enqueue `blocks-editor.js` in `inc/blocks.php` via `enqueue_block_editor_assets`.
  - [x] Verify FSE editor rendering / JS syntax.
  - [x] Commit: `feat(blocks): register eka/homepage-services-grid JS editor script`

- [ ] **Task 3: Enqueue native Gutenberg core columns style for `eka/homepage-services-grid`**
  - [ ] Call `wp_enqueue_style('wp-block-columns')` in `eka_render_homepage_services_grid()`.
  - [ ] Verify PHP syntax and block rendering.
  - [ ] Commit: `feat(blocks): enqueue native wp-block-columns style for homepage-services-grid`

- [ ] **Task 4: Add multilingual front-page and page-parent-sidebar HTML templates**
  - [ ] Create/verify `templates/front-page-el.html`, `templates/front-page-en.html`, `templates/front-page-ar.html`.
  - [ ] Create `templates/page-parent-sidebar-el.html`, `templates/page-parent-sidebar-en.html`, `templates/page-parent-sidebar-ar.html`.
  - [ ] Verify Gutenberg block template comment markup.
  - [ ] Commit: `feat(templates): add multilingual front-page and page-parent-sidebar templates`

- [ ] **Task 5: Final Checkpoint & Quality Review**
  - [ ] Review all changes across theme files.
  - [ ] Verify template parts, block registrations, and theme.json settings.
