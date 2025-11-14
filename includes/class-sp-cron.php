<?php
namespace WPSeatingPlanner;
class SP_Cron {
  public static function hooks(){ add_action('sp_daily_purge_hook',[__CLASS__,'purge_old_events']); }
  public static function purge_old_events(){
    global $wpdb; $p=$wpdb->prefix; $today=current_time('Y-m-d');
    $ids=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$p}sp_events WHERE event_date IS NOT NULL AND event_date < DATE_SUB(%s, INTERVAL 30 DAY)", $today));
    if(!$ids) return;
    foreach($ids as $id){
      $wpdb->delete("{$p}sp_assignments",['event_id'=>$id]);
      $wpdb->delete("{$p}sp_tables",['event_id'=>$id]);
      $wpdb->delete("{$p}sp_guests",['event_id'=>$id]);
      $wpdb->delete("{$p}sp_events",['id'=>$id]);
    }
  }
}