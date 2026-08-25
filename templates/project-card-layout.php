<?php
/**
 * Shared Project Card Layout Partial (templates/project-card-layout.php)
 * Everything below .card-header (Scope Container & Task List Module)
 * 
 * Copyright (c) 2026:
 * vatofichor - Sebastian Mass     [>_<]
 * & Assisted By Gemini Antigravity /|\
 */

if (!isset($p) || !is_array($p)) return;

$isSingleRoute = (isset($route) && $route === 'project') || (isset($isSingleView) && $isSingleView);
$scopeRows = $isSingleRoute ? 4 : 2;
$scopeContainerClass = $isSingleRoute ? 'scope-container scope-container-expanded' : 'scope-container';
?>
<div class="<?php echo $scopeContainerClass; ?>">
    <div class="scope-box scope-in">
        <div class="box-header">
            <span class="box-icon">Target</span>
            <h3>CORE IN-SCOPE OBJECTIVE</h3>
        </div>
        <textarea onblur="updateProjectField('<?php echo htmlspecialchars(addslashes($p['id'])); ?>', 'in_scope', this.value)" rows="<?php echo $scopeRows; ?>" placeholder="Define core objective..."><?php echo htmlspecialchars($p['in_scope'] ?? ''); ?></textarea>
    </div>

    <div class="scope-box scope-anti">
        <div class="box-header">
            <span class="box-icon">Warn</span>
            <h3>ANTI-SCOPE / SCOPE CREEP FLAGS</h3>
        </div>
        <textarea onblur="updateProjectField('<?php echo htmlspecialchars(addslashes($p['id'])); ?>', 'anti_scope', this.value)" rows="<?php echo $scopeRows; ?>" placeholder="What is explicitly OUT of scope..."><?php echo htmlspecialchars($p['anti_scope'] ?? ''); ?></textarea>
    </div>

    <div class="scope-box scope-edge">
        <div class="box-header">
            <span class="box-icon">Edge</span>
            <h3>EDGE RISKS & BOUNDARIES</h3>
        </div>
        <textarea onblur="updateProjectField('<?php echo htmlspecialchars(addslashes($p['id'])); ?>', 'edge_risks', this.value)" rows="<?php echo $scopeRows; ?>" placeholder="Unresolved risks & boundary items..."><?php echo htmlspecialchars($p['edge_risks'] ?? ''); ?></textarea>
    </div>
</div>

<div class="tasks-container">
    <div class="tasks-header">
        <h3>TASK LIST</h3>
        <span class="task-count"><?php 
            $completed = count(array_filter($p['tasks'] ?? [], fn($t) => !empty($t['completed'])));
            $total = count($p['tasks'] ?? []);
            echo "$completed / $total DONE";
        ?></span>
    </div>

    <ul class="task-list">
        <?php foreach ($p['tasks'] ?? [] as $t): ?>
            <li class="task-item <?php echo !empty($t['completed']) ? 'completed' : ''; ?>" id="task-<?php echo htmlspecialchars($t['id']); ?>">
                <input type="checkbox" <?php echo !empty($t['completed']) ? 'checked' : ''; ?> onchange="toggleTask('<?php echo htmlspecialchars(addslashes($p['id'])); ?>', '<?php echo htmlspecialchars(addslashes($t['id'])); ?>')">
                <span class="task-title"><?php echo htmlspecialchars($t['title']); ?></span>
                <?php if (!empty($t['notes'])): ?>
                    <span class="task-notes" title="<?php echo htmlspecialchars($t['notes']); ?>">(note)</span>
                <?php endif; ?>
                <button onclick="deleteTask('<?php echo htmlspecialchars(addslashes($p['id'])); ?>', '<?php echo htmlspecialchars(addslashes($t['id'])); ?>')" class="task-del-btn" title="Delete task">&times;</button>
            </li>
        <?php endforeach; ?>
    </ul>

    <form onsubmit="addTask(event, '<?php echo htmlspecialchars(addslashes($p['id'])); ?>')" class="add-task-form">
        <input type="text" placeholder="+ Add task (press Enter)..." name="task_title" required class="add-task-input">
        <button type="submit" class="add-task-btn">+</button>
    </form>
</div>
