<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$events_table = $wpdb->prefix . 'sp_events';
$current_user = get_current_user_id();
$is_admin     = current_user_can('manage_options');

// Fetch events for dropdown
if ($is_admin) {
    // Admin: see all events + owner name
    $events = $wpdb->get_results("
        SELECT e.*, u.display_name 
        FROM {$events_table} e
        LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
        ORDER BY e.event_date DESC
    ");
} else {
    // Planner: only own events
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
?>
<div class="wrap sp-wrap">
    <h1 class="sp-title"><?php esc_html_e('Guest Manager', 'wp-seating-planner'); ?></h1>

    <?php if (empty($events)): ?>
        <p>
            <?php esc_html_e('No events found. Please create an event first under Seating Planner → Seating Planner.', 'wp-seating-planner'); ?>
        </p>
        <?php return; ?>
    <?php endif; ?>

    <div class="sp-guest-toolbar" style="margin-bottom:15px;">
        <label for="sp-guest-event">
            <?php esc_html_e('Select Event:', 'wp-seating-planner'); ?>
        </label>

        <select id="sp-guest-event">
            <?php foreach ($events as $event): ?>
                <?php
                $date_label = $event->event_date ? $event->event_date : '';
                if ($is_admin) {
                    // Option B: "Title (2025-11-30) — Sujatha (Planner)"
                    $owner = $event->display_name ? $event->display_name : __('Unknown', 'wp-seating-planner');
                    $label = sprintf(
                        '%s (%s) — %s (%s)',
                        $event->title,
                        $date_label,
                        $owner,
                        __('Planner', 'wp-seating-planner')
                    );
                } else {
                    // Planner: no owner label
                    $label = sprintf('%s (%s)', $event->title, $date_label);
                }
                ?>
                <option value="<?php echo intval($event->id); ?>">
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button id="sp-add-guest" class="button button-primary">
            <?php esc_html_e('Add Guest', 'wp-seating-planner'); ?>
        </button>

        <button id="sp-import-guests" class="button">
            <?php esc_html_e('Import CSV', 'wp-seating-planner'); ?>
        </button>

        <button id="sp-export-guests" class="button">
            <?php esc_html_e('Export CSV', 'wp-seating-planner'); ?>
        </button>

        <button id="sp-print-guests" class="button">
            <?php esc_html_e('Print / PDF', 'wp-seating-planner'); ?>
        </button>

        <!-- Hidden file input for CSV import -->
        <input type="file" id="sp-import-file" accept=".csv" style="display:none;" />
    </div>

    <h2><?php esc_html_e('Guest List', 'wp-seating-planner'); ?></h2>

<?php
// Determine selected event
$selected_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : intval($events[0]->id);

// Fetch guests for this event
$guests = $wpdb->get_results(
    $wpdb->prepare("
        SELECT *
        FROM {$wpdb->prefix}sp_guests
        WHERE event_id = %d
        ORDER BY last_name, first_name
    ", $selected_event_id)
);
?>

<table id="sp-guest-table" class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Party / Group</th>
            <th>Meal Type</th>
            <th>Notes</th>
            <th>Child?</th>

            <!-- Action column hidden only on print -->
            <th class="sp-no-print">Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($guests as $guest): ?>
            <tr data-id="<?= esc_attr($guest->id); ?>"
                data-first-name="<?= esc_attr($guest->first_name); ?>"
                data-last-name="<?= esc_attr($guest->last_name); ?>"
                data-party="<?= esc_attr($guest->party); ?>"
                data-meal="<?= esc_attr($guest->meal); ?>"
                data-notes="<?= esc_attr($guest->notes); ?>"
                data-is-child="<?= esc_attr($guest->is_child); ?>">

                <td><?= esc_html($guest->first_name); ?></td>
                <td><?= esc_html($guest->last_name); ?></td>
                <td><?= esc_html($guest->party); ?></td>
                <td><?= esc_html($guest->meal); ?></td>
                <td><?= esc_html($guest->notes); ?></td>

                <!-- Fix Yes/No -->
                <td><?= $guest->is_child ? 'Yes' : 'No'; ?></td>

                <!-- Action buttons column (print-hidden) -->
                <td class="sp-no-print">
                    <button type="button" class="button button-small sp-guest-edit sp-no-print">Edit</button>
                    <button type="button" class="button button-small sp-guest-delete sp-no-print">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    <p style="margin-top:15px; max-width:600px; font-size:12px; color:#666;">
        <strong><?php esc_html_e('CSV format for import:', 'wp-seating-planner'); ?></strong>
        <?php esc_html_e('first_name,last_name,party,is_child,meal,notes', 'wp-seating-planner'); ?>
    </p>
</div>
