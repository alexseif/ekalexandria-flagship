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

---

## 2. Architecture & Design Trade-offs

### Clarification on Questions & Design Principles

#### Question 1: Scoping Directory & Configuration Location
- `ai-work/scoping/menus.json` is the sole source of truth for menu migration configurations.
- `ai-work/scoping/menus.json` defines classic menu IDs (`classic_menu_id`), menu slugs, areas (`header`, `top_bar`, `footer`), and translation titles without hardcoding `wp_navigation` post IDs.

#### Question 2: Top Bar & Footer Menu Translations
- **Top Bar**: Uses a single shared `wp_navigation` post ("Top Bar Language Switcher") fit for all languages (`single_shared_menu: true`), containing `wp:polylang/navigation-language-switcher`.
- **Footer Menu**: Converts classic footer menu items (`el`, `en`, `ar`) into `wp_navigation` posts and links them in Polylang via `pll_save_post_translations`, matching the main header menu setup.

#### Question 3: Option A Loading Architecture & Redundancy Removal
- **Loading Architecture (Option A)**:
  - Navigation blocks inside template parts (`parts/header*.html`, `parts/footer*.html`) reference the canonical (Greek) `ref` ID.
  - On render, `inc/polylang-fse.php` intercepts `core/navigation` via `render_block_data` and automatically swaps `ref` to the active language's `wp_navigation` post ID using `pll_get_post($ref, $current_lang)`.
- **Ending Functional Result**:
  - `parts/header-en.html`, `parts/header-ar.html`, `parts/footer-en.html`, and `parts/footer-ar.html` remain in place to render translated static text (e.g. localized copyright and site title branding).
  - The navigation block inside ALL header/footer template parts uses the exact same canonical `ref` ID.
  - Zero hardcoded translated menu IDs inside template part HTML files!

---

## 3. Core Features & Acceptance Criteria

### Core Features
1. **Dedicated Module `inc/polylang-fse.php`**: Clean modular file required via `functions.php`.
2. **Template Part Language Switcher (`render_block_data` hook)**:
   - Intercepts `core/template-part` blocks.
   - Switches slug dynamically based on language (`header-en.html`, `footer-ar.html`).
3. **`wp_navigation` Polylang CPT Registration (`pll_get_post_types` hook)**:
   - Registers `wp_navigation` post type for translation tracking.
4. **FSE Navigation Admin Submenu (`admin_menu` hook)**:
   - Adds "FSE Navigation Menus" under `Appearance` (`edit.php?post_type=wp_navigation`).
5. **Navigation Block Auto-Translation (`render_block_data` hook)**:
   - Intercepts `core/navigation` blocks.
   - Swaps `ref` to the translated navigation menu post ID via `pll_get_post($ref, $lang)`.

### Acceptance Criteria
- [x] Safe branch `experimental/fse-polylang-template-parts` created.
- [x] Spec file `ai-work/fse-polylang-unified-template-navigation-bridge-SPEC.md` updated with Option A architecture & scoping location.
- [x] Scoping file `ai-work/scoping/menus.json` contains classic menu IDs without hardcoded FSE post IDs.
- [x] Theme loads `inc/polylang-fse.php` in `functions.php`.
- [x] Classic to FSE menu migration script (`bin/migrate-classic-menus-to-fse.php`) dynamically resolves/creates `wp_navigation` posts using native `wp:navigation-submenu` and `wp:navigation-link` blocks.
- [x] Top bar uses a single shared `wp_navigation` post containing `wp:polylang/navigation-language-switcher`.
- [x] Footer menu converts classic footer menu items and links translations in Polylang (`pll_save_post_translations`).
- [x] Migration script programmatically syncs canonical navigation block references (`ref`) in template parts during deployment without manual editing.
- [x] Navigation assignment script (`bin/assign-nav-menus.php`) assigns menu locations & Polylang translation links.
- [x] Migration script execution orchestrated via `bin/03-assign-templates.sh` with dedicated log `ai-work/logs/03-assign-nav-menus.log`.
- [x] Visiting English pages automatically loads `parts/header-en.html` and `parts/footer-en.html`.
- [x] Visiting Arabic pages automatically loads `parts/header-ar.html` and `parts/footer-ar.html`.
- [x] Visiting Greek pages automatically loads `parts/header.html` and `parts/footer.html`.
- [x] Site Editor (`wp-admin/site-editor.php`) renders without block corruption or infinite loops (`!is_admin()` guard).
- [x] PHP syntax check (`php -l`) passes on all modified PHP files.

---

## 4. Project Structure & Files Touched

```text
public/wp-content/themes/ekalexandria-flagship/
├── ai-work/
│   ├── fse-polylang-unified-template-navigation-bridge-SPEC.md
│   ├── scoping/
│   │   └── menus.json                 # Scoping menu configuration
│   └── logs/
│       ├── 03-assign-templates.log
│       └── 03-assign-nav-menus.log
├── bin/
│   ├── 03-assign-templates.sh         # Stage 03 Orchestrator
│   ├── assign-page-templates.php      # FSE Page Template Assignments
│   ├── assign-nav-menus.php           # Nav Menu Locations & Polylang Linking
│   └── migrate-classic-menus-to-fse.php # Classic to FSE Block Menu Converter & Template Ref Updater
├── functions.php                       # Load inc/polylang-fse.php
├── inc/
│   ├── custom-features.php             # General custom features & shortcodes
│   └── polylang-fse.php                # Unified Polylang FSE bridge module
├── parts/                              # Header/Footer FSE template parts
└── theme.json                          # Verified clean without customTemplates
```
