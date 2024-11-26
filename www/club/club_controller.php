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

function chooseLanguage(string $languages) { // e.g. "cy,en"

    $languages = explode(",", $languages);

    // Language of last resort
    $lang = "en";

    // First lang code in settings used as default if available
    if (count($languages) > 0) {
      $lang = $languages[0];
    }

    // Prioritise query iaith then lang params if available
    if (isset($_GET['lang'])) {
      $lang = $_GET['lang'];
    }
    if (isset($_GET['iaith'])) {
      $lang = $_GET['iaith'];
    }

    // Convert 2-letter code to full locale
    switch($lang) {
	  case 'en':
	    return "en_GB";
      case 'cy':
        return "cy_GB";
      default:
        $log = new EmonLogger(__FILE__);
	    $log->error("Language code '".$lang."' unsupported, defaulting to en_GB");
        return "en_GB";
    }
}

function club_controller()
{
    global $mysqli, $redis, $session, $route, $user, $settings, $available_clubs;
    global $club;
    global $lang;

    $log = new EmonLogger(__FILE__);
    $log->info('club route: '.json_encode($route));

    require_once "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$settings["feed"]);

    require "Modules/club/club_model.php";
    $club_class = new Club($mysqli,$user,$feed);
    $club_settings = $club_class->get_settings($club);

	  global $translation;
	  $translation = new stdClass();

	  $session['lang'] = chooseLanguage($club_settings["languages"]);
    $lang = $session['lang']; // Why?

    $translation->cy_GB = json_decode(file_get_contents("Modules/club/app/locale/cy_GB.json"));

    $is_advisor = in_array($session['userid'], $club_class->get_advisors($club_settings['id']));

    if ($session["read"]) {
        $userid = (int) $session["userid"];

        $result = $mysqli->query("SELECT email,apikey_read FROM users WHERE `id`='$userid'");
        $row = $result->fetch_object();
        $session["email"] = $row->email;
        $session["apikey_read"] = $row->apikey_read;
    }

    // Load the main dashboard view
    // /club
    $tariffs = array();
    $standing_charge = 0;

    if ($route->action == "") {
        $route->format = "html";
        $available_reports = array();

        $tariffs_table = array();

        try {
            require_once "Modules/tariff/tariff_model.php";
            $tariff_class = new Tariff($mysqli);

            $current_tariff = $tariff_class->get_club_latest_tariff($club_settings["id"]);
            $tariffs = $tariff_class->list_periods($current_tariff->tariffid);
            $tariffs_table = $tariff_class->getTariffsTable($tariffs);
            /*$weekday_tariffs = $tariff_class->list_weekday_periods($current_tariff->tariffid);
            $weekday_tariffs_table = $tariff_class->getTariffsTable($weekday_tariffs);
            $weekend_tariffs = $tariff_class->list_weekend_periods($current_tariff->tariffid);
            $weekend_tariffs_table = $tariff_class->getTariffsTable($weekend_tariffs);*/
            $concise_tariffs_table = $tariff_class->get_concise_tariffs_table($current_tariff->tariffid);
            // Check if $weekend_tariffs_table has entries
            /*if (count($weekend_tariffs_table) > 0) {
                foreach ($weekend_tariffs_table as $weekend_entry) {
                    // Find an entry in $weekday_tariffs_table with the same 'start' value
                    $matching_weekday_entry = array_filter($weekday_tariffs_table, function($weekday_entry) use ($weekend_entry) {
                        return $weekday_entry->start === $weekend_entry->start;
                    });

                    // If there's a matching weekday entry
                    if (!empty($matching_weekday_entry)) {
                        // Take the first matching entry (assuming 'start' is unique)
                        $matching_weekday_entry = reset($matching_weekday_entry);

                        // Check if 'import' values are different
                        if ($matching_weekday_entry->import !== $weekend_entry->import) {
                            $concise_tariffs_table[] = $weekend_entry;
                        }
                    }
                }
            }*/
            $standing_charge = $tariff_class->get_tariff_standing_charge($current_tariff->tariffid);

            if ($session["read"]) {
                $userid = (int) $session["userid"];

                $tariffs_table = array();
                $standing_charge = 0;

                $tariffid = $tariff_class->get_user_tariff_id($userid);
                $tariffs = $tariff_class->list_periods($tariffid);
                $tariffs_table = $tariff_class->getTariffsTable($tariffs);
                /*$weekday_tariffs = $tariff_class->list_weekday_periods($current_tariff->tariffid);
                $weekday_tariffs_table = $tariff_class->getTariffsTable($weekday_tariffs);
                $weekend_tariffs = $tariff_class->list_weekend_periods($current_tariff->tariffid);
                $weekend_tariffs_table = $tariff_class->getTariffsTable($weekend_tariffs);*/

                $concise_tariffs_table = $tariff_class->get_concise_tariffs_table($current_tariff->tariffid);
                // Check if $weekend_tariffs_table has entries
                /*if (count($weekend_tariffs_table) > 0) {
                    foreach ($weekend_tariffs_table as $weekend_entry) {
                        // Find an entry in $weekday_tariffs_table with the same 'start' value
                        $matching_weekday_entry = array_filter($weekday_tariffs_table, function($weekday_entry) use ($weekend_entry) {
                            return $weekday_entry->start === $weekend_entry->start;
                        });

                        // If there's a matching weekday entry
                        if (!empty($matching_weekday_entry)) {
                            // Take the first matching entry (assuming 'start' is unique)
                            $matching_weekday_entry = reset($matching_weekday_entry);

                            // Check if 'import' values are different
                            if ($matching_weekday_entry->import !== $weekend_entry->import) {
                                $concise_tariffs_table[] = $weekend_entry;
                            }
                        }
                    }
                }*/
                $standing_charge = $tariff_class->get_tariff_standing_charge($tariffid);
                if (!$club_settings["has_generator"]) {
                $user_attributes = $user->get_attributes($userid);
                if (property_exists($user_attributes, "standing_charge")) {
                    $standing_charge = $user_attributes->standing_charge/100;
                }
                }


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
            'club_settings' => $club_settings,
            'tariffs_table' => $tariffs_table,
            'tariffs' => $tariffs,
            /*'weekday_tariffs_table' => $weekday_tariffs_table,
            'weekday_tariffs' => $weekday_tariffs,
            'weekend_tariffs_table' => $weekend_tariffs_table,
            'weekend_tariffs' => $weekend_tariffs,*/
            'concise_tariffs_table' => $concise_tariffs_table,
            'user_attributes' => isset($userid) ? $user->get_attributes($userid) : null,
            'available_reports'=>$available_reports,
            'clubid'=>$club_settings['id'],
            'standing_charge' => $standing_charge,
            'is_advisor' => $is_advisor
          ));

          return array('content'=>$content,'page_classes'=>array('collapsed','manual'));

        } catch (MissingUserTariffException $e) {
          $log = new EmonLogger(__FILE__);
          $log->error($e);
          return array('content'=>t('The tariff has not been correctly set for this user.'));
        } catch (MissingTariffException $e) {
          $log = new EmonLogger(__FILE__);
          $log->error($e);
          return array('content'=>t('The tariff has not been correctly set for this club.'));
        }
    }

    // Returns the report view
    // this needs to be moved to the account or data section
    // /club/report 
    if ($route->action == "report" && $session["read"]) {
        $route->format = "html";
        $userid = (int) $session["userid"];
        if (!$session["admin"]) $redis->incr("userhits:$userid");
        return view("Modules/club/app/report_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings));
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
        
        $gen_last_actual = $feed->get_timevalue($club_settings['generation_feed']);
        $use_last_actual = $feed->get_timevalue($club_settings['consumption_feed']);

        $live->source = "last_actual";
        $live->generation = number_format($gen_last_actual['value'],3)*2.0;
        $live->club = number_format($use_last_actual['value'],3)*2.0;
        
        // Use generation and consumption prediction from forecast if actual data is old
        if (($this_hh-$gen_last_actual['time'])>1800 && ($this_hh-$use_last_actual['time'])>1800) {
            if (isset($club_settings['generation_forecast_feed']) && isset($club_settings['consumption_forecast_feed'])) {
                $gen_forecast = $feed->get_value($club_settings['generation_forecast_feed'],$this_hh);
                $use_forecast = $feed->get_value($club_settings['consumption_forecast_feed'],$this_hh);
                
                if ($gen_forecast!==null && $use_forecast!==null) {
                    $live->source = "forecast";
                    $live->generation = number_format($gen_forecast,3)*2.0;
                    $live->club = number_format($use_forecast,3)*2.0;
                }
            }
        }
        
        $current_tariff = $tariff_class->get_club_latest_tariff($club_settings["id"]);
        $bands = $tariff_class->list_periods($current_tariff->tariffid);
        $concise_tariff_table = $tariff_class->get_concise_tariffs_table($current_tariff->tariffid);
        
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $hour = (int) $date->format("H");
        $day = $date->format('N');
        $weekend = 0;
        if ($day >= 6) {
            $weekend = 1;
        }
        
        $band = $tariff_class->get_tariff_band($concise_tariff_table,$hour,$weekend);
        
        
        $live->tariff = $band->name;
        $live->hour = $hour;
        $live->generator_price = $band->generator*1;
        $live->import_price = $band->import*1;
        $live->unit_price = $tariff_class->get_unit_price($live->club,$live->generation,$band);
        // call for traffic lights
        // old call
        //$live->status = $tariff_class->get_status($live->unit_price,$bands);
        // new call
        if ($result = $redis->get("$club:club:demandshaper")) {
            $live->demandshaper_data_raw = json_decode($result);
        }
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

    if ($route->action == "export-csv") {
        $authorized = $session["admin"] || $is_advisor;
        if (!$authorized) {
          $route->format = "json";
          return array("success"=>false, "message"=>"Not authorized");
        }
        $startMillis = get('start', false);
        $endMillis = get('end', false);
        $export = get('export', "demand");

        $feed_name = "";
        switch ($export) {
        case "demand":
          $feed_name = "use_hh";
          break;
        case "matched":
          $feed_name = "gen_hh";
          break;
        default:
          $route->format = "json";
          return array("success"=>false, "message"=>"export param must be 'demand' or 'matched'");
        }

        $data_by_mpan = $club_class->get_club_data_by_mpan($club_settings['id'], $feed_name, $startMillis, $endMillis);
        $durationMillis = $endMillis - $startMillis;
        $numberOfPeriods = $durationMillis / (30 * 60 * 1000); // Calculate the number of 30-minute periods
        $header = ",date,settlement_period";
        foreach($data_by_mpan as $mpan => $feed_data) {
          $header = $header.",".$mpan;
        }
        $doc = $header."\n";

        $hh_period = 1;
        for ($i = 0; $i <= $numberOfPeriods; $i++) {
          $date = DateTime::createFromFormat('U', ($startMillis/1000) + ($i * 1800));
          $formattedDate = $date->format('Y-m-d');
          $line = "$i,$formattedDate,$hh_period";
          foreach($data_by_mpan as $mpan => $feed_data) {
            $line = $line.",".number_format($feed_data[$i][1], 3);
          }
          $doc = $doc.$line."\n";
          $hh_period++;
          if ($hh_period > 48) {
            $hh_period = 1;
          }
        }

        $route->format = "text";
        return $doc;
    }

    if ($route->action == "set_fixed_user_tariff" && $session["write"]) { // used for clubless users who set their own rates
        $body = put_json();
        $user->set_attribute($session['userid'], 'tariff_type', $body['tariff_type']);
        $user->set_attribute($session['userid'], 'tariff', $body['tariff']);
        $user->set_attribute($session['userid'], 'economy7_tariff', $body['economy7_tariff']);
        $user->set_attribute($session['userid'], 'standing_charge', $body['standing_charge']);
        require_once "Modules/tariff/tariff_model.php";
        $tariff_class = new Tariff($mysqli);
        switch ($body['tariff_type']) {
          case 'fixed':
            $tariff_class->set_temporary_fixed_tariff($session['userid'], $body['tariff']);
            return;
          case 'economy7':
            $tariff_class->set_temporary_economy7_tariff($session['userid'], $body['tariff'], $body['economy7_tariff']);
            return;
          default:
            throw new Exception("tariff_type unrecognised");
        }
//        return $result;
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
            
            // FIXME: clubs need to come from DB
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

    // new password reset routes - START
    if ($route->action == "passwordreset_generation") {
        try {
        $base_url = "https://" . $_SERVER["SERVER_NAME"] . "/?household";
        $route->format = "json";
        $user->appname = "Cydynni";
        $data = $_POST['email'];
        // checking if username or email has been supplied
        // if email:
        if (strpos($data, "@")) {
            $email = $data;
            $users = $user->get_usernames_by_email($email);
	    // A single email can be associated with multiple accounts (usernames cannot).
	    // If such an email is used to login, the first username is used.
       	// Likewise, take the first when resetting the password.
	    $username = $users[0]["username"];	
        } else {
            $username = $data;
            $email = $user->get_email_by_username($username);
        }
        return $user->passwordreset_generation($username, $email, $base_url);
    } catch(InvalidUserException $e) {
        return array('success'=>false, 'message'=>"Invalid username or email.", 'reset_disabled'=>false, 'invalid_user_email'=>true);
    }
    }

    if ($route->action == "passwordreset_check_token") {
        $route->format = "json";
        $user->appname = "Cydynni";
        $token = get('token');
        if ($token) {
            return $user->passwordreset_check_token($token);
        }
    }

    if ($route->action == "passwordreset_reset") {
        $route->format = "json";
        $user->appname = "Cydynni";
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];
        if ($token) {
            return $user->passwordreset_reset($token, $new_password);
        }
    }
    // new password reset routes - END
    
    // ----------------------------------------------------------------------
    // Administration functions 
    // ----------------------------------------------------------------------
    if ($route->action == "admin-users-data-status" && $session['admin']) {
        $route->format = "json";
    
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
        
            $club_settings = 
        
            $gen_last_actual = $feed->get_timevalue($club_settings['generation_feed']);
            $use_last_actual = $feed->get_timevalue($club_settings['consumption_feed']);

            $generation = number_format($gen_last_actual['value'],3)*2.0;
            $consumption = number_format($use_last_actual['value'],3)*2.0;
            
            // Use generation and consumption prediction from forecast if actual data is old
            if (($this_hh-$gen_last_actual['time'])>1800 && ($this_hh-$use_last_actual['time'])>1800) {
                if (isset($club_settings['generation_forecast_feed']) && isset($club_settings['consumption_forecast_feed'])) {
                    $gen_forecast = $feed->get_value($club_settings['generation_forecast_feed'],$this_hh);
                    $use_forecast = $feed->get_value($club_settings['consumption_forecast_feed'],$this_hh);
                    
                    if ($gen_forecast!=null && $use_forecast!=null) {
                        $generation = number_format($gen_forecast,3)*2.0;
                        $consumption = number_format($use_forecast,3)*2.0;
                    }
                }
            }
        
            $club_list[$club_name] = array(
                "name"=>$club_settings["name"],
                "generator"=>$club_settings["generator"],
                "generation"=>$generation,
                "consumption"=>$consumption
            );
        }
        return $club_list;
        
        break;
        
    break;   
    */



    // API
    // List all clubs, Public
    // /club/list.json (returns json list of clubs)
    // /club/list (returns html list of clubs)
    if ($route->action == 'list') {
        if ($route->format == "json") {
            return $club_class->list();
        } else if ($session['admin']) {
            return view("Modules/club/club_admin_view.php", array());
        }
    }

    // Create a new club, admin only
    // /club/create.json (returns json success and clubid or fail)
    if ($route->action == 'create' && $session['admin']) {
        $route->format = "json";
        $name = get('name', true);
        return $club_class->create($name);
    }

    // Delete club, admin only
    // /club/delete.json?id=1 (returns json success or fail)
    if ($route->action == 'delete' && $session['admin']) {
        $route->format = "json";
        $id = get('id', true);
        return $club_class->delete($id);
    }
    
    return false;  
}

function t($s) {
    global $translation,$lang;
    /** @var translation $translation */
    if (isset($translation->$lang) && isset($translation->$lang->$s)) {
        return $translation->$lang->$s;
    } else {
        return $s;
    }
}

function translate($s,$lang) {
    global $translation;
    /** @var translation $translation */
    if (isset($translation->$lang) && isset($translation->$lang->$s)) {
        return $translation->$lang->$s;
    } else { 
        return $s;
    }
}
