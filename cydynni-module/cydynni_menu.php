<?php

global $session;

$_t['Dashboard'] = $session["lang"]=="cy_GB" ? "Dashfwrdd" : "Dashboard";
$_t['Report']    = $session["lang"]=="cy_GB" ? "Adroddiad" : "Report";

$apikeystr = isset($_GET['apikey']) ? "apikey=".$_GET['apikey'] : "";
if (isset($_GET['lang'])) $apikeystr .= "&lang=".$_GET['lang'];

$menu['tabs'][] = array(
    'icon'=>'cydynni',
    'text'=> "Cydynni",
    'path'=> 'app/view',
    'order'=> 6,
    'data'=> array('sidebar' => '#sidebar_cydynni')
);

$menu['sidebar']['cydynni'][] = array(
    'id'=>"cydynni_menu",
    'text' => $_t["Dashboard"],
    'path' => "".$apikeystr
);
$menu['sidebar']['cydynni'][] = array(
    'id'=>"cydynni_report_menu",
    'text' => $_t["Report"],
    'path' => "cydynni/report".$apikeystr
);
