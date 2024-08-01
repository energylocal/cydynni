<?php
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));

$forecast = file_get_contents("https://www.bmreports.com/bmrs/?q=ajax/csv_download/WINDFORFUELHH/csv/&filename=WindForecastOutTurn_".$date->format('Ymd_Hi'));

$date = new DateTime();
$date->setTimezone(new DateTimeZone("UTC"));


$lines = explode("\n",$forecast);

for ($i=1; $i<count($lines); $i++) {
    $line = $lines[$i];
    $parts = explode(",",$line);
    if (isset($parts[6])) {
        $datestr = $parts[1];
        $period = $parts[2];
        $forecast = $parts[6];
        
        if ($forecast!='NULL') {
            $forecast = (float) $forecast;
            $year = substr($datestr,0,4);
            $month = substr($datestr,4,2);
            $day = substr($datestr,6,2);   
            
        
            $date->setDate($year,$month,$day);
            $date->setTime(0,0,0);
            $timestamp = $date->getTimestamp()+(1800*$period);
          
            print $year.$month.$day." ".$timestamp." ".$period." ".$forecast."\n";
            
            $feed->post(2944,$timestamp,$timestamp,$forecast,"join");
        }
    }
}
