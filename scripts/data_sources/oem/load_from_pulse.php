<?php
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

// Force cache reload
$redis->hdel("feed:1",'time');
$timevalue = $feed->get_timevalue(1);

$start = $timevalue["time"]*1000;
//$start = 1581684780*1000;

$end = time()*1000;

$interval = 1800;

$scale = ((3600/$interval)/250)*0.5*0.975;

$data = $feed->get_data(814,$start,$end,1800,0,1);

echo json_encode($data)."\n";

for ($i=1; $i<count($data); $i++) {
    if ($data[$i][1]!=null && $data[$i-1][1]!=null) {
        $diff = ($data[$i][1] - $data[$i-1][1])*$scale;
        if ($diff<0) $diff = 0;
        print $data[$i-1][0]." ".$data[$i][1]." ".$diff."\n";
        $timestamp = $data[$i-1][0]*0.001;
        $feed->insert_data(1,$timestamp,$timestamp,$diff);
    }
}
