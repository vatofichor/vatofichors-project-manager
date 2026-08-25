# SimplePunch

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A zero-dependency, browser-based punch clock. Drop it, open it, done.

---

## Requirements

**None.** Any modern browser with File System Access API support (Chrome, Edge). No install, no server, no build step.

---

## Install

1. Download and unzip the release archive
2. Open `index.html` in your browser
3. Click **Select Folder** → pick a folder on your machine to store punch files
4. Use **+ New File** to create a dated `.txt` punch log
5. **Clock In / Clock Out** — timestamps are written directly to the file

---

## PHP Server Mode (optional)

Removed. Just run your local php server if you have it and want it. Not necessary.

---

## How It Works

- Timestamps are written in `MM-DD-YY_HH_MM_SS` format, one per line
- Pairs of punches (In → Out) are summed to compute total hours
- All data stays on your machine — nothing is sent anywhere
- Punch files are plain `.txt`, readable in any text editor

---

## Copyright

```
Copyright (c) 2026 vatofichor (Sebastian Mass)
Released under the MIT License — see LICENSE for details.
```
