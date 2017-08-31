<?php

function import_phpfina($local_datadir,$local_id,$remote_server,$remote_id,$remote_apikey)
{   
    $apikey_string = "";
    if ($remote_apikey) $apikey_string = "&apikey=$remote_apikey";
    
    // Download phpfiwa feed meta data
    $remote_meta = json_decode(file_get_contents($remote_server."/feed/getmeta.json?id=".$remote_id.$apikey_string));
    
    if ($remote_meta==false || !isset($remote_meta->start_time) || !isset($remote_meta->interval)) {
        echo "ERROR: Invalid remote meta, returned false\n";
        echo json_encode($remote_meta)."\n";
        return false;
    }
    
    $local_meta = new stdClass();
    $local_meta->start_time = 0;
    $local_meta->npoints = 0;
    
    // Load local meta data file
    if (file_exists($local_datadir.$local_id.".meta"))
    {
        $local_meta = new stdClass();
        
        if (!$metafile = @fopen($local_datadir.$local_id.".meta", 'rb')) {
            echo "Cannot open local metadata file\n";
            return false;
        }
        
        fseek($metafile,8);
        
        $tmp = unpack("I",fread($metafile,4)); 
        $local_meta->interval = $tmp[1];
        $tmp = unpack("I",fread($metafile,4)); 
        $local_meta->start_time = $tmp[1];
        
        fclose($metafile);
        
        $bytesize = 0;
        if (file_exists($local_datadir.$local_id.".dat")) {
            clearstatcache($local_datadir.$local_id.".dat");
            $bytesize += filesize($local_datadir.$local_id.".dat");
        }
        $npoints = floor($bytesize / 4.0);
        $local_meta->npoints = $npoints;
        
    }
    
    if ($local_meta->start_time==0 && $local_meta->npoints==0)
    {
        $local_meta = $remote_meta;

        if (!$metafile = @fopen($local_datadir.$local_id.".meta", 'wb')) {
            echo "Cannot open local metadata file\n";
            return false;
        }
        
        // First 8 bytes used to hold id and npoints but are now removed.
        fwrite($metafile,pack("I",0));
        fwrite($metafile,pack("I",0)); 
        fwrite($metafile,pack("I",$local_meta->interval));
        fwrite($metafile,pack("I",$local_meta->start_time)); 
        fclose($metafile);
    }
    
    // We now check if the local meta data is the same as the remote meta data.
    // Given that the starttime, the interval and the feedname is the same we assume
    // that we are dealing with the same feed
    if ($local_meta->start_time != $remote_meta->start_time || $local_meta->interval != $remote_meta->interval)
    {
        echo "ERROR: Local and remote meta data do not match\n";
        echo "-- local->start = ".$local_meta->start_time." remote->start = ".$remote_meta->start_time."\n";
        echo "-- local->interval = ".$local_meta->interval." remote->interval = ".$remote_meta->interval."\n";
        return false;
    }
    
    $downloadfrom = 0;
    if (file_exists($local_datadir.$local_id.".dat")) {
        $downloadfrom = filesize($local_datadir.$local_id.".dat");
        if (intval($downloadfrom/4.0)!=($downloadfrom/4.0)) { 
            echo "ERROR: local datafile filesize is not an integer number of 4 bytes\n";  
            return false; 
        }
    }

    $url = $remote_server."/feed/export.json?id=$remote_id&start=$downloadfrom.$apikey_string";
    
    if (!$primary = @fopen( $url, 'r' )) {
        echo "Cannot access remote server\n";
        return false;
    }

    if ($downloadfrom>=4) {
        // update last datapoint
        $firstdp = fread($primary,4);
        if (!$backup = @fopen($local_datadir.$local_id.".dat", 'c')) {
            echo "Cannot open local data file - to update last datapoint\n";
            return false;
        }
        fseek($backup,$downloadfrom-4);
        fwrite($backup,$firstdp);
        fclose($backup);
    }

    if (!$backup = @fopen($local_datadir.$local_id.".dat", 'a')) {
        echo "Cannot open local data file - to append data\n";
        return false;
    }

    $dnsize = 0;
    if ($primary)
    {
        for (;;)
        {
            $data = fread($primary,8192);
            fwrite($backup,$data);
            $dnsize += strlen($data);
            if (feof($primary)) break;
        }
    }
    
    fclose($backup);
    fclose($primary);
        
    // Last time and value
    clearstatcache($local_datadir.$local_id.".dat");
    $npoints = floor(filesize($local_datadir.$local_id.".dat")/4);
    $time = $local_meta->start_time + ($local_meta->interval * $npoints);

    $backup = @fopen($local_datadir.$local_id.".dat", 'r');
    fseek($backup,($npoints-1)*4);
    $d = fread($backup,4);
    $val = unpack("f",$d);
     
    echo "--downloaded: ".$dnsize." bytes\n";
    
    return array("time"=>$time, "value"=>$val[1]);
}

function upload($local_dir,$local_feed,$remote_host,$remote_feedid,$remote_apikey)
{
    $apikey_string = "";
    if ($remote_apikey) $apikey_string = "&apikey=$remote_apikey";
        
    // Read local feed meta file
    if (!$meta = get_meta($local_dir,$local_feed)) {
        print "ERROR: Could not open local feed.meta file\n";
        return false;
    }
    $start = $meta->start_time;
    $interval = $meta->interval;

    // Download remote feed meta data
    $remote_meta = json_decode(file_get_contents($remote_host."/feed/getmeta.json?apikey=$remote_apikey&id=".$remote_feedid));
    
    if ($remote_meta==false || !isset($remote_meta->start_time) || !isset($remote_meta->interval) || !isset($remote_meta->npoints)) {
        echo "ERROR: Invalid remote meta, returned false\n";
        echo json_encode($remote_meta)."\n";
        return false;
    }
    
    $start = $remote_meta->start_time + ($remote_meta->interval * $remote_meta->npoints);

    // Calculate size of file to upload in number of datapoints
    $start_pos = floor(($start - $meta->start_time)/$meta->interval);
    if ($start_pos<0) { 
        $start_pos = 0;
        $start = $meta->start_time;
    }
    
    $npoints = floor(filesize($local_dir.$local_feed.".dat")/4.0);
    $npoints_to_upload = $npoints - $start_pos;
    print "Upload size: ".(($npoints_to_upload*4)/1024)."kb\n";

    // Calculate number of blocks
    $blocksize = round((1024*512) / 4); // 100kb blocks
    $blocknum = ceil($npoints_to_upload / $blocksize);
    print "Number of blocks: $blocknum\n";

    // Open local feed data file
    $n=0;
    $fh = fopen($local_dir.$local_feed.".dat", 'rb');
    for ($block=0; $block<$blocknum; $block++) {
        
        // Seek to block start position and read block
        $pos = floor(($start - $meta->start_time)/$meta->interval);
        fseek($fh,$pos*4);
        $data = fread($fh,$blocksize*4);
        $actualblocksize = floor(strlen($data) / 4.0);
        
        // Send the data block        
        $result = request("$remote_host/feed/upload.json?id=$remote_feedid&start=$start&interval=$interval&npoints=$actualblocksize".$apikey_string,$data);
        
        // Print result
        $upload_size = round(($actualblocksize*4)/1024);
        print "$n $upload_size"."kb upload: $result\n"; 
        
        // Exit if upload fails (Extend here to attempt retry??)
        if ($result===false || $result==="false") break;
        
        // Advance next position
        $start += $actualblocksize * $interval;
        $n++;
    }

}

function get_meta($dir,$id)
{
    $meta = new stdClass();
    if (!$metafile = fopen($dir.$id.".meta", 'rb')) return false;
    fseek($metafile,8);
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->interval = $tmp[1];
    $tmp = unpack("I",fread($metafile,4)); 
    $meta->start_time = $tmp[1];
    fclose($metafile);
    return $meta;
}

function request($url,$data)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,5);
    curl_setopt($curl, CURLOPT_TIMEOUT,10);

    $curl_response = curl_exec($curl);
    curl_close($curl);

    return $curl_response;
}
