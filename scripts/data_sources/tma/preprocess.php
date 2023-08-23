<?php
// --------------------------------------------------------------------
// TMA data import tool
// --------------------------------------------------------------------
require "config.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

// $output_dir = "/home/cydynni/TMA";
// $rebuild = false;
$days = 30;

// read --rebuild argument using getopt
$opts = getopt("r", ["rebuild"]);
if (isset($opts['r']) || isset($opts['rebuild'])) {
    $days = 'all';
}

// read --days argument using getopt
$opts = getopt("d:", ["days:"]);
if (isset($opts['d']) || isset($opts['days'])) {
    if (isset($opts['d'])) $days = $opts['d'];
    if (isset($opts['days'])) $days = $opts['days'];
    // days must be an integer
    if (!is_numeric($days)) {
        print "Days must be an integer\n";
        exit(0);
    }
}

print "Days: ".$days."\n";

// --------------------------------------------------------------------

$mpan_errors = array();

// this will hold an associative array of MPANs and their data
$mpan_data = array();

// if rebuild clear processed files
// if ($rebuild) {
    // file_put_contents("$output_dir/processed.log","");
// }

// load processed files (not needed)
// $already_processed = get_processed_files($output_dir);

$files = scandir($ftp_dir);
for ($i=2; $i<count($files); $i++) {
    $filename = $files[$i];

    // check if file extension is .csv
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext != 'csv') continue;

    // check if file has already been processed (not needed)
    // if (isset($already_processed[$filename])) continue;

    // process file
    $mpan_data = process_file($ftp_dir, $filename, $mpan_data);

    // log processed file (not needed)
    // file_put_contents("$output_dir/processed.log",$filename."\n",FILE_APPEND);
}

// sort mpan data by timestamp
foreach ($mpan_data as $mpan=>$data) {
    ksort($mpan_data[$mpan]);
}

// write mpan data to csv files (not needed)
// write_csv_data($output_dir, $mpan_data);

// write mpan data to feeds
write_feed_data($feed, $map, $mpan_data, $days);

// Report MPANs with letters
foreach ($mpan_errors as $mpan=>$value) {
    print "MPAN $mpan contains letters\n";
    // Could send error email here
}

// No longer used
function get_processed_files($output_dir) {
    $processed = array();
    if (file_exists("$output_dir/processed.log")) {
        $tmp = explode("\n",file_get_contents("$output_dir/processed.log"));
        foreach ($tmp as $file) {
            $processed[$file] = 1;
        }
    }
    return $processed;
}

function process_file($ftp_dir, $filename, $mpan_data) {
    global $mpan_errors;

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone("UTC"));
    $date->setTime(0,0,0);

    $content = file_get_contents($ftp_dir."/".$filename);
    $lines = explode("\n",$content);
    for ($l=0; $l<count($lines); $l++) {
        $line = explode(",",trim($lines[$l]));

        // check if mpan contains letters
        $mpan = $line[0];
        if (preg_match('/[a-zA-Z]/', $mpan)) {
            $mpan_errors[$mpan] = 1;
            continue;
        }
        $mpan = (int) $mpan;
 
        if (count($line)==54) {
            $datestr = $line[5];
            $date_parts = explode("/",$datestr);
            if (count($date_parts)==3) {
                $date->setDate($date_parts[2],$date_parts[1],$date_parts[0]);
                $date->setTime(0,0,0);
                
                if ($line[4]=="AE" || $line[4]=="AI") {
                    // print $mpan." ".$line[4]." ".$line[5]."\n";
                    for ($hh=0; $hh<48; $hh++) {
                        $time = $date->getTimestamp() + $hh*1800;
                        
                        $index = ($hh)+6;
                        $value = trim($line[$index]);

                        $mpan_data[$mpan][$time] = $value;
                    }
                } else {
                    // print $mpan." not AE\n";
                }
            }
        }
        
        if (count($line)==99) {
            $datestr = $line[1];
            $date_parts = explode("/",$datestr);
            if (count($date_parts)==3) {
                $date->setDate($date_parts[2],$date_parts[1],$date_parts[0]);
                $date->setTime(0,0,0);
                              
                if ($line[2]=="AE" || $line[2]=="AI") {
                    // print $mpan." ".$line[2]." ".$line[1]."\n";
                    for ($hh=0; $hh<48; $hh+=2) {
                        $time = $date->getTimestamp() + $hh*1800;
                        
                        $index = ($hh)+3;
                        $value = trim($line[$index]);

                        $mpan_data[$mpan][$time] = $value;
                    }
                } else {
                    // print $mpan." not AE\n";
                }
            }
        }
    }

    return $mpan_data;
}

// No longer used
function write_csv_data($output_dir, $mpan_data) {
    foreach ($mpan_data as $mpan=>$data) {
        $fh = fopen("$output_dir/$mpan.csv","w");
        foreach ($data as $time=>$value) {
            fwrite($fh,$time.",".$value."\n");
        }
        fclose($fh);
    }
}

function write_feed_data($feed, $map, $mpan_data, $days=7) {
    $npoints = 0;
    // for each mpan in the map
    foreach ($map as $mpan=>$feedid) {
        // if the mpan is in the mpan data
        if (isset($mpan_data[$mpan])) {
            // get the last time value for the feed
            $timevalue = $feed->get_timevalue($feedid);

            $data = array();
            
            // for each time value in the mpan data
            foreach ($mpan_data[$mpan] as $time=>$value) {
                // if rebuild is true or the time value is newer than the last time value
                if ($days=='all' || $time>($timevalue['time']-($days*24*3600))) {  
                    $data[] = array($time,$value);
                    $npoints ++;
                }
            }

            if (count($data)>0) {
                $feed->post_multiple($feedid,$data);
            }
        }
    }
    
    print "npoints written: ".$npoints."\n";
}