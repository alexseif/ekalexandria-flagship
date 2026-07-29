# Homepage Implementation Plan
## Topic: HOMEPAGE
## Issue: IMPLEMENTATION

### 1. Overview and Dependency Graph
The objective is to achieve a 1:1 visual match with the legacy EKA homepage.
The dependency graph is as follows:
- **Global Elements:** Header (remove sticky), Footer (horizontal menu). These affect all templates and should be addressed to provide the correct framing.
- **Plugin Dependency:** The `carousel-block` plugin must be installed and activated before the Hero Slider can be implemented on the homepage.
- **Homepage Specifics (front-page.html):**
  - Depends on `carousel-block` for the Slider.
  - Depends on the core `post-content` block for the Community Introduction.
  - Depends on native query loops or group blocks for the 4 Featured Pages Grid.

### 2. Vertical Task Slicing

**Phase 1: Global Layout Alignment**
- **Task 1: Fix Header Behavior**
  - *Action:* Remove sticky positioning from all header template parts (`header.html`, `header-el.html`, etc.).
  - *Acceptance Criteria:* The header scrolls naturally with the document.
  - *Verification:* Open any page, scroll down, verify the header disappears as you scroll.
- **Task 2: Fix Footer Navigation**
  - *Action:* Modify footer template parts (`footer.html`, `footer-el.html`, etc.) and/or add SCSS/theme.json styles to force the menu items into a single horizontal line on desktop.
  - *Acceptance Criteria:* Footer menu is on one line on desktop; responds gracefully on mobile (wrap or scroll).
  - *Verification:* Check footer at desktop width and mobile width.

**Checkpoint 1:** Global header and footer match the legacy site.

**Phase 2: Homepage Dynamic Content**
- **Task 3: Install Carousel Dependency**
  - *Action:* Install and activate the `carousel-block` plugin via WP-CLI.
  - *Acceptance Criteria:* Plugin is active and the block is available in the editor.
  - *Verification:* Run `wp plugin status carousel-block` or check the block inserter.
- **Task 4: Implement Hero Slider**
  - *Action:* Edit `front-page.html` to add the Carousel Block, constrained to content-width, querying the latest 5 news posts.
  - *Acceptance Criteria:* Slider displays 5 posts, is not full-bleed (matches content width).
  - *Verification:* View homepage and confirm slider functionality and constraints.
- **Task 5: Add Community Introduction**
  - *Action:* Add the `core/post-content` block below the slider in `front-page.html` to allow an editable intro paragraph.
  - *Acceptance Criteria:* Content edited in the "Front Page" page from the WP admin reflects on the homepage.
  - *Verification:* Add dummy text in WP admin, view front end.
- **Task 6: Build Featured Pages Grid**
  - *Action:* Implement a 4-column responsive grid in `front-page.html` linking to *Υπηρεσίες, ΜΑΝΝΑ, Ιστορία, Ι.Ν. Ευαγγελισμού*. Use native blocks (e.g., Columns, Group, Post Title, Post Excerpt, Post Featured Image via Query Loop or manual linking if necessary for pages).
  - *Acceptance Criteria:* Exactly 4 boxes showing Title, Image, Excerpt. Stacks on mobile, 4 columns on desktop.
  - *Verification:* Resize browser to ensure responsiveness. Compare visually to legacy site.

**Checkpoint 2:** Homepage structure and functionality is complete and visually matched.

### 3. Git Workflow
Follow the `/build` workflow: Implement → Verify → Commit → Check-off.
*   `fix(theme): remove sticky positioning from header`
*   `fix(footer): force footer navigation menu to single horizontal line`
*   `feat(home): install carousel block and configure 5-post query`
*   `feat(home): integrate editable post-content for intro paragraph`
*   `feat(home): build 4-column grid for featured internal pages`

### 4. Estimated Token Cost (Industry Standards)
Assuming average interaction length and context window retention:

| AI Model / Tier | Token Estimate (In/Out) | Estimated Cost ($) | Notes |
| :--- | :--- | :--- | :--- |
| Gemini 1.5 Pro | 50k In / 5k Out | ~$0.20 - $0.35 | Highly capable, good for complex FSE mapping. |
| GPT-4o | 50k In / 5k Out | ~$0.30 - $0.40 | Standard industry benchmark. |
| Claude 3.5 Sonnet| 50k In / 5k Out | ~$0.22 - $0.25 | Fast and excellent at FSE coding tasks. |

*Areas to improve cost:* Clear context window between distinct phases. Focus purely on modifying only the necessary `.html` FSE files and SCSS without re-reading the entire theme structure repeatedly.
