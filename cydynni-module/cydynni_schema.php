<?php

$schema['cydynni'] = array(
    'userid' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'mpan' => array('type' => 'varchar(32)', "default"=>""),
    'token' => array('type' => 'varchar(128)'),
    'premisestoken' => array('type' => 'varchar(128)'),
    'welcomedate' => array('type' => 'varchar(32)', "default"=>"not sent"),
    'reportdate' => array('type' => 'varchar(32)', "default"=>"not sent"),
    'clubs_id' => array('type'=>'int(11)')
);
$schema['cydynni_clubs'] = array(
    'name' => array('type' => 'varchar(128)'),
    'generator' => array('type' => 'varchar(128)'),
    'root_token' => array('type' => 'varchar(128)'),
    'api_prefix' => array('type' => 'int(11)'),
    'languages' => array('type' => 'text'),
    'generation_feed' => array('type' => 'int(11)'),
    'consumption_feed' => array('type' => 'int(11)'),
    'color' => array('type' => 'varchar(11)')
);