# EKA Portal Visual Matching - Todo List



## Phase 1: Global Navigation & Footer (The Shell)
- [x] **Task 1:** Header & Top Bar Matching
  - [x] Implement light-gray top utility bar (title left, social icons right).
  - [x] Set main header background to white.
  - [x] Style navigation (dark blue text, blue button container for active item).
  - [x] Remove inline Polylang switchers from the main menu.
  - [x] `git commit -m "feat(header): match header and top bar with legacy design"`
- [x] **Task 2:** Footer Simplification
  - [x] Apply dark gray/blue aesthetic.
  - [x] Remove secondary columns, logos, and developer attribution bar.
  - [x] Group copyright text on left, horizontal nav on right.
  - [x] Implement floating back-to-top button.
  - [x] `git commit -m "feat(footer): simplify footer to match legacy aesthetic"`
- [x] **Checkpoint 1:** Review shell (Header & Footer) against legacy site.

## Phase 2: Homepage Parity
- [x] **Task 3:** Homepage Slider/Hero Integration
  - [x] Remove static cover placeholder from `front-page.html`.
  - [x] Implement interactive slider block to replicate legacy LayerSlider.
  - [x] `git commit -m "feat(home): integrate homepage interactive slider"`
- [x] **Task 4:** Homepage Content & News Feed Restructuring
  - [x] Add introductory paragraph ("Καλώς ήρθατε...").
  - [x] Complete 3-column "Quickfacts" (add missing images and descriptions).
  - [x] Remove "Latest News / Ανακοινώσεις" Query Loop.
  - [x] `git commit -m "feat(home): restructure homepage content and remove news feed"`
- [x] **Checkpoint 2:** Review homepage against legacy site.

## Phase 3: Inner Pages & Layout Structure
- [x] **Task 5:** Sidebar Layouts Standardization
  - [x] Update standard subpages to Left Sidebar layout (25/75 split).
  - [x] Update Archives and Search pages to Right Sidebar layout (75/25 split).
  - [x] Implement custom "Contact" page template without sidebar.
  - [x] `git commit -m "feat: implement standard page, archive, and contact legacy sidebar layouts"`
- [x] **Checkpoint 3:** Review inner pages and archives against legacy site.

## Post-requisites
- [x] Push branch to remote (`git push origin feature/visual-matching`)
- [x] Open Pull Request and request review.
