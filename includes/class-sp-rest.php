<?php
/**
 * REST API for WP Seating Planner (MVP)
 *
 * - Events CRUD
 * - Guests CRUD (inline editor)
 * - Tables/Layout CRUD (no rotation)
 * - Auto-seat suggestion (by party)
 * - Cleanup endpoint
 */

namespace WPSeatingPlanner;

use WP_REST_Request;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class SP_REST extends \WP_REST_Controller
{
    /**
     * Called from wp-seating-planner.php on rest_api_init
     */
    public function register_routes()
    {
        // ------------------------
        // EVENTS
        // ------------------------
        register_rest_route('sp/v1', '/save-event', [
            'methods'  => 'POST',
            'callback' => [$this, 'save_event'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        register_rest_route('sp/v1', '/get-event/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_event'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        register_rest_route('sp/v1', '/delete-event/(?P<id>\d+)', [
            'methods'  => 'DELETE',
            'callback' => [$this, 'delete_event'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        // Run cleanup (from Events screen)
        register_rest_route('sp/v1', '/cleanup', [
            'methods'  => 'POST',
            'callback' => [$this, 'cleanup'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        // ------------------------
        // GUESTS (inline editor)
																			 
																									 
        // ------------------------
        // List guests for an event
        register_rest_route('sp/v1', '/guests', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_guests'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Alias: /guests/{event_id}
        register_rest_route('sp/v1', '/guests/(?P<event_id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_guests'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Insert / update single guest
																	
        register_rest_route('sp/v1', '/save-guest', [
            'methods'  => 'POST',
            'callback' => [$this, 'save_guest'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Alias: /guest (POST)
        register_rest_route('sp/v1', '/guest/save', [
            'methods'  => 'POST',
            'callback' => [$this, 'save_guest'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Delete guest
        register_rest_route('sp/v1', '/guest/(?P<id>\d+)', [
            'methods'  => 'DELETE',
            'callback' => [$this, 'delete_guest'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // ------------------------
        // TABLES / LAYOUT EDITOR
        // ------------------------

        // Load all tables (optionally by event_id)
        register_rest_route('sp/v1', '/tables', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_tables'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        register_rest_route('sp/v1', '/guest/save', [
            'methods'  => 'POST',
            'callback' => [$this, 'save_guest'],
            'permission_callback' => function ($request) {
                $event_id = isset($request['event_id']) ? (int) $request['event_id'] : 0;
                return $event_id > 0 && $this->can_manage_event($event_id);
            }
        ]);

									
        register_rest_route('sp/v1', '/tables/(?P<event_id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_tables'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Add table
        register_rest_route('sp/v1', '/add-table', [
            'methods'  => 'POST',
            'callback' => [$this, 'add_table'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Update table (position / size / label / capacity)
        register_rest_route('sp/v1', '/update-table', [
            'methods'  => 'POST',
            'callback' => [$this, 'update_table'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Delete table
        register_rest_route('sp/v1', '/table/(?P<id>\d+)', [
            'methods'  => 'DELETE',
            'callback' => [$this, 'delete_table'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Bulk save (“Save Layout” button)
        register_rest_route('sp/v1', '/save-layout', [
            'methods'  => 'POST',
            'callback' => [$this, 'save_layout'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);

        // Auto-seat guests (suggestion only)
        register_rest_route('sp/v1', '/auto-seat', [
            'methods'  => 'POST',
            'callback' => [$this, 'auto_seat'],
            'permission_callback' => function () {
                return current_user_can('manage_options') || current_user_can('edit_posts');
            },
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Ensure current user can manage an event.
     */
    protected function can_manage_event($event_id)
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sp_events';

        $owner_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT user_id FROM {$table} WHERE id = %d", $event_id)
        );

        return ($owner_id && get_current_user_id() === $owner_id);
    }

    // ============================================================
    // EVENTS
    // ============================================================

    public function save_event(WP_REST_Request $request)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'sp_events';

        $id    = (int) $request->get_param('id');
        $title = sanitize_text_field($request->get_param('title'));
        $date  = sanitize_text_field($request->get_param('event_date'));
        $notes = sanitize_textarea_field($request->get_param('notes'));

        if (!$title || !$date) {
            return new WP_Error('missing_fields', 'Title and Date are required.', ['status' => 400]);
        }

        if ($id === 0) {
            // INSERT
            $ok = $wpdb->insert($table, [
                'user_id'    => get_current_user_id(),
                'title'      => $title,
                'event_date' => $date,
                'layout_json'=> '{}',
                'notes'      => $notes,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);

            if (!$ok) {
                return new WP_Error('db_error', 'Failed to create event.', ['status' => 500]);
            }

            return ['id' => (int) $wpdb->insert_id];
        }

        // UPDATE
        if (!$this->can_manage_event($id)) {
            return new WP_Error('forbidden', 'You cannot edit this event.', ['status' => 403]);
        }

        $ok = $wpdb->update($table, [
            'title'      => $title,
            'event_date' => $date,
            'notes'      => $notes,
            'updated_at' => current_time('mysql'),
        ], ['id' => $id]);

        if ($ok === false) {
            return new WP_Error('db_error', 'Failed to update event.', ['status' => 500]);
        }

        return ['id' => $id];
    }

    public function get_event(WP_REST_Request $request)
    {
        global $wpdb;

        $id    = (int) $request['id'];
        $table = $wpdb->prefix . 'sp_events';

        $event = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$event) {
            return new WP_Error('not_found', 'Event not found.', ['status' => 404]);
        }

        if (!$this->can_manage_event($id)) {
            return new WP_Error('forbidden', 'You cannot view this event.', ['status' => 403]);
        }

        return $event;
    }

    public function delete_event(WP_REST_Request $request)
    {
        global $wpdb;

        $id    = (int) $request['id'];
        $table = $wpdb->prefix . 'sp_events';

        if (!$this->can_manage_event($id)) {
            return new WP_Error('forbidden', 'You cannot delete this event.', ['status' => 403]);
        }

        $ok = $wpdb->delete($table, ['id' => $id]);

        if ($ok === false) {
            return new WP_Error('db_error', 'Failed to delete event.', ['status' => 500]);
        }

        return ['deleted' => (int) $ok];
    }

    /**
     * Cleanup: remove events older than 30 days and orphan guests/tables
     */
    public function cleanup()
    {
        global $wpdb;

        $events = $wpdb->prefix . 'sp_events';
        $guests = $wpdb->prefix . 'sp_guests';
        $tables = $wpdb->prefix . 'sp_tables';

        // Delete events older than 30 days
        $wpdb->query("
            DELETE FROM {$events}
            WHERE event_date IS NOT NULL
              AND event_date <> ''
              AND DATE_ADD(event_date, INTERVAL 30 DAY) < CURDATE()
        ");

        // Delete orphans
        $wpdb->query("
            DELETE g FROM {$guests} g
            LEFT JOIN {$events} e ON g.event_id = e.id
            WHERE e.id IS NULL
        ");

        $wpdb->query("
            DELETE t FROM {$tables} t
            LEFT JOIN {$events} e ON t.event_id = e.id
            WHERE e.id IS NULL
        ");

        return ['status' => 'ok'];
    }

    // ============================================================
    // GUESTS
    // ============================================================

    public function get_guests(WP_REST_Request $request)
    {
        global $wpdb;

        $event_id = (int) ($request->get_param('event_id') ?: $request['event_id']);

        if (!$event_id) {
            return new WP_Error('missing_event', 'Missing event_id.', ['status' => 400]);
        }

        if (!$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot view guests for this event.', ['status' => 403]);
        }

        $table = $wpdb->prefix . 'sp_guests';

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE event_id = %d ORDER BY party, first_name, last_name", $event_id),
            ARRAY_A
        );

        return $rows ?: [];
    }

    public function save_guest(WP_REST_Request $request)
    {
        global $wpdb;

        $table    = $wpdb->prefix . 'sp_guests';
        $id       = (int) $request->get_param('id');
        $event_id = (int) $request->get_param('event_id');

        if (!$event_id) {
            return new WP_Error('missing_event', 'Missing event_id.', ['status' => 400]);
        }

        if (!$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot edit guests for this event.', ['status' => 403]);
        }

        $first_name = sanitize_text_field($request->get_param('first_name'));
        $last_name  = sanitize_text_field($request->get_param('last_name'));
        $party      = sanitize_text_field($request->get_param('party'));
        $meal  = sanitize_text_field($request->get_param('meal'));
        $notes      = sanitize_textarea_field($request->get_param('notes'));
        $is_child   = (int) !!$request->get_param('is_child');

        $data = [
            'event_id'   => $event_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'party'      => $party,
            'meal'       => $meal,
            'notes'      => $notes,
            'is_child'   => $is_child,
            'updated_at' => current_time('mysql'),
        ];

        if ($id === 0) {
            $data['created_at'] = current_time('mysql');

            $ok = $wpdb->insert($table, $data);
            if (!$ok) {
                return new WP_Error('db_error', 'Failed to add guest.', ['status' => 500]);
            }

            $data['id'] = (int) $wpdb->insert_id;
            return $data;
        }

        $ok = $wpdb->update($table, $data, ['id' => $id]);

        if ($ok === false) {
            return new WP_Error('db_error', 'Failed to update guest.', ['status' => 500]);
        }

        $data['id'] = $id;
        return $data;
    }

    public function delete_guest(WP_REST_Request $request)
    {
        global $wpdb;

        $id    = (int) $request['id'];
        $table = $wpdb->prefix . 'sp_guests';

        // Check event permission
        $event_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT event_id FROM {$table} WHERE id = %d", $id)
        );

        if (!$event_id || !$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot delete this guest.', ['status' => 403]);
        }

        $ok = $wpdb->delete($table, ['id' => $id]);

        if ($ok === false) {
            return new WP_Error('db_error', 'Failed to delete guest.', ['status' => 500]);
        }

        return ['deleted' => (int) $ok];
    }

    // ============================================================
    // TABLES / LAYOUT (no rotation, MVP)
    // ============================================================

    public function get_tables(WP_REST_Request $request)
    {
        global $wpdb;

        // Allow either query param or path param
        $event_id = (int) ($request->get_param('event_id') ?: $request['event_id']);

        if (!$event_id) {
            return new WP_Error('missing_event', 'Missing event_id.', ['status' => 400]);
        }

        if (!$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot view tables for this event.', ['status' => 403]);
        }

        $table = $wpdb->prefix . 'sp_tables';

        // Map DB column "name" to API field "label" for JS
        $rows = $wpdb->get_results(
            $wpdb->prepare("
                SELECT id, event_id, shape, capacity, x, y, width, height,
                       name AS label, created_at, updated_at
                FROM {$table}
                WHERE event_id = %d
                ORDER BY id ASC
            ", $event_id),
            ARRAY_A
        );

        return $rows ?: [];
    }

    public function add_table(WP_REST_Request $request)
    {
        global $wpdb;

        $event_id = (int) $request->get_param('event_id');
        if (!$event_id) {
            return new WP_Error('missing_event', 'Missing event_id.', ['status' => 400]);
        }
        if (!$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot edit this layout.', ['status' => 403]);
        }

        $shape    = sanitize_text_field($request->get_param('shape') ?: 'round');
        $capacity = (int) $request->get_param('capacity') ?: 8;
        $x        = (int) $request->get_param('x');
        $y        = (int) $request->get_param('y');
        $width    = (int) $request->get_param('width') ?: 100;
        $height   = (int) $request->get_param('height') ?: 100;
        $label    = sanitize_text_field($request->get_param('label') ?: '');

        $table = $wpdb->prefix . 'sp_tables';

        $ok = $wpdb->insert($table, [
            'event_id'   => $event_id,
            'shape'      => $shape,
            'capacity'   => $capacity,
            'x'          => $x,
            'y'          => $y,
            'width'      => $width,
            'height'     => $height,
            'name'       => $label,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        if (!$ok) {
            return new WP_Error('db_error', 'Failed to add table.', ['status' => 500]);
        }

        $id = (int) $wpdb->insert_id;

        return [
            'id'        => $id,
            'event_id'  => $event_id,
            'shape'     => $shape,
            'capacity'  => $capacity,
            'x'         => $x,
            'y'         => $y,
            'width'     => $width,
            'height'    => $height,
            'label'     => $label,
        ];
    }

    public function update_table(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        if (!$id) {
            return new WP_Error('missing_id', 'Missing table id.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'sp_tables';

        $event_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT event_id FROM {$table} WHERE id = %d", $id)
        );

        if (!$event_id || !$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot edit this table.', ['status' => 403]);
        }

        $fields = [];
        foreach (['shape','capacity','x','y','width','height','label'] as $field) {
            if ($request->get_param($field) !== null) {
                if (in_array($field, ['capacity','x','y','width','height'], true)) {
                    $val = (int) $request->get_param($field);
                } else {
                    $val = sanitize_text_field($request->get_param($field));
                }

                if ($field === 'label') {
                    $fields['name'] = $val; // map label -> name column
                } else {
                    $fields[$field] = $val;
                }
            }
        }

        if (empty($fields)) {
            return ['id' => $id]; // nothing to update
        }

        $fields['updated_at'] = current_time('mysql');

        $ok = $wpdb->update($table, $fields, ['id' => $id]);

        if ($ok === false) {
            return new WP_Error('db_error', 'Failed to update table.', ['status' => 500]);
        }

        $fields['id'] = $id;
        return $fields;
    }

    public function delete_table(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request['id'];
        if (!$id) {
            return new WP_Error('missing_id', 'Missing table id.', ['status' => 400]);
        }

        $table = $wpdb->prefix . 'sp_tables';

        $event_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT event_id FROM {$table} WHERE id = %d", $id)
        );

        if (!$event_id || !$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot delete this table.', ['status' => 403]);
        }

        $ok = $wpdb->delete($table, ['id' => $id]);

        if ($ok === false) {
            return new WP_Error('db_error', 'Failed to delete table.', ['status' => 500]);
        }

        return ['deleted' => (int) $ok];
    }

    /**
     * Bulk save layout for an event.
     * Expects: event_id + tables[] (array of {id,x,y,width,height,capacity,label}).
     */
    public function save_layout(WP_REST_Request $request)
    {
        global $wpdb;

        $event_id = (int) $request->get_param('event_id');
        $tables   = $request->get_param('tables');

        if (!$event_id) {
            return new WP_Error('missing_event', 'Missing event_id.', ['status' => 400]);
        }

        if (!$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot edit this layout.', ['status' => 403]);
        }

        if (empty($tables) || !is_array($tables)) {
            return ['updated' => 0];
        }

        $table  = $wpdb->prefix . 'sp_tables';
        $updated = 0;

        foreach ($tables as $t) {
            if (!isset($t['id'])) {
					   
                continue;
            }
            $id = (int) $t['id'];

            $fields = [];
            foreach (['x','y','width','height','capacity','label'] as $field) {
                if (!isset($t[$field])) {
                    continue;
																			  
							
																							
					 
                }

                if (in_array($field, ['x','y','width','height','capacity'], true)) {
                    $val = (int) $t[$field];
                } else {
                    $val = sanitize_text_field($t[$field]);
                }

                if ($field === 'label') {
                    $fields['name'] = $val;
                } else {
                    $fields[$field] = $val;
                }
            }

            if (!empty($fields)) {
                $fields['updated_at'] = current_time('mysql');
                $res = $wpdb->update($table, $fields, ['id' => $id]);
                if ($res !== false) {
                    $updated++;
                }
            }
        }

        return ['updated' => $updated];
    }

    /**
     * Auto-seat guests by party.
     * This version does NOT persist the mapping, only returns a suggestion:
     *   { table_id: [guest_ids...] }
																	 
     */
    public function auto_seat(WP_REST_Request $request)
    {
        global $wpdb;

        $event_id = (int) $request->get_param('event_id');
        if (!$event_id) {
            return new WP_Error('missing_event', 'Missing event_id.', ['status' => 400]);
        }

        if (!$this->can_manage_event($event_id)) {
            return new WP_Error('forbidden', 'You cannot auto-seat this event.', ['status' => 403]);
        }

        $tables_table = $wpdb->prefix . 'sp_tables';
        $guests_table = $wpdb->prefix . 'sp_guests';

        $tables = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$tables_table} WHERE event_id = %d ORDER BY id ASC", $event_id),
            ARRAY_A
        );

        $guests = $wpdb->get_results(
            $wpdb->prepare("SELECT id, first_name, last_name, party, is_child FROM {$guests_table} WHERE event_id = %d ORDER BY party, last_name, first_name", $event_id),
            ARRAY_A
        );

        if (empty($tables) || empty($guests)) {
            return [
                'status'    => 'ok',
                'message'   => 'No tables or guests to auto-seat.',
                'seating'   => [],
            ];
        }

        // Group guests by party
        $by_party = [];
        foreach ($guests as $g) {
            $party_key = $g['party'] !== '' ? $g['party'] : ('__solo_' . $g['id']);
            if (!isset($by_party[$party_key])) {
                $by_party[$party_key] = [];
            }
            $by_party[$party_key][] = $g;
        }

        // Greedy fill: try to keep each party together
        $seating    = [];
        $table_caps = [];
        foreach ($tables as $t) {
            $seating[$t['id']]    = [];
            $table_caps[$t['id']] = (int) $t['capacity'];
        }

        $table_ids = array_keys($seating);
        $t_index   = 0;

        foreach ($by_party as $party_key => $group) {
            $remaining = count($group);
            $offset    = 0;

            while ($remaining > 0 && $t_index < count($table_ids)) {
                $tid  = $table_ids[$t_index];
                $free = $table_caps[$tid];

                if ($free <= 0) {
                    $t_index++;
                    continue;
                }

                $chunk = array_slice($group, $offset, $free);
                $seating[$tid] = array_merge($seating[$tid], array_column($chunk, 'id'));

                $used = count($chunk);
                $remaining        -= $used;
                $offset           += $used;
                $table_caps[$tid] -= $used;

                if ($table_caps[$tid] <= 0) {
                    $t_index++;
                }
            }
        }

																	  
        return [
            'status'  => 'ok',
            'message' => 'Auto-seat suggestion generated (not yet persisted).',
            'seating' => $seating,
																																																				   
        ];
    }
}
