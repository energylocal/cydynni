<?php

function account_controller() {

    global $session, $route, $mysqli, $user;
    
    require_once "Modules/account/account_model.php";
    $account = new Account($mysqli, $user);

    if ($route->action == 'list' && $session['admin']) {
        if ($route->format == 'json') {
            $clubid = get('clubid',false);
            return $account->list($clubid);
        } else {
            unset($session["token"]);
            $clubid = get('clubid',false,0);
            
            require "Modules/club/club_model.php";
            $club_class = new Club($mysqli, $user);
            $clubs = $club_class->list_assoc();
            
            return view("Modules/account/account_list_view.php",array('session'=>$session, 'clubid'=>$clubid, 'clubs'=>$clubs));
        }
    }
    
    if ($route->action == 'add' && $session['admin']) {
        $route->format = 'json';
        $u = json_decode(post('user'));
        return $account->add($u);
    }
    
    if ($route->action == 'update' && $session['admin']) {
        $route->format = 'json';
        $userid = get('userid');
        $changed = json_decode(post("data"));
        return $account->update($userid,$changed);
        
    }

    return false;
}
