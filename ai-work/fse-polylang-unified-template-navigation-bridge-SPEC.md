# SPECIFICATION: Unified Polylang FSE Template & Navigation Bridge

**TOPIC NAME**: `fse-polylang`  
**ISSUE NAME**: `unified-template-navigation-bridge`  

---

## 1. Objective & Target Users

### Objective
Unify and solidify multilingual Full Site Editing (FSE) support for WordPress using Polylang (Free version). With all language-specific template files (`single-en.html`, `page-ar.html`, etc.) deleted, all FSE templates rely on single canonical template files (`single.html`, `page.html`, `archive.html`, `front-page.html`, etc.) referencing generic template parts (`header`, `footer`).

This module (`inc/polylang-fse.php`) will:
1. Dynamically route generic `core/template-part` blocks (`header`, `footer`) to their localized template part files (`header-en.html`, `header-ar.html`, `footer-el.html`) based on active Polylang language (`pll_current_language()`).
2. Make `wp_navigation` post type translatable in Polylang Free using the `pll_get_post_types` filter (Nangka.dev pattern).
3. Expose FSE Navigation Menus in the WP Admin Dashboard (`Appearance > FSE Nav Menus`) for easy language association (`+` flags).
4. Dynamically route `core/navigation` blocks by mapping block `ref` attributes to translated `wp_navigation` post IDs via `pll_get_post($ref, $lang)`.

### Target Users
- **Site Visitors**: Receive fully localized headers, footers, and navigation menus matching the page language across all templates.
- **Content Editors & Admins**: Manage FSE navigation menus natively within WP Admin using standard Polylang UI.

---

## 2. Core Features & Acceptance Criteria

### Core Features
1. **Dedicated Module `inc/polylang-fse.php`**: Clean modular file required via `functions.php`.
2. **Template Part Language Switcher (`render_block_data` hook)**:
   - Intercepts `core/template-part` blocks.
   - If slug is `header` or `footer` (or any generic part), checks for `{slug}-{lang}.html` in theme `parts/`.
   - Replaces `$parsed_block['attrs']['slug']` dynamically before render.
3. **`wp_navigation` Polylang CPT Registration (`pll_get_post_types` hook)**:
   - Registers `wp_navigation` post type for translation tracking.
4. **FSE Navigation Admin Submenu (`admin_menu` hook)**:
   - Adds "FSE Navigation Menus" under `Appearance` (`edit.php?post_type=wp_navigation`).
5. **Navigation Block Auto-Translation (`render_block_data` hook)**:
   - Intercepts `core/navigation` blocks.
   - If `ref` attribute exists, queries `pll_get_post($ref, $lang)` to swap `ref` to the translated navigation menu post ID.
6. **Clean Up `inc/custom-features.php`**:
   - Remove legacy/redundant `pre_get_block_template` template file routing filters.

### Acceptance Criteria
- [x] Safe branch `experimental/fse-polylang-template-parts` created.
- [x] Spec file `ai-work/fse-polylang-unified-template-navigation-bridge-SPEC.md` created.
- [x] Theme loads `inc/polylang-fse.php` in `functions.php`.
- [x] Declarative `inc/menus.json` maps menu groups, areas, languages, and custom language switchers.
- [x] Classic to FSE menu migration script (`bin/migrate-classic-menus-to-fse.php`) converts classic `nav_menu` items to block-based `wp_navigation` posts with top bar Polylang language switcher.
- [x] Navigation assignment script (`bin/assign-nav-menus.php`) assigns menu locations & Polylang translation links.
- [x] Migration script execution orchestrated via `bin/03-assign-templates.sh` with dedicated log `ai-work/logs/03-assign-nav-menus.log`.
- [x] Visiting English pages automatically loads `parts/header-en.html` and `parts/footer-en.html`.
- [x] Visiting Arabic pages automatically loads `parts/header-ar.html` and `parts/footer-ar.html`.
- [x] Visiting Greek pages automatically loads `parts/header.html` (or `header-el.html`) and `parts/footer.html` (or `footer-el.html`).
- [x] Site Editor (`wp-admin/site-editor.php`) renders without block corruption or infinite loops (`!is_admin()` guard).
- [x] FSE Navigation menus appear in Polylang Admin dashboard with language translation icons.
- [x] PHP syntax check (`php -l`) passes on all modified PHP files.

---

## 3. Project Structure & Files Touched

```text
public/wp-content/themes/ekalexandria-flagship/
├── ai-work/
│   ├── fse-polylang-unified-template-navigation-bridge-SPEC.md
│   └── logs/
│       ├── 03-assign-templates.log
│       └── 03-assign-nav-menus.log
├── bin/
│   ├── 03-assign-templates.sh         # Stage 03 Orchestrator
│   ├── assign-page-templates.php      # FSE Page Template Assignments
│   ├── assign-nav-menus.php           # Nav Menu Locations & Polylang Linking
│   └── migrate-classic-menus-to-fse.php # Classic to FSE Block Menu Converter
├── functions.php                       # Load inc/polylang-fse.php
├── inc/
│   ├── custom-features.php             # General custom features & shortcodes
│   ├── menus.json                      # Declarative menu & area configuration
│   └── polylang-fse.php                # Unified Polylang FSE bridge module
├── parts/                              # Header/Footer FSE template parts
└── theme.json                          # Verified clean without customTemplates
```

---

## 4. Coding Standards & Boundaries

### Always Do
- Always guard Polylang calls with `function_exists('pll_current_language')` and `function_exists('pll_get_post')`.
- Always verify `!is_admin() && !wp_is_json_request()` before modifying block data at render time.
- Use `file_exists()` before switching template part slugs.

### Never Do
- Never modify core WordPress or Polylang plugin files.
- Never add hardcoded template files per language in `templates/`.
- Never execute database queries in `render_block_data` without defensive caching checks.

---

## 5. Testing & Verification Strategy

1. **Syntax Check**: `php -l inc/polylang-fse.php`, `php -l inc/custom-features.php`, `php -l functions.php`.
2. **Runtime Verification**:
   - Inspect loaded HTML for Greek, English, and Arabic URLs to confirm correct header/footer part injection.
   - Verify `wp_navigation` post translation lookup works as expected.
3. **Editor Verification**: Ensure WP Admin Site Editor functions properly without errors.
