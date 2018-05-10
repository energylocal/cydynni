<?php
/*

Source code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
CydYnni App - club energy smart grid web app
part of the EnergyLocal CydYnni project in Bethesda North Wales

Developed by OpenEnergyMonitor:
http://openenergymonitor.org

*/

date_default_timezone_set('Europe/London');

define('EMONCMS_EXEC', 1);

// ---------------------------------------------------------
// ---------------------------------------------------------
// $base_url = "http://cydynni.org.uk/bethesda";
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

//$wifisetup = false;
//if (file_exists("Modules/setup")) {
//    require "Modules/setup/setup_model.php";
//    $setup = new Setup($mysqli);
//    if ($setup->status()=="unconfigured") $wifisetup = true;
//}

// ---------------------------------------------------------
// ---------------------------------------------------------

require "Lib/email.php";
require("Modules/user/user_model.php");
$user = new User($mysqli,$redis);


chdir("/var/www/cydynni");

require("lib/cydynni_emails.php");
$cydynni_emails = new CydynniEmails($mysqli);

require "meter_data_api.php";
$path = get_application_path();

require "lib/PHPFina.php";
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
    
    if (!isset($_SESSION['userid'])) $session = false;
    else if ($_SESSION['userid']<1) $session = false;
    else {
         $session = $_SESSION;
         if (!isset($session['admin'])) $session['admin'] = 0;
    }
}

// Load token
if ($session['read']) {
    $userid = (int) $session["userid"];
    
    $redis->incr("userhits:$userid");
    
    $result = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
    $row = $result->fetch_object();
    if (isset($row->token)) $session["token"] = $row->token;
    
    $result = $mysqli->query("SELECT email,apikey_read FROM users WHERE `id`='$userid'");
    $row = $result->fetch_object();
    $session["email"] = $row->email;
    $session["apikey_read"] = $row->apikey_read;
}

// ---------------------------------------------------------

$lang = "";

// 1. Fetch query
$q = ""; if (isset($_GET['q'])) $q = $_GET['q'];

if ($q=="report") $q="bethesda/report";

// 2. Explode into parts
$query_parts = explode("/",$q);

// 3, Default club
if ($query_parts[0]=="") {
    header("Location: bethesda");
}

// 4. Club name is the first parameter
$club = $query_parts[0];

// 5. Check if club exists in root tokens
if (isset($club_settings[$club])) {
    // remove club from query string
    unset($query_parts[0]);
    // rebuild query string without club name
    $q = implode("/",$query_parts);
    $lang = $club_settings[$club]["languages"][0];
} else {
    $club = false;
}

$translation = new stdClass();
$translation->cy = json_decode(file_get_contents("locale/cy"));

if (isset($_GET['lang']) && $_GET['lang']=="cy") $lang = "cy";
if (isset($_GET['iaith']) && $_GET['iaith']=="cy") $lang = "cy";
if (isset($_GET['lang']) && $_GET['lang']=="en") $lang = "en";
if (isset($_GET['iaith']) && $_GET['iaith']=="en") $lang = "en";


$format = "html";
$content = "Sorry page not found";

$logger = new EmonLogger();

if ($club)
{
    switch ($q)
    {
        case "":
            $format = "html";
            unset($session["token"]);
            unset($club_settings[$club]["root_token"]);
            
            $content = view("views/client_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
            break;
            
        case "report":
            $format = "html";
            if ($session["read"]) {
                unset($session["token"]);
                unset($club_settings[$club]["root_token"]);
                
                $content = view("views/report_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
            } else {
                $content = "session not valid";
            }
            break;
            
        case "account":
            $format = "html";
            if ($session["read"]) {
                unset($session["token"]);
                unset($club_settings[$club]["root_token"]);
                
                $content = view("views/account_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
            } else {
                $content = "session not valid";
            }
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

            $content = json_decode(json_encode($content));
            break;
            
        case "household/summary/monthly":
            $format = "json";
            if ($session["read"]) {
                $month = get("month");
                $content = get_household_consumption_monthly($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$session['token']);
            } else {
                $content = "session not valid";
            }
            break;

        /*case "meter/live":
            $format = "json";
            if ($session["read"]) {
                $userid = $session["userid"];
                $content = array(
                  "power"=>$redis->get("meterdata:total_kW"),
                  "kwh"=>$redis->get("meterdata:total_im_kWh")
                );
            } else {
                $content = "session not valid";
            }
            break;*/
        
        // ------------------------------------------------------------------------
        // Generic meter API    
        // ------------------------------------------------------------------------
        case "data":
            $format = "json";
            if ($session["read"]) {
                if (isset($_GET['start']) && isset($_GET['end'])) {
                    $start = (int) $_GET['start'];
                    $end = (int) $_GET['end'];
                    
                    $content = get_meter_data_history($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$session['token'],27,$start,$end);
                } else {
                    $content = get_meter_data($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$session['token'],10);
                }
            } else {
                $content = "session not valid";
            }  
            break;  
            
        // ------------------------------------------------------------------------
        // Historic generation API
        // ------------------------------------------------------------------------
        case "generation":
            $format = "json";
            if (isset($_GET['start']) && isset($_GET['end'])) {
                $start = (int) $_GET['start'];
                $end = (int) $_GET['end'];
                if ($use_local_cache) {
                    $content = $phpfina->get_data($club_settings[$club]["generation_feed"],$start,$end,1800,1,0);
                } else {
                    $content = get_meter_data_history($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"],28,$start,$end);
                }
            } else {
                $content = json_decode($redis->get("$club:generation:data"));
            }
            break;
            
        // ------------------------------------------------------------------------
        // Club data
        // ------------------------------------------------------------------------
        case "club/summary/day":
            $format = "json";
            
            $content = json_decode($redis->get("$club:club:summary:day"));
            
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp(time());
            $date->modify("midnight");
            $time = $date->getTimestamp();
            $content->dayoffset = 0; //($time - decode_date($content->date))/(3600*24);
            //$content->dayoffset = ($time - decode_date($content->date))/(3600*24);
            
            break;

        case "club/summary/monthly":
            $format = "json";
            $month = get("month");
            $content = get_club_consumption_monthly($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
            break;
                    
        case "club/data":
            $format = "json";
            
            if (isset($_GET['start']) && isset($_GET['end'])) {
                $start = (int) $_GET['start'];
                $end = (int) $_GET['end'];
                
                if ($use_local_cache) {
                    $content = $phpfina->get_data(2,$start,$end,1800,1,0);
                } else {
                    $content = get_meter_data_history($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"],29,$start,$end);
                }
                
            } else {
                $content = json_decode($redis->get("$club:club:data"));
            }
            break;
            
        case "live":
            $format = "json";
            
            if (!$result = $redis->get("$club:live")) {
                // $result = http_request("GET","$base_url/live",array());
                // if ($result) $redis->set("live",$result);
            }
            $live = json_decode($result);
                      
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
            if ($live->generation>=$live->club) $tariff = "generation";
            
            $live->tariff = $tariff;
            
            $content = $live;
            break;
            
        case "generation/estimate":
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
            
            $feed = 166913;
            if ($club=="towerpower") $feed = 179247;
            
           $result = http_request("GET","https://emoncms.org/feed/average.json",array("id"=>$feed,"start"=>$estimatestart,"end"=>$end,"interval"=>$interval,"skipmissing"=>0,"limitinterval"=>1));

            if ($result) {
                $data = json_decode($result);
                if ($data!=null && is_array($data)) {
            
                    $scale = 1.1;  
                    // Scale ynni padarn peris data and impose min/max limits
                    for ($i=0; $i<count($data); $i++) {
                        if ($data[$i][1]==null) $data[$i][1] = 0;
                        if ($club=="bethesda") {
                        
                            $data[$i][1] = ((($data[$i][1] * 0.001)-4.5) * $scale);
                            if ($data[$i][1]<0) $data[$i][1] = 0;
                            if ($data[$i][1]>49) $data[$i][1] = 49;
                        } else if ($club=="towerpower") {
                            $data[$i][1] = -1 * $data[$i][1] * 0.001;
                        }
                    }
            
                    // remove last half hour if null
                    if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
            
                    $content = $data;
                } else {
                    $content = $result;
                }
            } else {
                $content = array();
            }  
            
            break;
            
        case "club/estimate":
            $format = "json";
            
            $end = (int) $_GET['lasttime'];
            $interval = (int) $_GET['interval'];
            
            $start = $end - (3600*24.0*7*1000);
            
            $data = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/feed/average.json?id=".$club_settings[$club]["consumption_feed"]."&start=$start&end=$end&interval=$interval"));

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
            $content = get_demand_shaper($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
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

                    $result = @file_get_contents($meter_data_api_baseurl.$club_settings[$club]["api_prefix"]."-$token-$api".$other);
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
            
            $email_or_username = post('email');
            $content = $user->login($email_or_username,post('password'),false);
            
            // Login with email address if username did not work
            if ($content["message"]=="Username does not exist") {
                $users = $user->get_usernames_by_email($email_or_username);
                if ($users && count($users)) $content = $user->login($users[0]["username"],post('password'),false);
                else $content = array("success"=>false, "message"=>"User not found");
            }
            
            break;
            
        case "logout":
            $format = "text";
            $content = $user->logout();
            break;
            
        case "passwordreset":
            $format = "json";
            $user->appname = "Cydynni";
            $users = $user->get_usernames_by_email(get('email'));
            if ($users && count($users)) $content = $user->passwordreset($users[0]["username"],get('email'));
            else $content = array("success"=>false, "message"=>"User not found");
            
            break;
            
        case "changepassword":
            $format = "text";
            if ($session["write"]) {
                $content = $user->change_password($session['userid'], post("old"), post("new"));
            } else {
                $content = "session not valid";
            }
            break;
            
        case "update":
            $format = "text";
            $content = "";
            
            // generation
            $result = get_meter_data($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"],4);
            if (count($result)>0) $redis->set("$club:generation:data",json_encode($result));
            // Club half-hour
            $result = get_meter_data($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"],11);
            if (count($result)>0) $redis->set("$club:club:data",json_encode($result));
            // Club totals
            $content .= "$club:summary:day: ";
            $result = get_club_consumption($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
            if ($result!="invalid data") {
                $redis->set("$club:club:summary:day",json_encode($result));
                $content .= json_encode($result)."\n";
            } else {
                $content .= "invalid\n";
            }
            break;
    }

}
else
{
    switch ($q)
    {     
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

        // ----------------------------------------------------------------------
        // Administration functions
        // ----------------------------------------------------------------------
        case "admin":
            $format = "html";
            unset($session["token"]);
            $content = view("views/admin_view.php",array('session'=>$session));
            break;
            
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
            
        case "admin/registeremail":
            $format = "text";
            if ($session['admin']) {
                $content = $cydynni_emails->registeremail(get('userid'));
            }
            break;
            
        case "admin/change-user-email":
            $format = "json";
            if ($session['admin']) {
                $content = $user->change_email(get("userid"),get("email"));
            }
            break;

        case "admin/change-user-username":
            $format = "json";
            if ($session['admin']) {
                $content = $user->change_username(get("userid"),get("username"));
            }
            break;
                    
        case "admin/switchuser":
            $format = "text";
            if ($session['admin']) {
                $userid = (int) get("userid");
                
                $_SESSION["userid"] = $userid;
                
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
                $content = $cydynni_emails->send_report_email(get('userid'));
            }
            break;
    }
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
        echo $s;
    }
}

function translate($s,$lang) {
    global $translation;
    
    if (isset($translation->$lang) && isset($translation->$lang->$s)) {
        return $translation->$lang->$s;
    } else { 
        return $s;
    }
}
