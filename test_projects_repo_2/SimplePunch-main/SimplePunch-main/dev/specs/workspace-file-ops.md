# SimplePunch — Spec: Workspace Selection & File Operations
> Generated: 2026-06-20 | Source: `index.html` v1 | Author: Antigravity (doc mode)

---

## 1. Architecture Overview

SimplePunch is a **fully serverless, single-file browser application**. All data I/O is handled exclusively through the [File System Access API](https://developer.mozilla.org/en-US/docs/Web/API/File_System_Access_API) (Chrome/Edge 86+). There is no backend, no database, no localStorage, and no network requests. Data lives entirely in `.txt` files on the user's filesystem.

```
Browser Session
│
├── dirHandle   ← FileSystemDirectoryHandle (workspace root)
├── fileHandle  ← FileSystemFileHandle (active punch log)
└── lines[]     ← in-memory mirror of active file content
```

All three are held in module-level variables. They are **not persisted between page loads** — each session starts blank.

---

## 2. Workspace Selection

### Trigger
**Button:** `📁 Load Folder` (`id="pickDir"`)

### API Call
```js
dirHandle = await window.showDirectoryPicker();
```

- Presents the OS native folder picker
- Returns a `FileSystemDirectoryHandle` for the selected directory
- **Browser security:** requires a user gesture (button click) to invoke
- **Scope:** grants read/write access to the selected directory and its immediate contents only — no parent traversal
- **Persistence:** handle lives only for the current browser session (tab); closing or refreshing destroys it

### Post-selection
Immediately calls `loadFileList()` to populate the file dropdown.

### Constraints
| Constraint | Detail |
|---|---|
| Browser support | Chrome 86+, Edge 86+. Firefox: not supported. Safari: partial. |
| Persistence | None — handle is session-scoped |
| Subdirectories | Not traversed — only root-level `.txt` files are listed |
| Permissions | Read + write granted implicitly by the picker |

---

## 3. File List Population

### Function: `loadFileList()`
```js
async function loadFileList() {
  fileSelect.innerHTML = '';
  for await (const [name] of dirHandle.entries()) {
    if (name.endsWith('.txt')) { ... }
  }
  if (fileSelect.value) loadFile(fileSelect.value);
}
```

**Behaviour:**
- Clears the `<select>` (`id="fileSelect"`) completely
- Iterates all entries in `dirHandle` using the async iterator
- Filters to `.txt` extension only — all other file types are silently ignored
- First `.txt` found auto-loads (browser returns entries in filesystem order, typically alphabetical)

**Important — Snapshot Behaviour:**  
`loadFileList()` is a one-time read of directory state. The dropdown is **not reactive** — files created outside the app or via `+ New File` will **not appear** until `loadFileList()` is called again (i.e., user clicks **Load Folder** again).

---

## 4. File Selection

### Trigger
`<select id="fileSelect">` — `onchange` event

```js
fileSelect.onchange = e => loadFile(e.target.value);
```

### Function: `loadFile(name)`
```js
async function loadFile(name) {
  fileHandle = await dirHandle.getFileHandle(name);
  const file = await fileHandle.getFile();
  lines = (await file.text()).trim().split('\n').filter(Boolean);
  render();
}
```

**Behaviour:**
1. Acquires a `FileSystemFileHandle` for the named file within `dirHandle`
2. Reads the full file text into memory
3. Splits on `\n`, trims, filters empty lines → `lines[]` array
4. Calls `render()` to draw the table

**Error conditions:**  
If the file was deleted externally between listing and selection, `getFileHandle()` will throw. Currently unhandled — will surface as a browser console error.

---

## 5. File Creation

### Trigger
**Button:** `+ New File` (`id="newFile"`)

```js
document.getElementById('newFile').onclick = async () => {
  if (!dirHandle) return alert('Select a folder first.');
  const name = new Date().toISOString().slice(0, 10) + '.txt';
  fileHandle = await dirHandle.getFileHandle(name, { create: true });
  // append to select, set as active, reset lines[], render()
};
```

**Behaviour:**
- Generates filename from UTC date: `YYYY-MM-DD.txt` (e.g. `2026-06-20.txt`)
- Calls `getFileHandle(name, { create: true })` — creates an empty file if it does not exist, or opens the existing file if the name already exists (no overwrite warning)
- Sets `fileHandle` to the new file and `lines = []`
- **Appends** the new option to `<select>` and sets it as the selected value

**Reload required:**  
The new option is injected into the dropdown directly (client-side only). However, if the user reloads the page or re-runs `loadFileList()`, the file will appear naturally because it now exists on disk. This direct injection avoids the need for a full folder reload on creation.

---

## 6. File Write Operations

All writes operate on `fileHandle` — the currently active file.

### 6a. Append Punch — `appendLine(rawLine)`
```js
async function appendLine(rawLine) {
  const file     = await fileHandle.getFile();
  const writable = await fileHandle.createWritable({ keepExistingData: true });
  await writable.seek(file.size);
  await writable.write(rawLine + '\n');
  await writable.close();
}
```

**Used by:** `punch(dir)` — Clock In / Clock Out

**Strategy:** Seek-to-end append. Does not read or rewrite existing content. O(1) regardless of file size.

---

### 6b. Full Rewrite — `rewriteFile()`
```js
async function rewriteFile() {
  const writable = await fileHandle.createWritable({ keepExistingData: false });
  await writable.write(lines.join('\n') + (lines.length ? '\n' : ''));
  await writable.close();
}
```

**Used by:** `deleteLine(index)`, `saveEdit(index)`

**Strategy:** Truncate-and-rewrite from the in-memory `lines[]` array. `keepExistingData: false` truncates the file to zero bytes before writing. The in-memory array is the source of truth; the file is rebuilt from it entirely.

**Risk:** If `lines[]` is out of sync with the file (e.g. concurrent external edit), rewrite will overwrite the external changes. Accepted risk for a single-user tool.

---

## 7. File Format

### Active Format (v1)
```
IN 2026-06-19 21:43:32
OUT 2026-06-19 22:01:05
IN 2026-06-19 23:00:00
```

| Component | Format | Example |
|---|---|---|
| Direction prefix | `IN ` or `OUT ` | `IN ` |
| Date | `YYYY-MM-DD` | `2026-06-19` |
| Separator | ` ` (space) | |
| Time | `HH:MM:SS` (24h) | `21:43:32` |
| Line terminator | `\n` | |

**Encoding:** UTF-8 plain text. No BOM.

**Parse path:** `parseLine(raw)` → splits on first space to extract `dir` and `ts`. `parseTS(ts)` → splits `ts` on space then on `-`/`:` to construct a `Date` object.

### Lines with no direction prefix
`parseLine()` returns `{ dir: null, ts: ... }` — rendered with an amber `?` badge. This handles any manually authored lines or edge cases.

---

## 8. In-Memory State

| Variable | Type | Description |
|---|---|---|
| `dirHandle` | `FileSystemDirectoryHandle` | Active workspace root |
| `fileHandle` | `FileSystemFileHandle` | Active punch log file |
| `lines[]` | `string[]` | Raw lines mirroring file content |

`lines[]` is the canonical in-memory source. Every write operation (`appendLine`, `rewriteFile`) keeps `lines[]` and the file in sync. `render()` always reads from `lines[]`, never re-reads the file.

---

## 9. Security Model

- **Origin-isolated:** File access is scoped to the selected directory only. No path traversal.
- **User-gesture gated:** `showDirectoryPicker()` requires a direct user interaction — cannot be auto-triggered.
- **No exfiltration surface:** No network requests are made. No data leaves the device.
- **Session-scoped:** Handles are not serialized or stored. Browser close = access revoked.

---

## 10. Known Limitations & Edge Cases

| Issue | Status |
|---|---|
| External file mutation during session | No detection — `lines[]` will be stale until reload |
| Filename collision on `+ New File` | Opens existing file silently (no warning) |
| Subdirectory `.txt` files | Not listed — only root of selected folder |
| Firefox support | File System Access API not supported — app non-functional |
| Concurrent tab access | Race condition on `rewriteFile()` — single-user assumption |
| Odd punch count (unpaired IN/OUT) | Total calculation silently ignores the trailing unpaired entry |

---

*SimplePunch `dev/specs/` — workspace-file-ops.md*
