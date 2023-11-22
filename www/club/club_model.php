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

class Club
{
    private $mysqli;
    private $user;
    private $feed;

    public function __construct($mysqli,$user = false,$feed = false)
    {
        $this->mysqli = $mysqli;
        $this->user = $user;
        $this->feed = $feed;
        $this->log = new EmonLogger(__FILE__);
    }

    // Check if a club exists by id
    public function exists($id) {
        $stmt = $this->mysqli->prepare("SELECT id FROM club WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if ($num_rows>0) return true; else return false;
    }

    // Check if a club exists by name
    public function exists_name($name) {
        $stmt = $this->mysqli->prepare("SELECT id FROM club WHERE name=?");
        $stmt->bind_param("s",$name);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        if ($num_rows>0) return true; else return false;
    }

    // Get club name from id
    public function get_name($id) {
        $stmt = $this->mysqli->prepare("SELECT name FROM club WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $stmt->bind_result($name);
        $stmt->fetch();
        $stmt->close();
        return $name;
    }

    // Get club userid from id
    public function get_userid($id) {
        $id = (int) $id;
        $result = $this->mysqli->query("SELECT userid FROM club WHERE id=$id");
        $row = $result->fetch_object();
        return $row->userid;
    }
    

    // Create a new club
    public function create($name) {
        $time = time();
        // Alphanumeric and spaces only
        if (!ctype_alnum(str_replace(" ","",$name))) {
            return array("success"=>false,"message"=>"Club name must be alphanumeric");
        }
        // Check if club name already exists
        if ($this->exists_name($name)) {
            return array("success"=>false,"message"=>"Club name already exists");
        }

        // Create lowercase key without spaces
        $key = strtolower(str_replace(" ","",$name));

        // Create club
        $stmt = $this->mysqli->prepare("INSERT INTO club (`key`,`name`,created,userid) VALUES (?,?,?,0)");
        $stmt->bind_param("ssi",$key,$name,$time);
        $stmt->execute();
        $stmt->close();
        $clubid = $this->mysqli->insert_id;

        // Create club aggregation user
        if (!$this->create_club_user($clubid)) {
            return array("success"=>false,"message"=>"Failed to create club user");
        }

        return array("success"=>true,"id"=>$clubid);
    }

    // Create club aggregation user
    private function create_club_user($clubid) {
        $clubid = (int) $clubid;
        $username = "club".$clubid;
        $password = generate_secure_key(16);
        $email = "club".$clubid."@energylocal.org.uk";
        
        $result = $this->user->register($username,$password,$email,"Europe/London");
        if (!$result['success']) {
            return false;
        }
        $userid = $result['userid'];
        $this->mysqli->query("UPDATE club SET userid=$userid WHERE id=$clubid");
        return true;
    }

    // Delete a club
    public function delete($id) {
        // Check if club exists
        if (!$this->exists($id)) {
            return array("success"=>false,"message"=>"Club does not exist");
        }

        // Get club userid
        $result = $this->mysqli->query("SELECT userid FROM club WHERE id=$id");
        $row = $result->fetch_object();
        $club_userid = $row->userid;

        // Delete club
        $stmt = $this->mysqli->prepare("DELETE FROM club WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $stmt->close();

        // Delete club user
        $this->mysqli->query("DELETE FROM users WHERE `id`='$club_userid'");
        // Note this does not delete feeds or inputs

        return array("success"=>true);
    }

    // Return a list of clubs
    public function list() {
        $result = $this->mysqli->query("SELECT * FROM club ORDER BY created ASC");
        $clubs = array();
        while ($row = $result->fetch_object()) {
            // Convert unix timestamp to date Europe/London
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone("Europe/London"));
            $date->setTimestamp($row->created);
            // Format date 14th Jan 2014
            $row->created = $date->format('jS M Y');
            $row->share = (bool) $row->share;
            $row->menu = (bool) $row->menu;
            $row->has_generator = (bool) $row->has_generator;
            $row->languages = explode(",",$row->languages);

            if ($row->skip_users) {
                $row->skip_users = explode(",",$row->skip_users);
            } else {
                $row->skip_users = array();
            }

            $clubs[] = $row;
        }
        return $clubs;
    }

    public function list_assoc() {
        $result = $this->mysqli->query("SELECT `id`,`name` FROM club ORDER BY created ASC");
        $clubs = array();
        while ($row = $result->fetch_object()) {
            $clubs[$row->id] = $row->name;
        }
        return $clubs;
    }

    public function get($id) {
        $id = (int) $id;
        $result = $this->mysqli->query("SELECT * FROM club WHERE id=$id");
        $row = $result->fetch_object();
        
        // Automatic population of feedids
        $row->generation_feed = $this->feed->exists_tag_name(1,"Generation",$row->key);
        $row->consumption_feed = $this->feed->exists_tag_name(1,"Demand",$row->key);
        $row->generation_forecast_feed = $this->feed->exists_tag_name(1,"demandshaper",$row->key."_forecast_gen");
        $row->consumption_forecast_feed = $this->feed->exists_tag_name(1,"demandshaper",$row->key."_forecast_use");
        
        return $row;
    }

    public function set($id, $settings) {

        // Limit to listed keys
        $settings = array_intersect_key($settings,array_flip(array('menu','share','generator','generator_color','export_color','languages','generation_feed','consumption_feed','generation_forecast_feed','consumption_forecast_feed','unitprice_comparison','gen_scale','gen_limit','skip_users')));

        // Convert languages array to csv
        if (isset($settings['languages'])) $settings['languages'] = implode(",",$settings['languages']);

        // Convert skip_users array to csv
        if (isset($settings['skip_users'])) $settings['skip_users'] = implode(",",$settings['skip_users']);

        // Build query dynamically
        $query = "UPDATE club SET ";
        $params = array();
        foreach ($settings as $key=>$value) {
            $query .= "$key=?,";
            $params[] = $value;
        }
        $query = rtrim($query,",");
        $query .= " WHERE id=?";
        $params[] = $id;

        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param(str_repeat("s",count($params)),...$params);
        $stmt->execute();
        $stmt->close();
    }
    
    public function get_settings($key) {
        
        $result = $this->mysqli->query("SELECT * FROM club WHERE `key`='$key'");
        $club_settings = $result->fetch_array();
        
        // Automatic population of feedids
        $club_settings['generation_feed'] = $this->feed->exists_tag_name(1,"Generation",$key);
        $club_settings['consumption_feed'] = $this->feed->exists_tag_name(1,"Demand",$key);
        $club_settings['generation_forecast_feed'] = $this->feed->exists_tag_name(1,"demandshaper",$key."_forecast_gen");
        $club_settings['consumption_forecast_feed'] = $this->feed->exists_tag_name(1,"demandshaper",$key."_forecast_use");
    
        if ($club_settings['gen_scale']==null) {
            $club_settings['gen_scale'] = 1;
        }
    
        if ($club_settings['skip_users']) {
            $club_settings['skip_users'] = explode(",",$club_settings['skip_users']);
        } else {
            $club_settings['skip_users'] = array();
        }
    
        return $club_settings;
    }
}
