<?php
require "lib/load_emoncms.php";
$result_users = $mysqli->query("SELECT * FROM users ORDER BY id ASC");
while ($row = $result_users->fetch_object()) {
    $feed->load_to_redis($row->id);   
}
