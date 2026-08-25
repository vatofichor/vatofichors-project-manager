<?php
/**
 * Standalone Migration Script: Upgrade `projects.json` from v1.5.0 Schema to v1.5.5 Schema
 * 
 * Copyright (c) 2026:
 * vatofichor - Sebastian Mass     [>_<]
 * & Assisted By Gemini Antigravity /|\
 * 
 * ===========================================================================================
 * STANDALONE USER & DEVELOPER CONVERSION GUIDE
 * ===========================================================================================
 * 
 * PURPOSE:
 * This administrative script non-destructively upgrades any legacy `projects.json` data file
 * originating from vatofichors-project-manager v1.5.0 into the expanded v1.5.5 schema.
 * 
 * INDEPENDENT EXECUTION & RELEASE UPGRADES:
 * This script is 100% standalone and has zero dependencies on application frameworks, models,
 * or database drivers. Users upgrading to a new release should run this script to convert
 * their existing `projects.json` data file BEFORE dropping it into a new release directory.
 * 
 * MANUAL BACKUP RECOMMENDATION:
 * Before executing this script against your production `projects.json` file, ALWAYS make a
 * manual backup copy (e.g. `cp projects.json projects.json.v150.bkup`).
 * 
 * COMMAND LINE USAGE:
 *   php upgrade_projects_json_150_to_155.php [path_to_target_projects.json]
 * 
 * EXAMPLES:
 *   1. Upgrade default `projects.json` in the current working directory:
 *      php upgrade_projects_json_150_to_155.php
 * 
 *   2. Upgrade a specific legacy data file:
 *      php upgrade_projects_json_150_to_155.php /path/to/my/old_projects.json
 * 
 * NON-LOSSY SCHEMA NORMALIZATION RULES:
 * - Top-Level Settings: Ensures `$data['settings']['version']` is set to `"1.5.5"`.
 * - Project Objects: Ensures `$project['custom_lists']` is initialized as `{}` if missing.
 * - Task Objects: Normalizes missing task keys:
 *     - `priority`: set to `""` if missing.
 *     - `type`: set to `"task"` if missing.
 *     - `children`: set to `[]` if missing.
 * - Sub-Task Objects: Normalizes child sub-task elements under `children`.
 * - All existing titles, scope descriptions, task completion states, notes, timestamps, and
 *   custom tags are preserved 100% without modification or data loss.
 * ===========================================================================================
 */

// CLI Output Helper
function cliLog($message) {
    echo "[VPM Upgrade 1.5.0->1.5.5] " . $message . PHP_EOL;
}

// 1. Resolve Target File Path
$targetPath = $argv[1] ?? 'projects.json';

if (!file_exists($targetPath)) {
    cliLog("ERROR: Target file not found at: {$targetPath}");
    cliLog("Usage: php upgrade_projects_json_150_to_155.php [path_to_projects.json]");
    exit(1);
}

cliLog("Reading target JSON file: {$targetPath}...");
$rawContent = file_get_contents($targetPath);
if ($rawContent === false || trim($rawContent) === '') {
    cliLog("ERROR: Target file is empty or unreadable.");
    exit(1);
}

// 2. Decode JSON
$data = json_decode($rawContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    cliLog("ERROR: Invalid JSON in target file: " . json_last_error_msg());
    exit(1);
}

cliLog("Target file decoded successfully. Starting non-lossy schema normalization...");

// 3. Normalize Settings & Version
if (!isset($data['settings']) || !is_array($data['settings'])) {
    $data['settings'] = [];
}
$data['settings']['title'] = $data['settings']['title'] ?? 'Simplex Workspace Dashboard';
$data['settings']['theme'] = $data['settings']['theme'] ?? 'dark-retro';
$data['settings']['version'] = '1.5.5';
$data['last_updated'] = date('c');

$projectCount = 0;
$taskCount = 0;
$subtaskCount = 0;
$customListCount = 0;

// Helper to normalize task objects recursively
function normalizeTaskItem(&$task, &$taskCount, &$subtaskCount) {
    $taskCount++;
    if (!isset($task['priority'])) {
        $task['priority'] = '';
    }
    if (!isset($task['type'])) {
        $task['type'] = 'task';
    }
    if (!isset($task['notes'])) {
        $task['notes'] = '';
    }
    if (!isset($task['children']) || !is_array($task['children'])) {
        $task['children'] = [];
    } else {
        foreach ($task['children'] as &$sub) {
            $subtaskCount++;
            if (!isset($sub['notes'])) {
                $sub['notes'] = '';
            }
            if (!isset($sub['completed'])) {
                $sub['completed'] = false;
            }
        }
        unset($sub);
    }
}

// 4. Normalize Projects Array
if (isset($data['projects']) && is_array($data['projects'])) {
    foreach ($data['projects'] as &$project) {
        $projectCount++;

        // Ensure scope fields exist
        $project['in_scope'] = $project['in_scope'] ?? '';
        $project['anti_scope'] = $project['anti_scope'] ?? '';
        $project['edge_risks'] = $project['edge_risks'] ?? '';
        $project['tags'] = $project['tags'] ?? [];

        // Ensure custom_lists object exists
        if (!isset($project['custom_lists']) || !is_array($project['custom_lists'])) {
            $project['custom_lists'] = new stdClass();
        } else {
            foreach ($project['custom_lists'] as $listId => &$customList) {
                $customListCount++;
                if (isset($customList['tasks']) && is_array($customList['tasks'])) {
                    foreach ($customList['tasks'] as &$clTask) {
                        normalizeTaskItem($clTask, $taskCount, $subtaskCount);
                    }
                    unset($clTask);
                }
            }
            unset($customList);
        }

        // Normalize main task list
        if (isset($project['tasks']) && is_array($project['tasks'])) {
            foreach ($project['tasks'] as &$task) {
                normalizeTaskItem($task, $taskCount, $subtaskCount);
            }
            unset($task);
        } else {
            $project['tasks'] = [];
        }
    }
    unset($project);
}

// 5. Re-encode and Save JSON
$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($newJson === false) {
    cliLog("ERROR: Failed to re-encode JSON data: " . json_last_error_msg());
    exit(1);
}

if (file_put_contents($targetPath, $newJson) === false) {
    cliLog("ERROR: Failed to write upgraded JSON back to: {$targetPath}");
    exit(1);
}

cliLog("------------------------------------------------------------------");
cliLog("SUCCESS: Migration to v1.5.5 schema completed!");
cliLog("Stats:");
cliLog("  - Projects normalized:     {$projectCount}");
cliLog("  - Tasks normalized:        {$taskCount}");
cliLog("  - Subtasks checked:        {$subtaskCount}");
cliLog("  - Custom lists processed:  {$customListCount}");
cliLog("Target file saved: {$targetPath}");
cliLog("You can now safely place this upgraded projects.json into your 1.5.5 installation.");
cliLog("------------------------------------------------------------------");
exit(0);
