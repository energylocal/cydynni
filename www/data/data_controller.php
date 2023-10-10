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

// The data controller is used to access processed data for users and clubs
// including daily summaries, monthly summaries and custom periods
// club generation and aggregated consumption data is also available
function data_controller()
{
    global $mysqli, $redis, $session, $route, $settings;

    require_once "Modules/club/club_model.php";
    $club = new Club($mysqli, false);

    require_once "Modules/tariff/tariff_model.php";
    $tariff = new Tariff($mysqli);
    
    // Feed model is used to access feed meta data
    // Enough for 1 year of half hourly data
    require "Modules/feed/feed_model.php";
    $settings['feed']['max_datapoints'] = 20000;
    $feed = new Feed($mysqli,$redis,$settings['feed']);

    require "Modules/data/account_data_model.php";
    $account_data = new AccountData($feed, $club, $tariff);

    // Daily consumption, time of use and use of generation data for a user
    // returns multiple days between start and end
    // url: /data/daily?userid=2&start=1695164400&end=1695942000
    if ($route->action == 'daily') {
        $route->format = "json";

        if (!isset($_GET['start']) || !isset($_GET['end'])) {
            // get midnight of today
            // using datetime
            $date = new DateTime();
            $date->setTime(0,0,0);
            $end = $date->getTimestamp();
    
            // get midnight of 7 days ago
            $date->modify('-7 days');
            $start = $date->getTimestamp();        
        } else {
            $start = get('start',true);
            $end = get('end',true);
        }

        if (isset($_GET['userid'])) {
            $userid = get('userid',true);
            return $account_data->daily_summary($userid,$start,$end, 'user');
        } elseif (isset($_GET['clubid'])) {
            $clubid = get('clubid',true);
            return $account_data->daily_summary($clubid,$start,$end, 'club');
        } else {
            return array("success"=>false, "message"=>"Missing userid or clubid");
        }
    }

    // Monthly consumption, time of use and use of generation data for a user
    // returns multiple months between start and end
    if ($route->action == 'monthly') {    
        
    }

    // Custom consumption, time of use and use of generation data for a user
    // returns summary results for a custom period
    if ($route->action == 'summary') {    
        $route->format = "json";

        $start = get('start',true);
        $end = get('end',true);

        if (isset($_GET['userid'])) {
            $userid = get('userid',true);
            return $account_data->custom_summary($userid,$start,$end, 'user');

        } elseif (isset($_GET['clubid'])) {
            $clubid = get('clubid',true);
            return $account_data->custom_summary($clubid,$start,$end, 'club');
        }
    }

    // Return list of reports available
    if ($route->action == 'available_reports') {
        $route->format = "json";
        $userid = get('userid',true);
        return $account_data->get_available_reports($userid);
    }

    return array('content'=>false);
}