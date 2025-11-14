<?php
/**
 * Layout Editor Admin Page
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

$current_user = get_current_user_id();
$is_admin     = current_user_can('manage_options');

$events_table = $wpdb->prefix . 'sp_events';

// Validate event_id
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if (!$event_id) {
    echo '<div class="wrap"><h1>Layout Editor</h1>';
    echo '<p style="color:#d63638;font-weight:bold;">No event selected.</p>';
    echo '</div>';
    return;
}

// Fetch event
$event = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $events_table WHERE id = %d", $event_id)
);

// Validate ownership
if (!$event || (!$is_admin && $event->user_id != $current_user)) {
    echo '<div class="wrap"><h1>Layout Editor</h1>';
    echo '<p style="color:#d63638;font-weight:bold;">Event not found or access denied.</p>';
    echo '</div>';
    return;
}

// Enqueue scripts
wp_enqueue_script('jquery-ui-draggable');
wp_enqueue_script(
    'sp-layout-editor',
    SP_PLUGIN_URL . 'assets/js/layout-editor.js',
    ['jquery', 'jquery-ui-draggable'],
    filemtime(SP_PLUGIN_DIR . 'assets/js/layout-editor.js'),
    true
);

// Localize data
wp_localize_script('sp-layout-editor', 'SP_LAYOUT', [
    'eventId'     => $event_id,
    'eventTitle'  => $event->title,
    'nonce'       => wp_create_nonce('wp_rest'),
    'root'        => esc_url_raw(rest_url('sp/v1/'))
]);

// Load admin CSS
wp_enqueue_style(
    'sp-admin-css',
    SP_PLUGIN_URL . 'assets/css/admin.css',
    [],
    filemtime(SP_PLUGIN_DIR . 'assets/css/admin.css')
);
?>
<div class="wrap sp-wrap">
    <h1>Layout Editor: <?php echo esc_html($event->title); ?></h1>

    <div class="sp-toolbar">
        <button id="sp-add-round" class="button button-primary">➕ Add Round Table</button>
        <button id="sp-add-rect" class="button button-primary">➕ Add Rectangle Table</button>
        <button id="sp-auto-seat" class="button">⚡ Auto-Seat by Party</button>
        <button id="sp-save-layout" class="button button-primary">💾 Save Layout</button>
        <button id="sp-export-pdf" class="button">📄 Export PDF</button>
    </div>

    <div class="layout-container">
        <div id="sp-layout-canvas" class="layout-canvas">
            <!-- Tables injected by JS -->
        </div>
    </div>

    <p style="margin-top:20px;color:#555;">
        Drag tables to position them. You can resize rectangle tables using corner grab handles.
    </p>
</div>

<style>
.layout-container {
    border: 2px solid #ccc;
    background: #f9f9f9;
    height: 760px;
    overflow: auto;
    border-radius: 8px;
}

.layout-canvas {
    width: 2000px;
    height: 1400px;
    position: relative;
}

.sp-table {
    position: absolute;
    border: 2px solid #0073aa;
    background: #e6f2ff;
    border-radius: 50%;
    text-align: center;
    cursor: move;
    padding-top: 32px;
    font-weight: bold;
}

.sp-table.rect {
    border-radius: 6px;
    background: #fff5d9;
}

.sp-delete-table {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #cc0000;
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    text-align: center;
    line-height: 18px;
    cursor: pointer;
    font-size: 10px;
}
</style>
<?php
/**
 * Layout Editor Admin Page
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

$current_user = get_current_user_id();
$is_admin     = current_user_can('manage_options');

$events_table = $wpdb->prefix . 'sp_events';

// Validate event_id
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if (!$event_id) {
    echo '<div class="wrap"><h1>Layout Editor</h1>';
    echo '<p style="color:#d63638;font-weight:bold;">No event selected.</p>';
    echo '</div>';
    return;
}

// Fetch event
$event = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $events_table WHERE id = %d", $event_id)
);

// Validate ownership
if (!$event || (!$is_admin && $event->user_id != $current_user)) {
    echo '<div class="wrap"><h1>Layout Editor</h1>';
    echo '<p style="color:#d63638;font-weight:bold;">Event not found or access denied.</p>';
    echo '</div>';
    return;
}

// Enqueue scripts
wp_enqueue_script('jquery-ui-draggable');
wp_enqueue_script(
    'sp-layout-editor',
    SP_PLUGIN_URL . 'assets/js/layout-editor.js',
    ['jquery', 'jquery-ui-draggable'],
    filemtime(SP_PLUGIN_DIR . 'assets/js/layout-editor.js'),
    true
);

// Localize data
wp_localize_script('sp-layout-editor', 'SP_LAYOUT', [
    'eventId'     => $event_id,
    'eventTitle'  => $event->title,
    'nonce'       => wp_create_nonce('wp_rest'),
    'root'        => esc_url_raw(rest_url('sp/v1/'))
]);

// Load admin CSS
wp_enqueue_style(
    'sp-admin-css',
    SP_PLUGIN_URL . 'assets/css/admin.css',
    [],
    filemtime(SP_PLUGIN_DIR . 'assets/css/admin.css')
);
?>
<div class="wrap sp-wrap">
    <h1>Layout Editor: <?php echo esc_html($event->title); ?></h1>

    <div class="sp-toolbar">
        <button id="sp-add-round" class="button button-primary">➕ Add Round Table</button>
        <button id="sp-add-rect" class="button button-primary">➕ Add Rectangle Table</button>
        <button id="sp-auto-seat" class="button">⚡ Auto-Seat by Party</button>
        <button id="sp-save-layout" class="button button-primary">💾 Save Layout</button>
        <button id="sp-export-pdf" class="button">📄 Export PDF</button>
    </div>

    <div class="layout-container">
        <div id="sp-layout-canvas" class="layout-canvas">
            <!-- Tables injected by JS -->
        </div>
    </div>

    <p style="margin-top:20px;color:#555;">
        Drag tables to position them. You can resize rectangle tables using corner grab handles.
    </p>
</div>

<style>
.layout-container {
    border: 2px solid #ccc;
    background: #f9f9f9;
    height: 760px;
    overflow: auto;
    border-radius: 8px;
}

.layout-canvas {
    width: 2000px;
    height: 1400px;
    position: relative;
}

.sp-table {
    position: absolute;
    border: 2px solid #0073aa;
    background: #e6f2ff;
    border-radius: 50%;
    text-align: center;
    cursor: move;
    padding-top: 32px;
    font-weight: bold;
}

.sp-table.rect {
    border-radius: 6px;
    background: #fff5d9;
}

.sp-delete-table {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #cc0000;
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    text-align: center;
    line-height: 18px;
    cursor: pointer;
    font-size: 10px;
}
</style>
