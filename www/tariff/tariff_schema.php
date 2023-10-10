<?php

$schema['tariffs'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'clubid' => array('type' => 'int(11)'),
    'name' => array('type' => 'varchar(32)', "default"=>""),
    'created' => array('type' => 'int(10)'),
    'first_assigned' => array('type' => 'int(10)'),
    'last_assigned' => array('type' => 'int(10)')
);

$schema['tariff_periods'] = array(
    'tariffid' => array('type' => 'int(11)'),
    'index' => array('type' => 'int(11)'),
    'name' => array('type' => 'varchar(16)'),
    'weekend' => array('type' => 'tinyint(1)'),
    'start' => array('type' => 'int(11)'),
    'generator' => array('type' => 'float'),
    'import' => array('type' => 'float'),
    'color' => array('type' => 'varchar(32)')
);

// schema that links users to tariffs
$schema['user_tariffs'] = array(
    'userid' => array('type' => 'int(11)'),
    'tariffid' => array('type' => 'int(11)'),
    'start' => array('type' => 'int(10)')
);
