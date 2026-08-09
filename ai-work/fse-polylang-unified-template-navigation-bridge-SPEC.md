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

#### Question 1: Should we assume navigation IDs in `menus.json` before migrating classic menus to FSE?
**Answer**: NO. Hardcoding `wp_navigation` post IDs in `menus.json` creates database ID collisions across environments (e.g. local vs staging vs production).  
**Resolution**:
- `ai-work/menus.json` strictly defines menu slugs, classic menu IDs (`classic_menu_id`), areas (`header`, `top_bar`, `footer`), and translation titles.
- `bin/migrate-classic-menus-to-fse.php` queries existing `wp_navigation` posts by title/slug or creates them dynamically, then programmatically injects the generated post IDs into template part navigation `ref` attributes during automated migration execution.

#### Question 2: Should we create an area definition in the template for these navigations?
**Options & Trade-offs**:
- **Option A (Dynamic `ref` ID Routing via Polylang Bridge - RECOMMENDED)**:
  - Template parts use a single canonical `ref` ID (or dynamically assigned ID). `inc/polylang-fse.php` intercepts `core/navigation` at render time and calls `pll_get_post($ref, $lang)` to swap `ref` to the translated navigation menu ID.
  - *Pros*: Simple, clean, standard FSE block paradigm. Single source of truth.
  - *Cons*: Requires `inc/polylang-fse.php` hook (already implemented).
- **Option B (Area Definition / Menu Location via `theme_mod`)**:
  - Assign navigation menus to theme locations (`main-menu`, `footer-menu`) and reference by area slug.
  - *Pros*: Standard classic theme pattern.
  - *Cons*: Gutenberg FSE `core/navigation` blocks in template parts require `ref` post IDs, not classic location slugs.

#### Question 3: Is it double work to translate menus in template files AND functions file?
**Answer**: YES, hardcoding translated `ref` IDs inside `parts/header-en.html` AND having `inc/polylang-fse.php` perform runtime translation switching is redundant double work.  
**Resolution**:
- `parts/*.html` template parts will reference the canonical `ref` ID (or area slug).
- Runtime translation switching is handled exclusively by `inc/polylang-fse.php` via `render_block_data` hook (`pll_get_post($ref, $lang)`).

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
- [x] Spec file `ai-work/fse-polylang-unified-template-navigation-bridge-SPEC.md` updated with architectural decisions.
- [x] Scoping file `ai-work/menus.json` contains classic menu IDs without hardcoded FSE post IDs.
- [x] Theme loads `inc/polylang-fse.php` in `functions.php`.
- [x] Classic to FSE menu migration script (`bin/migrate-classic-menus-to-fse.php`) dynamically resolves/creates `wp_navigation` posts using native `wp:navigation-submenu` and `wp:navigation-link` blocks.
- [x] Top bar uses a single shared `wp_navigation` post containing `wp:polylang/navigation-language-switcher`.
- [x] Footer menu converts classic footer menu items exclusively (without language switcher).
- [x] Migration script programmatically syncs navigation block references (`ref`) in template parts during deployment without manual editing.
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
│   ├── menus.json                      # Declarative scoping menu configuration
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
