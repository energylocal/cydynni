<?php

$schema['cydynni'] = array(
    'userid' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'mpan' => array('type' => 'varchar(32)', "default"=>""),
    'token' => array('type' => 'varchar(128)'),
    'welcomedate' => array('type' => 'varchar(32)', "default"=>"not sent"),
    'reportdate' => array('type' => 'varchar(32)', "default"=>"not sent")
);
