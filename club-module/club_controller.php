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
    
    if ($club=="repower" || $club=="bridport" || $club=="roupellpark") {
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
            $available_reports = array();
        
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                
                require_once "Modules/feed/feed_model.php";
                $feed = new Feed($mysqli,$redis,$settings["feed"]);
                $available_reports = $club_model->get_available_reports($feed,$feed->get_id($userid,"use_hh_est"));
                
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
                'tariffs_table' => $club_model->getTariffsTable($tariffs),
                'tariffs' => $tariffs,
                'available_reports'=>$available_reports
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
            
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp($this_hh);
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
            if ($live->club>0) {
                $live->unit_price = number_format(($import_cost + $hydro_cost) / $live->club,2)*1;
            } else {
                $live->unit_price = 0;
            }

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
                
                $result = $mysqli->query("SELECT userid,mpan,cad_serial,meter_serial,octopus_apikey,welcomedate,reportdate,clubs_id FROM cydynni $select_by_club ORDER BY userid ASC");
                $users = array();
                while($row = $result->fetch_object()) {
                    $userid = $row->userid;
                    
                    $user_result = $mysqli->query("SELECT username,email,apikey_read,admin FROM users WHERE `id`='$userid'");
                    $user_row = $user_result->fetch_object();
                    if ($user_row) {
                        foreach ($user_row as $key=>$val) $row->$key = $user_row->$key;
                    }
                    
                    $row->hits = $redis->get("userhits:$userid");
                    // $row->testdata = json_decode($redis->get("user:summary:lastday:$userid"));
                    
                    $result1 = $mysqli->query("SELECT count(*) FROM feeds WHERE `userid`='$userid'");
                    $row->feeds = $result1->fetch_array()[0];
                    
                    $feed_result = $mysqli->query("SELECT id,tag,name FROM feeds WHERE `userid`='$userid' AND `name`='use_hh'");
                    if ($feed_row = $feed_result->fetch_object()) {
                        $row->use_hh_est = $feed_row->id;
                        $row->last_updated = $redis->hget("feed:".$feed_row->id,'time');
                    } else { 
                        $row->use_hh_est = "";
                        $row->last_updated = 0;
                    }
                    
                    $users[] = $row;
                }
                return $users;
            }
            break;

        case "admin-users-list":
            $route->format = "json";
            if ($session['admin']) {
            
                $select_by_club = "";
                if (isset($_GET['club_id'])) {
                    $club_id = (int) $_GET['club_id'];
                    $select_by_club = "WHERE `clubs_id`='$club_id'";
                }
                
                $result = $mysqli->query("SELECT userid,mpan,cad_serial,meter_serial,octopus_apikey,welcomedate,reportdate,clubs_id FROM cydynni $select_by_club ORDER BY userid ASC");
                $users = array();
                while($row = $result->fetch_object()) {
                    $userid = $row->userid;
                    
                    $user_result = $mysqli->query("SELECT username,email,apikey_read,admin FROM users WHERE `id`='$userid'");
                    $user_row = $user_result->fetch_object();
                    if ($user_row) {
                        foreach ($user_row as $key=>$val) $row->$key = $user_row->$key;
                    }
                    
                    $users[] = $row;
                }
                return $users;
            }
            break;
            
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
            
        case "admin-users-csv":
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
            break;

        case "admin-add-user":
            $route->format = "json";
            if ($session['admin']) {
                $u = json_decode(post('user'));
                
                if (!isset($u->username)) return array("success"=>false, "message"=>"missing username");  
                if (!isset($u->email)) return array("success"=>false, "message"=>"missing email");  
                if (!isset($u->clubs_id)) return array("success"=>false, "message"=>"missing clubs_id");  
                if (!isset($u->mpan)) return array("success"=>false, "message"=>"missing mpan");
                if (!isset($u->cad_serial)) return array("success"=>false, "message"=>"missing cad_serial");
                if (!isset($u->octopus_apikey)) return array("success"=>false, "message"=>"missing octopus_apikey");
                if (!isset($u->meter_serial)) return array("success"=>false, "message"=>"meter_serial email");

                if (!ctype_digit($u->mpan) && $u->mpan!="") return array("success"=>false, "message"=>"invalid mpan");  
                if (!ctype_alnum($u->cad_serial) && $u->cad_serial!="") return array("success"=>false, "message"=>"invalid cad_serial");  
                if (!preg_match('/^\w+$/',$u->octopus_apikey) && $u->octopus_apikey!="") return array("success"=>false, "message"=>"invalid octopus_apikey");  
                if (!ctype_alnum($u->meter_serial) && $u->meter_serial!="") return array("success"=>false, "message"=>"invalid meter_serial");
                
                // Generate new random password if not set
                if (!isset($u->password)) $u->password = "";
                if ($u->password==null || $u->password=="") {
                    $u->password = hash('sha256',md5(uniqid(rand(), true)));
                    $u->password = substr($u->password, 0, 10);
                }
                
                $result = $user->register($u->username, $u->password, $u->email, "Europe/London");
                if ($result["success"]) {
                    $userid = $result["userid"];
                    $result = $club_model->add_user((int)$u->clubs_id,$userid,(int)$u->mpan,$u->cad_serial,$u->octopus_apikey,$u->meter_serial);
                    include "Modules/remoteaccess/remoteaccess_userlink.php";
                    remoteaccess_userlink_existing($mysqli,$userid);
                }
                return $result;
                
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

        case "admin-update-user":
            $route->format = "json";
            if ($session['admin'] && isset($_GET['userid']) && isset($_POST['data'])) {
            
                $userid = get('userid');
                $changed = json_decode(post("data"));

                if (isset($changed->username)) {
                    $result = $user->change_username($userid,$changed->username);
                    if (!$result['success']) return $result;
                }

                if (isset($changed->email)) {
                    $result = $user->change_email($userid,$changed->email);
                    if (!$result['success']) return $result;
                }

                if (isset($changed->mpan)) {
                    $changed->mpan = trim($changed->mpan);
                    if (!ctype_digit($changed->mpan)) return array("success"=>false, "message"=>"invalid mpan");  
                    $result = $club_model->change_user_prop($userid,"mpan",$changed->mpan);
                    if (!$result['success']) return $result;
                }

                if (isset($changed->cad_serial)) {
                    $changed->cad_serial = trim($changed->cad_serial);
                    if (!ctype_alnum($changed->cad_serial)) return array("success"=>false, "message"=>"invalid cad_serial");  
                    $result = $club_model->change_user_prop($userid,"cad_serial",$changed->cad_serial);
                    if (!$result['success']) return $result;
                }

                if (isset($changed->octopus_apikey)) {
                    $changed->mpan = trim($changed->octopus_apikey);
                    if (!preg_match('/^\w+$/',$changed->octopus_apikey)) return array("success"=>false, "message"=>"invalid octopus_apikey");  
                    $result = $club_model->change_user_prop($userid,"octopus_apikey",$changed->octopus_apikey);
                    if (!$result['success']) return $result;
                }
                
                if (isset($changed->meter_serial)) {
                    $changed->mpan = trim($changed->meter_serial);
                    if (!ctype_alnum($changed->meter_serial)) return array("success"=>false, "message"=>"invalid meter_serial");  
                    $result = $club_model->change_user_prop($userid,"meter_serial",$changed->meter_serial);
                    if (!$result['success']) return $result;
                }

            }
            return array('success'=>true, 'message'=>'User updated');
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
