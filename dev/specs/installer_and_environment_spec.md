Timestamp: 2026-08-25T01:49:44Z

# Installer & Environment Specification (`installer_and_environment_spec.md`)

This specification defines the host environment requirements, portable PHP 5 fallback architecture, installation pipeline, launcher runtime hooking, uninstaller isolation boundaries, and `.gitattributes` line-ending rules for `vatofichors-project-manager`.

---

## 1. Host Environment Requirements & Version Matrix

The application is engineered to operate on any host OS (Windows 10/11, macOS, Linux) with zero pre-installed web servers or SQL database requirements.

### Supported PHP Version Matrix
- **Native Supported Versions**: `PHP 5.3.x`, `PHP 5.5.x`, `PHP 5.6.x`, `PHP 7.x`, `PHP 8.x`+
- **Evaluation Test**:
  ```bash
  php -r "exit(version_compare(PHP_VERSION, '5.3.0', '>=') ? 0 : 1);"
  ```
- **Fallback Trigger**: If host native `php` is missing or version < 5.3.0, installers unpack the bundled portable package template `lib/installer/php.zip` into `lib/php/`.

---

## 2. Installation Architecture (`install.bat` & `install.sh`)

```
                              +-------------------------+
                              |   Run install.bat/.sh   |
                              +-------------------------+
                                           |
                 +-------------------------+-------------------------+
                 |  Active Installation Guard                        |
                 |  (Does uninstall.bat / uninstall.sh exist?)       |
                 +-------------------------+-------------------------+
                                    |                     |
                                   Yes                   No
                                    |                     |
                            [ EXIT CODE 1 ]               |
                            (Abort Install)               |
                                                          v
                                           +-----------------------------+
                                           | Check Host Native PHP 5.3+  |
                                           +-----------------------------+
                                                    |            |
                                                  Found       Missing
                                                    |            |
                                                    v            v
                                             [ Skip Unpack ] [ Unpack lib/installer/php.zip -> lib/php ]
                                                    |            |
                                                    +-----+------+
                                                          |
                                                          v
                                           +-----------------------------+
                                           | Deploy Root Launchers       |
                                           | - run-dashboard.bat/.sh     |
                                           | - config.json / manifests   |
                                           | - OS-Matched uninstall script|
                                           +-----------------------------+
```

### A. Active Installation Protection Guard
To prevent state corruption or overwriting active deployments, both installers execute a pre-installation check:
- If `uninstall.bat` or `uninstall.sh` exists in the target root, the installer outputs:
  ```
  [ERROR] An active installation is already present: uninstall.bat found.
  You cannot run install again until uninstall.bat has been executed.
  ```
- Aborts immediately with exit code `1`.

### B. Portable Runtime Unpacking Strategy
- **Windows (`install.bat`)**: Uses PowerShell `Expand-Archive`:
  ```cmd
  powershell -Command "Expand-Archive -Path '%INSTALLER_DIR%php.zip' -DestinationPath '%ROOT_DIR%lib\php' -Force"
  ```
- **Unix / macOS (`install.sh`)**: Multi-tier extraction fallback chain:
  ```bash
  if command -v unzip >/dev/null 2>&1; then
      unzip -q -o "$INSTALLER_DIR/php.zip" -d "$SCRIPT_DIR/lib/php"
  elif command -v tar >/dev/null 2>&1; then
      tar -xf "$INSTALLER_DIR/php.zip" -C "$SCRIPT_DIR/lib/php"
  elif command -v python3 >/dev/null 2>&1; then
      python3 -m zipfile -e "$INSTALLER_DIR/php.zip" "$SCRIPT_DIR/lib/php"
  fi
  ```

### C. OS-Specific Uninstaller Deployment
- Running `install.bat` deploys **`uninstall.bat`** to root.
- Running `install.sh` deploys **`uninstall.sh`** to root.

---

## 3. Runtime Hooking & Dashboard Server (`run-dashboard.bat` & `run-dashboard.sh`)

The launcher scripts dynamically resolve the available PHP binary prior to booting the PHP Built-in Web Server:

### Resolution Sequence
1. Check `PATH` for native `php`. If `version_compare(PHP_VERSION, '5.3.0', '>=')` passes, set `$PHP_EXEC = "php"`.
2. If native PHP is unavailable, inspect local filesystem for unpacked binary (`lib/php/php.exe` on Windows; `lib/php/bin/php` or `lib/php/php` on Unix).
3. If no valid executable is found, display error prompt advising the user to run `install.bat`/`install.sh`.
4. Launch PHP Development Server:
   ```cmd
   %PHP_EXEC% -S 127.0.0.1:8080 -t "%ROOT_DIR%"
   ```

---

## 4. Uninstaller Safety Boundaries (`uninstall.bat` & `uninstall.sh`)

Uninstallers remove runtime and launcher assets while preserving user workspace data:

> [!IMPORTANT]
> **Data Preservation Contract**:
> `uninstall.bat` and `uninstall.sh` **NEVER** delete user JSON data or configuration files.

### Deletion Scope Matrix

| File / Folder Path | `uninstall.bat` | `uninstall.sh` | Purpose |
| :--- | :---: | :---: | :--- |
| `run-dashboard.bat` | **Deleted** | **Deleted** | Launcher script. |
| `run-dashboard.sh` | **Deleted** | **Deleted** | Launcher script. |
| `lib/php/` | **Deleted** | **Deleted** | Unpacked portable runtime directory. |
| `uninstall.bat` | **Self-Deleted** | Preserved | Windows uninstaller. |
| `uninstall.sh` | Preserved | **Self-Deleted** | Unix uninstaller. |
| `install.bat` | Preserved | Preserved | Root installer (stays in root by default). |
| `install.sh` | Preserved | Preserved | Root installer (stays in root by default). |
| `projects.json` | **STRICTLY PRESERVED** | **STRICTLY PRESERVED** | User project database. |
| `config.json` | **STRICTLY PRESERVED** | **STRICTLY PRESERVED** | User configuration. |
| `projects-aggregates-report.json` | **STRICTLY PRESERVED** | **STRICTLY PRESERVED** | Discovered pool report. |
| `./saves/` | **STRICTLY PRESERVED** | **STRICTLY PRESERVED** | Database backups directory. |

---

## 5. Git Repository Line Ending Rules (`.gitattributes`)

To prevent line-ending corruption when cloning across platforms, [.gitattributes](file:///d:/Dev/vatofichors-project-manager/.gitattributes) enforces explicit line-ending conversions:

```gitattributes
# Ensure shell scripts always retain Unix LF line endings across all platforms
*.sh text eol=lf

# Batch scripts retain Windows CRLF line endings
*.bat text eol=crlf

# Markdown and JSON text formatting
*.md text eol=lf
*.json text eol=lf
```

---
# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\
