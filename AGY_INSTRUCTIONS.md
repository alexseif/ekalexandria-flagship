# EXECUTIVE BRIEF FOR AGY: AUTOMATED 1:1 REPLICATION & SYSTEM MIGRATION

## 1. STRATEGIC GOAL

You are tasked with executing a full technical modernization of the Greek Community of Alexandria portal (`ekalexandria.org`). You must enforce a strict 1:1 visual replication of the existing BeTheme design into the blank `ekalexandria-flagship` Full Site Editing (FSE) block theme. Under no circumstances should content be rewritten manually; the migration must be entirely programmatic, repeatable, and validated.

## 2. AGENT SKILLSET ENVIRONMENT & DIRECTIVES

You must operate strictly within the boundaries of the official WordPress Developer Core Agent Skills.

### Installed Capabilities Bundle

- `wp-block-themes`: Establishes FSE structure, `theme.json`, template hierarchy, and style mappings.
    
- `wp-plugin-development`: Orchestrates custom post type declarations and custom PHP feature logic (e.g., prefix `eka_`).
    
- `wp-wpcli-and-ops`: Governs database sanitization, search-and-replace protocols, and programmatic data migration via custom commands.
    

### Non-Negotiable Directives

- **No Speculative Logic:** If a translation mapping (Greek to Arabic/English) is ambiguous during database extraction, insert the post but leave it unlinked for manual resolution.
    
- **Media Proxying:** Do not download the 60GB media library. Rely on the pre-configured Nginx staging server block to dynamically proxy missing local assets to `https://ekalexandria.org/wp-content/uploads/`.
    
- **Zero Inline Styles:** Never write `style="..."` attributes within theme templates or block patterns. Rely entirely on SCSS compiled via `@wordpress/scripts`.
    

## 3. CORE ARCHITECTURAL CONVERSIONS (PROGRAMMATIC MIGRATION)

### 3.1 Alexandrinos Tachydromos (Newsletters)

- **Legacy Form:** Embedded, flat single-page lists with manual links to PDF attachments.
    
- **Target State:** A dedicated custom post type `alx_tachydromos` with REST API enabled and the exact Greek rewrite slug `αλεξανδρινός-ταχυδρόμος`.
    
- **Agent Logic:** Write a WP-CLI parsing script (`wp eka migrate-tachydromos`) within `inc/cli-commands.php`. It must query historical attachment tables in `db207080_eka`, isolate PDF items, extract raw upload timestamps to serve as the `post_date`, generate the front-page thumbnail via Imagick/Ghostscript (stripping dimension suffixes), and map the layout natively to `templates/archive-alx_tachydromos.html`. This CPT must NOT be exposed to Polylang.
    

### 3.2 Board of Directors (Στελέχωση)

- **Legacy Form:** Hardcoded WPBakery grid components embedded inside BeTheme testimonial shortcodes.
    
- **Target State:** A distinct `board_member` CPT mapped cleanly through Polylang. The Greek version must be the canonical primary post.
    
- **Agent Logic:** Write a WP-CLI parsing script (`wp eka migrate-board`) that reads raw shortcode entries. It must run RegEx/DOMDocument operations to separate titles and media, assign sequential `menu_order` keys based on historical appearance, and utilize `pll_save_post_translations` to bind the three language configurations natively. Visibility must default to `publish`.
    

### 3.3 Dynamic Carousel Layer (Legacy Slider Migration)

- **Legacy Form:** Bloated `LayerSlider` slide structures.
    
- **Target State:** Core native block patterns (e.g., `core/gallery` or `core/query` loops).
    
- **Agent Logic:** Parse static page wrappers containing `[rev_slider]` parameters. For dynamic pages (Homepage, News), build a Query Loop block pulling the latest 5 posts from the "News" / "Ανακοινώσεις" category. For static galleries (e.g., Music Museum, Cemeteries), programmatically generate Gutenberg gallery blocks pre-populated with the exact original image IDs extracted from the historical database (e.g., `7821, 7822, 7823` for the Music Museum) to guarantee zero data loss.
    

### 3.4 In-Page Sub-Navigation & Shortcode Mitigation

- **Legacy Form:** BeTheme sidebars and custom shortcodes used to query and show subpages inside parents.
    
- **Target State:** Native core Query Loops or Navigation block columns.
    
- **Agent Logic:** Swap legacy structural codes for standard block configurations. For sidebars on top-level pages (e.g., Establishment, Activities), embed a native Navigation block mapped to the corresponding legacy menu ID (e.g., ID 70 for Greek Establishment) while respecting the Polylang language context.
    

## 4. OBSERVABILITY & QUALITY GUARDRAILS

### 4.1 Test Observability via Playwright

- **The Framework:** Initialize Playwright inside the theme root pointing to `https://backstage.ekalexandria.org`.
    
- **Visual Regression Loop:** Prior to executing code shifts, trigger a script to snap baseline layout configurations directly from production (`ekalexandria.org`). Every time an FSE HTML layout template or SCSS layer is written, run `npx playwright test`. Parse text-level logs indicating pixel drift and tweak elements until deviation is 0%.
    

### 4.2 Structural Observability via Block Serialization

- **The Framework:** Use Node's standard module loading framework to read generated layouts before outputting them to theme templates.
    
- **AST Analysis Loop:** Before writing migrated block contents back to database tables, run the blocks through `@wordpress/block-serialization-default-parser`. If the layout structure triggers parsing flags, isolate the block wrapper strings and correct validation bugs before committing changes.
    

### 4.3 Git Version Control Standard

- **Atomic Commits:** Isolate every single step. A single task equals a single feature commit.
    
- **Formatting Rules:** Commit messages must use plain technical tags (e.g., `feat(cpt): register newsletter post type with native greek rewrite slug`).
    

## 5. SEQUENTIAL TASK PROTOCOL FOR AGY

- [ ] **Step 1 (Environmental Reset):** Clear the staging theme directory. Sync live plugins and core table dumps from production to backstage. Execute database domain mapping via WP-CLI (`wp search-replace`) while skipping GUID rows (`--skip-columns=guid`) to preserve downstream RSS feed integrity.
    
- [ ] **Step 2 (Orchestration Layer Setup):** Run `npm init -y` and add `@wordpress/scripts` as a devDependency to manage block asset compilation. Install Playwright and `@wordpress/block-serialization-default-parser` locally. Mount official WordPress agent-skills profiles to `.agents/skills/`.
    
- [ ] **Step 3 (Baseline Visual Extraction):** Configure Playwright to scrape live layouts across major linguistic versions (Home, Στελέχωση, Προγράμματα) to create absolute, empirical baseline assets.
    
- [ ] **Step 4 (Scaffold Architecture & Server Check):** Write automated bash/WP-CLI checks to verify server compliance (`imagick`, Ghostscript execution, PDF read/write permissions in `policy.xml`) prior to any migration. Initialize `inc/custom-features.php` and stub out the WP-CLI command structures inside `inc/cli-commands.php`.
    
- [ ] **Step 5 (Programmatic Migration Loop):** Trigger migrations for the Newsletter CPT (`wp eka migrate-tachydromos`), the Board CPT (`wp eka migrate-board`), and dynamic block replacements (galleries/menus). Verify structural parsing and visual regression logs iteratively for each slice.

## 6. Project Client update
the file Master Project Roadmap: EKA Portal Modernization & Redesign.md is the main file used to communicate with the client, keeping that file in structure and up to date with the tasks is favorable for communication.
Currently we are restarting so it's ok to uncheck tasks from it and recheck them when they are complete. 