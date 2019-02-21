<?php

    global $lang;

    $domain = "messages";
    
    $apikeystr = "";
    if (isset($_GET['apikey'])) $apikeystr = "?apikey=".$_GET['apikey'];

    if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];
    
    $menu_left[] = array(
        'id'=>"cydynni_menu",
        'name'=>"Dashboard",
        'path'=>"".$apikeystr , 
        'session'=>"read", 
        'order' => 3,
        'icon'=>'icon-th-large icon-white',
        'hideinactive'=>0
    );
    
    $menu_left[] = array(
        'id'=>"cydynni_report_menu",
        'name'=>"Reports",
        'path'=>"cydynni/report".$apikeystr , 
        'session'=>"read", 
        'order' => 4,
        'icon'=>'icon-book icon-white',
        'hideinactive'=>0
    );

