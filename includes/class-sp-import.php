<?php
namespace WPSeatingPlanner;
use League\Csv\Reader;
class SP_Import {
  public static function csv(string $path, int $event_id, array $map): int {
    global $wpdb; $t=$wpdb->prefix.'sp_guests'; $now=current_time('mysql');
    $csv=Reader::createFromPath($path); $csv->setHeaderOffset(0);
    $headers=$csv->getHeader(); $count=0;
    foreach ($csv->getRecords() as $row){
      $first = sanitize_text_field(self::get($row,$map,'first_name',$headers));
      $last  = sanitize_text_field(self::get($row,$map,'last_name',$headers));
      if (!$first && !$last) continue;
      $party = sanitize_text_field(self::get($row,$map,'party',$headers));
      $is_child = (int) in_array(strtolower((string) self::get($row,$map,'is_child',$headers)), ['1','true','yes','y','child','kid'], true);
      $meal  = sanitize_text_field(self::get($row,$map,'meal',$headers));
      $notes = sanitize_textarea_field(self::get($row,$map,'notes',$headers));
      $wpdb->insert($t, ['event_id'=>$event_id,'first_name'=>$first,'last_name'=>$last,'party'=>$party,'is_child'=>$is_child,'meal'=>$meal,'notes'=>$notes,'meta_json'=>wp_json_encode($row),'created_at'=>$now,'updated_at'=>$now]); $count++;
    }
    return $count;
  }
  private static function get($row,$map,$key,$headers){ $col = $map[$key] ?? $key; return array_key_exists($col,$row) ? $row[$col] : ''; }
}
