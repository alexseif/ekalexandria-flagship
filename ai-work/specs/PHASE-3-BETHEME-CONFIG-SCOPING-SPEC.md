# Specification: Phase 3 - BeTheme Deep Configuration Scoping (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 3 - Deep BeTheme Configuration, Options & Layout Scoping  
**STATUS**: `[SCOPING IN PROGRESS / AI ACTIVE]`  

---

## 1. Objective & Target Users
* **Objective**: Deeply analyze, extract, and document all legacy BeTheme settings, options array, dynamic sidebars, custom builder items, and custom CSS into structured JSON documents under PHP 7.4 to ensure complete data availability for Phase 5 block template implementation.
* **Target Users**: System architects and AI block builder agents.

---

## 2. Core Features & Acceptance Criteria
* **Criteria 1**: Export complete serialized `betheme` / `mfn_theme_options` settings array into `ai-work/scopings/betheme-config-scoping.json`.
* **Criteria 2**: Catalog all pages using MFN builder (`_mfn-builder-items` postmeta).
* **Criteria 3**: Scrape dynamic sidebar assignments and custom CSS rules into `ai-work/scopings/betheme-custom-css.css`.
* **Criteria 4**: Map header options, logo dimensions, color palettes, and typography tokens.
* **Criteria 5**: All WP-CLI scoping commands routed through `php7.4`.
* **Criteria 6**: **Manual User Validation Pause** required upon completion of Phase 3 scoping.

---

## 3. Tech Stack Preferences & Constraints
* **PHP Routing**: Strictly `php7.4` (`php7.4 $(which wp) ...`).
* **Output Format**: Structured JSON (`jq` compatible).

---

## 4. Commands
```bash
# Export theme options array
php7.4 $(which wp) option get betheme --format=json --path=public > ai-work/scopings/betheme-config-scoping.json

# Audit pages using MFN builder
php7.4 $(which wp) db query "SELECT post_id, meta_key FROM wp_postmeta WHERE meta_key = '_mfn-builder-items'" --path=public > ai-work/scopings/mfn-pages.json

# Validate exported JSON
jq . ai-work/scopings/betheme-config-scoping.json > /dev/null
```

---

## 5. Project Structure
```text
ai-work/
├── scopings/
│   ├── betheme-config-scoping.json     # Complete serialized theme options
│   ├── mfn-pages.json                  # MFN builder page list
│   └── betheme-custom-css.css          # Extracted styling rules
└── specs/
    └── PHASE-3-BETHEME-CONFIG-SCOPING-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Automated `jq` syntax validation of JSON scoping files.
  2. Completeness audit verifying all active color tokens and sidebar mappings exist in output JSON.
* **Boundaries**:
  - **ALWAYS**: Route CLI calls through `php7.4`.
  - **ALWAYS**: Require user manual validation pause before advancing to Phase 4.
  - **NEVER**: Delete legacy `wp_options` records during the scoping phase.
