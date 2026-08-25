/**
 * Simplex Dashboard - Admin Route Script Module
 * 
 * Copyright (c) 2026:
 * vatofichor - Sebastian Mass     [>_<]
 * & Assisted By Gemini Antigravity /|\
 */

let lastLoadedReportJson = null;
window.selectedProjects = [];

async function loadAdminViewportReport() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp) return;
    vp.innerHTML = '<pre>Scanning target roots dynamically from config.json...</pre>';
    try {
        const scanRes = await apiCall({ action: 'scan_pool_projects' });
        if (scanRes && scanRes.success && scanRes.report) {
            lastLoadedReportJson = scanRes.report;
        } else {
            const res = await fetch('projects-aggregates-report.json');
            if (!res.ok) throw new Error('Report file not found.');
            lastLoadedReportJson = await res.json();
        }
        convertReportToChecklist();
    } catch (err) {
        vp.innerHTML = `<pre>[ERROR] ${escapeHtml(err.message)}</pre>`;
        if (sub) sub.innerHTML = '';
    }
}

function convertReportToChecklist() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp || !lastLoadedReportJson) return;

    const projects = lastLoadedReportJson.projects || [];
    window.selectedProjects = [];

    if (sub) {
        sub.innerHTML = `
            <button onclick="loadAdminViewportReport()" class="filter-btn" style="padding: 2px 8px; font-size: 11px;">RELOAD TABLE</button>
            <button onclick="commitSelectedChecklistProjects()" class="action-btn" style="padding: 2px 8px; font-size: 11px;">+ COMMIT CHECKLIST</button>
        `;
    }

    let html = `<div class="admin-checklist-container">`;
    html += `<div class="viewport-filter-toolbar checklist-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; padding-bottom:6px; border-bottom:1px dashed var(--border-color);">`;
    html += `<div><strong style="color:var(--accent-teal);">DISCOVERED PROJECTS TABLE</strong> (Total: ${projects.length})</div>`;
    html += `<div style="display:flex; gap:6px; align-items:center;">`;
    html += `<button type="button" onclick="selectChecklistProjects('all')" class="filter-btn">SELECT ALL</button>`;
    html += `<button type="button" onclick="selectChecklistProjects('outer')" class="filter-btn" style="color:var(--accent-teal); border-color:var(--accent-teal);">SELECT OUTER</button>`;
    html += `<button type="button" onclick="selectChecklistProjects('none')" class="filter-btn">DESELECT ALL</button>`;
    html += `<span id="selectedCountBadge" class="task-count" style="margin-left:6px; font-weight:bold;">Selected: 0</span>`;
    html += `</div>`;
    html += `</div>`;

    html += `<ul class="admin-checklist-list" style="list-style:none; display:flex; flex-direction:column; gap:4px; max-height:480px; overflow-y:auto;">`;
    projects.forEach((p, idx) => {
        const markers = [];
        if (p.has_ltap) markers.push('LTAP');
        if (p.has_readme) markers.push('README');
        if (p.has_workspace) markers.push('CODE-WORKSPACE');
        const markerStr = markers.length > 0 ? ` <span style="color:var(--accent-amber);">[${markers.join(' | ')}]</span>` : '';

        const isSubdir = (p.id && p.id.includes('/')) || (p.depth && p.depth > 1);
        const indentStyle = isSubdir ? 'margin-left: 20px; border-left: 2px solid var(--accent-teal);' : '';

        html += `<li class="task-item" style="display:flex; align-items:center; gap:8px; padding:4px 6px; ${indentStyle}">`;
        html += `<input type="checkbox" id="chk_${idx}" class="admin-proj-chk" data-id="${escapeHtml(p.id)}" data-name="${escapeHtml(p.name)}" data-path="${escapeHtml(p.path)}" data-is-outer="${isSubdir ? '0' : '1'}" onchange="updateSelectedProjectsArray()">`;
        html += `<label for="chk_${idx}" style="cursor:pointer; flex:1; font-family:var(--font-mono); font-size:11px;">`;
        if (isSubdir) {
            html += `<span style="color:var(--accent-teal); font-weight:bold; margin-right:4px;">↳</span>`;
        }
        html += `<strong style="color:#fff;">${escapeHtml(p.id)}</strong> <span style="color:var(--text-muted);">| Path: ${escapeHtml(p.path)}</span>${markerStr}`;
        html += `</label>`;
        html += `</li>`;
    });
    html += `</ul>`;
    html += `</div>`;

    vp.innerHTML = html;
    updateSelectedProjectsArray();
}

function updateSelectedProjectsArray() {
    const checkboxes = document.querySelectorAll('.admin-proj-chk');
    window.selectedProjects = [];
    checkboxes.forEach(chk => {
        if (chk.checked) {
            window.selectedProjects.push({
                id: chk.getAttribute('data-id'),
                name: chk.getAttribute('data-name'),
                path: chk.getAttribute('data-path')
            });
        }
    });

    const badge = document.getElementById('selectedCountBadge');
    if (badge) {
        badge.textContent = `Selected: ${window.selectedProjects.length}`;
    }
}

function selectChecklistProjects(mode) {
    const checkboxes = document.querySelectorAll('.admin-proj-chk');
    checkboxes.forEach(chk => {
        if (mode === 'all') {
            chk.checked = true;
        } else if (mode === 'none') {
            chk.checked = false;
        } else if (mode === 'outer') {
            const isOuter = chk.getAttribute('data-is-outer') === '1';
            chk.checked = isOuter;
        }
    });
    updateSelectedProjectsArray();
}

async function commitSelectedChecklistProjects() {
    if (!window.selectedProjects || window.selectedProjects.length === 0) {
        return;
    }

    try {
        const res = await apiCall({ action: 'commit_projects', projects: window.selectedProjects });
        if (res && res.success) {
            const vp = document.getElementById('adminViewport');
            const sub = document.getElementById('adminSubActions');
            if (vp) {
                const disambigStr = res.disambiguated_count > 0 ? `\n(Disambiguated ${res.disambiguated_count} duplicate name(s) via dirpath root traversal)` : '';
                vp.innerHTML = `<pre>[SUCCESS] Committed ${res.added_count} new project(s) to projects.json.${disambigStr}\n(Skipped ${res.skipped_count} existing path entry/entries).\nTotal processed: ${res.total_processed}</pre>`;
            }
            if (sub) sub.innerHTML = '';
        }
    } catch (err) {
        // Fail silently
    }
}

function loadDeleteAllProjectsConfirmation() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp) return;
    if (sub) sub.innerHTML = '';
    window.selectedProjects = [];
    lastLoadedReportJson = null;

    let html = `<div class="admin-purge-card" style="padding:12px; border:1px solid var(--accent-red); background:var(--bg-dark);">`;
    html += `<h3 style="color:var(--accent-red); font-size:13px; margin-bottom:8px;">[WARNING] DANGER ZONE - PURGE ALL PROJECTS</h3>`;
    html += `<p style="color:var(--text-muted); font-size:11px; margin-bottom:12px;">This action will clear all project entries from <code style="color:#fff;">projects.json</code> so you can rebuild from scratch.</p>`;
    html += `<div style="display:flex; gap:8px; align-items:center;">`;
    html += `<input type="text" id="purgeConfirmationInput" placeholder="type 'yes' to confirm" style="padding:4px 8px; background:var(--bg-input); border:1px solid var(--border-color); color:var(--text-main); font-family:var(--font-mono); font-size:11px; flex:1; max-width:240px;">`;
    html += `<button type="button" onclick="runDeleteAllProjectsAction()" class="action-btn" style="background:var(--accent-red); color:#fff; padding:4px 10px;">CONFIRM &amp; PURGE ALL PROJECTS</button>`;
    html += `</div>`;
    html += `</div>`;

    vp.innerHTML = html;
}

async function runDeleteAllProjectsAction() {
    const input = document.getElementById('purgeConfirmationInput');
    if (!input || input.value.trim().toLowerCase() !== 'yes') {
        return; // Silent failure
    }

    try {
        const res = await apiCall({ action: 'delete_all_projects' });
        if (res && res.success) {
            const vp = document.getElementById('adminViewport');
            if (vp) {
                vp.innerHTML = `<pre>[SUCCESS] All project entries have been cleared from projects.json.\n(Total projects removed: ${res.removed_count})</pre>`;
            }
        }
    } catch (err) {
        // Silent failure
    }
}

function clearAdminViewport() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (vp) vp.innerHTML = '<pre>Viewport cleared.</pre>';
    if (sub) sub.innerHTML = '';
    lastLoadedReportJson = null;
    window.selectedProjects = [];
}

const ADMIN_AVAILABLE_TAGS = [
    'ALL',
    'PROJECTS',
    'APPS',
    'TOOLS',
    'LIBRARIES',
    'PACKAGES',
    'TEMPLATES',
    'META',
    'OPEN SOURCE',
    'PROPRIETARY'
];

async function loadAdminTagManager() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp) return;

    if (sub) {
        sub.innerHTML = `
            <button onclick="loadAdminTagManager()" class="filter-btn" style="padding: 2px 8px; font-size: 11px;">RELOAD TAG MANAGER</button>
        `;
    }

    vp.innerHTML = '<pre>Loading projects from projects.json...</pre>';

    try {
        let availableTags = ['ALL', 'PROJECTS', 'APPS', 'TOOLS', 'LIBRARIES', 'PACKAGES', 'TEMPLATES', 'META', 'OPEN SOURCE', 'PROPRIETARY'];
        try {
            const cfgRes = await fetch('config.json');
            if (cfgRes.ok) {
                const cfgData = await cfgRes.json();
                if (cfgData.categories && Array.isArray(cfgData.categories)) {
                    availableTags = cfgData.categories;
                }
            }
        } catch (e) {}

        const res = await fetch('projects.json');
        if (!res.ok) throw new Error('Could not load projects.json.');
        const dbData = await res.json();
        const projects = dbData.projects || [];

        if (projects.length === 0) {
            vp.innerHTML = '<pre>[INFO] No projects currently exist in projects.json.</pre>';
            return;
        }

        let html = `<div class="admin-tag-manager-container">`;
        html += `<div class="viewport-filter-toolbar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; padding-bottom:6px; border-bottom:1px dashed var(--border-color-config);">`;
        html += `<div><strong style="color:var(--accent-theme);">PROJECT TAG MANAGER</strong> (Total Projects: ${projects.length})</div>`;
        html += `</div>`;

        html += `<ul class="admin-tag-list" style="list-style:none; display:flex; flex-direction:column; gap:8px; max-height:480px; overflow-y:auto;">`;

        projects.forEach((p, idx) => {
            const activeTags = (p.tags || []).map(t => String(t).toUpperCase());
            if (p.category) {
                const catUpper = String(p.category).toUpperCase();
                if (catUpper === 'PROJECT' && !activeTags.includes('PROJECTS')) activeTags.push('PROJECTS');
                if (catUpper === 'TOOL' && !activeTags.includes('TOOLS')) activeTags.push('TOOLS');
                if (catUpper === 'META' && !activeTags.includes('META')) activeTags.push('META');
            }

            html += `<li class="task-item" style="display:flex; flex-direction:column; gap:6px; padding:8px 10px;">`;
            html += `<div style="display:flex; justify-content:space-between; align-items:center;">`;
            html += `<div><strong style="color:var(--text-white); font-size:12px;">${escapeHtml(p.name)}</strong> <code style="font-size:11px; color:var(--text-muted);">(${escapeHtml(p.id)})</code></div>`;
            html += `</div>`;

            html += `<div class="scroll-filter-widget">`;
            availableTags.forEach(tag => {
                const isChecked = activeTags.includes(tag.toUpperCase());
                const chkId = `tag_chk_${idx}_${tag.replace(/[^a-zA-Z0-9]/g, '_')}`;
                html += `<label for="${chkId}" style="display:inline-flex; align-items:center; gap:4px; font-size:10px; padding:2px 6px; background:var(--bg-input); border:1px solid var(--border-color-config); white-space:nowrap; cursor:pointer;">`;
                html += `<input type="checkbox" id="${chkId}" ${isChecked ? 'checked' : ''} onchange="toggleProjectTag('${escapeHtml(p.id)}', '${escapeHtml(tag)}', this.checked)">`;
                html += `<span style="${isChecked ? 'color:var(--accent-theme); font-weight:bold;' : 'color:var(--text-muted);'}">${escapeHtml(tag)}</span>`;
                html += `</label>`;
            });
            html += `</div>`;
            html += `</li>`;
        });

        html += `</ul>`;
        html += `</div>`;

        vp.innerHTML = html;
    } catch (err) {
        vp.innerHTML = `<pre>[ERROR] ${escapeHtml(err.message)}</pre>`;
    }
}

async function toggleProjectTag(projId, tag, isChecked) {
    try {
        await apiCall({
            action: 'toggle_project_tag',
            project_id: projId,
            tag: tag,
            enabled: isChecked
        });
    } catch (err) {
        // Silent failure per rule
    }
}

async function launchNativeSavesExplorer() {
    try {
        const res = await apiCall({ action: 'launch_saves_explorer' });
        if (res && res.success) {
            const vp = document.getElementById('adminViewport');
            if (vp && (!vp.innerText || vp.innerText.includes('Select an action') || vp.innerText.includes('Viewport cleared') || vp.innerText.includes('Run the projects'))) {
                vp.innerHTML = `<pre style="color:var(--accent-theme);">[INFO] Opened native file manager at:\n${escapeHtml(res.path)} (${escapeHtml(res.os)})</pre>`;
            }
        } else {
            alert('Could not open saves folder: ' + (res.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Error launching saves explorer: ' + err.message);
    }
}

function loadAdminBackupTool() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp) return;

    if (sub) {
        sub.innerHTML = `
            <button type="button" onclick="launchNativeSavesExplorer()" class="filter-btn" style="padding: 2px 8px; font-size: 11px; color: var(--accent-theme); border-color: var(--accent-theme);">SAVES FOLDER ↗</button>
            <button onclick="runBackupProjectsJsonAction()" class="filter-btn active">RUN BACKUP</button>
        `;
    }

    vp.innerHTML = '<pre>Run the projects backup tool.</pre>';
}

async function runBackupProjectsJsonAction() {
    const vp = document.getElementById('adminViewport');
    if (!vp) return;
    vp.innerHTML = '<pre>Creating timestamped backup of projects.json in ./saves/...</pre>';

    try {
        const res = await apiCall({ action: 'backup_projects_json' });
        if (res && res.success) {
            let html = `<div style="padding:10px; font-family:var(--font-mono);">`;
            html += `<div style="color:var(--accent-green); font-weight:bold; font-size:13px; margin-bottom:8px;">[SUCCESS] projects.json backed up successfully!</div>`;
            html += `<div style="color:var(--text-main); font-size:11px; margin-bottom:12px;">Saved destination: <code>./saves/${escapeHtml(res.filename)}</code></div>`;
            html += `<a href="saves/${escapeHtml(res.filename)}" download="${escapeHtml(res.filename)}" class="action-btn" style="display:inline-block; text-decoration:none; padding:6px 12px;">DOWNLOAD BACKUP (${escapeHtml(res.filename)})</a>`;
            html += `</div>`;
            vp.innerHTML = html;
        } else {
            vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(res.error || 'Failed to create backup.')}</pre>`;
        }
    } catch (err) {
        vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(err.message)}</pre>`;
    }
}

async function loadAdminRestoreTool() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp) return;

    if (sub) {
        sub.innerHTML = `
            <button type="button" onclick="launchNativeSavesExplorer()" class="filter-btn" style="padding: 2px 8px; font-size: 11px; color: var(--accent-theme); border-color: var(--accent-theme);">SAVES FOLDER ↗</button>
            <button onclick="loadAdminRestoreTool()" class="filter-btn" style="padding: 2px 8px; font-size: 11px;">RELOAD SAVES</button>
        `;
    }

    vp.innerHTML = '<pre>Loading available backups from ./saves/...</pre>';

    try {
        const res = await apiCall({ action: 'list_saves' });
        const saves = (res && res.saves) ? res.saves : [];

        let html = `<div class="admin-restore-container" style="padding:4px; font-family:var(--font-mono);">`;
        html += `<div style="background:var(--bg-input); border:1px solid var(--accent-amber); padding:8px 10px; margin-bottom:12px; color:var(--accent-amber); font-size:11px;">`;
        html += `<strong>[IMPORTANT] A backup should be performed prior to restoring!</strong>`;
        html += `</div>`;

        if (saves.length === 0) {
            html += `<pre>[INFO] No backup files found in ./saves/</pre>`;
            html += `</div>`;
            vp.innerHTML = html;
            return;
        }

        html += `<div style="margin-bottom:10px; font-size:12px; font-weight:bold; color:var(--text-white);">Select a backup file to restore:</div>`;
        html += `<ul style="list-style:none; display:flex; flex-direction:column; gap:6px; margin-bottom:14px; max-height:260px; overflow-y:auto;">`;

        saves.forEach((s, idx) => {
            const kbSize = (s.size / 1024).toFixed(1);
            html += `<li class="task-item" style="padding:6px 10px; display:flex; align-items:center; gap:8px;">`;
            html += `<input type="radio" name="restore_file_choice" id="save_file_${idx}" value="${escapeHtml(s.filename)}" ${idx === 0 ? 'checked' : ''}>`;
            html += `<label for="save_file_${idx}" style="cursor:pointer; flex:1; display:flex; justify-content:space-between; align-items:center; font-size:11px;">`;
            html += `<strong style="color:var(--accent-theme);">${escapeHtml(s.filename)}</strong>`;
            html += `<span style="color:var(--text-muted); font-size:10px;">Size: ${kbSize} KB | Date: ${escapeHtml(s.mtime)}</span>`;
            html += `</label>`;
            html += `</li>`;
        });

        html += `</ul>`;

        html += `<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; border-top:1px dashed var(--border-color-config); padding-top:10px;">`;
        html += `<label style="font-size:11px; color:var(--text-main);">Type <code style="color:var(--accent-amber);">restore</code> to confirm:</label>`;
        html += `<input type="text" id="restoreConfirmInput" placeholder="restore" autocomplete="off" style="background:var(--bg-input); border:1px solid var(--border-color-config); color:var(--text-white); padding:4px 8px; font-family:var(--font-mono); font-size:11px; width:120px;">`;
        html += `<button type="button" onclick="runRestoreProjectsJsonAction()" class="action-btn" style="background:var(--accent-amber); color:#000; padding:5px 12px;">RESTORE SELECTED BACKUP</button>`;
        html += `</div>`;

        html += `</div>`;
        vp.innerHTML = html;
    } catch (err) {
        vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(err.message)}</pre>`;
    }
}

async function runRestoreProjectsJsonAction() {
    const vp = document.getElementById('adminViewport');
    const selectedRadio = document.querySelector('input[name="restore_file_choice"]:checked');
    const confirmInput = document.getElementById('restoreConfirmInput');

    if (!selectedRadio) {
        alert('Please select a backup file to restore.');
        return;
    }

    if (!confirmInput || confirmInput.value.trim().toLowerCase() !== 'restore') {
        alert("Please type 'restore' into the text field to authorize restoration.");
        return;
    }

    const filename = selectedRadio.value;
    vp.innerHTML = `<pre>Restoring projects.json from ${escapeHtml(filename)}...</pre>`;

    try {
        const res = await apiCall({
            action: 'restore_projects_json',
            filename: filename
        });

        if (res && res.success) {
            let html = `<div style="padding:10px; font-family:var(--font-mono);">`;
            html += `<div style="color:var(--accent-green); font-weight:bold; font-size:13px; margin-bottom:8px;">[SUCCESS] projects.json has been restored successfully!</div>`;
            html += `<div style="color:var(--text-main); font-size:11px;">Restored file: <code>./saves/${escapeHtml(res.filename)}</code></div>`;
            html += `<div style="color:var(--text-muted); font-size:10px; margin-top:4px;">Restored timestamp: ${escapeHtml(res.restored_at)}</div>`;
            html += `</div>`;
            vp.innerHTML = html;
        } else {
            vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(res.error || 'Failed to restore projects.json.')}</pre>`;
        }
    } catch (err) {
        vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(err.message)}</pre>`;
    }
}

async function loadAdminConfigEditor() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp) return;

    if (sub) {
        sub.innerHTML = `
            <button onclick="submitAdminConfigForm()" class="action-btn" style="padding: 2px 8px; font-size: 11px;">UPDATE CONFIG</button>
            <button onclick="resetAdminConfigDefault()" class="filter-btn" style="padding: 2px 8px; font-size: 11px; color: var(--accent-red); border-color: var(--accent-red);">RESET TO DEFAULT</button>
        `;
    }

    vp.innerHTML = '<pre>Loading configuration data...</pre>';

    try {
        const res = await apiCall({ action: 'get_config' });
        if (!res || !res.success || !res.config) {
            throw new Error(res.error || 'Failed to load configuration.');
        }
        renderConfigEditorForm(res.config);
    } catch (err) {
        vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(err.message)}</pre>`;
    }
}

function renderConfigEditorForm(cfg) {
    const vp = document.getElementById('adminViewport');
    if (!vp) return;

    const targetRoots = Array.isArray(cfg.target_root_dir) ? cfg.target_root_dir.join('\n') : (cfg.target_root_dir || '');
    const categories = Array.isArray(cfg.categories) ? cfg.categories.join('\n') : (cfg.categories || '');
    const statuses = Array.isArray(cfg.statuses) ? cfg.statuses.join('\n') : (cfg.statuses || '');
    const ignoreDirs = Array.isArray(cfg.ignore_dirs) ? cfg.ignore_dirs.join('\n') : (cfg.ignore_dirs || '');

    let html = `<div class="admin-config-editor" style="padding: 6px; font-family: var(--font-mono); font-size: 11px;">`;
    html += `<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:6px; border-bottom:1px dashed var(--border-color);">`;
    html += `<strong style="color:var(--accent-teal);">CONFIG.JSON EDITOR</strong>`;
    html += `<span style="color:var(--text-muted); font-size:10px;">Edit configuration parameters below. Click UPDATE CONFIG to apply.</span>`;
    html += `</div>`;

    html += `<form id="adminConfigForm" onsubmit="event.preventDefault(); submitAdminConfigForm();" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">`;

    html += `<div style="display:flex; flex-direction:column; gap:6px;">`;
    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">App Name (app_name):</label>`;
    html += `<input type="text" id="cfg_app_name" value="${escapeHtml(cfg.app_name || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Homepage Title (title):</label>`;
    html += `<input type="text" id="cfg_title" value="${escapeHtml(cfg.title || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Entity Subtitle (subtitle):</label>`;
    html += `<input type="text" id="cfg_subtitle" value="${escapeHtml(cfg.subtitle || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Logo Emoticon (logo_emoticon):</label>`;
    html += `<input type="text" id="cfg_logo_emoticon" value="${escapeHtml(cfg.logo_emoticon || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">`;
    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Logo Color:</label>`;
    html += `<input type="text" id="cfg_logo_color" value="${escapeHtml(cfg.logo_color || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Border Color:</label>`;
    html += `<input type="text" id="cfg_border_color" value="${escapeHtml(cfg.border_color || '')}" class="add-task-input" style="width:100%;"></div>`;
    html += `</div>`;

    html += `<div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">`;
    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Accent Theme Color:</label>`;
    html += `<input type="text" id="cfg_accent_theme" value="${escapeHtml(cfg.accent_theme || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Theme Hover Color:</label>`;
    html += `<input type="text" id="cfg_theme_hover" value="${escapeHtml(cfg.theme_hover || '')}" class="add-task-input" style="width:100%;"></div>`;
    html += `</div>`;

    html += `<div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:6px;">`;
    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Version:</label>`;
    html += `<input type="text" id="cfg_version" value="${escapeHtml(cfg.version || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Theme Name:</label>`;
    html += `<input type="text" id="cfg_theme" value="${escapeHtml(cfg.theme || '')}" class="add-task-input" style="width:100%;"></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Scan Depth:</label>`;
    html += `<input type="number" id="cfg_scan_depth" min="1" max="10" value="${escapeHtml(cfg.scan_depth || 3)}" class="add-task-input" style="width:100%;"></div>`;
    html += `</div>`;

    html += `<div style="margin-top:4px;"><label style="cursor:pointer; display:flex; align-items:center; gap:6px;">`;
    html += `<input type="checkbox" id="cfg_ignore_hidden_dirs" ${cfg.ignore_hidden_dirs !== false ? 'checked' : ''}>`;
    html += `<span style="color:#fff;">Ignore Hidden Directories (ignore_hidden_dirs)</span>`;
    html += `</label></div>`;

    html += `<div style="margin-top:4px;"><label style="cursor:pointer; display:flex; align-items:center; gap:6px;">`;
    html += `<input type="checkbox" id="cfg_show_home_backup_btn" ${cfg.show_home_backup_btn !== false ? 'checked' : ''}>`;
    html += `<span style="color:#fff;">Show "BACKUP NOW" hotlink on Home toolbar (show_home_backup_btn)</span>`;
    html += `</label></div>`;

    html += `</div>`;

    html += `<div style="display:flex; flex-direction:column; gap:6px;">`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Target Root Directories (one per line):</label>`;
    html += `<textarea id="cfg_target_root_dir" rows="3" class="task-notes-input" style="width:100%; font-family:var(--font-mono); resize:vertical;">${escapeHtml(targetRoots)}</textarea></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Categories (one per line):</label>`;
    html += `<textarea id="cfg_categories" rows="3" class="task-notes-input" style="width:100%; font-family:var(--font-mono); resize:vertical;">${escapeHtml(categories)}</textarea></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Statuses (one per line):</label>`;
    html += `<textarea id="cfg_statuses" rows="3" class="task-notes-input" style="width:100%; font-family:var(--font-mono); resize:vertical;">${escapeHtml(statuses)}</textarea></div>`;

    html += `<div><label style="color:var(--text-muted); display:block; margin-bottom:2px;">Ignored Directory Patterns (one per line):</label>`;
    html += `<textarea id="cfg_ignore_dirs" rows="4" class="task-notes-input" style="width:100%; font-family:var(--font-mono); resize:vertical;">${escapeHtml(ignoreDirs)}</textarea></div>`;

    html += `</div>`;

    html += `</form>`;
    html += `<div id="configStatusMessage" style="margin-top:8px;"></div>`;
    html += `</div>`;

    vp.innerHTML = html;
}

function parseTextareaLines(id) {
    const el = document.getElementById(id);
    if (!el) return [];
    return el.value
        .split('\n')
        .map(s => s.trim())
        .filter(s => s.length > 0);
}

async function submitAdminConfigForm() {
    const statusMsg = document.getElementById('configStatusMessage');
    if (statusMsg) statusMsg.innerHTML = '<span style="color:var(--accent-amber);">Saving configuration...</span>';

    const newConfig = {
        app_name: document.getElementById('cfg_app_name')?.value?.trim() || '',
        title: document.getElementById('cfg_title')?.value?.trim() || '',
        subtitle: document.getElementById('cfg_subtitle')?.value?.trim() || '',
        logo_emoticon: document.getElementById('cfg_logo_emoticon')?.value?.trim() || '[>_<]',
        logo_color: document.getElementById('cfg_logo_color')?.value?.trim() || '#00f0ff',
        border_color: document.getElementById('cfg_border_color')?.value?.trim() || '#2b303c',
        accent_theme: document.getElementById('cfg_accent_theme')?.value?.trim() || '#39c5bb',
        theme_hover: document.getElementById('cfg_theme_hover')?.value?.trim() || '#4ae0d5',
        version: document.getElementById('cfg_version')?.value?.trim() || '1.5.0',
        theme: document.getElementById('cfg_theme')?.value?.trim() || 'dark-retro',
        scan_depth: parseInt(document.getElementById('cfg_scan_depth')?.value, 10) || 3,
        ignore_hidden_dirs: document.getElementById('cfg_ignore_hidden_dirs')?.checked ?? true,
        show_home_backup_btn: document.getElementById('cfg_show_home_backup_btn')?.checked ?? true,
        target_root_dir: parseTextareaLines('cfg_target_root_dir'),
        categories: parseTextareaLines('cfg_categories'),
        statuses: parseTextareaLines('cfg_statuses'),
        ignore_dirs: parseTextareaLines('cfg_ignore_dirs')
    };

    try {
        const res = await apiCall({
            action: 'update_config',
            config: newConfig
        });

        if (res && res.success) {
            if (res.config) {
                renderConfigEditorForm(res.config);
                const newStatusMsg = document.getElementById('configStatusMessage');
                if (newStatusMsg) {
                    newStatusMsg.innerHTML = `<span style="color:var(--accent-green); font-weight:bold;">[SUCCESS] Configuration saved to config.json successfully!</span>`;
                }
            } else if (statusMsg) {
                statusMsg.innerHTML = `<span style="color:var(--accent-green); font-weight:bold;">[SUCCESS] Configuration saved to config.json successfully!</span>`;
            }
        } else {
            if (statusMsg) {
                statusMsg.innerHTML = `<span style="color:var(--accent-red); font-weight:bold;">[ERROR] ${escapeHtml(res.error || 'Failed to save configuration.')}</span>`;
            }
        }
    } catch (err) {
        if (statusMsg) {
            statusMsg.innerHTML = `<span style="color:var(--accent-red); font-weight:bold;">[ERROR] ${escapeHtml(err.message)}</span>`;
        }
    }
}

async function resetAdminConfigDefault() {
    const confirmation = prompt("Are you sure you want to reset config.json to default installer values?\nType 'yes' to authorize reset:");
    if (!confirmation || confirmation.trim().toLowerCase() !== 'yes') {
        return;
    }

    const vp = document.getElementById('adminViewport');
    if (vp) vp.innerHTML = '<pre>Resetting config.json to installer defaults...</pre>';

    try {
        const res = await apiCall({ action: 'reset_config' });
        if (res && res.success && res.config) {
            renderConfigEditorForm(res.config);
            const statusMsg = document.getElementById('configStatusMessage');
            if (statusMsg) {
                statusMsg.innerHTML = `<span style="color:var(--accent-green); font-weight:bold;">[SUCCESS] Configuration reset to default installer values!</span>`;
            }
        } else {
            if (vp) vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(res.error || 'Failed to reset configuration.')}</pre>`;
        }
    } catch (err) {
        if (vp) vp.innerHTML = `<pre style="color:var(--accent-red);">[ERROR] ${escapeHtml(err.message)}</pre>`;
    }
}

function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
