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
    global $mysqli, $redis, $session, $route, $homedir, $user;
    $result = false;
    
    $route->format = "json";
    $result = false;
    require "Modules/cydynni/cydynni_model.php";

    $cydynni = new Cydynni($mysqli,$redis);

    // -----------------------------------------------------------------------------------------
    $ota_version = (int) $redis->get("otaversion");
    // -----------------------------------------------------------------------------------------
    
    switch ($route->action)
    {
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
            
            if ($redis->exists("live")) {
                $live = json_decode($redis->get("live"));
                
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
                if ($live->hydro>=$live->community) $tariff = "hydro";
                
                $live->tariff = $tariff;
                $result = $live;
            } else {
                $result = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/cydynni/live"));
            }
            break;
            
        case "hydro-estimate":
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
            
            $data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$estimatestart&end=$end&interval=$interval&skipmissing=0&limitinterval=1"));
            
            $scale = 1.1;
            
            //$data = json_decode(file_get_contents("https://emoncms.org/feed/average.json?id=166913&start=$start&end=$end&interval=1800&skipmissing=0&limitinterval=1"));
            
            // Scale ynni padarn peris data and impose min/max limits
            for ($i=0; $i<count($data); $i++) {
                if ($data[$i][1]==null) $data[$i][1] = 0;
                $data[$i][1] = ((($data[$i][1] * 0.001)-4.5) * $scale);
                if ($data[$i][1]<0) $data[$i][1] = 0;
                if ($data[$i][1]>49) $data[$i][1] = 49;
            }
            
            // remove last half hour if null
            if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
            // if ($data[count($data)-1][1]==null) unset($data[count($data)-1]);
            
            
            $result = $data;
            
            break;
            
        case "community-estimate":
            $route->format = "json";
            
            $end = (int) 1*$_GET['lasttime'];
            $interval = (int) $_GET['interval'];
            
            $start = $end - (3600*24.0*7*1000);
            
            $data = json_decode(file_get_contents("https://emoncms.cydynni.org.uk/feed/average.json?id=2&start=$start&end=$end&interval=$interval"));

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
                $result = $consumption_profile;
            } else {
                $result = false;
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
                                $result = $cydynni->getUser($returned2['user_id']);
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
                            $cydynni_users[$key]['club'] = $cydynni->getClubs($cydynni_users[$key]['clubs_id']);
                            $cydynni_users[$key]['user'] = $user->get($cydynni_users[$key]['userid']);
                        }
                        $result = $cydynni_users;
                        
                    } elseif ($route->method=="PUT") {
                        //UPDATE USER
                        $userid = put('userid');
                        if(!$userid){
                            $result = array('success'=>false,'message'=>'no user id sent');
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
                                if(!empty(put('username'))) $user->change_username($userid,put('username'));
                                if(!empty(put('email'))) $user->change_email($userid,put('email'));
                                $userdata = array(
                                    'name'=>put('name'),
                                    'location'=>put('location'),
                                    'bio'=>put('bio')
                                );
                                array_filter($userdata);
                                if (!empty($userdata) && $user->set($userid, $userdata)) {
                                    //cydynni and user tables updated. return with newly edited user 
                                    $result = $cydynni->getUser($userid);
                                }
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
                        $club_id = put('clubid');
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
