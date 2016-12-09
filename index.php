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
require "settings.php";
require "core.php";
require "meter_data_api.php";

$path = get_application_path();
//$path = "http://cydynni.org.uk/";
$mysqli = @new mysqli($mysql['server'],$mysql['username'],$mysql['password'],$mysql['database']);
// ---------------------------------------------------------
require("user_model.php");
$user = new User($mysqli);
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

$lang = "en";
if (isset($_GET['lang']) && $_GET['lang']=="cy") $lang = "cy";
if (isset($_GET['iaith']) && $_GET['iaith']=="cy") $lang = "cy";

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
        if ($session) $rsession = array('email'=>$session['email']); else $rsession = false;
        $content = view("pages/report.php",array('session'=>$rsession));
        break;
                
    case "household/data":
        if ($session && isset($session['apikey']) && isset($session['feedid'])) {
            $format = "json";
            $content = get_household_consumption($meter_data_api_baseurl,$session['apikey']);
        }
        break;
        
    case "community/data":
        $format = "json";
        $content = array(
          "kwh"=>array("morning"=>100,"midday"=>200,"evening"=>100,"overnight"=>200,"hydro"=>300,"total"=>900),
          "cost"=>array("morning"=>10,"midday"=>20,"evening"=>10,"overnight"=>20,"hydro"=>30,"total"=>90)
        );
        break;
        
    case "community/halfhourlydata":
        $format = "json";
        $content = get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,11);
        break;


    // ------------------------------------------------------------------------
    // Emoncms.org feed    
    // ------------------------------------------------------------------------
    case "hydro":
        $format = "json";
        $content = get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,4);
        break;
    
    case "data":
        $format = "json";
        if ($session && isset($session['apikey']))
            $content = get_meter_data($meter_data_api_baseurl,$session['apikey'],10);
        break;
    
    // ------------------------------------------------------------------------
    // User    
    // ------------------------------------------------------------------------
    case "status":
        $format = "json";
        $content = $session;
        break;

    case "register":
        $format = "text";
        if ($session['admin']) $content = $user->register(get('email'),get('password'),get('apikey'),get('feedid'));
        break;
        
    case "registeremail":
        $format = "text";
        if ($session['admin']) $content = $user->registeremail(get('userid'));
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
                
    case "login":
        $format = "json";
        $content = $user->login(get('email'),get('password'));
        break;
        
    case "logout":
        $format = "text";
        $content = $user->logout();
        break;
        
    case "admin/users":
        $format = "json";
        if ($session['admin']) $content = $user->userlist();
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

