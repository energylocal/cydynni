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

    public function __construct($mysqli,$user = false)
    {
        $this->mysqli = $mysqli;
        $this->user = $user;
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

        // Create club
        $stmt = $this->mysqli->prepare("INSERT INTO club (name,created,userid) VALUES (?,?,0)");
        $stmt->bind_param("si",$name,$time);
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
            $clubs[] = $row;
        }
        return $clubs;
    }

    public function get($id) {
        $id = (int) $id;
        $result = $this->mysqli->query("SELECT * FROM club WHERE id=$id");
        return $result->fetch_object();
    }

    public function set($id, $settings) {

        // Limit to listed keys
        $settings = array_intersect_key($settings,array_flip(array('share','generator','generator_color','export_color','languages','generation_feed','consumption_feed','generation_forecast_feed','consumption_forecast_feed','unitprice_comparison','gen_scale','gen_limit','skip_users')));

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
}