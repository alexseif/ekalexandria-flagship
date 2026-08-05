# Specification: Scoping & Modular Migration Remediation

**TOPIC NAME**: Migration Pipeline Optimization  
**ISSUE NAME**: Scoping & Modular Migration Remediation  
**SPEC PATH**: `public/wp-content/themes/ekalexandria-flagship/ai-work/MIGRATION-MODULARIZATION-SPEC.md`

---

## 1. Objective & Target Scope

The goal of this specification is to refine the EKA portal migration pipeline (`db207080_eka` database target) based on legacy scoping data in `ai-work/scopings/`.

Key objectives:
1. **Fix Navigation Menu Assignments**: Eliminate cross-language menu pollution (e.g. Greek front page showing Arabic navigation) by updating FSE header template parts and standardizing location bindings.
2. **Enforce Strict Cache Flush Order**: Ensure `wp transient delete --all` and `wp cache flush` are executed **after** content transformations and **before** page template and menu location assignments.
3. **Deconstruct Content Engine into Sequential Numbered Scripts**: Separate the monolithic content migration engine into discrete, numbered scripts (`03`, `04`, `05`, `06`) to prevent unintended data loss or regression and maintain deterministic pipeline ordering.

---

## 2. Core Architectural & Technical Findings

### 2.1 Navigation & Menu Root Cause
- **Finding**: `parts/header-el.html` and `parts/header.html` contain hardcoded navigation block references (`<!-- wp:navigation {"ref":72611} /-->`), pointing to a specific `wp_navigation` post ID that was misaligned or overridden by Arabic menu data.
- **Solution**:
  - Update `header-el.html` and `header.html` to use slug-based references (`<!-- wp:navigation {"slug":"main-menu"} /-->`).
  - Align `assign-menus.php` with scoping names (`Main Greek Menu` -> ID 13, `Main English Menu` -> ID 3315, `Main Arabic Menu` -> ID 3316, `Footer Greek Menu` -> ID 21).

### 2.2 Execution Order & Cache Invalidation
- **Current Issue**: Template and menu assignments occurred prior to clearing cached transients, leaving stale menu locations and template meta active in WP option cache.
- **Target Sequential Pipeline Execution**:
  ```
  Step 03: bin/03-surgical-migrations.php
  Step 04: bin/04-shortcode-migrations.php
  Step 05: bin/05-classic-editor-migrations.php
  Step 06: bin/06-assign-templates-and-menus.sh (Runs transient delete + cache flush BEFORE assign-page-templates.php & assign-menus.php)
  ```

### 2.3 Modular Content Migration Split (Numbered Pipeline)
Separate transformations into 3 standalone, idempotent PHP scripts followed by orchestration:
1. `bin/03-surgical-migrations.php`:
   - Specific page ID transformations (dynamic Query Loop on home/news, static Gallery block creation using media IDs, testimonials board query, VC posts grid cards).
2. `bin/04-shortcode-migrations.php`:
   - Global structural shortcode conversions (`vc_row`, `vc_column`, `vc_single_image`, `[our_team]`, `[caption]`, residual shortcode wrapping).
3. `bin/05-classic-editor-migrations.php`:
   - HTML-to-Gutenberg block conversion (`<p>`, `<h1>`-`<h6>`, `<ul>`, `<ol>`, `<table>`, `<blockquote>`) with FSE CSS allowlist sanitization.
4. `bin/06-assign-templates-and-menus.sh`:
   - Flushes transients & object cache, assigns FSE page templates (`assign-page-templates.php`), assigns menu locations (`assign-menus.php`, `seed-footer-menus.php`), and injects sidebars.

---

## 3. Project Structure & File Deliverables

| Target File | Purpose / Change Description |
| :--- | :--- |
| `parts/header-el.html` | Change navigation block ref `72611` to `{"slug":"main-menu"}` |
| `parts/header.html` | Change navigation block ref `72611` to `{"slug":"main-menu"}` |
| `bin/03-surgical-migrations.php` | Independent surgical migration script for sliders, galleries, and board queries |
| `bin/04-shortcode-migrations.php` | Independent shortcode remediation script for WPBakery & structural tags |
| `bin/05-classic-editor-migrations.php` | Independent classic editor & HTML block conversion script |
| `bin/06-assign-templates-and-menus.sh` | Orchestration script running cache flush, template assignments, and menu assignments |
| `bin/assign-menus.php` | Updated menu mapping and location assignment script |

---

## 4. Selection & Architectural Decision

**Selected Approach: Numbered Sequential Scripts (Option 1 - Custom Numbering)**
- **Structure**: Create numbered scripts (`03`, `04`, `05`, `06`) to ensure strict, unambiguous execution order.
- **Benefits**: Modular isolation, clear log tracking per stage, zero state collision, easy debugging, transparent execution flow.

---

## 5. Verification Strategy & Acceptance Criteria

1. **Menu Assignment Verification**:
   - Verify `parts/header-el.html` references `"slug":"main-menu"`.
   - Run `wp eval-file bin/assign-menus.php` and verify `nav_menu_locations` returns:
     - `main-menu` => 13
     - `main-menu___en` => 3315
     - `main-menu___ar` => 3316
     - `social-menu-bottom` => 21

2. **Cache Invalidation & Order Verification**:
   - Ensure `wp transient delete --all` and `wp cache flush` run in `06-assign-templates-and-menus.sh` before `assign-page-templates.php` and `assign-menus.php`.

3. **AST Validation**:
   - All content modifications in `03`, `04`, `05` must pass `eka_validate_blocks_ast()` before updating `wp_posts`.

---

## 6. Known Boundaries & Explicit Rules

- **DO NOT** write or execute implementation code until the planning phase is approved by the user.
- **DO NOT** execute destructive commands without user approval.
- **MUST** enforce the `/build` workflow loop once implementation phase commences.
