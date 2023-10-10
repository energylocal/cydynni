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
    
    /**
     * build table of tariff prices per production source
     */
    public function getTariffsTable($tariffs) {
        global $lang;
        $tariffs = json_decode(json_encode($tariffs));
        // add properties and format strings...
        foreach($tariffs as $t) {
            // calculate how much smaller "hydro" is from "import"
            // $t->diff = sprintf("(%d%%)", round(100/($t->import / $t->generator)));
            $start = intval(date('G', strtotime($t->start)));
            $end = intval(date('G', strtotime($t->end)));
            $now = intval(date('G'));
            $t->isCurrent = $now >= $start && $now < $end;
            // add 12hr times with am/pm
            $t->start = date('g', strtotime($t->start)) . ($t->start < 12 ? translate('am', $lang): translate('pm', $lang));
            $t->end = date('g', strtotime($t->end)) . ($t->end < 12 ? translate('am', $lang): translate('pm', $lang));
            // add css class names to style the title column
            $t->css = 'text-' . $t->name;
            $t->rowClass = $t->isCurrent ? ' class="current"': '';
        }
        return $tariffs;
    }
    
    public function get_available_reports($feed,$feedid) {
        $available_reports = array();
        if (!$feedid) return false;
        
        $meta = $feed->get_meta($feedid);
       
        $d = new DateTime();
        $d->setTimezone(new DateTimeZone("Europe/London"));
        $d->setTimestamp($meta->start_time);
        $d->setDate($d->format("Y"),$d->format("m"),1);
        $d->setTime(0,0,0);
        $time = $d->getTimestamp();
        
        $end = time();
        
        while ($time<$end) {
            $available_reports[] = $d->format("Y-m");
            $d->modify('+1 month');
            $time = $d->getTimestamp();
        }
        
        return $available_reports;
    }
}
