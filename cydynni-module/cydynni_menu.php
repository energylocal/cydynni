<?php

    $apikeystr = "";
    if (isset($_GET['apikey'])) $apikeystr = "?apikey=".$_GET['apikey'];
    if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];
    
    // navbar link
    $menu['tabs'][] = array(
        'icon'=>'cydynni',
        'title'=> dgettext("dashboard_messages","Dashboards"),
        'path'=> "cydynni".$apikeystr,
        'order' => 4,
        'data'=> array('sidebar' => '#sidebar_cydynni')
    );

    $menu['sidebar']['cydynni'][] = array(
        'text' => $session["lang"]=="cy_GB" ? "Dashfwrdd" : "Dashboard",
        'path'=> "cydynni".$apikeystr, 
        'order' => 1
    );
    $menu['sidebar']['cydynni'][] = array(
        'text' => $session["lang"]=="cy_GB" ? "Adroddiad" : "Report",
        'path'=> "cydynni/report".$apikeystr, 
        'order' => 2
    );