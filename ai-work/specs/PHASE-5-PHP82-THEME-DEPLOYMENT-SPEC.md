# Specification: Phase 5 - PHP 8.2 Upgrade, Theme Deployment & Visual Audit

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 5 - FSE Flagship Theme Activation, Layout Templates & Visual Audit  
**STATUS**: `[PLANNED / AI ACTIVE]`  

---

## 1. Objective & Target Users
* **Objective**: Activate the bespoke `ekalexandria-flagship` Full Site Editing (FSE) block theme under PHP 8.2, construct multi-language layout templates (EL, EN, AR) for Front-Page, Pages, Posts, Board Members, and Tachydromos, build top bar, header (with search), and footer components matching legacy elements, assign menus, and perform Playwright visual regression snapshots.
* **Target Users**: Portal visitors, mobile & desktop users, site administrators.

---

## 2. Detailed Layout & Template Implementation Requirements `[AI ACTIVE]`

### 1. Template Construction Across 3 Languages (EL, EN, AR)
* **Homepage Templates**:
  - `front-page-el.html` (mapped to `header-el.html` and `footer-el.html`)
  - `front-page-en.html` (mapped to `header-en.html` and `footer-en.html`)
  - `front-page-ar.html` (mapped to `header-ar.html` and `footer-ar.html`)
* **Page Templates**:
  - `page-el.html`, `page-en.html`, `page-ar.html` (or `page.html` with dynamic language parts)
* **Post Templates**:
  - `single-el.html`, `single-en.html`, `single-ar.html` (or `single.html` with dynamic language parts)
* **Board Members List Page Templates**:
  - `board-members-el.html`, `board-members-en.html`, `board-members-ar.html`
* **Alexandrinos Tachydromos Page Templates**:
  - `tachydromos-el.html`, `tachydromos-en.html`, `tachydromos-ar.html` (supporting newsletter PDF viewer layout across languages even though publication content is Greek)

### 2. Top Bar Implementation
* Construct native block-based Top Bar in header template parts (`header-el`, `header-en`, `header-ar`) containing:
  - Polylang Language Selector / Switcher.
  - Quick Info Links (Contact info, address, working hours).
  - Social media icon links.

### 3. Main Navigation & Search Feature Implementation
* Construct Main Navigation bar containing:
  - Site Logo (responsive SVG / unscaled asset).
  - Dynamic Navigation block wired to legacy menu IDs (`Main Greek Menu` ID 13, `Main English Menu` ID 3315, `Main Arabic Menu` ID 3316).
  - Restored Search Feature Trigger (native modal search button / block replacing legacy broken search).

### 4. Footer Implementation
* Construct Footer template parts (`footer-el`, `footer-en`, `footer-ar`) containing:
  - 4-Column Footer Widget layout.
  - Footer Navigation Menu wired to legacy ID (`Footer Greek Menu` ID 21).
  - Copyright statement and credits in current language.

### 5. Visual Regression Snapshot Audit
* Execute Playwright snapshot script (`node bin/scrape-baselines.js`).
* Perform visual diff comparison of active FSE templates against baseline images in `ai-work/baselines/`.

---

## 3. Tech Stack Preferences & Constraints
* **PHP Target**: PHP 8.2.
* **Theme Architecture**: Block Theme (FSE), `theme.json` v2 schema, modular SCSS design system (`assets/scss/`).
* **Visual Audit**: Playwright pixel-by-pixel comparison tool.

---

## 4. Commands
```bash
# Verify PHP 8.2 runtime
php8.2 -v

# Activate Flagship theme under PHP 8.2
php8.2 $(which wp) theme activate ekalexandria-flagship --path=public

# Flush permalinks
php8.2 $(which wp) rewrite flush --path=public

# Execute Playwright visual baseline snapshotting
node bin/scrape-baselines.js
```

---

## 5. Project Structure
```text
public/wp-content/themes/ekalexandria-flagship/
├── style.css
├── theme.json                    # FSE design system tokens & colors
├── templates/
│   ├── front-page-el.html
│   ├── front-page-en.html
│   ├── front-page-ar.html
│   ├── page.html
│   ├── single.html
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
    └── specs/
        └── PHASE-5-PHP82-THEME-DEPLOYMENT-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Playwright automated visual snapshotting and diff audit.
  2. Gutenberg block editor compatibility check.
  3. `debug.log` inspection during site crawl confirming 100% clean PHP 8.2 execution.
* **Boundaries**:
  - **ALWAYS**: Use `theme.json` design system tokens.
  - **ALWAYS**: Halt at end of Phase 5 for **Final User Manual Validation & Cutover Approval**.
  - **NEVER**: Hardcode inline styles inside HTML template files.
