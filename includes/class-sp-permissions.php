<?php
namespace WPSeatingPlanner;
class SP_Permissions {
  const CAP_ADMIN='manage_options'; const CAP_ORG='sp_manage_own';
  public static function register(){
    add_role('sp_organizer','Seating Organizer',[ self::CAP_ORG=>true, 'read'=>true ]);
  }
  public static function can_manage(){ return current_user_can(self::CAP_ADMIN)||current_user_can(self::CAP_ORG); }
  public static function is_admin(){ return current_user_can(self::CAP_ADMIN); }
}