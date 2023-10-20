<?php

$schema['club'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'name' => array('type' => 'text'),
    'created' => array('type' => 'int(10)'),
    'userid' => array('type' => 'int(11)')
);