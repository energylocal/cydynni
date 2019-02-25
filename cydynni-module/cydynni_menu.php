<?php

    global $lang, $session;

    $domain = "messages";
    
    $apikeystr = "";
    if (isset($_GET['apikey'])) $apikeystr = "?apikey=".$_GET['apikey'];

    if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];
    
    $name = "Dashboard"; if ($session["lang"]=="cy_GB") $name = "Dashfwrdd";
    $menu_left[] = array(
        'id'=>"cydynni_menu",
        'name'=>$name,
        'path'=>"".$apikeystr , 
        'session'=>"read", 
        'order' => 3,
        'icon'=>'icon-th-large icon-white',
        'hideinactive'=>0
    );

    $name = "Report"; if ($session["lang"]=="cy_GB") $name = "Adroddiad";
    $menu_left[] = array(
        'id'=>"cydynni_report_menu",
        'name'=>$name,
        'path'=>"cydynni/report".$apikeystr , 
        'session'=>"read", 
        'order' => 4,
        'icon'=>'icon-book icon-white',
        'hideinactive'=>0
    );

