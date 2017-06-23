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
require "test_user.php";

$path = get_application_path();
$mysqli = @new mysqli($mysql['server'],$mysql['username'],$mysql['password'],$mysql['database']);
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
                        
    case "household/data":
        if ($session && isset($session['apikey'])) {
            $format = "json";
            $content = get_household_consumption($meter_data_api_baseurl,$session['apikey']);
        }
        if (isset($session["userid"]) && $session["userid"]==$test_user) $content = $test_user_household_last_day_summary;
        break;
        
    case "household/monthlydata":
        if ($session && isset($session['apikey'])) {
            $format = "json";
            $content = get_household_consumption_monthly($meter_data_api_baseurl,$session['apikey']);
        }
        
        if (isset($session["userid"]) && $session["userid"]==59) $content = json_decode('[{"month":1,"year":2017,"kwh":{"morning":8.37,"midday":12.14,"evening":34.28,"overnight":25.33,"hydro":200.78,"total":280.9},"cost":{"morning":3.89,"midday":4.98,"evening":7.56,"overnight":9.64,"total":26.07}},{"month":12,"year":2016,"kwh":{"morning":24.72,"midday":40.23,"evening":61.59,"overnight":66.16,"hydro":75.5,"total":268.2},"cost":{"morning":3.81,"midday":6.29,"evening":8.19,"overnight":9.85,"total":28.14}}]');
        
        break;
        
    case "community/data":
        $format = "json";
        $content = json_decode(mysql_store_get($mysqli,"community:totals"));
        //$content = get_community_consumption($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        break;
        
    case "community/halfhourlydata":
        $format = "json";
        $content = json_decode(mysql_store_get($mysqli,"community:halfhour"));
        //$content = get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,11);
        break;

    case "community/monthlydata":
        $format = "json";
        $content = get_community_consumption_monthly($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        break;
        
    // ------------------------------------------------------------------------
    // Emoncms.org feed    
    // ------------------------------------------------------------------------
    case "hydro":
        $format = "json";
        $content = json_decode(mysql_store_get($mysqli,"hydro"));
        // get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,4);
        // test user:
        // if (isset($session["userid"]) && $session["userid"]==$test_user) $content = $test_user_hydro_get_meter_data; 
        break;
        
    case "hydrohistory":
        $format = "json";
        $content = get_meter_data_history($meter_data_api_baseurl,$meter_data_api_hydrotoken,4);
        break;
    
    case "data":
        $format = "json";
        if ($session && isset($session['apikey'])) $content = get_meter_data($meter_data_api_baseurl,$session['apikey'],10);
        // test user:
        if (isset($session["userid"]) && $session["userid"]==$test_user) $content = $test_user_household_meter_data;
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
        mysql_store_set($mysqli,"hydro",json_encode($content));
        // Community half-hour
        $content = get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,11);
        mysql_store_set($mysqli,"community:halfhour",json_encode($content));
        // Community totals
        $content = get_community_consumption($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        mysql_store_set($mysqli,"community:totals",json_encode($content));
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
