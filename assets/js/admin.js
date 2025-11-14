/* global SP_API */

document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const page = params.get('page') || '';

    console.log('SP Admin JS Loaded');

    if (!window.SP_API) {
        console.warn('SP_API not defined – REST calls will fail.');
        return;
    }

    if (page === 'sp_events') {
        initEventsPage();
    } else if (page === 'sp_guests') {
        initGuestManagerPage();
    }
    // Layout editor uses its own JS file (layout-editor.js)
});

/* ----------------------------------------------------------
 *  EVENTS PAGE
 * ------------------------------------------------------- */

function initEventsPage() {
    const addBtn   = document.getElementById('sp-add-event');
    const editor   = document.getElementById('sp-event-editor');
    const cancel   = document.getElementById('sp-cancel-event');
    const saveBtn  = document.getElementById('sp-save-event');
    const listWrap = document.getElementById('sp-event-list');
    const cleanup  = document.getElementById('sp-run-cleanup');

    if (!listWrap) return;

    console.log('Initializing Events Page');

    if (addBtn && editor) {
        addBtn.addEventListener('click', function () {
            openEventEditor();
        });
    }

    if (cancel && editor) {
        cancel.addEventListener('click', function () {
            editor.style.display = 'none';
            document.getElementById('sp-event-id').value = '0';
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', saveEventFromForm);
    }

    if (cleanup) {
        cleanup.addEventListener('click', runCleanupNow);
    }

    // Edit / Delete buttons in event table
    const table = listWrap.querySelector('table');
    if (table) {
        table.addEventListener('click', function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;

            if (btn.classList.contains('sp-edit-event')) {
                const id = parseInt(btn.dataset.id, 10);
                if (id) loadEventAndEdit(id);
            } else if (btn.classList.contains('sp-delete-event')) {
                const id = parseInt(btn.dataset.id, 10);
                if (id && confirm('Delete this event and all its guests/tables?')) {
                    deleteEvent(id);
                }
            }
        });
    }
}

function openEventEditor(eventData) {
    const editor = document.getElementById('sp-event-editor');
    const heading = document.getElementById('sp-editor-heading');
    const idField = document.getElementById('sp-event-id');
    const titleField = document.getElementById('sp-event-title');
    const dateField = document.getElementById('sp-event-date');
    const notesField = document.getElementById('sp-event-notes');

    if (!editor) return;

    if (eventData) {
        heading.textContent = 'Edit Event';
        idField.value = eventData.id || 0;
        titleField.value = eventData.title || '';
        dateField.value = eventData.event_date || '';
        notesField.value = eventData.notes || '';
    } else {
        heading.textContent = 'Add Event';
        idField.value = '0';
        titleField.value = '';
        dateField.value = '';
        notesField.value = '';
    }

    editor.style.display = 'block';
}

async function loadEventAndEdit(id) {
    try {
        const res = await fetch(SP_API.root + 'get-event/' + id, {
            headers: { 'X-WP-Nonce': SP_API.nonce }
        });
        if (!res.ok) throw new Error('Failed to load event');
        const data = await res.json();
        openEventEditor(data);
    } catch (err) {
        console.error(err);
        alert('Error loading event details.');
    }
}

async function saveEventFromForm() {
    const id    = parseInt(document.getElementById('sp-event-id').value || '0', 10);
    const title = document.getElementById('sp-event-title').value.trim();
    const date  = document.getElementById('sp-event-date').value.trim();
    const notes = document.getElementById('sp-event-notes').value.trim();

    console.log('Saving Event:', { id, title, date, notes });

    if (!title || !date) {
        alert('Title and Date are required.');
        return;
    }

    try {
        const res = await fetch(SP_API.root + 'save-event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': SP_API.nonce
            },
            body: JSON.stringify({ id, title, event_date: date, notes })
        });

        if (!res.ok) throw new Error('Request failed');
        const data = await res.json();
        console.log('Event Saved:', data);
        alert('Event saved successfully!');
        window.location.reload();
    } catch (err) {
        console.error(err);
        alert('Error saving event. Check console.');
    }
}

async function deleteEvent(id) {
    try {
        const res = await fetch(SP_API.root + 'delete-event/' + id, {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': SP_API.nonce }
        });
        if (!res.ok) throw new Error('Failed to delete');
        alert('Event deleted.');
        window.location.reload();
    } catch (err) {
        console.error(err);
        alert('Error deleting event.');
    }
}

async function runCleanupNow() {
    if (!confirm('Run cleanup now? This will delete events older than 30 days and orphaned guests/tables.')) {
        return;
    }

    try {
        const res = await fetch(SP_API.root + 'cleanup', {
            method: 'POST',
            headers: { 'X-WP-Nonce': SP_API.nonce }
        });
        if (!res.ok) throw new Error('Cleanup failed');
        alert('Cleanup completed.');
        window.location.reload();
    } catch (err) {
        console.error(err);
        alert('Error running cleanup.');
    }
}

/* ----------------------------------------------------------
 *  GUEST MANAGER PAGE
 * ------------------------------------------------------- */

let SP_CURRENT_GUESTS = [];
let SP_CURRENT_EVENT_ID = null;

function initGuestManagerPage() {
    console.log('Initializing Guest Manager Page');

    const eventSelect = document.getElementById('sp-guest-event');
    const addBtn      = document.getElementById('sp-add-guest');
    const importBtn   = document.getElementById('sp-import-guests');
    const exportBtn   = document.getElementById('sp-export-guests');
    const printBtn    = document.getElementById('sp-print-guests');
    const fileInput   = document.getElementById('sp-import-file');
    const table       = document.getElementById('sp-guest-table');

    if (!eventSelect || !table) return;

    SP_CURRENT_EVENT_ID = parseInt(eventSelect.value, 10) || null;

    // Load guests on startup
    if (SP_CURRENT_EVENT_ID) {
        loadGuests(SP_CURRENT_EVENT_ID);
    }

    eventSelect.addEventListener('change', function () {
        const id = parseInt(this.value, 10) || null;
        SP_CURRENT_EVENT_ID = id;
        if (id) loadGuests(id);
    });

    // Add guest -> create inline edit row at top
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            if (!SP_CURRENT_EVENT_ID) {
                alert('Please select an event first.');
                return;
            }
            createNewGuestRow();
        });
    }

    // Table delegated events: Edit / Delete / Save / Cancel
    const tbody = table.querySelector('tbody');
    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const row = btn.closest('tr');
        if (!row) return;

        if (btn.classList.contains('sp-guest-edit')) {
            enterGuestEditMode(row);
        } else if (btn.classList.contains('sp-guest-delete')) {
            const id = parseInt(row.dataset.id || '0', 10);
            if (id && confirm('Delete this guest?')) {
                deleteGuest(id);
            }
        } else if (btn.classList.contains('sp-guest-save')) {
            saveGuestFromRow(row);
        } else if (btn.classList.contains('sp-guest-cancel')) {
            loadGuests(SP_CURRENT_EVENT_ID);
        }
    });

    // Import CSV
    if (importBtn && fileInput) {
        importBtn.addEventListener('click', function () {
            if (!SP_CURRENT_EVENT_ID) {
                alert('Please select an event first.');
                return;
            }
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                importGuestsFromCSV(this.files[0]);
            }
            this.value = '';
        });
    }

    // Export CSV
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            exportGuestsToCSV();
        });
    }

    // Print / PDF
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            printGuestList();
        });
    }
}

async function loadGuests(eventId) {
    const tbody = document.querySelector('#sp-guest-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="7">Loading guests…</td></tr>';

    try {
        const res = await fetch(SP_API.root + 'guests/' + eventId, {
            headers: { 'X-WP-Nonce': SP_API.nonce }
        });
        if (!res.ok) throw new Error('Failed to load guests');
        const data = await res.json();
        SP_CURRENT_GUESTS = Array.isArray(data) ? data : [];
        renderGuestTable(SP_CURRENT_GUESTS);
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="7">Error loading guests.</td></tr>';
    }
}

// Renders read-only table rows (inline edit will modify specific row)
function renderGuestTable(guests) {
    const tbody = document.querySelector('#sp-guest-table tbody');
    if (!tbody) return;

    if (!guests || guests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">No guests found for this event.</td></tr>';
        return;
    }

    tbody.innerHTML = '';

    guests.forEach(function (g) {
        const tr = document.createElement('tr');
        tr.dataset.id = g.id;
        tr.dataset.firstName = g.first_name || '';
        tr.dataset.lastName  = g.last_name || '';
        tr.dataset.party     = g.party || '';
        tr.dataset.meal      = g.meal || '';
        tr.dataset.notes     = g.notes || '';
        tr.dataset.isChild   = (g.is_child == 1 ? '1' : '0');

        tr.innerHTML = `
            <td>${escapeHtml(g.first_name || '')}</td>
            <td>${escapeHtml(g.last_name || '')}</td>
            <td>${escapeHtml(g.party || '')}</td>
            <td>${escapeHtml(g.meal || '')}</td>
            <td>${escapeHtml(g.notes || '')}</td>
            <td>${parseInt(g.is_child, 10) === 1 ? 'Yes' : 'No'}</td>
            <td>
                <button type="button" class="button button-small sp-guest-edit">Edit</button>
                <button type="button" class="button button-small sp-guest-delete">Delete</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Creates a new top row in edit mode
function createNewGuestRow() {
    const tbody = document.querySelector('#sp-guest-table tbody');
    if (!tbody) return;

    // Remove any existing edit rows before creating a new one
    const editingRow = tbody.querySelector('.sp-editing');
    if (editingRow) {
        editingRow.remove();
    }

    const tr = document.createElement('tr');
    tr.classList.add('sp-editing');
    tr.dataset.id = '0';
    tr.dataset.firstName = '';
    tr.dataset.lastName  = '';
    tr.dataset.party     = '';
    tr.dataset.meal      = '';
    tr.dataset.notes     = '';
    tr.dataset.isChild   = '0';

    // Insert new editing row at top
    tbody.insertBefore(tr, tbody.firstChild);

    // Turn this new row into edit mode
    enterGuestEditMode(tr);
}


function enterGuestEditMode(row) {
    if (!row) return;
     
    row.classList.add('sp-editing');
 
    const first = row.dataset.firstName || '';
    const last  = row.dataset.lastName || '';
    const party = row.dataset.party || '';
    const meal  = row.dataset.meal || '';
    const notes = row.dataset.notes || '';
    const isChild = row.dataset.isChild === '1';
    
    row.innerHTML = `
        <td><input type="text" class="sp-g-first" value="${escapeAttr(first)}" /></td>
        <td><input type="text" class="sp-g-last" value="${escapeAttr(last)}" /></td>
        <td><input type="text" class="sp-g-party" value="${escapeAttr(party)}" /></td>
        <td><input type="text" class="sp-g-meal" value="${escapeAttr(meal)}" /></td>
        <td><input type="text" class="sp-g-notes" value="${escapeAttr(notes)}" /></td>
        <td>
            <label>
                <input type="checkbox" class="sp-g-child" ${isChild ? 'checked' : ''} />
                Child
            </label>
        </td>
        <td>
            <button type="button" class="button button-small button-primary sp-guest-save">Save</button>
            <button type="button" class="button button-small sp-guest-cancel">Cancel</button>
        </td>
    `;
}

async function saveGuestFromRow(row) {
    if (!row || !SP_CURRENT_EVENT_ID) return;

    const id = parseInt(row.dataset.id || '0', 10);

    const first = row.querySelector('.sp-g-first').value.trim();
    const last  = row.querySelector('.sp-g-last').value.trim();
    const party = row.querySelector('.sp-g-party').value.trim();
    const meal  = row.querySelector('.sp-g-meal').value.trim();
    const notes = row.querySelector('.sp-g-notes').value.trim();
    const child = row.querySelector('.sp-g-child').checked ? 1 : 0;

    if (!first && !last) {
        alert('Please provide at least a first or last name.');
        return;
    }

    const payload = {
        id: id,
        event_id: SP_CURRENT_EVENT_ID,
        first_name: first,
        last_name: last,
        party: party,
        meal: meal,
        notes: notes,
        is_child: child
    };

    try {
        const res = await fetch(SP_API.root + 'guest/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': SP_API.nonce
            },
            body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error('Save failed');
        const data = await res.json();
        console.log('Guest saved', data);
        loadGuests(SP_CURRENT_EVENT_ID);
    } catch (err) {
        console.error(err);
        alert('Error saving guest.');
    }
}

async function deleteGuest(id) {
    try {
        const res = await fetch(SP_API.root + 'guest/' + id, {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': SP_API.nonce }
        });
        if (!res.ok) throw new Error('Delete failed');
        const data = await res.json();
        console.log('Guest deleted', data);
        loadGuests(SP_CURRENT_EVENT_ID);
    } catch (err) {
        console.error(err);
        alert('Error deleting guest.');
    }
}

/* ----------------------------------------------------------
 *  CSV IMPORT / EXPORT / PRINT
 * ------------------------------------------------------- */

function importGuestsFromCSV(file) {
    if (!SP_CURRENT_EVENT_ID) {
        alert('No event selected.');
        return;
    }
    const reader = new FileReader();
    reader.onload = async function (e) {
        const text = e.target.result || '';
        const lines = text.split(/\r?\n/).filter(l => l.trim() !== '');
        if (lines.length < 2) {
            alert('CSV appears to be empty.');
            return;
        }

        // Expect header: first_name,last_name,party,is_child,meal,notes
        const header = lines[0].split(',').map(h => h.trim().toLowerCase());
        const idx = {
            first_name: header.indexOf('first_name'),
            last_name:  header.indexOf('last_name'),
            party:      header.indexOf('party'),
            is_child:   header.indexOf('is_child'),
            meal:       header.indexOf('meal'),
            notes:      header.indexOf('notes')
        };

        let imported = 0;

        for (let i = 1; i < lines.length; i++) {
            const cols = lines[i].split(',');
            if (!cols.length || !cols.join('').trim()) continue;

            const payload = {
                id: 0,
                event_id: SP_CURRENT_EVENT_ID,
                first_name: idx.first_name >= 0 ? (cols[idx.first_name] || '').trim() : '',
                last_name:  idx.last_name  >= 0 ? (cols[idx.last_name]  || '').trim() : '',
                party:      idx.party      >= 0 ? (cols[idx.party]      || '').trim() : '',
                meal:       idx.meal       >= 0 ? (cols[idx.meal]       || '').trim() : '',
                notes:      idx.notes      >= 0 ? (cols[idx.notes]      || '').trim() : '',
                is_child:   idx.is_child   >= 0 ? ((cols[idx.is_child]  || '').trim() === '1' ? 1 : 0) : 0
            };

            if (!payload.first_name && !payload.last_name) continue;

            try {
                const res = await fetch(SP_API.root + 'guest/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': SP_API.nonce
                    },
                    body: JSON.stringify(payload)
                });
                if (res.ok) imported++;
            } catch (err) {
                console.error('Error importing guest from CSV line', i + 1, err);
            }
        }

        alert('Imported ' + imported + ' guests from CSV.');
        loadGuests(SP_CURRENT_EVENT_ID);
    };
    reader.readAsText(file);
}

function exportGuestsToCSV() {
    if (!SP_CURRENT_GUESTS || SP_CURRENT_GUESTS.length === 0) {
        alert('No guests to export.');
        return;
    }

    const rows = [];
    rows.push(['first_name', 'last_name', 'party', 'is_child', 'meal', 'notes']);

    SP_CURRENT_GUESTS.forEach(function (g) {
        rows.push([
            g.first_name || '',
            g.last_name || '',
            g.party || '',
            g.is_child ? '1' : '0',
            g.meal || '',
            (g.notes || '').replace(/\r?\n/g, ' ')
        ]);
    });

    const csv = rows.map(r => r.map(escapeCsv).join(',')).join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = 'guests.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function printGuestList() {
    const table = document.querySelector("#sp-guest-table");
    if (!table) return;

    // Clone table for printing only
    const printTable = table.cloneNode(true);

    // Remove action buttons + column
    printTable.querySelectorAll(".sp-no-print, th.sp-no-print, td.sp-no-print")
        .forEach(el => el.remove());

    // Get Event Info
    const eventSelect = document.getElementById("sp-guest-event");
    const selectedOption = eventSelect.options[eventSelect.selectedIndex].text;

    let eventTitle = selectedOption.split("—")[0].trim();
    let organizer   = selectedOption.includes("—") ? selectedOption.split("—")[1].trim() : "";
    let eventDateMatch = eventTitle.match(/\((.*?)\)/);
    let eventDate = eventDateMatch ? eventDateMatch[1] : "";

    eventTitle = eventTitle.replace(/\(.*?\)/, "").trim();

    // Open print window
    const win = window.open("", "_blank");

    win.document.write(`
        <html>
        <head>
            <title>Guest List</title>
            <link rel="stylesheet" type="text/css"
                  href="${SP_VARS.plugin_url}assets/css/print.css">
        </head>
        <body>

            <h2>Guest List</h2>

            <div style="font-size:14px; margin-bottom:15px;">
                <strong>Event:</strong> ${eventTitle}<br>
                <strong>Date:</strong> ${eventDate}<br>
                <strong>Organizer:</strong> ${organizer}
            </div>

            ${printTable.outerHTML}

        </body>
        </html>
    `);

    win.document.close();

    // 🔥 Wait for CSS + HTML to finish loading before printing
    win.onload = function () {
        setTimeout(() => {
            win.focus();
            win.print();
        }, 200);
    };
}


/* ----------------------------------------------------------
 *  Small helpers
 * ------------------------------------------------------- */

function escapeHtml(str) {
    return (str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeAttr(str) {
    return escapeHtml(str).replace(/"/g, '&quot;');
}

function escapeCsv(str) {
    str = String(str || '');
    if (str.search(/("|,|\n|\r)/g) >= 0) {
        str = '"' + str.replace(/"/g, '""') + '"';
    }
    return str;
}
