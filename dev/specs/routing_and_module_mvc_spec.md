Timestamp: 2026-08-25T01:49:44Z

# Routing & Module MVC Architecture Specification (`routing_and_module_mvc_spec.md`)

This specification defines the architectural rules, entry point routing contracts, module sub-router dispatching, and API handling conventions for the `vatofichors-project-manager` web application.

---

## 1. Core Architectural Principles

> [!IMPORTANT]
> **Filesystem-Routed Module MVC ("The Vatofichor Way")**:
> 1. **No Monolithic Controllers**: Traditional monolithic controller classes handling all routes and views are strictly prohibited.
> 2. **Glorified Filesystem Simulator**: The root entry script (`index.php`) acts as a pure filesystem router and global layout shell wrapper. Route paths map directly to isolated module directories (`modules/[route]/index.php`).
> 3. **Strict Model Hygiene**: Models (`lib/ProjectsModel.php`) contain data access and business logic only. Models **NEVER** output or buffer HTML, CSS, or view copy.
> 4. **Isolated Module Execution**: Each module operates within its own directory scope (`modules/[route]/`), executing its own sub-router and partial rendering logic.

---

## 2. Root Entry Router Contract (`index.php`)

The root `index.php` is the single entry point for all HTTP GET and POST requests.

```
                              +-------------------------+
                              |      HTTP Request       |
                              +-------------------------+
                                           |
                    +----------------------+----------------------+
                    |                                             |
            [ Method = POST ]                             [ Method = GET ]
                    |                                             |
     +------------------------------+             +------------------------------+
     | Central POST API Dispatcher  |             |  Sanitize & Resolve Route    |
     | (php://input / $_POST)       |             |  $_GET['route'] -> $route    |
     +------------------------------+             +------------------------------+
                    |                                             |
     +------------------------------+             +------------------------------+
     | Execute Model / Pool Action  |             |  Render Layout Shell Header  |
     | Output JSON & exit           |             +------------------------------+
     +------------------------------+                             |
                                                  +------------------------------+
                                                  | Require Sub-Router:          |
                                                  | modules/$route/index.php     |
                                                  +------------------------------+
                                                                  |
                                                  +------------------------------+
                                                  | Render Layout Shell Footer   |
                                                  | (templates/footer-layout.php)|
                                                  +------------------------------+
```

### A. Initialization & System Immutable Statuses
- Instantiates `ProjectsModel` targeting `projects.json`.
- Loads and parses `config.json` (auto-creating fallback defaults if missing).
- Defines `getSystemStatuses($configStatuses)` helper function, guaranteeing that system immutable statuses (`ALL` @ index 0, `Inactive` @ index 1) are prepended ahead of any user-configured status list.

### B. Route Resolution Logic
```php
$rawRoute = $_GET['route'] ?? 'home';
$route = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawRoute) ?: 'home';
$modulePath = __DIR__ . "/modules/$route/index.php";

if (!file_exists($modulePath)) {
    $route = 'home';
    $modulePath = __DIR__ . "/modules/home/index.php";
}
```

---

## 3. Module Sub-Router Contract (`modules/[route]/index.php`)

Every route requires a corresponding directory under `modules/` containing an `index.php` sub-router script.

### A. Existing Module Registry
1. **`modules/home/index.php`**: Primary dashboard grid rendering active projects, filter controls, keyword search bar, and task management cards.
2. **`modules/project/index.php`**: Single project view rendering deep-dive scope editors (`In-Scope`, `Anti-Scope`, `Edge Risks`), task lists, status updater, rename tool, and deletion confirm tool.
3. **`modules/admin/index.php`**: Administration workspace rendering Pool Scanner project discovery, interactive Tag Manager matrix, JSON backup creator, restore tool, and global purge tool.

### B. Shared Execution Scope
When `index.php` includes `modules/$route/index.php`, the sub-router inherits the following global scope variables:

| Variable | Type | Description |
| :--- | :--- | :--- |
| `$model` | `ProjectsModel` | Instantiated data access layer object. |
| `$config` | `array` | Decoded configuration array from `config.json`. |
| `$route` | `string` | Resolved active route slug (e.g. `'home'`, `'project'`, `'admin'`). |
| `$routeTitle` | `string` | Resolved page/header title. |
| `$projects` | `array` | List of all loaded project records from `projects.json`. |
| `$status_list` | `array` | System status list output by `getSystemStatuses()`. |
| `$categories_list` | `array` | Category list from `config.json`. |

---

## 4. Central POST API Dispatcher Contract

All asynchronous client operations execute via `POST` requests to `index.php`. Responses are returned strictly as `application/json`.

### A. API Request Format
Client payloads may be transmitted as standard `application/x-www-form-urlencoded` or JSON payload strings (`php://input`):
```json
{
  "action": "action_name",
  "param1": "value1",
  "param2": "value2"
}
```

### B. Registered API Actions

| Action Slug | Input Parameters | Return Payload | Model Method |
| :--- | :--- | :--- | :--- |
| `add_task` | `project_id`, `title`, `notes` | `{success: bool, task: array}` | `$model->addTask()` |
| `toggle_task` | `project_id`, `task_id` | `{success: bool, completed: bool}` | `$model->toggleTask()` |
| `delete_task` | `project_id`, `task_id` | `{success: bool}` | `$model->deleteTask()` |
| `update_scope` | `project_id`, `in_scope`, `anti_scope`, `edge_risks` | `{success: bool}` | `$model->updateScope()` |
| `add_project` | `name`, `category`, `in_scope` | `{success: bool, project: array}` | `$model->addProject()` |
| `delete_project` | `project_id` | `{success: bool}` | `$model->deleteProject()` |
| `rename_project` | `project_id`, `new_name` | `{success: bool, new_id: string}` | `$model->renameProject()` |
| `commit_projects` | `projects` (array) | `{success: bool, added: int}` | `$model->commitProjects()` |
| `delete_all_projects` | None | `{success: bool}` | `$model->deleteAllProjects()` |
| `toggle_project_tag` | `project_id`, `tag`, `enabled` | `{success: bool}` | `$model->toggleProjectTag()` |
| `backup_projects_json` | None | `{success: bool, filename: string}` | `$model->backupProjectsJson()` |
| `list_saves` | None | `{success: bool, saves: array}` | `$model->listSaves()` |
| `restore_projects_json` | `filename` | `{success: bool}` | `$model->restoreProjectsJson()` |
| `scan_pool_projects` | None | `{success: bool, report: array}` | `PoolProjects->run()` |

---

## 5. Developer Guide: Extending the System

### Adding a New Route/Module (e.g. `?route=analytics`)

1. **Create Module Sub-Router**:
   Create `modules/analytics/index.php`:
   ```php
   <?php
   /**
    * Analytics Module Sub-Router
    * Location: modules/analytics/index.php
    */
   ?>
   <div class="analytics-view">
       <h2>Analytics & Project Health</h2>
       <p>Total Projects: <?php echo count($projects); ?></p>
   </div>
   ```

2. **Add Navigation Link**:
   In `index.php` (within `.app-header .app-nav`):
   ```html
   <a href="index.php?route=analytics" class="nav-btn <?php echo $route === 'analytics' ? 'active' : ''; ?>">ANALYTICS</a>
   ```

3. **Register Custom POST Action (Optional)**:
   In `index.php` (inside `if ($_SERVER['REQUEST_METHOD'] === 'POST')` dispatcher):
   ```php
   if ($action === 'custom_action') {
       echo json_encode(['success' => true, 'message' => 'Custom action executed']);
       exit;
   }
   ```

---
# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\
