# Specification: Phase 5 - PHP 8.2 Upgrade, Theme Deployment & Visual Audit

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 5 - FSE Flagship Theme Activation, Layout Templates & Visual Audit  
**STATUS**: `[REVISED SPEC / VERIFICATION PENDING]`  

---

## 1. Objective & Target Users
* **Objective**: Activate the bespoke `ekalexandria-flagship` Full Site Editing (FSE) block theme under PHP 8.2, compile SCSS design system assets (`npm run dev` / `npm run build`), construct multi-language layout templates (EL, EN, AR) for Front-Page, Pages, Single Posts, Listing Archives, Categories, Board Members, and Tachydromos, build header (with search) and footer components strictly matching scoped BeTheme features (no un-scoped assumptions), assign menus, validate block AST serialization against WordPress standards, and perform Playwright visual regression snapshots.
* **Target Users**: Portal visitors, mobile & desktop users, site administrators.

---

## 2. Core Features, Acceptance Criteria & Layout Implementation Requirements

### 1. Multi-Language Template Construction (EL, EN, AR)
* **Homepage Templates**:
  - `front-page-el.html` (mapped to `header-el.html` and `footer-el.html`)
  - `front-page-en.html` (mapped to `header-en.html` and `footer-en.html`)
  - `front-page-ar.html` (mapped to `header-ar.html` and `footer-ar.html`)
* **Page Templates**:
  - `page-el.html`, `page-en.html`, `page-ar.html` (or `page.html` with dynamic language template parts)
* **Single Post Templates**:
  - `single.html` (or `single-el.html`, `single-en.html`, `single-ar.html`)
* **Archive & Listing Page Templates**:
  - `archive.html` (General post listing archive)
  - `archive-alx_tachydromos.html` (Tachydromos newsletter listing archive)
  - `archive-board_member.html` (Board Members listing archive)
* **Category Page Templates**:
  - `category.html` (Category taxonomy archive)
* **Board Members Listing & Single Templates**:
  - `board-members.html`
* **Alexandrinos Tachydromos Newsletter Templates**:
  - `tachydromos.html` (supporting newsletter PDF viewer layout)

> [!NOTE]
### 2. Top Bar & Header Feature Implementation (Scoped)
* Construct Header template parts (`header-el`, `header-en`, `header-ar`, `header`) strictly adhering to Gutenberg FSE core block structure (passing block parser tests without invalid block errors):
  - **Vertical Flow Layout**: Topbar and main header are stacked vertically (`eka-header-topbar-wrapper` followed by `eka-header-main-wrapper`), spanning 100% full screen width.
  - **Top Bar**: Polylang Language Selector / Switcher and social media links (Facebook, Twitter, YouTube, Flickr, LinkedIn, Instagram).
  - **Main Header Structure**: Site Logo (`wp:site-logo` / `wp:image`) aligned on the left, Main Navigation menu on the right, followed by a interactive Search Icon button.
  - **Interactive Search Icon Trigger**: Search icon button toggles the search input box on click via lightweight vanilla JS / CSS overlay.
  - **Navigation Typography**: Clean, legible font weight (`font-weight: 500` or `400`, `Roboto` font family) without heavy artificial bolding.

### 3. Frontpage Layout & Core Component Re-engineering
* **Page Scrollability**: Ensure `html`, `body`, and `.wp-site-blocks` containers have standard scrolling enabled (`overflow-y: auto`, no scroll lock traps).
* **News Query Loop Carousel Component**: Re-engineered 5 latest post carousel utilizing native Gutenberg Query Loop data paired with bespoke SCSS and JS slider controls (`eka-carousel`) replacing legacy LayerSlider.
* **Greek Homepage Content & 3/4-Column Selected Pages Grid**:
  - Displays the 5 latest post carousel.
  - Renders native post content of the Greek homepage (`<!-- wp:post-content /-->`).
  - Displays the selected pages grid (3 to 4 columns, each containing Title, Featured Image, Excerpt on separate lines, all linked to target page).

### 4. Edge-to-Edge Footer Implementation (Scoped)
* Construct Footer template parts (`footer-el`, `footer-en`, `footer-ar`) strictly adhering to user layout specifications:
  - **Edge-to-Edge Gray Background**: Footer background spans full screen width with scoped gray background (`#545454` / `#2b2b2b`).
  - **Inner Content Width Wrapper**: Content constrained to maximum theme width (`max-width: 1200px`).
  - **Footer Content Alignment**: Left side contains copyright icon, year range `1843-2026`, and site title (`© 1843-2026 Ελληνική Κοινότητα Αλεξανδρείας`). Right side displays the Greek footer menu (`social-menu-bottom`).

### 5. Playwright Visual Baseline Audit
* Automated visual baseline capture (`node bin/scrape-baselines.js`) captures screenshots from the **Live Production Website** (`https://ekalexandria.org/`) to establish reference baselines, comparing them against the **Staging Portal** (`https://backstage.ekalexandria.org/`) to achieve visual parity.

### 4. Custom Features & Core Component Re-engineering
* **Mailchimp Newsletter Registration**: Re-engineer newsletter registration block securely without legacy plugin autoload dependencies.
* **Search System Restoration**: Functional search template (`search.html`) integrated with main header search trigger.

---

## 3. WordPress Design Patterns & Coding Standards

* **WordPress Coding Standards (WPCS) & PHP 8.2 Compatibility**:
  - All theme functions, custom features (`inc/custom-features.php`), and CLI commands (`inc/cli-commands.php`) must strictly adhere to WordPress Coding Standards (WPCS) for code formatting, sanitization, and escaping.
  - Theme execution must run 100% clean under PHP 8.2 with zero deprecation warnings, notices, or fatal errors in `wp-content/debug.log`.
* **Gutenberg FSE Architecture & AST Validation**:
  - Native `theme.json` v2 schema defining global design tokens, typography, and color palettes.
  - HTML templates and template parts must use valid Gutenberg core block markup, verified via `@wordpress/block-serialization-default-parser`.
  - Pure semantic HTML structure with ZERO inline `style="..."` attributes within block templates.
* **SCSS Architecture & Compilation**:
  - Modular SCSS compiled via `@wordpress/scripts` (`npm run dev` for dev, `npm run build` for production bundle).
  - Primary design system stylesheet located at `assets/scss/style.scss` and RTL framework at `assets/scss/rtl.scss`.
* **Feature Encapsulation**:
  - CPT registrations, custom meta fields, and save hooks encapsulated in `inc/custom-features.php`.
* **WP-CLI Custom Commands**:
  - Production deployment and programmatic migration operations executed via custom WP-CLI commands in `inc/cli-commands.php`.

---

## 4. Tech Stack Preferences, Build Instructions & Constraints

### Tech Stack
* **PHP Target**: PHP 8.2 (`php8.2 $(which wp)`).
* **Theme Architecture**: Full Site Editing (FSE) Block Theme, `theme.json` v2 schema, modular SCSS design system (`assets/scss/`).
* **System & NPM Dependencies**: `@wordpress/scripts`, `playwright`, `@wordpress/block-serialization-default-parser`, `sass`.
* **Visual Audit**: Playwright pixel-by-pixel comparison tool (`bin/scrape-baselines.js`).

### SCSS Build Pipeline & Asset Compilation
* **Source SCSS**: `assets/scss/style.scss` and RTL framework `assets/scss/rtl.scss`.
* **Development Build Command**:
  ```bash
  npm run dev
  ```
  *Compiles SCSS into CSS files with source maps and live watching for local theme development.*
* **Production Asset Build Command**:
  ```bash
  npm run build
  ```
  *Compiles, minifies, and optimizes ready asset bundle (`build/style-index.css`, `build/rtl-index.css`) for production deployment.*

---

## 5. Deployment & Spec Test Commands

```bash
# 1. Verify PHP 8.2 runtime
php8.2 -v

# 2. Install dependencies & compile SCSS production bundle via @wordpress/scripts
npm install
npm run build > ai-work/logs/phase5-deployment.log 2>&1

# 3. Activate Flagship theme under PHP 8.2
php8.2 $(which wp) theme activate ekalexandria-flagship --path=public >> ai-work/logs/phase5-deployment.log 2>&1

# 4. Flush rewrite rules
php8.2 $(which wp) rewrite flush --path=public >> ai-work/logs/phase5-deployment.log 2>&1

# 5. Execute Playwright visual baseline snapshotting
node bin/scrape-baselines.js >> ai-work/logs/phase5-deployment.log 2>&1

# 6. Inspect log output & debug log for PHP 8.2 cleanliness
cat ai-work/logs/phase5-deployment.log
cat public/wp-content/debug.log
```

---

## 6. Project Structure & Observability

```text
public/wp-content/themes/ekalexandria-flagship/
├── style.css
├── theme.json                    # FSE design system tokens & colors (v2 schema)
├── package.json                  # @wordpress/scripts & SCSS build scripts
├── assets/
│   └── scss/
│       ├── style.scss            # Main SCSS design system
│       └── rtl.scss              # RTL SCSS framework
├── build/                        # Compiled production CSS assets (npm run build)
├── templates/
│   ├── front-page-el.html
│   ├── front-page-en.html
│   ├── front-page-ar.html
│   ├── page.html
│   ├── single.html
│   ├── archive.html
│   ├── archive-alx_tachydromos.html
│   ├── archive-board_member.html
│   ├── category.html
│   ├── board-members.html
│   └── tachydromos.html
├── parts/
│   ├── header-el.html
│   ├── header-en.html
│   ├── header-ar.html
│   ├── footer-el.html
│   ├── footer-en.html
│   └── footer-ar.html
├── bin/
│   └── scrape-baselines.js       # Playwright screenshot capture script
└── ai-work/
    ├── baselines/                # Reference baseline screenshots
    ├── logs/
    │   └── phase5-deployment.log # Phase 5 execution & auditing log
    └── specs/
        └── PHASE-5-PHP82-THEME-DEPLOYMENT-SPEC.md
```

---

## 7. Spec Test Strategy & Verification Criteria

1. **SCSS Build Verification**:
   - `npm run build` exits zero without compilation errors and generates minified production bundles in `build/`.
2. **Block AST Serialization Audit**:
   - All FSE template files pass AST block parser validation via `@wordpress/block-serialization-default-parser`.
3. **PHP 8.2 Runtime Cleanliness**:
   - Theme activation and rewrite flushing execute without PHP warnings, deprecations, or fatal errors in `public/wp-content/debug.log`.
4. **Playwright Visual Regression Audit**:
   - Automated visual snapshot comparison (`node bin/scrape-baselines.js`) matches baseline screenshots in `ai-work/baselines/`.
5. **Standalone Production Cutover Script Verification**:
   - Deployment script (`wp eka production-cutover`) executes autonomously without AI intervention, logging all execution outputs, encountered issues, fallbacks, and technical reasoning into `ai-work/logs/cutover.log`.

---

## 8. Boundaries & Operational Governance

> [!IMPORTANT]
> **Production Cutover Constraint & Reasoning Mandate**: The final production deployment and cutover will be executed via standalone scripts and WP-CLI commands WITHOUT an AI assistant present. All manual workarounds or fallback logic MUST be codified into script files. All scripts MUST log execution outputs, errors, specific issues encountered, fallback actions taken, and technical reasoning into `ai-work/logs/` for post-run audits.

* **ALWAYS**:
  - **ALWAYS**: Use `theme.json` design system tokens and compiled SCSS (`assets/scss/`).
  - **ALWAYS**: Compile production assets using `@wordpress/scripts` (`npm run build`).
  - **ALWAYS**: Output all command execution outputs, errors, and reasoning to `ai-work/logs/phase5-deployment.log`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Halt at the end of Phase 5 for **Final User Manual Validation & Cutover Approval**.
* **NEVER**:
  - **NEVER**: Hardcode inline `style="..."` attributes inside HTML template files.
  - **NEVER**: Add un-scoped header or footer widget assumptions not present in Phase 3 scoping.
  - **NEVER**: Rely on interactive AI workarounds for production cutover tasks without codifying them into standalone scripts.
