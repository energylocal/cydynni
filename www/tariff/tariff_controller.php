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

function tariff_controller()
{
    global $mysqli, $redis, $user, $path, $session, $route , $settings;

    // require_once "Modules/club/club_model.php";
    // $club = new Club($mysqli,$redis,$user);

    require_once "Modules/tariff/tariff_model.php";
    $tariff = new Tariff($mysqli);

    // ----- Tariffs ------

    // List tariffs
    // /tariff/list.json?clubid=1 (returns json list of tariffs) (PUBLIC)
    // /tariff/list?clubid=1 (returns html view of tariffs)
    if ($route->action == 'list') {
        if ($route->format == "json") {
            $clubid =(int) get('clubid',true);
            return $tariff->list($clubid);

        } else if ($session['admin']) {
            $clubid = get('clubid',false);
            /*
            if (!$club_info = $club->get($clubid)) {
                return "Club not found";
            }*/

            require "Modules/club/club_model.php";
            $club_class = new Club($mysqli, $user);
            $clubs = $club_class->list_assoc();

            return view("Modules/tariff/tariffs_view.php", array(
                "clubid"=>$clubid,
                "clubs"=>$clubs
            ));
        }
    }

    // Add a new tariff
    // /tariff/create, POST BODY club=1&name=MyTariff, (returns json success or fail)
    if ($route->action == 'create' && $session['admin']) {
        $route->format = "json";
        return $tariff->create(
            post('club',true),
            post('name',true)
        );
    }

    if ($route->action == 'update' && $session['admin']) {
        $route->format = "json";
        // TODO handle errors
        $tariffid = (int) post('tariff_id', true);
        $tariff_name = post('name', true);
        $standing_charge = (float) post('standing_charge', true);
        if (!$tariff->set_tariff_name($tariffid, $tariff_name)) {
          return array("success"=>false,"message"=>"Could not set tariff name");
        }
        if (!$tariff->set_tariff_standing_charge($tariffid, $standing_charge)) {
          return array("success"=>false,"message"=>"Could not set tariff standing charge");
        }
        return array("success"=>true);
    }

    // Create a new tariff based on an existing one
    // /tariff/create, POST BODY tariff_id
    if ($route->action == 'clone' && $session['admin']) {
        $route->format = "json";
        return $tariff->clone(
            post('tariff_id',true),
        );
    }

    // Assign tariff to all the users in a club
    if ($route->action == 'assign_all_users' && $session['admin']) {
        $route->format = "json";
        $tariffid = (int) post('tariff_id', true);
        $start = (int) post('start', true); // unixtime
        return $tariff->assign_all_user_tariffs($tariffid, $start);
    }

    // Delete tariff
    // /tariff/delete?id=1 (returns json success or fail)
    if ($route->action == 'delete' && $session['admin']) {
        $route->format = "json";
        $id = get('id',true);
        return $tariff->delete($id);
    }

    // ----- Periods ------

    // List tariff periods (PUBLIC)
    // /tariff/periods?id=1 (returns json list of periods)
    if ($route->action == 'periods') {
        $route->format = "json";
        $id = get('id',true);
        return $tariff->list_periods($id);
    }

    if ($route->action == 'tarifftable') {
        $route->format = "json";
        $id = get('id',true);
        $tariffs = $tariff->list_periods($id);
        return $tariff->getTariffsTable($tariffs);
    }

    // Add period
    // /tariff/addperiod, POST BODY tariffid=1&name=MyPeriod&weekend=0&start=0&generator=15&import=20&color=#000 (returns json success or fail)
    if ($route->action == 'addperiod' && $session['admin']) {
        $route->format = "json";
        return $tariff->add_period(
            post('tariffid',true),
            post('name',true),
            post('weekend',true),
            post('start',true),
            post('generator',true),
            post('import',true),
            post('color',true)
        );
    }

    // Delete period
    // /tariff/deleteperiod?tariffid=1&index=0 (returns json success or fail)
    if ($route->action == 'deleteperiod' && $session['admin']) {
        $route->format = "json";
        return $tariff->delete_period(
            get('tariffid',true),
            get('index',true)
        );
    }

    // Save period
    // /tariff/saveperiod, POST BODY tariffid=1&index=0&name=MyPeriod&weekend=0&start=0&generator=15&import=20&color=#000 (returns json success or fail)
    if ($route->action == 'saveperiod' && $session['admin']) {
        $route->format = "json";
        return $tariff->save_period(
            post('tariffid',true),
            post('index',true),
            post('name',true),
            post('weekend',true),
            post('start',true),
            post('generator',true),
            post('import',true),
            post('color',true)
        );
    }

    
    if ($route->action == 'user') {
        // Get users tariff history
        // /tariff/user/history?userid=1 (returns json list of tariff history)
        if ($route->subaction == 'history' && $session['admin']) {
            $route->format = "json";
            return $tariff->get_user_tariff_history(get('userid',true),true);
        }

        // Set users current tariff
        // /tariff/user/set?userid=1&tariffid=1 (returns json success or fail)
        if ($route->subaction == 'set' && $session['admin']) {
            $route->format = "json";
            return $tariff->set_user_tariff(
                get('userid',true),
                get('tariffid',true),
                get('start',true)
            );
        }
    }
    
    return false;
}
