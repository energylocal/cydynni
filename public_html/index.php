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

error_reporting(E_ALL);
ini_set('display_errors', 'on');
date_default_timezone_set('Europe/London');

// ---------------------------------------------------------
$test_user = 59;
// ---------------------------------------------------------
require "settings.php";
require "core.php";
require "meter_data_api.php";
require "mysql_store.php";

$path = get_application_path();
$mysqli = @new mysqli($mysql['server'],$mysql['username'],$mysql['password'],$mysql['database']);

$redis = new Redis();
$connected = $redis->connect("localhost");
        
// ---------------------------------------------------------
require("user_model.php");
$user = new User($mysqli);

ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
session_start();
$session = $user->status();

if ($session) {
    $userid = (int) $session['userid'];
    $mysqli->query("UPDATE users SET hits=hits+1 WHERE `id`='$userid'");
}

// ---------------------------------------------------------

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

    case "test":
        $content = "new server";
        break;

    case "":
        $format = "html";
        if ($session) $rsession = array('email'=>$session['email']); else $rsession = false;
        $content = view("pages/client.php",array('session'=>$rsession));
        break;

    case "admin":
        $format = "html";
        if ($session) $rsession = array('email'=>$session['email']); else $rsession = false;
        $content = view("pages/admin.php",array('session'=>$rsession));
        break;
        
    case "report":
        $format = "html";
        if (isset($_GET["reportkey"])) $session = $user->check_reportkey($_GET["reportkey"]);
        if ($session) $rsession = array('email'=>$session['email']); else $rsession = false;
        if ($session) $content = view("pages/report.php",array('session'=>$rsession));
        break;
  
    // ------------------------------------------------------------------------
    // Household 
    // ------------------------------------------------------------------------     
    case "household/summary/day":
        if ($session && isset($session['apikey'])) {
            $format = "json";
            $content = get_household_consumption($meter_data_api_baseurl,$session['apikey']);
        }
        break;
        
    case "household/summary/month":
        if ($session && isset($session['apikey'])) {
            $format = "json";
            $content = get_household_consumption_monthly($meter_data_api_baseurl,$session['apikey']);
        }
        break;
        
    // ------------------------------------------------------------------------
    // Generic meter API    
    // ------------------------------------------------------------------------
    case "data":
        $format = "json";
        if ($session && isset($session['apikey'])) {
            if (isset($_GET['start']) && isset($_GET['end'])) {
                $start = (int) $_GET['start'];
                $end = (int) $_GET['end'];
                $content = get_meter_data_history($meter_data_api_baseurl,$session['apikey'],27,$start,$end);
            } else {
                $content = get_meter_data($meter_data_api_baseurl,$session['apikey'],10);
            }
        }
        break;  
        
    // ------------------------------------------------------------------------
    // Historic hydro API
    // ------------------------------------------------------------------------
    case "hydro":
        $format = "json";
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $start = (int) $_GET['start'];
            $end = (int) $_GET['end'];
            $content = get_meter_data_history($meter_data_api_baseurl,$meter_data_api_hydrotoken,28,$start,$end);
        } else {
            $content = json_decode($redis->get("hydro:data"));
        }
        break;
        
    // ------------------------------------------------------------------------
    // Special forecast API that uses hydro data from YnniPadarnPeris
    // ------------------------------------------------------------------------
    case "hydro/forecast":
        $format = "json";

        if (isset($_GET['lasttime'])) $forecaststart = $_GET['lasttime'];
        if (isset($_GET['lastvalue'])) $lastvalue = $_GET['lastvalue'];
        
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $end = $_GET['end'];
            $start = $_GET['start'];
        
        } else {
            $end = time() * 1000;
            $start = $forecaststart;
        }
        
        $data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$forecaststart&end=$end&interval=1800&skipmissing=0&limitinterval=1"));
        $scale = $lastvalue / (($data[0][1]*0.001)-7.8);
        //if ($scale>1.3) $scale = 1.3;
        
        
        // $data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$start&end=$end&interval=1800&skipmissing=0&limitinterval=1"));
        
        // Scale ynni padarn peris data and impose min/max limits
        for ($i=0; $i<count($data); $i++) {
            $data[$i][1] = ((($data[$i][1] * 0.001)-7.8) * $scale);
            if ($data[$i][1]<0) $data[$i][1] = 0;
            if ($data[$i][1]>49) $data[$i][1] = 49;
        }
        
        // remove last half hour if null
        if ($data[count($data)-2][1]==null) unset($data[count($data)-2]);
        if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
        
        $content = $data;
        
        break;

    // ------------------------------------------------------------------------
    // Community data
    // ------------------------------------------------------------------------
    case "community/summary/day":
        $format = "json";
        $content = json_decode($redis->get("community:summary:day"));
        break;
        
    case "community/summary/month":
        $format = "json";
        $content = get_community_consumption_monthly($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        break;
        
    case "community/data":
        $format = "json";
        
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $start = (int) $_GET['start'];
            $end = (int) $_GET['end'];
            $content = get_meter_data_history($meter_data_api_baseurl,$meter_data_api_hydrotoken,29,$start,$end);
        } else {
            $content = json_decode($redis->get("community:data"));
        }
        break;

    // ------------------------------------------------------------------------
    // Consumption forecast based on last 7 days
    // ------------------------------------------------------------------------
    case "community/forecast":
        $format = "json";

        $end = time();
        $start = $end - (3600*24.0*7);
        $data = get_meter_data_history($meter_data_api_baseurl,$meter_data_api_hydrotoken,29,$start*1000,$end*1000);
        
        $days = count($data)/48;
        
        // Quick quality check
        if ($days==round($days)) {
        
            $consumption_profile_tmp = array();
            for ($h=0; $h<48; $h++) $consumption_profile_tmp[$h] = 0;
            
            $i = 0;
            for ($d=0; $d<$days; $d++) {
                for ($h=0; $h<48; $h++) {
                    $consumption_profile_tmp[$h] += $data[$i][1]*1;
                    $i++;
                }
            }
            
            for ($h=0; $h<48; $h++) {
                $consumption_profile_tmp[$h] = $consumption_profile_tmp[$h] / $days;
                $consumption_profile[] = number_format($consumption_profile_tmp[$h],2);
            }
            $content = $consumption_profile;
        } else {
            $content = false;
        }
        
        break;
        
    // ------------------------------------------------------------------------
    // User    
    // ------------------------------------------------------------------------
    case "status":
        $format = "json";
        $content = $session;
        break;
                
    case "login":
        $format = "json";
        $content = $user->login(get('email'),get('password'));
        break;
        
    case "logout":
        $format = "text";
        $content = $user->logout();
        break;
        
    case "passwordreset":
        $format = "text";
        $content = $user->passwordreset(get('email'));
        break;
        
    case "changepassword":
        $format = "text";
        if ($session && isset($session['userid']) && $session['userid']>0) {
            $content = $user->change_password($session['userid'], post("old"), post("new"));
        } else {
            $content = "session not valid";
        }
        break;
        
    // ----------------------------------------------------------------------
    // Administration functions
    // ----------------------------------------------------------------------
    case "admin/users":
        $format = "json";
        if ($session['admin']) {
            $content = $user->userlist();
        }
        break;
        
    case "admin/reportlist":
        $format = "text";
        if ($session['admin']) {
            $users = $user->userlist();
            $content = "";
            foreach ($users as $user) {
                $content .= $user->email.", https://cydynni.org.uk/report?reportkey=".$user->reportkey."&lang=cy\n";
            
            }
        }
        break;
        
    case "admin/register":
        $format = "text";
        if ($session['admin']) {
            $content = $user->register(get('email'),get('password'),get('apikey'));
        }
        break;
        
    case "admin/registeremail":
        $format = "text";
        if ($session['admin']) {
            $content = $user->registeremail(get('userid'));
        }
        break;
        
    case "admin/check-household-breakdown":
        $format = "json";
        if ($session['admin']) {
            $u = $user->getbyid(get('userid'));
            $content = get_household_consumption($meter_data_api_baseurl,$u["apikey"]);
        }
        break;
        
    case "admin/change-user-email":
        $format = "text";
        if ($session['admin']) {
            $content = $user->change_email(get("userid"),get("email"));
        }
        break;
        
    case "admin/switchuser":
        $format = "text";
        if ($session['admin']) {
            $userid = get("userid");
            $_SESSION["userid"] = $userid;
            $u = $user->getbyid($userid);
            $_SESSION["apikey"] = $u["apikey"];
            $_SESSION['email'] = $u['email'];
            $content = "User switched";
        }
        header('Location: '."http://cydynni.org.uk/#household");
        break;

    case "admin/sendreport":
        $format = "text";
        if ($session['admin']) {
            $content = $user->send_report_email(get('userid'));
        }
        break;
        
    case "admin/cron":
        $format = "text";
        // Hydro
        $content = get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,4);
        $redis->set("hydro:data",json_encode($content));
        // Community half-hour
        $content = get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,11);
        $redis->set("community:data",json_encode($content));
        // Community totals
        $content = get_community_consumption($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        $redis->set("community:summary:day",json_encode($content));
        // Store Updated
        $content = "store updated";
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
