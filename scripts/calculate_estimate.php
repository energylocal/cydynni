<?php

print "---------------------------------------------------------------------\n";
print "CALCULATE ESTIMATE\n";
print "---------------------------------------------------------------------\n";

require "config.php";
require "lib/load_emoncms.php";
$dir = "/var/lib/phpfina/";

$user_select = "";
if ($userid) $user_select = "WHERE userid=$userid ";
$result_users = $mysqli->query("SELECT * FROM cydynni ".$user_select."ORDER BY userid ASC");
while ($row = $result_users->fetch_object()) 
{
    $userid = $row->userid;
    $clubid = $row->clubs_id;
    
    print $userid;

    if ($source = $feed->get_id($userid,"use_hh"))
    {
        if (!$output = $feed->get_id($userid,"use_hh_est")) {
            $result = $feed->create($userid,"user","use_hh_est",5,json_decode('{"interval":1800}'));
            if (!$result['success']) { echo json_encode($result)."\n"; die; }
            $output = $result['feedid'];
        }

        // copy("/var/lib/phpfina/$source.dat","/var/lib/phpfina/$output.dat");
        if (($recalc_club && $clubid==$recalc_club) || $recalc_all) $feed->clear($output);
        copy("/var/lib/phpfina/$source.meta","/var/lib/phpfina/$output.meta");
        
        //die;
        // --------------------------------------------------
        // CALC ESTIMATE
        // --------------------------------------------------

        if (!$source_meta = getmeta($dir,$source)) {
            print "input file $source.meta does not exist\n";
            // return false;
        }

        if (!$output_meta = getmeta($dir,$output)) {
            print "output file $output.meta does not exist\n";
            // return false;
        }

        if ($source_meta->interval != $output_meta->interval) {
            print "NOTICE: interval of feeds do not match, source:$source_meta->interval, output:$output_meta->interval\n";
            // return false;
        }

        if (!$source_fh = @fopen($dir.$source.".dat", 'rb')) {
            echo "ERROR: could not open $dir $source.dat\n";
            return false;
        }
        
        if (!$output_fh = @fopen($dir.$output.".dat", 'c+')) {
            echo "ERROR: could not open $dir $output.dat\n";
            return false;
        }

        $o_end_time = $output_meta->start_time + ($output_meta->npoints * $output_meta->interval);

        $start_time = $source_meta->start_time;
        $start_time = $o_end_time - $recalc_period;
        if ($start_time<$source_meta->start_time) $start_time=$source_meta->start_time;

        // $start_time = 1585474200;
        $now = time();
        $end_time = floor($now/1800)*1800;
        
        $source_duration = $source_meta->interval * $source_meta->npoints;
        if ($source_duration>(3600*24*14)) $average_over = 7; else $average_over = 1;
        
        $i=0;
        if ($source_meta->npoints>0) {
            for ($time=$start_time; $time<$end_time; $time+=1800) {

                $pos = floor(($time - $source_meta->start_time) / $source_meta->interval);
                
                $actual = NAN;
                $estimate = NAN;
                $estimate_count = 0;
                
                if ($pos<$source_meta->npoints) {
                    fseek($source_fh,$pos*4);
                    $source_tmp = unpack("f",fread($source_fh,4));
                    $actual = $source_tmp[1];
                    
                    $insert_pos = floor(($time-$output_meta->start_time)/$output_meta->interval);
                    fseek($output_fh,$insert_pos*4);
                    fwrite($output_fh,pack("f",$actual));
                }
                
                if (is_nan($actual)) {
                    $estimate_sum = 0;
                    $estimate_count = 0;
                    for ($z=1; $z<=50; $z++) {
                        $pos = floor(($time - $source_meta->start_time - 3600*24*$average_over*$z) / $source_meta->interval);
                        if ($pos<0) break;
                        if ($pos>0 && $pos<$source_meta->npoints) {
                            fseek($source_fh,$pos*4);
                            $source_tmp = unpack("f",fread($source_fh,4));
                            if (!is_nan($source_tmp[1])) {
                                $estimate_sum += $source_tmp[1];
                                $estimate_count++;
                                if ($estimate_count==8) break;
                            }
                        }
                    }
                    
                    if ($estimate_count<8) {
                        for ($z=1; $z<=50; $z++) {
                            $pos = floor(($time - $source_meta->start_time + 3600*24*$average_over*$z) / $source_meta->interval);
                            if ($pos>$source_meta->npoints-1) break;
                            if ($pos>0 && $pos<$source_meta->npoints) {
                                fseek($source_fh,$pos*4);
                                $source_tmp = unpack("f",fread($source_fh,4));
                                if (!is_nan($source_tmp[1])) {
                                    $estimate_sum += $source_tmp[1];
                                    $estimate_count++;
                                    if ($estimate_count==8) break;
                                }
                            }
                        }
                    }
                    
                    if ($estimate_count>0) {
                        $estimate = $estimate_sum / $estimate_count;
                        $insert_pos = floor(($time-$output_meta->start_time)/$output_meta->interval);
                        fseek($output_fh,$insert_pos*4);
                        fwrite($output_fh,pack("f",$estimate));
                    }
                }
                
                // print $actual." ".number_format($estimate,3)." ".$estimate_count."\n";
            }
        } else {
            print " no data in source feed";
        }
        
        if ($output) {
            $redis->hdel("feed:$output",'time');
            $timevalue = $feed->get_timevalue($output);
        }
    }
    print "\n";
}

function getmeta($dir,$id) 
{
    if (!file_exists($dir.$id.".meta")) {
        print "input file $id.meta does not exist\n";
        return false;
    }
    
    $meta = new stdClass();
    $metafile = fopen($dir.$id.".meta", 'rb');
    fseek($metafile,8);
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->interval = $tmp[1];
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->start_time = $tmp[1];
    fclose($metafile);
    
    clearstatcache($dir.$id.".dat");
    $npoints = floor(filesize($dir.$id.".dat") / 4.0);
    $meta->npoints = $npoints;
    
    return $meta;
}
