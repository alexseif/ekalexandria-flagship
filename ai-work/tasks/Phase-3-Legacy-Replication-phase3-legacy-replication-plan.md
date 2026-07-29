# Plan: Phase 3 Legacy Layout Extraction & Replication (phase3-legacy-replication)

## 1. Overview and Dependency Graph
This plan addresses the strict 1:1 visual replication of the existing EKA website's layout using WordPress Full Site Editing (FSE) block architecture and `theme.json`, migrating away from legacy builders and sliders.

### Dependency Graph
1.  **Global Foundation:** `theme.json` configuration and SCSS base variables (Depends on: Extraction of live site variables).
2.  **Core Layouts:** 
    *   Homepage FSE Template & Query Loop (Depends on: Global Foundation).
    *   Internal Pages (`page.html`, `single.html`, `archive.html`) & Sidebars (Depends on: Global Foundation, `legacy_data.md` for menu mappings).
3.  **Specialty Templates:** 
    *   Board of Directors FSE Template (`page-board.html`) (Depends on: Internal Pages structure).
    *   Tachydrómos FSE Templates (`archive-alx_tachydromos.html`, `single-alx_tachydromos.html`) (Depends on: Internal Pages structure).
4.  **Plugin Mitigation (Galleries):** Specific page updates with `core/gallery` placeholders based on `legacy_data.md` image IDs (Depends on: Content readiness).

## 2. Vertical Slices and Task Breakdown

### Phase 3.1: Global Foundation & Style Mapping
*   **Action:** Extract typography, colors, and layout widths from the live legacy site. Configure `theme.json` to reflect these exact values. Establish SCSS variables for complex component overrides.
*   **Acceptance Criteria:** `theme.json` contains exact legacy color hex codes, typography stacks, and layout constraints. SCSS architecture is set up for non-block-native FSE overrides.
*   **Verification:** Inspect the FSE Site Editor globally to confirm default typography and color palettes match legacy.
*   **Git Workflow:** `git checkout -b feature/phase3-1-global-foundation` -> implement -> verify -> commit.

### Phase 3.2: Homepage FSE Replication
*   **Action:** Rebuild the Homepage layout (`front-page.html` or `home.html`) matching the legacy site. Implement a dynamic Query Loop block for the "News / Ανακοινώσεις" section (latest 5 posts).
*   **Acceptance Criteria:** Homepage structure exactly matches legacy. Query loop displays the correct 5 latest posts. No legacy page builder shortcodes are used.
*   **Verification:** Compare local homepage render against live ekalexandria.org visually. Verify the query loop parameters (News category, limit 5).
*   **Git Workflow:** `git checkout -b feature/phase3-2-homepage-replication` -> implement -> verify -> commit.

### Phase 3.3: Internal Templates & Legacy Menu Routing
*   **Action:** Build `page.html`, `single.html`, and `archive.html` FSE templates. Integrate specific sidebar Navigation blocks based on `legacy_data.md` (Establishment, Activities, Services across 3 languages).
*   **Acceptance Criteria:** Page templates have correct content width, title placement, and sidebar configurations. Navigation blocks are hardcoded/configured to pull the specified legacy Menu IDs (e.g., ID 70 for Greek Establishment).
*   **Verification:** Visit a child page under "Establishment" in Greek and verify the correct sidebar menu (ID 70) appears. Verify 1:1 visual match.
*   **Git Workflow:** `git checkout -b feature/phase3-3-internal-templates` -> implement -> verify -> commit.

### Phase 3.4: Specialty Templates (Board & Tachydrómos)
*   **Action:** Build `page-board.html`, `archive-alx_tachydromos.html`, and `single-alx_tachydromos.html`.
*   **Acceptance Criteria:** 
    *   Board template correctly queries and loops the Board CPT, respecting `menu_order`.
    *   Tachydrómos templates correctly display the PDF featured images and adhere to legacy layout.
*   **Verification:** Render the Board page and Tachydrómos archive locally; compare with legacy layout.
*   **Git Workflow:** `git checkout -b feature/phase3-4-specialty-templates` -> implement -> verify -> commit.

### Phase 3.5: Third-Party Plugin Mitigation (Galleries)
*   **Action:** Replace legacy sliders with native `core/gallery` blocks on specific static pages (Staff, Community Lounge, Cemeteries, Museums) using Image IDs specified in `legacy_data.md`.
*   **Acceptance Criteria:** Native FSE galleries are implemented in place of LayerSliders/grids.
*   **Verification:** Check the 'Cemeteries' and 'Museums' pages in the editor/frontend to ensure galleries load the correct images based on IDs.
*   **Git Workflow:** `git checkout -b feature/phase3-5-gallery-mitigation` -> implement -> verify -> commit.

## 3. Checkpoints
*   **Checkpoint 1:** After `theme.json` configuration to ensure global styles are perfectly aligned before building block layouts.
*   **Checkpoint 2:** After Homepage replication to validate the FSE grid and Query Loop logic.
*   **Checkpoint 3:** Final visual QA of all FSE templates against the live site before proceeding to Phase 4.

## 4. Estimated Token Cost & AI Standard Alignment

| Phase / Task | Estimated Tokens (In/Out) | Est. Cost (Gemini 1.5 Pro) | Industry Avg Cost (Similar Models) | Notes |
| :--- | :--- | :--- | :--- | :--- |
| 3.1: Global Foundation | ~15,000 | ~$0.05 | ~$0.07 | Heavily relies on context reading of SCSS |
| 3.2: Homepage FSE | ~20,000 | ~$0.07 | ~$0.10 | FSE HTML manipulation |
| 3.3: Internal & Sidebars | ~25,000 | ~$0.09 | ~$0.12 | Multiple templates & complex menu IDs |
| 3.4: Specialty Templates | ~20,000 | ~$0.07 | ~$0.10 | CPT query loops in HTML |
| 3.5: Plugin Mitigation | ~15,000 | ~$0.05 | ~$0.07 | Parsing `legacy_data.md` to blocks |
| **Total Phase 3** | **~95,000** | **~$0.33** | **~$0.46** | High-efficiency FSE block operations |

*Advice for improvement:* The most token-heavy operations will be iterating on the FSE block HTML (which is notoriously verbose). To optimize, use smaller template parts (e.g., `sidebar.html`, `hero.html`) and assemble them, rather than updating monolithic template files.
