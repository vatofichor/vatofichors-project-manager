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

function loadAdminBackupTool() {
    const vp = document.getElementById('adminViewport');
    const sub = document.getElementById('adminSubActions');
    if (!vp) return;

    if (sub) {
        sub.innerHTML = `
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

function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
