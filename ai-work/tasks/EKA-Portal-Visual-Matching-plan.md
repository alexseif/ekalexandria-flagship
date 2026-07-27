# EKA Portal Visual Matching Plan

## 1. Dependency Graph
- **Global Styles & SCSS (`theme.json`, `theme-components.scss`)**
  - Provides foundation for legacy variables, typography, and precise spacing.
  - *Dependencies:* None.
- **Header Structure (`parts/header.html` & translations)**
  - Requires layout adjustments (sliding top bar colors, nav styling).
  - *Dependencies:* Global Styles.
- **Footer Structure (`parts/footer.html` & translations)**
  - Requires layout simplification and color matching.
  - *Dependencies:* Global Styles.
- **Homepage Structure (`templates/front-page.html`)**
  - Requires slider block integration, intro paragraph addition, 3-column update, and news feed removal.
  - *Dependencies:* Header, Footer, Global Styles.
- **Sidebar Layouts (`templates/page.html`, `templates/archive.html`, etc.)**
  - Requires column structural changes (Left Sidebar for pages, Right Sidebar for archives).
  - *Dependencies:* Header, Footer, Global Styles.

## 2. Vertical Task Slices & Acceptance Criteria

### Phase 1: Global Navigation & Footer (The Shell)

**Task 1: Header & Top Bar Matching**
- **Action:** Update `parts/header.html` (and translated parts) to match legacy header.
- **Acceptance Criteria:**
  - Light-gray top utility bar exists with correct title on left and social icons on right.
  - Main header background is white.
  - Navigation text is dark blue. Active item has a distinct blue button container.
  - Inline Polylang switchers are removed from the main menu.
- **Verification:** Visual comparison against legacy site header.

**Task 2: Footer Simplification**
- **Action:** Update `parts/footer.html` (and translated parts) to match legacy dark gray/blue aesthetic.
- **Acceptance Criteria:**
  - Secondary columns, logos, and developer attribution bar removed.
  - Copyright text grouped on the left.
  - Horizontal navigation menu grouped on the right.
  - Floating back-to-top button implemented.
- **Verification:** Visual comparison against legacy site footer; back-to-top button functionality test.

**Checkpoint 1:** The outer shell (header, navigation, footer) completely matches the legacy site.

---

### Phase 2: Homepage Parity

**Task 3: Homepage Slider/Hero Integration**
- **Action:** Replace static placeholder in `templates/front-page.html` with an interactive slider.
- **Acceptance Criteria:**
  - Static cover block removed.
  - Native FSE block or lightweight alternative used to replicate legacy LayerSlider visual impact.
- **Verification:** Slider renders correctly on desktop and mobile, full width.

**Task 4: Homepage Content & News Feed Restructuring**
- **Action:** Update `templates/front-page.html` content blocks.
- **Acceptance Criteria:**
  - Missing intro paragraph ("Καλώς ήρθατε...") is added.
  - 3-column "Quickfacts" section has accurate missing images and descriptions.
  - "Latest News / Ανακοινώσεις" Query Loop is completely removed.
- **Verification:** Side-by-side comparison of the homepage content structure.

**Checkpoint 2:** Homepage visually and structurally matches the legacy live site.

---

### Phase 3: Inner Pages & Layout Structure

**Task 5: Sidebar Layouts Standardization**
- **Action:** Update `templates/page.html` variants and `templates/archive.html` variants.
- **Acceptance Criteria:**
  - `page.html` (and variants) uses a Left Sidebar layout (approx 25% left, 75% right).
  - Archive templates retain a Right Sidebar layout.
- **Verification:** Navigate to a standard page and an archive page to verify column structures.

**Checkpoint 3:** Inner pages and archives follow exact structural constraints of the legacy site.

---

## 3. Build & Git Workflow

To ensure stability and adherence to the user's `global_rules` (`Implement -> Verify -> Commit -> Check-off`), the following Git workflow MUST be enforced for each task:

1. **Branching**: Continue work on the current branch, as it serves this purpose.
2. **Implementation**: Execute the task as described in the vertical slice.
3. **Verification**: Visually and functionally verify changes *before* committing. "Seems right" is not sufficient. 
4. **Committing**: 
   - Use conventional commit messages.
   - Example: `feat(header): match header and top bar with legacy design`
   - **Never auto-commit**; always present the user with the git status and ask for approval to commit or explicitly run the commit if instructed.
5. **Check-off**: Check off the task in the `todo.md` list only after the commit is successful.
6. **Integration**: Upon phase completion or project completion (checkpoints), push the branch and open a Pull Request.

---

## 4. Estimated Token Cost & Optimization Advice

| Phase / Task | Estimated Input Tokens | Estimated Output Tokens | Estimated Cost (Industry Avg) |
|--------------|------------------------|-------------------------|-------------------------------|
| Phase 1: Shell | ~4,500 | ~2,000 | ~$0.08 |
| Phase 2: Home | ~3,500 | ~1,500 | ~$0.06 |
| Phase 3: Pages | ~3,000 | ~1,500 | ~$0.05 |
| **Total** | **~11,000** | **~5,000** | **~$0.19** |

*Note: Calculations based on standard $3/1M input and $15/1M output for High-End models.*

**Areas to Improve / Token Cost Advice:**
- **Translation Overheads:** The theme uses `header-ar.html`, `header-el.html`, etc. Updating all of these concurrently increases token usage significantly. Consider synchronizing one language perfectly first (e.g., base `header.html`), testing it, and then explicitly duplicating to other language templates to save iterative context tokens.
- **SCSS Consolidation:** If many small SCSS tweaks are needed for visual matching, batch them in a single pass rather than doing iterative style tweaks to minimize context window refreshes.
