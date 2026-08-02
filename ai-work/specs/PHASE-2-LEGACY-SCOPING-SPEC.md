# Specification: Phase 2 - Deep Legacy Item & Page URL Scoping (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 2 - Deep Scoping of Legacy Sliders, Shortcodes, MFN Builder Items & Page URLs  
**STATUS**: `[SPECIFICATION REVISED / READY FOR EXECUTION]`  

---

## 1. Objective & Target Users
* **Objective**: Deeply analyze and inventory all legacy items across all pages in the WordPress portal. Extract the page ID, full page URL, legacy item type (e.g., `layerslider`, `rev_slider`, `vc_posts_grid`, `testimonials`, `mfn_builder_item`, `static_slider`), raw snippet/shortcode, and proposed Gutenberg block remediation strategy into a structured JSON dataset `ai-work/scopings/legacy-items-inventory.json` under PHP 7.4 runtime.
* **Target Users**: System architects, developer CLI scripts, and AI migration agents.

---

## 2. Core Features & Acceptance Criteria

### Task 1: Scoping Legacy Sliders, Shortcodes & Page URLs
* **Command**: `php7.4 $(which wp) eval-file bin/scope-legacy-items.php`
* **Log File**: `ai-work/logs/phase2-legacy-scoping.log`
* **Output File**: `ai-work/scopings/legacy-items-inventory.json`
* **Criteria**:
  1. Audit all WordPress posts/pages (`post_type IN ('page', 'post')`).
  2. Map post ID to its full staging permalink/URL (e.g. `https://backstage.ekalexandria.org/...`).
  3. Identify all embedded shortcodes and legacy builder elements:
     - `layerslider` / `rev_slider` shortcodes
     - Static image sliders / galleries
     - `vc_posts_grid` / WPBakery grid components
     - `[testimonials]` / board member listings
     - `_mfn-builder-items` postmeta components
  4. Classify each legacy item with `item_type`, `item_identifier`, `page_id`, `page_url`, `raw_snippet`, and `proposed_remediation`.
  5. Dump clean execution logs to `ai-work/logs/phase2-legacy-scoping.log`.

---

## 3. Tech Stack Preferences & Constraints
* **PHP Routing**: Strictly `php7.4` (`php7.4 $(which wp) ...`).
* **Output Format**: Validated JSON (`ai-work/scopings/legacy-items-inventory.json`).
* **Logging Path**: `ai-work/logs/phase2-legacy-scoping.log`.

---

## 4. Commands
```bash
# Execute Phase 2 legacy item scoping and inventory script
php7.4 $(which wp) eval-file bin/scope-legacy-items.php --path=public > ai-work/logs/phase2-legacy-scoping.log 2>&1

# Validate output JSON file
jq . ai-work/scopings/legacy-items-inventory.json > /dev/null
```

---

## 5. Project Structure
```text
bin/
└── scope-legacy-items.php          # PHP script to extract legacy items and URLs to JSON
ai-work/
├── scopings/
│   └── legacy-items-inventory.json # Structured inventory of all legacy items and page URLs
├── logs/
│   └── phase2-legacy-scoping.log
└── specs/
    └── PHASE-2-LEGACY-SCOPING-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Validate JSON syntax using `jq . ai-work/scopings/legacy-items-inventory.json`.
  2. Verify all pages with `_mfn-builder-items` or shortcodes are correctly indexed with valid permalink URLs.
* **Boundaries**:
  - **ALWAYS**: Route CLI calls through `php7.4`.
  - **ALWAYS**: Dump execution output to `ai-work/logs/phase2-legacy-scoping.log`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Require **Manual User Validation Pause** before starting Phase 3.
  - **NEVER**: Modify `post_content` or database records during Phase 2.
