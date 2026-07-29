# Homepage Implementation Todo List
## Topic: HOMEPAGE
## Issue: IMPLEMENTATION

- [ ] **Phase 1: Global Layout Alignment**
  - [x] Task 1: Fix Header Behavior (Remove sticky)
    - [x] Modify `header*.html` templates.
    - [x] Verify scrolling behavior.
    - [x] Commit: `fix(theme): remove sticky positioning from header`
  - [ ] Task 2: Fix Footer Navigation (Single horizontal line)
    - [ ] Modify `footer*.html` templates or `style.scss`.
    - [ ] Verify desktop (one line) and mobile (responsive wrap/scroll).
    - [ ] Commit: `fix(footer): force footer navigation menu to single horizontal line`

- [ ] **Checkpoint 1: Global layouts matched**

- [ ] **Phase 2: Homepage Dynamic Content**
  - [ ] Task 3: Install Carousel Dependency
    - [ ] Run `wp plugin install carousel-block --activate`.
    - [ ] Verify block availability.
  - [ ] Task 4: Implement Hero Slider
    - [ ] Edit `front-page.html` to add the Carousel Block (content-width, 5 posts).
    - [ ] Verify slider visuals and constraints on frontend.
    - [ ] Commit: `feat(home): install carousel block and configure 5-post query`
  - [ ] Task 5: Add Community Introduction
    - [ ] Add `core/post-content` block in `front-page.html`.
    - [ ] Verify editable text appears on frontend.
    - [ ] Commit: `feat(home): integrate editable post-content for intro paragraph`
  - [ ] Task 6: Build Featured Pages Grid
    - [ ] Add 4-column responsive grid to `front-page.html`.
    - [ ] Map boxes to specific pages (Title, Image, Excerpt).
    - [ ] Verify mobile stacking and desktop 4-column layout.
    - [ ] Commit: `feat(home): build 4-column grid for featured internal pages`

- [ ] **Checkpoint 2: Homepage structure complete**
