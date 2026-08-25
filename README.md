# vatofichors-project-manager

A portable, zero-dependency, environment-agnostic PHP project management dashboard designed for developer workspaces.

---

## What It Is

`vatofichors-project-manager` is a lightweight, self-hosted project tracking dashboard. It gives developers a high-density, retro-styled interface to discover, organize, and manage local project repositories across configured workspace folders without complex setup or heavy database dependencies.

---

## What It Does

- **Dynamic Workspace Aggregation**: Automatically scans and indexes multi-level target directory pools configured in `config.json`.
- **Project Scope & Risk Tracking**: Define and edit `in-scope`, `anti-scope`, and `edge-risks` for each project.
- **Real-Time Task Checklists**: Manage task checklists with dynamic DOM updates, focus retention, and status badges (`X / Y DONE`).
- **Tag & Status Management**: Categorize projects with dynamic tags (`PROJECTS`, `TOOLS`, `META`, `MIT`, `OPEN SOURCE`, `PROPRIETARY`) and status lifecycles.
- **Admin Workspace Suite**:
  - Multi-level directory pool scanner (`POOL PROJECTS TABLE`) with interactive import selection.
  - Interactive project tag management grid (`TAG MANAGER`).
  - One-click timestamped `projects.json` backups and restoration tools.
- **Fast Search & Visibility Controls**: Filter cards in real-time by search queries, category tags, or status, with a global textarea toggle (`LESS TEXT / MORE TEXT`).

---

Needs new screenshots..

---

## How to Run It

### Option 1: Automatic Installer (Recommended)
- **Windows**: Double-click or run `install.bat`
- **Linux / macOS**: Run `./install.sh`

The installer sets up launcher scripts (`run-dashboard.bat` / `run-dashboard.sh`) and automatically unpacks a portable PHP binary (`lib/php/`) if a host PHP 5.3+ installation is not detected.

### Option 2: Direct PHP CLI
Run PHP's built-in web server from the project root:

```bash
php -S localhost:8000
```

Open `http://localhost:8000` in your web browser.

---

## Where to Run It

- **Operating Systems**: Windows, Linux, macOS.
- **PHP Compatibility**: Vanilla PHP 5.3+ through PHP 8.x.
- **Database Requirement**: None (uses lightweight JSON files `config.json` and `projects.json`).
- **External Dependencies**: Zero third-party npm packages, SQL databases, or external frameworks.

---

## Technical Specifications & Architecture

For deep-dive architecture specs, refer directly to the localized specification sheets:

- 🛠️ **[Routing & Module MVC Spec](dev/specs/routing_and_module_mvc_spec.md)**  
  Filesystem-routed sub-router architecture (`modules/[route]/index.php`), route shell dispatching, and isolated view contracts.

- 💾 **[Data Schema & Model Spec](dev/specs/data_schema_and_model_spec.md)**  
  `ProjectsModel` data access layer, `projects.json` structure, immutable status resolvers (`ALL`, `Inactive`), and backup/restore protocols.

- 🔍 **[Pool Scanner & Collision Spec](dev/specs/pool_scanner_and_collision_spec.md)**  
  Multi-level directory scanner (`lib/PoolProjects.php`) and automated dirpath root traversal for project ID collision disambiguation.

- 🎨 **[Layout & Component Spec](dev/specs/layout_and_component_spec.md)**  
  Retro dark mode design system tokens (`:root`), in-flow UI controls, partial layout templates (`templates/project-card-layout.php`), and responsive breakpoints.

- 📦 **[Installer & Environment Spec](dev/specs/installer_and_environment_spec.md)**  
  Installer protection guards, data-preserving uninstallers (`uninstall.bat` / `uninstall.sh`), and portable PHP fallback unpacking.

---

> [!NOTE]  
> **Security Footnote for Developers**: This software is designed with the intention of being run locally using launcher scripts (`run-dashboard.bat` / `run-dashboard.sh`) on desktop Linux & Windows environments. If developers plan to host or expose this package publicly or on shared networks (`0.0.0.0`), additional security hardening, network access controls, reverse proxy authentication, and local command execution safeguards must be implemented.

---

## AI Model Integration & Data Management

`vatofichors-project-manager` is fundamentally built around managing and maintaining the structural integrity, completeness, and state quality of `projects.json`.

> [!TIP]
> **AI Agent & LLM Guidance**:
> Automated coding assistants and AI agents can be pointed directly to `README.md` and `projects.json` to immediately understand how to inspect, query, and manipulate a `vatofichors-project-manager` workspace installation effectively against parameters configured in `config.json`.

---

## Version 1.5.5 Release Updates

- **Task Priority Levels & Neon Flares**: Retro priority selector (`-`, `1`, `2`, `3`, `*`, `!`, `$`) with neon flare badges and border color accents.
- **Live Prepend & Focus Auto-Advance**: Completing a task prepends it to index 0 in DOM and storage while auto-advancing focus to the next task.
- **Multi-Line Textareas & Key Handlers**: Auto-expanding task inputs supporting `SHIFT+ENTER` newlines and `ENTER` submission.
- **Nested Sub-Task Tree Hierarchy**: Child sub-task trees with dashed connectors, `+ sub` prompts, and dedicated API management.
- **Home Card Truncation & Top Toolbar Hotlinks**: 5-item max card truncation with `SEE MORE` expander and top toolbar `BACKUP NOW` hotlink.
- **Native OS File Manager Launcher**: Non-blocking native desktop file manager hotlink launcher (`SAVES FOLDER ↗`) for `./saves/`.
- **Multiple Sibling Custom Task Lists**: Uncapped additional project-specific custom task lists with individual `[✕] DELETE LIST` controls.
- **Tier 5 Additional Task List Toolbar**: Dedicated `.additional-lists-toolbar` on project route featuring `+ CREATE TASK LIST` action.
- **Task List Component Partial**: Extracted reusable partial `templates/task-list-partial.php` included across all task list views.

---

# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\  
