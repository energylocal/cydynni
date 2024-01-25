<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Account {

    private $mysqli;
    private $user;

    public function __construct($mysqli,$user) {
        $this->mysqli = $mysqli;
        $this->user = $user;
    }

    public function list($clubid) {
        $clubid = (int) $clubid;
        
        $result = $this->mysqli->query("SELECT userid,mpan,cad_serial,owl_id,meter_serial,octopus_apikey,welcomedate,reportdate,clubs_id FROM cydynni WHERE `clubs_id`='$clubid' ORDER BY userid ASC");
        $users = array();
        while($row = $result->fetch_object()) {
            $userid = $row->userid;
            
            $user_result = $this->mysqli->query("SELECT username,email,apikey_read,admin FROM users WHERE `id`='$userid'");
            $user_row = $user_result->fetch_object();
            if ($user_row) {
                foreach ($user_row as $key=>$val) $row->$key = $user_row->$key;
            }
            
            $users[] = $row;
        }
        return $users;
    }

    public function add($u) {
        
        if (!isset($u->username)) return array("success"=>false, "message"=>"missing username");  
        if (!isset($u->email)) return array("success"=>false, "message"=>"missing email");  
        if (!isset($u->clubs_id)) return array("success"=>false, "message"=>"missing clubs_id");  
        if (!isset($u->mpan)) return array("success"=>false, "message"=>"missing mpan");
        if (!isset($u->cad_serial)) return array("success"=>false, "message"=>"missing cad_serial");
        if (!isset($u->owl_id)) return array("success"=>false, "message"=>"missing owl_id");
        if (!isset($u->octopus_apikey)) return array("success"=>false, "message"=>"missing octopus_apikey");
        if (!isset($u->meter_serial)) return array("success"=>false, "message"=>"meter_serial email");

        if (!ctype_digit($u->mpan) && $u->mpan!="") return array("success"=>false, "message"=>"invalid mpan");  
        if (!ctype_alnum($u->cad_serial) && $u->cad_serial!="") return array("success"=>false, "message"=>"invalid cad_serial");  
        if (!ctype_digit($u->owl_id) && $u->owl_id!="") return array("success"=>false, "message"=>"invalid owl_id");  
        if (!preg_match('/^\w+$/',$u->octopus_apikey) && $u->octopus_apikey!="") return array("success"=>false, "message"=>"invalid octopus_apikey");  
        if (!ctype_alnum($u->meter_serial) && $u->meter_serial!="") return array("success"=>false, "message"=>"invalid meter_serial");
        
        // Generate new random password if not set
        if (!isset($u->password)) $u->password = "";
        if ($u->password==null || $u->password=="") {
            $u->password = hash('sha256',md5(uniqid(rand(), true)));
            $u->password = substr($u->password, 0, 10);
        }
        
        $result = $this->user->register($u->username, $u->password, $u->email, "Europe/London");
        if ($result["success"]) {
            $userid = $result["userid"];
            $result = $this->add_user((int)$u->clubs_id,$userid,(int)$u->mpan,$u->cad_serial,$u->octopus_apikey,$u->meter_serial);
            include "Modules/remoteaccess/remoteaccess_userlink.php";
            remoteaccess_userlink_existing($this->mysqli,$userid);
        }
        return $result;
    }
    
    public function update($userid, $changed) {

        if (isset($changed->username)) {
            $result = $this->user->change_username($userid,$changed->username);
            if (!$result['success']) return $result;
        }

        if (isset($changed->email)) {
            $result = $this->user->change_email($userid,$changed->email);
            if (!$result['success']) return $result;
        }

        if (isset($changed->mpan)) {
            $changed->mpan = trim($changed->mpan);
            if (!ctype_digit($changed->mpan)) return array("success"=>false, "message"=>"invalid mpan");
            $result = $this->change_user_prop($userid,"mpan",$changed->mpan);
            if (!$result['success']) return $result;
        }

        if (isset($changed->cad_serial)) {
            $changed->cad_serial = trim($changed->cad_serial);
            if (!ctype_alnum($changed->cad_serial)) return array("success"=>false, "message"=>"invalid cad_serial");  
            $result = $this->change_user_prop($userid,"cad_serial",$changed->cad_serial);
            if (!$result['success']) return $result;
        }

        if (isset($changed->owl_id)) {
            $changed->owl_id = trim($changed->owl_id);
            if (!ctype_digit($changed->owl_id)) return array("success"=>false, "message"=>"invalid owl_id");
            $result = $this->change_user_prop($userid,"owl_id",$changed->owl_id);
            if (!$result['success']) return $result;
        }
        if (isset($changed->octopus_apikey)) {
            $changed->mpan = trim($changed->octopus_apikey);
            if (!preg_match('/^\w+$/',$changed->octopus_apikey)) return array("success"=>false, "message"=>"invalid octopus_apikey");  
            $result = $this->change_user_prop($userid,"octopus_apikey",$changed->octopus_apikey);
            if (!$result['success']) return $result;
        }
        
        if (isset($changed->meter_serial)) {
            $changed->mpan = trim($changed->meter_serial);
            if (!ctype_alnum($changed->meter_serial)) return array("success"=>false, "message"=>"invalid meter_serial");  
            $result = $this->change_user_prop($userid,"meter_serial",$changed->meter_serial);
            if (!$result['success']) return $result;
        }
        
        return array('success'=>true, 'message'=>'User updated');
    }
    
    public function add_user($club_id,$userid,$mpan,$cad_serial,$octopus_apikey,$meter_serial) {    
        $club_id = (int) $club_id;
        $userid = (int) $userid;
        
        $stmt = $this->mysqli->prepare("INSERT INTO cydynni (clubs_id,userid,mpan,cad_serial,octopus_apikey,meter_serial,welcomedate,reportdate) VALUES (?,?,?,?,?,?,0,0)");
        $stmt->bind_param("iiisss", $club_id, $userid, $mpan, $cad_serial, $octopus_apikey, $meter_serial);
        $stmt->execute();
        $stmt->close();
        
        return array("success"=>true, "userid"=>$userid, "message"=>"user created");
    }
    
    public function change_user_prop($uid,$prop,$value) {    
        // filter and check userid
        $uid = (int) $uid;
        if (!$uid) return array("success"=>false, "message"=>"invalid userid");
                
        // check if already set by another user
        $stmt = $this->mysqli->prepare("SELECT userid FROM cydynni WHERE $prop = ?");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->bind_result($userid);
        $stmt->fetch();
        $stmt->close();
        if ($userid!=null && $uid!=$userid) {
            return array("success"=>false, "message"=>"$prop already in use");
        }

        $stmt = $this->mysqli->prepare("UPDATE cydynni SET $prop = ? WHERE userid = ?");
        $stmt->bind_param("si", $value, $uid);
        $result = $stmt->execute();

        $success = true;
        $message = "$prop updated";
        if (!$result) {
          $log = new EmonLogger(__FILE__);
          $log->error("Problem updating cydynni.$prop for user $userid: ".$stmt->error);
          $success = false;
          $message = $stmt->error;
        }
        $stmt->close();
        return array("success"=>$success, "message"=>$message);
    }
}
