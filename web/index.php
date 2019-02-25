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
/*
$url = "https://dashboard.energylocal.org.uk/";

if (isset($_GET['q']) && $_GET['q']!="bethesda") $url .= $_GET['q'];
if (isset($_GET['apikey'])) $url .= "&apikey=".$_GET['apikey'];
if (isset($_GET['lang'])) $url .= "&lang=".$_GET['lang'];

header("Location: $url");*/

date_default_timezone_set('Europe/London');

define('EMONCMS_EXEC', 1);

// ---------------------------------------------------------
// ---------------------------------------------------------

chdir("/var/www/emoncms");
require "process_settings.php";
require "core.php";

$path = get_application_path();

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

if (IS_HUB) {
	$wifisetup = false;
	if (file_exists("Modules/setup")) {
	    require "Modules/setup/setup_model.php";
	    $setup = new Setup($mysqli);
	    if ($setup->status()=="unconfigured") $wifisetup = true;
	}
}
// ---------------------------------------------------------
// ---------------------------------------------------------

if (!IS_HUB) require "Lib/email.php";
if (!defined('IS_HUB')) define('IS_HUB',true);

require("Modules/user/user_model.php");
$user = new User($mysqli,$redis);

require_once "Modules/feed/feed_model.php";
$feed = new Feed($mysqli,$redis,$feed_settings);
$feed->EngineClass(Engine::PHPFINA);
$feed->EngineClass(Engine::PHPTIMESERIES);

$base_url = IS_HUB ? "http://cydynni.org.uk/bethesda/" : "http://localhost/cydynni/";
$emoncms_url = IS_HUB ? 'http://localhost/emoncms/' : 'https://emoncms.cydynni.org.uk/';

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
         
         $tmp = $feed->get_user_feeds($session['userid']);
         $session["feeds"] = array();
         foreach ($tmp as $f) {
             $session["feeds"][$f["name"]] = (int) $f["id"];
         }
    }
}

// Load token
if ($session['read']) {
    $userid = (int) $session["userid"];
    
    if (!IS_HUB) {
        $redis->incr("userhits:$userid");
        
        $result = $mysqli->query("SELECT * FROM cydynni WHERE `userid`='$userid'");
        $row = $result->fetch_object();
        if (isset($row->token)) $session["token"] = $row->token;
    }   
    $result = $mysqli->query("SELECT email,apikey_read,apikey_write FROM users WHERE `id`='$userid'");
    $row = $result->fetch_object();
    $session["email"] = $row->email;
    $session["apikey_read"] = $row->apikey_read;
    $session["apikey_write"] = $row->apikey_write;
}

if (IS_HUB) {
// ---------------------------------------------------------
// manually set club per installation
// ---------------------------------------------------------
	$club = "bethesda";
	$club_settings = array();
	$club_settings[$club] = array(
	    "name"=>"Bethesda",
	    "generator"=>"hydro",
	    "languages"=>array("cy","en"),
	    "generation_feed"=>1,
	    "consumption_feed"=>2
	);
}
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
    $club = "bethesda";
}


chdir("/var/www/cydynni");
require "meter_data_api.php";
if (!IS_HUB) {
    require("lib/cydynni_emails.php");
    $cydynni_emails = new CydynniEmails($mysqli);
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

switch ($q)
{
    case "":
        $format = "html";
        if (!IS_HUB) unset($session["token"]);
        if (!IS_HUB) unset($club_settings[$club]["root_token"]);
        if (IS_HUB) {
            //show register view for hubs
            if ($session["write"]) {
                $content = view("views/client_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
            } else {
                if ($wifisetup) {
                    header('Location: ../emoncms');
                } else {
                    // check register status
                    $register = true; if ($user->get_number_of_users()>0) $register = false;
                    $content = view("views/login_view.php",array('session'=>$session,'register'=>$register));
                }
            }
        }else{
            //show content on web
            $content = view("views/client_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
        }
        break;

    case "report":
        $format = "html";
        if ($session["read"]) {
            if (!IS_HUB) unset($session["token"]);
            if (!IS_HUB) unset($club_settings[$club]["root_token"]);
            
            $content = view("views/report_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
        } else {
            $content = "session not valid";
        }
        break;
        
    case "account":
        $format = "theme";
        if ($session["read"]) {
            if (!IS_HUB) unset($session["token"]);
            if (!IS_HUB) unset($club_settings[$club]["root_token"]);
            
            $content = view("views/account_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
        } else {
            $content = "session not valid";
        }
        break;

    case "find":
        $format = "theme";
        if ($session["read"]) {
            if (!IS_HUB) unset($session["token"]);
            if (!IS_HUB) unset($club_settings[$club]["root_token"]);
            
            $content = view("Modules/find/Views/intro.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
        } else {
            $content = "session not valid";
        }
        break;
        
    case "find/ok":
        $format = "theme";
        if ($session["read"]) {
            if (!IS_HUB) unset($session["token"]);
            if (!IS_HUB) unset($club_settings[$club]["root_token"]);
            
            $content = view("Modules/find/Views/find.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
        } else {
            $content = "session not valid";
        }
        break;
                        
    // ------------------------------------------------------------------------
    // Household 
    // ------------------------------------------------------------------------         
    case "household/summary/day":
        $format = "json";
        if (isset($session["read"]) && $session["read"]) {
            $userid = $session["userid"];
            $content = json_decode($redis->get("user:summary:lastday:$userid"));
        
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp(time());
            $date->modify("midnight");
            $time = $date->getTimestamp();
            if ($content){
                $content->dayoffset = ($time - decode_date($content->date))/(3600*24);
            } else {
                $content = "Invalid data";
            }
        } else {
            $content = "session not valid";
        }

        $content = json_decode(json_encode($content));
        break;
        
    case "household/summary/monthly":
        $format = "json";
        if ($session["read"]) {
            $month = get("month");
            if (IS_HUB) {
                $content = file_get_contents("$base_url/household/summary/monthly?month=$month");
            }else{
                $content = get_household_consumption_monthly($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$session['token']);
            }
        } else {
            $content = "session not valid";
        }
        break;

    case "meter/live":
        if (IS_HUB) {
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
                $content = $feed->get_data($club_settings[$club]["generation_feed"],$start,$end,1800,1,0);
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

        if (!$result = $redis->get("$club:club:summary:day")) {
            if( IS_HUB ) {
                $result = file_get_contents("$base_url/club/summary/day");
                if ($result) $redis->set("community:summary:day",$result);
            }
        }
        $content = json_decode($result);
        
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->setTimestamp(time());
        $date->modify("midnight");
        $time = $date->getTimestamp();
        if ($content){
            $content->dayoffset = ($time - decode_date($content->date))/(3600*24);
        } else {
            $content = "Invalid data";
        }
        break;

    case "club/summary/monthly":
        $format = "json";
        $month = get("month");

        if (IS_HUB) {
            $content = file_get_contents("$base_url/community/summary/monthly?month=$month");
        }else{
            $content = get_club_consumption_monthly($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
        }
        break;
                
    case "club/data":
        $format = "json";
        
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $start = (int) $_GET['start'];
            $end = (int) $_GET['end'];
            
            if ($use_local_cache) {
                $content = $feed->get_data(2,$start,$end,1800,1,0);
            } else {
                $content = get_meter_data_history($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"],29,$start,$end);
            }
            
        } else {
            $content = json_decode($redis->get("$club:club:data"));
        }
        break;
        
    case "live":
        $format = "json";
        $result = $redis->get("$club:live");
        if (IS_HUB) {
            if (!$result) {
                $result = file_get_contents("${base_url}live");
                if ($result) $redis->set("live",$result);
            }
        }
        if ($live = json_decode($result)) {
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
        } else {
            $content = array('success'=>false,'message'=>'Feed not available');
        }
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
        
        $feedid = 166913;
        //$feedid = 384377;
        if ($club=="towerpower") $feedid = 179247;
        
        $url = "https://emoncms.org/feed/average.json?";
        $url .= http_build_query(array("id"=>$feedid,"start"=>$estimatestart,"end"=>$end,"interval"=>$interval,"skipmissing"=>0,"limitinterval"=>1));
        $result = @file_get_contents($url);

        if ($result) {
            $data = json_decode($result);
            if ($data!=null && is_array($data)) {
        
                $scale = 1.1;  
                // Scale ynni padarn peris data and impose min/max limits
                for ($i=0; $i<count($data); $i++) {
                    if ($data[$i][1]==null) $data[$i][1] = 0;
                    if ($club=="bethesda") {
                    
                        $data[$i][1] = ((($data[$i][1] * 0.001)-4.5) * $scale);
                        //$data[$i][1] = $data[$i][1]*0.001;
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
        
        $data = json_decode(file_get_contents($emoncms_url."feed/average.json?id=".$club_settings[$club]["consumption_feed"]."&start=$start&end=$end&interval=$interval"));
    
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
        if (!IS_HUB) {
            $format = "json";
            $content = get_demand_shaper($meter_data_api_baseurl,$club_settings[$club]["api_prefix"],$club_settings[$club]["root_token"]);
        }
        break;
    
    case "epower-api":
        if (!IS_HUB) {
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
        
        $email_or_username = IS_HUB ? post('username') : post('email');

        $content = $user->login($email_or_username,post('password'),false);
        
        // Login with email address if username did not work
        if (!IS_HUB) {
            if ($content["message"]=="Username does not exist") {
                $users = $user->get_usernames_by_email($email_or_username);
                if ($users && count($users)) $content = $user->login($users[0]["username"],post('password'),false);
                else $content = array("success"=>false, "message"=>"User not found");
            }
        }
        
        $session = $_SESSION;
        if (!isset($session['admin'])) $session['admin'] = 0;
        if (isset($session['userid'])) {
        $userid = $session['userid'];
        
        $result = $mysqli->query("SELECT email,apikey_read FROM users WHERE `id`='$userid'");
        $row = $result->fetch_object();
        $session["email"] = $row->email;
        $session["apikey_read"] = $row->apikey_read;      
         
        chdir("/var/www/emoncms");
        $tmp = $feed->get_user_feeds($userid);
        chdir("/var/www/cydynni");
        
        $session["feeds"] = array();
        foreach ($tmp as $f) {
            $session["feeds"][$f["name"]] = (int) $f["id"];
        }
        
        $content["session"] = $session;
        }
        break;

    case "register":
        if (IS_HUB) {
            $format = "json";
            
            if ($user->get_number_of_users()==0)
            {
                $username = $_POST['username'];
                $password = $_POST['password'];
                
                // Send request
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL,"https://emoncms.cydynni.org.uk/user/auth.json");
                curl_setopt($ch,CURLOPT_POST,1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,"username=$username&password=".$password);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                $result = curl_exec($ch);
                curl_close($ch);

                $result = json_decode($result);
                if ($result!=null && isset($result->success) && $result->success) {

                    // Fetch full account details from remote emoncms
                    $u = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/user/get.json?apikey=".$result->apikey_write));

                    // Register account locally
                    $result = $user->register($username, $password, $u->email);

                    // Save remote account apikey to local hub
                    if ($result['success']==true) {
                        $userid = $result['userid'];
                        $mysqli->query("UPDATE users SET apikey_write = '".$u->apikey_write."' WHERE id='$userid'");
                        $mysqli->query("UPDATE users SET apikey_read = '".$u->apikey_read."' WHERE id='$userid'");

                        // Trigger download of user data
                        $sync_script = "/home/pi/cydynni/scripts-hub/cydynni-sync.sh";
                        $sync_logfile = "/home/pi/data/cydynni-sync.log";
                        $redis->rpush("service-runner","$sync_script>$sync_logfile");

                        $content = $user->login($username, $password, false);

                        $content = array("success"=>true);

                    } else {
                        $content = array("success"=>false, "message"=>"error creating account");
                    }
                } else {
                    $content = array("success"=>false, "message"=>"cydynni online account not found");
                }
            }
        }
        break;
    
    case "logout":
        $format = "text";
        $content = $user->logout();
        break;

    case "passwordreset":
        if (!IS_HUB) {    
            $format = "json";
            $user->appname = "Cydynni";
            $users = $user->get_usernames_by_email(get('email'));
            if ($users && count($users)) $content = $user->passwordreset($users[0]["username"],get('email'));
            else $content = array("success"=>false, "message"=>"User not found");
        }   
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
	$content  = "";
        if (IS_HUB) {
            // Hydro
            $redis->set("live",file_get_contents("$base_url/live"));
            $redis->set("hydro:data",file_get_contents("$base_url/hydro"));
            $redis->set("community:data",file_get_contents("$base_url/community/data"));
            $redis->set("community:summary:day",file_get_contents("$base_url/community/summary/day"));
            // Store Updated
            $content = "store updated";
        } else {
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
        }
        break;
        
    // These will only work with public feeds::
    case "feed/data.json":
        $format = "json";
        // Params
        $id = (int) get("id");
        $start = get("start");
        $end = get("end");
        $interval = (int) get("interval");
        $skipmissing = (int) get("skipmissing");
        $limitinterval = (int) get("limitinterval");
        
        $apikeystr = ""; if (isset($_GET['apikey'])) $apikeystr = "&apikey=".$_GET['apikey'];
        
        if (isset($_GET['mode']) && $_GET['mode']=="daily") {
            $result = file_get_contents($emoncms_url."feed/data.json?id=$id&start=$start&end=$end&mode=daily&skipmissing=$skipmissing&limitinterval=$limitinterval".$apikeystr);
        } else {
            $result = file_get_contents($emoncms_url."feed/data.json?id=$id&start=$start&end=$end&interval=$interval&skipmissing=$skipmissing&limitinterval=$limitinterval".$apikeystr);
        }
        
        $content = json_decode($result);
        if ($content==null) $content = $result;
        
        break;
        
    case "feed/average.json":
        $format = "json";
        // Params
        $id = (int) get("id");
        $start = get("start");
        $end = get("end");
        $interval = (int) get("interval");
        
        $apikeystr = ""; if (isset($_GET['apikey'])) $apikeystr = "&apikey=".$_GET['apikey'];
        $params = http_build_query(array("id"=>$id, "start"=>$start, "end"=>$end, "interval"=>$interval));
        //apikey not valid in web mode..?
        $result = @file_get_contents($emoncms_url.'feed/average.json?'.$params.$apikeystr);

        $content = json_decode($result);
        if ($content==null) $content = $result;
        
        break;
        
    case "feed/timevalue.json":
        $format = "json";
        // Params
        $id = (int) get("id");
      
        
        $apikeystr = ""; if (isset($_GET['apikey'])) $apikeystr = "&apikey=".$_GET['apikey'];
        $result = @file_get_contents($emoncms_url.'feed/timevalue.json?id='.$id.$apikeystr);

        $content = json_decode($result);
        if ($content==null) $content = $result;
        
        break;

    // ----------------------------------------------------------------------
    // Administration functions 
    // ----------------------------------------------------------------------
    case "admin":
        if (!IS_HUB) {
            $format = "html";
            unset($session["token"]);
            $content = view("views/admin_view.php",array('session'=>$session));
        }
        break;
        
    case "admin/users":
        if (!IS_HUB) {
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
        }
        break;
        
    case "admin/users/csv":
        if (!IS_HUB) {
            $format = "text";
            if ($session['admin']) {
                // Include data from cydynni table here too
                $result = $mysqli->query("SELECT id,username,email,admin FROM users ORDER BY id ASC");
                $users = array();
                while($row = $result->fetch_object()) {
                    $userid = $row->id;
                    // Include fields from cydynni table
                    $user_result = $mysqli->query("SELECT mpan,welcomedate,reportdate FROM cydynni WHERE `userid`='$userid'");
                    $user_row = $user_result->fetch_object();
                    if ($user_row) {
                        foreach ($user_row as $key=>$val) $row->$key = $user_row->$key;
                    }
                    $row->hits = $redis->get("userhits:$userid");
                    $users[] = $row;
                }
                
                $content = "";
                foreach ($users as $user) {
                    $tmp = array();
                    foreach ($user as $key=>$val) {
                        $tmp[] = $val;
                    }
                    $content .= implode(",",$tmp)."\n";
                }
            }
        }
        break;
        
    case "admin/registeremail":
        if (!IS_HUB) {
            $format = "text";
            if ($session['admin']) {
                $content = $cydynni_emails->registeremail(get('userid'));
            }
        }
        break;
        
    case "admin/change-user-email":
        if (!IS_HUB) {
            $format = "json";
            if ($session['admin']) {
                $content = $user->change_email(get("userid"),get("email"));
            }
        }
        break;

    case "admin/change-user-username":
        if (!IS_HUB) {
            $format = "json";
            if ($session['admin']) {
                $content = $user->change_username(get("userid"),get("username"));
            }
        }
        break;
                
    case "admin/switchuser":
        if (!IS_HUB) {
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
        }
        break;

    case "admin/sendreport":
        if (!IS_HUB) {
            $format = "text";
            if ($session['admin']) {
                $content = $cydynni_emails->send_report_email(get('userid'));
            }
        }
        break;
        
    case "setupguide":
        header("Location: https://github.com/TrystanLea/cydynni/blob/master/docs/userguide.md");
        die;
        break;
} // end switch

switch ($format) 
{
    case "theme":
        header('Content-Type: text/html');
        print view("Theme/cydynni/theme.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club],'content'=>$content));
        break;
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
