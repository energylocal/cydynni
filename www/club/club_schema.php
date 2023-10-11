<?php

$schema['cydynni'] = array(
    'userid' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'mpan' => array('type' => 'varchar(32)', "default"=>""),
    'welcomedate' => array('type' => 'varchar(32)', "default"=>"not sent"),
    'reportdate' => array('type' => 'varchar(32)', "default"=>"not sent"),
    'clubs_id' => array('type'=>'int(11)'),
    'cad_serial' => array('type' => 'varchar(11)', "default"=>""),
    'meter_serial' => array('type' => 'varchar(11)', "default"=>""),
    'octopus_apikey' => array('type' => 'varchar(32)', "default"=>"")
);
$schema['cydynni_clubs'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),    
    'name' => array('type' => 'varchar(128)'),
    'generator' => array('type' => 'varchar(128)'),
    'api_prefix' => array('type' => 'int(11)'),
    'languages' => array('type' => 'text'),
    'generation_feed' => array('type' => 'int(11)'),
    'consumption_feed' => array('type' => 'int(11)'),
    'color' => array('type' => 'varchar(11)'),
    'slug'=> array('type'=>'varchar(24)')
);

$schema['energylocal_clubs'] = array(
    'id' => array('type' => 'int(11)'),
    'name' => array('type' => 'varchar(32)'),
    'settings' => array('type' => 'text')
);
