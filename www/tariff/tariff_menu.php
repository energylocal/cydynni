<?php
global $session;
if ($session["admin"]) {
    $menu['setup']['l2']['tariff'] = array(
        'name' => _("EnergyLocal Tariffs"),
        'href' => 'tariff/list',
        'icon' => 'tasks',
        'order' => 15         
    );
}
