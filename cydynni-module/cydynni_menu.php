<?php
    global $session;
    // $userid = (int) $session["userid"];
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

    // $cydynni = new Cydynni($mysqli,$redis);
    // foreach($cydynni->getHouseholdSummaryMonthly($userid) as $key=>$value) {
    //     DateTime::createFromFormat('!m', $value['month']);
    //     $dateObj = DateTime::createFromFormat('!m', $monthNum);
    //     $monthName = $dateObj->format('F');
    //     $menu['sidebar']['cydynni'][] = array(
    //         'text' => sprintf("%s %s", _($dateObj->format('F')), $value['year']),
    //         'href'=> "#".$key
    //     );
    // }
