# TOPIC NAME: EKA-PORTAL
# ISSUE NAME: MIGRATION-AGY

## 1. Objective
To execute a full programmatic technical modernization and migration of the Greek Community of Alexandria portal (`ekalexandria.org`) into the blank `ekalexandria-flagship` Full Site Editing (FSE) block theme. The goal is to achieve a strict 1:1 visual replication of the existing BeTheme design without manual content rewriting, utilizing a repeatable, validated, programmatic workflow. 

## 2. Core Features and Acceptance Criteria
- **Alexandrinos Tachydromos (Newsletters) Migration:** 
  - Read the `ai-work/scopings/tachydromos-scoping.json` file, which already contains the parsed block HTML structures of the legacy newsletter page (`/el/ανακοινώσεις-νέα/αλεξανδρινός-ταχυδρόμος/`). Do not re-parse the HTML from the database.
  - Create an `alx_tachydromos` CPT. **Requirements:** REST API must be enabled (`'show_in_rest' => true`) and the exact Greek rewrite slug must be `αλεξανδρινός-ταχυδρόμος`. Polylang is NOT required.
  - **Generation Logic from JSON:** Write a custom WP-CLI PHP script (e.g., `wp eka migrate-tachydromos`) that iterates through the JSON scoping array. For each newsletter item:
    1. **Title:** Use the `extracted_title` property from the JSON. Ensure the format strictly follows `[Greek Month] [YYYY]` (e.g., "Μάρτιος 2026"). Clean up any instances where the title was extracted as just a number or raw HTML tag.
    2. **Featured Image:** Use the `img_url` property from the JSON. Find its existing attachment ID in the database and reassign it as the featured image of the new post (do not download it again). 
    3. **PDF Link:** Use the `pdf_url` property from the JSON. Handle edge cases where the link might be a `.jpg` or a 404 gracefully without breaking the loop.
  - **Issue Date Mapping:** Programmatically parse the extracted title (e.g., "Μάρτιος 2026") and assign the exact corresponding date (e.g., `2026-03-01 00:00:00`) to the `post_date` so the chronological timeline remains authentic.
  - **Single Post View:** Each newsletter gets its own page. The PDF will be displayed using the native `core/file` block (which supports inline embedding).
  - Feature: Gutenberg meta field for future PDF uploads with automatic featured image generation (via ImageMagick).
  - **Idempotency:** Tag migrated posts with `_eka_pdf_filename` (derived from the PDF url). Scripts must check this meta to skip duplicates.
- **Board of Directors Migration:** 
  - Create a `board_member` CPT. **Architecture Decision:** `publicly_queryable` must be `false`. Board members will NOT have individual pages. They will only be displayed collectively on a single page, ordered by a custom `menu_order`. Visibility must default to `publish`.
  - Migrate member images and titles from WPBakery testimonials. You must utilize `pll_save_post_translations` to bind the three language configurations natively.
  - **Idempotency:** Tag with `_eka_legacy_id` to prevent duplicate migrations.
- **Legacy Slider & Shortcode Mitigation:** 
  - **Dynamic Layer Sliders:** Read `ai-work/scopings/layer-sliders-scoping.json` to map dynamic sliders (e.g., `slider_id: 6` on Homepage and News) and programmatically replace them with a native Query Loop block featuring a title, link, and a small title-only overlay.
  - **Static Revolution Sliders:** Utilize the mapping in `tasks/legacy_data.md` alongside the `rev_sliders` array in `ai-work/scopings/legacy-ids.json`. The legacy inner pages (e.g., Cemeteries, Museums, Community Lounge) use Revolution Sliders for static image galleries. The migration script must programmatically inject native `core/gallery` blocks into these specific Page IDs using the exact pre-extracted Media IDs listed in `tasks/legacy_data.md` (e.g., inserting Image IDs `7821, 7822, 7823` into the Music Museum pages). This bypasses the need to parse the Revolution Slider database entirely.
- **Sub-Navigation Migration:** Replace BeTheme sidebars and custom shortcodes with native core Query Loops or Navigation blocks.
- **1:1 Visual Parity:** Visual output must strictly match the legacy design.
- **Mailchimp Newsletter Registration:** Re-engineer the newsletter registration integration securely.
- **Search System Restoration:** Build a functional search and search-results page (currently broken on the live site) that integrates seamlessly with the existing search trigger in the main header menu.
- **Polylang FSE Template Variations:** Create explicit language-specific block templates and template parts across all crucial layout elements (e.g., `front-page-el`, `header-ar`, `footer-en`, `page-el`). This ensures we perfectly account for different language-specific menus, widgets, and potential structural variations.
- **RTL Language Support:** Build comprehensive Right-to-Left (RTL) CSS support to correctly render the existing Arabic content alongside Greek and English.


## 3. Tech Stack Preferences and Constraints
- **Required Packages & Plugins:**
  - *NPM:* `@wordpress/scripts`, `playwright`, `@wordpress/block-serialization-default-parser`.
  - *System:* `php7.4`, `wp-cli`, `imagemagick`, `ghostscript`.
  - *WordPress:* `polylang` (must be active during migration for mapping).
- **Execution Flow / Sequential Task Protocol (Script-Driven):** 
  1. **Environmental Reset:** Utilize the already written `reset-env.sh` script to synchronize all files and the database dump from the local production copy. Its primary target is to restore the legacy state and **remove any new files** created from previous failed solution attempts. It must clean the new theme folder (`ekalexandria-flagship`) from any previous work, but **strictly preserve** this spec file and the existing AI scoping work (e.g., `ai-work/` directory) to avoid wasting time on this process again. To prevent the "Mailchimp composer cache error" during plugin sync, exclude the mailchimp plugin from the sync or clear composer cache appropriately. Execute DB domain mapping via WP-CLI. Do not perform these steps manually.
  2. **Orchestration Layer Setup:** The agent must write a bash script (e.g., `setup-orchestration.sh`) to run `npm init -y`, add `@wordpress/scripts` as devDependency, and install Playwright/AST parser.
  3. **Scoping & Baseline Extraction:** Scope the current theme folder and utilize existing scripts (`clean-revsliders.php`, `clean-wpbakery.php`, `extract.php`) where favorable.
     - **Enhancement:** Map exactly which pages contain which sliders (e.g., Revolution Slider, BeTheme sliders), and specifically identify **what part of the page content** (which section or shortcode) relates to which slider, so we know exactly where they belong on the page.
     - **Enhancement:** Ensure a robust extraction of BeTheme options, looking deep into the database (e.g., `options_betheme` or specific post meta) as previous extractions were weak.
     - Configure Playwright to scrape live layouts across major linguistic versions. **Critical:** Playwright configuration must include `ignoreHTTPSErrors: true` to bypass HTTPS warnings generated on the local staging site.
  4. **Implement & Test Programmatic Migration:** When possible, have the AI write a script that does the migration work rather than the AI doing the work directly in terminal. Validate generated blocks via AST serialization.
  5. **Disable Legacy Plugins.**
  6. **PAUSE / NOTIFY USER:** Halt operations and notify the user to upgrade PHP from 7.4 to 8.2.
  7. **Plugin Updates:** Immediately after the PHP upgrade, run a WP-CLI command to update all plugins. This prevents PHP 8.2 fatal errors caused by outdated legacy plugins.
  8. **Routing Preservation:** The migration process must **not** break the existing routing or homepage configuration. Ensure the current system is preserved as is, retaining the correct static front page and permalink structures to prevent 404s.
  9. **Theme Implementation:** Build FSE templates using extracted JSON tokens and test via Playwright visual regression.
- **Styling:** Zero inline styles. All styles must use SCSS compiled via `@wordpress/scripts`.
- **Media Asset Proxying:** The 60GB media library must not be downloaded; rely on the existing Nginx proxy setup.
- **Agents & Skills:** Install official WordPress Developer Core Agent Skills (`wp-block-themes`, `wp-plugin-development`, `wp-wpcli-and-ops`) in `.agents/skills/`.

## 4. Boundaries
- **Always Do:** 
  - **Explicit PHP CLI Routing:** Before the server is upgraded to PHP 8.2, all WP-CLI commands must be strictly executed using PHP 7.4 (e.g., `php7.4 $(which wp) ...`) to avoid token-wasting fatal errors. After the Nginx config is upgraded to 8.2, you may use the standard `wp` command.
  - Ensure `AGY_INSTRUCTIONS.md` and `Master Project Roadmap: EKA Portal Modernization & Redesign.md` are safely preserved.
  - Use atomic Git commits per task.
  - Export scoping findings into standard JSON files (`ai-work/scopings/*.json`) to hand off to subsequent agent sessions, minimizing token costs.
  - **Execution Halt Standard:** If you hit an infrastructure wall, a command fails after a couple of tries, or there is ambiguity in implementation, HALT execution immediately. Notify the user and provide at least two options with their respective pros and cons.
- **Ask First:** If any translation mapping is ambiguous during data extraction, insert the post unlinked and halt for manual resolution.
- **Never Do:** 
  - Never execute terminal operations or migrations without perfect verification of prior steps.
  - Never write `style="..."` attributes inside templates. 
  - Never download the 60GB media library. 
  - Never rewrite content manually.

## 5. Design Patterns & Coding Standards
- **Performance & Acceptance Results:** The resulting theme must be exceptionally lightweight, targeting high page speed scores on both mobile and desktop.
- **SEO & Tracking Architecture:** Implement high-quality SEO/Geo standards into the HTML architecture. Safely account for and inject standard tracking scripts (Google Analytics, Meta Pixel) without degrading core web vitals.
- **Token Reduction Strategy (Script-First):** Rather than having the AI ingest massive DB outputs or execute discrete steps manually, the AI must rely heavily on writing targeted bash scripts, DB queries, and PHP conversion scripts to automate tasks locally. Heavy research and scoping outputs must be dumped into `ai-work/scopings/*.json`. The agent will formulate specific execution instructions into separate markdown files to allow starting fresh chat sessions with narrow contexts. **Critical:** Messages accumulating in the IDE can crash it; break tasks into smaller steps and keep script console output extremely minimal.
- **WP-CLI Custom Commands:** Scripts must be placed in `inc/cli-commands.php` (e.g., `wp eka migrate-tachydromos`).
- **Feature Encapsulation:** Gutenberg meta fields and save hooks must be encapsulated in `inc/custom-features.php`.
- **SCSS Architecture & Compilation:** Styles must use modular, mobile-first responsive SCSS rules to ensure a seamless flow across all devices. Typography, colors, and visual branding must remain exactly the same as legacy. Use `npm run dev` (or `start`) for local environment testing. Use `npm run build` strictly to generate the final minified, optimized asset bundle for production deployment.

## 6. Project Structure & Observability
- **Root Directory:** `/var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/`
- **Data Handoff:** `/var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/ai-work/scopings/`
- **Playwright Testing:** Used for two purposes: 1) Extracting computed style tokens directly from the live `ekalexandria.org` production render. 2) Visual regression matching during theme development.
- **AST Serialization:** Used to validate block HTML generation before committing to the database, ensuring that migration data is structurally sound even before the FSE theme is active.

## 7. Testing Strategy
- **Pre-Flight Environment Test:** A script that verifies WP-CLI, PHP 7.4, Node.js are active, and specifically checks `imagick`, Ghostscript execution, and PDF read/write permissions in ImageMagick's `policy.xml` before any migrations start.
- **AST Block Serialization:** Run migrated layouts through `@wordpress/block-serialization-default-parser` before writing to DB tables.
- **Data Integrity Test:** Compare extracted legacy shortcode image IDs (from JSON) against the migrated block IDs.

## 8. Deployment Strategy
- **Manual Theme Upload:** Deployment will circumvent complex CI/CD for safety. The final, optimized `ekalexandria-flagship` theme directory (containing only the output of `npm run build`, omitting `node_modules` and raw SCSS) will be manually uploaded via FTP to the live server.
- **Migration Script Execution:** A dedicated WP-CLI deployment script will execute on the live server to flip the active theme, disable the legacy plugins, and run the programmatic data migration routines locally on the production database, ensuring a clean, atomic cutover.
