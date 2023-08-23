<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class Tariff{

    private $mysqli;
    private $redis;
    private $club_settings;

    private $current_timestamp = false;
    private $current_tariff = false;
    private $current_tariff_period = false;

    public function __construct($mysqli, $redis, $club_settings) {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
        $this->club_settings = $club_settings;
    }

    /**
     * Get the tariff that was active at the given timestamp
     * 
     * @param string $clubname
     * @param int $timestamp
     * @return array|false
    */
    public function get_club_tariff($clubname, $timestamp=false) {
        if (!$timestamp) $timestamp = time();
        $this->current_timestamp = $timestamp;

        // get tariff history
        $tariff_history = $this->club_settings[$clubname]['tariff_history'];

        // order tariff history by start date (descending)
        usort($tariff_history, function($a, $b) {
            return $b['start'] - $a['start'];
        });

        // find the tariff that was active at the given timestamp
        // only use start date as end date is not always set
        foreach ($tariff_history as $tariff) {
            if ($timestamp >= $tariff['start']) {
                $this->current_tariff = $tariff;
                return $tariff["tariffs"];
            }
        }
        
        return false;
    }

    /**
    * Get the tariff period that was active at the given timestamp
    * 
    * @param string $clubname
    * @param int $timestamp
    * @return array|false
    */
    public function get_club_tariff_period($clubname, $timestamp=false) {
        if (!$timestamp) $timestamp = time();

        // get tariff from history
        $tariff = $this->get_club_tariff($clubname, $timestamp);

        // find the tariff period that was active at the given timestamp
        $this_hh = floor($timestamp/1800)*1800;
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone("Europe/London"));
        $date->setTimestamp($this_hh);
        $hour = $date->format("H");

        foreach ($tariff as $period) {
            $start = explode(":",$period["start"])[0];
            $end = explode(":",$period["end"])[0];
            
            // check if start and end are on the same day
            if ($start<$end) {
                if ($hour>=$start && $hour<$end) {
                    $this->current_tariff_period = $period;
                    return $period;
                }
            // overlap midnight
            } else {
                // check if hour is between start and midnight
                // or between midnight and end
                if ($hour>=$start || $hour<$end) {
                    $this->current_tariff_period = $period;
                    return $period;
                }
            }
        }
        return false;
    }

    // Calculate unit price 
    public function get_unit_price($consumption, $generation) {

        // calculate self consumption and import
        $import = 0.0;
        if ($generation<=$consumption) $import = $consumption - $generation;
        $self_consumption = $consumption - $import;

        // calculate unit price
        $unit_price = 0.0;
        $generation_cost = $self_consumption * $this->current_tariff_period['generator'];
        $import_cost = $import * $this->current_tariff_period['import'];
        if ($consumption>0) {
            $unit_price = number_format(($import_cost + $generation_cost) / $consumption,2)*1;
        }
        return $unit_price;
    }

    // find min and max import and generator prices
    function get_min_max_prices() {

        $min_import = 1000;
        $max_import = 0;

        $min_generator = 1000;
        $max_generator = 0;

        foreach ($this->current_tariff['tariffs'] as $period) {
            if ($period['import'] < $min_import) $min_import = $period['import'];
            if ($period['import'] > $max_import) $max_import = $period['import'];

            if ($period['generator'] < $min_generator) $min_generator = $period['generator'];
            if ($period['generator'] > $max_generator) $max_generator = $period['generator'];
        }

        return array(
            "min_import" => $min_import,
            "max_import" => $max_import,
            "min_generator" => $min_generator,
            "max_generator" => $max_generator,
            "min" => min($min_import,$min_generator),
            "max" => max($max_import,$max_generator)
        );
    }

    // get green, amber, red status based on unit price in range of min and max
    function get_status($unit_price) {
        $range = $this->get_min_max_prices();

        // split range into 3 equal parts
        $step = ($range['max'] - $range['min']) / 3;

        // calculate green, amber, red thresholds
        $green = $range['min'] + $step;
        $amber = $range['min'] + $step*2;
        $red = $range['max'];

        // calculate status
        if ($unit_price<$green) return "green";
        if ($unit_price<$amber) return "amber";
        return "red";
    }
}