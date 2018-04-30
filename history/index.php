<?php

/*

Source code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
CydYnni App - community energy smart grid web app
part of the EnergyLocal CydYnni project in Bethesda North Wales

Developed by OpenEnergyMonitor:
http://openenergymonitor.org

*/
define('EMONCMS_EXEC', 1);

error_reporting(E_ALL);
ini_set('display_errors', 'on');
date_default_timezone_set('Europe/London');

require "core.php";

$path = get_application_path();

$redis = new Redis();
$connected = $redis->connect("localhost");

$q = "";
if (isset($_GET['q'])) $q = $_GET['q'];

$translation = new stdClass();
$translation->cy = json_decode(file_get_contents("locale/cy"));

$lang = "cy";
if (isset($_GET['lang']) && $_GET['lang']=="cy") $lang = "cy";
if (isset($_GET['iaith']) && $_GET['iaith']=="cy") $lang = "cy";
if (isset($_GET['lang']) && $_GET['lang']=="en") $lang = "en";
if (isset($_GET['iaith']) && $_GET['iaith']=="en") $lang = "en";


$format = "html";
$content = "Sorry page not found";

$logger = new EmonLogger();

switch ($q)
{   
    case "":
        $format = "html";
        $live = json_decode($redis->get("live"));
        $content = view("view.php",array("live"=>$live));
        break;
        
    case "feed/data.json":
        $format = "json";
        // Params
        $id = (int) get("id");
        $start = (int) get("start");
        $end = (int) get("end");
        $interval = (int) get("interval");
        $skipmissing = (int) get("skipmissing");
        $limitinterval = (int) get("limitinterval");
        // Request
        $content = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/feed/data.json?id=$id&start=$start&end=$end&interval=$interval&skipmissing=$skipmissing&limitinterval=$limitinterval"));
        break;
        
    case "feed/average.json":
        $format = "json";
        // Params
        $id = (int) get("id");
        $start = (int) get("start");
        $end = (int) get("end");
        $interval = (int) get("interval");
        // Request
        $content = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/feed/average.json?id=$id&start=$start&end=$end&interval=$interval"));
        break;

    case "emoncmsorg":
        $format = "json";
        // Params
        $id = (int) get("id");
        $start = (int) get("start");
        $end = (int) get("end");
        $interval = (int) get("interval");
        // Request
        $content = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=$id&start=$start&end=$end&interval=$interval"));
        break;        
        
    case "live":
        $format = "json";
        
        $live = json_decode($redis->get("live"));
        
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->setTimestamp(time());
        $hour = $date->format("H");

        $tariff = "";
        if ($hour<6) $tariff = "overnight";
        if ($hour>=6 && $hour<11) $tariff = "morning";
        if ($hour>=11 && $hour<16) $tariff = "midday";
        if ($hour>=16 && $hour<20) $tariff = "evening";
        if ($hour>=20) $tariff = "overnight";
        if ($live->hydro>=$live->community) $tariff = "hydro";
        
        $live->tariff = $tariff;
        $content = $live;
        break;
        
    case "hydro/estimate":
        $format = "json";

        $interval = (int) $_GET['interval'];
        if (isset($_GET['lasttime'])) $estimatestart = $_GET['lasttime'];
        if (isset($_GET['lastvalue'])) $lastvalue = $_GET['lastvalue'];
        
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $end = $_GET['end'];
            $start = $_GET['start'];
        
        } else {
            $end = time() * 1000;
            $start = $estimatestart;
        }
        
        $data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$estimatestart&end=$end&interval=$interval&skipmissing=0&limitinterval=1"));
        
        $scale = 1.1;
        
        //$data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$start&end=$end&interval=1800&skipmissing=0&limitinterval=1"));
        
        // Scale ynni padarn peris data and impose min/max limits
        for ($i=0; $i<count($data); $i++) {
            if ($data[$i][1]==null) $data[$i][1] = 0;
            $data[$i][1] = ((($data[$i][1] * 0.001)-4.5) * $scale);
            if ($data[$i][1]<0) $data[$i][1] = 0;
            if ($data[$i][1]>49) $data[$i][1] = 49;
        }
        
        // remove last half hour if null
        if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
        // if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
        
        
        $content = $data;
        
        break;
        
    case "community/estimate":
        $format = "json";
        
        $end = (int) $_GET['lasttime'];
        $interval = (int) $_GET['interval'];
        
        $start = $end - (3600*24.0*7*1000);
        
        $data = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/feed/average.json?id=2&start=$start&end=$end&interval=$interval"));

        $divisions = round((24*3600) / $interval);

        $days = count($data)/$divisions;
        // Quick quality check
        if ($days==round($days)) {
        
            $consumption_profile_tmp = array();
            for ($h=0; $h<$divisions; $h++) $consumption_profile_tmp[$h] = 0;
            
            $i = 0;
            for ($d=0; $d<$days; $d++) {
                for ($h=0; $h<$divisions; $h++) {
                    $consumption_profile_tmp[$h] += $data[$i][1]*1;
                    $i++;
                }
            }
            
            for ($h=0; $h<$divisions; $h++) {
                $consumption_profile_tmp[$h] = $consumption_profile_tmp[$h] / $days;
                $consumption_profile[] = number_format($consumption_profile_tmp[$h],2);
            }
            $content = $consumption_profile;
        } else {
            $content = false;
        }
        
        break;
}

switch ($format) 
{
    case "html":
        header('Content-Type: text/html');
        print $content;
        break;
    case "text":
        header('Content-Type: text/plain');
        print $content;
        break;
    case "json":
        header('Content-Type: application/json');
        print json_encode($content);
        break;
}

class EmonLogger {
    public function __construct() {}
    public function info ($message){ }
    public function warn ($message){ }
}
