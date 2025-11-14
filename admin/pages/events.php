<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$table = $wpdb->prefix . 'sp_events';
$current_user = get_current_user_id();
$is_admin = current_user_can('manage_options');

// Admin → see all events
// Planner → only their own events
if ($is_admin) {
    $events = $wpdb->get_results("SELECT * FROM $table ORDER BY event_date DESC");
} else {
    $events = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY event_date DESC", $current_user)
    );
}
?>

<div class="wrap sp-wrap">
    <h1 class="sp-title">
        Seating Planner Events
        <button id="sp-add-event" class="button button-primary">Add New Event</button>
    </h1>

    <!-- Cleanup Button -->
    <button id="sp-run-cleanup" class="button">Run Cleanup Now</button>

    <hr />

    <!-- EVENT LIST -->
    <div id="sp-event-list">
        <?php if (empty($events)): ?>
            <p>No events found. Click <strong>Add New Event</strong> to create one.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $e): 

                    // Fetch event owner info
                    $owner_user = get_user_by('id', $e->user_id);
                    $owner_name = $owner_user ? $owner_user->display_name : 'Unknown';

                    // Detect role
                    $owner_role = ($owner_user && !empty($owner_user->roles))
                                  ? ucfirst($owner_user->roles[0])
                                  : 'User';

                    ?>
                    <tr>
                        <td><?php echo intval($e->id); ?></td>

                        <td>
                            <strong><?php echo esc_html($e->title); ?></strong>

                            <?php if ($is_admin): ?>
                                <div style="
                                    display:inline-block;
                                    margin-left:8px;
                                    padding:2px 6px;
                                    background:#e2e8f0;
                                    border-radius:4px;
                                    font-size:11px;
                                    color:#333;
                                ">
                                    <?php echo esc_html($owner_name . " (" . $owner_role . ")"); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td><?php echo esc_html($e->event_date); ?></td>

                        <td>
                            <button class="button sp-edit-event" data-id="<?php echo $e->id; ?>">Edit</button>
                            <button class="button sp-delete-event" data-id="<?php echo $e->id; ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- EVENT EDITOR -->
    <div id="sp-event-editor" style="display:none; margin-top:20px;">
        <h2 id="sp-editor-heading">Add Event</h2>

        <table class="form-table">
            <tr>
                <th><label for="sp-event-title">Event Title</label></th>
                <td><input type="text" id="sp-event-title" class="regular-text"></td>
            </tr>

            <tr>
                <th><label for="sp-event-date">Event Date</label></th>
                <td><input type="date" id="sp-event-date"></td>
            </tr>

            <tr>
                <th><label for="sp-event-notes">Notes</label></th>
                <td><textarea id="sp-event-notes" rows="4" class="large-text"></textarea></td>
            </tr>
        </table>

        <input type="hidden" id="sp-event-id" value="0">

        <button id="sp-save-event" class="button button-primary">Save Event</button>
        <button id="sp-cancel-event" class="button">Cancel</button>
    </div>
</div>
