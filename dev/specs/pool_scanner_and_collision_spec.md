# Pool Scanner & Collision Disambiguation Specification (`pool_scanner_and_collision_spec.md`)

This specification defines the multi-level directory pool scanner architecture, path sanitization rules, ignore filters, metadata indexing, and the **Dirpath Root Traversal Collision Disambiguation Algorithm** for `vatofichors-project-manager`.

---

## 1. Pool Scanner Architecture (`PoolProjects.php`)

The pool scanner dynamically discovers project directories within host filesystem locations configured in `config.json`.

```
                        +--------------------------------+
                        |  Load & Parse config.json      |
                        +--------------------------------+
                                        |
                 +----------------------+----------------------+
                 | Defensive Windows Backslash Healing         |
                 | (replaces \ with / in unescaped JSON)       |
                 +---------------------------------------------+
                                        |
                        +--------------------------------+
                        | Resolve Target Roots & Rel Path|
                        | (Relative -> $this->devDir)    |
                        +--------------------------------+
                                        |
                        +--------------------------------+
                        |  Recursive Directory Scan      |
                        |  (Bounded by scan_depth)       |
                        +--------------------------------+
                                        |
                 +----------------------+----------------------+
                 | Ignore Filters                               |
                 | - Hidden Dirs (strpos == 0)                 |
                 | - Blacklisted (Exact & Wildcard match)      |
                 +---------------------------------------------+
                                        |
                        +--------------------------------+
                        | Index Project Flags            |
                        | (has_ltap, has_readme, workspace)|
                        +--------------------------------+
                                        |
                        +--------------------------------+
                        | Output Report Payload          |
                        | (projects-aggregates-report.json)|
                        +--------------------------------+
```

---

## 2. Path Sanitization & Resilience Rules

### A. Defensive Windows Backslash Auto-Healing
When parsing `config.json` containing unescaped Windows backslashes (e.g. `"target_root_dir": "D:\Dev\repos"`), standard `json_decode()` fails. `PoolProjects.php` executes a regex sanitizer before parsing:
```php
$sanitized = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"/', function ($m) {
    return str_replace('\\', '/', $m[0]);
}, $rawJson);
```

### B. Relative vs. Absolute Target Root Resolution
Target paths in `config.json` support both absolute paths (`"D:/Dev"`) and relative paths (`"./test_projects_repo"`):
1. If `is_dir($normalizedRoot)` is `true`, path is used directly.
2. If direct check fails, path is resolved relative to the application base directory `$this->devDir`.

---

## 3. Directory Traversal & Ignore Blacklist Engine

### A. Recursion Bounding
Recursion is strictly bounded by `scan_depth` (default: `3`). Traversal halts once `$currentDepth > $maxDepth`.

### B. Blacklist Engine (`isBlacklisted()`)
Directories are excluded if:
1. `ignore_hidden_dirs` is `true` and directory name starts with `.` (e.g. `.git`, `.idea`).
2. Directory name matches any pattern in `ignore_dirs` array (`node_modules`, `vendor`, `.bkup`, `.backup`, `dist`, `build`, `temp`, `.gemini`).
3. Supports wildcard matching (e.g. `test_*` matches `test_projects_repo`).

---

## 4. Metadata Detection Flags & Output Report Schema

Every discovered directory is indexed with contextual metadata flags:

```json
{
  "generated_at": "2026-08-24T23:43:00+00:00",
  "target_roots": ["D:/Dev"],
  "scan_depth": 3,
  "ignore_hidden_dirs": true,
  "ignore_dirs": ["node_modules", "vendor", ".git", ".bkup", "dist"],
  "total_discovered": 1,
  "projects": [
    {
      "id": "vatofichors-project-manager",
      "name": "vatofichors-project-manager",
      "path": "D:/Dev/vatofichors-project-manager",
      "target_root": "D:/Dev",
      "depth": 1,
      "parent_dir": "",
      "has_ltap": true,
      "has_readme": true,
      "has_workspace": true
    }
  ]
}
```

---

## 5. Dirpath Root Traversal Collision Disambiguation Algorithm

When importing discovered projects via `commitProjects($projectsList)` in `ProjectsModel.php`, duplicate project folder names across subdirectories are disambiguated deterministically:

> [!IMPORTANT]
> **4-Step Collision Resolution Pipeline**:
> 1. **Path Normalization**: Standardizes slashes to `/` and trims trailing slashes (`D:/Dev/repo1/app` ➔ `d:/dev/repo1/app`).
> 2. **Duplicate Path Suppression**: If normalized `path` already exists in `projects.json`, project is skipped (`skipped_count++`).
> 3. **Dirpath Root Traversal**: If `id` or `name` collides with an existing project, the parent directory name is prepended to form a composite slug:
>    - Original Name: `my-app`
>    - Parent Folder: `client-a`
>    - Disambiguated Candidate: `client-a/my-app`
> 4. **Numeric Fail-Safe Suffix**: If `client-a/my-app` still collides, an incremental numeric suffix is appended (`client-a/my-app_1`, `client-a/my-app_2`).

### Algorithm Flowchart
```
[ Incoming Project Item ]
          |
  (Path Exists?) ---- Yes ----> [ SKIP PROJECT ] (skipped_count++)
          |
         No
          |
  (ID/Name Collides?) -- No --> [ ADD DIRECTLY ]
          |
         Yes
          |
  Apply Dirpath Traversal:
  Candidate = [ParentFolder] / [Name]
          |
  (Candidate Still Collides?)
     |                   |
    No                  Yes
     |                   |
 [ ADD CANDIDATE ]    Append Numeric Suffix:
                      [Candidate] + '_' + [Counter]
                         |
                      [ ADD SUFFIXED CANDIDATE ]
```

### Commit Return Payload Structure
```json
{
  "success": true,
  "added_count": 2,
  "skipped_count": 1,
  "disambiguated_count": 1,
  "total_processed": 3
}
```

---
# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\
