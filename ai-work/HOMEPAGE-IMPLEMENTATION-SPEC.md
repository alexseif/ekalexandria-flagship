# HOMEPAGE-IMPLEMENTATION-SPEC

## 1. Objective and Target Users
**Objective:** Correct previous implementation deviations and achieve a strict 1:1 visual and structural parity with the legacy EKA homepage (https://ekalexandria.org/el/). This includes fixing header behavior, implementing a block-based post slider, structuring the homepage content properly, and aligning the footer menu.
**Target Users:** All visitors and members of the EKA portal seeking community news and navigation.

## 2. Core Features and Acceptance Criteria

*   **Header Behavior:** 
    *   The header must *not* be sticky. It must scroll naturally with the rest of the page document, matching the legacy site.
*   **Hero Slider:** 
    *   Must be constrained to content-width (not full-screen bleed).
    *   Must use the [Carousel Block plugin](https://wordpress.org/plugins/carousel-block/) (or equivalent requested block plugin).
    *   Must dynamically query and display the latest 5 news posts.
*   **Community Introduction:** 
    *   Include a short introductory paragraph about the community directly below the slider.
    *   This paragraph must be user-editable via the WordPress editor, optimally rendering the static front page's main content area (`core/post-content`).
*   **Featured Pages Grid (4 Boxes):** 
    *   Implement a responsive grid/row containing exactly 4 boxes pointing to internal pages: *Υπηρεσίες, ΜΑΝΝΑ, Ιστορία, Ι.Ν. Ευαγγελισμού*. The grid must stack gracefully on mobile devices and expand to 4 columns on desktop viewports.
    *   Each box must dynamically pull and display its respective page's: **Title**, **Featured Image**, and **Excerpt**.
*   **Footer Navigation:** 
    *   The footer menu items must be forced into a single horizontal line on desktop, mimicking the legacy layout. On mobile, ensure these items respond gracefully (e.g., via wrapping or horizontal scroll) without breaking the layout.

## 3. Tech Stack Preferences and Constraints
*   **Architecture:** WordPress Full Site Editing (FSE) using block templates (`front-page.html`, `header.html`, `footer.html`).
*   **Plugins:** Install and utilize the `carousel-block` plugin via WP-CLI to handle the slider functionality efficiently without custom JS overhead.
*   **Styling:** Leverage `theme.json` for core values, and SCSS-first styling for layout structural overrides (like forcing the footer to one line or grid spacing) where FSE is insufficient.

## 4. Known Boundaries
*   **Always do:** Build mobile-first and ensure elements respond gracefully on smaller viewports. Ensure true 1:1 visual matching with the live site on desktop. Verify changes locally before committing. Follow a strict and healthy Git workflow (atomic commits per feature).
*   **Never do:** Do not leave the header sticky. Do not hardcode the 4 boxes if native FSE blocks can render them dynamically. Do not reinvent the wheel for the slider.

## 5. Design Patterns & Coding Standards, Separation of Concern
*   **Dynamic Data:** The introduction paragraph, the slider posts, and the 4 featured boxes should rely on WordPress database content rather than being hardcoded HTML.
*   **Modularity:** Ensure changes to the Header and Footer templates do not break internal pages.

## 6. Healthy Git & Implementation Workflow
*   Follow the `/build` workflow: Implement → Verify → Commit → Check-off.
*   **Commit Strategy:** Use atomic, descriptive conventional commits for each requirement:
    *   `fix(theme): remove sticky positioning from header`
    *   `feat(home): install carousel block and configure 5-post query`
    *   `feat(home): integrate editable post-content for intro paragraph`
    *   `feat(home): build 4-column grid for featured internal pages`
    *   `fix(footer): force footer navigation menu to single horizontal line`
