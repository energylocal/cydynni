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

function cydynni_controller()
{
    global $mysqli, $redis, $session, $route, $homedir, $user, $feed_settings;
    $result = false;
    
    $route->format = "json";
    $result = false;
    require "Modules/cydynni/cydynni_model.php";

    $cydynni = new Cydynni($mysqli,$redis);

	  $club = "bethesda";
	  $club_settings = array();
	  $club_settings[$club] = array(
	      "name"=>"Bethesda",
	      "generator"=>"hydro",
	      "languages"=>array("cy","en"),
	      "generation_feed"=>1,
	      "consumption_feed"=>2
	  );
	  
	  global $translation;
	  $translation = new stdClass();
    $translation->cy = json_decode(file_get_contents("Modules/cydynni/app/locale/cy"));

    $base_url = IS_HUB ? "http://cydynni.org.uk/bethesda/" : "http://localhost/cydynni/";
    $emoncms_url = IS_HUB ? 'http://localhost/emoncms/' : 'https://emoncms.cydynni.org.uk/';
    // -----------------------------------------------------------------------------------------
    $ota_version = (int) $redis->get("otaversion");
    // -----------------------------------------------------------------------------------------
    
    switch ($route->action)
    {
        case "":
            if ($session["read"]) {
                $userid = (int) $session["userid"];
                
                require_once "Modules/feed/feed_model.php";
                $feed = new Feed($mysqli,$redis,$feed_settings);
                
                $tmp = $feed->get_user_feeds($userid);
                
                $session["feeds"] = array();
                foreach ($tmp as $f) {
                    $session["feeds"][$f["name"]] = (int) $f["id"];
                }
                
                $result = $mysqli->query("SELECT email,apikey_read,apikey_write FROM users WHERE `id`='$userid'");
                $row = $result->fetch_object();
                $session["email"] = $row->email;
                $session["apikey_read"] = $row->apikey_read;
                $session["apikey_write"] = $row->apikey_write;
            }
        
            $route->format = "html";
            return view("Modules/cydynni/app/client_view.php",array('session'=>$session,'club'=>$club,'club_settings'=>$club_settings[$club]));
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
                 $result .= "<p>Hub version <i>(".date("Y-m-d H:i:s",$r->time).")</i>:</p><pre>".$r->hub."</pre>";                 
                 
                 $r = json_decode($redis->get("cydynni:ota:log:$userid"));
                 $result .= "<p>Log output: <i>(".date("Y-m-d H:i:s",$r->time).")</i>:</p>";
                 $result .= "<pre>".$r->log."</pre>";
            }
            break;
        
        case "ota-version":
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
            }
            break;
            
        case "ota-log-get":
            if ($session["write"]) {
                 $route->format = "json";
                 $userid = $session["userid"];
                 $result = json_decode($redis->get("cydynni:ota:log:$userid"));
            }
            break;

        // -----------------------------------------------------------------------------------------
        // Live
        // -----------------------------------------------------------------------------------------
        case "live":
            $route->format = "json";
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
                
                return $live;
            } else {
                return array('success'=>false,'message'=>'Feed not available');
            }
            break;

        case "household-summary-day":
            $route->format = "json";
            if ($session["read"]) {
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
                    return "Invalid data";
                }
            } else {
                return "session not valid";
            }

            return json_decode(json_encode($content));
            break;

        case "club-summary-day":
            $route->format = "json";

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
                return "Invalid data";
            }
            
            return $content;
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
        break;
    }
    
    return array("content"=>$result);   
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
