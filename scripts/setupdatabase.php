<?php
if (php_sapi_name()!="cli") { echo "Please run from command line..."; die; }

require "../settings.php";
require "../lib/dbschemasetup.php";
$mysqli = @new mysqli($mysql['server'],$mysql['username'],$mysql['password'],$mysql['database']);

// -----------------------------------
// Uncomment to delete database
// -----------------------------------
// $mysqli->query("DROP TABLE users");
// -----------------------------------

$schema = array();
$schema['users'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'email' => array('type' => 'varchar(30)'),
    'dbhash' => array('type' => 'varchar(64)'),
    'salt' => array('type' => 'varchar(32)'),
    'admin' => array('type' => 'int(11)', 'Null'=>'NO'),
    'apikey' => array('type' => 'varchar(128)'),
    'feedid' => array('type' => 'int(11)', 'Null'=>'NO')
);

print "----------------------------------\n";
print "Running database setup/update tool\n";
print "----------------------------------\n";
print "Result: ".json_encode(db_schema_setup($mysqli,$schema,true))."\n";


// ----------------------------------------
// Create admin user
// ----------------------------------------
// require("../user_model.php");
// $user = new User($mysqli);
// print $user->register("user@email.com","admin","apikey",0);
