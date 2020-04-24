<?php

function mergefeeds3($dir,$processitem)
{
    if (!isset($processitem->feedA)) return false;
    if (!isset($processitem->feedB)) return false;
    if (!isset($processitem->feedC)) return false;
    if (!isset($processitem->output)) return false;
    if (!isset($processitem->recalc)) $processitem->recalc = 0;
    
    $feedA = $processitem->feedA;
    $feedB = $processitem->feedB;
    $feedC = $processitem->feedC;
    $output = $processitem->output;
    // --------------------------------------------------
    
    if (!file_exists($dir.$feedA.".meta")) {
        print "input file $feedA.meta does not exist\n";
        return false;
    }

    if (!file_exists($dir.$feedB.".meta")) {
        print "input file $feedB.meta does not exist\n";
        return false;
    }

    if (!file_exists($dir.$feedC.".meta")) {
        print "input file $feedC.meta does not exist\n";
        return false;
    }
    
    if (!file_exists($dir.$output.".meta")) {
        print "output file $output.meta does not exist\n";
        return false;
    }

    $feedA_meta = getmeta($dir,$feedA);
    $feedB_meta = getmeta($dir,$feedB);
    $feedC_meta = getmeta($dir,$feedC);
    
    if ($feedA_meta->interval != $feedB_meta->interval) {
        print "NOTICE: interval of feeds do not match, feedA:$feedA_meta->interval, feedB:$feedB_meta->interval\n";
        die;
    }

    if ($feedA_meta->interval != $feedC_meta->interval) {
        print "NOTICE: interval of feeds do not match, feedA:$feedA_meta->interval, feedB:$feedC_meta->interval\n";
        die;
    }
    
    $out_interval = $feedA_meta->interval;
    
    print "FeedA start_time=$feedA_meta->start_time interval=$feedA_meta->interval\n";
    print "FeedB start_time=$feedB_meta->start_time interval=$feedB_meta->interval\n";
    print "FeedB start_time=$feedC_meta->start_time interval=$feedC_meta->interval\n";
    
    $out_start_time = 20000000000;
    if ($feedA_meta->start_time<$out_start_time) $out_start_time = (int) $feedA_meta->start_time;
    if ($feedB_meta->start_time<$out_start_time) $out_start_time = (int) $feedB_meta->start_time;
    if ($feedC_meta->start_time<$out_start_time) $out_start_time = (int) $feedC_meta->start_time;
    
    $out_start_time = floor($out_start_time / $out_interval) * $out_interval;
    
    $out_meta = new stdClass();
    $out_meta->start_time = $out_start_time;
    $out_meta->interval = $out_interval;
    
    if ($out_start_time==0) return false;
    
    print "OUT start_time=$out_start_time interval=$out_interval\n";
    
    createmeta($dir,$output,$out_meta);
    
    $output_meta = getmeta($dir,$output);

    if (!$feedA_fh = @fopen($dir.$feedA.".dat", 'rb')) {
        echo "ERROR: could not open $dir $feedA.dat\n";
        return false;
    }
    
    if (!$feedB_fh = @fopen($dir.$feedB.".dat", 'rb')) {
        echo "ERROR: could not open $dir $feedB.dat\n";
        return false;
    }
    
    if (!$feedC_fh = @fopen($dir.$feedC.".dat", 'rb')) {
        echo "ERROR: could not open $dir $feedC.dat\n";
        return false;
    }
    
    if (!$output_fh = @fopen($dir.$output.".dat", 'c+')) {
        echo "ERROR: could not open $dir $output.dat\n";
        return false;
    }
    
    // Work out start and end time of merged feeds:
    $feedA_end_time = $feedA_meta->start_time + ($feedA_meta->interval * $feedA_meta->npoints);
    $feedB_end_time = $feedB_meta->start_time + ($feedB_meta->interval * $feedB_meta->npoints);
    $feedC_end_time = $feedC_meta->start_time + ($feedC_meta->interval * $feedC_meta->npoints);
    $start_time = $output_meta->start_time + ($output_meta->npoints * $output_meta->interval);
    $start_time -= $processitem->recalc;
    if ($start_time<$output_meta->start_time) $start_time = $output_meta->start_time;
    
    $out_pos = floor(($start_time-$output_meta->start_time)/$output_meta->interval);
    fseek($output_fh,$out_pos*4);
    
    $end_time = $feedA_end_time;
    if ($feedB_end_time>$end_time) $end_time = $feedB_end_time;
    if ($feedC_end_time>$end_time) $end_time = $feedC_end_time;
    
    $interval = $output_meta->interval;
    
    $buffer = "";
    for ($time=$start_time; $time<$end_time; $time+=$interval) 
    {
        $posA = floor(($time - $feedA_meta->start_time) / $feedA_meta->interval);
        $posB = floor(($time - $feedB_meta->start_time) / $feedB_meta->interval);
        $posC = floor(($time - $feedC_meta->start_time) / $feedC_meta->interval);
    
        $valueA = NAN;
        $valueB = NAN;
        $valueC = NAN;
        
        //if ($posA>=0 && $posA<$feedA_meta->npoints && $time<1572567880) {
        if ($posA>=0 && $posA<$feedA_meta->npoints) {
            fseek($feedA_fh,$posA*4);
            $feedA_tmp = unpack("f",fread($feedA_fh,4));
            $valueA = $feedA_tmp[1];
        }

        if ($posB>=0 && $posB<$feedB_meta->npoints) {
            fseek($feedB_fh,$posB*4);
            $feedB_tmp = unpack("f",fread($feedB_fh,4));
            $valueB = $feedB_tmp[1];
        }
        
        if ($posC>=0 && $posC<$feedC_meta->npoints) {
            fseek($feedC_fh,$posC*4);
            $feedC_tmp = unpack("f",fread($feedC_fh,4));
            $valueC = $feedC_tmp[1];
        }
        
        $outval = NAN;
        if (!is_nan($valueA)) $outval = $valueA;
        if (!is_nan($valueB)) $outval = $valueB;
        if (!is_nan($valueC)) $outval = $valueC;
        // if (!is_nan($valueA) && !is_nan($valueB)) $outval = ($valueB+$valueA)*0.5;
        
        $buffer .= pack("f",$outval*1.0);
    }
        
    fwrite($output_fh,$buffer);
    
    $byteswritten = strlen($buffer);
    print "bytes written: ".$byteswritten."\n";
    fclose($output_fh);
    fclose($feedA_fh);
    fclose($feedB_fh);
    fclose($feedC_fh);
    
    if ($byteswritten>0) {
        print "last time value: ".$time." ".$outval."\n";
        // updatetimevalue($output,$time,$outval);
    }
    return true;
}
