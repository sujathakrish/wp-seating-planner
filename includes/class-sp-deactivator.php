<?php
namespace WPSeatingPlanner;
class SP_Deactivator {
  public static function deactivate(){
    $ts = wp_next_scheduled('sp_daily_purge_hook');
    if($ts) wp_unschedule_event($ts,'sp_daily_purge_hook');
  }
}