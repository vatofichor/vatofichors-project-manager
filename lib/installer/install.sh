#!/usr/bin/env bash
# Copyright (c) 2026:
# vatofichor - Sebastian Mass     [>_<]
# & Assisted By Gemini Antigravity /|\

INSTALLER_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$( cd "$INSTALLER_DIR/../.." && pwd )"

# Prevent re-installation if active uninstaller already exists
if [ -f "$ROOT_DIR/uninstall.bat" ] || [ -f "$ROOT_DIR/uninstall.sh" ]; then
    echo "[ERROR] An active installation is already present (uninstall.bat or uninstall.sh found)."
    echo "You cannot run install again until uninstallation has been executed."
    exit 1
fi

echo "Installing vatofichors Project Manager..."

# Check host native PHP availability and version (PHP 5.3x, 5.5x, 5.6+)
NEED_LOCAL_PHP=1
if command -v php >/dev/null 2>&1; then
    if php -r "exit(version_compare(PHP_VERSION, '5.3.0', '>=') ? 0 : 1);" >/dev/null 2>&1; then
        NEED_LOCAL_PHP=0
    fi
fi

if [ "$NEED_LOCAL_PHP" -eq 0 ]; then
    echo "[INFO] Host native PHP version 5.3+ (5.3x/5.5x/5.6+) detected. Skipping local PHP unpack."
else
    echo "[INFO] Native PHP 5.3+ not detected. Unpacking local portable PHP package into lib/php..."
    mkdir -p "$ROOT_DIR/lib/php"
    if [ -f "$INSTALLER_DIR/php.zip" ]; then
        if command -v unzip >/dev/null 2>&1; then
            unzip -q -o "$INSTALLER_DIR/php.zip" -d "$ROOT_DIR/lib/php"
        elif command -v tar >/dev/null 2>&1; then
            tar -xf "$INSTALLER_DIR/php.zip" -C "$ROOT_DIR/lib/php"
        elif command -v python3 >/dev/null 2>&1; then
            python3 -m zipfile -e "$INSTALLER_DIR/php.zip" "$ROOT_DIR/lib/php"
        fi
    fi
fi

if [ -f "$INSTALLER_DIR/run-dashboard.bat" ]; then
    cp -f "$INSTALLER_DIR/run-dashboard.bat" "$ROOT_DIR/run-dashboard.bat"
fi
if [ -f "$INSTALLER_DIR/run-dashboard.sh" ]; then
    cp -f "$INSTALLER_DIR/run-dashboard.sh" "$ROOT_DIR/run-dashboard.sh"
    chmod +x "$ROOT_DIR/run-dashboard.sh"
fi

if [ ! -f "$ROOT_DIR/config.json" ] && [ -f "$INSTALLER_DIR/config.json" ]; then
    cp -f "$INSTALLER_DIR/config.json" "$ROOT_DIR/config.json"
fi

if [ ! -f "$ROOT_DIR/projects.json" ] && [ -f "$INSTALLER_DIR/projects.json" ]; then
    cp -f "$INSTALLER_DIR/projects.json" "$ROOT_DIR/projects.json"
fi

if [ ! -f "$ROOT_DIR/projects-aggregates-report.json" ] && [ -f "$INSTALLER_DIR/projects-aggregates-report.json" ]; then
    cp -f "$INSTALLER_DIR/projects-aggregates-report.json" "$ROOT_DIR/projects-aggregates-report.json"
fi

# Bring out uninstall.sh specifically for Linux/macOS installer
if [ -f "$INSTALLER_DIR/uninstall.sh" ]; then
    cp -f "$INSTALLER_DIR/uninstall.sh" "$ROOT_DIR/uninstall.sh"
    chmod +x "$ROOT_DIR/uninstall.sh"
fi

echo "[SUCCESS] Installation complete! Run './run-dashboard.sh' to start the server."
