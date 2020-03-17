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
    global $tariffs, $club_settings;
    global $lang;

    if (isset($_GET['lang']) && $_GET['lang']=="cy") $session['lang'] = "cy_GB";
    if (isset($_GET['iaith']) && $_GET['iaith']=="cy") $session['lang'] = "cy_GB";
    if (isset($_GET['lang']) && $_GET['lang']=="en") $session['lang'] = "en_GB";
    if (isset($_GET['iaith']) && $_GET['iaith']=="en") $session['lang'] = "en_GB";
    $lang = $session["lang"];
    
    $result = false;
    
    $route->format = "json";
    $result = false;
    require "Modules/club/club_model.php";
    $club_model = new Club($mysqli,$redis);

    $club = "bethesda";
    
    if (isset($available_clubs) && in_array($route->action,$available_clubs)) {
        $club = $route->action;
        $route->action = "";
    }
    
    if ($settings["cydynni"]["is_hub"]) {
	      $club_settings = array(
  "bethesda"=>array(
      "name"=>"Bethesda",
      "generator"=>"hydro",
      "generator_color"=>"#29aae3",
      "export_color"=>"#a5e7ff",
      "api_prefix"=>1,
      "languages"=>array("cy","en"),
      "generation_feed"=>1,
      "consumption_feed"=>2,
      "tariffs"=>[[
          "name" => "overnight",
          "start" => '20:00',
          "end" => '7:00',
          "generator" => 5.8,
          "import" => 10.5,
          "color" => "#014c2d"
      ],[
          "name" => "daytime",
          "start" => '7:00',
          "end" => '16:00',
          "generator" => 10.4,
          "import" => 18.9,
          "color" => "#ffb401"
      ],[
          "name" => "evening",
          "start" => '16:00',
          "end" => '20:00',
          "generator" => 12.7,
          "import" => 23.1,
          "color" => "#e6602b"
      ]]
  ));


	  }
	  
	  global $translation;
	  $translation = new stdClass();
    $translation->cy_GB = json_decode(file_get_contents("Modules/club/app/locale/cy_GB"));

    $base_url = $settings["cydynni"]["is_hub"] ? "https://dashboard.energylocal.org.uk/club/" : "http://localhost/club/";
    $emoncms_url = $settings["cydynni"]["is_hub"] ? 'http://localhost/emoncms/' : 'https://dashboard.energylocal.org.uk/';

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
                'tariffs_table' => $club_model->getTariffsTable($club_settings[$club]['tariffs']),
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
            $live->generation = number_format($feed->get_value(1),3)*2.0;
            $live->club = number_format($feed->get_value(2),3)*2.0;
            
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp(time());
            $hour = $date->format("H");

            $tariff = "";
            if ($hour<7) $tariff = "overnight";
            if ($hour>=7 && $hour<16) $tariff = "daytime";
            if ($hour>=16 && $hour<20) $tariff = "evening";
            if ($hour>=20) $tariff = "overnight";
            if ($live->generation>=$live->club) $tariff = "generation";
                
            $live->tariff = $tariff;

            $imprt = 0.0;
            if ($live->generation<=$live->club) $imprt = $live->club - $live->generation;
            $selfuse = $live->club - $imprt;
            
            $hydro_price = 0.0;
            $import_price = 0.0;
            // hydro price
            if ($hour>=20.0 || $hour<7.0) $hydro_price = 5.8;
            if ($hour>=7.0 && $hour<16.0) $hydro_price = 10.4;
            if ($hour>=16.0 && $hour<20.0) $hydro_price = 12.7;
            $hydro_cost = $selfuse * $hydro_price;
            // import price
            if ($hour>=20.0 || $hour<7.0) $import_price = 10.5;
            if ($hour>=7.0 && $hour<16.0) $import_price = 18.9;
            if ($hour>=16.0 && $hour<20.0) $import_price = 23.1;
            $import_cost = $imprt * $import_price;
            // unit price
            $live->unit_price = ($import_cost + $hydro_cost) / $live->club;

            return $live;
            break;
        
        case "household-daily-summary":
            $route->format = "json";
            if ($session["read"]) {
                $userid = $session["userid"];
                
                $data = json_decode($redis->get("household:daily:summary:$userid"));
                
                if (isset($_GET['start']) && isset($_GET['end'])) {
                    $start = $_GET['start']*0.001;
                    $end = $_GET['end']*0.001;
                    $tmp = array();
                    if ($data) {
                        for ($i=0; $i<count($data); $i++) {
                            if ($data[$i][0]>=$start && $data[$i][0]<=$end) {
                                $tmp[] = $data[$i];
                            }
                        }
                    }
                    $data = $tmp;
                }
                
                return $data;
            } else {
                return "session not valid";
            }
            break;

        case "household-summary-monthly":
            $format = "json";
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                if ($result = $redis->get("household:summary:monthly:$userid")) {
                    return json_decode($result);
                }
            } else {
                return "session not valid";
            }

/*
        case "household-summary-monthly":
            $format = "json";
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                return $cydynni->getHouseholdSummaryMonthly($userid,get("month"),$session["apikey_read"]);
            } else {
                return "session not valid";
            }
            break;
*/

            break;
            
        case "club-summary-day":
            $route->format = "json";

            if (!$result = $redis->get("$club:club:summary:day")) {
                if($settings["cydynni"]["is_hub"]) {
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
                $input_format = 'M d Y G:i:s';
                $output_format = 'U';
                $content_time = DateTime::createFromFormat($input_format, $content->date)->format($output_format);
                $content->dayoffset = ($time - $content_time)/(3600*24);
            } else {
                return "Invalid data";
            }
            
            $content->time = $content_time;
            return $content;
            break;

        case "club-summary-monthly":
            $format = "json";
            $month = get("month");

            if ($settings["cydynni"]["is_hub"]) {
                return json_decode(file_get_contents("$base_url/club-summary-monthly?month=$month"));
            }else{
                if ($result = $redis->get("$club:club:summary:monthly")) {
                    return json_decode($result);
                }
                return $result;
            }
            break;
        
        case "club-summary":
            $route->format = "json";
            
            if (!isset($_GET['start'])) return false;
            if (!isset($_GET['end'])) return false;
            $end = (int) ($_GET['end'] * 0.001);
            $start = (int) ($_GET['start'] * 0.001);
            
            require_once "Modules/feed/feed_model.php";
            $feed = new Feed($mysqli,$redis,$settings["feed"]);
            $datadir = $settings["feed"]["phpfina"]["datadir"];
            
            $gen_id = $club_settings[$club]['generation_feed'];
            $club_id = $club_settings[$club]['consumption_feed'];
            
            // 1. Load gen and club meta files - check for interval match
            if (!$gen_meta = $feed->get_meta($gen_id)) return false;
            if (!$club_meta = $feed->get_meta($club_id)) return false;
            if ($gen_meta->interval!=1800 || $gen_meta->interval!=$club_meta->interval) return false;
            if ($start<$gen_meta->start_time) $start = $gen_meta->start_time;
            if ($start<$club_meta->start_time) $start = $club_meta->start_time;
            if ($end<$start) $end = $start;
            
            // 2. Load gen file position
            $pos_gen_start = floor(($start - $gen_meta->start_time) / $gen_meta->interval);
            $pos_gen_end = floor(($end - $gen_meta->start_time) / $gen_meta->interval);
            if ($pos_gen_end>$gen_meta->npoints) $pos_gen_end = $gen_meta->npoints;
            $fh_gen = fopen($datadir.$gen_id.".dat", 'rb');
            fseek($fh_gen,$pos_gen_start*4);

            // 3. Load club file position
            $pos_club_start = floor(($start - $club_meta->start_time) / $club_meta->interval);
            $pos_club_end = floor(($end - $club_meta->start_time) / $club_meta->interval);
            if ($pos_club_end>$club_meta->npoints) $pos_club_end = $club_meta->npoints;
            $fh_club = fopen($datadir.$club_id.".dat", 'rb');
            fseek($fh_club,$pos_club_start*4);

            // 4. Prepare date and time
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $time = $club_meta->start_time + $pos_club_start * $club_meta->interval;
                 
            $out = ""; $n = 0;
            
            // Used keys here so that we can itterate through array in multiple places below
            $v = array('use'=>0,'gen'=>0,'selfuse'=>0,'import'=>0,'export'=>0);
            
            $tariffs = $club_settings[$club]['tariffs'];
            
            // Init totals array
            $kwh = array();
            foreach ($tariffs as $t) $kwh[$t['name']] = $v;
            $kwh['total'] = $v;
            
            for ($pos=$pos_gen_start; $pos<$pos_gen_end; $pos++) {
                $date->setTimestamp($time);
                $hour = $date->format("H");
                
                $tmp = unpack("f",fread($fh_gen,4));
                if (!is_nan($tmp[1])) $v['gen'] = $tmp[1];
                $tmp = unpack("f",fread($fh_club,4));
                if (!is_nan($tmp[1])) $v['use'] = $tmp[1];
                
                $v['import'] = 0;
                $v['export'] = 0;
                if ($v['gen']<=$v['use']) $v['import'] = $v['use']-$v['gen']; else $v['export'] = $v['gen']-$v['use'];
                $v['selfuse'] = $v['use'] - $v['import'];
                
                // echo "$hour $gen $use\n";
                
                foreach ($tariffs as $t) {
                    $on_tariff = false;
                    $sh = explode(":",$t['start'])[0];
                    $eh = explode(":",$t['end'])[0];
                    
                    if ($sh<$eh && ($hour>=$sh && $hour<$eh)) $on_tariff = true;
                    if ($sh>$eh && ($hour>=$sh || $hour<$eh)) $on_tariff = true;
                    
                    if ($on_tariff) {
                        foreach ($v as $key=>$val) $kwh[$t['name']][$key] += $val;
                    }
                }
                foreach ($v as $key=>$val) $kwh['total'][$key] += $val;
                    
                $time += 1800;
                if ($n>100000) break;
                $n++;
            }
            
            fclose($fh_gen);
            fclose($fh_club);
            
            // Costs
            $total_selfuse = 0;
            $total_import = 0;
            $cost = array();
            foreach ($tariffs as $t) {
                $cost[$t['name']] = array();
                
                $selfuse = $kwh[$t['name']]['selfuse'] * 0.01 * $t['generator'];
                $total_selfuse += $selfuse;
                $cost[$t['name']]['selfuse'] = 1*number_format($selfuse,2,'.','');
                
                $import = $kwh[$t['name']]['import'] * 0.01 * $t['import'];
                $total_import += $import;
                $cost[$t['name']]['import'] = 1*number_format($import,2,'.','');
            }
            $cost['total']['selfuse'] = 1*number_format($total_selfuse,2,'.','');
            $cost['total']['import'] = 1*number_format($total_import,2,'.','');
            
            foreach ($kwh as $name=>$a) {
                foreach ($v as $key=>$b) $kwh[$name][$key] = 1*number_format($kwh[$name][$key],3,'.','');
            }
            
            return array('kwh'=>$kwh,'cost'=>$cost);
            break;
                    
        case "generation-estimate":
            $route->format = "json";

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
                            //$data[$i][1] = $data[$i][1] * 0.001;
                            if ($data[$i][1]<0) $data[$i][1] = 0;
                            if ($data[$i][1]>49) $data[$i][1] = 49;
                        } else if ($club=="towerpower") {
                            $data[$i][1] = -1 * $data[$i][1] * 0.001;
                        }
                    }
            
                    // remove last half hour if null
                    if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
            
                    return $data;
                } else {
                    return $result;
                }
            } else {
                return array();
            }  
            
            break;
            
        case "club-estimate":
            $route->format = "json";
            
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
                return $consumption_profile;
            } else {
                return "session not valid";
            }
            
            break;

        case "demandshaper":
            $format = "json";
            if ($result = $redis->get("$club:club:demandshaper")) {
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
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "html";
                unset($session["token"]);
                return view("Modules/club/app/admin_view.php",array('session'=>$session));
            }
            break;
            
        case "admin-users":
            if (!$settings["cydynni"]["is_hub"]) {
                $route->format = "json";
                if ($session['admin']) {
                    // Include data from club table here too
                    $result = $mysqli->query("SELECT id,username,email,apikey_read,admin FROM users ORDER BY id ASC");
                    $users = array();
                    while($row = $result->fetch_object()) {
                        $userid = $row->id;
                        // Include fields from club table
                        $user_result = $mysqli->query("SELECT mpan,token,welcomedate,reportdate,clubs_id FROM cydynni WHERE `userid`='$userid'");
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
                        header("Location: ../?household");
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
        /*
        case "admin":
            if($session["admin"]){
                //get single user
                if ($route->subaction=='users') {
                    //get/set group users
                    //users CRUD                    
                    $route->format = "json";
                    if ($route->method=="POST") {
                        //CREATE USER
                        $returned = $user->register($_POST['username'], $_POST['password'], $_POST['email']);
                        if($returned['success']){
                            //cydynni model save
                            $returned2 = $cydynni->saveUser($_POST, $returned['userid']);
                            if($returned2['success'] && ($returned2['affected_rows']>0||!empty($returned2['user_id']>0))){
                                $result = $cydynni->getUsers($returned2['user_id']);
                            }elseif(!$returned2['success'] && $returned2['affected_rows']==0 && empty($returned2['error'])){
                                $result = array('success'=>false,'message'=>'no added rows');
                            }else{
                                $result = array('success'=>false,'message'=>$returned2['error']);
                            }
                        }else{
                            $result = array('success'=>false,'message'=>'error in creating user');
                        }
                    } elseif ($route->method=="GET") {
                        //READ USER
                        $route->format = "json";
                        if(!empty($route->subaction2)){
                            if(is_numeric($route->subaction2)){
                                //identify single user by id
                                $cydynni_users = $cydynni->getUsers($route->subaction2);
                            }else{
                                //identify single club by slug
                                $club = $cydynni->getClubBySlug($route->subaction2);
                                //identify all users by club _id
                                $cydynni_users = $cydynni->getUsersByClub($club['id']);
                            }
                        }else{
                            //get all users
                            $cydynni_users = $cydynni->getUsers();
                        }
                        // add club and emoncms user data
                        foreach ($cydynni_users as $key=>$value) {
                            $cydynni_users[$key]['club'] = $cydynni->getClubs($value['clubs_id']);
                            $cydynni_users[$key]['user'] = $user->get($value['userid']);
                        }
                        $result = $cydynni_users;
                        
                    } elseif ($route->method=="PUT") {
                        //UPDATE USER
                        $userid = put('userid');
                        if(!$userid){
                            $result = array('success'=>false,'message'=>'no userid sent');
                        }else{
                            $data = array(
                                'mpan'=>put('mpan'),
                                'token'=>put('token'),
                                'premisestoken'=>put('premisestoken'),
                                'welcomedate'=>put('welcomedate'),
                                'reportdate'=>put('reportdate'),
                                'clubs_id'=>put('clubs_id')
                            );
                            array_filter($data);
                            $returned = $cydynni->saveUser($data, $userid);
                            if ($returned['success'] && $returned['affected_rows']>0) {
                                //@todo: should i check for changed values?
                                if (put('username')!=put('username-original')) {
                                    $user->change_username($userid,put('username'));
                                }
                                if (put('email')!=put('email-original')) {
                                    $user->change_email($userid,put('email'));
                                }
                                $result = $cydynni->getUsers($userid);
                            }elseif ($returned['affected_rows']==0) {
                                $result = array('success'=>false,'message'=>'no affected rows');
                            }else{
                                $result = array('success'=>false,'message'=>$returned['error']);
                            }
                        }
                    } elseif ($route->method=="DELETE"){
                        //DELETE USER
                        $userid = delete('userid');
                        if($cydynni->deleteUser($userid)){
                            if($user->delete($userid)){
                                return array('success'=>'true', 'message'=>"User $userid Deleted");
                            }
                        }
                    }

                }elseif($route->subaction=='clubs'){
                    //clubs CRUD
                    if($route->method=="POST"){
                        //CREATE CLUB
                        $club = $cydynni->saveClub($_POST);
                        if(!empty($club['success']) && $club['success']){
                            $result = array($club['data']);
                        }else{
                            $result = array('success'=>false, 'message'=>$club['error'], 'params'=>$club['params']);
                        }
                    }elseif($route->method=="GET"){
                        //READ CLUB
                        if(empty($route->subaction2)){
                            //select all clubs
                            return $cydynni->getClubs();
                        }else{
                            //select club by id or slug
                            if(is_numeric($route->subaction2)){
                                $result = $cydynni->getClubs($route->subaction2);
                            }else{
                                $result = $cydynni->getClubBySlug($route->subaction2);
                            }
                        }
                    }elseif($route->method=="PUT"){
                        //UPDATE CLUB
                        $club_id = put('club_id');
                        if($club_id) {
                            $data = array(
                                'name'=>put('name'),
                                'generator'=>put('generator'),
                                'root_token'=>put('root_token'),
                                'api_prefix'=>put('api_prefix'),
                                'languages'=>put('languages'),
                                'generation_feed'=>put('generation_feed'),
                                'consumption_feed'=>put('consumption_feed'),
                                'color'=>put('color'),
                                'id'=>put('id'),
                                'slug'=>put('slug')
                            );
                            array_filter($data);
                            $returned = $cydynni->saveClub($data, $club_id);
                            if(!empty($returned['success']) && $returned['success']){
                                $result = $cydynni->getClubs($returned['data'][0]['club_id']);
                            }else{
                                $result = array('success'=>false,'message'=>$returned['error']);
                            }
                        }else{
                            $result = array('success'=>false,'message'=>'club id not given');
                        }
                    }elseif($route->method=="DELETE"){
                        //DELETE CLUB
                        $club_id = delete('club_id');
                        if($club_id) {
                            if($cydynni->deleteClub($club_id)){
                                return array('success'=>'true', 'message'=>"Club $club_id Deleted");                                
                            }
                        }else{
                            $result = array('success'=>false,'message'=>'club id not given');
                        }
                    }
                    $route->format = "json";
                }else{
                    //show list of clubs 
                    $route->format = "html";
                    return view("Modules/cydynni/admin_view.php", array());
                }
            }else{
                //does not have privilates or may not be logged in
                if(!$route->is_ajax){
                    $route->format = "html";
                }
                return false;
            }
        break;*/
        case 'tariffs':
            return $club_model->getTariffsTable($club_settings[$club]['tariffs']);
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
