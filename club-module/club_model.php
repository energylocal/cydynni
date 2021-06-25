<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Club{

    private $mysqli;
    private $redis;

    public function __construct($mysqli,$redis) {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
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
        $stmt->execute();
        $stmt->close();
        return array("success"=>true, "message"=>"$prop updated");
    }
    
    /**
     * build table of tariff prices per production source
     */
    public function getTariffsTable($tariffs) {
        global $lang;
        $tariffs = json_decode(json_encode($tariffs));
        // add properties and format strings...
        foreach($tariffs as $t) {
            // calculate how much smaller "hydro" is from "import"
            // $t->diff = sprintf("(%d%%)", round(100/($t->import / $t->generator)));
            $start = intval(date('G', strtotime($t->start)));
            $end = intval(date('G', strtotime($t->end)));
            $now = intval(date('G'));
            $t->isCurrent = $now >= $start && $now < $end;
            // add 12hr times with am/pm
            $t->start = date('g', strtotime($t->start)) . ($t->start < 12 ? translate('am', $lang): translate('pm', $lang));
            $t->end = date('g', strtotime($t->end)) . ($t->end < 12 ? translate('am', $lang): translate('pm', $lang));
            // add css class names to style the title column
            $t->css = 'text-' . $t->name;
            $t->rowClass = $t->isCurrent ? ' class="current"': '';
        }
        return $tariffs;
    }
    
    public function get_available_reports($feed,$feedid) {
        $available_reports = array();
        if (!$feedid) return false;
        
        $meta = $feed->get_meta($feedid);
       
        $d = new DateTime();
        $d->setTimezone(new DateTimeZone("Europe/London"));
        $d->setTimestamp($meta->start_time);
        $d->setDate($d->format("Y"),$d->format("m"),1);
        $d->setTime(0,0,0);
        $time = $d->getTimestamp();
        
        $end = time();
        
        while ($time<$end) {
            $available_reports[] = $d->format("Y-m");
            $d->modify('+1 month');
            $time = $d->getTimestamp();
        }
        
        return $available_reports;
    }
}
