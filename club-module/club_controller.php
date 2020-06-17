<?php
/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/



// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function club_controller()
{
    global $mysqli, $redis, $session, $route, $user, $settings, $available_clubs;
    global $club_settings, $club;
    global $lang;

    if (isset($_GET['lang']) && $_GET['lang']=="cy") $session['lang'] = "cy_GB";
    if (isset($_GET['iaith']) && $_GET['iaith']=="cy") $session['lang'] = "cy_GB";
    if (isset($_GET['lang']) && $_GET['lang']=="en") $session['lang'] = "en_GB";
    if (isset($_GET['iaith']) && $_GET['iaith']=="en") $session['lang'] = "en_GB";
    $lang = $session["lang"];
    
    $result = false;
    $route->format = "json";
    
    require "Modules/club/club_model.php";
    $club_model = new Club($mysqli,$redis);
    
    if ($club=="repower") {
        $session['lang'] = "en_GB";
        $lang = $session["lang"];
    }
    
    $tariff_history = $club_settings[$club]['tariff_history'];
    $tariffs = $tariff_history[count($tariff_history)-1]["tariffs"];
    
	  global $translation;
	  $translation = new stdClass();
    $translation->cy_GB = json_decode(file_get_contents("Modules/club/app/locale/cy_GB"));

    if ($session["read"]) {
        $userid = (int) $session["userid"];
                
        $result = $mysqli->query("SELECT email,apikey_read FROM users WHERE `id`='$userid'");
        $row = $result->fetch_object();
        $session["email"] = $row->email;
        $session["apikey_read"] = $row->apikey_read;
    }
    
    switch ($route->action)
    {
        case "":
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                
                require_once "Modules/feed/feed_model.php";
                $feed = new Feed($mysqli,$redis,$settings["feed"]);
                
                $tmp = $feed->get_user_feeds($userid);
                
                $session["feeds"] = array();
                foreach ($tmp as $f) {
                    $session["feeds"][$f["name"]] = (int) $f["id"];
                }
                if (!$session["admin"]) $redis->incr("userhits:$userid");
            }

            $route->format = "html";

            $content = view("Modules/club/app/client_view.php", array(
                'is_hub' => $settings["cydynni"]["is_hub"], 
                'session' => $session,'club' => $club,
                'club_settings' => $club_settings[$club],
                'tariffs_table' => $club_model->getTariffsTable($tariffs),
                'tariffs' => $tariffs
            ));

            return array('content'=>$content,'page_classes'=>array('collapsed','manual'));
            break;

        case "report":
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                if (!$session["admin"]) $redis->incr("userhits:$userid");
                $route->format = "html";
                return view("Modules/club/app/report_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
            }
            break;
            
        // -----------------------------------------------------------------------------------------
        // Live
        // -----------------------------------------------------------------------------------------
        case "live":
            $route->format = "json";
            
            $live = new stdClass();

            require_once "Modules/feed/feed_model.php";
            $feed = new Feed($mysqli,$redis,$settings["feed"]);

            $live->generation = number_format($feed->get_value($club_settings[$club]['generation_feed']),3)*2.0;
            $live->club = number_format($feed->get_value($club_settings[$club]['consumption_feed']),3)*2.0;
            
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp(time());
            $hour = $date->format("H");
           
            $hydro_price = 0.0;
            $import_price = 0.0;
            
            $imprt = 0.0;
            if ($live->generation<=$live->club) $imprt = $live->club - $live->generation;
            $selfuse = $live->club - $imprt;
            
            foreach ($tariffs as $tariff) {
                $start = explode(":",$tariff["start"])[0];
                $end = explode(":",$tariff["end"])[0];
                
                if ($start<$end) {
                    if ($hour>=$start && $hour<$end) {
                        $live->tariff = $tariff["name"];
                        $live->generator_price = $tariff["generator"];
                        $live->import_price = $tariff["import"];
                    }
                } else {
                    if ($hour>=$start || $hour<$end) {
                        $live->tariff = $tariff["name"];
                        $live->generator_price = $tariff["generator"];
                        $live->import_price = $tariff["import"];
                    }
                }
            }
            $hydro_cost = $selfuse * $live->generator_price;
            $import_cost = $imprt * $live->import_price;
            $live->unit_price = number_format(($import_cost + $hydro_cost) / $live->club,2)*1;

            return $live;
            break;
        
        case "household-daily-summary":
            $route->format = "json";
            if ($session["read"]) {
                $userid = $session["userid"];
                
                if (isset($_GET['start']) && isset($_GET['end'])) {
                    $start = $_GET['start']*0.001;
                    $end = $_GET['end']*0.001;
                    
                    require_once "Modules/feed/feed_model.php";
                    $feed = new Feed($mysqli,$redis,$settings["feed"]);

                    if (!$use_id = $feed->get_id($userid,"use_hh_est")) return "Could not find consumption feed";
                    if (!$gen_id = $feed->get_id($userid,"gen_hh")) return "Could not find generation share feed";
                        
                    require_once "/opt/emoncms/modules/cydynni/scripts/sharing_summary.php";
                    return get_daily_summary($club_settings[$club]["tariff_history"],$use_id,$gen_id,$start,$end,"index");
                }
            } else {
                return "session not valid";
            }
            break;


        case "household-summary-monthly":
            $format = "json";
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                
                $end = floor(time()/1800)*1800;
                $start = $end-3600*24*365;
                
                $d = new DateTime();
                $d->setTimezone(new DateTimeZone("Europe/London"));
                $d->setTimestamp($start);
                $d->setDate($d->format("Y"),$d->format("m"),1);
                $d->setTime(0,0,0);
                $start = $d->getTimestamp();

                require_once "Modules/feed/feed_model.php";
                $feed = new Feed($mysqli,$redis,$settings["feed"]);
                
                if (!$use_id = $feed->get_id($userid,"use_hh_est")) return "Could not find consumption feed";
                if (!$gen_id = $feed->get_id($userid,"gen_hh")) return "Could not find generation share feed";

                require_once "/opt/emoncms/modules/cydynni/scripts/sharing_summary.php";
                return get_monthly_summary($club_settings[$club]["tariff_history"],$use_id,$gen_id,$start,$end,"keys");



            } else {
                return "session not valid";
            }
            break;

        case "club-summary-monthly":
            $format = "json";
            
            $end = floor(time()/1800)*1800;
            $start = $end-3600*24*365;
            
            $d = new DateTime();
            $d->setTimezone(new DateTimeZone("Europe/London"));
            $d->setTimestamp($start);
            $d->setDate($d->format("Y"),$d->format("m"),1);
            $d->setTime(0,0,0);
            $start = $d->getTimestamp();

            $gen_id = $club_settings[$club]['generation_feed'];
            $club_id = $club_settings[$club]['consumption_feed'];

            require_once "/opt/emoncms/modules/cydynni/scripts/sharing_summary.php";
            return get_monthly_summary($club_settings[$club]["tariff_history"],$club_id,$gen_id,$start,$end,"keys");

            break;

        case "club-summary":
            $format = "json";
            
            if (!isset($_GET['start'])) return false;
            if (!isset($_GET['end'])) return false;
            $end = (int) ($_GET['end'] * 0.001);
            $start = (int) ($_GET['start'] * 0.001);

            $gen_id = $club_settings[$club]['generation_feed'];
            $club_id = $club_settings[$club]['consumption_feed'];

            require_once "/opt/emoncms/modules/cydynni/scripts/sharing_summary.php";
            return get_summary($club_settings[$club]["tariff_history"],$club_id,$gen_id,$start,$end,"keys");

            break;

        case "demandshaper":
            $format = "json";
            if ($result = $redis->get("$club:club:demandshaper")) {
                return json_decode($result);
            }
            break;

        case "demandshaper-octopus":
            $format = "json";
            if ($result = $redis->get("$club:club:demandshaper-octopus")) {
                return json_decode($result);
            }
            break;

        case "login":
            if (!$session['read']) {
            
                if ($user->get_number_of_users()>0) {
                    return $user->login(post('username'),post('password'),post('rememberme'));
                    
                } else if ($settings["cydynni"]["is_hub"]) {
                    $username = $_POST['username'];
                    $password = $_POST['password'];
                    
                    // Send request
                    $ch = curl_init();
                    curl_setopt($ch,CURLOPT_URL,"https://dashboard.energylocal.org.uk/user/auth.json");
                    curl_setopt($ch,CURLOPT_POST,1);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,"username=$username&password=".$password);
                    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                    $result = curl_exec($ch);
                    curl_close($ch);

                    $result = json_decode($result);
                    if ($result!=null && isset($result->success) && $result->success) {

                        // Fetch full account details from remote emoncms
                        $u = json_decode(file_get_contents("https://dashboard.energylocal.org.uk/user/get.json?apikey=".$result->apikey_write));

                        // Register account locally
                        $result = $user->register($username, $password, $u->email);

                        // Save remote account apikey to local hub
                        if ($result['success']==true) {
                            $userid = $result['userid'];
                            $mysqli->query("UPDATE users SET apikey_write = '".$u->apikey_write."' WHERE id='$userid'");
                            $mysqli->query("UPDATE users SET apikey_read = '".$u->apikey_read."' WHERE id='$userid'");

                            // Trigger download of user data
                            $sync_script = $settings['emoncms_dir']."/modules/club/scripts-hub/club-sync.sh";
                            $sync_logfile = "/var/log/emoncms/club-sync.log";
                            $redis->rpush("service-runner","$sync_script>$sync_logfile");

		            // Setup remote access
                            $host = "dashboard.energylocal.org.uk";
                            $config_file = $settings['emoncms_dir']."/modules/remoteaccess-client/remoteaccess.json";
                            $config = json_decode(file_get_contents($config_file));
                            if ($config!=null) {
                                $config->APIKEY_WRITE = $u->apikey_write;
                                $config->APIKEY_READ = $u->apikey_read;
                                $config->MQTT_HOST = $host;
                                $config->MQTT_USERNAME = $username;
                                $config->MQTT_PASSWORD = $u->apikey_write;
                                $fh = fopen($settings['emoncms_dir']."/modules/remoteaccess-client/remoteaccess.json","w");
                                fwrite($fh,json_encode($config, JSON_PRETTY_PRINT));
                                fclose($fh);
                            }
		            sleep(3);
                            $content = $user->login($username, $password, false);

                            return array("success"=>true);

                        } else {
                            return array("success"=>false, "message"=>"error creating account");
                        }
                    } else {
                        return array("success"=>false, "message"=>"club online account not found");
                    }
                }
            }
            break;

        case "passwordreset":
            if (!$settings["cydynni"]["is_hub"]) {    
                $format = "json";
                $user->appname = "Cydynni";
                $users = $user->get_usernames_by_email(get('email'));
                if ($users && count($users)) return $user->passwordreset($users[0]["username"],get('email'));
                else return array("success"=>false, "message"=>"User not found");
            }   
        	  break;
    	    
        // ----------------------------------------------------------------------
        // Administration functions 
        // ----------------------------------------------------------------------
        case "admin":
            $route->format = "html";
            if ($session['admin']) {
                unset($session["token"]);
                return view("Modules/club/app/admin_view.php",array('session'=>$session));
            }
            break;
            
        case "admin-users":
            $route->format = "json";
            if ($session['admin']) {
            
                $select_by_club = "";
                if (isset($_GET['club_id'])) {
                    $club_id = (int) $_GET['club_id'];
                    $select_by_club = "WHERE `clubs_id`='$club_id'";
                }
                
                $result = $mysqli->query("SELECT userid,mpan,token,welcomedate,reportdate,clubs_id FROM cydynni $select_by_club ORDER BY userid ASC");
                $users = array();
                while($row = $result->fetch_object()) {
                    $userid = $row->userid;
                    
                    $user_result = $mysqli->query("SELECT username,email,apikey_read,admin FROM users WHERE `id`='$userid'");
                    $user_row = $user_result->fetch_object();
                    if ($user_row) {
                        foreach ($user_row as $key=>$val) $row->$key = $user_row->$key;
                    }
                    
                    $row->hits = $redis->get("userhits:$userid");
                    $row->testdata = json_decode($redis->get("user:summary:lastday:$userid"));
                    
                    $result1 = $mysqli->query("SELECT count(*) FROM feeds WHERE `userid`='$userid'");
                    $row->feeds = $result1->fetch_array()[0];
                    
                    $feed_result = $mysqli->query("SELECT id FROM feeds WHERE `userid`='$userid' AND `name`='meter_power'");
                    if ($feed_row = $feed_result->fetch_object()) {
                        $row->meter_power = $feed_row->id;
                    } else { 
                        $row->meter_power = "";
                    }
                    
                    $users[] = $row;
                }
                return $users;
            }
            break;
            
        case "admin-users-csv":
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "text";
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
                    return $content;
                }
            }
            break;

        case "admin-add-user":
            $route->format = "json";
            if ($session['admin']) {
                
                $club_id = (int) post('club_id');
                $username = post('username');
                $email = post('email');
                $mpan = (int) post('mpan');
            
                // Generate new random password
                $password = hash('sha256',md5(uniqid(rand(), true)));
                $password = substr($password, 0, 10);
            
                $result = $user->register($username, $password, $email);
                if ($result["success"]) {
                    $userid = $result["userid"];
                    $mysqli->query("INSERT INTO cydynni (clubs_id,userid,mpan,token,premisestoken,welcomedate,reportdate) VALUES ('$club_id','$userid','$mpan','','',0,0)");
                }
                return $result;
            }
            break;
            
            
        case "admin-registeremail":
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "text";
                if ($session['admin']) {
                    require("Lib/email.php");
                    require("Modules/club/club_emails.php");
                    $club_emails = new ClubEmails($mysqli);
                    return $club_emails->registeremail(get('userid'));
                }
            }
            break;
            
        case "admin-change-user-email":
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "json";
                if ($session['admin']) {
                    return $user->change_email(get("userid"),get("email"));
                }
            }
            break;

        case "admin-change-user-username":
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "json";
                if ($session['admin']) {
                    return $user->change_username(get("userid"),get("username"));
                }
            }
            break;
                    
        case "admin-switchuser":
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "text";
                if ($session['admin']) {
                    $userid = (int) get("userid");
                
                    $result = $mysqli->query("SELECT username FROM users WHERE `id`='$userid'");
                    if ($row = $result->fetch_object()) {
                        $_SESSION['userid'] = $userid;
                        $_SESSION['username'] = $row->username;
                        header("Location: ../graph");
                    }
                }
            }
            break;

        case "admin-sendreport":
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "text";
                if ($session['admin']) {
                    require("Lib/email.php");
                    require("Modules/club/club_emails.php");
                    $club_emails = new ClubEmails($mysqli);
                    return $club_emails->send_report_email(get('userid'));
                }
            }
            break;
            
        case "setupguide":
            header("Location: https://github.com/energylocal/cydynni/blob/master/docs/userguide.md");
            die;
            break;

        // -----------------------------------------------------------------------------------------
        // OTA: Record local hub OTA version and log
        // -----------------------------------------------------------------------------------------
        case "ota":
            if ($session["write"]) {
                 $route->format = "html";
                 $userid = $session["userid"];
                 
                 $result = "<br>";
                 $result .= "<h3>OTA Status</h3>";

                 $r = json_decode($redis->get("cydynni:ota:version:$userid"));
                 if (isset($r->time) && isset($r->hub)) { 
                     $result .= "<p>Hub version <i>(".date("Y-m-d H:i:s",$r->time).")</i>:</p><pre>".$r->hub."</pre>";  
                 }                
                 
                 $r = json_decode($redis->get("cydynni:ota:log:$userid"));
                 if (isset($r->time) && isset($r->log)) { 
                    $result .= "<p>Log output: <i>(".date("Y-m-d H:i:s",$r->time).")</i>:</p>";
                    $result .= "<pre>".$r->log."</pre>";
                 }
            }
            break;
        
        case "ota-version":
             $ota_version = (int) $redis->get("otaversion");
             
             // Record local hub ota version
             if (isset($_GET['hub']) && $session["write"]) {
                 $userid = $session["userid"];
                 $redis->set("cydynni:ota:version:$userid",json_encode(array(
                     "time"=>time(),
                     "hub"=> (int) $_GET['hub'],
                     "master"=>$ota_version
                 )));
             }
             
             $route->format = "text";
             $result = $ota_version;
             break;

        case "ota-version-get":
            if ($session["write"]) {
                 $route->format = "json";
                 $userid = $session["userid"];
                 $result = json_decode($redis->get("cydynni:ota:version:$userid"));
            }
            break;
             
        case "ota-log-set":
            if ($session["write"]) {
                 $userid = $session["userid"];
                 $redis->set("cydynni:ota:log:$userid",json_encode(array(
                     "time"=>time(),
                     "log"=>file_get_contents('php://input')
                 )));
                 return "ok";
            }
            break;
            
        case "ota-log-get":
            if ($session["write"]) {
                 $route->format = "json";
                 $userid = $session["userid"];
                 $result = json_decode($redis->get("cydynni:ota:log:$userid"));
            }
            break;
        break;
    }
    
    return array("content"=>$result);   
}

function t($s) {
    global $translation,$lang;
    
    if (isset($translation->$lang) && isset($translation->$lang->$s)) {
        return $translation->$lang->$s;
    } else {
        return $s;
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
