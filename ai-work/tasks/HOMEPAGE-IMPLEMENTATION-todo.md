# Homepage Implementation Todo List
## Topic: HOMEPAGE
## Issue: IMPLEMENTATION

- [x] **Phase 1: Global Layout Alignment**
  - [x] Task 1: Fix Header Behavior (Remove sticky)
    - [x] Modify `header*.html` templates.
    - [x] Verify scrolling behavior.
    - [x] Commit: `fix(theme): remove sticky positioning from header`
  - [x] Task 2: Fix Footer Navigation (Single horizontal line)
    - [x] Modify `footer*.html` templates or `style.scss`.
    - [x] Verify desktop (one line) and mobile (responsive wrap/scroll).
    - [x] Commit: `fix(footer): force footer navigation menu to single horizontal line`

- [x] **Checkpoint 1: Global layouts matched**

- [x] **Phase 2: Homepage Dynamic Content**
  - [x] Task 3: Install Carousel Dependency
    - [x] Run `wp plugin install carousel-block --activate`.
    - [x] Verify block availability.
  - [x] Task 4: Implement Hero Slider
    - [x] Edit `front-page.html` to add the Carousel Block (content-width, 5 posts).
    - [x] Verify slider visuals and constraints on frontend.
    - [x] Commit: `feat(home): install carousel block and configure 5-post query`
  - [x] Task 5: Add Community Introduction
    - [x] Add `core/post-content` block in `front-page.html`.
    - [x] Verify editable text appears on frontend.
    - [x] Commit: `feat(home): integrate editable post-content for intro paragraph`
  - [x] Task 6: Build Featured Pages Grid
    - [x] Add 4-column responsive grid to `front-page.html`.
    - [x] Map boxes to specific pages (Title, Image, Excerpt).
    - [x] Verify mobile stacking and desktop 4-column layout.
    - [x] Commit: `feat(home): build 4-column grid for featured internal pages`

- [x] **Checkpoint 2: Homepage structure complete**
