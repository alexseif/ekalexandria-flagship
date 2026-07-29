# Phase 3 Legacy Layout Extraction & Replication
# phase3-legacy-replication

## 1. Objective and Target Users
**Objective:** Execute a strict 1:1 visual replication of the existing EKA website's layout (as a pivot from the redesign). This includes the **Homepage**, Internal Pages, and specialty pages (Tachydrómos Newsletter and Board of Directors) to unblock technical upgrades while maintaining the familiar aesthetic.

## 2. Core Features and Acceptance Criteria
*   **Style Extraction & Variable Mapping:** Extract typography, colors, and layout widths from the live site to establish the foundation for `theme.json` and block settings.
*   **Homepage & Core Structural Parity:** 
    *   Replicate the Homepage layout to match the legacy site exactly.
    *   Implement a dynamic Query Loop block (or native block equivalent) for the Homepage "News / Ανακοινώσεις" section pulling the latest 5 posts, as defined in `legacy_data.md`.
    *   Ensure the structure of Internal Pages and News/Category layouts map to native block patterns without altering the original visual aesthetic.
*   **Specialty Page Development:** Build native FSE templates (`archive-alx_tachydromos.html`, `single-alx_tachydromos.html`, `page-board.html`) that perfectly match the legacy visual layout.
*   **Legacy Data & Navigation Integration:** Ensure the FSE templates correctly implement the specific sidebars and menus (Greek/English/Arabic) mapped in `legacy_data.md`.
*   **Third-Party Plugin Mitigation:** Replace legacy LayerSliders and visual builder grids with modern, lightweight equivalents like native Gutenberg blocks (e.g., core Galleries), using the exact Image IDs specified in `legacy_data.md` for pages like Cemeteries, Museums, and Staff.

## 3. Tech Stack Preferences and Constraints
*   WordPress Full Site Editing (FSE) block architecture powered by `theme.json`.
*   SCSS-first styling for complex component overrides (no inline bloat).
*   Compatible with the data structures defined in the `cpt_migration_spec.md` (e.g., using `menu_order` for Board Members, and featured images derived from PDFs for Newsletters).

## 4. Known Boundaries
*   **Always do:** Maintain exact 1:1 visual parity with the legacy design. Ensure proper Polylang menu mapping across the 3 languages. Maintain the specific Greek rewrite slugs (e.g., `αλεξανδρινός-ταχυδρόμος`).
*   **Never do:** Reintroduce legacy page builders. Do not alter the legacy media library structure (no media duplication). Do not expose the Tachydrómos archive to Polylang translation routing (as per spec).

## 5. Design Patterns & Coding Standards Required, Separation of Concern
*   Strict separation between data (managed by the CLI migration scripts in Phase 5/6) and layout presentation (managed by these FSE templates in Phase 3).
*   Use native Block Patterns and template parts for reusability.
*   Maintain idempotency where programmatic logic is required.
