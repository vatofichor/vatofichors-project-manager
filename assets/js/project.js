/**
 * Simplex Dashboard - Project Route Actions Module
 * 
 * Copyright (c) 2026:
 * vatofichor - Sebastian Mass     [>_<]
 * & Assisted By Gemini Antigravity /|\
 */

async function deleteProjectAction(projId, projName) {
    const confirmation = prompt(`To delete project "${projName}", type "yes":`);
    if (!confirmation || confirmation.trim().toLowerCase() !== 'yes') {
        return;
    }

    try {
        const res = await apiCall({ action: 'delete_project', project_id: projId });
        if (res && res.success) {
            window.location.href = '?route=home';
        }
    } catch (err) {
        // Silent failure
    }
}

async function renameProjectPrompt(projId, currentName) {
    const newName = prompt(`Rename project "${currentName}":`, currentName);
    if (newName === null) return;
    const trimmed = newName.trim();
    if (!trimmed || trimmed === currentName) return;

    try {
        const res = await apiCall({ action: 'rename_project', project_id: projId, new_name: trimmed });
        if (res && res.success) {
            const redirectId = res.new_id || projId;
            window.location.href = `?route=project&title=${encodeURIComponent(redirectId)}`;
        } else if (res && res.error) {
            alert(`[ERROR] ${res.error}`);
        }
    } catch (err) {
        // Silent failure
    }
}
function toggleUpdateStatusBox() {
    const box = document.getElementById('updateStatusBox');
    if (box) {
        box.style.display = (box.style.display === 'none' || !box.style.display) ? 'block' : 'none';
    }
}

async function runUpdateStatusAction(projId) {
    const sel = document.getElementById('updateStatusSelect');
    if (!sel) return;
    const newStatus = sel.value;

    try {
        const res = await apiCall({
            action: 'update_scope',
            project_id: projId,
            status: newStatus
        });
        if (res && res.success) {
            window.location.reload();
        } else if (res && res.error) {
            alert(`[ERROR] ${res.error}`);
        }
    } catch (err) {
        // Silent failure
    }
}
