<?php
/**
 * Admin Page: Seating Planner Cleanup
 * Location: wp-seating-planner/admin/pages/cleanup.php
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$tbl_events = $wpdb->prefix . 'sp_events';
$tbl_guests = $wpdb->prefix . 'sp_guests';
$tbl_tables = $wpdb->prefix . 'sp_tables';
?>

<div class="wrap sp-admin-page">
  <h1 class="wp-heading-inline">🧹 Seating Planner Cleanup</h1>
  <hr class="wp-header-end">
  <p>This tool removes expired events (older than 30 days) and their related guests and tables.</p>

  <table class="widefat striped" style="max-width:600px;">
    <tbody>
      <tr>
        <th>Events Table</th>
        <td><?php echo esc_html($tbl_events); ?></td>
      </tr>
      <tr>
        <th>Guests Table</th>
        <td><?php echo esc_html($tbl_guests); ?></td>
      </tr>
      <tr>
        <th>Tables Table</th>
        <td><?php echo esc_html($tbl_tables); ?></td>
      </tr>
    </tbody>
  </table>

  <p style="margin-top:20px;">
    <button id="sp-run-cleanup" class="button button-primary">Run Cleanup Now</button>
  </p>

  <div id="sp-cleanup-result" style="margin-top:20px;"></div>
</div>

<script>
jQuery(document).ready(function($) {

  $('#sp-run-cleanup').on('click', function() {
    if (!confirm('Are you sure you want to run cleanup? This will delete old events and their related data.')) {
      return;
    }

    const $btn = $(this);
    $btn.prop('disabled', true).text('Cleaning...');

    $.post(ajaxurl, { action: 'sp_run_cleanup' }, function(response) {
      $btn.prop('disabled', false).text('Run Cleanup Now');

      if (response.success) {
        const data = response.data;
        $('#sp-cleanup-result').html(`
          <div class="updated notice">
            <p><strong>Cleanup completed successfully.</strong></p>
            <ul>
              <li>🗑️ Deleted events: ${data.deleted_events}</li>
              <li>👥 Deleted guests: ${data.deleted_guests}</li>
              <li>🍽️ Deleted tables: ${data.deleted_tables}</li>
            </ul>
          </div>
        `);
      } else {
        $('#sp-cleanup-result').html(`<div class="error notice"><p>Error: ${response.data}</p></div>`);
      }
    });
  });
});
</script>
