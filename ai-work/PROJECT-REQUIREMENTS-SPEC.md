# MASTER PROJECT TECHNICAL REQUIREMENTS SPECIFICATION

**Project:** Greek Community of Alexandria (EKA) Portal Modernization  
**Theme:** `ekalexandria-flagship` (Gutenberg Full Site Editing Theme)  
**Database Context:** `backstage_eka` (Development/Staging Target DB)  
**Base PHP Target:** PHP 7.4 (Migration & Transformation) $\rightarrow$ PHP 8.2 (Production Runtime)  
**Document Purpose:** Complete, fully-detailed functional and technical requirements specification.

---

## 1. CUSTOM POST TYPES (CPTs) SPECIFICATIONS

### 1.1 Alexandrinos Tachydromos / Newsletters CPT (`alx_tachydromos`)
- **Post Type Slug:** `alx_tachydromos`
- **Labels:** Name: `Alexandrinos Tachydromos`, Singular: `Tachydromos`, Menu Label: `Tachydromos`
- **Registration Settings:**
  - `'public' => true`, `'has_archive' => true`, `'show_in_rest' => true`
  - `'supports' => ['title', 'editor', 'thumbnail']`
  - `'rewrite' => ['slug' => 'αλεξανδρινός-ταχυδρόμος', 'with_front' => false]`
  - `'menu_icon' => 'dashicons-media-document'`
- **Gutenberg REST Meta Fields:**
  - `_eka_pdf_attachment_id` (integer, single, REST-enabled, auth callback: `edit_posts`)
  - `_eka_pdf_filename` (string, single, REST-enabled, auth callback: `edit_posts`)
- **Automated PDF-to-PNG Featured Image Save Hook (`save_post_alx_tachydromos`):**
  - Triggers automatically upon save/update when `_eka_pdf_attachment_id` exists and no thumbnail is assigned.
  - Executes ImageMagick CLI command: `convert -density 150 <pdf_path>[0] -quality 90 <thumb_path>`
  - Inserts output image into Media Library as attachment and binds as post featured image (`set_post_thumbnail`).
- **Polylang Integration:** Explicitly excluded from Polylang translation post types (`pll_get_post_types`) as issues are published as unified multilingual PDFs.

### 1.2 Board of Directors CPT (`board_member`)
- **Post Type Slug:** `board_member`
- **Labels:** Name: `Board Members`, Singular: `Board Member`
- **Registration Settings:**
  - `'public' => true`, `'publicly_queryable' => false` (no individual single post permalink view), `'has_archive' => false`, `'show_in_rest' => true`
  - `'supports' => ['title', 'editor', 'thumbnail', 'page-attributes']` (`menu_order` support)
  - `'menu_icon' => 'dashicons-groups'`
- **Gutenberg REST Meta Fields:**
  - `_eka_legacy_id` (integer, single, REST-enabled) for legacy testimonial ID tracking.
- **Multilingual Translation Group Linking:**
  - Included in Polylang post types via `pll_get_post_types` filter.
  - CLI migration driver (`bin/migrate-cpts.php`) parses `board-scoping.json` translation groups and invokes `pll_save_post_translations()` to bind Greek (`el`), English (`en`), and Arabic (`ar`) member profiles.
- **Content Cleaning & Featured Image Rules:**
  - `post_content` is strictly cleaned to remove all `<img>` tags, legacy shortcodes, and figures, retaining only plain bio text.
  - Matches existing media library attachment IDs by unscaled original filename (stripping dimension suffixes like `-246x300`) and assigns as `_thumbnail_id` without re-uploading or local cropping.

---

## 2. FSE PAGES & TEMPLATE SPECIFICATIONS

### 2.1 Front Page (`front-page.html`, `front-page-en.html`, `front-page-ar.html`)
- **Header & Footer Routing:** Loads language-specific header (`parts/header.html` for Greek default, `parts/header-en.html`, `parts/header-ar.html`) and footer (`parts/footer.html` for Greek default, `parts/footer-en.html`, `parts/footer-ar.html`).
- **Hero Slider Section:** Native FSE hero slider component built into templates.
- **Services Grid Section:** Embedded `eka/homepage-services-grid` dynamic block displaying a 4-column card grid for primary establishment pages (`7837, 8088, 28, 14`).
- **Newsletter Subscription:** Embedded `[eka_mailchimp_form]` shortcode.
- **Page ID Template Assignments:**
  - Greek Front Page (`ID: 13236`): Assigned to `front-page` (Default Template)
  - English Front Page (`ID: 16894`): Assigned to `front-page-en`
  - Arabic Front Page (`ID: 16892`): Assigned to `front-page-ar`

### 2.2 Post List Page / News Index (`index.html`, `index-en.html`, `index-ar.html`)
- **Page ID Template Assignments:**
  - Greek News Page (`ID: 18`): Assigned to `index` (Default Template)
  - English News Page (`ID: 16920`): Assigned to `index-en`
  - Arabic News Page (`ID: 16923`): Assigned to `index-ar`
- **Layout Proportions:** 2-column flex layout (75% main news post query loop, 25% right sidebar).
- **Right Sidebar Component:** Integrates `parts/sidebar-news.html` featuring search bar (`core/search`), category taxonomy navigation menu (`core/categories`), and recent posts loop.

### 2.3 Single Post Page (`single.html`, `single-en.html`, `single-ar.html`)
- **Template Resolution:** Intercepted by `pre_get_block_template` filter hook resolving language templates (`single.html` for Greek default, `single-en.html`, `single-ar.html`) based on Polylang context.
- **Layout Elements:** Main post column featuring category badge, post title, date, featured image, post content, author bio, social sharing button component, and right news sidebar.

### 2.4 Newsletter Pages (`archive-alx_tachydromos.html`, `single-alx_tachydromos.html`)
- **Listing View (`archive-alx_tachydromos.html` / `tachydromos.html`):** Grid view displaying historical PDF newsletter issues of *Alexandrinos Tachydromos*, paginated by year. Displays PNG cover thumbnail, normalized Greek month/year title (e.g. "Ιούνιος 2026"), "View PDF" button, and direct download link.
- **Single View (`single-alx_tachydromos.html`):** Dedicated single issue view embedding native `core/file` block with `displayPreview: true` (interactive PDF viewer canvas) and direct download option.

### 2.5 Board Page (`board-members.html`, `archive-board_member.html`)
- **Grid Layout:** 3-column team card grid sorted by `menu_order`.
- **Card Elements:** Member photo thumbnail, full name/title, bio text, and language switcher.

---

## 3. REMAINING SHORTCODES & EXCEPTION SPECIFICATIONS

### 3.1 Slider Exception List
Pages where sliders are built natively into FSE templates must be skipped by shortcode converters.
- **Exception Page IDs:** `13236` (Front EL/Default), `16894` (Front EN), `16892` (Front AR), `18` (Index EL/Default), `16920` (Index EN), `16923` (Index AR).
- **Transformation Action:** During migration, any `[layerslider]` or `[rev_slider]` shortcode and its surrounding WPBakery wrapper container on exception pages must be completely removed.

### 3.2 Remaining Shortcode Categories & Transformation Rules

| Category | Tags & Extracted Examples | Transformation & Gutenberg Target Block |
| :--- | :--- | :--- |
| **1. WPBakery Structural** | `[vc_row]`, `[vc_column]`, `[vc_column_text]`, `[vc_single_image]`, `[vc_raw_html]` | Convert fraction widths (`1/2` $\rightarrow$ `50%`) to `core/columns` & `core/column` (`flex-basis: X%`), `core/image`, `core/html`. |
| **2. BeTheme / Muffin** | `[mfn_button]`, `[items_list]`, `[content_box]` | Strip wrapper tags; unwrap inner buttons/text into Gutenberg `core/buttons` or `core/paragraph`. |
| **3. Sliders (Non-Exception)** | `[rev_slider]`, `[layerslider]` on inner pages | Convert to `core/gallery` (`is-style-legacy-slider`) populated with attachment Media IDs. |
| **4. Team & Testimonials** | `[our_team]`, `[testimonials]` | Convert to `core/group` member cards or `core/query` block targeting `board_member` CPT sorted by `menu_order`. |
| **5. Core & Media Embeds** | `[embed]`, `[caption]`, `[gallery]`, `[video]` | Convert to native `core/embed`, `core/image` with `<figcaption>`, `core/gallery`, `core/video`. |
| **6. Plugin Integrations** | `[gview]`, `[mc4wp_form]` | `[gview]` $\rightarrow$ `core/file` block (`displayPreview: true`); `[mc4wp_form]` $\rightarrow$ `[eka_mailchimp_form]`. |
| **7. Numeric References / Brackets** | `[14]`, `[7837, 8088]`, `[Sigma]`, bracketed text | Map numeric ID lists to sub-page card grids; ignore bracketed regular text. |

### 3.3 FSE Inline Style Allowlist Sanitization (`sanitize_inline_styles_fse`)
- **Allowed Properties:** `flex-basis`, `flex-grow`, `flex-shrink`, `flex-direction`, `grid-template-columns`, `width`, `height`, `min-height`, `max-width`, `aspect-ratio`, `object-fit`, `vertical-align`, `text-align`.
- **Discarded Properties:** Legacy presentation styles (`font-family`, hardcoded `color`, `background-color`, `line-height`, `font-size`, `margin`, `padding`, `float`, `clear`).

---

## 4. NAVIGATION MENUS & LOCALIZATION ARCHITECTURE

### 4.1 WordPress Core Navigation Menu Locations
Registered in `inc/custom-features.php` under `after_setup_theme`:
- `main-menu`: Main Menu (Greek Default)
- `main-menu___en`: Main Menu (English)
- `main-menu___ar`: Main Menu (Arabic)
- `secondary-menu`: Secondary Menu
- `footer-menu`: Footer Menu
- `social-menu-bottom`: Social Menu Bottom

### 4.2 Legacy Navigation Menu Mapping
- **Greek (el / default):** `Main Greek Menu` (ID: 13) $\rightarrow$ Assigned to `main-menu`
- **English (en):** `Main English Menu` (ID: 3315) $\rightarrow$ Assigned to `main-menu___en`
- **Arabic (ar):** `Main Arabic Menu` (ID: 3316) $\rightarrow$ Assigned to `main-menu___ar`
- **Footer Navigation (el / default):** `Footer Greek Menu` (ID: 21) $\rightarrow$ Assigned to `footer-menu`

---

## 5. MIGRATION SCRIPTS & AUTOMATION ARCHITECTURE

The migration pipeline is structured into a 3-script execution workflow in `bin/`:

1. **`bin/01-reset-and-setup.sh` (Script 1):** Mirror production DB, sync web root with flagship theme exclusion, search-replace, delete legacy plugins (`rm -rf` fallback), activate flagship theme, import CPTs (`bin/migrate-cpts.php`).
2. **`bin/02-migrate-content.sh` (Script 2):** Content transformation engine (`bin/migration-content-engine.php`), applying homepage & news page slider exception lists, WPBakery remediation, and classic HTML AST block conversion (`bin/convert-classic-to-gutenberg.php`).
3. **`bin/03-assign-templates.sh` (Script 3):** FSE page template ID assignments (`bin/assign-page-templates.php`). Note: Menu location assignment and sidebar injection are removed from automation to be managed manually in WP Admin.
