# MIGRATION WORK STATUS, GAP ANALYSIS & SCRIPT CLEANUP PLAN

**Project:** Greek Community of Alexandria (EKA) Portal Modernization  
**Theme:** `ekalexandria-flagship` (Gutenberg Full Site Editing Theme)  
**Database Context:** `backstage_eka` (Development/Staging Target DB)  
**Base PHP Target:** PHP 7.4 (Migration & Transformation) $\rightarrow$ PHP 8.2 (Production Runtime)  
**Document Purpose:** Complete audit of work completed, missing work, gap analysis, block validation fixes, shortcode recommendations, and script cleanup plan.

---

## 1. EXECUTIVE SUMMARY & IMPLEMENTATION STATUS MATRIX

This document tracks the current execution status of the EKA Portal migration, detailing what has been completed, what requires active remediation, what requires manual admin intervention, and the script cleanup plan.

### Master Implementation Status Overview

| Component / Task | Status | Action Required & Gap Analysis |
| :--- | :--- | :--- |
| **Manual Site Admin Actions** | `[PENDING MANUAL]` | Delete old news page, set new news page under parent, update main Greek menu link. |
| **Front Page Templates** | `[WORK NEEDED]` | Assign page IDs (`13236`, `16894`, `16892`); add LayerSlider to Exception List (strip shortcode & container). |
| **Post List / Index Pages** | `[WORK NEEDED]` | Assign page IDs (`18`, `16920`, `16923`); add LayerSlider to Exception List; render 25% right category sidebar menu. |
| **Single Post (`single.html`)** | `[PARTIALLY IMPLEMENTED]` | Template core implemented; missing Social Share Button component & Polylang mapping for `single-en` / `single-ar`. |
| **Newsletter Listing (`alx_tachydromos`)** | `[WORK NEEDED]` | Grid view of newsletter PDF issues, paginated by year. |
| **Newsletter Single (`single-alx_tachydromos`)** | `[WORK NEEDED]` | Fix Gutenberg `core/file` AST invalid content error (strip invalid `aria-label` attributes from block markup). |
| **Newsletter Create / Edit (Admin)** | `[WORK NEEDED]` | Custom admin PDF upload metabox; PDF-to-PNG save hook; render viewer in FSE template, NOT `post_content`. |
| **Board Page (`board_member`)** | `[NEEDS HUMAN REVISION]` | CPT & translation group scoping complete; page layout needs human review (separate from BeTheme `our_team` staff shortcodes). |
| **Shortcode Remediation Engine** | `[WORK NEEDED]` | Implement LayerSlider Exception List and individual missed shortcode handlers (excluding `vc_row`/`vc_column` which are already implemented). |
| **Migration Pipeline (`bin/`)** | `[WORK NEEDED]` | `01-reset-and-setup.sh` (Good); rename `03` $\rightarrow$ `02-migrate-content.sh`; rename `06` $\rightarrow$ `03-assign-templates.sh` (strip menu assignments). |
| **Legacy Script Cleanup** | `[NEEDS HUMAN REVISION]` | Execute script cleanup table (keep core 3-stage pipeline, deprecate redundant runners). |

---

## 2. MANUAL ADMIN ACTIONS CHECKLIST

The following tasks must be performed manually in WP Admin:
1. **Delete Old News Page:** Remove legacy WPBakery news page placeholder in WP Admin.
2. **Set New News Page:** Create fresh Gutenberg News page and set as Posts Page under parent hierarchy.
3. **Edit Main Greek Menu:** Update `Main Greek Menu` navigation to point to the newly assigned News page.

---

## 3. COMPONENT GAP ANALYSIS & UPDATE CRITERIA

### 3.1 Front Page (`front-page.html`, `front-page-en.html`, `front-page-ar.html`)
- **Status:** `[WORK NEEDED]`
- **LayerSlider Exception Rule:** Front page LayerSliders are built natively into FSE templates. Front page IDs (`13236`, `16894`, `16892`) are added to an **Exception List**. During content migration, shortcodes (`[layerslider]`, `[rev_slider]`) and their wrapper containers are stripped cleanly.
- **Page ID Assignments (`bin/03-assign-templates.sh`):**
  - `13236` $\rightarrow$ `front-page` (Greek Default)
  - `16894` $\rightarrow$ `front-page-en` (English)
  - `16892` $\rightarrow$ `front-page-ar` (Arabic)

### 3.2 Post List Page / News Index (`index.html`, `index-en.html`, `index-ar.html`)
- **Status:** `[WORK NEEDED]`
- **LayerSlider Exception Rule:** News pages are added to the **Exception List**. Shortcodes (`[layerslider]`, `[rev_slider]`) and wrapper containers are stripped during content migration.
- **Page ID Assignments (`bin/03-assign-templates.sh`):**
  - `18` $\rightarrow$ `index` (Greek Default)
  - `16920` $\rightarrow$ `index-en` (English)
  - `16923` $\rightarrow$ `index-ar` (Arabic)
- **Sidebar Integration:** 2-column flex layout (75% main news post query loop, 25% right category sidebar menu via `parts/sidebar-news.html`).

### 3.3 Single Post Page (`single.html`, `single-en.html`, `single-ar.html`)
- **Status:** `[PARTIALLY IMPLEMENTED]`
- **Gap 1 - Social Share Buttons:** Embed lightweight social sharing component (Facebook, Twitter/X, LinkedIn, WhatsApp, Email) beneath post content.
- **Gap 2 - Polylang Posts Page Mapping:** Configure dynamic template part routing & Polylang posts page binding so `single-en` and `single-ar` correctly map localized headers/footers and permalinks.

### 3.4 Newsletter Listing Page (`archive-alx_tachydromos.html` / `tachydromos.html`)
- **Status:** `[WORK NEEDED]`
- Render grid of newsletter issues paginated by year. Card displays PNG cover thumbnail, normalized Greek month/year title (e.g. "Ιούνιος 2026"), "View PDF" button, and direct download link.

### 3.5 Newsletter Single Page (`single-alx_tachydromos.html`) & Block Validation Fix
- **Status:** `[WORK NEEDED]`
- **Gutenberg Editor Block Invalid Content Fix:**
  - *Invalid Generator Output (DO NOT USE - Triggers "Block contains unexpected or invalid content"):*
    ```html
    <!-- wp:file {"id":72271,"href":"https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf","displayPreview":true} -->
    <div class="wp-block-file"><object class="wp-block-file__embed" data="https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf" type="application/pdf" style="width:100%;height:600px" aria-label="Embed of Ιούνιος 2026"></object><a href="https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf">Ιούνιος 2026</a><a href="https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf" class="wp-block-file__button wp-element-button" download aria-label="Λήψη Ιούνιος 2026">Λήψη</a></div>
    <!-- /wp:file -->
    ```
  - *Validated Native Gutenberg Block Output (REQUIRED):*
    ```html
    <!-- wp:file {"id":72271,"href":"https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf","displayPreview":true} -->
    <div class="wp-block-file"><object class="wp-block-file__embed" data="https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf" type="application/pdf" style="width:100%;height:600px" aria-label="Ιούνιος 2026"></object><a href="https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf">Ιούνιος 2026</a><a href="https://backstage.ekalexandria.org/wp-content/uploads/2026/07/06-26.pdf" class="wp-block-file__button wp-element-button" download>Λήψη</a></div>
    <!-- /wp:file -->
    ```

### 3.6 Newsletter Admin Create / Edit UI Architecture
- **Status:** `[WORK NEEDED]`
- **Admin PDF Upload Metabox:** Separate custom metabox registered for `alx_tachydromos` edit screen in WP Admin allowing editors to select/upload a PDF file (`_eka_pdf_attachment_id`).
- **Save Hook Image Generation:** `save_post_alx_tachydromos` hook automatically converts PDF page 1 into a PNG/JPG featured image using ImageMagick (`convert -density 150 <pdf>[0] ...`).
- **Separation of Concerns:** PDF embed markup is rendered dynamically by the FSE single block template (`single-alx_tachydromos.html`), NOT hardcoded inside `post_content`.

### 3.7 Board of Directors Page (`board_member`)
- **Status:** `[NEEDS HUMAN REVISION]`
- CPT `board_member` registered with REST support, `menu_order` ordering, zero `<img>` tags inside body text, and Polylang translation groups mapped across EL, EN, AR. Page layout needs human review. Note: `board_member` is strictly decoupled from legacy BeTheme `our_team` staff shortcodes.

---

## 4. MISSED SHORTCODES ANALYSIS & RECOMMENDATIONS TABLE

*(Extracted from `missed-shortcodes.json` & `missed-shortcodes.log`. Note: `vc_row` and `vc_column` are already fully implemented in `bin/migration-content-engine.php` and excluded below).*

| Shortcode Tag | Logged Occurrences | Sample Raw Shortcode | Recommended Remediation Action / Block Mapping |
| :--- | :--- | :--- | :--- |
| **`[embed]`** | 74 | `[embed]https://youtu.be/SwE-OTtqqtc[/embed]` | Transform to native Gutenberg `core/embed` block with `providerNameSlug: "youtube"`. |
| **`[video]`** | 7 | `[video mp4="https://...mp4"][/video]` | Transform to native Gutenberg `core/video` block embedding HTML `<video src="...">`. |
| **`[hr]`** | 5 | `[hr height="30" style="default"]` | Transform to native Gutenberg `core/spacer` or `core/separator` (`height: 30px`). |
| **`[map]`** | 6 | `[map lat="31.19" lng="29.89"]` | Transform to HTML iframe embed or Google Maps block wrapper. |
| **`[gview]`** | 1 | `[gview file="...pdf"]` | Transform to native Gutenberg `core/file` block (`displayPreview: true`). |
| **`[mc4wp_form]`** | 1 | `[mc4wp_form]` | Replace with custom theme shortcode `[eka_mailchimp_form]`. |
| **`[our_team_list]`** | 1 | `[our_team_list]Member Text...` | Transform to static `core/group` member cards. (Separate from `board_member` CPT). |
| **Numeric Arrays** | 35 | `[7399,7397,7395,7390,...]` | Transform to dynamic sub-page grid block `eka/homepage-services-grid` or `core/query`. |
| **Numeric IDs** | 32 | `[14]`, `[13369]`, `[16892]` | Transform to single sub-page card grid or parent page link card block. |
| **Bracketed Text False Positives** | 18 | `[Sigma]`, `[Greek]`, `[during World War II]` | Ignore in regex transformer to preserve inline text content without breaking HTML. |

---

## 5. MIGRATION SCRIPTS PIPELINE & SCRIPT CLEANUP PLAN

### 5.1 Revised 3-Stage Shell Script Pipeline
1. **`bin/01-reset-and-setup.sh` (Script 1):** `[GOOD / IMPLEMENTED]`
   - Mirror production DB, sync web root with flagship theme exclusion, search-replace, delete legacy plugins (`rm -rf` fallback), activate flagship theme, import CPTs (`bin/migrate-cpts.php`).
2. **`bin/02-migrate-content.sh` (Script 2 - Renamed from 03):** `[WORK NEEDED]`
   - Run shortcode & content transformation engine (`bin/migration-content-engine.php`), applying homepage & news page slider exception lists and classic HTML AST conversion.
3. **`bin/03-assign-templates.sh` (Script 3 - Renamed from 06):** `[WORK NEEDED]`
   - Run FSE page template assignments (`assign-page-templates.php`).
   - **REMOVED:** Automatic menu assignment and sidebar injection removed (menus and sidebars will be assigned manually).

### 5.2 Script Audit & Cleanup Table

| Script Path | Description / Purpose | Proposed Action | Reason / Notes |
| :--- | :--- | :--- | :--- |
| `bin/01-reset-and-setup.sh` | Main Script 1: Reset DB, sync files, setup theme/CPTs. | **KEEP** | Core pipeline entry point. |
| `bin/02-migrate-content.sh` | Main Script 2: Content transformation & shortcode remediation. | **KEEP (RENAMED)** | Formerly `bin/03-migrate-content.sh`. |
| `bin/03-assign-templates.sh` | Main Script 3: FSE Page Template ID assignments. | **KEEP (RENAMED)** | Formerly `bin/06-assign-templates-and-menus.sh`. Menu logic stripped. |
| `bin/migration-content-engine.php` | Driver for content transformation & shortcode parsing. | **KEEP** | Executed by Script 2. |
| `bin/convert-classic-to-gutenberg.php` | Driver for classic HTML to AST block conversion. | **KEEP** | Executed by Script 2. |
| `bin/assign-page-templates.php` | Driver for assigning FSE template slugs to post IDs. | **KEEP** | Executed by Script 3. |
| `bin/migrate-cpts.php` | Driver for Tachydromos PDF & Board Member CPT import. | **KEEP** | Executed by Script 1. |
| `bin/pre-flight.sh` | Pre-execution sanity check script. | **KEEP** | Executed by Script 1. |
| `bin/03-surgical-migrations.php` | Legacy surgical slider/shortcode script. | **DELETE** | Merged into `migration-content-engine.php`. |
| `bin/04-shortcode-migrations.php` | Old shortcode migration runner. | **DELETE** | Merged into `migration-content-engine.php`. |
| `bin/05-classic-editor-migrations.php` | Old classic editor migration runner. | **DELETE** | Merged into `convert-classic-to-gutenberg.php`. |
| `bin/inject-sidebar-menus.php` | Injected sidebar menus into posts. | **DELETE** | Menus and sidebars assigned manually. |
| `bin/remediate-shortcodes-to-blocks.php` | Old shortcode remediation runner. | **DELETE** | Merged into `migration-content-engine.php`. |
| `bin/run-scopings.sh` | CLI runner for legacy scoping scripts. | **NEEDS HUMAN REVISION** | Review if required for re-scoping. |
| `bin/scope-*.php` | Legacy scoping scripts (`scope-legacy-items.php`, etc.). | **NEEDS HUMAN REVISION** | Retain in `bin/` or archive to `ai-work/scratch/`. |
| `bin/*.js` | DOM comparison & node scripts (`compare-dom.js`, etc.). | **NEEDS HUMAN REVISION** | Retain for Playwright testing. |
