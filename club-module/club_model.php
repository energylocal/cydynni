<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Club{

    private $mysqli;
    private $redis;

    public function __construct($mysqli,$redis) {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
    }
}
