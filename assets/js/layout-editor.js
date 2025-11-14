jQuery(function ($) {
    if (typeof SP_LAYOUT === "undefined" || !SP_LAYOUT.eventId) {
        return; // No event selected, nothing to do
    }

    const canvas = $("#sp-layout-canvas");
    const eventId = SP_LAYOUT.eventId;
    const apiRoot = SP_LAYOUT.root;      // e.g. /wp-json/sp/v1/
    const nonce  = SP_LAYOUT.nonce;

    // -------------------------------
    // Helpers
    // -------------------------------
    function toast(msg) {
        // Simple console + alert fallback
        console.log("[SP_LAYOUT]", msg);
    }

    function ajax(options) {
        options = options || {};
        options.headers = options.headers || {};
        options.headers["X-WP-Nonce"] = nonce;
        $.ajax(options);
    }

    // -------------------------------
    // Load existing tables
    // -------------------------------
    function loadTables() {
        ajax({
            url: apiRoot + "tables/" + eventId,
            method: "GET",
            success: function (tables) {
                canvas.empty();
                (tables || []).forEach(function (t) {
                    addTableToCanvas(t);
                });
            },
            error: function (xhr) {
                console.error("Failed to load tables", xhr);
            }
        });
    }

    // -------------------------------
    // Add tables
    // -------------------------------
    $("#sp-add-round").on("click", function (e) {
        e.preventDefault();
        const table = {
            event_id: eventId,
            shape: "round",
            label: "Table",
            x: 200,
            y: 100,
            width: 120,
            height: 120,
            capacity: 8
        };
        saveNewTable(table);
    });

    $("#sp-add-rect").on("click", function (e) {
        e.preventDefault();
        const table = {
            event_id: eventId,
            shape: "rect",
            label: "Table",
            x: 300,
            y: 150,
            width: 160,
            height: 90,
            capacity: 10
        };
        saveNewTable(table);
    });

    function saveNewTable(table) {
        ajax({
            url: apiRoot + "add-table",   // <-- matches class-sp-rest.php
            method: "POST",
            data: table,
            success: function () {
                toast("Table added");
                loadTables();
            },
            error: function (xhr) {
                console.error("Failed to add table", xhr);
                alert("Error adding table.");
            }
        });
    }

    // -------------------------------
    // Build draggable DOM elements
    // -------------------------------
    function addTableToCanvas(t) {
        const el = $("<div class='sp-table'></div>");
        el.attr("data-id", t.id);

        if (t.shape === "rect") {
            el.addClass("sp-table-rect");
        } else {
            el.addClass("sp-table-round");
        }

        const labelText = t.label || ("Table " + t.id);
        el.text(labelText);

        el.css({
            top: (t.y || 0) + "px",
            left: (t.x || 0) + "px",
            width: (t.width || 120) + "px",
            height: (t.height || 120) + "px",
            lineHeight: (t.height || 120) + "px"
        });

        // Delete button
        const del = $("<button type='button' class='sp-table-delete'>&times;</button>");
        el.append(del);

        del.on("click", function (e) {
            e.stopPropagation();
            if (!confirm("Delete this table?")) return;
            deleteTable(t.id);
        });

        // Draggable
        el.draggable({
            containment: "parent"
        });

        canvas.append(el);
    }

    function deleteTable(id) {
        ajax({
            url: apiRoot + "table/" + id,   // <-- matches class-sp-rest.php
            method: "DELETE",
            success: function () {
                toast("Table deleted");
                loadTables();
            },
            error: function (xhr) {
                console.error("Failed to delete table", xhr);
                alert("Error deleting table.");
            }
        });
    }

    // -------------------------------
    // Save All Layout
    // -------------------------------
    $("#sp-save-layout").on("click", function (e) {
        e.preventDefault();

        const tables = [];
        $(".sp-table").each(function () {
            const el = $(this);
            tables.push({
                id: el.data("id"),
                event_id: eventId,
                x: parseInt(el.css("left"), 10) || 0,
                y: parseInt(el.css("top"), 10) || 0,
                width: el.outerWidth(),
                height: el.outerHeight()
            });
        });

        ajax({
            url: apiRoot + "save-layout",   // <-- matches class-sp-rest.php
            method: "POST",
            data: {
                event_id: eventId,
                tables: tables
            },
            traditional: true, // safer with arrays in jQuery
            success: function (res) {
                toast("Layout saved");
                alert("Layout saved successfully.");
            },
            error: function (xhr) {
                console.error("Failed to save layout", xhr);
                alert("Error saving layout.");
            }
        });
    });

    // -------------------------------
    // Auto-Seat
    // -------------------------------
    $("#sp-auto-seat").on("click", function (e) {
        e.preventDefault();

        if (!confirm("Auto-seat guests by party into the existing tables?")) {
            return;
        }

        ajax({
            url: apiRoot + "auto-seat",
            method: "POST",
            data: { event_id: eventId },
            success: function (res) {
                console.log("Auto-seat result:", res);
                alert(res && res.message ? res.message : "Auto-seat suggestion generated. (Not yet persisted.)");
            },
            error: function (xhr) {
                console.error("Failed to auto-seat", xhr);
                alert("Error running auto-seat.");
            }
        });
    });

    // Initial load
    loadTables();
});
