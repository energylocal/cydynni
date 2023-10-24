<?php
global $session, $redis, $mysqli, $path, $club;

$result = $mysqli->query("SELECT `key`,`name` FROM club WHERE menu=1");
while($row = $result->fetch_array()) {
    $available_clubs_menu[$row['key']] = $row['name'];
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

if (!$session["read"]) {
    $menu["cydynni"]['l2'] = array();
    
    foreach ($available_clubs_menu as $key=>$name) {
        $menu["cydynni"]['l2'][$name] = array(
            "name"=>$name,
            "href"=>$key, 
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
