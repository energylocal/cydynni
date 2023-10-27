<?php

/*
// This is not live yet
$schema['club_accounts'] = array(
    'clubid' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>'PRI'),
    'userid' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>'PRI')
);*/

// Old schema but currently in use
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