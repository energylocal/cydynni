<?php
    global $session, $redis, $path;

    $apikeystr = "";
    if (isset($_GET['apikey'])) $apikeystr = "?apikey=".$_GET['apikey'];
    if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];

    // navbar link
    $menu['tabs'][] = array(
        'icon'=>'cydynni',
        'text'=> $session["lang"]=="cy_GB" ? "Dangosfwrdd" : "Dashboard",
        'path'=> "cydynni".$apikeystr,
        'order' => 2,
        'data'=> array('is-link' => true)
    );

    $menu['tabs'][] = array(
        'text' => $session["lang"]=="cy_GB" ? "Adroddiad" : "Report",
        'path'=> "cydynni/report".$apikeystr,
        'order' => 3,
        'icon'=>'folder-plus',
        'data' => array(
            'sidebar' => '#sidebar_reports'
        )
    );

    if ($session["read"]) {
        $userid = (int) $session["userid"];
        $months = array("January","February","March","April","May","June","July","August","September","October","November","December");
        if ($result = $redis->get("household:summary:monthly:$userid")) {
            $result = json_decode($result,true);

            $menu['sidebar']['reports'][] = array(
                'path' => 'cydynni/report',
                'li_class' => 'd-none'
            );

            foreach ($result as $index=>$item) {
                $name = $months[$item['month'] - 1];
                $year = $item['year'];

                $menu['sidebar']['reports'][] = array(
                    'href' => $path.'cydynni/report'.$apikeystr.'#'.$index,
                    'active' => $path.'cydynni/report'.$apikeystr.'#'.$index,
                    'text' => sprintf("%s %s",$name,$year),
                    'order' => $index
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
