<?php

function mysql_store_get($mysqli,$key) {
    $stmt = $mysqli->prepare("SELECT value FROM store WHERE `key`=?");
    $stmt->bind_param("s", $key);
    $stmt->execute(); $stmt->store_result();
    if ($stmt->num_rows==1) {
        $stmt->bind_result($value);
        $stmt->fetch();
        return $value;
    }
    return 0;
}

function mysql_store_set($mysqli,$key,$value) {

    $stmt = $mysqli->prepare("SELECT value FROM store WHERE `key`=?");
    $stmt->bind_param("s", $key);
    $stmt->execute(); $stmt->store_result();
    
    if ($stmt->num_rows==0) {
        $stmt = $mysqli->prepare("INSERT INTO store (`key`,`value`) VALUES (?,?)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    } else {
        $stmt = $mysqli->prepare("UPDATE store SET value=? WHERE `key`=?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
}

