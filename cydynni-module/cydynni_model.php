<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Cydynni {

    private $mysqli;
    private $redis;

    public function __construct($mysqli,$redis) {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
    }

    public function getClubs($slug = "") {
        $result = $this->mysqli->query("SELECT * FROM cydynni_clubs ORDER BY id ASC LIMIT 1");
        $clubs = array();
        while($row = $result->fetch_object()) {
            $clubs[] = $row;
        }        
        return $clubs;
    }

    public function getClubById($id = "") {
        $result = $this->mysqli->query("SELECT * FROM cydynni_clubs WHERE id = ".$id);
        $clubs = array();
        while($row = $result->fetch_object()) {
            $clubs[] = $row;
        }        
        return $clubs;
    }

    
    public function getUsers($club_id = "") {
        $club_id = (int) $club_id;
        // Include data from cydynni table here too
        $result = $this->mysqli->query("SELECT id,username,email,apikey_read,admin FROM users ORDER BY id ASC");
        $users = array();
        while($row = $result->fetch_object()) {
            $userid = $row->id;
            // Include fields from cydynni table
            $user_result = $this->mysqli->query("SELECT mpan,token,welcomedate,reportdate,club_id FROM cydynni");
            $user_row = $user_result->fetch_object();
            if ($user_row) {
                foreach ($user_row as $key=>$val) $row->$key = $user_row->$key;
            }
            $row->hits = $this->redis->get("userhits:$userid");
            $row->testdata = json_decode($this->redis->get("user:summary:lastday:$userid"));
            $row->club = $this->getClubById($row->club_id)[0];
            $users[] = $row;
        }
        return $users;
    }
    
    public function set($userid="") {
    
        $userid = (int) $userid;
        
        return 'set';
    }

}
