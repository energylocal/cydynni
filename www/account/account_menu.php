<?php
global $session;
if ($session["admin"]) {
    $menu['setup']['l2']['energylocal'] = array(
            'name' => _("EnergyLocal Accounts"),
            'href' => 'account/list',
            'icon' => 'tasks',
            'order' => 15         
        );
}
