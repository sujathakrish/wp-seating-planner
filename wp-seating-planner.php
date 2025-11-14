<?php
/**
 * Plugin Name: WP Seating Planner
 * Description: Seating arrangement and guest management system for event organizers.
 * Version: 1.2
 * Author: Love2Celebrate
 */

if (!defined('ABSPATH')) exit;

define('SP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Autoload Classes
 */
spl_autoload_register(function ($class) {
    if (strpos($class, 'WPSeatingPlanner\\') === 0) {
        $class_name = str_replace('WPSeatingPlanner\\', '', $class);
        $path = SP_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        if (file_exists($path)) require_once $path;
    }
});

/**
 * Register Custom Roles and Permissions
 */
register_activation_hook(__FILE__, function() {
    \WPSeatingPlanner\SP_Permissions::register();
});

/**
 * Initialize Plugin
 */
add_action('plugins_loaded', function() {

    // Core includes (safe even with autoloader)
    require_once SP_PLUGIN_DIR . 'includes/class-sp-permissions.php';
    require_once SP_PLUGIN_DIR . 'includes/class-sp-admin.php';
    require_once SP_PLUGIN_DIR . 'includes/class-sp-rest.php';
    require_once SP_PLUGIN_DIR . 'includes/class-sp-pdf-export.php';

    // Admin UI
    new \WPSeatingPlanner\SP_Admin();

    // Shared REST instance
    global $sp_rest_instance;
    $sp_rest_instance = new \WPSeatingPlanner\SP_REST();
});

/**
 * Register REST API Routes
 */
add_action('rest_api_init', function() {
    global $sp_rest_instance;
    if ($sp_rest_instance instanceof \WPSeatingPlanner\SP_REST) {
        $sp_rest_instance->register_routes();
    }
});

/**
 * Admin Scripts & Styles
 */
add_action('admin_enqueue_scripts', function($hook) {

    // Only load on plugin screens
    if (strpos($hook, 'sp_') === false && strpos($hook, 'seating-planner') === false) {
        return;
    }

    /** Admin CSS */
    if (file_exists(SP_PLUGIN_DIR . 'assets/css/admin.css')) {
        wp_enqueue_style(
            'sp-admin-css',
            SP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            filemtime(SP_PLUGIN_DIR . 'assets/css/admin.css')
        );
    }

    /** NEW: Print Stylesheet (used inside print popup) */
    if (file_exists(SP_PLUGIN_DIR . 'assets/css/print.css')) {
        wp_enqueue_style(
            'sp-print-css',
            SP_PLUGIN_URL . 'assets/css/print.css',
            [],
            filemtime(SP_PLUGIN_DIR . 'assets/css/print.css'),
			'print'
        );
    }

    /** Admin JS */
    if (file_exists(SP_PLUGIN_DIR . 'assets/js/admin.js')) {
        wp_enqueue_script(
            'sp-admin-js',
            SP_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            filemtime(SP_PLUGIN_DIR . 'assets/js/admin.js'),
            true
        );

        wp_localize_script('sp-admin-js', 'SP_API', [
            'root'  => esc_url_raw(rest_url('sp/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'plugin_url' => SP_PLUGIN_URL
        ]);
		
		wp_localize_script('sp-admin-js', 'SP_VARS', [
            'plugin_url' => SP_PLUGIN_URL
    ]);
    }
});

/**
 * Render Admin Pages
 */
function sp_render_events_page() {
    include SP_PLUGIN_DIR . 'admin/pages/events.php';
}

function sp_render_guests_page() {
    include SP_PLUGIN_DIR . 'admin/pages/guest-manager.php';
}

function sp_render_layout_page() {
    include SP_PLUGIN_DIR . 'admin/pages/layout-editor.php';
}

/**
 * Daily Cleanup
 */
if (!wp_next_scheduled('sp_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'sp_daily_cleanup');
}

add_action('sp_daily_cleanup', function() {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->prefix}sp_events 
                  WHERE event_date < NOW() - INTERVAL 30 DAY");

    $wpdb->query("DELETE FROM {$wpdb->prefix}sp_guests 
                  WHERE event_id NOT IN (SELECT id FROM {$wpdb->prefix}sp_events)");

    $wpdb->query("DELETE FROM {$wpdb->prefix}sp_tables 
                  WHERE event_id NOT IN (SELECT id FROM {$wpdb->prefix}sp_events)");
});
