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
$base_url = "http://cydynni.org.uk/bethesda";
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

$wifisetup = false;
if (file_exists("Modules/setup")) {
    require "Modules/setup/setup_model.php";
    $setup = new Setup($mysqli);
    if ($setup->status()=="unconfigured") $wifisetup = true;
}

// ---------------------------------------------------------
// ---------------------------------------------------------

chdir("/var/www/cydynni");

require("lib/user_model.php");
$user = new User($mysqli);

$path = get_application_path();

require "lib/PHPFina.php";
$phpfina = new PHPFina(array("datadir"=>"/home/pi/data/phpfina/"));
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

$club = "bethesda";
$club_settings = array(
    "name"=>"Bethesda",
    "generator"=>"hydro",
    "languages"=>array("cy","en"),
    "generation_feed"=>1,
    "consumption_feed"=>2
);

$logger = new EmonLogger();
switch ($q)
{
    case "":
        $format = "html";
        
        if ($session["write"]) {
            $content = view("views/client_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings));
        } else {
        
            if ($wifisetup) {
                header('Location: ../emoncms');
            } else {
        
                // check register status
                $register = true; if ($user->get_number_of_users()>0) $register = false;
                $content = view("views/login_view.php",array('session'=>$session,'register'=>$register));
            }
        }
        break;

    case "report":
        $format = "html";
        if ($session["read"]) {
            $content = view("views/report_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings));
        } else {
            $content = "session not valid";
        }
        break;
        
    case "account":
        $format = "html";
        if ($session["read"]) {
            $content = view("views/account_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings));
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
        
    case "meter/live":
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
        break;
    
    /*
    case "household/summary/monthly":
        $format = "json";
        if ($session["read"]) {
            $month = get("month");
            $content = get_household_consumption_monthly($meter_data_api_baseurl,$session['token']);
        } else {
            $content = "session not valid";
        }
        break;
    */
        
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
            $content = $phpfina->get_data(1,$start,$end,1800,1,0);
        } else {
            $content = json_decode($redis->get("hydro:data"));
        }
        break;
        
    // ------------------------------------------------------------------------
    // Club data
    // ------------------------------------------------------------------------
    case "club/summary/day":
        $format = "json";

        if (!$result = $redis->get("community:summary:day")) {
            $result = http_request("GET","$base_url/club/summary/day",array());
            if ($result) $redis->set("community:summary:day",$result);
        }
        $content = json_decode($result);
        
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->setTimestamp(time());
        $date->modify("midnight");
        $time = $date->getTimestamp();
        $content->dayoffset = ($time - decode_date($content->date))/(3600*24);
        
        break;

    case "club/summary/monthly":
        $format = "json";
	$month = get("month");
        $content = file_get_contents("$base_url/community/summary/monthly?month=$month");
        break;
                
    case "club/data":
        $format = "json";
        
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $start = (int) $_GET['start'];
            $end = (int) $_GET['end'];
            $content = $phpfina->get_data(2,$start,$end,1800,1,0);
        } else {
            $content = json_decode($redis->get("community:data"));
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

        $result = json_decode(file_get_contents("http://localhost/emoncms/feed/data.json?id=$id&start=$start&end=$end&interval=$interval&skipmissing=$skipmissing&limitinterval=$limitinterval".$apikeystr));
        
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
        
        $result = file_get_contents("http://localhost/emoncms/feed/average.json?id=$id&start=$start&end=$end&interval=$interval".$apikeystr);
        
        $content = json_decode($result);
        if ($content==null) $content = $result;
        
        break;
        
    case "live":
        $format = "json";
        
        if (!$result = $redis->get("live")) {
            $result = http_request("GET","$base_url/live",array());
            if ($result) $redis->set("live",$result);
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
        if ($live->generation>=$live->club) $tariff = "hydro";
        
//        $live->club = $live->community;
        
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
        
        $result = http_request("GET","https://emoncms.org/feed/average.json",array("id"=>166913,"start"=>$estimatestart,"end"=>$end,"interval"=>$interval,"skipmissing"=>0,"limitinterval"=>1));
        
        if ($result) {
            $data = json_decode($result);
            if ($data!=null && is_array($data)) {

                $scale = 1.1;
            
                // Scale ynni padarn peris data and impose min/max limits
                for ($i=0; $i<count($data); $i++) {
                    if ($data[$i][1]==null) $data[$i][1] = 0;
                    $data[$i][1] = ((($data[$i][1] * 0.001)-4.5) * $scale);
                    if ($data[$i][1]<0) $data[$i][1] = 0;
                    if ($data[$i][1]>49) $data[$i][1] = 49;
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
        
        $end = $_GET['lasttime'] * 1;
        $interval = (int) $_GET['interval'];
        
        $start = $end - (3600*24.0*7*1000);
        
        $data = json_decode(file_get_contents("http://localhost/emoncms/feed/average.json?id=2&start=$start&end=$end&interval=$interval"));
        
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
        $content = $user->login(post('username'),post('password'));
        break;

    case "register":
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
                    $sync_flag = "/tmp/emoncms-flag-sync";
                    $sync_script = "/home/pi/cydynni/scripts-hub/cydynni-sync.sh";
                    $sync_logfile = "/home/pi/data/cydynni-sync.log";
                    $fh = @fopen($sync_flag,"w");
                    if ($fh) fwrite($fh,"$sync_script>$sync_logfile");
                    @fclose($fh);
                    
                    $content = $user->login($username, $password);
                    
                    $content = array("success"=>true);
                    
                } else {
                    $content = array("success"=>false, "message"=>"error creating account");
                }
            } else {
                $content = array("success"=>false, "message"=>"cydynni online account not found");
            }
        }
        
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
        
    case "admin/cron":
        $format = "text";
        // Hydro
        $redis->set("live",file_get_contents("$base_url/live"));
        $redis->set("hydro:data",file_get_contents("$base_url/hydro"));
        $redis->set("community:data",file_get_contents("$base_url/community/data"));
        $redis->set("community:summary:day",file_get_contents("$base_url/community/summary/day"));
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
        echo $s;
    }
}

// -------------------------------------------------------------
// Convert date of form: November, 02 2016 00:00:00 to unix timestamp
// -------------------------------------------------------------
function decode_date($datestr) {
    $datestr = str_replace(",","",$datestr);
    $date_parts = explode(" ",$datestr);
    if (count($date_parts)!=4) return "invalid date string";
    $date2 = $date_parts[1]." ".$date_parts[0]." ".$date_parts[2];
    
    $day = $date_parts[1];
    $month = $date_parts[0];
    $year = $date_parts[2];
    
    $months = array("January"=>1,"February"=>2,"March"=>3,"April"=>4,"May"=>5,"June"=>6,"July"=>7,"August"=>8,"September"=>9,"October"=>10,"November"=>11,"December"=>12);
    
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("Europe/London"));
    $date->setDate($year,$months[$month],$day);
    $date->setTime(0,0,0);
    
    //$date->modify("midnight");
    $time = $date->getTimestamp();
    // November, 02 2016 00:00:00
    // print $date2."\n";
    // Mid night start of day
    return $time; //strtotime($date2);
}
