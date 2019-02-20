<?php

    global $lang;

    $domain = "messages";

    $menu_left[] = array(
        'id'=>"cydynni_menu",
        'name'=>"Dashboard",
        'path'=>"" , 
        'session'=>"write", 
        'order' => 3,
        'icon'=>'icon-th-large icon-white',
        'hideinactive'=>0
    );
    
    $menu_left[] = array(
        'id'=>"cydynni_report_menu",
        'name'=>"Reports",
        'path'=>"cydynni/report" , 
        'session'=>"write", 
        'order' => 3,
        'icon'=>'icon-book icon-white',
        'hideinactive'=>0
    );

