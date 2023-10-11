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

    //require "Modules/club/tariff_model.php";
    //$tariff_model = new Tariff($mysqli,$redis, $club_settings);
    
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
    
    switch ($route->action)
    {
        case "":
            $available_reports = array();
            
            require_once "Modules/tariff/tariff_model.php";
            $tariff_class = new Tariff($mysqli);
        
            $tariffid = $tariff_class->get_user_tariff(2);
            $tariffs = $tariff_class->list_periods($tariffid);
            $tariffs_table = $tariff_class->getTariffsTable($tariffs);
        
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                
                $tariffid = $tariff_class->get_user_tariff($userid);
                $tariffs = $tariff_class->list_periods($tariffid);
                $tariffs_table = $tariff_class->getTariffsTable($tariffs);
                
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

            $route->format = "html";

            $content = view("Modules/club/app/client_view.php", array(
                'session' => $session,'club' => $club,
                'club_settings' => $club_settings[$club],
                'tariffs_table' => $tariffs_table,
                'tariffs' => $tariffs,
                'available_reports'=>$available_reports,
                'clubid'=>$club_settings[$club]['club_id']
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

        case "configure-device":
            if ($session["write"]) {
                $userid = (int) $session["userid"];
                $route->format = "html";
                return view("Modules/club/app/configure.php",array('session'=>$session));
            }
            break;
            
        // -----------------------------------------------------------------------------------------
        // Live
        // -----------------------------------------------------------------------------------------
        case "live":
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

            $period = $tariff_class->get_club_tariff_period($club);
            $live->tariff = $period["name"];
            $live->generator_price = $period["generator"];
            $live->import_price = $period["import"];
            $live->unit_price = $tariff_class->get_unit_price($live->club,$live->generation);
            $live->status = $tariff_class->get_status($live->unit_price);

            return $live;
            break;
        
        case "household-daily-summary":
            $route->format = "json";
            if ($session["read"]) {
                $userid = $session["userid"];
                $format = "index";
                if (isset($_GET['format']) && $_GET['format']=="keys") {
                    $format = "keys";
                }
                
                if (isset($_GET['start']) && isset($_GET['end'])) {
                    $start = $_GET['start']*0.001;
                    $end = $_GET['end']*0.001;
                    
                    require_once "Modules/feed/feed_model.php";
                    $feed = new Feed($mysqli,$redis,$settings["feed"]);

                    if (!$use_id = $feed->get_id($userid,"use_hh_est")) return "Could not find consumption feed";
                    if (!$gen_id = $feed->get_id($userid,"gen_hh")) return "Could not find generation share feed";
                        
                    require_once "/opt/emoncms/modules/cydynni/scripts/sharing_summary.php";
                    return get_daily_summary($club_settings[$club]["tariff_history"],$use_id,$gen_id,$start,$end,$format);
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

        case "household-summary":
            $format = "json";
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                
                if (!isset($_GET['start'])) return false;
                if (!isset($_GET['end'])) return false;
                $end = (int) ($_GET['end'] * 0.001);
                $start = (int) ($_GET['start'] * 0.001);
                
                require_once "Modules/feed/feed_model.php";
                $feed = new Feed($mysqli,$redis,$settings["feed"]);
                
                if (!$use_id = $feed->get_id($userid,"use_hh_est")) return "Could not find consumption feed";
                if (!$gen_id = $feed->get_id($userid,"gen_hh")) return "Could not find generation share feed";

                require_once "/opt/emoncms/modules/cydynni/scripts/sharing_summary.php";
                return get_summary($club_settings[$club]["tariff_history"],$use_id,$gen_id,$start,$end,"keys");
                
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

        // Demandshaper v2: renamed to forecast
        // multiple forecasts per club e.g with and without solar
        // format matches latest format used by demandshaper module
        case "forecast":
            $format = "json";
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
            break;

        case "login":
            if (!$session['read']) {
                if ($user->get_number_of_users()>0) {
                    return $user->login(post('username'),post('password'),post('rememberme')); 
                }
            }
            break;

        case "passwordreset":
            $format = "json";
            $user->appname = "Cydynni";
            $users = $user->get_usernames_by_email(get('email'));
            if ($users && count($users)) return $user->passwordreset($users[0]["username"],get('email'));
            else return array("success"=>false, "message"=>"User not found");
        	  break;
    	    
        // ----------------------------------------------------------------------
        // Administration functions 
        // ----------------------------------------------------------------------
                    
        case "admin-users-data-status":
            $route->format = "json";
            if ($session['admin']) {

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
            break;
            
        case "admin-registeremail":
            $route->format = "text";
            if ($session['admin']) {
                require("Lib/email.php");
                require("Modules/club/club_emails.php");
                $club_emails = new ClubEmails($mysqli);
                return $club_emails->registeremail(get('userid'));
            }
            break;  
                    
        case "admin-switchuser":
            $route->format = "text";
            if ($session['admin']) {
                $userid = (int) get("userid");
            
                $result = $mysqli->query("SELECT username FROM users WHERE `id`='$userid'");
                if ($row = $result->fetch_object()) {
                    $_SESSION['userid'] = $userid;
                    $_SESSION['username'] = $row->username;
                    header("Location: ../feed/view");
                }
            }
            break;

        case "admin-sendreport":
            $route->format = "text";
            if ($session['admin']) {
                require("Lib/email.php");
                require("Modules/club/club_emails.php");
                $club_emails = new ClubEmails($mysqli);
                return $club_emails->send_report_email(get('userid'));
            }
            break;
            
        case "admin-link":
            $route->format = "json";
            if ($session['admin'] && isset($_GET['userid'])) {
                $userid = (int) $_GET['userid'];
                include "Modules/remoteaccess/remoteaccess_userlink.php";
                $result = remoteaccess_userlink_existing($mysqli,$userid);
                if (!isset($result["success"]) || !$result["success"]) return $result;
            }
            break;
            
        case "setupguide":
            header("Location: https://github.com/energylocal/cydynni/blob/master/docs/userguide.md");
            die;
            break;
            
        case "set-passiv-plan":
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
            break;
            
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
