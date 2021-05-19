<?php
require "lib/load_emoncms.php";
$result = $mysqli->query("SELECT * FROM feeds ORDER BY id ASC");
while ($row = $result->fetch_object()) {
    $feedid = (int) $row->id;
    if ($feedid && $row->engine==5) {
         try {
             $meta = $feed->get_meta($feedid);
         } catch (Exception $e) {
             echo 'Caught exception: ',  $feedid." ".$e->getMessage(), "\n";
             $feed->delete($feedid);
         }
         if (isset($meta->start_time) && $meta->start_time==0 && $meta->npoints==0) {
             print json_encode($row)."\n";
             $feed->delete($feedid);
         }
    }
}
