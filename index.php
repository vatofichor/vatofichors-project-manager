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
        'ignore_hidden_dirs' => true,
        'show_home_backup_btn' => true
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
        echo json_encode($model->addTask($input['project_id'] ?? '', $input['title'] ?? '', $input['notes'] ?? '', $input['priority'] ?? '', $input['type'] ?? 'task'));
        exit;
    }
    if ($action === 'delete_task') {
        echo json_encode($model->deleteTask($input['project_id'] ?? '', $input['task_id'] ?? ''));
        exit;
    }
    if ($action === 'update_task_priority') {
        echo json_encode($model->updateTaskPriority($input['project_id'] ?? '', $input['task_id'] ?? '', $input['priority'] ?? ''));
        exit;
    }
    if ($action === 'update_task_type') {
        echo json_encode($model->updateTaskType($input['project_id'] ?? '', $input['task_id'] ?? '', $input['type'] ?? 'task'));
        exit;
    }
    if ($action === 'add_sub_task') {
        echo json_encode($model->addSubTask($input['project_id'] ?? '', $input['parent_task_id'] ?? '', $input['title'] ?? '', $input['notes'] ?? ''));
        exit;
    }
    if ($action === 'toggle_sub_task') {
        echo json_encode($model->toggleSubTask($input['project_id'] ?? '', $input['parent_task_id'] ?? '', $input['sub_task_id'] ?? ''));
        exit;
    }
    if ($action === 'delete_sub_task') {
        echo json_encode($model->deleteSubTask($input['project_id'] ?? '', $input['parent_task_id'] ?? '', $input['sub_task_id'] ?? ''));
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
            'show_home_backup_btn' => (bool)($rawCfg['show_home_backup_btn'] ?? true),
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
    if ($action === 'launch_saves_explorer') {
        $res = $model->launchSavesExplorer();
        echo json_encode($res);
        exit;
    }
    if ($action === 'add_custom_task_list') {
        $projId = $input['project_id'] ?? '';
        $title = $input['title'] ?? '';
        $res = $model->addCustomTaskList($projId, $title);
        echo json_encode($res);
        exit;
    }
    if ($action === 'delete_custom_task_list') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $res = $model->deleteCustomTaskList($projId, $listId);
        echo json_encode($res);
        exit;
    }
    if ($action === 'add_custom_list_task') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $title = $input['title'] ?? '';
        $res = $model->addCustomListTask($projId, $listId, $title);
        echo json_encode($res);
        exit;
    }
    if ($action === 'toggle_custom_list_task') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $taskId = $input['task_id'] ?? '';
        $res = $model->toggleCustomListTask($projId, $listId, $taskId);
        echo json_encode($res);
        exit;
    }
    if ($action === 'delete_custom_list_task') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $taskId = $input['task_id'] ?? '';
        $res = $model->deleteCustomListTask($projId, $listId, $taskId);
        echo json_encode($res);
        exit;
    }
    if ($action === 'update_custom_list_task_priority') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $taskId = $input['task_id'] ?? '';
        $priority = $input['priority'] ?? '-';
        $res = $model->updateCustomListTaskPriority($projId, $listId, $taskId, $priority);
        echo json_encode($res);
        exit;
    }
    if ($action === 'add_custom_sub_task') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $parentTaskId = $input['parent_task_id'] ?? '';
        $title = $input['title'] ?? '';
        $res = $model->addCustomSubTask($projId, $listId, $parentTaskId, $title);
        echo json_encode($res);
        exit;
    }
    if ($action === 'toggle_custom_sub_task') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $parentTaskId = $input['parent_task_id'] ?? '';
        $subTaskId = $input['sub_task_id'] ?? '';
        $res = $model->toggleCustomSubTask($projId, $listId, $parentTaskId, $subTaskId);
        echo json_encode($res);
        exit;
    }
    if ($action === 'delete_custom_sub_task') {
        $projId = $input['project_id'] ?? '';
        $listId = $input['list_id'] ?? '';
        $parentTaskId = $input['parent_task_id'] ?? '';
        $subTaskId = $input['sub_task_id'] ?? '';
        $res = $model->deleteCustomSubTask($projId, $listId, $parentTaskId, $subTaskId);
        echo json_encode($res);
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

        function handleTaskRowClick(e, projId, taskId) {
            const tag = e.target.tagName.toLowerCase();
            if (tag === 'input' || tag === 'select' || tag === 'button' || tag === 'a' || e.target.closest('.subtask-item') || e.target.closest('.add-subtask-btn')) {
                return;
            }
            const li = document.getElementById('task-' + taskId);
            if (li) {
                const cb = li.querySelector('input[type="checkbox"]');
                if (cb) {
                    cb.focus();
                }
            }
        }

        async function toggleTask(projId, taskId) {
            const res = await apiCall({ action: 'toggle_task', project_id: projId, task_id: taskId });
            if (res.success) {
                const li = document.getElementById('task-' + taskId);
                if (li) {
                    li.classList.toggle('completed', res.completed);
                    const cb = li.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = res.completed;
                    
                    const ul = li.closest('.task-list');
                    if (res.completed && ul) {
                        ul.prepend(li);
                    }
                    
                    updateTaskCountBadge(projId, li);

                    // Auto-advance focus to next unchecked task checkbox when checked completed
                    if (res.completed && ul) {
                        const nextUnchecked = ul.querySelector('.task-item:not(.completed) input[type="checkbox"]');
                        if (nextUnchecked) {
                            nextUnchecked.focus();
                        }
                    }
                }
            }
        }

        async function addTask(e, projId) {
            e.preventDefault();
            const form = e.target;
            const input = form.querySelector('[name="task_title"]');
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
                        li.setAttribute('onclick', `handleTaskRowClick(event, '${escapeHtml(projId)}', '${escapeHtml(t.id)}')`);

                        const pVal = t.priority || '';
                        const pClass = pVal ? 'priority-' + (pVal === '*' ? 'star' : (pVal === '!' ? 'excl' : (pVal === '$' ? 'dollar' : pVal))) : '';
                        if (pClass) li.classList.add(pClass);

                        let notesHtml = '';
                        if (t.notes) {
                            notesHtml = ` <span class="task-notes" title="${escapeHtml(t.notes)}">(note)</span>`;
                        }

                        li.innerHTML = `
                            <input type="checkbox" onchange="toggleTask('${escapeHtml(projId)}', '${escapeHtml(t.id)}')">
                            <span class="item-task-flare" style="${pVal ? '' : 'display:none;'}">${escapeHtml(pVal)}</span>
                            <span class="task-title">${escapeHtml(t.title)}</span>${notesHtml}
                            <button type="button" onclick="promptAddSubTask(event, '${escapeHtml(projId)}', '${escapeHtml(t.id)}')" class="add-subtask-btn" title="Add sub-task">+ sub</button>
                            <select class="task-priority-select" onchange="updateTaskPriority('${escapeHtml(projId)}', '${escapeHtml(t.id)}', this.value)" title="Set priority level">
                                <option value="" ${pVal === '' ? 'selected' : ''}>-</option>
                                <option value="1" ${pVal === '1' ? 'selected' : ''}>1</option>
                                <option value="2" ${pVal === '2' ? 'selected' : ''}>2</option>
                                <option value="3" ${pVal === '3' ? 'selected' : ''}>3</option>
                                <option value="*" ${pVal === '*' ? 'selected' : ''}>*</option>
                                <option value="!" ${pVal === '!' ? 'selected' : ''}>!</option>
                                <option value="$" ${pVal === '$' ? 'selected' : ''}>$</option>
                            </select>
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

        async function updateTaskPriority(projId, taskId, priority) {
            const res = await apiCall({ action: 'update_task_priority', project_id: projId, task_id: taskId, priority: priority });
            if (res.success) {
                const li = document.getElementById('task-' + taskId);
                if (li) {
                    li.className = li.className.replace(/\bpriority-\S+/g, '').trim();
                    if (res.priority) {
                        const pClass = 'priority-' + (res.priority === '*' ? 'star' : (res.priority === '!' ? 'excl' : (res.priority === '$' ? 'dollar' : res.priority)));
                        li.classList.add(pClass);
                    }
                    const flare = li.querySelector('.item-task-flare');
                    if (flare) {
                        flare.textContent = res.priority || '';
                        flare.style.display = res.priority ? 'inline-block' : 'none';
                    }
                }
            }
        }

        async function updateTaskType(projId, taskId, type) {
            return await apiCall({ action: 'update_task_type', project_id: projId, task_id: taskId, type: type });
        }

        async function addSubTask(projId, parentTaskId, title) {
            return await apiCall({ action: 'add_sub_task', project_id: projId, parent_task_id: parentTaskId, title: title });
        }

        async function toggleSubTask(projId, parentTaskId, subTaskId) {
            const res = await apiCall({ action: 'toggle_sub_task', project_id: projId, parent_task_id: parentTaskId, sub_task_id: subTaskId });
            if (res.success) {
                const subLi = document.getElementById('subtask-' + subTaskId);
                if (subLi) subLi.classList.toggle('completed', res.completed);
            }
        }

        async function promptAddSubTask(e, projId, parentTaskId) {
            if (e && e.stopPropagation) e.stopPropagation();
            const title = prompt("Enter sub-task title:");
            if (!title || !title.trim()) return;
            const res = await addSubTask(projId, parentTaskId, title.trim());
            if (res && res.success && res.sub_task) {
                const st = res.sub_task;
                const parentLi = document.getElementById('task-' + parentTaskId);
                if (parentLi) {
                    let subUl = parentLi.querySelector('.task-sublist');
                    if (!subUl) {
                        subUl = document.createElement('ul');
                        subUl.className = 'task-sublist';
                        parentLi.appendChild(subUl);
                    }
                    const subLi = document.createElement('li');
                    subLi.className = 'subtask-item';
                    subLi.id = 'subtask-' + st.id;
                    subLi.innerHTML = `
                        <input type="checkbox" onchange="toggleSubTask('${escapeHtml(projId)}', '${escapeHtml(parentTaskId)}', '${escapeHtml(st.id)}')">
                        <span class="subtask-title">${escapeHtml(st.title)}</span>
                        <button type="button" onclick="deleteSubTask('${escapeHtml(projId)}', '${escapeHtml(parentTaskId)}', '${escapeHtml(st.id)}')" class="subtask-del-btn" title="Delete sub-task">&times;</button>
                    `;
                    subUl.appendChild(subLi);
                }
            }
        }

        async function deleteSubTask(projId, parentTaskId, subTaskId) {
            if (!confirm('Delete sub-task?')) return;
            const res = await apiCall({ action: 'delete_sub_task', project_id: projId, parent_task_id: parentTaskId, sub_task_id: subTaskId });
            if (res.success) {
                const subLi = document.getElementById('subtask-' + subTaskId);
                if (subLi) subLi.remove();
            }
        }

        async function promptCreateCustomTaskList(e, projId) {
            if (e && e.stopPropagation) e.stopPropagation();
            const title = prompt("Enter title for new task list:");
            if (!title || !title.trim()) return;

            const res = await apiCall({ action: 'add_custom_task_list', project_id: projId, title: title.trim() });
            if (res && res.success && res.list) {
                location.reload();
            } else {
                alert("Could not create task list: " + (res.error || 'Unknown error'));
            }
        }

        async function deleteCustomTaskList(projId, listId) {
            if (!confirm("Are you sure you want to delete this custom task list and all its tasks?")) return;
            const res = await apiCall({ action: 'delete_custom_task_list', project_id: projId, list_id: listId });
            if (res && res.success) {
                const card = document.getElementById('custom-list-card-' + listId);
                if (card) card.remove();
            } else {
                alert("Could not delete custom task list: " + (res.error || 'Unknown error'));
            }
        }

        function handleCustomTaskRowClick(e, projId, listId, taskId) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' || e.target.tagName === 'SELECT' || e.target.tagName === 'A' || e.target.tagName === 'TEXTAREA') {
                return;
            }
            const li = document.getElementById('task-' + taskId);
            if (li) {
                const cb = li.querySelector('input[type="checkbox"]');
                if (cb) {
                    cb.focus();
                }
            }
        }

        function updateCustomTaskCountBadge(listId, containerEl) {
            if (!containerEl) return;
            const badge = containerEl.querySelector('#task-count-badge-' + listId) || containerEl.querySelector('.task-count');
            if (badge) {
                const total = containerEl.querySelectorAll('.task-item').length;
                const completed = containerEl.querySelectorAll('.task-item.completed').length;
                badge.textContent = `${completed} / ${total} DONE`;
            }
        }

        async function addCustomListTask(e, projId, listId) {
            e.preventDefault();
            const form = e.target;
            const input = form.querySelector('[name="task_title"]');
            const submitBtn = form.querySelector('.add-task-btn') || form.querySelector('button[type="submit"]');
            const activeEl = document.activeElement;
            const title = input.value.trim();
            if (!title) return;

            const res = await apiCall({ action: 'add_custom_list_task', project_id: projId, list_id: listId, title: title });
            if (res && res.success && res.task) {
                const t = res.task;
                const container = document.getElementById('tasks-container-' + listId) || form.closest('.tasks-container');
                if (container) {
                    const ul = container.querySelector('.task-list');
                    if (ul) {
                        const li = document.createElement('li');
                        li.className = 'task-item';
                        li.id = 'task-' + t.id;
                        li.setAttribute('onclick', `handleCustomTaskRowClick(event, '${escapeHtml(projId)}', '${escapeHtml(listId)}', '${escapeHtml(t.id)}')`);

                        const pVal = t.priority || '';
                        const pClass = pVal ? 'priority-' + (pVal === '*' ? 'star' : (pVal === '!' ? 'excl' : (pVal === '$' ? 'dollar' : pVal))) : '';
                        if (pClass) li.classList.add(pClass);

                        li.innerHTML = `
                            <input type="checkbox" onchange="toggleCustomListTask('${escapeHtml(projId)}', '${escapeHtml(listId)}', '${escapeHtml(t.id)}')">
                            <span class="item-task-flare" style="${pVal ? '' : 'display:none;'}">${escapeHtml(pVal)}</span>
                            <span class="task-title">${escapeHtml(t.title)}</span>
                            <button type="button" onclick="promptAddCustomSubTask(event, '${escapeHtml(projId)}', '${escapeHtml(listId)}', '${escapeHtml(t.id)}')" class="add-subtask-btn" title="Add sub-task">+ sub</button>
                            <select class="task-priority-select" onchange="updateCustomListTaskPriority('${escapeHtml(projId)}', '${escapeHtml(listId)}', '${escapeHtml(t.id)}', this.value)" title="Set priority level">
                                <option value="" ${pVal === '' ? 'selected' : ''}>-</option>
                                <option value="1" ${pVal === '1' ? 'selected' : ''}>1</option>
                                <option value="2" ${pVal === '2' ? 'selected' : ''}>2</option>
                                <option value="3" ${pVal === '3' ? 'selected' : ''}>3</option>
                                <option value="*" ${pVal === '*' ? 'selected' : ''}>*</option>
                                <option value="!" ${pVal === '!' ? 'selected' : ''}>!</option>
                                <option value="$" ${pVal === '$' ? 'selected' : ''}>$</option>
                            </select>
                            <button onclick="deleteCustomListTask('${escapeHtml(projId)}', '${escapeHtml(listId)}', '${escapeHtml(t.id)}')" class="task-del-btn" title="Delete task">&times;</button>
                        `;
                        ul.appendChild(li);
                    }
                    updateCustomTaskCountBadge(listId, container);
                }

                input.value = '';
                if (activeEl === submitBtn) {
                    submitBtn.focus();
                } else if (input) {
                    input.focus();
                }
            }
        }

        async function toggleCustomListTask(projId, listId, taskId) {
            const res = await apiCall({ action: 'toggle_custom_list_task', project_id: projId, list_id: listId, task_id: taskId });
            if (res.success) {
                const li = document.getElementById('task-' + taskId);
                if (li) {
                    li.classList.toggle('completed', res.completed);
                    const ul = li.closest('.task-list');
                    if (res.completed && ul) {
                        ul.prepend(li);
                    }
                    const container = li.closest('.tasks-container');
                    if (container) updateCustomTaskCountBadge(listId, container);

                    if (res.completed && ul) {
                        const nextUnchecked = ul.querySelector('.task-item:not(.completed) input[type="checkbox"]');
                        if (nextUnchecked) nextUnchecked.focus();
                    }
                }
            }
        }

        async function deleteCustomListTask(projId, listId, taskId) {
            if (!confirm('Delete task?')) return;
            const res = await apiCall({ action: 'delete_custom_list_task', project_id: projId, list_id: listId, task_id: taskId });
            if (res.success) {
                const li = document.getElementById('task-' + taskId);
                if (li) {
                    const container = li.closest('.tasks-container');
                    li.remove();
                    if (container) updateCustomTaskCountBadge(listId, container);
                }
            }
        }

        async function updateCustomListTaskPriority(projId, listId, taskId, priority) {
            const res = await apiCall({ action: 'update_custom_list_task_priority', project_id: projId, list_id: listId, task_id: taskId, priority: priority });
            if (res.success) {
                const li = document.getElementById('task-' + taskId);
                if (li) {
                    li.className = li.className.replace(/\bpriority-\S+/g, '').trim();
                    if (res.priority) {
                        const pClass = 'priority-' + (res.priority === '*' ? 'star' : (res.priority === '!' ? 'excl' : (res.priority === '$' ? 'dollar' : res.priority)));
                        li.classList.add(pClass);
                    }
                    const flare = li.querySelector('.item-task-flare');
                    if (flare) {
                        flare.textContent = res.priority || '';
                        flare.style.display = res.priority ? 'inline-block' : 'none';
                    }
                }
            }
        }

        async function promptAddCustomSubTask(e, projId, listId, parentTaskId) {
            if (e && e.stopPropagation) e.stopPropagation();
            const title = prompt("Enter sub-task title:");
            if (!title || !title.trim()) return;

            const res = await apiCall({ action: 'add_custom_sub_task', project_id: projId, list_id: listId, parent_task_id: parentTaskId, title: title.trim() });
            if (res && res.success && res.sub_task) {
                const st = res.sub_task;
                const parentLi = document.getElementById('task-' + parentTaskId);
                if (parentLi) {
                    let subUl = parentLi.querySelector('.task-sublist');
                    if (!subUl) {
                        subUl = document.createElement('ul');
                        subUl.className = 'task-sublist';
                        parentLi.appendChild(subUl);
                    }
                    const subLi = document.createElement('li');
                    subLi.className = 'subtask-item';
                    subLi.id = 'subtask-' + st.id;
                    subLi.innerHTML = `
                        <input type="checkbox" onchange="toggleCustomSubTask('${escapeHtml(projId)}', '${escapeHtml(listId)}', '${escapeHtml(parentTaskId)}', '${escapeHtml(st.id)}')">
                        <span class="subtask-title">${escapeHtml(st.title)}</span>
                        <button type="button" onclick="deleteCustomSubTask('${escapeHtml(projId)}', '${escapeHtml(listId)}', '${escapeHtml(parentTaskId)}', '${escapeHtml(st.id)}')" class="subtask-del-btn" title="Delete sub-task">&times;</button>
                    `;
                    subUl.appendChild(subLi);
                }
            }
        }

        async function toggleCustomSubTask(projId, listId, parentTaskId, subTaskId) {
            const res = await apiCall({ action: 'toggle_custom_sub_task', project_id: projId, list_id: listId, parent_task_id: parentTaskId, sub_task_id: subTaskId });
            if (res.success) {
                const subLi = document.getElementById('subtask-' + subTaskId);
                if (subLi) subLi.classList.toggle('completed', res.completed);
            }
        }

        async function deleteCustomSubTask(projId, listId, parentTaskId, subTaskId) {
            if (!confirm('Delete sub-task?')) return;
            const res = await apiCall({ action: 'delete_custom_sub_task', project_id: projId, list_id: listId, parent_task_id: parentTaskId, sub_task_id: subTaskId });
            if (res.success) {
                const subLi = document.getElementById('subtask-' + subTaskId);
                if (subLi) subLi.remove();
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

        function showDashboardSearchHelp() {
            alert(
                "DASHBOARD SEARCH & FILTER GUIDE:\n\n" +
                "1. Category & Status Filters: Filter projects by tag category and lifecycle status using the scrollable filter bars.\n" +
                "2. Keyword Search: Click 'SEARCH' to filter visible cards by matching text across project names, IDs, tags, or scopes.\n" +
                "3. Task Count Sort: Click 'DESC BY TASK QTY' to reorder the currently filtered list from most tasks to least tasks (equal task counts sort alphabetically by Project ID).\n\n" +
                "Note: Updating search keywords or selecting category/status filters automatically resets the task sort order."
            );
        }

        function promptDashboardSearch() {
            const currentQuery = window.dashboardSearchQuery || '';
            const query = prompt("Filter dashboard projects by keyword:\n(Leave empty to clear active search)", currentQuery);
            if (query === null) return;
            applyDashboardCardSearch(query.trim());
        }

        function resetTaskSortState() {
            const btn = document.getElementById('sortTaskQtyBtn');
            if (btn && btn.classList.contains('active')) {
                btn.classList.remove('active');
                btn.textContent = 'DESC BY TASK QTY';
            }
        }

        function applyDashboardCardSearch(query) {
            resetTaskSortState();
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

        async function triggerHomeBackupNow() {
            const res = await apiCall({ action: 'backup_projects_json' });
            if (res && res.success) {
                alert('Backup created successfully: ' + res.filename);
            } else {
                alert('Backup failed: ' + (res.error || 'Unknown error'));
            }
        }

        function sortCardsByTaskQuantity() {
            const grid = document.querySelector('.dashboard-grid');
            const btn = document.getElementById('sortTaskQtyBtn');
            if (!grid) return;

            const cards = Array.from(grid.querySelectorAll('.project-card'));
            if (!cards.length) return;

            const isSorted = btn && btn.classList.contains('active');

            if (isSorted) {
                cards.sort((a, b) => {
                    const idA = (a.getAttribute('data-project-id') || a.id || '').toLowerCase();
                    const idB = (b.getAttribute('data-project-id') || b.id || '').toLowerCase();
                    return idA.localeCompare(idB);
                });
                resetTaskSortState();
            } else {
                cards.sort((a, b) => {
                    const countA = a.querySelectorAll('.task-list .task-item').length;
                    const countB = b.querySelectorAll('.task-list .task-item').length;
                    if (countA !== countB) {
                        return countB - countA;
                    }
                    const idA = (a.getAttribute('data-project-id') || a.id || '').toLowerCase();
                    const idB = (b.getAttribute('data-project-id') || b.id || '').toLowerCase();
                    return idA.localeCompare(idB);
                });
                if (btn) {
                    btn.classList.add('active');
                    btn.textContent = 'DESC BY TASK QTY (ACTIVE)';
                }
            }

            cards.forEach(card => grid.appendChild(card));
        }

        function reverseTaskListOrder(listId) {
            const ul = document.getElementById('task-list-ul-' + listId) || (document.getElementById('tasks-container-' + listId) ? document.getElementById('tasks-container-' + listId).querySelector('.task-list') : null);
            if (!ul) return;

            const items = Array.from(ul.children);
            if (!items.length) return;

            items.reverse().forEach(li => ul.appendChild(li));

            const container = ul.closest('.tasks-container');
            if (container) {
                const btn = container.querySelector('.btn-reverse-order');
                if (btn) btn.classList.toggle('active');
            }
        }

        function toggleCardTaskTruncation(e, link) {
            if (e && e.preventDefault) e.preventDefault();
            const container = link.closest('.tasks-container');
            if (!container) return;
            const ul = container.querySelector('.task-list');
            if (!ul) return;
            const isTruncated = ul.classList.contains('truncated');
            if (isTruncated) {
                ul.classList.remove('truncated');
                ul.classList.add('expanded');
                link.textContent = 'SEE LESS';
            } else {
                ul.classList.remove('expanded');
                ul.classList.add('truncated');
                const total = ul.querySelectorAll('.task-item').length;
                const hidden = total > 5 ? total - 5 : 0;
                link.textContent = `SEE MORE (${hidden} MORE)`;
            }
        }

        function getStoragePrefix() {
            const path = window.location.pathname || '';
            let hash = 0;
            for (let i = 0; i < path.length; i++) {
                hash = ((hash << 5) - hash) + path.charCodeAt(i);
                hash |= 0;
            }
            return 'vpm_' + Math.abs(hash).toString(36);
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
                const key = getStoragePrefix() + '_dashboard_hide_textareas';
                localStorage.setItem(key, isHidden ? 'true' : 'false');
            } catch (e) {}
        }

        document.addEventListener('keydown', (e) => {
            if (e.target && e.target.classList.contains('add-task-input')) {
                if (e.key === 'Enter') {
                    if (e.shiftKey) {
                        e.preventDefault();
                        const el = e.target;
                        const start = el.selectionStart || 0;
                        const end = el.selectionEnd || 0;
                        const val = el.value;
                        el.value = val.substring(0, start) + "\n" + val.substring(end);
                        el.selectionStart = el.selectionEnd = start + 1;
                    } else {
                        e.preventDefault();
                        const form = e.target.closest('form');
                        if (form) {
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                const submitBtn = form.querySelector('.add-task-btn') || form.querySelector('button[type="submit"]');
                                if (submitBtn) submitBtn.click();
                            }
                        }
                    }
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            try {
                const key = getStoragePrefix() + '_dashboard_hide_textareas';
                const saved = localStorage.getItem(key) || localStorage.getItem('dashboard_hide_textareas');
                if (saved === 'true') {
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
