@echo off
rem Copyright (c) 2026:
rem vatofichor - Sebastian Mass     [>_<]
rem & Assisted By Gemini Antigravity /|\

set "ROOT_DIR=%~dp0..\..\"
set "INSTALLER_DIR=%~dp0"

rem Prevent re-installation if active uninstaller already exists
if exist "%ROOT_DIR%uninstall.bat" (
    echo [ERROR] An active installation is already present: uninstall.bat found.
    echo You cannot run install again until uninstall.bat has been executed.
    exit /b 1
)
if exist "%ROOT_DIR%uninstall.sh" (
    echo [ERROR] An active installation is already present: uninstall.sh found.
    echo You cannot run install again until uninstall.sh has been executed.
    exit /b 1
)

echo Installing vatofichors Project Manager...

rem Check host native PHP availability and version (PHP 5.3x, 5.5x, 5.6+)
set "NEED_LOCAL_PHP=1"
where php >nul 2>nul
if %errorlevel% equ 0 php -r "exit(version_compare(PHP_VERSION, '5.3.0', '>=') ? 0 : 1);" >nul 2>nul
if %errorlevel% equ 0 set "NEED_LOCAL_PHP=0"

if "%NEED_LOCAL_PHP%"=="0" (
    echo [INFO] Host native PHP version 5.3+ detected. Skipping local PHP unpack.
) else (
    echo [INFO] Native PHP 5.3+ not detected. Unpacking local portable PHP package into lib\php...
    if not exist "%ROOT_DIR%lib\php" mkdir "%ROOT_DIR%lib\php"
    if exist "%INSTALLER_DIR%php.zip" (
        powershell -Command "Expand-Archive -Path '%INSTALLER_DIR%php.zip' -DestinationPath '%ROOT_DIR%lib\php' -Force" >nul 2>nul
    )
)

if exist "%INSTALLER_DIR%run-dashboard.bat" (
    copy /y "%INSTALLER_DIR%run-dashboard.bat" "%ROOT_DIR%run-dashboard.bat" >nul
)
if exist "%INSTALLER_DIR%run-dashboard.sh" (
    copy /y "%INSTALLER_DIR%run-dashboard.sh" "%ROOT_DIR%run-dashboard.sh" >nul
)

if not exist "%ROOT_DIR%config.json" (
    if exist "%INSTALLER_DIR%config.json" (
        copy /y "%INSTALLER_DIR%config.json" "%ROOT_DIR%config.json" >nul
    )
)

if not exist "%ROOT_DIR%projects.json" (
    if exist "%INSTALLER_DIR%projects.json" (
        copy /y "%INSTALLER_DIR%projects.json" "%ROOT_DIR%projects.json" >nul
    )
)

if not exist "%ROOT_DIR%projects-aggregates-report.json" (
    if exist "%INSTALLER_DIR%projects-aggregates-report.json" (
        copy /y "%INSTALLER_DIR%projects-aggregates-report.json" "%ROOT_DIR%projects-aggregates-report.json" >nul
    )
)

rem Bring out uninstall.bat specifically for Windows installer
if exist "%INSTALLER_DIR%uninstall.bat" (
    copy /y "%INSTALLER_DIR%uninstall.bat" "%ROOT_DIR%uninstall.bat" >nul
)

echo [SUCCESS] Installation complete! Run 'run-dashboard.bat' to start the server.
