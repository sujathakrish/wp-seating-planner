<?php
namespace WPSeatingPlanner;
class SP_Activator {
  public static function activate(){
    global $wpdb; $prefix=$wpdb->prefix; $charset=$wpdb->get_charset_collate();
    $sql_events = "CREATE TABLE IF NOT EXISTS {$prefix}sp_events (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT UNSIGNED NULL,
      title VARCHAR(191) NOT NULL,
      event_date DATE NULL,
      layout_json LONGTEXT NULL,
      created_at DATETIME NULL, updated_at DATETIME NULL,
      KEY user_id (user_id), KEY event_date (event_date)
    ) $charset;";
    $sql_tables = "CREATE TABLE IF NOT EXISTS {$prefix}sp_tables (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event_id BIGINT UNSIGNED NOT NULL,
      name VARCHAR(191) NULL,
      shape VARCHAR(16) DEFAULT 'round',
      capacity INT DEFAULT 8,
      x INT NULL, y INT NULL, width INT NULL, height INT NULL, rotation INT NULL,
      created_at DATETIME NULL, updated_at DATETIME NULL,
      KEY event_id (event_id)
    ) $charset;";
    $sql_guests = "CREATE TABLE IF NOT EXISTS {$prefix}sp_guests (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event_id BIGINT UNSIGNED NOT NULL,
      table_id BIGINT UNSIGNED NULL,
      seat_index INT NULL,
      first_name VARCHAR(100) NULL,
      last_name VARCHAR(100) NULL,
      party VARCHAR(100) NULL,
      is_child TINYINT(1) DEFAULT 0,
      meal VARCHAR(100) NULL,
      notes TEXT NULL,
      created_at DATETIME NULL, updated_at DATETIME NULL,
      KEY event_id (event_id)
    ) $charset;";
    $sql_assign = "CREATE TABLE IF NOT EXISTS {$prefix}sp_assignments (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event_id BIGINT UNSIGNED NOT NULL,
      table_id BIGINT UNSIGNED NULL,
      guest_id BIGINT UNSIGNED NOT NULL,
      seat_number INT NULL,
      created_at DATETIME NULL, updated_at DATETIME NULL,
      UNIQUE KEY uniq (event_id, guest_id), KEY table_id (table_id)
    ) $charset;";
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql_events); dbDelta($sql_tables); dbDelta($sql_guests); dbDelta($sql_assign);
    self::add_columns_if_missing();
    (new SP_Permissions())->register();
    if(!wp_next_scheduled('sp_daily_purge_hook')) wp_schedule_event(time()+60,'daily','sp_daily_purge_hook');
  }
  private static function add_columns_if_missing(){
    global $wpdb; $p=$wpdb->prefix;
    $cols = $wpdb->get_col("DESC {$p}sp_guests",0);
    if($cols && !in_array('meal',$cols,true)) $wpdb->query("ALTER TABLE {$p}sp_guests ADD COLUMN meal VARCHAR(30) NULL AFTER is_child");
    if($cols && !in_array('notes',$cols,true)) $wpdb->query("ALTER TABLE {$p}sp_guests ADD COLUMN notes VARCHAR(100) NULL AFTER meal");
    $colsE = $wpdb->get_col("DESC {$p}sp_events",0);
    if($colsE && !in_array('user_id',$colsE,true)) $wpdb->query("ALTER TABLE {$p}sp_events ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id");
    if($colsE && !in_array('event_date',$colsE,true)) $wpdb->query("ALTER TABLE {$p}sp_events ADD COLUMN event_date DATE NULL AFTER title");
  }
}