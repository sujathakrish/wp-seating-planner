jQuery(function ($) {

    const canvas = $("#sp-layout-canvas");
    const eventId = SP_LAYOUT.eventId;
    const apiRoot = SP_LAYOUT.root;
    const nonce = SP_LAYOUT.nonce;

    // -------------------------------
    // Load existing tables
    // -------------------------------
    loadTables();

    function loadTables() {
        $.ajax({
            url: apiRoot + "tables/" + eventId,
            method: "GET",
            headers: { "X-WP-Nonce": nonce },
            success: function (tables) {
                canvas.empty();
                tables.forEach(t => addTableToCanvas(t));
            }
        });
    }

    // -------------------------------
    // Add tables
    // -------------------------------
    $("#sp-add-round").on("click", function () {
        const table = {
            id: 0,
            event_id: eventId,
            shape: "round",
            label: "Table",
            x: 200,
            y: 100,
            width: 120,
            height: 120
        };
        saveNewTable(table);
    });

    $("#sp-add-rect").on("click", function () {
        const table = {
            id: 0,
            event_id: eventId,
            shape: "rect",
            label: "Table",
            x: 300,
            y: 150,
            width: 160,
            height: 90
        };
        saveNewTable(table);
    });

    function saveNewTable(table) {
        $.ajax({
            url: apiRoot + "tables",
            method: "POST",
            headers: { "X-WP-Nonce": nonce },
            data: table,
            success: function () {
                loadTables();
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
            el.addClass("rect");
        }

        el.css({
            top: t.y + "px",
            left: t.x + "px",
            width: t.width + "px",
            height: t.height + "px",
            lineHeight: t.height + "px"
        });

        el.text(t.label);

        // Delete button
        const del = $("<div class='sp-delete-table'>×</div>");
        el.append(del);

        del.on("click", function (e) {
            e.stopPropagation();
            deleteTable(t.id);
        });

        el.draggable({
            containment: "parent",
            stop: function (event, ui) {
                t.x = ui.position.left;
                t.y = ui.position.top;
            }
        });

        canvas.append(el);
    }

    function deleteTable(id) {
        $.ajax({
            url: apiRoot + "tables/" + id,
            method: "DELETE",
            headers: { "X-WP-Nonce": nonce },
            success: loadTables
        });
    }

    // -------------------------------
    // Save All Layout
    // -------------------------------
    $("#sp-save-layout").on("click", function () {
        const tables = [];

        $(".sp-table").each(function () {
            const el = $(this);
            tables.push({
                id: el.data("id"),
                event_id: eventId,
                x: parseInt(el.css("left")),
                y: parseInt(el.css("top")),
                width: el.width(),
                height: el.height()
            });
        });

        $.ajax({
            url: apiRoot + "tables/bulk/" + eventId,
            method: "POST",
            headers: { "X-WP-Nonce": nonce },
            data: { tables: tables },
            success: function () {
                alert("Layout saved!");
            }
        });
    });

    // -------------------------------
    // Auto-Seat (future extension)
    // -------------------------------
    $("#sp-auto-seat").on("click", function () {
        alert("Auto-seat logic will be implemented next.");
    });

});
