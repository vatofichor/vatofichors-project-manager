Timestamp: 2026-08-25T01:49:44Z

# Data Schema & Model Specification (`data_schema_and_model_spec.md`)

This specification defines the JSON data storage schema, immutable status hierarchy rules, persistence write mechanics, backup file specifications, client storage mechanics, and complete method contracts for `lib/ProjectsModel.php`.

---

## 1. Top-Level Data Schema (`projects.json`)

The primary data store is a flat JSON file located at `./projects.json`.

```json
{
  "last_updated": "2026-08-24T23:36:00+00:00",
  "settings": {
    "title": "Simplex Workspace Dashboard"
  },
  "projects": [
    {
      "id": "vatofichors-project-manager",
      "name": "vatofichors-project-manager",
      "path": "D:/Dev/vatofichors-project-manager",
      "category": "PROJECTS",
      "status": "Inactive",
      "tags": ["PROJECTS", "OPEN SOURCE", "GITHUB"],
      "in_scope": "Core workspace management and project tracking dashboard.",
      "anti_scope": "No database servers, heavy frameworks, or SPA single-page routing.",
      "edge_risks": "Host environments lacking native PHP 5.3+ (resolved via local portable PHP fallback).",
      "tasks": [
        {
          "id": "t_1724540400_123",
          "title": "Initial workspace setup and layout standardization",
          "completed": true,
          "created_at": "2026-08-24T23:00:00+00:00",
          "notes": "Verified layout CSS custom variables."
        }
      ]
    }
  ]
}
```

### A. Entity Field Specifications

#### 1. Top-Level Store
| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `last_updated` | `string` (ISO 8601) | Yes | Auto-stamped timestamp updated on every write operation. |
| `settings` | `object` | Yes | Workspace-level metadata settings object. |
| `projects` | `array<Project>` | Yes | Array of indexed project entities. |

#### 2. `Project` Entity
| Field | Type | Required | Default | Description |
| :--- | :--- | :---: | :---: | :--- |
| `id` | `string` | Yes | N/A | Unique identifier. Disambiguated via dirpath prefix if colliding (e.g. `parent_dir/project_name`). |
| `name` | `string` | Yes | N/A | Human-readable project title. |
| `path` | `string` | No | `""` | Absolute or relative filesystem root directory path. |
| `category` | `string` | Yes | `"project"` | Category slug (e.g. `"PROJECTS"`, `"TOOLS"`, `"LIBRARIES"`). |
| `status` | `string` | Yes | `"Inactive"` | Active status indicator. Initialized to immutable default `"Inactive"`. |
| `tags` | `array<string>` | No | `[]` | Uppercase tag flags (e.g. `["MIT", "OPEN SOURCE"]`). |
| `in_scope` | `string` | No | `""` | Text description of project objectives and boundaries. |
| `anti_scope` | `string` | No | `""` | Text description of excluded functionality. |
| `edge_risks` | `string` | No | `""` | Text description of potential architecture or operational risks. |
| `tasks` | `array<Task>` | No | `[]` | List of task items assigned to the project. |

#### 3. `Task` Entity
| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `id` | `string` | Yes | Unique task ID generated via `t_{timestamp}_{rand3}` (e.g. `t_1724540400_842`). |
| `title` | `string` | Yes | Task title string. |
| `completed` | `bool` | Yes | Completion state flag (`true` or `false`). |
| `created_at` | `string` (ISO 8601) | Yes | Timestamp of task creation. |
| `notes` | `string` | No | Optional developer notes or details. |

---

## 2. Immutable Status Rules & Normalization

To guarantee system stability and baseline filtering across all installations, status values follow a strict immutability hierarchy:

> [!IMPORTANT]
> **Immutable Status Hierarchy**:
> 1. **Index 0: `ALL`**: Reserved global wildcard status representing all projects.
> 2. **Index 1: `Inactive`**: Reserved system default status. All newly initialized or imported projects are assigned status `'Inactive'`.
> 3. **Configured Statuses**: Custom statuses specified in `config.json` (`['Active', 'WIP', 'Closed', 'Maintenance', 'Archived']`) are appended following the immutable defaults via `getSystemStatuses()`. Case-insensitive deduplication is automatically enforced.

---

## 3. Persistence Engine, Locking & Backup Mechanics

### A. Atomic Write Pattern
Data modification follows an atomic serialization pattern via `saveData($data)`:
```php
public function saveData($data) {
    $data['last_updated'] = date('c');
    return file_put_contents($this->jsonFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}
```

### B. Backup Storage (`./saves/`)
- **Location**: `./saves/` directory relative to application root.
- **Filename Convention**: `Ymd_His_projects.json` (e.g., `20260824_233600_projects.json`).
- **Security Sanitization**: Restore requests validate filenames using strict regex matching (`/^[a-zA-Z0-9_\-]+\.json$/i`) to prevent directory traversal attacks (`../`).

---

## 4. `ProjectsModel.php` Method Signatures & Contracts

```php
class ProjectsModel {
    public function __construct(?string $jsonFile = null)
    public function getData(): array
    public function saveData(array $data): bool
    public function getProjects(): array
    public function getProjectById(string $id): ?array
    public function toggleTask(string $projectId, string $taskId): array
    public function addTask(string $projectId, string $title, string $notes = ''): array
    public function deleteTask(string $projectId, string $taskId): array
    public function updateScope(string $projectId, array $fields): array
    public function addProject(string $name, string $category = 'project', string $inScope = ''): array
    public function deleteProject(string $projectId): array
    public function renameProject(string $projectId, string $newName): array
    public function commitProjects(array $projectsList): array
    public function deleteAllProjects(): array
    public function toggleProjectTag(string $projectId, string $tag, bool $enabled): array
    public function backupProjectsJson(): array
    public function listSaves(): array
    public function restoreProjectsJson(string $filename): array
}
```

---

## 5. Client State Persistence & Zero-Cookie Architecture

> [!NOTE]
> **Zero-Cookie Serverless Design**:
> The application uses **NO HTTP cookies** and **NO PHP session state**, making it completely stateless and serverless-friendly.

### `localStorage` Inventory
| Key | Values | Scope | Purpose |
| :--- | :---: | :---: | :--- |
| `dashboard_hide_textareas` | `'true'` \| `'false'` | Browser Client | Persists UI state for the `LESS TEXT` / `MORE TEXT` grid textarea toggle across page refreshes. |

---
# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\
