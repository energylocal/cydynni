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

class AccountData
{
    private $feed;
    private $club;
    private $tariff;
    private $log;

    public function __construct($feed, $club, $tariff)
    {
        $this->feed = $feed;
        $this->club = $club;
        $this->tariff = $tariff;
        $this->log = new EmonLogger(__FILE__);
    }
    
    public function user_daily_summary($userid,$start,$end) 
    {
        // Load tariff history for user
        $tariff_history = $this->tariff->get_user_tariff_history($userid);
        
        // Load tariff bands for each tariff
        foreach ($tariff_history as $tariff) {
            $tariff->bands = $this->tariff->list_periods($tariff->tariffid);
        }

        // Check if user has consumption feed
        // Load half hourly data between start and end times
        if ($userid != 1) {
            if (!$use_feedid = $this->feed->get_id($userid,"use_hh_est")) {
                return array("success"=>false, "message"=>"Missing consumption feed");
            }
        }

        $gen_feedid = $this->feed->get_id($userid,"gen_hh");
        
        return $this->daily_summary($tariff_history, $use_feedid, $gen_feedid ,$start, $end);
    }
    
    public function club_daily_summary($clubid,$start,$end) 
    {
        // Load tariff history for user
        // this is based on the complete list of tariffs for the club
        // and when each tariff was first assigned to a user
        $tariff_history = $this->tariff->get_club_tariff_history($clubid);

        // Load tariff bands for each tariff
        foreach ($tariff_history as $tariff) {
            $tariff->bands = $this->tariff->list_periods($tariff->tariffid);
        }
        
        $club = $this->club->get($clubid);
        
        return $this->daily_summary($tariff_history, $club->consumption_feed, $club->generation_feed, $start, $end);
    }

    public function daily_summary($tariff_history, $use_feedid, $gen_feedid ,$start, $end) 
    {
        $use_feedid = (int) $use_feedid;
        $gen_feedid = (int) $gen_feedid;
        $start = (int) $start;
        $end = (int) $end;

        $d = new DateTime();
        $d->setTimezone(new DateTimeZone("Europe/London"));
        $d->setTimestamp($start);
        // echo $d->format('Y-m-d H:i:s');
        $d->setTime(0,0,0);
        $start = $d->getTimestamp();
        $d->setTimestamp($end);
        $d->setTime(0,0,0);
        $d->modify('+1 day');
        $end = $d->getTimestamp();
        
        // Limit to end time of use and gen feeds
        $meta_use = $this->feed->get_meta($use_feedid);
        if ($end>$meta_use->end_time) $end = $meta_use->end_time;
        
        if ($gen_feedid) {
            $meta_gen = $this->feed->get_meta($gen_feedid);
            if ($end>$meta_gen->end_time) $end = $meta_gen->end_time;
        }
                
        $use_data = $this->feed->get_data($use_feedid,$start,$end,1800,0,"Europe/London","notime",false,0,0,false,-1);
        
        // Check if user has generation feed
        // Load generation data between start and end times
        if (!$gen_feedid) {
            // No generation feed, create empty array
            $gen_data = array();
            for ($i=0; $i<count($use_data); $i++) $gen_data[$i] = 0;
        } else {
            $gen_data = $this->feed->get_data($gen_feedid,$start,$end,1800,0,"Europe/London","notime",false,0,0,false,-1);
        }

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London")); 
        $date->setTimestamp($start);

        $year = (int) $date->format("Y");
        $month = (int) $date->format("m");
        $day = (int) $date->format("d");
        $hour = (int) $date->format("H");

        $period_allocation = array();
        $daily = array();

        // Keys to sum
        $categories = array("demand","import","generation","generation_cost","import_cost","cost");

        $n=0;
        for ($time=$start; $time<=$end; $time+=1800) {
            $date->setTimestamp($time);

            $last_year = $year;
            $last_month = $month;
            $last_day = $day;

            $year = (int) $date->format("Y");
            $month = (int) $date->format("m");
            $day = (int) $date->format("d");
            $hour = (int) $date->format("H");
            $iso_day = $date->format('N');
            $weekend = 0;
            if ($iso_day >= 6) {
                $weekend = 1;
            }
            
            // Slice data by day
            $slice = false;  
            if ($last_day!=$day) $slice = true;
            if ($time==$end) $slice = true;

            if ($slice) {
                // roll back to last day
                $date->setDate($last_year,$last_month,$last_day);
                $date->setTime(0,0,0);

                // date_str is used in output to identify the day
                $Ymd = $date->format("Y-m-d");

                $daily[$Ymd] = array();
                $daily[$Ymd]['time'] = $date->getTimestamp();

                // 1. Initialise
                $totals = array();
                foreach ($categories as $key) {
                    // these will hold daily totals per band
                    $daily[$Ymd][$key] = array();
                    // these will hold daily totals for all bands
                    $totals[$key] = 0;
                }

                // 2. Breakdown by tariff band
                foreach ($period_allocation as $name=>$breakdown) {
                    // Allocate by key
                    foreach ($categories as $key) {
                        $totals[$key] += $breakdown[$key];
                        $daily[$Ymd][$key][$name] = $this->fixed($breakdown[$key],3);
                    }
                }

                // 3. Calculate totals
                foreach ($totals as $key=>$value) {
                    $daily[$Ymd][$key]['total'] = $this->fixed($totals[$key],3);
                }
                
                $period_allocation = array();
                
                if ($time==$end) break;  
            }
            
            // Get use and generation for this time
            // calculate import
            $use = $use_data[$n];
            if ($use==null) $use = 0;
            
            $gen = $gen_data[$n];
            if ($gen==null) $gen = 0;

            if ($gen>$use) $gen = $use;
            $import = $use - $gen;

            // Get tariff bands for this time
            $bands = $this->get_tariff_bands($tariff_history,$time);
            $band = $this->get_tariff_band($bands,$hour,$weekend);
            if ($band) {
                // initialise period allocation
                if (!isset($period_allocation[$band->name])) {
                    $period_allocation[$band->name] = array();
                    foreach ($categories as $key) {
                        $period_allocation[$band->name][$key] = 0;
                    }
                }

                // add to period allocation, kwh
                $period_allocation[$band->name]['demand'] += $use;
                $period_allocation[$band->name]['generation'] += $gen;
                $period_allocation[$band->name]['import'] += $import;

                // add to period allocation, costs
                $period_allocation[$band->name]['generation_cost'] += $gen*$band->generator*0.01;
                $period_allocation[$band->name]['import_cost'] += $import*$band->import*0.01;
                $period_allocation[$band->name]['cost'] += ($gen*$band->generator*0.01) + ($import*$band->import*0.01);
            }

            $n++; // increment data index
        }
        
        return $daily;
    }
    
    
    public function club_custom_summary($clubid, $start, $end) 
    {
        $daily = $this->club_daily_summary($clubid, $start, $end);
        return $this->custom_summary($daily);
    }
    
    public function user_custom_summary($userid, $start, $end) 
    {
        $daily = $this->user_daily_summary($userid, $start, $end);
        return $this->custom_summary($daily);
    }

    private function custom_summary($daily) 
    {
        $summary = array();
        $days = 0;
        
        foreach ($daily as $key=>$day) {
            
            $keys = array_keys($day);
            array_shift($keys); // remove time field
            
            foreach ($keys as $name) {
                if (!isset($summary[$name])) $summary[$name] = [];
                foreach ($day[$name] as $key=>$val) {
                    if (!isset($summary[$name][$key])) $summary[$name][$key] = 0;
                    $summary[$name][$key] += $val;
                }
            }
            
            $days++;
        }
        
        $summary["days"] = $days;
        
        return $summary; 
    }

    // Returns a list of months for which data is available
    public function get_available_reports($userid) {
        $userid = (int) $userid;

        // Get feedid of consumption feed
        if (!$use_feedid = $this->feed->get_id($userid,"use_hh_est")) {
            return array("success"=>false, "message"=>"Missing consumption feed");
        }
        // Get meta data for consumption feed
        $meta = $this->feed->get_meta($use_feedid);
       
        // Set start time to first day of month
        $d = new DateTime();
        $d->setTimezone(new DateTimeZone("Europe/London"));
        $d->setTimestamp($meta->start_time);
        $d->setDate($d->format("Y"),$d->format("m"),1);
        $d->setTime(0,0,0);
        $time = $d->getTimestamp();
        
        $end = time();

        $available_reports = array();
        while ($time<$end) {
            $available_reports[] = $d->format("Y-m");
            $d->modify('+1 month');
            $time = $d->getTimestamp();
        }
        
        return $available_reports;
    }


    // Get tariff bands for a given time
    public function get_tariff_bands($tariff_history,$time) {
        $bands = array();
        foreach ($tariff_history as $tariff) {
            if ($time>=$tariff->start) {
                $bands = $tariff->bands;
            }
        }
        return $bands;
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

    public function fixed($value, $dp) {
        $formatted = number_format($value, $dp, '.', ''); // Use '.' as decimal point and remove thousands separator
        return (float) $formatted; // Cast the formatted string to a float
    }
}
