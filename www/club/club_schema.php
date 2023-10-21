<?php

$schema['club'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>false, 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'name' => array('type' => 'text'),
    'created' => array('type' => 'int(10)'),
    'userid' => array('type' => 'int(11)'),

    // Review the location of these settings

    // share bool
    'share' => array('type' => 'tinyint(1)'),
    // generator varchar: hydro
    'generator' => array('type' => 'varchar(16)'),
    // generator color varchar: #000000
    'generator_color' => array('type' => 'varchar(8)'),
    // export color varchar: #000000
    'export_color' => array('type' => 'varchar(8)'),
    // languages varchar: en,cy
    'languages' => array('type' => 'varchar(16)'),
    // generation feed int 
    'generation_feed' => array('type' => 'int(11)'),
    // consumption feed int
    'consumption_feed' => array('type' => 'int(11)'),
    // generation forecast feed int
    'generation_forecast_feed' => array('type' => 'int(11)'),
    // consumption forecast feed int
    'consumption_forecast_feed' => array('type' => 'int(11)'),
    // unitprice comparion float
    'unitprice_comparison' => array('type' => 'float'),
    // generation scale by
    'gen_scale' => array('type' => 'float'),
    // generation limit
    'gen_limit' => array('type' => 'float'),
    // skip users varchar csv
    'skip_users' => array('type' => 'varchar(128)')
);
