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

require "missing_tariff_exception.php";
require "missing_user_tariff_exception.php";

class Tariff
{
    private $mysqli;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
    }

    // List tariffs belonging to a club from tariffs table
    public function list($clubid) {
        $clubid = (int) $clubid;

        // build map of tariffid=>array of start times
        $result = $this->mysqli->query("SELECT DISTINCT tariffid,start FROM user_tariffs;");
        while ($row = $result->fetch_assoc()) {
          $tariffId = $row['tariffid'];
          $start = $row['start'];

          if (!isset($distinct_tariff_starts[$tariffId])) {
            // Initialise the start array
            $distinct_tariff_starts[$tariffId] = [];
          }

          // Append the start to the array of starts for this tariff_id
          $distinct_tariff_starts[$tariffId][] = $start;
        }
        $result->close();

        $stmt = $this->mysqli->prepare("SELECT
                                          MAX(ut.start) AS last_assigned_unix,
                                          (SELECT COUNT(*) FROM cydynni WHERE clubs_id=?) AS total_club_users_count,
                                          t.*, COUNT(ut.tariffid) AS active_users
                                        FROM
                                          tariffs t
                                        LEFT JOIN user_tariffs ut ON t.id=ut.tariffid
                                        WHERE
                                          t.clubid=? GROUP BY t.id, ut.tariffid;");
        $stmt->bind_param("ii", $clubid, $clubid);
        $stmt->execute();
        $result = $stmt->get_result();

        $tariffs = array();
        /*
        {
        "id": "1",
        "clubid": "1",
        "name": "Tariff 1",
        "created": "1st January 2010",
        "first_assigned": "1st January 2010",
        "last_assigned": "1st January 2010",
        "standing_charge": "0",
        "total_club_users_count": "140",
        "distinct_tariff_starts": [
            "1262304000"
        ],
        "active_users": 138,
        "last_assigned_unix": 1262304000
        },*/
        while ($row = $result->fetch_object()) {
            // add distinct tariff starts to $row
            if (isset($distinct_tariff_starts[$row->id])) {
              $row->distinct_tariff_starts = $distinct_tariff_starts[$row->id];
            } else {
              $row->distinct_tariff_starts = [];
            }
            $row->created = date("jS F Y",$row->created); // convert created to date 12th September 2013
            $tariffs[] = $row;
        }
        $result->close();
        return $tariffs;
    }

    // Create a new tariff
    public function create($clubid,$name) {
        $clubid = (int) $clubid;
        if ($name=="") return array("success"=>false, "message"=>"Tariff name cannot be empty");

        // a-z A-Z 0-9 whitespace and - _ . only
        if (preg_match('/[^a-zA-Z0-9\s\-_\.]/', $name)) {
            return array("success"=>false, "message"=>"Tariff name can only contain a-z A-Z 0-9 whitespace and - _ .");
        }

        $time = time();
        $stmt = $this->mysqli->prepare("INSERT INTO tariffs (clubid,name,created,standing_charge) VALUES (?,?,?,0)");
        $stmt->bind_param("isi",$clubid,$name,$time);
        $stmt->execute();
        $stmt->close();
        return array("success"=>true, "id"=>$this->mysqli->insert_id);
    }

    // Create a new tariff
    public function clone(int $tariffid) {
        $tariffid = (int) $tariffid;

        $this->mysqli->begin_transaction();

        $stmt = $this->mysqli->prepare("
          INSERT INTO tariffs
            (clubid,name,created,first_assigned,last_assigned,standing_charge)
          SELECT
            clubid,CONCAT('Copy of ', name),unix_timestamp(),first_assigned,last_assigned,standing_charge
          FROM
            tariffs
          WHERE
            id=?;
        ");
        $stmt->bind_param("i",$tariffid);
        $stmt->execute();
        $clonedTariffId = $stmt->insert_id;
        $stmt->close();

        $stmt = $this->mysqli->prepare("
          INSERT INTO tariff_periods
            (tariffid,`index`,name,weekend,start,generator,import,color,subdued_color)
          SELECT
            ?,`index`,name,weekend,start,generator,import,color,subdued_color
          FROM
            tariff_periods
          WHERE
            tariffid=?;
        ");
        $stmt->bind_param("ii",$clonedTariffId, $tariffid);
        $stmt->execute();
        $stmt->close();
        $this->mysqli->commit();
        return array("success"=>true, "id"=>$this->mysqli->insert_id);
    }

    // Assign this tariff to all the users in that club, for a given start time
    public function assign_all_user_tariffs($tariffid, $start) {
      $stmt = $this->mysqli->prepare("
        INSERT INTO user_tariffs
          (userid, tariffid, start)
        SELECT
          cy.userid,t.id,?
        FROM
          cydynni cy
        INNER JOIN
          tariffs t ON t.clubid=cy.clubs_id
        WHERE
          t.id=?;
      ");
      $stmt->bind_param("ii", $start, $tariffid);
      $stmt->execute();
      $stmt->close();

      return array("success"=>true);
    }

    // Delete a tariff
    public function delete($tariffid) {
        $tariffid = (int) $tariffid;

        // Only allow tariff to be deleted if it has never been assigned
        if ($this->first_assigned($tariffid)) {
            return array("success"=>false, "message"=>"Tariff has been assigned to users");
        }

        // Delete all tariff periods
        $this->mysqli->query("DELETE FROM tariff_periods WHERE tariffid='$tariffid'");

        $stmt = $this->mysqli->prepare("DELETE FROM tariffs WHERE id=?");
        $stmt->bind_param("i",$tariffid);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected==0) return array("success"=>false);


        // Delete all user tariffs (can only delete unassigned tariffs)
        // $this->mysqli->query("DELETE FROM user_tariffs WHERE tariffid='$tariffid'");

        return array("success"=>true);
    }

    // List tariff periods belonging to tariff from tariff_periods table
    public function list_periods($tariffid) {
        $tariffid = (int) $tariffid;
        
        $result = $this->mysqli->query("SELECT * FROM tariff_periods WHERE tariffid='$tariffid' ORDER BY `index` ASC");
        $periods = array();
        while ($row = $result->fetch_object()) {
            $row->tariffid = (int) $row->tariffid;
            $row->index = (int) $row->index;
            $row->weekend = (int) $row->weekend;
            $row->start = (float) $row->start / 10;
            $periods[] = $row;
        }
        return $periods;
    }

      // List tariff periods belonging to tariff from tariff_periods table
    public function list_weekday_periods($tariffid) {
        $tariffid = (int) $tariffid;
        
        $result = $this->mysqli->query("SELECT * FROM tariff_periods WHERE tariffid='$tariffid' AND weekend=0 ORDER BY `index` ASC");
        $periods = array();
        while ($row = $result->fetch_object()) {
            $row->tariffid = (int) $row->tariffid;
            $row->index = (int) $row->index;
            $row->weekend = (int) $row->weekend;
            $row->start = (float) $row->start / 10;
            $periods[] = $row;
        }
        return $periods;
    }

    public function list_weekend_periods($tariffid) {
      $tariffid = (int) $tariffid;
      
      $result = $this->mysqli->query("SELECT * FROM tariff_periods WHERE tariffid='$tariffid' AND weekend=1 ORDER BY `index` ASC");
      $periods = array();
      while ($row = $result->fetch_object()) {
          $row->tariffid = (int) $row->tariffid;
          $row->index = (int) $row->index;
          $row->weekend = (int) $row->weekend;
          $row->start = (float) $row->start / 10;
          $periods[] = $row;
      }
      return $periods;
    }
    

    // Add a tariff period to a tariff
    public function add_period($tariffid,$name,$weekend,$start,$generator,$import,$color) {
        $tariffid = (int) $tariffid;
        $weekend = (int) $weekend;
        $start = (int) ($start * 10);
        $generator = (float) $generator;
        $import = (float) $import;

        // a-z A-Z 0-9 whitespace and - _ . only
        if (preg_match('/[^a-zA-Z0-9\s\-_\.]/', $name)) {
            return array("success"=>false, "message"=>"Tariff period name can only contain a-z A-Z 0-9 whitespace and - _ .");
        }

        // validate color #000 or #000000
        if (!preg_match('/^#[a-fA-F0-9]{3}([a-fA-F0-9]{3})?$/', $color)) {
            return array("success"=>false, "message"=>"Tariff period color is invalid");
        }

        // Only allow tariff periods to be added if it has never been assigned
        if ($this->first_assigned($tariffid)) return array("success"=>false, "message"=>"Tariff has been assigned to users");

        // Check number of periods in this tariff
        $result = $this->mysqli->query("SELECT COUNT(*) AS count FROM tariff_periods WHERE tariffid='$tariffid'");
        $row = $result->fetch_object();
        $index = $row->count;

        $stmt = $this->mysqli->prepare("INSERT INTO tariff_periods (tariffid,`index`,name,weekend,start,generator,import,color) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iisiidds",$tariffid,$index,$name,$weekend,$start,$generator,$import,$color);

        $stmt->execute();
        $stmt->close();
        return array("success"=>true);
    }

    // Save period
    public function save_period($tariffid,$index,$name,$weekend,$start,$generator,$import,$color) {
        $tariffid = (int) $tariffid;
        $index = (int) $index;
        $weekend = (int) $weekend;
        $start = (int) ($start * 10);
        $generator = (float) $generator;
        $import = (float) $import;

        // a-z A-Z 0-9 whitespace and - _ . only
        if (preg_match('/[^a-zA-Z0-9\s\-_\.]/', $name)) {
            return array("success"=>false, "message"=>"Tariff period name can only contain a-z A-Z 0-9 whitespace and - _ .");
        }

        // validate color #000 or #000000
        if (!preg_match('/^#[a-fA-F0-9]{3}([a-fA-F0-9]{3})?$/', $color)) {
            return array("success"=>false, "message"=>"Tariff period color is invalid");
        }

        // Only allow tariff periods to be added if it has never been assigned
        if ($this->first_assigned($tariffid)) return array("success"=>false, "message"=>"Tariff has been assigned to users");

        // Validate index
        $result = $this->mysqli->query("SELECT COUNT(*) AS count FROM tariff_periods WHERE tariffid='$tariffid'");
        $row = $result->fetch_object();
        if ($index<0 || $index>=$row->count) return array("success"=>false, "message"=>"Invalid index");

        $stmt = $this->mysqli->prepare("UPDATE tariff_periods SET name=?, weekend=?, start=?, generator=?, import=?, color=? WHERE tariffid=? AND `index`=?");
        $stmt->bind_param("siiddsii",$name,$weekend,$start,$generator,$import,$color,$tariffid,$index);
        $stmt->execute();
        $stmt->close();
        return array("success"=>true);
    }

    // Delete a tariff period
    public function delete_period($tariffid,$index) {
        $tariffid = (int) $tariffid;
        $index = (int) $index;

        // Only allow tariff periods to be added if it has never been assigned
        if ($this->first_assigned($tariffid)) return array("success"=>false, "message"=>"Tariff has been assigned to users");
        
        $stmt = $this->mysqli->prepare("DELETE FROM tariff_periods WHERE tariffid=? AND `index`=?");
        $stmt->bind_param("ii",$tariffid,$index);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected==0) return array("success"=>false);

        // Re-index tariff periods
        $result = $this->mysqli->query("SELECT * FROM tariff_periods WHERE tariffid='$tariffid' ORDER BY `index` ASC");
        $index = 0;
        while ($row = $result->fetch_object()) {
            $this->mysqli->query("UPDATE tariff_periods SET `index`='$index' WHERE `index`='$row->index'");
            $index++;
        }

        return array("success"=>true);
    }

    // Find when tariff was first and last assigned
    public function first_assigned($tariffid) {
        $tariffid = (int) $tariffid;

        $result = $this->mysqli->query("SELECT `start` FROM user_tariffs WHERE tariffid='$tariffid' ORDER BY `start` ASC LIMIT 1");
        $row = $result->fetch_object();
        if ($row) return $row->start*1;
        return false;
    }
    public function last_assigned($tariffid) {
        $tariffid = (int) $tariffid;

        $result = $this->mysqli->query("SELECT `start` FROM user_tariffs WHERE tariffid='$tariffid' ORDER BY `start` DESC LIMIT 1");
        $row = $result->fetch_object();
        if ($row) return $row->start*1;
        return false;
    }

    // Check if a tariff exists
    public function get_tariff($tariffid) {
        $tariffid = (int) $tariffid;
        $result = $this->mysqli->query("SELECT * FROM tariffs WHERE id=$tariffid");
        return $result->fetch_object();
    }

    // Get tariff name
    public function get_tariff_name($tariffid) {
        $tariffid = (int) $tariffid;
        $result = $this->mysqli->query("SELECT name FROM tariffs WHERE id=$tariffid");
        if ($row = $result->fetch_object()) {
            return $row->name;
        } else {
            return false;
        }
    }

    // Set tariff name
    public function set_tariff_name($tariffid, $name) {
        // TODO handle errors - return t/f
        $stmt = $this->mysqli->prepare("UPDATE tariffs SET name=? WHERE id=?");
        $stmt->bind_param("si", $name, $tariffid);
        $stmt->execute();
        $stmt->close();
        // TODO handle errors
        return true;
    }

    // Get tariff standing charge
    public function get_tariff_standing_charge($tariffid) {
        $tariffid = (int) $tariffid;
        $result = $this->mysqli->query("SELECT standing_charge FROM tariffs WHERE id=$tariffid");
        if ($row = $result->fetch_object()) {
            return $row->standing_charge*0.01;
        } else {
            return false;
        }
    }

    // Set tariff standing charge
    public function set_tariff_standing_charge($tariffid, $charge) {
        $stmt = $this->mysqli->prepare("UPDATE tariffs SET standing_charge=? WHERE id=?");
        $stmt->bind_param("di", $charge, $tariffid);
        $stmt->execute();
        $stmt->close();
        // TODO handle errors
        return true;
    }


    // User tariff methods

    // Add tariff to user
    public function set_user_tariff($userid,$tariffid,$start=false) {
        $userid = (int) $userid;
        $tariffid = (int) $tariffid;

        if (!$start) $start = time();

        // Check if user exists
        $result = $this->mysqli->query("SELECT userid FROM cydynni WHERE userid=$userid");
        if (!$row = $result->fetch_object()) {
            return array("success"=>false,"message"=>"User does not exist");
        }

        // Check if tariff exists
        if (!$tariff = $this->get_tariff($tariffid)) {
            return array("success"=>false,"message"=>"Tariff does not exist");
        }
        
        // Get most recent tariff
        $result = $this->mysqli->query("SELECT tariffid,`start` FROM user_tariffs WHERE userid=$userid ORDER BY start DESC LIMIT 1");
        if ($row = $result->fetch_object()) {
            // Check if tariff is already set
            if ($row->tariffid==$tariffid) {
                return array("success"=>false,"message"=>"Tariff already set");
            }

            // check if tariff is already set to start in the future (this should never happen)
            if ($row->start>$start) {
                return array("success"=>false,"message"=>"Tariff already set to start in the future");
            }
        }

        // Add tariff to user
        $stmt = $this->mysqli->prepare("INSERT INTO user_tariffs (userid,tariffid,start) VALUES (?,?,?)");
        $stmt->bind_param("iii",$userid,$tariffid,$start);
        $stmt->execute();
        $stmt->close();

        return array("success"=>true, "message"=>"User tariff added");
    }

    // Get user tariff
    public function get_user_tariff_id($userid) {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT tariffid FROM user_tariffs WHERE userid=$userid ORDER BY start DESC LIMIT 1");

        if (mysqli_num_rows($result) == 0) {
          throw new MissingUserTariffException("No user tariff for user id $userid.");
        }

        if ($row = $result->fetch_object()) {
            return $row->tariffid;
        } else {
            return false;
        }
    }

    // Get user tariff history
    public function get_user_tariff_history($userid, $with_names = false) {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT tariffid,start FROM user_tariffs WHERE userid=$userid ORDER BY start ASC");
        $history = array();
        while ($row = $result->fetch_object()) {
            if ($with_names) {
                $row->tariff_name = $this->get_tariff_name($row->tariffid);
                // convert start to date 12th September 2013 00:00
                $row->start = date("jS F Y H:i",$row->start);
            }
            $history[] = $row;
        }
        return $history;
    }

    // Get club tariff history
    // This is a temporary approach that gets the first history  for the first user allocated to the club
    // This is used to display the tariff history on the club page
    public function get_club_tariff_history($clubid) {
        $clubid = (int) $clubid;

        // get tariff list for club
        $result = $this->mysqli->query("SELECT id,name,standing_charge FROM tariffs WHERE clubid='$clubid'");
        $history = array();
        while ($row = $result->fetch_object()) {
            $t = new stdClass();
            $t->tariffid = (int) $row->id;
            $t->tariff_name = $row->name;
            $t->standing_charge = $row->standing_charge;

            $t->start = $this->first_assigned($row->id);

            $history[] = $t;
        }
        return $history;
    }

    // Get club tariff with return limit 1 desc
    public function get_club_latest_tariff($clubid) {
        $clubid = (int) $clubid;
        $result = $this->mysqli->query("SELECT id,name,standing_charge FROM tariffs WHERE clubid='$clubid' ORDER BY id DESC LIMIT 1");
        $row = $result->fetch_object();
        $t = new stdClass();
        if (mysqli_num_rows($result) == 0) {
          throw new MissingTariffException("No tariff for club id $clubid");
        }
        $t->tariffid = (int) $row->id;
        $t->tariff_name = $row->name;
        $t->standing_charge = $row->standing_charge;
        // $t->start = $this->first_assigned($row->id);
        return $t;
    }


    // Replace with client side pre-processing?
    public function getTariffsTable($tariffs) {
        global $lang;
        $tariffs = json_decode(json_encode($tariffs));
        $weekday_periods = array();
        $weekend_periods = array();
        $output = array();
        for ($i=0; $i<count($tariffs); $i++) {
          $t = $tariffs[$i];
          if ($t->weekend == 1) {
            $weekend_periods[] = $t;
          } else {
            $weekday_periods[] = $t;
          }
        }
        // add properties and format strings...
        for ($i=0; $i<count($weekday_periods); $i++) {
            $t = $weekday_periods[$i];
            $next = $i+1;
            if ($next<count($weekday_periods)) {
              // if there is a 'next' tariff period, endtime of current period becomes the start of next period
              $t->end = $weekday_periods[$next]->start;
            } else {
              // otherwise, if this is the last tariff period, end becomes the start of the first period
              $t->end = $weekday_periods[0]->start;
            }

            // start is taken directly from this entry's start value
            $t->start = (int) $t->start;
            // convert 6.5 to 06:30
            $h = floor($t->start);
            if ($h<10) $h = '0' . $h;
            $m = ($t->start-floor($t->start))*60;
            if ($m<10) $m = '0' . $m;
            $t->start = $h . ':' . $m;

            $t->end = (int) $t->end;
            // convert 6.5 to 06:30
            $h = floor($t->end);
            if ($h<10) $h = '0' . $h;
            $m = ($t->end-floor($t->end))*60;
            if ($m<10) $m = '0' . $m;
            $t->end = $h . ':' . $m;

            $start = intval(date('G', strtotime($t->start)));
            $end = intval(date('G', strtotime($t->end)));
            $now = intval(date('G'));
            $t->isCurrent = $now >= $start && $now < $end;
            // add css class names to style the title column // TODO - this would be better as a flag for the frontend to resolve
            $t->css = 'text-' . $t->name;
            $t->rowClass = $t->isCurrent ? ' class="current"': '';
            $output[] = $t;
        }
        if (count($weekend_periods) > 0) {
          for ($i=0; $i<count($weekend_periods); $i++) {
            $t = $weekend_periods[$i];
            $next = $i+1;
            if ($next<count($weekend_periods)) {
              // if there is a 'next' tariff period, endtime of current period becomes the start of next period
              $t->end = $weekend_periods[$next]->start;
            } else {
              // otherwise, if this is the last tariff period, end becomes the start of the first period
              $t->end = $weekend_periods[0]->start;
            }

            // start is taken directly from this entry's start value
            $t->start = (int) $t->start;
            // convert 6.5 to 06:30
            $h = floor($t->start);
            if ($h<10) $h = '0' . $h;
            $m = ($t->start-floor($t->start))*60;
            if ($m<10) $m = '0' . $m;
            $t->start = $h . ':' . $m;

            $t->end = (int) $t->end;
            // convert 6.5 to 06:30
            $h = floor($t->end);
            if ($h<10) $h = '0' . $h;
            $m = ($t->end-floor($t->end))*60;
            if ($m<10) $m = '0' . $m;
            $t->end = $h . ':' . $m;

            $start = intval(date('G', strtotime($t->start)));
            $end = intval(date('G', strtotime($t->end)));
            $now = intval(date('G'));
            $t->isCurrent = $now >= $start && $now < $end;
            // add css class names to style the title column // TODO - this would be better as a flag for the frontend to resolve
            $t->css = 'text-' . $t->name;
            $t->rowClass = $t->isCurrent ? ' class="current"': '';
            $output[] = $t;
          }
        }

        return $output;
    }

    public function get_concise_tariffs_table($tariffid) {
      $weekday_tariffs = $this->list_weekday_periods($tariffid);
      $weekday_tariffs_table = $this->getTariffsTable($weekday_tariffs);
      $weekend_tariffs = $this->list_weekend_periods($tariffid);
      $weekend_tariffs_table = $this->getTariffsTable($weekend_tariffs);
      $concise_tariffs_table = $weekday_tariffs_table;
      // Check if $weekend_tariffs_table has entries
      if (count($weekend_tariffs_table) > 0) {
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
      }
      return $concise_tariffs_table;
    }

    // Get tariff bands for a given time
    public function get_tariff_bands($tariff_history,$time) {
      $bands = array();
      foreach ($tariff_history as $individual_tariff) {
          if ($time>=$individual_tariff->start) {
              $bands = $this->getTariffsTable($individual_tariff->bands);
          }
      }
      $weekday_bands = array();
      $weekend_bands = array();
      $weekend_present = 0;
      foreach ($bands as $band) {
          if ($band->weekend == 0) {
              $weekday_bands[] = $band;
          } else if ($band->weekend == 1) {
              $weekend_bands[] = $band;
              $weekend_present = 1;
          }
      }
      if ($weekend_present = 0) {
          return $bands;
      } else {
          $concise_bands = $weekday_bands;
          foreach ($weekend_bands as $weekend_entry) {
              foreach ($weekday_bands as $weekday_entry) {
                  if ($weekend_entry->start === $weekday_entry->start && $weekend_entry->import < $weekday_entry->import) {
                      $concise_bands[] = $weekend_entry;
                  }
              }
          }
          return $concise_bands;
      }
  }

    // Get tariff band for a given hour
    public function get_tariff_band($bands,$hour,$weekend) {
      // first, if the requested hour falls within a weekend, check if there's a weekend tariff period that matches
      if ($weekend == 1) {
        for ($i=0; $i<count($bands); $i++) {
          if ($bands[$i]->weekend == 0) {
            continue;
          }
          $start = (float) $bands[$i]->start;

          // calculate end
          $end = (float) $bands[$i]->end;

          // if start is less than end then period is within a day
          if ($start<$end) {
              if ($hour>=$start && $hour<$end) {
                  return $bands[$i];
              }
          // if start is greater than end then period is over midnight
          } else if ($end<$start) {
              if ($hour>=$start || $hour<$end) {
                  return $bands[$i];
              }
          // if start is equal to end then period is 24 hours
          // flat rate tariff
          } else if ($start==$end) {
              return $bands[$i];
          }
        }
      }

      // Work out which tariff period this hour falls into
      for ($i=0; $i<count($bands); $i++) {
          $start = (float) $bands[$i]->start;

          // calculate end
          $next = $i+1;
          if ($next==count($bands)) $next=0;
          $end = (float) $bands[$next]->start;

          // if start is less than end then period is within a day
          if ($start<$end) {
              if ($hour>=$start && $hour<$end) {
                  return $bands[$i];
              }
          // if start is greater than end then period is over midnight
          } else if ($end<$start) {
              if ($hour>=$start || $hour<$end) {
                  return $bands[$i];
              }
          // if start is equal to end then period is 24 hours
          // flat rate tariff
          } else if ($start==$end) {
              return $bands[$i];
          }
      }
      return false;
  }

    // Calculate unit price 
    public function get_unit_price($consumption, $generation, $band) {

        // calculate self consumption and import
        $import = 0.0;
        if ($generation<=$consumption) $import = $consumption - $generation;
        $self_consumption = $consumption - $import;

        // calculate unit price
        $unit_price = 0.0;
        $generation_cost = $self_consumption * $band->generator;
        $import_cost = $import * $band->import;
        if ($consumption>0) {
            $unit_price = number_format(($import_cost + $generation_cost) / $consumption,2)*1;
        }
        return $unit_price;
    }

    // find min and max import and generator prices
    public function get_min_max_prices($periods) {

        $min_import = 1000;
        $max_import = 0;

        $min_generator = 1000;
        $max_generator = 0;

        foreach ($periods as $period) {
            if ($period->import < $min_import) $min_import = $period->import;
            if ($period->import > $max_import) $max_import = $period->import;

            if ($period->generator < $min_generator) $min_generator = $period->generator;
            if ($period->generator > $max_generator) $max_generator = $period->generator;
        }

        return array(
            "min_import" => $min_import,
            "max_import" => $max_import,
            "min_generator" => $min_generator,
            "max_generator" => $max_generator,
            "min" => min($min_import,$min_generator),
            "max" => max($max_import,$max_generator)
        );
    }

    // get traffic light (green, amber, red) status based on unit price in range of min and max
    public function get_status($unit_price,$periods) {
        $range = $this->get_min_max_prices($periods);

        // split range into 3 equal parts
        $step = ($range['max'] - $range['min']) / 3;

        // calculate green, amber, red thresholds
        $green = $range['min'] + $step;
        $amber = $range['min'] + $step*2;
        $red = $range['max'];

        // calculate status
        if ($unit_price<$green) return "green";
        if ($unit_price<$amber) return "amber";
        return "red";
    }
    public function set_temporary_fixed_tariff($userid, $import): void {
      // If the current user tariff is called user_tariff_{USERID} then update all period's import rate
      // Used for non-club dashboards

      $tariffid = $this->get_user_tariff_id($userid);
      $tariff = $this->get_tariff($tariffid);
      $tariff_name = "user_tariff_".$userid;
      if ($tariff->name != "user_tariff_".$userid) {
        throw new Exception("User tariff is not called '$tariff_name'");
      }

      $periods = $this->list_periods($tariffid);
      foreach ($periods as $period) {
        $currentRate = $period->import;
        $index = $period->index;
        $t = $period->tariffid;
        $stmt = $this->mysqli->prepare("UPDATE tariff_periods SET import=? WHERE tariffid=? AND `index`=?");
        $stmt->bind_param("dii", $import, $period->tariffid, $period->index);
        $stmt->execute();
        $stmt->close();
      }
    }
    public function set_temporary_economy7_tariff($userid, $daytime, $economy7): void {
      // The current user tariff must be called user_tariff_{USERID} and have two
      // periods, daytime and overnight

      $tariffid = $this->get_user_tariff_id($userid);
      $tariff = $this->get_tariff($tariffid);
      $tariff_name = "user_tariff_".$userid;
      if ($tariff->name != "user_tariff_".$userid) {
        throw new Exception("User tariff is not called '$tariff_name'");
      }

      $periods = $this->list_periods($tariffid);
      if (count($periods) != 2) {
        throw new Exception("User tariff '$tariff_name' does not have two periods, not economy7 compatible");
      }
      foreach ($periods as $period) {
        switch ($period->name) {
        case "daytime":
          break;
        case "overnight":
          break;
        default:
          throw new Exception("User tariff '$tariff_name' has periods must be daytime & overnight to be economy7 compatible; have '".$period->name."'");
        }
      }

      $rate = 0;
      foreach ($periods as $period) {
        switch ($period->name) {
        case "daytime":
          $rate = $daytime;
          break;
        case "overnight":
          $rate = $economy7;
          break;
        }
        $currentRate = $period->import;
        $index = $period->index;
        $t = $period->tariffid;
        $stmt = $this->mysqli->prepare("UPDATE tariff_periods SET import=? WHERE tariffid=? AND `index`=?");
        $stmt->bind_param("dii", $rate, $period->tariffid, $period->index);
        $stmt->execute();
        $stmt->close();
      }
    }
}
