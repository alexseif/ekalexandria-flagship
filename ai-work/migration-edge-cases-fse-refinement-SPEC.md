# SPECIFICATION: Migration Edge Cases & FSE Refinements

**TOPIC NAME**: `migration`  
**ISSUE NAME**: `edge-cases-fse-refinement`  

---

## 1. Objective & Target Users

### Objective
Resolve critical edge cases, block structural malfunctions, template tag duplications, MFN page sidebar layouts, and template/language assignment execution sequencing resulting from the legacy WPBakery / BeTheme to WordPress Full Site Editing (FSE) migration pipeline.

### Target Users
- **Site Visitors**: Visitors navigating Greek, English, and Arabic pages who expect clean, non-malformed layout rendering, functional sidebars, and localized headers/footers.
- **Content Editors & Admins**: Site administrators working in the WordPress FSE Block Editor who require valid AST block trees without corrupted column blocks or unassigned block templates.

---

## 2. Core Features & Acceptance Criteria

### Feature 1: Outer Semantic Tag Clean-up (`tagName` Deduplication)
- **Problem**: `"tagName":"header"` and `"tagName":"footer"` are defined both in `templates/*.html` block references (`wp:template-part`) and inside `parts/header*.html` / `parts/footer*.html` outer `wp:group` blocks, causing duplicate nested `<header><header>...</header></header>` tags in HTML DOM output.
- **Solution**: 
  - Remove `"tagName":"header"` and `"tagName":"footer"` from `wp:template-part` calls across all `templates/*.html` files.
  - Retain `"tagName":"header"` and `"tagName":"footer"` strictly on the top-level block inside the template part files (`parts/header*.html` and `parts/footer*.html`).
- **Acceptance Criteria**:
  - `curl` or AST inspection confirms single `<header>` and `<footer>` elements in generated DOM.
  - Zero loss of block attributes or styling on template parts.

### Feature 2: Smart `[vc_row]` / `[vc_column]` & Sub-grid Shortcode Remediation
- **Problem**: `step_4a_transform_wpbakery_and_caption` naively converts every `[vc_row]` into `wp:columns` and every `[vc_column width="1/1"]` into `wp:column style="flex-basis:100%"`. When wrapping `[vc_column_text]` or `[vc_posts_grid]`, this generates redundant nested column wrappers and invalid un-wrapped text nodes.
- **Solution**:
  - Update `04-shortcode-migrations.php` to analyze column layout before converting.
  - **Single 1/1 Column Rows**: Strip `vc_row` and `vc_column width="1/1"` wrappers entirely. Convert inner `[vc_column_text]` directly to native `<!-- wp:paragraph -->` (or heading/list) blocks. Wrap sub-grids (`[vc_posts_grid]`) in clean `wp:group` + `wp:query` blocks without outer `wp:columns` wrappers.
  - **Multi-column Rows** (e.g. `1/2 + 1/2`, `1/3 + 2/3`): Retain `wp:columns` and `wp:column` with calculated `flex-basis` percentages.
- **Acceptance Criteria**:
  - `[vc_row][vc_column width="1/1"][vc_column_text]Text...[/vc_column_text][/vc_column][/vc_row]` produces `<!-- wp:paragraph --><p>Text...</p><!-- /wp:paragraph -->`.
  - `[vc_row][vc_column width="1/1"][vc_posts_grid ...][/vc_column][/vc_row]` produces `<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:query ... -->...<!-- /wp:query --></div><!-- /wp:group -->`.
  - All transformed content passes `eka_validate_blocks_ast()` without AST syntax errors.

### Feature 3: MFN Sidebar Page Layout Support
- **Problem**: Pages previously utilizing BeTheme/MFN left or right sidebars lose their sidebar structure during FSE migration.
- **Solution**:
  - Create dedicated FSE sidebar page templates: `page-sidebar-left.html`, `page-sidebar-right.html`, and localized variants (`page-sidebar-left-en.html`, `page-sidebar-left-ar.html`, `page-sidebar-right-en.html`, `page-sidebar-right-ar.html`).
  - Read legacy postmeta (`mfn-post-sidebar`, `_mfn-post-sidebar`, `mfn_layout`) from `betheme-config-scoping.json` / `wp_postmeta`.
  - Programmatically assign corresponding sidebar templates (`_wp_page_template`) for pages with active MFN sidebars.
- **Acceptance Criteria**:
  - Pages with legacy left/right sidebars render content in a 2-column flex layout with the respective sidebar template part (`parts/sidebar-child-pages.html` or `parts/sidebar-news.html`).

### Feature 4: Deferred Language & Template Assignment with Cache/Transient Flushes
- **Problem**: Template assignments fail to take effect if applied before theme block template transients and Polylang language tax caches are invalidated.
- **Solution**:
  - Execute template assignment logic late in the pipeline (in `bin/assign-page-templates.php` / Stage 06).
  - Explicitly call `delete_transient('wp_theme_files_')`, `wp_cache_flush()`, and Polylang cache invalidations before updating `_wp_page_template`.
  - Map page templates according to strict language rules:
    - Homepage Greek: `front-page` (template `front-page.html`)
    - Homepage English: `front-page-en` (template `front-page-en.html`)
    - Homepage Arabic: `front-page-ar` (template `front-page-ar.html`)
    - Internal pages Greek: `page` (template `page.html`)
    - Internal pages English: `page-en` (template `page-en.html`)
    - Internal pages Arabic: `page-ar` (template `page-ar.html`)
- **Acceptance Criteria**:
  - Querying `_wp_page_template` postmeta returns exact expected slugs.
  - Dynamic template part routing and FSE template matching resolve correctly in frontend rendering and WP-CLI runtime verification.

---

## 3. Project Structure & Files Touched

```text
public/wp-content/themes/ekalexandria-flagship/
├── ai-work/
│   └── migration-edge-cases-fse-refinement-SPEC.md
├── bin/
│   ├── 04-shortcode-migrations.php           # Refine vc_row/vc_column 1/1 unwrapping logic
│   ├── assign-page-templates.php             # Add transient flushing & late template assignment
│   └── 06-assign-templates-and-menus.sh      # Ensure execution after database resets & conversions
├── parts/
│   ├── header.html                           # Retain tagName:"header" on top-level group
│   ├── header-en.html
│   ├── header-ar.html
│   ├── footer.html                           # Retain tagName:"footer" on top-level group
│   ├── footer-en.html
│   └── footer-ar.html
└── templates/
    ├── front-page.html                       # Remove duplicate tagName from template-part blocks
    ├── front-page-en.html
    ├── front-page-ar.html
    ├── page.html
    ├── page-en.html
    ├── page-ar.html
    ├── page-sidebar-left.html                # NEW: Left sidebar layout template
    ├── page-sidebar-right.html               # NEW: Right sidebar layout template
    ├── page-sidebar-left-en.html             # NEW: Localized left sidebar template
    └── page-sidebar-right-en.html            # NEW: Localized right sidebar template
```

---

## 4. Architecture & Design Options

### Design Decision 1: `tagName` Placement Strategy
- **Option A (Chosen)**: Place `tagName` strictly inside `parts/header*.html` and `parts/footer*.html` outer group blocks. Keep `wp:template-part` references in `templates/*.html` free of `tagName` attributes.
  - *Pros*: Aligns with standard FSE pattern where template parts encapsulate their own semantic container tags; avoids outer `<header class="wp-block-template-part">` duplicate wrappers.
  - *Cons*: None.
- **Option B**: Remove `tagName` from `parts/header*.html` and specify `"tagName":"header"` on `wp:template-part` call in `templates/*.html`.
  - *Trade-off*: Leaves template part markup non-semantic if previewed in isolation.

### Design Decision 2: Single Column `[vc_row]` Unwrapping Strategy
- **Option A (Chosen)**: Structural un-nesting prior to block conversion. If `vc_row` contains a single `vc_column` with `width="1/1"` (or no width attribute), remove the row/column wrapper tags completely and process inner content (`vc_column_text`, `vc_posts_grid`) directly into root Gutenberg blocks.
  - *Pros*: Clean DOM, no unnecessary flex/grid wrappers, perfectly resolves the bug reported in `vc_posts_grid` transformation.
  - *Cons*: Requires two-pass regex parser or AST pre-processor in `04-shortcode-migrations.php`.

---

## 5. Testing & Verification Strategy

1. **Unit & AST Validation**:
   - Run `php bin/test-fse-sanitizer.php` to ensure helper utilities and AST validation pass.
   - Run `php -l bin/04-shortcode-migrations.php` and `php -l bin/assign-page-templates.php`.
2. **Shortcode & AST Execution Test**:
   - Run `bin/04-shortcode-migrations.php` on staging database and verify 0 AST validation failures.
   - Inspect `ai-work/logs/04-shortcode-migrations.log` and `ai-work/logs/missed-shortcodes.log`.
3. **Template & Language Assignment Verification**:
   - Execute `wp eval-file bin/assign-page-templates.php` via WP-CLI.
   - Run SQL check on `_wp_page_template` for homepages and internal pages across Greek, English, and Arabic.
4. **Runtime Frontend Verification**:
   - Run `php bin/test-runtime-render.php` or `curl` against key URLs (`/`, `/en/`, `/ar/`, `/services/`, etc.) to confirm valid single `<header>`/`<footer>` tags and expected grid rendering.

---

## 6. Known Boundaries & Constraints

### Always Do
- Always preserve existing functional transformations in `04-shortcode-migrations.php` (e.g. sliders, captions, testimonials).
- Always run AST validation (`eka_validate_blocks_ast()`) before saving any transformed content to `wp_posts`.
- Always flush transients and caches before querying or assigning `_wp_page_template`.
- Stop and request human approval before committing git changes.

### Ask First About
- Modifying CPT archive templates (`archive-alx_tachydromos.html`, `archive-board_member.html`).
- Adjusting menu IDs or navigation ref attributes in header template parts.

### Never Do
- Never loop more than 3 times when debugging an execution failure (halt and ask human for intervention).
- Never allow nested `wp:columns` wrappers around single full-width (1/1) column blocks.
- Never hardcode database connection strings outside `migration-helpers.php`.
