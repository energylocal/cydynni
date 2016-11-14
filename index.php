<?php

/*

Source code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
OpenEnergyMonitor VirtualSmartGrid - Open source virtual smart grid renewable energy aggregation and sharing concept with a focus on carbon metrics.

Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

error_reporting(E_ALL);
ini_set('display_errors', 'on');
date_default_timezone_set('Europe/London');

// ---------------------------------------------------------
require "settings.php";
require "core.php";
require "household_process.php";
require "meter_data_api.php";

$path = get_application_path();
$mysqli = @new mysqli($mysql['server'],$mysql['username'],$mysql['password'],$mysql['database']);
// ---------------------------------------------------------
require("user_model.php");
$user = new User($mysqli);
session_start();
$session = $user->status();
// ---------------------------------------------------------

// $redis = new Redis();
// $connected = $redis->connect("127.0.0.1");

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
            $content = array(
                "morningkwh"=>1,
                "middaykwh"=>2,
                "eveningkwh"=>1,
                "overnightkwh"=>2,
                "totalkwh"=>6
            );
        }
        break;
        
    case "community/data":
        $format = "json";
        $content = array(
            "morningkwh"=>100,
            "middaykwh"=>200,
            "eveningkwh"=>100,
            "overnightkwh"=>200,
            "totalkwh"=>600
        );
        break;
        
    case "community/halfhourlydata":
        $format = "json";
        $content = get_meter_data($meter_data_api_baseurl,1,$meter_data_api_hydrotoken,4);
        break;


    // ------------------------------------------------------------------------
    // Emoncms.org feed    
    // ------------------------------------------------------------------------
    case "hydro":
        $format = "json";
        $content = get_meter_data($meter_data_api_baseurl,1,$meter_data_api_hydrotoken,4);
        break;
    
    case "data":
        $format = "json";
        if ($session && isset($session['apikey']))
            $content = get_meter_data($meter_data_api_baseurl,$session['feedid'],$session['apikey'],4);
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

