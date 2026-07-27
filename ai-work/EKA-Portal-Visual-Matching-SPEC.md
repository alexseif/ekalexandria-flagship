# EKA Portal Visual Matching

## 1. Objective and Target Users
- **Objective**: Precisely match the visual layout and structure of the modernized WordPress block theme (`backstage.ekalexandria.org`) to the legacy live site (`ekalexandria.org`), achieving a strict 1:1 visual clone.
- **Target Users**: End-users of the EKA portal who expect the familiar design and interface of the legacy site but with modernized underlying technology.

## 2. Core Features and Acceptance Criteria
- **Header & Top Bar**: Implement a light-gray top utility bar containing the site title on the left and social icons on the right. Modify the main header background to white. Update the navigation styling to match the dark blue text with a distinct blue button container for the active item, and remove inline Polylang switchers from the main menu.
- **Slider/Hero**: Replace the static cover placeholder on the front page with an interactive slider block (or similar dynamic hero section) equivalent to the legacy LayerSlider.
- **Homepage Content**: Add the missing introductory paragraph block ("Καλώς ήρθατε..."). Complete the 3-column "Quickfacts" section by adding the missing images and descriptions to accurately match the live site.
- **News Feed**: Remove the "Latest News / Ανακοινώσεις" Query Loop section from the homepage, as it does not exist on the live site's homepage.
- **Sidebar Layouts**: Update standard subpages (`templates/page.html` variants) to use a **Left Sidebar** layout (approx. 25% sidebar left, 75% content right). Archive templates should retain a Right Sidebar layout.
- **Footer**: Simplify the footer to mirror the live site's dark gray/blue aesthetic. Remove secondary columns, logos, and the developer attribution bar. Group the copyright text on the left and a horizontal navigation menu on the right. Implement a floating back-to-top button.

## 3. Tech Stack Preferences and Constraints
- **Core**: WordPress Full Site Editing (FSE) Block Theme leveraging HTML template parts and `theme.json`.
- **Styling**: Use FSE configuration for primary design tokens. Use SCSS for specific overrides when native block styles are insufficient.
- **Plugins**: Default to WordPress core blocks. If a slider plugin is necessary, propose a lightweight block-native alternative that aligns with the visual intent.

## 4. Boundaries
- **Always do**: Adhere to the established 960px layout constraint and legacy typography variables. Verify changes across multiple device viewpoints.
- **Ask first**: Before installing new plugins (e.g., for sliders or advanced blocks) or overriding global styles that might affect other layouts.
- **Never do**: Introduce modern design embellishments (e.g., rounded corners, drop shadows, non-standard colors) that deviate from the legacy site's aesthetics.

## 5. Design Patterns & Coding Standards
- **Separation of Concerns**: Use FSE templates for structure (`header.html`, `footer.html`, `front-page.html`), `theme.json` for broad style parameters, and localized SCSS components for targeted layout fixes.
- **Data vs Template**: Homepage content adjustments (like paragraphs and images) should ideally be made in the block editor (database content) rather than hardcoded in FSE HTML templates, unless utilizing pattern placeholders.

## 6. Testing Strategy
- **Visual Verification**: Side-by-side comparison of local templates against the live URLs for desktop and mobile viewports.
- **Functional Validation**: Ensure layout blocks adapt correctly on smaller screens and that specific legacy interactions (like the back-to-top button and active menu states) perform as expected.
