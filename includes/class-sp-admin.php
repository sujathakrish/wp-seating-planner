<?php
/**
 * Handles admin menus, pages, and UI initialization for WP Seating Planner.
 */

namespace WPSeatingPlanner;

if (!defined('ABSPATH')) exit;

class SP_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
    }

    /**
     * Register plugin admin menus
     */
    public function register_menus() {
        $capability = current_user_can('manage_options') ? 'manage_options' : 'sp_manage_own';

        // ✅ Top-level menu (only once)
        add_menu_page(
            __('Seating Planner', 'wp-seating-planner'),
            __('Seating Planner', 'wp-seating-planner'),
            $capability,
            'sp_events',
            [$this, 'render_events_page'],
            'dashicons-groups',
            25
        );

        // Submenu: Guest Manager
        add_submenu_page(
            'sp_events',
            __('Guest Manager', 'wp-seating-planner'),
            __('Guest Manager', 'wp-seating-planner'),
            $capability,
            'sp_guests',
            [$this, 'render_guests_page']
        );

        // Submenu: Layout Editor
        add_submenu_page(
            'sp_events',
            __('Layout Editor', 'wp-seating-planner'),
            __('Layout Editor', 'wp-seating-planner'),
            $capability,
            'sp_layout_editor',
            [$this, 'render_layout_editor_page']
        );

        // Optional Submenu: Cleanup
        add_submenu_page(
            'sp_events',
            __('Cleanup', 'wp-seating-planner'),
            __('Cleanup', 'wp-seating-planner'),
            'manage_options', // Admins only
            'sp_cleanup',
            [$this, 'render_cleanup_page']
        );
    }

    /**
     * Render: Events Page
     */
    public function render_events_page() {
        $file = SP_PLUGIN_DIR . 'admin/pages/events.php';
        if (file_exists($file)) {
            include $file;
        } else {
            $this->missing_page_notice('events.php');
        }
    }

    /**
     * Render: Guest Manager
     */
    public function render_guests_page() {
        $file = SP_PLUGIN_DIR . 'admin/pages/guest-manager.php';
        if (file_exists($file)) {
            include $file;
        } else {
            $this->missing_page_notice('guest-manager.php');
        }
    }

    /**
     * Render: Layout Editor
     */
    public function render_layout_editor_page() {
        $file = SP_PLUGIN_DIR . 'admin/pages/layout-editor.php';
        if (file_exists($file)) {
            include $file;
        } else {
            $this->missing_page_notice('layout-editor.php');
        }
    }

    /**
     * Render: Cleanup Page
     */
    public function render_cleanup_page() {
        $file = SP_PLUGIN_DIR . 'admin/pages/cleanup.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>Seating Planner Cleanup</h1>';
            echo '<p>This tool will remove outdated events and orphaned guests/tables.</p>';
            echo '<p><em>File missing: admin/pages/cleanup.php</em></p>';
            echo '</div>';
        }
    }

    /**
     * Display a helpful message if a page file is missing
     */
    private function missing_page_notice($filename) {
        echo '<div class="wrap">';
        echo '<h1>Page Missing</h1>';
        echo '<p>The admin page file <strong>' . esc_html($filename) . '</strong> is missing.</p>';
        echo '<p>Please check <code>' . esc_html(SP_PLUGIN_DIR . 'admin/pages/</code>') . ' and restore it if needed.</p>';
        echo '</div>';
    }
}
