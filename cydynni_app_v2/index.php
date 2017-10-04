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

date_default_timezone_set('Europe/London');

define('EMONCMS_EXEC', 1);

// ---------------------------------------------------------
// ---------------------------------------------------------

chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";

// Connect to MYSQL
$mysqli = @new mysqli($server,$username,$password,$database,$port);
if ( $mysqli->connect_error ) {
    echo "Can't connect to database, please verify credentials/configuration in settings.php<br />";
    if ( $display_errors ) {
        echo "Error message: <b>" . $mysqli->connect_error . "</b>";
    }
    die();
}
// Set charset to utf8
$mysqli->set_charset("utf8");

// Connect to Redis
$redis = new Redis();
$connected = $redis->connect($redis_server['host'], $redis_server['port']);
if (!$connected) { echo "Can't connect to redis at ".$redis_server['host'].":".$redis_server['port']." , it may be that redis-server is not installed or started see readme for redis installation"; die; }

// ---------------------------------------------------------
// ---------------------------------------------------------

chdir("/var/www/develop");

require("user_model.php");
$user = new User($mysqli);

require "meter_data_api.php";
$path = get_application_path();

require "PHPFina.php";
$phpfina = new PHPFina(array("datadir"=>"/var/lib/phpfina/"));
$use_local_cache = true;

$apikey = false;
if (isset($_GET['reportkey'])) $apikey = $_GET['reportkey'];
if (isset($_GET['apikey'])) $apikey = $_GET['apikey'];

if ($apikey) {
    $session = $user->apikey_session($apikey);
} else {
    ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
    session_start();
    $session = $user->status();
}

// Load token
if ($session['read']) {
    $userid = (int) $session["userid"];
    
    $redis->incr("userhits:$userid");
    
    $result = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
    $row = $result->fetch_object();
    $session["token"] = $row->token;
    
    $result = $mysqli->query("SELECT email,apikey_read FROM users WHERE `id`='$userid'");
    $row = $result->fetch_object();
    $session["email"] = $row->email;
    $session["apikey_read"] = $row->apikey_read;
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
        unset($session["token"]);
        $content = view("client.php",array('session'=>$session));
        break;
        
    // ------------------------------------------------------------------------
    // Household 
    // ------------------------------------------------------------------------         
    case "household/summary/day":
        $format = "json";
        if ($session["read"]) {
            $userid = $session["userid"];
            $content = json_decode($redis->get("user:summary:lastday:$userid"));
        
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp(time());
            $date->modify("midnight");
            $time = $date->getTimestamp();
            $content->dayoffset = ($time - decode_date($content->date))/(3600*24);
            
        } else {
            $content = "session not valid";
        }
        
        $content = array("kwh"=>array("morning"=>0.5,"midday"=>0.2,"evening"=>1.0,"overnight"=>0.5,"hydro"=>2.0,"total"=>4.2));
        $content = json_decode(json_encode($content));
        break;
        
    case "household/summary/monthly":
        $format = "json";
        if ($session["read"]) {
            $month = get("month");
            $content = get_household_consumption_monthly($meter_data_api_baseurl,$session['token']);
        } else {
            $content = "session not valid";
        }
        break;
        
    // ------------------------------------------------------------------------
    // Generic meter API    
    // ------------------------------------------------------------------------
    case "data":
        $format = "json";
        if ($session["read"]) {
            if (isset($_GET['start']) && isset($_GET['end'])) {
                $start = (int) $_GET['start'];
                $end = (int) $_GET['end'];
                
                $content = get_meter_data_history($meter_data_api_baseurl,$session['token'],27,$start,$end);
            } else {
                $content = get_meter_data($meter_data_api_baseurl,$session['token'],10);
            }
        } else {
            $content = "session not valid";
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
            if ($use_local_cache) {
                $content = $phpfina->get_data(1,$start,$end,1800,1,0);
            } else {
                $content = get_meter_data_history($meter_data_api_baseurl,$meter_data_api_hydrotoken,28,$start,$end);
            }
        } else {
            $content = json_decode($redis->get("hydro:data"));
        }
        break;
        
    // ------------------------------------------------------------------------
    // Community data
    // ------------------------------------------------------------------------
    case "community/summary/day":
        $format = "json";
        
        $content = json_decode($redis->get("community:summary:day"));
        
        // $content = array("kwh"=>array("morning"=>4,"midday"=>3,"evening"=>5,"overnight"=>1.0,"hydro"=>30,"total"=>43));
        $content = json_decode(json_encode($content));
        
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->setTimestamp(time());
        $date->modify("midnight");
        $time = $date->getTimestamp();
        $content->dayoffset = 0; //($time - decode_date($content->date))/(3600*24);
        
        break;

    case "community/summary/monthly":
        $format = "json";
        $month = get("month");
        $content = get_community_consumption_monthly($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        break;
                
    case "community/data":
        $format = "json";
        
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $start = (int) $_GET['start'];
            $end = (int) $_GET['end'];
            
            if ($use_local_cache) {
                $content = $phpfina->get_data(2,$start,$end,1800,1,0);
            } else {
                $content = get_meter_data_history($meter_data_api_baseurl,$meter_data_api_hydrotoken,29,$start,$end);
            }
            
        } else {
            $content = json_decode($redis->get("community:data"));
        }
        break;

    // These will only work with public feeds::
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
        
    case "live":
        $format = "json";
        
        // $redis->set("live",file_get_contents("https://cydynni.org.uk/live"));
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
        
        // $data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$start&end=$end&interval=1800&skipmissing=0&limitinterval=1"));
        
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
            $content = "session not valid";
        }
        
        break;
    
    case "demandshaper":
        $format = "json";
        $content = get_demand_shaper($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        break;

    
    case "epower-api":
        $format = "text";
        if ($session["read"]) {
            $token = $session['token'];
            
            if (isset($_GET['api'])) {
                $api = (int) $_GET['api'];
                
                $other = "";
                if (isset($_GET['start']) && isset($_GET['end'])) {
                    $other = "?dateStart=".$_GET['start']."&dateEnd=".$_GET['end'];
                }

                $result = @file_get_contents($meter_data_api_baseurl."1-$token-$api".$other);
                $json = json_decode(substr($result,2));
                $content = json_encode($json,JSON_PRETTY_PRINT);
            } else {
            
            }
        } else {
            $content = "session not valid";
        }
        break;
    
    // ------------------------------------------------------------------------
    // User    
    // ------------------------------------------------------------------------
    case "status":
        $format = "json";
        unset($session["token"]);
        $content = $session;
        break;
                
    case "login":
        $format = "json";
        $content = $user->login(post('email'),post('password'));
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
        if ($session["write"]) {
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
            // Include data from cydynni table here too
            $result = $mysqli->query("SELECT id,username,email,apikey_read,admin FROM users ORDER BY id ASC");
            $users = array();
            while($row = $result->fetch_object()) {
                $userid = $row->id;
                // Include fields from cydynni table
                $user_result = $mysqli->query("SELECT mpan,token,welcomedate,reportdate FROM cydynni WHERE `userid`='$userid'");
                $user_row = $user_result->fetch_object();
                if ($user_row) {
                    foreach ($user_row as $key=>$val) $row->$key = $user_row->$key;
                }
                $row->hits = $redis->get("userhits:$userid");
                $row->testdata = json_decode($redis->get("user:summary:lastday:$userid"));
                $users[] = $row;
            }
            $content = $users;
        }
        break;
    
    // Register from script    
    // case "admin/register":
    //    $format = "text";
    //    if ($session['admin']) {
    //        $content = $user->register(get('email'),get('password'),get('apikey'));
    //    }
    //    break;
        
    case "admin/registeremail":
        $format = "text";
        if ($session['admin']) {
            $content = $user->registeremail(get('userid'));
        }
        break;
        
    case "admin/change-user-email":
        $format = "json";
        if ($session['admin']) {
            $content = $user->change_email(get("userid"),get("email"));
        }
        break;
        
    case "admin/switchuser":
        $format = "text";
        if ($session['admin']) {
            $userid = (int) get("userid");
            
            // fetch email
            $u = $user->getbyid($userid);
            $_SESSION["userid"] = $userid;
            $_SESSION['email'] = $u->email;
            
            // fetch token
            $result = $mysqli->query("SELECT token FROM cydynni WHERE `userid`='$userid'");
            $row = $result->fetch_object();
            $_SESSION['token'] = $row->token;
            
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
        if (count($content)>0) $redis->set("hydro:data",json_encode($content));
        // Community half-hour
        $content = get_meter_data($meter_data_api_baseurl,$meter_data_api_hydrotoken,11);
        if (count($content)>0) $redis->set("community:data",json_encode($content));
        // Community totals
        $content = get_community_consumption($meter_data_api_baseurl,$meter_data_api_hydrotoken);
        if ($content!="invalid data") $redis->set("community:summary:day",json_encode($content));
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

function t($s) {
    global $translation,$lang;
    
    if (isset($translation->$lang) && isset($translation->$lang->$s)) {
        echo $translation->$lang->$s;
    } else { 
    
        // $fh = fopen("lang.log","a");
        // fwrite($fh,'"'.$s.'":""'.",\n");
        // fclose($fh);
        
        echo $s;
    }
}
