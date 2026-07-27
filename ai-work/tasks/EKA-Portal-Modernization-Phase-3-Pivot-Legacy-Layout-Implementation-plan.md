# Plan: EKA Portal Modernization - Phase 3 Pivot - Legacy Layout Implementation

## 1. Dependency Graph
- **Foundation Level**: SCSS Architecture & Compilation Setup -> `theme.json` (Colors, Typography, Spacing).
- **Global Structure**: Header Template Parts, Footer Template Parts.
- **Page Layouts**: 
  - Homepage Template -> depends on Carousel/Sliders blocks.
  - Page Templates (Default, Sidebar) -> depends on Foundation & Global Structure.
  - Archive Templates (News, Category) -> depends on Foundation & Global Structure.
- **Custom Integration**: Contact Page (Forms), Scaffolding for Phase 5 (Tachydrómos, Board of Directors).

## 2. Git & Development Workflow
- Create a feature branch: `feature/eka-portal-legacy-layout`
- Commit continuously after each task using Conventional Commits (e.g., `feat:`, `fix:`, `chore:`).
- Push branch and create a Pull Request upon reaching Checkpoint 5.

## 3. Work Phases & Vertical Slices

### Phase 1: Foundation (SCSS & FSE Core)
- **Task 1.1**: Set up SCSS architecture and build process (compile to `style.css` and/or block-specific CSS).
- **Task 1.2**: Extract typography, color palettes, and global spacing from the legacy live site.
- **Task 1.3**: Configure `theme.json` and base SCSS files to map the extracted styles globally.
- **Checkpoint 1**: Validate that native blocks render with legacy typography and colors in the editor.

### Phase 2: Global Structure
- **Task 2.1**: Implement Header block pattern and template part (Navigation, Logo, Search).
- **Task 2.2**: Implement Footer block pattern and template part (Widgets, Copyright, Links).
- **Checkpoint 2**: Verify Header and Footer match legacy site on all device sizes.

### Phase 3: Homepage Replication
- **Task 3.1**: Replicate the homepage layout using core blocks.
- **Task 3.2**: Implement a modern Carousel Block to replace the legacy LayerSlider.
- **Checkpoint 3**: Homepage is fully functional and visually identical to the legacy counterpart.

### Phase 4: Standard & Archival Pages
- **Task 4.1**: Build standard internal page template with sidebar layout.
- **Task 4.2**: Build News and Categories archival templates with sidebars.
- **Task 4.3**: Implement Contact Page replacing legacy embedded plugins with clean forms/native blocks.
- **Checkpoint 4**: Internal links and archival loops work and look identical to the legacy site.

### Phase 5: Phase 5 Scaffolding (Exceptions)
- **Task 5.1**: Scaffold placeholders/templates for Tachydrómos Newsletter Archive and Board of Directors pages.
- **Checkpoint 5**: Placeholders are ready for Phase 5 custom implementation.

## 4. Token Cost Estimation & Industry Alignment

| Phase | Estimated Input Tokens | Estimated Output Tokens | Est. Cost (Based on modern AI $3/1M In, $15/1M Out) |
|---|---|---|---|
| Phase 1: Foundation | ~40,000 | ~2,500 | ~$0.16 |
| Phase 2: Global Structure | ~50,000 | ~3,000 | ~$0.20 |
| Phase 3: Homepage | ~60,000 | ~3,500 | ~$0.23 |
| Phase 4: Standard/Archival | ~60,000 | ~4,000 | ~$0.24 |
| Phase 5: Scaffolding | ~20,000 | ~1,000 | ~$0.08 |
| **Total Estimation** | **~230,000** | **~14,000** | **~$0.91** |

*Note: Token costs are estimations and will vary depending on the actual number of iterations and debugging steps required.*

**Advice for optimization**: Focus on keeping context windows lean. Only include necessary files during context building. Utilize `theme.json` heavily to reduce the amount of custom SCSS needed, minimizing the custom code footprint and token overhead.
