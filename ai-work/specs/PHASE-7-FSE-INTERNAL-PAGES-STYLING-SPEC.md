# Technical Specification: Phase 7 FSE Theme Internal Pages, Multilingual Templates & Block Registration

**TOPIC NAME**: `FSE-THEME-REFINEMENT`  
**ISSUE NAME**: `INTERNAL-PAGES-STYLING-REGISTRATION`  
**DATE**: 2026-08-04  
**STATUS**: DRAFT - PENDING USER APPROVAL  

---

## 1. Objective & Target Users

### 1.1 Objective
Refine Gutenberg-native templates for internal pages and frontpages across three languages (Greek `el`, English `en`, Arabic `ar`), and fix block editor registration and core columns stylesheet enqueuing specifically for `eka/homepage-services-grid`. Specifically:
1. Provide `page-parent-sidebar` templates in 3 language variants (`page-parent-sidebar-el.html`, `page-parent-sidebar-en.html`, `page-parent-sidebar-ar.html`), ensuring header/footer parts and sidebar design render as intended.
2. Add border settings for `core/group` and `core/image` blocks in `theme.json`.
3. Programmatically enqueue WordPress Gutenberg's native core columns stylesheet (`wp_enqueue_style('wp-block-columns')`) inside `eka_render_homepage_services_grid()` in `inc/blocks.php`, so Gutenberg's official core columns CSS handles the column layout naturally without writing custom column SCSS.
4. Register JS block editor script specifically for `eka/homepage-services-grid` using `wp.serverSideRender` (enqueued via `enqueue_block_editor_assets` in `inc/blocks.php`), resolving the FSE Site Editor "Your site doesn't include support for the 'eka/homepage-services-grid' block" error banner.
5. Enforce single git branch workflow with user review before each commit.

---

## 2. Core Architecture & Acceptance Criteria

### 2.1 `theme.json` Block Border Settings
- **Acceptance Criteria**:
  - `theme.json` includes `settings.blocks`:
    - `core/group` border settings (`radius: true`, `style: true`, `width: true`)
    - `core/image` border settings (`radius: true`, `style: true`, `width: true`)

### 2.2 Multilingual Frontpage & Sidebar Page Templates
- **Acceptance Criteria**:
  - Frontpage HTML templates in `templates/`:
    - `templates/front-page-el.html` (includes `header-el` & `footer-el`)
    - `templates/front-page-en.html` (includes `header-en` & `footer-en`)
    - `templates/front-page-ar.html` (includes `header-ar` & `footer-ar`)
  - Parent Sidebar Page HTML templates in `templates/`:
    - `templates/page-parent-sidebar-el.html` (includes `header-el` & `footer-el`, 2-column layout: 70% post content, 30% sidebar)
    - `templates/page-parent-sidebar-en.html` (includes `header-en` & `footer-en`, 2-column layout: 70% post content, 30% sidebar)
    - `templates/page-parent-sidebar-ar.html` (includes `header-ar` & `footer-ar`, 2-column layout: 70% post content, 30% sidebar)

### 2.3 Native Gutenberg Core Columns Enqueue for `eka/homepage-services-grid`
- **Acceptance Criteria**:
  - In `inc/blocks.php` inside `eka_render_homepage_services_grid()`, `wp_enqueue_style('wp-block-columns')` is explicitly called.
  - WordPress automatically loads Gutenberg's official native core columns stylesheet (`wp-includes/css/dist/block-library/style.min.css`) whenever `eka/homepage-services-grid` is rendered on the page.
  - Zero custom column SCSS is written — the block uses Gutenberg's native core column classes (`wp-block-columns`, `wp-block-column`, `is-layout-flex`) rendered directly with WordPress native styles.

### 2.4 JS Block Editor Registration for `eka/homepage-services-grid` (FSE Site Editor Fix)
- **Acceptance Criteria**:
  - A JS editor script `assets/js/blocks-editor.js` is created and enqueued via `enqueue_block_editor_assets` in `inc/blocks.php`.
  - Registers the `eka/homepage-services-grid` block JS definition using `@wordpress/blocks` (`wp.blocks.registerBlockType`) and `@wordpress/server-side-render` (`wp.serverSideRender`).
  - In the FSE Site Editor (`wp-admin/site-editor.php`), opening `front-page.html` or `front-page-el.html` renders a live preview of `eka/homepage-services-grid` via `ServerSideRender` without displaying "Your site doesn't include support for the 'eka/homepage-services-grid' block. You can leave it as-is or remove it." error banners.

---

## 3. Project Structure & Key Files

```
public/wp-content/themes/ekalexandria-flagship/
├── theme.json                           # Block border settings for core/group & core/image
├── inc/
│   └── blocks.php                       # eka_render_homepage_services_grid() calling wp_enqueue_style('wp-block-columns') & JS editor enqueue
├── assets/
│   └── js/
│       └── blocks-editor.js             # JS registration for eka/homepage-services-grid using ServerSideRender
└── templates/
    ├── front-page-el.html
    ├── front-page-en.html
    ├── front-page-ar.html
    ├── page-parent-sidebar-el.html
    ├── page-parent-sidebar-en.html
    └── page-parent-sidebar-ar.html
```

---

## 4. Boundaries & Workflow Rules

### 4.1 Boundaries
- **ALWAYS DO**:
  - Verify `eka/homepage-services-grid` rendering in both WP Page Editor / FSE Site Editor and frontend.
  - Present implementation step results to user for review BEFORE committing to git.
  - Keep execution strictly on 1 git branch (`master`).
- **ASK FIRST ABOUT**:
  - Any architectural layout changes to FSE templates.
- **NEVER DO**:
  - Never write custom column SCSS when WordPress core styles can be enqueued natively.
  - Never override user approval gates or commit without user explicit review.
  - Never run automated DB content remediation commands.

### 4.2 Git Workflow Strategy
- Single active branch: `master`.
- Granular commits after user review of each completed sub-task:
  1. `feat(theme.json): add core/group and core/image border settings`
  2. `feat(blocks): register eka/homepage-services-grid JS editor script`
  3. `feat(blocks): enqueue native wp-block-columns style for homepage-services-grid`
  4. `feat(templates): add multilingual front-page and page-parent-sidebar templates`

---

## 5. Verification Strategy

1. **`eka/homepage-services-grid` FSE Registration Check**: Open Gutenberg Site Editor on `front-page.html` / `front-page-el.html` and verify `eka/homepage-services-grid` renders a live preview without error banners.
2. **`eka/homepage-services-grid` Native Columns CSS Check**: Inspect page source on frontend to verify `wp-block-columns-css` is loaded and `.homepage-pages-grid` uses Gutenberg's native column styling.
3. **Multilingual Sidebar Page Verification**: Test `page-parent-sidebar-el`, `page-parent-sidebar-en`, and `page-parent-sidebar-ar` in Page Editor; verify 2-column layout renders correctly with main content and editable sidebar column alongside matching language header/footer.
4. **Theme.json validation**: Validate JSON schema for block border settings.

---
