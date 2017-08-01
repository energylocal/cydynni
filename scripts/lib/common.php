<?php

function getmeta($dir,$id) 
{
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

function createmeta($dir,$id,$meta) 
{
    $metafile = fopen($dir.$id.".meta", 'wb');
    fwrite($metafile,pack("I",0));
    fwrite($metafile,pack("I",0)); 
    fwrite($metafile,pack("I",$meta->interval));
    fwrite($metafile,pack("I",$meta->start_time)); 
    fclose($metafile);
}

function updatetimevalue($id,$time,$value){
    global $redis;
    $redis->hMset("feed:$id", array('value' => $value, 'time' => $time));
}
