<?php
    global $session, $redis;

    $apikeystr = "";
    if (isset($_GET['apikey'])) $apikeystr = "?apikey=".$_GET['apikey'];
    if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];
    
    // navbar link
    $menu['tabs'][] = array(
        'icon'=>'cydynni',
        'text'=> dgettext("dashboard_messages","Dashboard"),
        'path'=> "cydynni".$apikeystr,
        'order' => 4,
        'data'=> array('sidebar' => '#sidebar_cydynni')
    );

    $menu['tabs'][] = array(
        'text' => $session["lang"]=="cy_GB" ? "Adroddiad" : "Report",
        'path'=> "cydynni/report".$apikeystr,
        'order' => 2,
        'icon'=>'folder-plus',
        'data' => array(
            'is-link' => false,
            'sidebar' => '#sidebar_reports',
        )
    );
    /*
    $menu['tabs'][] = array(
        'icon'=>'cydynni',
        'text'=> dgettext("dashboard_messages","Dashboard"),
        'path'=> "cydynni".$apikeystr,
        'order' => 4,
        'data'=> array('sidebar' => '#sidebar_cydynni')
    );

    $menu['sidebar']['cydynni'][] = array(
        'text' => $session["lang"]=="cy_GB" ? "Dashfwrdd" : "Dashboard",
        'path'=> "cydynni".$apikeystr, 
        'order' => 1,
        'icon'=>'dashboard'
    );
    $menu['sidebar']['cydynni'][] = array(
        'text' => $session["lang"]=="cy_GB" ? "Adroddiad" : "Report",
        'path'=> "cydynni/report".$apikeystr, 
        'order' => 2,
        'icon'=>'folder-plus'
    );*/
    
    if ($session["read"]) {
        $userid = (int) $session["userid"];
        if ($result = $redis->get("household:summary:monthly:$userid")) {
            $result = json_decode($result,true);
            
            $months = array("January","February","March","April","May","June","July","August","September","October","November","December");
            
            foreach ($result as $item) {
                $name = $months[$item['month'] - 1];
                $year = $item['year'];

                $menu['sidebar']['reports'][] = array(
                    'path' => 'cydynni/report'.$apikeystr,
                    'text' => sprintf("%s %s",$name,$year)
                );
            }
        }
    }

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
