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
        $content = view("pages/client.php",array('session'=>$session));
        break;

    case "admin":
        $format = "html";
        $content = view("pages/admin.php",array('session'=>$session));
        break;
                
    case "household/data":
        if ($session && isset($session['apikey']) && isset($session['feedid'])) {
            $format = "json";
            $content = get_household_data($session['apikey'],$session['feedid']);
        }
        break;
        
    case "community/data":
        $format = "json";
        $users = $user->userlist();
        
        $data = array("morningkwh"=>0, "middaykwh"=>0, "eveningkwh"=>0, "overnightkwh"=>0, "totalkwh"=>0);
        foreach ($users as $u) {
            if ($u->feedid>0) {
                $userdata = get_household_data($u->apikey,$u->feedid);
                $data["morningkwh"] += $userdata["morningkwh"];
                $data["middaykwh"] += $userdata["middaykwh"];
                $data["eveningkwh"] += $userdata["eveningkwh"];
                $data["overnightkwh"] += $userdata["overnightkwh"];
                $data["totalkwh"] += $userdata["totalkwh"];
            }
        }
        $content = $data;
         
        break;
        
    case "community/halfhourlydata":
        $format = "json";
        $users = $user->userlist();
        
        $start = get("start");
        $end = get("end");
        
        $data = array();
        
        foreach ($users as $u) {
            if ($u->feedid>0) {
                $feedid = $u->feedid; $apikey = $u->apikey;
                $userdata = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=$feedid&start=$start&end=$end&interval=1800&skipmissing=0&limitinterval=0&apikey=$apikey"));
                for ($z=0; $z<count($userdata); $z++) {
                    if (!isset($data[$z])) $data[$z] = array(0,0);
                    $data[$z][0] = $userdata[$z][0];
                    $data[$z][1] += $userdata[$z][1];
                }
            }
        }
        $content = $data;
         
        break;


    // ------------------------------------------------------------------------
    // Emoncms.org feed    
    // ------------------------------------------------------------------------
    case "data":
        $format = "json";
        // Interval
        if (isset($_GET['interval']))
            $content = json_decode(file_get_contents("https://emoncms.org/feed/data.json?id=".get("id")."&start=".get("start")."&end=".get("end")."&interval=".get("interval")."&skipmissing=".get("skipmissing")."&limitinterval=".get("limitinterval")."&apikey=".get("apikey")));
        // Mode
        if (isset($_GET['mode']))
            $content = json_decode(file_get_contents("https://emoncms.org/feed/data.json?id=".get("id")."&start=".get("start")."&end=".get("end")."&mode=".get("mode")."&apikey=".get("apikey")));
        break;

    case "average":
        $format = "json";
        // Interval
        if (isset($_GET['interval']))
            $content = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=".get("id")."&start=".get("start")."&end=".get("end")."&interval=".get("interval")."&skipmissing=".get("skipmissing")."&limitinterval=".get("limitinterval")."&apikey=".get("apikey")));
        // Mode
        if (isset($_GET['mode']))
            $content = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=".get("id")."&start=".get("start")."&end=".get("end")."&mode=".get("mode")."&apikey=".get("apikey")));
            
        break;
                
    case "value":
        $format = "text";
        $content = file_get_contents("https://emoncms.org/feed/value.json?id=".get("id")."&apikey=".get("apikey"));
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

