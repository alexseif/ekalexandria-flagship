# EKA Portal Visual Matching - Todo List



## Phase 1: Global Navigation & Footer (The Shell)
- [ ] **Task 1:** Header & Top Bar Matching
  - [ ] Implement light-gray top utility bar (title left, social icons right).
  - [ ] Set main header background to white.
  - [ ] Style navigation (dark blue text, blue button container for active item).
  - [ ] Remove inline Polylang switchers from the main menu.
  - [ ] `git commit -m "feat(header): match header and top bar with legacy design"`
- [ ] **Task 2:** Footer Simplification
  - [ ] Apply dark gray/blue aesthetic.
  - [ ] Remove secondary columns, logos, and developer attribution bar.
  - [ ] Group copyright text on left, horizontal nav on right.
  - [ ] Implement floating back-to-top button.
  - [ ] `git commit -m "feat(footer): simplify footer to match legacy aesthetic"`
- [ ] **Checkpoint 1:** Review shell (Header & Footer) against legacy site.

## Phase 2: Homepage Parity
- [ ] **Task 3:** Homepage Slider/Hero Integration
  - [ ] Remove static cover placeholder from `front-page.html`.
  - [ ] Implement interactive slider block to replicate legacy LayerSlider.
  - [ ] `git commit -m "feat(home): integrate homepage interactive slider"`
- [ ] **Task 4:** Homepage Content & News Feed Restructuring
  - [ ] Add introductory paragraph ("Καλώς ήρθατε...").
  - [ ] Complete 3-column "Quickfacts" (add missing images and descriptions).
  - [ ] Remove "Latest News / Ανακοινώσεις" Query Loop.
  - [ ] `git commit -m "feat(home): restructure homepage content and remove news feed"`
- [ ] **Checkpoint 2:** Review homepage against legacy site.

## Phase 3: Inner Pages & Layout Structure
- [ ] **Task 5:** Sidebar Layouts Standardization
  - [ ] Update standard subpages to Left Sidebar layout (25/75 split).
  - [ ] Verify archive templates retain Right Sidebar layout.
  - [ ] `git commit -m "feat(layout): standardize left sidebar layout for standard pages"`
- [ ] **Checkpoint 3:** Review inner pages and archives against legacy site.

## Post-requisites
- [ ] Push branch to remote (`git push origin feature/visual-matching`)
- [ ] Open Pull Request and request review.
