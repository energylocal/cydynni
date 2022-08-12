<?php

function mergefeeds4($dir,$processitem)
{
    if (!isset($processitem->feeds)) return false;
    if (!isset($processitem->output)) return false;
    if (!isset($processitem->recalc)) $processitem->recalc = 0;
    
    $feeds = $processitem->feeds;
    $output = $processitem->output;
    
    $interval = 1800;
    $start_time = 20000000000;
    $end_time = 0;
    // --------------------------------------------------

    if (!file_exists($dir.$output.".meta")) {
        print "output file $output.meta does not exist\n";
        return false;
    }

    $meta = array();
    foreach ($feeds as $feed) {
        if (!file_exists($dir.$feed.".meta")) {
            print "input file $feed.meta does not exist\n";
            return false;
        }
        $meta[$feed] = getmeta($dir,$feed);
        if ($meta[$feed]->interval!=$interval) {
            print "ERROR in merge feeds function, feeds must be half hourly\n";
            die;
        }
        print "feed:$feed start_time=".$meta[$feed]->start_time." interval=".$meta[$feed]->interval."\n";  
        
        if ($meta[$feed]->start_time>0) {
            if ($meta[$feed]->start_time<$start_time) $start_time = (int) $meta[$feed]->start_time;
            if ($meta[$feed]->end_time>$end_time) $end_time = (int) $meta[$feed]->end_time;
        }
    }
    
    $start_time = floor($start_time / $interval) * $interval;
    
    $out_meta = new stdClass();
    $out_meta->start_time = $start_time;
    $out_meta->interval = $interval;
    
    if ($start_time==0) return false;
    
    print "OUT start_time=$start_time interval=$interval\n";
    
    createmeta($dir,$output,$out_meta);
    
    $output_meta = getmeta($dir,$output);

    $fh = array();
    foreach ($feeds as $feed) {
        if (!$fh[$feed] = @fopen($dir.$feed.".dat", 'rb')) {
            echo "ERROR: could not open $dir $feed.dat\n";
            return false;
        }
    }
    
    if (!$output_fh = @fopen($dir.$output.".dat", 'c+')) {
        echo "ERROR: could not open $dir $output.dat\n";
        return false;
    }
    
    // $start_time -= $processitem->recalc;
    if ($start_time<$output_meta->start_time) $start_time = $output_meta->start_time;
    
    $out_pos = floor(($start_time-$output_meta->start_time)/$output_meta->interval);
    fseek($output_fh,$out_pos*4);
    
    $buffer = "";
    for ($time=$start_time; $time<$end_time; $time+=$interval) 
    {
    
        $outval = NAN;
        foreach ($feeds as $feed) {
            $pos = floor(($time - $meta[$feed]->start_time) / $meta[$feed]->interval);
            if ($pos>=0 && $pos<$meta[$feed]->npoints) {
                // fseek($fh[$feed],$pos*4);
                $tmp = unpack("f",fread($fh[$feed],4));
                if (!is_nan($tmp[1])) $outval = $tmp[1];
            }
        }
        
        $buffer .= pack("f",$outval*1.0);
    }
        
    fwrite($output_fh,$buffer);
    
    $byteswritten = strlen($buffer);
    print "bytes written: ".$byteswritten."\n";
    fclose($output_fh);
    
    foreach ($feeds as $feed) {
        fclose($fh[$feed]);
    }
    
    if ($byteswritten>0) {
        print "last time value: ".$time." ".$outval."\n";
        // updatetimevalue($output,$time,$outval);
    }
    return true;
}
