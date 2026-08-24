# Layout & Component Architecture Specification (`layout_and_component_spec.md`)

This specification defines the unified front-end layout taxonomy, UI component conventions, design token system, partial template contracts, and responsive grid behaviors for `vatofichors-project-manager`.

---

## 1. UI/UX Philosophy & Design System Tokens

> [!IMPORTANT]
> **Old-School Retro Dark Mode Philosophy**:
> 1. **High-Density Data Grid**: Maximum information density, compact padding, and monospace typography (`Consolas`, `Courier New`).
> 2. **In-Flow UI Controls**: No floating out-of-flow modals or popups. Administrative actions (`POOL PROJECTS TABLE`, `TAG MANAGER`, `BACKUP`, `RESTORE`, `UPDATE STATUS`, `RENAME PROJECT`) render inline within the main view or card toolboxes.
> 3. **Curated Muted Palette**: High-contrast dark backgrounds (`#0f1115`, `#16191f`) paired with desaturated accent colors (`#2b9a90`, `#2e8b57`, `chocolate`, `darkred`, `slateblue`).

### Design Tokens (`:root` CSS Custom Variables)

```css
:root {
    /* Base Background Colors */
    --bg-dark: #0f1115;
    --bg-card: #16191f;
    --bg-input: #1f232b;
    --bg-modal-overlay: rgba(0, 0, 0, 0.8);

    /* Border Colors */
    --border-color-config: #2b303c;
    --border-focus: #4a5264;
    --border-closed: #3b4252;

    /* Text Colors */
    --text-main: #e1e4ea;
    --text-muted: slateblue;
    --text-white: #ffffff;
    --text-black: #000000;
    --text-closed: #5a6272;

    /* Accent & Status Palette */
    --accent-theme: #2b9a90;
    --theme-hover: #39b5aa;
    --accent-green: #2e8b57;
    --accent-amber: chocolate;
    --accent-red: darkred;

    /* Typography Fonts */
    --font-mono: 'Consolas', 'Courier New', monospace;
    --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
```

---

## 2. Structural Section Taxonomy

```
+-----------------------------------------------------------------------+
| 1. APP HEADER (.app-header)                                           |
+-----------------------------------------------------------------------+
| 2. MAIN TOOLBAR & NAVIGATION (.toolbar.main-toolbar)                  |
+-----------------------------------------------------------------------+
| 3. SUB-ACTIONS BAR (.sub-actions-bar)                                 |
+-----------------------------------------------------------------------+
| 4. VIEWPORT CARD (.viewport-card) [For Viewport-enabled Routes]       |
|   +-----------------------------------------------------------------+ |
|   | 4a. VIEWPORT HEADER & SUB-ACTIONS TOOLBAR (.viewport-header)    | |
|   +-----------------------------------------------------------------+ |
|   | 4b. VIEWPORT FILTER TOOLBAR (.viewport-filter-toolbar)          | |
|   +-----------------------------------------------------------------+ |
|   | 4c. VIEWPORT CONTENT AREA (.viewport-box / #adminViewport)       | |
|   +-----------------------------------------------------------------+ |
+-----------------------------------------------------------------------+
| 5. CONTENT BODY (.content-body) [For Standard Matrix/Single Views]    |
+-----------------------------------------------------------------------+
| 6. APP FOOTER (.app-footer) [templates/footer-layout.php]             |
+-----------------------------------------------------------------------+
```

### Standardized Naming & CSS Class Mapping

| Section Name | HTML Element / Class | Description |
| :--- | :--- | :--- |
| **1. App Header** | `<header class="app-header">` | Top shell header; renders logo emoticon, app title, dynamic route subtitle, and metadata. |
| **2. Main Toolbar & Navigation** | `<nav class="toolbar main-toolbar">` | Top navigation bar; houses category filters, status filters, route back links, and inline action buttons. |
| **3. Sub-Actions Bar** | `<section class="sub-actions-bar">` | Route-specific action button taglist (e.g. `POOL PROJECTS TABLE`, `[!] DELETE PROJECT`). |
| **4a. Viewport Header Toolbar** | `<div class="viewport-header">` | Card header wrapping title badge, dynamic sub-actions (`#adminSubActions`), and utility buttons (`CLEAR`). |
| **4b. Viewport Filter Toolbar** | `<div class="viewport-filter-toolbar">` | Selection control bar inside tables (`SELECT ALL`, `SELECT OUTER`, `DESELECT ALL`, `Selected: N`). |
| **4c. Viewport Content Area** | `<div id="adminViewport" class="viewport-box">` | Rendered output container for JSON reports, interactive checkbox tables, and danger zone cards. |
| **5. Content Body** | `<main class="content-body">` | Main layout container (`.dashboard-grid` for Home, `.dashboard-single` for Project). |
| **6. App Footer** | `<footer class="app-footer">` | Global bottom signature block (`templates/footer-layout.php`). |

---

## 3. Route-Specific Mapping Matrix

### Route 1: Home Dashboard (`?route=home`)
- **App Header**: Global shell header displaying logo emoticon and application title.
- **Main Toolbar & Navigation**: Category Filters + Status Filters + `ADMIN` button + `LESS TEXT / MORE TEXT` toggle + `SEARCH` prompt button.
- **Content Body**: `.dashboard-grid` rendering project cards via `templates/project-card-layout.php`.
- **App Footer**: Reusable partial `templates/footer-layout.php`.

### Route 2: Single Project View (`?route=project&title=ID`)
- **App Header**: Dynamic route subtitle hooked to active project title.
- **Main Toolbar & Navigation**: `< BACK TO DASHBOARD` + Category Tag + Project ID + Status Select Dropdown.
- **Sub-Actions Bar**: `.sub-actions-bar` containing `RENAME PROJECT`, `UPDATE STATUS`, and `[!] DELETE PROJECT` action buttons.
- **Content Body**: `.dashboard-single` rendering full-screen expanded project scope textareas and interactive task management card.
- **App Footer**: Reusable partial `templates/footer-layout.php`.

### Route 3: Admin Workspace (`?route=admin`)
- **App Header**: Dynamic route subtitle set to `Admin`.
- **Main Toolbar & Navigation**: `< BACK TO DASHBOARD` + `Admin Workspace` badge.
- **Sub-Actions Bar**: `.sub-actions-bar` containing `POOL PROJECTS TABLE`, `TAG MANAGER`, `BACKUP PROJECTS.JSON`, `RESTORE PROJECTS.JSON`, and `DELETE ALL PROJECTS`.
- **Viewport Card**:
  - **Viewport Header Toolbar**: Title badge + dynamic sub-actions (`+ COMMIT CHECKLIST`, `RELOAD TABLE`) + `CLEAR` button.
  - **Viewport Filter Toolbar**: `DISCOVERED PROJECTS TABLE` title + `SELECT ALL` + `SELECT OUTER` + `DESELECT ALL` + `Selected: N` badge.
  - **Viewport Content Area**: Dedicated `#adminViewport` box rendering interactive directory tables or administration cards.
- **App Footer**: Reusable partial `templates/footer-layout.php`.

---

## 4. Shared Component & Partial Templates

### A. Project Card Partial (`templates/project-card-layout.php`)
Reused across `home` dashboard grid cards and single `project` view:
1. **Card Header (`.card-header`)**: Displays category tag badge, project title link, status pill, and tag flags (`PROJECTS`, `TOOLS`, `MIT`, `OPEN SOURCE`).
2. **Scope Section (`.scope-container`)**:
   - `IN-SCOPE`: Primary objective text.
   - `ANTI-SCOPE`: Boundary and exclusion text.
   - `EDGE RISKS`: Architectural risk notes.
3. **Task List Module (`.task-list-module`)**:
   - Task Header: `TASKS` label + dynamic `X / Y DONE` badge (`updateTaskCountBadge()`).
   - Add Task Form: Input field + `+` submit button retaining focus on submission.
   - Task Items: Interactive checkbox items with completed strikethrough styling and `✕` delete action.

### B. Reusable Footer Partial (`templates/footer-layout.php`)
```html
<footer class="app-footer" style="text-align: right;">
<code>
Copyright (c) 2026: vatofichor - Sebastian Mass [&gt;_&lt;]
<br>
Assisted By Gemini Antigravity &nbsp;&nbsp;&nbsp;/|\&nbsp;
</code>
</footer>
```

---

## 5. Responsive Design & Textarea Visibility System

### Responsive Breakpoints
- **Mobile Viewports (`@media (max-width: 640px)`)**:
  - `.dashboard-grid` switches to 1-column layout (`grid-template-columns: 1fr`).
  - `.app-header` stacks logo, title, and navigation buttons vertically.
  - Form inputs and filter toolbars wrap controls gracefully.
- **Tablet Viewports (`@media (max-width: 840px)`)**:
  - `.dashboard-grid` scales to 2-column layout (`grid-template-columns: repeat(2, 1fr)`).

### Textarea Visibility Toggle (`.hide-textareas`)
- Toggling `LESS TEXT` / `MORE TEXT` applies or removes the `.hide-textareas` class on `document.body`.
- When `.hide-textareas` is active, CSS hides `.scope-container` textareas inside Home dashboard cards while preserving single project view textboxes.
- User choice persists across reloads via `localStorage.getItem('dashboard_hide_textareas')`.

---
# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\
