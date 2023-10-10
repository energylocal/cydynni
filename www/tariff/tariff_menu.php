<?php
global $session;
if ($session["admin"]) {
    $menu['setup']['l2']['tariff'] = array(
        'name' => _("EnergyLocal Tariffs"),
        'href' => 'tariff/list?clubid=1',
        'icon' => 'tasks',
        'order' => 15         
    );
}
