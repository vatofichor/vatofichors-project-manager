Timestamp: 2026-08-25T21:51:39Z

# Layout & Component Architecture Specification (`layout_and_component_spec.md`)

This specification defines the unified front-end layout taxonomy, UI component conventions, design token system, partial template contracts, responsive grid behaviors, and toolbar action tiers for `vatofichors-project-manager`.

---

## 1. UI/UX Philosophy & Design System Tokens

> [!IMPORTANT]
> **Old-School Retro Dark Mode Philosophy**:
> 1. **High-Density Data Grid**: Maximum information density, compact padding, and monospace typography (`Consolas`, `Courier New`).
> 2. **In-Flow UI Controls**: No floating out-of-flow modals or popups. Administrative actions (`POOL PROJECTS TABLE`, `TAG MANAGER`, `BACKUP`, `RESTORE`, `UPDATE STATUS`, `RENAME PROJECT`, `+ CREATE TASK LIST`) render inline within the main view or card toolboxes.
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

## 2. The 5 Application Toolbar Action Tiers & ARIA / Keyboarding Taxonomy

The application UI taxonomy establishes 5 distinct action toolbar tiers across different view routes. Each tier maintains explicit contracts for user action location, DOM class selector, ARIA accessibility semantics, and keyboard navigation focus (`.focus()`):

```
+---------------------------------------------------------------------------------------------------+
| APPLICATION TOOLBAR ACTION TAXONOMY (5 TIERS)                                                     |
+---------------------------------------------------------------------------------------------------+
| Tier 1: NAVIGATION TOOLBAR RIGHT-SIDE TOOLS (.toolbar.main-toolbar .toolbar-top-row)             |
| Tier 2: ACTION MENU TOOLBAR (.sub-actions-bar)                                                    |
| Tier 3: SUB-ACTION TOOLBAR [VIEWPORT HEADER] (.viewport-header .viewport-actions)                |
| Tier 4: SUB-HEADER FILTER ACTIONS (.toolbar-bottom-row / .scroll-filter-widget)                  |
| Tier 5: PROJECT-ROUTE ADDITIONAL TASK LIST TOOLBAR (.additional-lists-toolbar)                   |
+---------------------------------------------------------------------------------------------------+
```

### Detailed Tier Matrix

| Tier Name | DOM Selector | Action Controls & Triggers | ARIA Role & Semantics | Keyboarding & Focus Rules |
| :--- | :--- | :--- | :--- | :--- |
| **Tier 1: Navigation Toolbar Right-Side Tools** | `.toolbar.main-toolbar .toolbar-top-row` | `SEARCH`, `LESS TEXT / MORE TEXT`, `BACKUP NOW`, `ADMIN`, `+ NEW PROJECT`, `< BACK TO DASHBOARD` | `role="navigation"` `aria-label="Global Application Toolbar"` | Tab-navigable inline buttons with visible focus rings (`--border-focus`). Focus returns to trigger after modal/action close. |
| **Tier 2: Action Menu Toolbar** | `.sub-actions-bar` | `UPDATE STATUS`, `RENAME PROJECT`, `[!] DELETE PROJECT`, `POOL PROJECTS TABLE`, `TAG MANAGER`, `BACKUP PROJECTS.JSON`, `RESTORE PROJECTS.JSON`, `CONFIG EDITOR`, `DELETE ALL PROJECTS` | `role="toolbar"` `aria-label="Route Action Commands Menu"` | Primary route action triggers. Focus is managed when toggling in-flow toolboxes (e.g. `#updateStatusBox`). |
| **Tier 3: Sub-Action Toolbar (Viewport Header)** | `.viewport-header .viewport-actions` | Dynamic sub-actions (`#adminSubActions`: `SAVES FOLDER ↗`, `+ COMMIT CHECKLIST`, `RELOAD TABLE`), `CLOSE` button | `role="toolbar"` `aria-label="Viewport Sub-Actions Toolbar"` `aria-controls="adminViewport"` | Contextual to active viewport tool. Closing viewport (`CLOSE`) restores focus to Tier 2 action trigger. |
| **Tier 4: Sub-Header Filter Actions** | `.toolbar-bottom-row .scroll-filter-widget` | Category Filters (`ALL`, `PROJECTS`, `TOOLS`...), Status Filters (`ALL`, `Active`, `WIP`...) | `role="region"` `aria-label="Workspace Filter Controls"` | Horizontal scroll widget container. Tab cycles active filter links (`.filter-btn.active`). |
| **Tier 5: Additional Task List Toolbar** | `.additional-lists-toolbar` | `+ CREATE TASK LIST` button | `role="toolbar"` `aria-label="Additional Custom Task Lists Toolbar"` `aria-controls="additional-lists-container"` | Positioned at top of additional lists section on `?route=project`. Triggering list creation auto-focuses new list input. |

---

## 3. Structural Section Taxonomy

```
+-----------------------------------------------------------------------+
| 1. APP HEADER (.app-header)                                           |
+-----------------------------------------------------------------------+
| 2. MAIN TOOLBAR & NAVIGATION (.toolbar.main-toolbar)                  |
|    - Top Row: Tier 1 Navigation Toolbar Right-Side Tools              |
|    - Bottom Row: Tier 4 Sub-Header Filter Actions                     |
+-----------------------------------------------------------------------+
| 3. SUB-ACTIONS BAR (.sub-actions-bar) [Tier 2 Action Menu Toolbar]    |
+-----------------------------------------------------------------------+
| 4. VIEWPORT CARD (.viewport-card) [For Viewport-enabled Routes]       |
|   +-----------------------------------------------------------------+ |
|   | 4a. VIEWPORT HEADER & SUB-ACTIONS TOOLBAR [Tier 3 Sub-Actions]  | |
|   +-----------------------------------------------------------------+ |
|   | 4b. VIEWPORT FILTER TOOLBAR (.viewport-filter-toolbar)          | |
|   +-----------------------------------------------------------------+ |
|   | 4c. VIEWPORT CONTENT AREA (.viewport-box / #adminViewport)       | |
|   +-----------------------------------------------------------------+ |
+-----------------------------------------------------------------------+
| 5. CONTENT BODY (.content-body) [For Standard Matrix/Single Views]    |
|    - Primary Project Card (Main Task List: $p['tasks'])               |
|    - Additional Lists Toolbar Card (.additional-lists-toolbar-card)   |
|      * Tier 5 Additional Task List Toolbar (.additional-lists-toolbar)|
|    - Sibling Custom Task Lists Container (#additional-lists-container)|
|      * Custom List Cards (.custom-list-card: $p['custom_lists'])      |
+-----------------------------------------------------------------------+
| 6. APP FOOTER (.app-footer) [templates/footer-layout.php]             |
+-----------------------------------------------------------------------+
```

---

## 4. Route-Specific Mapping Matrix

### Route 1: Home Dashboard (`?route=home`)
- **App Header**: Global shell header displaying logo emoticon and application title.
- **Main Toolbar & Navigation**: Category Filters + Status Filters (Tier 4) + `ADMIN` button + `LESS TEXT / MORE TEXT` toggle + `SEARCH` prompt button + `BACKUP NOW` hotlink (Tier 1).
- **Content Body**: `.dashboard-grid` rendering project cards via `templates/project-card-layout.php`. Cards display ONLY main task list (`$p['tasks']`) with 5-item truncation.
- **App Footer**: Reusable partial `templates/footer-layout.php`.

### Route 2: Single Project View (`?route=project&title=ID`)
- **App Header**: Dynamic route subtitle hooked to active project title.
- **Main Toolbar & Navigation**: `< BACK TO DASHBOARD` + Category Tag + Project ID (Tier 1).
- **Sub-Actions Bar**: `.sub-actions-bar` containing `RENAME PROJECT`, `UPDATE STATUS`, and `[!] DELETE PROJECT` action buttons (Tier 2).
- **Content Body**: `.dashboard-single` rendering:
  1. Main project card with full-screen expanded scope textareas and main task list (`$p['tasks']`).
  2. Tier 5 Additional Task List Toolbar Card (`.additional-lists-toolbar-card`) with `.additional-lists-toolbar` and `+ CREATE TASK LIST` prompt button.
  3. Additional Sibling Task Lists container (`#additional-lists-container`) rendering `$p['custom_lists']` keyed by random 8-character hex hash IDs (`list_[hash]`), each in a `.custom-list-card` featuring list-level `[✕] DELETE LIST` action.
- **App Footer**: Reusable partial `templates/footer-layout.php`.

### Route 3: Admin Workspace (`?route=admin`)
- **App Header**: Dynamic route subtitle set to `Admin`.
- **Main Toolbar & Navigation**: `< BACK TO DASHBOARD` + `Admin Workspace` badge (Tier 1).
- **Sub-Actions Bar**: `.sub-actions-bar` containing `POOL PROJECTS TABLE`, `TAG MANAGER`, `BACKUP PROJECTS.JSON`, `RESTORE PROJECTS.JSON`, `CONFIG EDITOR`, and `DELETE ALL PROJECTS` (Tier 2).
- **Viewport Card**:
  - **Viewport Header Toolbar**: Title badge + dynamic sub-actions (`#adminSubActions`: `SAVES FOLDER ↗`, `+ COMMIT CHECKLIST`, `RELOAD TABLE`) + `CLOSE` button (Tier 3).
  - **Viewport Filter Toolbar**: `DISCOVERED PROJECTS TABLE` title + `SELECT ALL` + `SELECT OUTER` + `DESELECT ALL` + `Selected: N` badge.
  - **Viewport Content Area**: Dedicated `#adminViewport` box rendering interactive directory tables or administration cards.
- **App Footer**: Reusable partial `templates/footer-layout.php`.

---

## 5. Shared Component & Partial Templates

### A. Task List Component Partial (`templates/task-list-partial.php`)
Extracted reusable component partial included by `templates/project-card-layout.php` and `modules/project/index.php`:
1. **Component Interface Parameters**:
   - `$listId`: string (`'main'` or `list_hash_id`).
   - `$listTitle`: string (e.g. `'TASK LIST'` or custom list title).
   - `$tasks`: array of task objects.
   - `$isCustomList`: boolean controls visibility of `[✕] DELETE LIST` button.
   - `$p`: active project array.
   - `$shouldTruncate`: boolean controls Home 5-item truncation behavior.
2. **Component Features**:
   - Task Header: Label + dynamic `X / Y DONE` badge + optional `[✕] DELETE LIST` button (`$isCustomList = true`).
   - Add Task Form: Input field + `+` submit button retaining focus on submission.
   - Task Items: Interactive checkbox items with priority flags (`.task-priority-select`), neon flares (`.item-task-flare`), sub-task tree lists (`.task-sublist`), `SHIFT+ENTER` textareas, and row click focus delegation (`handleTaskRowClick` / `handleCustomTaskRowClick`).

### B. Project Card Partial (`templates/project-card-layout.php`)
Reused across `home` dashboard grid cards and single `project` view:
1. **Card Header (`.card-header`)**: Category tag badge, project title link, status pill, and tag flags (`PROJECTS`, `TOOLS`, `MIT`, `OPEN SOURCE`).
2. **Scope Section (`.scope-container`)**: `IN-SCOPE`, `ANTI-SCOPE`, and `EDGE RISKS` textareas.
3. **Main Task List Integration**: Includes `templates/task-list-partial.php` with `$listId = 'main'`.

### C. Reusable Footer Partial (`templates/footer-layout.php`)
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

# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\  
