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
    
    if ($club=="repower" || $club=="bridport" || $club=="roupellpark") {
        $session['lang'] = "en_GB";
        $lang = $session["lang"];
    }
    
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
    
    // Load the main dashboard view
    // /club
    if ($route->action == "") {
        $route->format = "html";
        $available_reports = array();
        
        require_once "Modules/tariff/tariff_model.php";
        $tariff_class = new Tariff($mysqli);
    
        $current_tariff = $tariff_class->get_club_latest_tariff($club_settings[$club]["club_id"]);
        $tariffs = $tariff_class->list_periods($current_tariff->tariffid);
        $tariffs_table = $tariff_class->getTariffsTable($tariffs);
        $standing_charge = $tariff_class->get_tariff_standing_charge($current_tariff->tariffid);
    
        if ($session["read"]) {
            $userid = (int) $session["userid"];
            
            $tariffid = $tariff_class->get_user_tariff($userid);
            $tariffs = $tariff_class->list_periods($tariffid);
            $tariffs_table = $tariff_class->getTariffsTable($tariffs);
            
            $standing_charge = $tariff_class->get_tariff_standing_charge($tariffid);
            
            require_once "Modules/feed/feed_model.php";
            $feed = new Feed($mysqli,$redis,$settings["feed"]);

            require "Modules/data/account_data_model.php";
            $account_data = new AccountData($feed, false, $tariff_class);
            
            $available_reports = $account_data->get_available_reports($userid);
            
            $tmp = $feed->get_user_feeds($userid);
            
            $session["feeds"] = array();
            foreach ($tmp as $f) {
                $session["feeds"][$f["name"]] = (int) $f["id"];
            }
            if (!$session["admin"]) $redis->incr("userhits:$userid");
        }

        $content = view("Modules/club/app/client_view.php", array(
            'session' => $session,
            'club' => $club,
            'club_settings' => $club_settings[$club],
            'tariffs_table' => $tariffs_table,
            'tariffs' => $tariffs,
            'available_reports'=>$available_reports,
            'clubid'=>$club_settings[$club]['club_id'],
            'standing_charge' => $standing_charge
        ));

        return array('content'=>$content,'page_classes'=>array('collapsed','manual'));
    }

    // Returns the report view
    // this needs to be moved to the account or data section
    // /club/report 
    if ($route->action == "report" && $session["read"]) {
        $route->format = "html";
        $userid = (int) $session["userid"];
        if (!$session["admin"]) $redis->incr("userhits:$userid");
        return view("Modules/club/app/report_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
    }

    // Configure device (review, is this still needed?)
    if ($route->action == "configure-device" && $session["write"]) {
        $route->format = "html";
        $userid = (int) $session["userid"];
        return view("Modules/club/app/configure.php",array('session'=>$session));
    }
    
    // Live
    // /club/live.json
    if ($route->action == "live") {
        $route->format = "json";
        $this_hh = floor(time()/1800)*1800;
            
        $live = new stdClass();

        require_once "Modules/tariff/tariff_model.php";
        $tariff_class = new Tariff($mysqli);
        
        require_once "Modules/feed/feed_model.php";
        $feed = new Feed($mysqli,$redis,$settings["feed"]);
        
        $gen_last_actual = $feed->get_timevalue($club_settings[$club]['generation_feed']);
        $use_last_actual = $feed->get_timevalue($club_settings[$club]['consumption_feed']);

        $live->generation = number_format($gen_last_actual['value'],3)*2.0;
        $live->club = number_format($use_last_actual['value'],3)*2.0;
        
        // Use generation and consumption prediction from forecast if actual data is old
        if (($this_hh-$gen_last_actual['time'])>1800 && ($this_hh-$use_last_actual['time'])>1800) {
            if (isset($club_settings[$club]['generation_forecast_feed']) && isset($club_settings[$club]['consumption_forecast_feed'])) {
                $gen_forecast = $feed->get_value($club_settings[$club]['generation_forecast_feed'],$this_hh);
                $use_forecast = $feed->get_value($club_settings[$club]['consumption_forecast_feed'],$this_hh);
                
                if ($gen_forecast!=null && $use_forecast!=null) {
                    $live->generation = number_format($gen_forecast,3)*2.0;
                    $live->club = number_format($use_forecast,3)*2.0;
                }
            }
        }
        
        $current_tariff = $tariff_class->get_club_latest_tariff($club_settings[$club]["club_id"]);
        $bands = $tariff_class->list_periods($current_tariff->tariffid);
        
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $hour = (int) $date->format("H");
        
        $band = $tariff_class->get_tariff_band($bands,$hour);
        
        $live->tariff = $band->name;
        $live->hour = $hour;
        $live->generator_price = $band->generator*1;
        $live->import_price = $band->import*1;
        $live->unit_price = $tariff_class->get_unit_price($live->club,$live->generation,$band);
        $live->status = $tariff_class->get_status($live->unit_price,$bands);

        return $live;
    }

    // Return demandshaper data
    if ($route->action == "demandshaper") {
        $route->format = "json";
        if ($result = $redis->get("$club:club:demandshaper")) {
            return json_decode($result);
        }
    }

    // Return demandshaper data (octopus format)
    if ($route->action == "demandshaper-octopus") {
        $route->format = "json";
        if ($result = $redis->get("$club:club:demandshaper-octopus")) {
            return json_decode($result);
        }
    }

    // Demandshaper v2: renamed to forecast (review, not all clubs listed here)
    // multiple forecasts per club e.g with and without solar
    // format matches latest format used by demandshaper module
    // /club/forecast.json?name=bethesda
    if ($route->action == "forecast") {
        $route->format = "json";
        if (isset($_GET['name'])) {
            $key = $_GET['name'];
            
            $format = "standard";
            if (isset($_GET['format'])) {
                $format = $_GET['format'];
            }
            
            if (in_array($key,array("bethesda","corwen","crickhowell","bethesda_solar","repower"))) {
                if ($format=="standard") {
                    if ($result = $redis->get("energylocal:forecast:$key")) {
                        return json_decode($result);
                    }
                } else if ($format=="octopus") { 
                    if ($result = $redis->get("$club:club:demandshaper-octopus")) {
                        return json_decode($result);
                    }         
                }
            } else {
                return "forecast not found\n";
            }
        }
    }

    // Login
    // /club/login.json
    if ($route->action == "login" && !$session['read']) {
        $route->format = "json";
        if ($user->get_number_of_users()>0) {
            return $user->login(post('username'),post('password'),post('rememberme')); 
        }
    }

    // Password reset
    if ($route->action == "passwordreset") {
        $route->format = "json";
        $user->appname = "Cydynni"; 
        $users = $user->get_usernames_by_email(get('email'));
        if ($users && count($users)) return $user->passwordreset($users[0]["username"],get('email'));
        else return array("success"=>false, "message"=>"User not found");
    }
    
    // ----------------------------------------------------------------------
    // Administration functions 
    // ----------------------------------------------------------------------
    if ($route->action == "admin-users-data-status" && $session['admin']) {
        $route->format = "json";

        require_once "Modules/feed/feed_model.php";
        $feed = new Feed($mysqli,$redis,$settings["feed"]);
    
        $select_by_club = "";
        if (isset($_GET['club_id'])) {
            $club_id = (int) $_GET['club_id'];
            $select_by_club = "WHERE `clubs_id`='$club_id'";
        }
        
        $result = $mysqli->query("SELECT userid FROM cydynni $select_by_club ORDER BY userid ASC");
        $users = array();
        while($row = $result->fetch_object()) {
            $userid = $row->userid;
            
            $row = array(
                'tma'=>array('days'=>0,'updated'=>0),                    
                'CR'=>array('days'=>0,'updated'=>0),                    
                'octopus'=>array('days'=>0,'updated'=>0),
                'W'=>array('days'=>0,'updated'=>0),
                'est'=>array('days'=>0,'updated'=>0)                    
            );

            if ($feedid = $feed->get_id($userid,"use_hh_TMA")) {
                if ($meta = $feed->get_meta($feedid)) {
                    $row['tma']['days'] = $meta->npoints / 48;
                    $row['tma']['updated'] = (time() - ($meta->start_time + ($meta->npoints*$meta->interval)))/86400; 
                    $row['tma']['feedid'] = $feedid;  
                }
            }

            if ($feedid = $feed->get_id($userid,"use_hh_CR")) {
                if ($meta = $feed->get_meta($feedid)) {
                    $row['CR']['days'] = $meta->npoints / 48;
                    $row['CR']['updated'] = (time() - ($meta->start_time + ($meta->npoints*$meta->interval)))/86400;   
                    $row['CR']['feedid'] = $feedid;  
                }
            }
            
            if ($feedid = $feed->get_id($userid,"use_hh_octopus")) {
                if ($meta = $feed->get_meta($feedid)) {
                    $row['octopus']['days'] = $meta->npoints / 48;
                    $row['octopus']['updated'] = (time() - ($meta->start_time + ($meta->npoints*$meta->interval)))/86400;   
                    $row['octopus']['feedid'] = $feedid;
                }
            }

            if ($feedid = $feed->get_id($userid,"use_hh_W")) {
                if ($meta = $feed->get_meta($feedid)) {
                    $row['W']['days'] = $meta->npoints / 48;
                    $row['W']['updated'] = (time() - ($meta->start_time + ($meta->npoints*$meta->interval)))/86400;   
                    $row['W']['feedid'] = $feedid;
                }  
            }

            if ($feedid = $feed->get_id($userid,"use_hh_est")) {
                if ($meta = $feed->get_meta($feedid)){
                    $row['est']['days'] = $meta->npoints / 48;
                    $row['est']['updated'] = (time() - ($meta->start_time + ($meta->npoints*$meta->interval)))/86400;   
                    $row['est']['feedid'] = $feedid;  
                }
            }                    
            $users[] = $row;
        }
        return $users;
    }
    
    // Admin register email
    if ($route->action == "admin-registeremail" && $session['admin']) {
        $route->format = "text";
        require("Lib/email.php");
        require("Modules/club/club_emails.php");
        $club_emails = new ClubEmails($mysqli);
        return $club_emails->registeremail(get('userid'));
    }

    // Admin switch user
    if ($route->action == "admin-switchuser" && $session['admin']) {
        $route->format = "text";
        $userid = (int) get("userid");
    
        $result = $mysqli->query("SELECT username FROM users WHERE `id`='$userid'");
        if ($row = $result->fetch_object()) {
            $_SESSION['userid'] = $userid;
            $_SESSION['username'] = $row->username;
            header("Location: ../feed/view");
        }
    }

    // Admin send report
    if ($route->action == "admin-sendreport" && $session['admin']) {
        $route->format = "text";
        require("Lib/email.php");
        require("Modules/club/club_emails.php");
        $club_emails = new ClubEmails($mysqli);
        return $club_emails->send_report_email(get('userid'));
    }

    // Admin link
    if ($route->action == "admin-link" && $session['admin']) {
        $route->format = "json";
        if (isset($_GET['userid'])) {
            $userid = (int) $_GET['userid'];
            include "Modules/remoteaccess/remoteaccess_userlink.php";
            $result = remoteaccess_userlink_existing($mysqli,$userid);
            if (!isset($result["success"]) || !$result["success"]) return $result;
        }
    }
            
    // Set passiv plan
    if ($route->action == "set-passiv-plan") {
        $route->format = "text";
        if (!isset($_POST['authkey'])) return "missing authkey parameter";
        if ($_POST['authkey']!=$settings["passivkey"]) return "invalid auth key";
        if (!isset($_POST['mpan'])) return "missing mpan parameter";
        if (!isset($_POST['plan'])) return "missing plan parameter";
        
        $fh = fopen("/home/cydynni/set-passiv-plan.log","a");
        fwrite($fh,time()." ".$_POST['mpan']." ".$_POST['plan']."\n");
        fclose($fh);
        
        // -----------------------------------------------------------
        $plan = [];
        $json = json_decode($_POST['plan']);
        if (isset($json->source) && $json->source=="/hubs/25354") {
            foreach ($json->payload as $payload) {
                if ($payload->energyType=="electricity" && $payload->assetType=="heatpump") {
                    foreach($payload->planEvents as $slot) {
                        $date = new DateTime($slot->startDateTime);
                        $plan[$date->getTimestamp()] = $slot->value;
                    }
                }
            }
            $redis->set("passivplan:85",json_encode($plan));
        }
        // -----------------------------------------------------------
        return "plan received";
    }
    
    /*
    case "list":
        $route->format = "json";
        
        require_once "Modules/feed/feed_model.php";
        $feed = new Feed($mysqli,$redis,$settings["feed"]);
            
        global $available_clubs_menu;
        $club_list = array();
        $this_hh = floor(time()/1800)*1800;
        
        foreach ($available_clubs_menu as $i=>$club_name) {
        
            $gen_last_actual = $feed->get_timevalue($club_settings[$club_name]['generation_feed']);
            $use_last_actual = $feed->get_timevalue($club_settings[$club_name]['consumption_feed']);

            $generation = number_format($gen_last_actual['value'],3)*2.0;
            $consumption = number_format($use_last_actual['value'],3)*2.0;
            
            // Use generation and consumption prediction from forecast if actual data is old
            if (($this_hh-$gen_last_actual['time'])>1800 && ($this_hh-$use_last_actual['time'])>1800) {
                if (isset($club_settings[$club]['generation_forecast_feed']) && isset($club_settings[$club_name]['consumption_forecast_feed'])) {
                    $gen_forecast = $feed->get_value($club_settings[$club_name]['generation_forecast_feed'],$this_hh);
                    $use_forecast = $feed->get_value($club_settings[$club_name]['consumption_forecast_feed'],$this_hh);
                    
                    if ($gen_forecast!=null && $use_forecast!=null) {
                        $generation = number_format($gen_forecast,3)*2.0;
                        $consumption = number_format($use_forecast,3)*2.0;
                    }
                }
            }
        
            $club_list[$club_name] = array(
                "name"=>$club_settings[$club_name]["name"],
                "generator"=>$club_settings[$club_name]["generator"],
                "generation"=>$generation,
                "consumption"=>$consumption
            );
        }
        return $club_list;
        
        break;
        
    break;   
    */

    require "Modules/club/club_model.php";
    $club = new Club($mysqli,$user);

    // API
    // List all clubs, Public
    // /club/list.json (returns json list of clubs)
    // /club/list (returns html list of clubs)
    if ($route->action == 'list') {
        if ($route->format == "json") {
            return $club->list();
        } else if ($session['admin']) {
            return view("Modules/club/club_admin_view.php", array());
        }
    }

    // Create a new club, admin only
    // /club/create.json (returns json success and clubid or fail)
    if ($route->action == 'create' && $session['admin']) {
        $route->format = "json";
        $name = get('name', true);
        return $club->create($name);
    }

    // Delete club, admin only
    // /club/delete.json?id=1 (returns json success or fail)
    if ($route->action == 'delete' && $session['admin']) {
        $route->format = "json";
        $id = get('id', true);
        return $club->delete($id);
    }
    
    return false;  
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
