<?php
/**
 * Root Filesystem Router & Layout Shell
 * Vatofichor Filesystem-Routed Module MVC Architecture
 * 
 * Copyright (c) 2026:
 * vatofichor - Sebastian Mass     [>_<]
 * & Assisted By Gemini Antigravity /|\
 */

require_once __DIR__ . '/lib/ProjectsModel.php';

$jsonFile = __DIR__ . '/projects.json';
$configFile = __DIR__ . '/config.json';

// Ensure config.json exists
if (!file_exists($configFile)) {
    file_put_contents($configFile, json_encode([
        'app_name' => 'PROJECT MANAGER',
        'title' => 'Dev',
        'subtitle' => 'vatofichors dev repo',
        'logo_emoticon' => '[>_<]',
        'logo_color' => '#00f0ff',
        'border_color' => '#2b303c',
        'accent_theme' => '#39c5bb',
        'theme_hover' => '#4ae0d5',
        'categories' => ['ALL', 'PROJECTS', 'APPS', 'TOOLS', 'LIBRARIES', 'PACKAGES', 'TEMPLATES', 'META', 'OPEN SOURCE', 'PROPRIETARY'],
        'statuses' => ['ALL', 'Active', 'WIP', 'Inactive', 'Closed', 'Maintenance', 'Archived'],
        'version' => '1.5.0',
        'target_root_dir' => ['D:/Dev'],
        'ignore_hidden_dirs' => true
    ], JSON_PRETTY_PRINT));
}
$config = json_decode(file_get_contents($configFile), true) ?: [];

function getSystemStatuses($configStatuses = []) {
    $immutables = ['ALL', 'Inactive'];
    $merged = $immutables;
    foreach ((array)$configStatuses as $st) {
        $stClean = trim($st);
        if ($stClean === '') continue;
        $exists = false;
        foreach ($merged as $m) {
            if (strcasecmp($m, $stClean) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $merged[] = $stClean;
        }
    }
    return $merged;
}

// Initialize Model
$model = new ProjectsModel($jsonFile);

// Handle API POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? '';

    if ($action === 'toggle_task') {
        echo json_encode($model->toggleTask($input['project_id'] ?? '', $input['task_id'] ?? ''));
        exit;
    }
    if ($action === 'add_task') {
        echo json_encode($model->addTask($input['project_id'] ?? '', $input['title'] ?? '', $input['notes'] ?? ''));
        exit;
    }
    if ($action === 'delete_task') {
        echo json_encode($model->deleteTask($input['project_id'] ?? '', $input['task_id'] ?? ''));
        exit;
    }
    if ($action === 'update_scope') {
        echo json_encode($model->updateScope($input['project_id'] ?? '', $input));
        exit;
    }
    if ($action === 'add_project') {
        echo json_encode($model->addProject($input['name'] ?? '', $input['category'] ?? 'project', $input['in_scope'] ?? ''));
        exit;
    }
    if ($action === 'delete_project') {
        echo json_encode($model->deleteProject($input['project_id'] ?? ''));
        exit;
    }
    if ($action === 'rename_project') {
        echo json_encode($model->renameProject($input['project_id'] ?? '', $input['new_name'] ?? ''));
        exit;
    }
    if ($action === 'commit_projects') {
        echo json_encode($model->commitProjects($input['projects'] ?? []));
        exit;
    }
    if ($action === 'delete_all_projects') {
        echo json_encode($model->deleteAllProjects());
        exit;
    }
    if ($action === 'toggle_project_tag') {
        echo json_encode($model->toggleProjectTag($input['project_id'] ?? '', $input['tag'] ?? '', (bool)($input['enabled'] ?? false)));
        exit;
    }
    if ($action === 'backup_projects_json') {
        echo json_encode($model->backupProjectsJson());
        exit;
    }
    if ($action === 'list_saves') {
        echo json_encode($model->listSaves());
        exit;
    }
    if ($action === 'restore_projects_json') {
        echo json_encode($model->restoreProjectsJson($input['filename'] ?? ''));
        exit;
    }
    if ($action === 'scan_pool_projects') {
        require_once __DIR__ . '/lib/PoolProjects.php';
        $pool = new PoolProjects(__DIR__);
        $report = $pool->run();
        echo json_encode(['success' => true, 'report' => $report]);
        exit;
    }
    if ($action === 'get_config') {
        $configData = json_decode(@file_get_contents($configFile), true) ?: [];
        echo json_encode(['success' => true, 'config' => $configData]);
        exit;
    }
    if ($action === 'update_config') {
        $rawCfg = $input['config'] ?? [];
        if (!is_array($rawCfg)) {
            echo json_encode(['success' => false, 'error' => 'Invalid configuration data.']);
            exit;
        }
        $cleanCfg = [
            'app_name' => (string)($rawCfg['app_name'] ?? 'PROJECT MANAGER'),
            'title' => (string)($rawCfg['title'] ?? 'Dev'),
            'subtitle' => (string)($rawCfg['subtitle'] ?? 'vatofichors dev repo'),
            'logo_emoticon' => (string)($rawCfg['logo_emoticon'] ?? '[>_<]'),
            'logo_color' => (string)($rawCfg['logo_color'] ?? '#00f0ff'),
            'border_color' => (string)($rawCfg['border_color'] ?? '#2b303c'),
            'accent_theme' => (string)($rawCfg['accent_theme'] ?? '#39c5bb'),
            'theme_hover' => (string)($rawCfg['theme_hover'] ?? '#4ae0d5'),
            'version' => (string)($rawCfg['version'] ?? '1.5.0'),
            'theme' => (string)($rawCfg['theme'] ?? 'dark-retro'),
            'target_root_dir' => array_values(array_filter(array_map('trim', (array)($rawCfg['target_root_dir'] ?? [])))),
            'scan_depth' => max(1, (int)($rawCfg['scan_depth'] ?? 3)),
            'ignore_hidden_dirs' => (bool)($rawCfg['ignore_hidden_dirs'] ?? true),
            'categories' => array_values(array_filter(array_map('trim', (array)($rawCfg['categories'] ?? [])))),
            'statuses' => array_values(array_filter(array_map('trim', (array)($rawCfg['statuses'] ?? [])))),
            'ignore_dirs' => array_values(array_filter(array_map('trim', (array)($rawCfg['ignore_dirs'] ?? []))))
        ];
        $saved = file_put_contents($configFile, json_encode($cleanCfg, JSON_PRETTY_PRINT));
        if ($saved !== false) {
            echo json_encode(['success' => true, 'message' => 'Configuration updated successfully.', 'config' => $cleanCfg]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to write config.json']);
        }
        exit;
    }
    if ($action === 'reset_config') {
        $defaultConfigFile = __DIR__ . '/lib/installer/config.json';
        if (!file_exists($defaultConfigFile)) {
            echo json_encode(['success' => false, 'error' => 'Default installer config not found.']);
            exit;
        }
        $defaultContent = file_get_contents($defaultConfigFile);
        $copied = file_put_contents($configFile, $defaultContent);
        if ($copied !== false) {
            $defaultCfg = json_decode($defaultContent, true) ?: [];
            echo json_encode(['success' => true, 'message' => 'Configuration reset to installer defaults.', 'config' => $defaultCfg]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to reset config.json']);
        }
        exit;
    }
}

// Filesystem Route Dispatcher
$rawRoute = $_GET['route'] ?? 'home';
$route = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawRoute) ?: 'home';
$modulePath = __DIR__ . "/modules/$route/index.php";

if (!file_exists($modulePath)) {
    $route = 'home';
    $modulePath = __DIR__ . "/modules/home/index.php";
}

// Dynamic Title Resolution
$selectedProject = null;
if ($route === 'project') {
    $selectedProject = $model->getProjectById($_GET['title'] ?? '');
}

if ($selectedProject) {
    $routeTitle = $selectedProject['name'];
} elseif ($route === 'admin') {
    $routeTitle = 'Admin';
} elseif ($route === 'home') {
    $routeTitle = $config['title'] ?? 'Dev';
} else {
    $routeTitle = ucfirst($route);
}

$dbData = $model->getData();
$projects = $dbData['projects'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PROJECT MANAGER by vatofichor</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --border-color-config: <?php echo htmlspecialchars($config['border_color'] ?? '#2b303c'); ?>;
            --accent-theme: <?php echo htmlspecialchars($config['accent_theme'] ?? '#39c5bb'); ?>;
            --theme-hover: <?php echo htmlspecialchars($config['theme_hover'] ?? '#4ae0d5'); ?>;
        }
    </style>
    <?php if ($route === 'admin'): ?>
        <script src="assets/js/admin.js" defer></script>
    <?php elseif ($route === 'project'): ?>
        <script src="assets/js/project.js" defer></script>
    <?php endif; ?>
</head>
<body>
    <header class="app-header">
        <div class="header-title">
            <span class="logo-icon" style="color: <?php echo htmlspecialchars($config['logo_color'] ?? '#00f0ff'); ?>;"><?php echo htmlspecialchars($config['logo_emoticon'] ?? '[>_<]'); ?></span>
            <h1><?php echo htmlspecialchars($config['app_name'] ?? 'PROJECT MANAGER'); ?></h1>
            <span class="subtitle"><?php echo htmlspecialchars($config['subtitle'] ?? 'vatofichors dev repo'); ?> | <?php echo htmlspecialchars($routeTitle); ?></span>
        </div>
        <div class="header-meta">
            <span class="meta-item">Updated: <?php echo date('Y-m-d H:i', strtotime($dbData['last_updated'] ?? 'now')); ?></span>
            <span class="meta-item">Total Projects: <?php echo count($projects); ?></span>
        </div>
    </header>

    <!-- Require Filesystem-Routed Module View -->
    <?php require $modulePath; ?>

    <!-- New Project Modal -->
    <div id="addProjectModal" class="modal-overlay" style="display: none;">
        <div class="modal-card">
            <h3>Add New Project Entry</h3>
            <form onsubmit="addProject(event)">
                <label>Project Directory / ID Name:</label>
                <input type="text" id="new_proj_name" required placeholder="e.g. MyNewProject">
                <label>Category:</label>
                <select id="new_proj_cat">
                    <option value="project">Project</option>
                    <option value="tool">Tool</option>
                    <option value="meta">Meta</option>
                </select>
                <label>Core Objective (In-Scope):</label>
                <textarea id="new_proj_in_scope" rows="2" placeholder="Brief objective..."></textarea>
                <div class="modal-buttons">
                    <button type="submit" class="btn-save">CREATE ENTRY</button>
                    <button type="button" onclick="toggleAddProjectModal()" class="btn-cancel">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reusable Footer Partial -->
    <?php require __DIR__ . '/templates/footer-layout.php'; ?>

    <script>
        async function apiCall(data) {
            const res = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await res.json();
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function updateTaskCountBadge(projId, elem) {
            const card = elem ? (elem.classList.contains('project-card') ? elem : elem.closest('.project-card')) : document.getElementById('proj-' + projId);
            if (!card) return;
            const countSpan = card.querySelector('.task-count');
            const ul = card.querySelector('.task-list');
            if (countSpan && ul) {
                const total = ul.querySelectorAll('.task-item').length;
                const completed = ul.querySelectorAll('.task-item.completed').length;
                countSpan.textContent = `${completed} / ${total} DONE`;
            }
        }

        async function toggleTask(projId, taskId) {
            const res = await apiCall({ action: 'toggle_task', project_id: projId, task_id: taskId });
            if (res.success) {
                const li = document.getElementById('task-' + taskId);
                if (li) {
                    li.classList.toggle('completed', res.completed);
                    updateTaskCountBadge(projId, li);
                }
            }
        }

        async function addTask(e, projId) {
            e.preventDefault();
            const form = e.target;
            const input = form.querySelector('input[name="task_title"]');
            const submitBtn = form.querySelector('.add-task-btn') || form.querySelector('button[type="submit"]');
            const activeEl = document.activeElement;
            const title = input.value.trim();
            if (!title) return;

            const res = await apiCall({ action: 'add_task', project_id: projId, title: title });
            if (res && res.success && res.task) {
                const t = res.task;
                const card = document.getElementById('proj-' + projId) || form.closest('.project-card');
                if (card) {
                    const ul = card.querySelector('.task-list');
                    if (ul) {
                        const li = document.createElement('li');
                        li.className = 'task-item';
                        li.id = 'task-' + t.id;

                        let notesHtml = '';
                        if (t.notes) {
                            notesHtml = ` <span class="task-notes" title="${escapeHtml(t.notes)}">(note)</span>`;
                        }

                        li.innerHTML = `
                            <input type="checkbox" onchange="toggleTask('${escapeHtml(projId)}', '${escapeHtml(t.id)}')">
                            <span class="task-title">${escapeHtml(t.title)}</span>${notesHtml}
                            <button onclick="deleteTask('${escapeHtml(projId)}', '${escapeHtml(t.id)}')" class="task-del-btn" title="Delete task">&times;</button>
                        `;
                        ul.appendChild(li);
                    }
                    updateTaskCountBadge(projId, card);
                }

                input.value = '';

                // Restore focus to whichever control was last activated (+ button or text input)
                if (activeEl === submitBtn) {
                    submitBtn.focus();
                } else if (submitBtn && submitBtn.matches(':focus')) {
                    submitBtn.focus();
                } else if (input) {
                    input.focus();
                }
            }
        }

        async function deleteTask(projId, taskId) {
            if (!confirm('Delete task?')) return;
            const res = await apiCall({ action: 'delete_task', project_id: projId, task_id: taskId });
            if (res.success) {
                const li = document.getElementById('task-' + taskId);
                if (li) {
                    const card = li.closest('.project-card');
                    li.remove();
                    if (card) updateTaskCountBadge(projId, card);
                }
            }
        }

        async function updateProjectField(projId, field, value) {
            const payload = { action: 'update_scope', project_id: projId };
            payload[field] = value;
            await apiCall(payload);
        }

        function toggleAddProjectModal() {
            const modal = document.getElementById('addProjectModal');
            modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
        }

        async function addProject(e) {
            e.preventDefault();
            const name = document.getElementById('new_proj_name').value;
            const category = document.getElementById('new_proj_cat').value;
            const in_scope = document.getElementById('new_proj_in_scope').value;
            const res = await apiCall({ action: 'add_project', name, category, in_scope });
            if (res.success) {
                location.reload();
            }
        }

        window.dashboardSearchQuery = '';

        function promptDashboardSearch() {
            const currentQuery = window.dashboardSearchQuery || '';
            const query = prompt("Filter dashboard projects by keyword:\n(Leave empty to clear active search)", currentQuery);
            if (query === null) return;
            applyDashboardCardSearch(query.trim());
        }

        function applyDashboardCardSearch(query) {
            window.dashboardSearchQuery = query;
            const btn = document.getElementById('searchProjectsBtn');
            const cards = document.querySelectorAll('.dashboard-grid .project-card');
            const queryLower = query.toLowerCase();

            if (btn) {
                if (query) {
                    btn.textContent = `SEARCH ("${query}")`;
                    btn.classList.add('active');
                } else {
                    btn.textContent = 'SEARCH';
                    btn.classList.remove('active');
                }
            }

            cards.forEach(card => {
                if (!query) {
                    card.style.display = '';
                } else {
                    const text = card.innerText.toLowerCase();
                    if (text.includes(queryLower)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }

        function toggleDashboardTextareas() {
            document.body.classList.toggle('hide-textareas');
            const isHidden = document.body.classList.contains('hide-textareas');
            const btn = document.getElementById('toggleTextareasBtn');
            if (btn) {
                btn.textContent = isHidden ? 'MORE TEXT' : 'LESS TEXT';
                btn.classList.toggle('active', isHidden);
            }
            try {
                localStorage.setItem('dashboard_hide_textareas', isHidden ? 'true' : 'false');
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            try {
                if (localStorage.getItem('dashboard_hide_textareas') === 'true') {
                    document.body.classList.add('hide-textareas');
                    const btn = document.getElementById('toggleTextareasBtn');
                    if (btn) {
                        btn.textContent = 'MORE TEXT';
                        btn.classList.add('active');
                    }
                }
            } catch (e) {}
        });
    </script>
</body>
</html>
