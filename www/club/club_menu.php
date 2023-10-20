<?php
global $session, $redis, $path, $club, $available_clubs_menu, $club_settings;



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

if (!$session["read"]) {
    $menu["cydynni"]['l2'] = array();
    
    foreach ($available_clubs_menu as $i=>$club_name) {
        $menu["cydynni"]['l2'][$club_name] = array(
            "name"=>$club_settings[$club_name]["name"],
            "href"=>$club_name, 
            "icon"=>"star", 
            "order"=>$i
        );
    }
}

if ($session["admin"]) {
    $menu['setup']['l2']['club'] = array(
        'name' => _("EnergyLocal Clubs"),
        'href' => 'club/list',
        'icon' => 'tasks',
        'order' => 14         
    );
}
