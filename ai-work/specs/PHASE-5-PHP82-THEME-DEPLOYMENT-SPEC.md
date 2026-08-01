# Specification: Phase 5 - PHP 8.2 Upgrade, Theme Deployment & Visual Audit

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 5 - FSE Flagship Theme Activation, Layout Templates & Visual Audit  
**STATUS**: `[REVISED SPEC / VERIFICATION PENDING]`  

---

## 1. Objective & Target Users
* **Objective**: Activate the bespoke `ekalexandria-flagship` Full Site Editing (FSE) block theme under PHP 8.2, compile SCSS design system assets (`npm run dev` / `npm run build`), construct multi-language layout templates (EL, EN, AR) for Front-Page, Pages, Single Posts, Listing Archives, Categories, Board Members, and Tachydromos, build header (with search) and footer components strictly matching scoped BeTheme features (no un-scoped assumptions), assign menus, and perform Playwright visual regression snapshots.
* **Target Users**: Portal visitors, mobile & desktop users, site administrators.

---

## 2. Detailed Layout & Template Implementation Requirements `[AI ACTIVE]`

### 1. Template Construction Across 3 Languages (EL, EN, AR)
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
> Structure templates cleanly and natively using Gutenberg core blocks (Query Loop, Post Title, Post Content, Featured Image, Columns). Do NOT over-complicate block structures.

### 2. Top Bar & Header Feature Implementation (Scoped)
* Construct Header template parts (`header-el`, `header-en`, `header-ar`) strictly adhering to Phase 3 BeTheme scoping:
  - Top Bar: Polylang Language Selector / Switcher and social media links (only elements present on existing site; no un-scoped quick info links).
  - Main Navigation: Responsive Site Logo, dynamic navigation block mapped to legacy menus (`Main Greek Menu` ID 13, `Main English Menu` ID 3315, `Main Arabic Menu` ID 3316).
  - Restored Search Feature Trigger: Native modal search trigger button / block replacing legacy broken search.

### 3. Footer Implementation (Scoped)
* Construct Footer template parts (`footer-el`, `footer-en`, `footer-ar`) strictly adhering to Phase 3 BeTheme scoping:
  - Layout matching actual legacy footer widgets and structure extracted in Phase 3 (no arbitrary 4-column widget assumptions).
  - Footer Navigation Menu wired to legacy ID (`Footer Greek Menu` ID 21).
  - Copyright statement and site credits.

### 4. Visual Regression Snapshot Audit
* Execute Playwright snapshot script (`node bin/scrape-baselines.js`).
* Perform visual diff comparison of active FSE templates against baseline images in `ai-work/baselines/`.

---

## 3. Tech Stack Preferences, Build Instructions & Constraints

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
  *Compiles SCSS into css files with source maps and live watching for local theme development.*
* **Production Asset Build Command**:
  ```bash
  npm run build
  ```
  *Compiles, minifies, and optimizes ready asset bundle (`build/style-index.css`, `build/rtl-index.css`) for production deployment.*

---

## 4. Commands
```bash
# Verify PHP 8.2 runtime
php8.2 -v

# Install dependencies & compile SCSS production bundle
npm install
npm run build > ai-work/logs/phase5-deployment.log 2>&1

# Activate Flagship theme under PHP 8.2
php8.2 $(which wp) theme activate ekalexandria-flagship --path=public >> ai-work/logs/phase5-deployment.log 2>&1

# Flush permalinks
php8.2 $(which wp) rewrite flush --path=public >> ai-work/logs/phase5-deployment.log 2>&1

# Execute Playwright visual baseline snapshotting
node bin/scrape-baselines.js >> ai-work/logs/phase5-deployment.log 2>&1

# Inspect log output
cat ai-work/logs/phase5-deployment.log
```

---

## 5. Project Structure
```text
public/wp-content/themes/ekalexandria-flagship/
├── style.css
├── theme.json                    # FSE design system tokens & colors
├── package.json                  # @wordpress/scripts & SCSS build scripts
├── assets/
│   └── scss/
│       ├── style.scss            # Main SCSS design system
        └── rtl.scss              # RTL SCSS framework
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
    ├── baselines/                # Reference screenshots
    ├── logs/
    │   └── phase5-deployment.log # Phase 5 execution audit log
    └── specs/
        └── PHASE-5-PHP82-THEME-DEPLOYMENT-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. SCSS compilation verification (`npm run build`).
  2. Playwright automated visual snapshotting and diff audit.
  3. Gutenberg block editor compatibility check across templates.
  4. `debug.log` inspection during site crawl confirming 100% clean PHP 8.2 execution.
* **Boundaries**:
  - **ALWAYS**: Use `theme.json` design system tokens and compiled SCSS.
  - **ALWAYS**: Dump all command outputs into `ai-work/logs/phase5-deployment.log`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Halt at end of Phase 5 for **Final User Manual Validation & Cutover Approval**.
  - **NEVER**: Hardcode inline styles inside HTML template files.
  - **NEVER**: Add un-scoped header or footer widget assumptions.
