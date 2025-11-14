<?php
namespace WPSeatingPlanner;
use PhpOffice\PhpSpreadsheet\Spreadsheet; use PhpOffice\PhpSpreadsheet\Writer\Xlsx; use League\Csv\Writer; use Dompdf\Dompdf;
class SP_Export {
  private static function fetch(int $event_id){
    global $wpdb; $g=$wpdb->prefix.'sp_guests'; $a=$wpdb->prefix.'sp_assignments'; $t=$wpdb->prefix.'sp_tables';
    return $wpdb->get_results($wpdb->prepare("SELECT g.*, a.table_id, a.seat_number, tt.name AS table_name
      FROM $g g LEFT JOIN $a a ON a.guest_id=g.id AND a.event_id=g.event_id
      LEFT JOIN $t tt ON tt.id=a.table_id WHERE g.event_id=%d ORDER BY g.last_name,g.first_name", $event_id), ARRAY_A);
  }
  public static function xlsx(int $event_id, string $group){
    $rows=self::fetch($event_id);
    $sheetMap=['table'=>fn($r)=>$r['table_name']?:'Unassigned','first_name'=>fn($r)=>$r['first_name'][0]??'#','last_name'=>fn($r)=>$r['last_name'][0]??'#'];
    $key=$sheetMap[$group]??$sheetMap['table']; $groups=[]; foreach($rows as $r){ $groups[$key($r)][]=$r; }
    $ss=new Spreadsheet(); $ss->removeSheetByIndex(0);
    foreach($groups as $k=>$items){ $ws=$ss->createSheet(); $ws->setTitle(substr((string)$k,0,28));
      $ws->fromArray(['First Name','Last Name','Party','Child','Meal','Table','Seat','Notes'], null, 'A1'); $i=2;
      foreach($items as $r){ $ws->fromArray([$r['first_name'],$r['last_name'],$r['party'],$r['is_child']?'Yes':'No',$r['meal'],$r['table_name'],$r['seat_number'],$r['notes']], null, 'A'.$i++); }
    }
    $writer=new Xlsx($ss); $filename='seating-export-'.$event_id.'-'.time().'.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment; filename='.$filename); $writer->save('php://output'); exit;
  }
  public static function csv(int $event_id, string $group){
    $rows=self::fetch($event_id); $csv=Writer::createFromFileObject(new \SplTempFileObject());
    $csv->insertOne(['first_name','last_name','party','is_child','meal','table','seat','notes']);
    foreach ($rows as $r){ $csv->insertOne([$r['first_name'],$r['last_name'],$r['party'],$r['is_child'],$r['meal'],$r['table_name'],$r['seat_number'],$r['notes']]); }
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=seating-export-'.$event_id.'-'.time().'.csv'); echo $csv->toString(); exit;
  }
  public static function pdf(int $event_id){
    ob_start(); include __DIR__.'/../templates/print-plan.php'; $html=ob_get_clean();
    $dompdf=new Dompdf(); $dompdf->loadHtml($html); $dompdf->setPaper('A4','portrait'); $dompdf->render(); $dompdf->stream('seating-plan-'.$event_id.'.pdf'); exit;
  }
}
