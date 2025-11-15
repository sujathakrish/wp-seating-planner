<?php
/**
 * Layout Editor Admin Page
 * WP Seating Planner
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$current_user = get_current_user_id();
$is_admin     = current_user_can('manage_options');

$events_table = $wpdb->prefix . 'sp_events';

// --------------------------------------------------
// Fetch events user is allowed to see
// --------------------------------------------------
if ($is_admin) {
    // Admin: see all events + owner name
    $events = $wpdb->get_results("
        SELECT e.*, u.display_name AS owner_name
        FROM {$events_table} e
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        ORDER BY e.event_date DESC, e.id DESC
    ");
} else {
    // Planner: see only own events
    $events = $wpdb->get_results(
        $wpdb->prepare("
            SELECT e.*, u.display_name AS owner_name
            FROM {$events_table} e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.user_id = %d
            ORDER BY e.event_date DESC, e.id DESC
        ", $current_user)
    );
}

if (empty($events)) {
    echo '<div class="notice notice-error"><p>'
        . esc_html__('No events found. Please create an event first.', 'wp-seating-planner')
        . '</p></div>';
    return;
}

// --------------------------------------------------
// Determine current event_id
// --------------------------------------------------
$requested_id = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

// Make sure requested event is one of the allowed ones
$event_map = [];
foreach ($events as $e) {
    $event_map[(int) $e->id] = $e;
}

if ($requested_id && isset($event_map[$requested_id])) {
    $current_event = $event_map[$requested_id];
} else {
    // fall back to first event in list
    $current_event = reset($events);
    $requested_id  = (int) $current_event->id;
}

// Nicely formatted label for current event
$current_label = $current_event->title;
if (!empty($current_event->event_date)) {
    $current_label .= ' (' . $current_event->event_date . ')';
}
if ($is_admin && !empty($current_event->owner_name)) {
    $current_label .= ' — ' . $current_event->owner_name . ' (' . __('Planner', 'wp-seating-planner') . ')';
}

// --------------------------------------------------
// Enqueue JS & CSS for the layout editor
// --------------------------------------------------
wp_enqueue_script('jquery-ui-draggable');

wp_enqueue_style(
    'sp-admin-css',
    SP_PLUGIN_URL . 'assets/css/admin.css',
    [],
    filemtime(SP_PLUGIN_DIR . 'assets/css/admin.css')
);

wp_enqueue_script(
    'sp-layout-editor',
    SP_PLUGIN_URL . 'assets/js/layout-editor.js',
    ['jquery', 'jquery-ui-draggable'],
    filemtime(SP_PLUGIN_DIR . 'assets/js/layout-editor.js'),
    true
);

// Localize data expected by assets/js/layout-editor.js
$layout_local = [
    'root'    => esc_url_raw(rest_url('sp/v1/')),
    'nonce'   => wp_create_nonce('wp_rest'),
    'eventId' => $requested_id,
];

wp_localize_script('sp-layout-editor', 'SP_LAYOUT', $layout_local);
// Backwards compatible alias (if any old code used spData)
wp_localize_script('sp-layout-editor', 'spData', $layout_local);
?>

<div class="wrap sp-wrap sp-layout-editor-wrap">
    <h1 class="sp-title">
        <?php esc_html_e('Layout Editor', 'wp-seating-planner'); ?>
    </h1>

    <!-- Event selector -->
    <div class="sp-layout-toolbar" style="margin-bottom: 15px;">
        <label for="sp-layout-event">
            <?php esc_html_e('Select Event:', 'wp-seating-planner'); ?>
        </label>

        <select id="sp-layout-event">
            <?php foreach ($events as $event): ?>
                <?php
                $label = $event->title;
                if (!empty($event->event_date)) {
                    $label .= ' (' . $event->event_date . ')';
                }
                if ($is_admin && !empty($event->owner_name)) {
                    $label .= ' — ' . $event->owner_name . ' (' . __('Planner', 'wp-seating-planner') . ')';
                }
                ?>
                <option value="<?php echo (int) $event->id; ?>"
                    <?php selected((int) $event->id, $requested_id); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <span class="sp-layout-current-label">
            <?php
            printf(
                /* translators: %s = event label */
                esc_html__('Editing Event: %s', 'wp-seating-planner'),
                esc_html($current_label)
            );
            ?>
        </span>
    </div>

    <!-- Layout controls -->
    <div class="sp-layout-toolbar" style="margin-bottom: 15px; display:flex; flex-wrap:wrap; gap:8px;">
        <button id="sp-add-round" class="button button-primary">
            <?php esc_html_e('Add Round Table', 'wp-seating-planner'); ?>
        </button>

        <button id="sp-add-rect" class="button button-primary">
            <?php esc_html_e('Add Rectangle Table', 'wp-seating-planner'); ?>
        </button>

        <button id="sp-auto-seat" class="button">
            <?php esc_html_e('Auto-Seat by Party', 'wp-seating-planner'); ?>
        </button>

        <button id="sp-save-layout" class="button">
            <?php esc_html_e('Save Layout', 'wp-seating-planner'); ?>
        </button>
    </div>

    <!-- Canvas -->
    <div class="sp-layout-container" style="border:1px solid #ddd; background:#f9f9f9; height:700px; overflow:auto;">
        <div id="sp-layout-canvas" class="sp-layout-canvas" style="position:relative; width:2000px; height:1200px;">
            <!-- Tables will be injected here by layout-editor.js -->
        </div>
    </div>

    <p style="margin-top:15px; max-width:600px; font-size:12px; color:#666;">
        <?php esc_html_e('Drag tables around to arrange your floor plan. Use the Save Layout button to persist table positions.', 'wp-seating-planner'); ?>
    </p>
</div>

<script>
    // Change event selector -> reload page with ?event_id=
    (function () {
        const sel = document.getElementById('sp-layout-event');
        if (!sel) return;

        sel.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('event_id', this.value);
            window.location.href = url.toString();
        });
    })();
</script>
