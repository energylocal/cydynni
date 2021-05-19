<?php
// -------------------------------------------------------------------------
// Imports from copy and paste of google sheet
// 
// Copy contents of each page to a local spreadsheet and merge together into one long list
// Copy and paste result into file called export
// Order of columns need to be:

// email	mpan	cad_serial	guid	install_code	requested_pairing	crl_server	confirmed_plugged_in	octopus_apikey 	meter_serial	dash_id

// -------------------------------------------------------------------------
$export = file_get_contents("export"); $club_id = 1; $dry_run = true;

require "create_user.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
include "Modules/remoteaccess/remoteaccess_userlink.php";

$users_claimed = array();

$lines = explode("\n",$export);
foreach ($lines as $line) {
    $p = explode("\t",$line);
    
    if (isset($p[9]) && $p[1]!="" && $p[1]!="#N/A") {
    
        $email = strtolower(trim($p[0]));
        $mpan = trim($p[1]);
        $cad_serial = trim($p[2]);
        $octopus_apikey = trim($p[8]);
        $meter_serial = trim($p[9]);
        
        // ---------------------------------------------------------------
        // Validate spreadsheet values
        // ---------------------------------------------------------------        
        $valid_email = false;
        $valid_mpan = false;
        $valid_cad_serial = false;
        $valid_meter_serial = false;
        $valid_octopus_apikey = false;
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) $valid_email = true;       
        if (strlen("$mpan")==13 && $mpan == (int) $mpan) $valid_mpan = true;
        if (strlen("$cad_serial")==10) $valid_cad_serial = true;
        if (strlen("$meter_serial")==10) $valid_meter_serial = true;
        if (strlen("$octopus_apikey")==32 && strpos($octopus_apikey,"sk_live_")==0) $valid_octopus_apikey = true;

        // ---------------------------------------------------------------
        // Find and check that emoncms user matches spreadsheet values
        // ---------------------------------------------------------------         
        $userid = false;
        $match_email = true; $local_email = "";
        $match_cad_serial = true; $local_cad_serial = "";
        $match_meter_serial = true; $local_meter_serial = "";
        $match_octopus_apikey = true; $local_octopus_apikey = "";
        
        if ($valid_mpan) {
            $result = $mysqli->query("SELECT * FROM cydynni WHERE `mpan`='".$mpan."'");
            if ($result->num_rows>1) echo "-- $result->num_rows users found with mpan=$mpan\n";
            
            if ($row = $result->fetch_object()) {
                $userid = $row->userid;
                $local_cad_serial = $row->cad_serial;
                $local_meter_serial = $row->meter_serial;
                $local_octopus_apikey = $row->octopus_apikey;
                
                if (!in_array($userid,$users_claimed)) {
                    $users_claimed[] = $userid; 
                } else {
                    print "-- User already claimed $userid $mpan\n";
                }
                
                if ($row->cad_serial!=$cad_serial) {
                    $match_cad_serial = false;
                    if ($valid_cad_serial) {
                        print "-- Updating user=$userid cad_serial=$cad_serial\n";                    
                        if (!$dry_run) $mysqli->query("UPDATE cydynni SET `cad_serial`='$cad_serial' WHERE `userid`='$userid'");
                    }
                }
                
                if ($row->meter_serial!=$meter_serial) {
                    $match_meter_serial = false;
                    if ($valid_meter_serial) {
                        print "-- Updating user=$userid meter_serial=$meter_serial\n";                    
                        if (!$dry_run) $mysqli->query("UPDATE cydynni SET `meter_serial`='$meter_serial' WHERE `userid`='$userid'");
                    }
                }
                
                if ($row->octopus_apikey!=$octopus_apikey) {
                    $match_octopus_apikey = false;
                    if ($valid_octopus_apikey) {
                        print "-- Updating user=$userid octopus_apikey=$octopus_apikey\n";                    
                        if (!$dry_run) $mysqli->query("UPDATE cydynni SET `octopus_apikey`='$octopus_apikey' WHERE `userid`='$userid'");
                    }
                }    
                
                $result2 = $mysqli->query("SELECT * FROM users WHERE `id`='".$row->userid."'");
                if ($row2 = $result2->fetch_object()) {
                    $local_email = $row2->email;
                    if ($row2->email!=$email) $match_email = false;
                }
            } else {
                if ($valid_email && $valid_mpan) {
                    // Create user
                    print "-- CREATE USER $email\n";
                    if (!$dry_run) create_user($club_id,$email,$mpan);
                }
            }
        } 

        // ---------------------------------------------------------------
        // Print out results
        // ---------------------------------------------------------------         
        $out = "";
        $out .= str_pad($email,40);
        if ($valid_email) $out .= "[OK]  "; else $out .= "      ";
                
        $out .= str_pad($mpan,14);
        if ($valid_mpan) $out .= "[OK]  "; else $out .= "      ";
        
        $out .= str_pad($cad_serial,11);
        if ($valid_cad_serial) $out .= "[OK]  "; else $out .= "      ";
        
        $out .= str_pad($meter_serial,11);
        if ($valid_meter_serial) $out .= "[OK]  "; else $out .= "      ";
        
        $out .= str_pad($octopus_apikey,33);
        if ($valid_octopus_apikey) $out .= "[OK]  "; else $out .= "      ";
        
        $out .= str_pad($userid,5);
        
        if (!$match_email) $out .= "(Local email=$local_email)";
        if (!$match_cad_serial && $local_cad_serial!="") $out .= "(Local cad_serial=$local_cad_serial)";
        if (!$match_meter_serial && $local_meter_serial!="") $out .= "(Local meter_serial=$local_meter_serial)";
        if (!$match_octopus_apikey && $local_octopus_apikey!="") $out .= "(Local octopus_apikey=$local_octopus_apikey)";

        print "$out\n";
    }   
}
