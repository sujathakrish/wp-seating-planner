<?php
if (!defined('ABSPATH')) exit; global $wpdb; $id=(int)($_GET['event_id']??0);
$t=$wpdb->prefix.'sp_tables'; $g=$wpdb->prefix.'sp_guests'; $a=$wpdb->prefix.'sp_assignments';
$tables=$wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE event_id=%d ORDER BY name",$id));
?>
<html><head><meta charset="utf-8"><style>
body{font-family:sans-serif;} .table{border:1px solid #000; padding:8px; margin:8px 0;} .h{font-weight:bold; margin-bottom:4px;}
</style></head><body>
<h1><?php echo esc_html(get_bloginfo('name')); ?> — Seating Plan</h1>
<?php foreach($tables as $tbl): ?>
  <div class="table"><div class="h"><?php echo esc_html($tbl->name); ?> (<?php echo esc_html($tbl->shape); ?> · cap <?php echo (int)$tbl->capacity; ?>)</div><ol>
  <?php $rows=$wpdb->get_results($wpdb->prepare("SELECT g.first_name,g.last_name,a.seat_number FROM $a a JOIN $g g ON g.id=a.guest_id WHERE a.event_id=%d AND a.table_id=%d ORDER BY a.seat_number,g.last_name",$id,$tbl->id));
    foreach($rows as $r){ echo '<li>'.esc_html(($r->seat_number?('#'.$r->seat_number.' '):'').$r->first_name.' '.$r->last_name).'</li>'; } ?>
  </ol></div>
<?php endforeach; ?>
</body></html>
