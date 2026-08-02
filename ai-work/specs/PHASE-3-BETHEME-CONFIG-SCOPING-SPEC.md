# Specification: Phase 3 - BeTheme Deep Configuration Scoping (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 3 - Deep BeTheme Configuration, Options & Layout Scoping  
**STATUS**: `[SCOPING IN PROGRESS / SPEC READY]`  

---

## 1. Objective & Target Users
* **Objective**: Deeply analyze, extract, and document all legacy BeTheme settings, options array (`betheme` / `mfn_theme_options`), dynamic sidebars, custom builder layout meta (`_mfn-builder-items`), color palettes, typography tokens, header configurations, and active CSS stylesheets into structured JSON/CSS artifacts under **PHP 7.4** to ensure complete data availability for Phase 5 block template implementation.
* **Target Users**: System architects, theme developers, and AI block builder agents.

---

## 2. Core Features & Acceptance Criteria
* **Criteria 1**: Export complete serialized `betheme` / `mfn_theme_options` settings array into `ai-work/scopings/betheme-config-scoping.json`.
* **Criteria 2**: Catalog all pages using MFN builder (`_mfn-builder-items` postmeta) into `ai-work/scopings/mfn-pages.json`.
* **Criteria 3**: Extract custom CSS options (`custom-css`, `mfn_custom_css`, core customizer CSS) into `ai-work/scopings/betheme-custom-css.css`.
* **Criteria 4**: Process and save active used legacy styles using **UnCSS** CLI into `ai-work/scopings/betheme-active-styles.css` for computed/used style reference.
* **Criteria 5**: Map header layout options, logo dimensions, color palettes, container grid spacing, and typography tokens.
* **Criteria 6**: All WP-CLI scoping commands routed strictly through `php7.4`.
* **Criteria 7**: All command outputs dumped to clean log file `ai-work/logs/phase3-scoping.log`.
* **Criteria 8**: **Manual Spec Verification Before Commit**: Spec changes require manual user approval before Git commit.
* **Criteria 9**: **Manual User Validation Pause** required upon completion of Phase 3 scoping.

---

## 3. Tech Stack Preferences, Tool Selection & Constraints

### Tool Selection: UnCSS vs. SnipCSS
* **Selected Tool**: **UnCSS** (`npx uncss`)
* **Rationale**: 
  - **UnCSS** operates fully headless via CLI and Node.js automation. It loads target pages, renders the JS DOM tree, evaluates active selectors against loaded stylesheets (`style-static.css`, theme CSS), and strips unused CSS declarations without human intervention.
  - **SnipCSS** is a manual Chrome browser extension/GUI tool, which cannot be automated non-interactively within headless build pipelines.

### Stack Constraints
* **PHP Routing**: Strictly `php7.4` (`php7.4 $(which wp) ...`).
* **Output Formats**: 
  - Structured JSON (`jq` compatible)
  - Raw and UnCSS-processed CSS stylesheets
* **CSS Extraction Utility**: `npx uncss` (or `@uncss/uncss` Node package).
* **Logging Path**: `ai-work/logs/phase3-scoping.log`.
* **Database Protocol**: Strictly read-only operations. No `$wpdb` writes or `update_option` calls allowed.

---

## 4. Script Execution & Detailed Execution Steps

To minimize token consumption during script generation and execution, `bin/scope-betheme-config.php` must follow this exact sequential execution flow:

### Step 1: Environment & Directory Preparation
- Verify directory `ai-work/scopings/` exists (create if missing).
- Verify directory `ai-work/logs/` exists (create if missing).

### Step 2: Extract BeTheme Options JSON (`ai-work/scopings/betheme-config-scoping.json`)
1. Fetch `get_option('betheme')` and fallback to `get_option('mfn_theme_options')`.
2. Parse and structure the data into key sections:
   - `header`: Header layout style, sticky header status, top bar display, logo URL, logo width/height.
   - `typography`: Body font family, header font family, primary font size, line heights.
   - `colors`: Main theme color, background color, header/footer backgrounds, text colors.
   - `sidebars`: Dynamic sidebar definitions (`sidebars` option array or `$wp_registered_sidebars`).
   - `grid_and_spacing`: Container width (`grid-width`), padding, margins.
   - `raw_options`: Full serialized options array.
3. Save cleanly formatted output:
   ```php
   file_put_contents('ai-work/scopings/betheme-config-scoping.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
   ```

### Step 3: Catalog MFN Builder Pages (`ai-work/scopings/mfn-pages.json`)
1. Execute read-only DB query:
   ```sql
   SELECT p.ID as post_id, p.post_title, p.post_type, m.meta_value 
   FROM wp_posts p 
   INNER JOIN wp_postmeta m ON p.ID = m.post_id 
   WHERE m.meta_key = '_mfn-builder-items' AND p.post_status != 'trash';
   ```
2. For each post, decode `_mfn-builder-items` (if serialized/base64 encoded) and extract section/wrap/item counts.
3. Add permalink via `get_permalink($post_id)`.
4. Save formatted array to `ai-work/scopings/mfn-pages.json`.

### Step 4: Extract Database Custom CSS (`ai-work/scopings/betheme-custom-css.css`)
1. Retrieve `custom-css`, `custom-css-imp`, and `mfn_custom_css` from BeTheme options.
2. Retrieve WordPress core customizer CSS via `wp_get_custom_css()`.
3. Concatenate with clear visual header comments and save to `ai-work/scopings/betheme-custom-css.css`.

### Step 5: Process Active Styles via UnCSS (`ai-work/scopings/betheme-active-styles.css`)
1. Locate static compiled BeTheme CSS at `public/wp-content/uploads/mfn-css/style-static.css` or `public/wp-content/themes/betheme/css/style.css`.
2. Execute **UnCSS** CLI against local/staging homepage URL to filter out unused selectors:
   ```bash
   npx uncss public/wp-content/uploads/mfn-css/style-static.css > ai-work/scopings/betheme-active-styles.css 2>> ai-work/logs/phase3-scoping.log
   ```
3. If static stylesheet is missing, fallback to querying active homepage styles directly:
   ```bash
   npx uncss https://backstage.ekalexandria.org/el/ > ai-work/scopings/betheme-active-styles.css 2>> ai-work/logs/phase3-scoping.log
   ```

---

## 5. Execution Commands & Verification

```bash
# 1. Execute the Phase 3 BeTheme Scoping Script
php7.4 $(which wp) eval-file bin/scope-betheme-config.php --path=public > ai-work/logs/phase3-scoping.log 2>&1

# 2. Extract active/used CSS using UnCSS
npx uncss public/wp-content/uploads/mfn-css/style-static.css > ai-work/scopings/betheme-active-styles.css 2>> ai-work/logs/phase3-scoping.log || true

# 3. Validate JSON syntax integrity
jq . ai-work/scopings/betheme-config-scoping.json > /dev/null
jq . ai-work/scopings/mfn-pages.json > /dev/null

# 4. Verify CSS output files are non-empty
test -s ai-work/scopings/betheme-custom-css.css && echo "Custom CSS generated successfully"
test -s ai-work/scopings/betheme-active-styles.css && echo "Active Styles generated successfully"

# 5. Review execution log output
cat ai-work/logs/phase3-scoping.log
```

---

## 6. Project Structure

```text
ai-work/
├── scopings/
│   ├── betheme-config-scoping.json     # Complete serialized theme options, typography, colors, sidebars
│   ├── mfn-pages.json                  # MFN builder page list & item metrics
│   ├── betheme-custom-css.css          # Extracted customizer & theme options custom CSS rules
│   └── betheme-active-styles.css       # UnCSS processed active stylesheet (unused rules stripped)
├── logs/
│   └── phase3-scoping.log              # Execution output and error log
└── specs/
    └── PHASE-3-BETHEME-CONFIG-SCOPING-SPEC.md
```

---

## 7. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Automated `jq` syntax validation of JSON scoping files.
  2. File size and non-empty checks (`test -s`) on CSS artifacts.
  3. Log verification checking for zero PHP errors or warnings in `phase3-scoping.log`.
* **Boundaries**:
  - **ALWAYS**: Route CLI calls through `php7.4`.
  - **ALWAYS**: Dump execution outputs cleanly to `ai-work/logs/phase3-scoping.log`.
  - **ALWAYS**: Enforce strict read-only operations on the database and options table.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Require user manual validation pause before advancing to Phase 4.
  - **NEVER**: Execute database mutations or delete legacy `wp_options` records during Phase 3 scoping.
