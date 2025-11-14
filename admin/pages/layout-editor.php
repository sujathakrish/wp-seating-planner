<?php
/**
 * Layout Editor admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$current_user = get_current_user_id();
$is_admin     = current_user_can('manage_options');
$events_table = $wpdb->prefix . 'sp_events';

// Fetch events for dropdown (similar logic as Events / Guest Manager)
if ($is_admin) {
    $events = $wpdb->get_results("
        SELECT e.*, u.display_name 
        FROM {$events_table} e
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        ORDER BY e.event_date DESC
    ");
} else {
    $events = $wpdb->get_results(
        $wpdb->prepare("
            SELECT e.*, u.display_name 
            FROM {$events_table} e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.user_id = %d
            ORDER BY e.event_date DESC
        ", $current_user)
    );
}

$selected_event_id = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$selected_event    = null;

if ($selected_event_id && !empty($events)) {
    foreach ($events as $ev) {
        if ((int) $ev->id === $selected_event_id) {
            $selected_event = $ev;
            break;
        }
    }
}
?>
<div class="wrap sp-wrap">
    <h1 class="sp-title"><?php esc_html_e('Layout Editor', 'wp-seating-planner'); ?></h1>

    <?php if (empty($events)): ?>
        <p>
            <?php esc_html_e('No events found. Please create an event first under Seating Planner → Seating Planner.', 'wp-seating-planner'); ?>
        </p>
        <?php return; ?>
    <?php endif; ?>

    <!-- Event selector -->
    <form id="sp-layout-event-form" method="get" style="margin-bottom: 15px;">
        <input type="hidden" name="page" value="sp-layout-editor" />
        <label for="sp-layout-event">
            <?php esc_html_e('Select Event:', 'wp-seating-planner'); ?>
        </label>

        <select id="sp-layout-event" name="event_id" onchange="this.form.submit()">
            <option value="0"><?php esc_html_e('-- Choose an event --', 'wp-seating-planner'); ?></option>
            <?php foreach ($events as $event): ?>
                <?php
                $owner = $event->display_name ? $event->display_name : __('Unknown', 'wp-seating-planner');
                $label = sprintf(
                    '%s (%s) — %s (%s)',
                    $event->title,
                    $event->event_date,
                    $owner,
                    __('Planner', 'wp-seating-planner')
                );
                ?>
                <option value="<?php echo (int) $event->id; ?>" <?php selected($selected_event_id, (int) $event->id); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!$selected_event): ?>
        <p><?php esc_html_e('Please select an event above to edit its seating layout.', 'wp-seating-planner'); ?></p>
        <?php return; ?>
    <?php endif; ?>

    <?php
    // Enqueue layout editor assets for this page
    wp_enqueue_script('jquery-ui-draggable');

    // Register script handle if not already
    wp_register_script(
        'sp-layout-editor',
        SP_PLUGIN_URL . 'assets/js/layout-editor.js',
        ['jquery', 'jquery-ui-draggable'],
        filemtime(SP_PLUGIN_DIR . 'assets/js/layout-editor.js'),
        true
    );

    wp_enqueue_script('sp-layout-editor');

    wp_localize_script('sp-layout-editor', 'SP_LAYOUT', [
        'root'    => esc_url_raw(rest_url('sp/v1/')),
        'nonce'   => wp_create_nonce('wp_rest'),
        'eventId' => $selected_event_id,
    ]);
    ?>

    <h2>
        <?php
        printf(
            /* translators: 1: event title, 2: event date */
            esc_html__('Editing layout for "%1$s" (%2$s)', 'wp-seating-planner'),
            esc_html($selected_event->title),
            esc_html($selected_event->event_date)
        );
        ?>
    </h2>

    <div class="sp-layout-toolbar" style="margin: 10px 0;">
        <button id="sp-add-round" class="button button-primary">
            <?php esc_html_e('Add Round Table', 'wp-seating-planner'); ?>
        </button>
        <button id="sp-add-rect" class="button button-primary">
            <?php esc_html_e('Add Rectangular Table', 'wp-seating-planner'); ?>
        </button>
        <button id="sp-save-layout" class="button">
            <?php esc_html_e('Save Layout', 'wp-seating-planner'); ?>
        </button>
        <button id="sp-auto-seat" class="button">
            <?php esc_html_e('Auto-Seat by Party', 'wp-seating-planner'); ?>
        </button>
    </div>

    <div id="sp-layout-wrapper" style="border:1px solid #ccc; background:#f9f9f9; padding:10px; border-radius:6px; height:700px; overflow:auto;">
        <div id="sp-layout-canvas" style="position:relative; width:2000px; height:1200px;">
            <!-- Tables rendered by layout-editor.js -->
        </div>
    </div>

    <p style="margin-top:10px; font-size:12px; color:#666;">
        <?php esc_html_e('Drag tables around to arrange them. Click "Save Layout" to persist positions. Auto-seat will propose a simple grouping by party.', 'wp-seating-planner'); ?>
    </p>
</div>
<?php
/**
 * Layout Editor admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$current_user = get_current_user_id();
$is_admin     = current_user_can('manage_options');
$events_table = $wpdb->prefix . 'sp_events';

// Fetch events for dropdown (similar logic as Events / Guest Manager)
if ($is_admin) {
    $events = $wpdb->get_results("
        SELECT e.*, u.display_name 
        FROM {$events_table} e
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        ORDER BY e.event_date DESC
    ");
} else {
    $events = $wpdb->get_results(
        $wpdb->prepare("
            SELECT e.*, u.display_name 
            FROM {$events_table} e
            LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.user_id = %d
            ORDER BY e.event_date DESC
        ", $current_user)
    );
}

$selected_event_id = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$selected_event    = null;

if ($selected_event_id && !empty($events)) {
    foreach ($events as $ev) {
        if ((int) $ev->id === $selected_event_id) {
            $selected_event = $ev;
            break;
        }
    }
}
?>
<div class="wrap sp-wrap">
    <h1 class="sp-title"><?php esc_html_e('Layout Editor', 'wp-seating-planner'); ?></h1>

    <?php if (empty($events)): ?>
        <p>
            <?php esc_html_e('No events found. Please create an event first under Seating Planner → Seating Planner.', 'wp-seating-planner'); ?>
        </p>
        <?php return; ?>
    <?php endif; ?>

    <!-- Event selector -->
    <form id="sp-layout-event-form" method="get" style="margin-bottom: 15px;">
        <input type="hidden" name="page" value="sp-layout-editor" />
        <label for="sp-layout-event">
            <?php esc_html_e('Select Event:', 'wp-seating-planner'); ?>
        </label>

        <select id="sp-layout-event" name="event_id" onchange="this.form.submit()">
            <option value="0"><?php esc_html_e('-- Choose an event --', 'wp-seating-planner'); ?></option>
            <?php foreach ($events as $event): ?>
                <?php
                $owner = $event->display_name ? $event->display_name : __('Unknown', 'wp-seating-planner');
                $label = sprintf(
                    '%s (%s) — %s (%s)',
                    $event->title,
                    $event->event_date,
                    $owner,
                    __('Planner', 'wp-seating-planner')
                );
                ?>
                <option value="<?php echo (int) $event->id; ?>" <?php selected($selected_event_id, (int) $event->id); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (!$selected_event): ?>
        <p><?php esc_html_e('Please select an event above to edit its seating layout.', 'wp-seating-planner'); ?></p>
        <?php return; ?>
    <?php endif; ?>

    <?php
    // Enqueue layout editor assets for this page
    wp_enqueue_script('jquery-ui-draggable');

    // Register script handle if not already
    wp_register_script(
        'sp-layout-editor',
        SP_PLUGIN_URL . 'assets/js/layout-editor.js',
        ['jquery', 'jquery-ui-draggable'],
        filemtime(SP_PLUGIN_DIR . 'assets/js/layout-editor.js'),
        true
    );

    wp_enqueue_script('sp-layout-editor');

    wp_localize_script('sp-layout-editor', 'SP_LAYOUT', [
        'root'    => esc_url_raw(rest_url('sp/v1/')),
        'nonce'   => wp_create_nonce('wp_rest'),
        'eventId' => $selected_event_id,
    ]);
    ?>

    <h2>
        <?php
        printf(
            /* translators: 1: event title, 2: event date */
            esc_html__('Editing layout for "%1$s" (%2$s)', 'wp-seating-planner'),
            esc_html($selected_event->title),
            esc_html($selected_event->event_date)
        );
        ?>
    </h2>

    <div class="sp-layout-toolbar" style="margin: 10px 0;">
        <button id="sp-add-round" class="button button-primary">
            <?php esc_html_e('Add Round Table', 'wp-seating-planner'); ?>
        </button>
        <button id="sp-add-rect" class="button button-primary">
            <?php esc_html_e('Add Rectangular Table', 'wp-seating-planner'); ?>
        </button>
        <button id="sp-save-layout" class="button">
            <?php esc_html_e('Save Layout', 'wp-seating-planner'); ?>
        </button>
        <button id="sp-auto-seat" class="button">
            <?php esc_html_e('Auto-Seat by Party', 'wp-seating-planner'); ?>
        </button>
    </div>

    <div id="sp-layout-wrapper" style="border:1px solid #ccc; background:#f9f9f9; padding:10px; border-radius:6px; height:700px; overflow:auto;">
        <div id="sp-layout-canvas" style="position:relative; width:2000px; height:1200px;">
            <!-- Tables rendered by layout-editor.js -->
        </div>
    </div>

    <p style="margin-top:10px; font-size:12px; color:#666;">
        <?php esc_html_e('Drag tables around to arrange them. Click "Save Layout" to persist positions. Auto-seat will propose a simple grouping by party.', 'wp-seating-planner'); ?>
    </p>
</div>
