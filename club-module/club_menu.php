<?php
    global $session, $redis, $path;
    
    if ($session["admin"]) {
        $menu['sidebar']['emoncms'][] = array(
            'text' => _("Clubs"),
            'path' => 'club/admin',
            'icon' => 'format_list_bulleted',
            'order' => 'z'
        );
    }

    $apikeystr = "";
    if (isset($_GET['apikey'])) $apikeystr = "?apikey=".$_GET['apikey'];
    if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];

    // navbar link
    $menu['tabs'][] = array(
        'icon'=>'cydynni',
        'text'=> $session["lang"]=="cy_GB" ? "Dangosfwrdd" : "Dashboard",
        'path'=> "club".$apikeystr,
        'order' => 2,
        'data'=> array('is-link' => true)
    );

    $menu['tabs'][] = array(
        'text' => $session["lang"]=="cy_GB" ? "Adroddiad" : "Report",
        'path'=> "club/report".$apikeystr,
        'order' => 3,
        'icon'=>'folder-plus',
        'data' => array(
            'sidebar' => '#sidebar_reports'
        )
    );

    if ($session["read"]) {
        $userid = (int) $session["userid"];
        
        $end = floor(time()/1800)*1800;
        $start = $end-3600*24*365;
        
        $d = new DateTime();
        $d->setTimezone(new DateTimeZone("Europe/London"));
        $d->setTimestamp($start);
        $d->setDate($d->format("Y"),$d->format("m"),1);
        $d->setTime(0,0,0);
        $time = $d->getTimestamp();
        
        $months = array("January","February","March","April","May","June","July","August","September","October","November","December");

        $menu['sidebar']['reports'][] = array(
            'path' => 'club/report',
            'li_class' => 'd-none'
        );
        
        while ($time<$end) {
            $name = $months[$d->format("m")-1];
            $year = $d->format("Y");
            $index = $d->format("Y-m");

            $menu['sidebar']['reports'][] = array(
                'href' => $path.'club/report'.$apikeystr.'#'.$index,
                'active' => $path.'club/report'.$apikeystr.'#'.$index,
                'text' => sprintf("%s %s",$name,$year),
                'order' => $index
            );
            
            $d->modify('+1 month');
            $time = $d->getTimestamp();
        }
    }
