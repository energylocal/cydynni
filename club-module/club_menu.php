<?php
    global $session, $redis, $path, $club;
    
    if ($session["admin"]) {
        $menu["setup"]["l2"]['clubs'] = array("name"=>"Clubs","href"=>"club/admin", "order"=>12, "icon"=>"format_list_bulleted");
    }

    $apikeystr = "";
    if (isset($_GET['apikey'])) $apikeystr = "?apikey=".$_GET['apikey'];
    if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];

    // Level 1 top bar
    $menu["cydynni"] = array(
        "name"=>$session["lang"]=="cy_GB" ? "Dangosfwrdd" : "Dashboard", 
        "order"=>2, 
        "icon"=>"cydynni", 
        'href'=> "club".$apikeystr
    );
