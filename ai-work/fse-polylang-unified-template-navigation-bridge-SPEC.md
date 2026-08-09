# SPECIFICATION: Unified Polylang FSE Template & Navigation Bridge

**TOPIC NAME**: `fse-polylang`  
**ISSUE NAME**: `unified-template-navigation-bridge`  

---

## 1. Objective & Target Users

### Objective
Unify and solidify multilingual Full Site Editing (FSE) support for WordPress using Polylang (Free version). With all language-specific template files (`single-en.html`, `page-ar.html`, etc.) deleted, all FSE templates rely on single canonical template files (`single.html`, `page.html`, `archive.html`, `front-page.html`, etc.) referencing single canonical template parts (`header.html`, `footer.html`).

This module (`inc/polylang-fse.php`) will:
1. Make `wp_navigation` post type translatable in Polylang Free using the `pll_get_post_types` filter (Nangka.dev pattern).
2. Expose FSE Navigation Menus in the WP Admin Dashboard (`Appearance > FSE Nav Menus`) for easy language association (`+` flags).
3. Dynamically route `core/navigation` blocks by mapping block `ref` attributes to translated `wp_navigation` post IDs via `pll_get_post($ref, $lang)`.

---

## 2. Architecture & Consolidated Migration Flow

### Key Principles

1. **Scoping Configuration (`ai-work/scoping/menus.json`)**:
   - Contains classic menu configuration (`classic_menu_id`), menu slugs, areas (`header`, `top_bar`, `footer`), and translation titles.
   - Contains zero hardcoded `wp_navigation` post IDs.

2. **Consolidated Migration & Assignment (`bin/migrate-classic-menus-to-fse.php`)**:
   - Converts classic menus (`nav_menu`) into `wp_navigation` Gutenberg block posts (`el`, `en`, `ar`).
   - Links translated FSE navigation posts via `pll_save_post_translations`.
   - Generates the Top Bar Polylang language switcher navigation post ("Top Bar Language Switcher") containing `wp:polylang/navigation-language-switcher`.
   - Assigns classic `nav_menu_locations` in `theme_mod`.
   - Programmatically updates the generated Greek (`el`) `wp_navigation` post IDs into `parts/header.html` and `parts/footer.html` in a single pass.

3. **Single Canonical Header & Footer Parts**:
   - `parts/header.html` and `parts/footer.html` are the ONLY header/footer template parts in the theme.
   - On page load, `inc/polylang-fse.php` intercepts `core/navigation` (`render_block_data` filter) and automatically swaps `ref` to the active language's `wp_navigation` post ID using `pll_get_post($ref, $current_lang)`.

---

## 3. Core Features & Acceptance Criteria

### Core Features
1. **Dedicated Module `inc/polylang-fse.php`**: Required via `functions.php`.
2. **`wp_navigation` Polylang CPT Registration (`pll_get_post_types` hook)**:
   - Registers `wp_navigation` post type for translation tracking.
3. **FSE Navigation Admin Submenu (`admin_menu` hook)**:
   - Adds "FSE Navigation Menus" under `Appearance` (`edit.php?post_type=wp_navigation`).
4. **Navigation Block Auto-Translation (`render_block_data` hook)**:
   - Intercepts `core/navigation` blocks.
   - Swaps `ref` to the translated navigation menu post ID via `pll_get_post($ref, $lang)`.

### Acceptance Criteria
- [x] Safe branch `experimental/fse-polylang-template-parts` created.
- [x] Spec file `ai-work/fse-polylang-unified-template-navigation-bridge-SPEC.md` updated with consolidated migration & single header/footer architecture.
- [x] Scoping file `ai-work/scoping/menus.json` contains classic menu IDs without hardcoded FSE post IDs.
- [x] Theme loads `inc/polylang-fse.php` in `functions.php`.
- [x] Consolidated migration script (`bin/migrate-classic-menus-to-fse.php`) dynamically creates `wp_navigation` posts, links Polylang translations, generates top bar switcher, assigns classic menu locations, and injects generated Greek FSE post IDs into `parts/header.html` and `parts/footer.html`.
- [x] Top bar uses a single shared `wp_navigation` post containing `wp:polylang/navigation-language-switcher`.
- [x] Footer menu converts classic footer menu items and links translations in Polylang (`pll_save_post_translations`).
- [x] Migration script execution orchestrated via `bin/03-assign-templates.sh` with dedicated log `ai-work/logs/03-assign-nav-menus.log`.
- [x] Visiting English pages automatically renders English header and footer menus.
- [x] Visiting Arabic pages automatically renders Arabic header and footer menus.
- [x] Visiting Greek pages automatically renders Greek header and footer menus.
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
│   └── migrate-classic-menus-to-fse.php # Consolidated Classic to FSE Block Menu Converter & Template Ref Updater
├── functions.php                       # Load inc/polylang-fse.php
├── inc/
│   ├── custom-features.php             # General custom features & shortcodes
│   └── polylang-fse.php                # Unified Polylang FSE bridge module
├── parts/
│   ├── header.html                    # Canonical Header Template Part
│   └── footer.html                    # Canonical Footer Template Part
└── theme.json                          # Verified clean without customTemplates
```
