<?php
// -------------------------------------------------------------------------
// Imports from copy and paste of google sheet
// 
// Copy contents of each page to a local spreadsheet and merge together into one long list
// Copy and paste result into file called export
// Order of columns need to be:

// email	mpan	cad_serial	guid	install_code	requested_pairing	crl_server	confirmed_plugged_in	octopus_apikey 	meter_serial	dash_id

// -------------------------------------------------------------------------
$export = file_get_contents("export"); $club_id = 1; $dry_run = false;

require "create_user.php";
require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";
include "Modules/remoteaccess/remoteaccess_userlink.php";

$users_claimed = array();

$lines = explode("\n",$export);

$map = array();
// Map columns
echo "---------------------------------------------------\n";
echo "Mapping columns\n";
echo "---------------------------------------------------\n";
$p = explode("\t",$lines[0]);
for ($i=0; $i<count($p); $i++) {
    $key = trim(strtolower($p[$i]));
    print $i.": ".$key;
    if ($key=="email") { $map["email"] = $i; echo " #"; }
    else if ($key=="mpan") { $map["mpan"] = $i; echo " #"; }
    else if ($key=="cad serial") { $map["cad_serial"] = $i; echo " #"; }
    else if ($key=="api key") { $map["octopus_apikey"] = $i; echo " #"; }
    else if ($key=="meter serial") { $map["meter_serial"] = $i; echo " #"; }
    
    echo "\n";
}
echo "---------------------------------------------------\n";

if (!isset($map["email"])) { die("missing email\n"); }
if (!isset($map["mpan"])) { die("missing mpan\n"); }
if (!isset($map["cad_serial"])) { die("missing cad_serial\n"); }
if (!isset($map["octopus_apikey"])) { die("missing octopus_apikey\n"); }
if (!isset($map["meter_serial"])) { die("missing meter_serial\n"); }

echo "---------------------------------------------------\n";

$changes = false;

for ($i=1; $i<count($lines); $i++) {
    $p = explode("\t",$lines[$i]);
    
    if (isset($p[$map['mpan']]) && $p[$map['mpan']]!="" && $p[$map['mpan']]!="#N/A") {
    
        $email = strtolower(trim($p[$map['email']]));
        $mpan = trim($p[$map['mpan']]);
        $cad_serial = trim($p[$map['cad_serial']]);
        $octopus_apikey = trim($p[$map['octopus_apikey']]);
        $meter_serial = trim($p[$map['meter_serial']]);
        
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
                        $changes = true;                 
                        print "-- Updating user=$userid cad_serial=$cad_serial\n";                    
                        if (!$dry_run) $mysqli->query("UPDATE cydynni SET `cad_serial`='$cad_serial' WHERE `userid`='$userid'");
                    }
                }
                
                if ($row->meter_serial!=$meter_serial) {
                    $match_meter_serial = false;
                    if ($valid_meter_serial) {
                        $changes = true;      
                        print "-- Updating user=$userid meter_serial=$meter_serial\n";                    
                        if (!$dry_run) $mysqli->query("UPDATE cydynni SET `meter_serial`='$meter_serial' WHERE `userid`='$userid'");
                    }
                }
                
                if ($row->octopus_apikey!=$octopus_apikey) {
                    $match_octopus_apikey = false;
                    if ($valid_octopus_apikey) {
                        $changes = true;            
                        print "-- Updating user=$userid octopus_apikey=$octopus_apikey\n";                    
                        if (!$dry_run) $mysqli->query("UPDATE cydynni SET `octopus_apikey`='$octopus_apikey' WHERE `userid`='$userid'");
                    }
                }
                
                $result2 = $mysqli->query("SELECT * FROM users WHERE `id`='".$row->userid."'");
                if ($row2 = $result2->fetch_object()) {
                    $local_email = $row2->email;
                    if ($row2->email!=$email) {
                        $match_email = false;
                        $changes = true;
                        //if (!$dry_run) $mysqli->query("UPDATE users SET `email`='$email' WHERE `id`='$userid'");
                    }
                }
            } else {
                if ($valid_email && $valid_mpan) {
                    // Create user
                    print "-- CREATE USER $email\n";
                    if (!$dry_run) create_user($club_id,$email,$mpan);
                    $changes = true;
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
        
        if (!$match_email && $valid_email) $out .= "(Local email=$local_email)";
        if (!$match_cad_serial && $valid_cad_serial) $out .= "(Local cad_serial=$local_cad_serial)";
        if (!$match_meter_serial && $valid_meter_serial) $out .= "(Local meter_serial=$local_meter_serial)";
        if (!$match_octopus_apikey && $valid_octopus_apikey) $out .= "(Local octopus_apikey=$local_octopus_apikey)";

        print "$out\n";
    }   
}
echo "---------------------------------------------------\n";
if (!$changes) {
    echo "no changes detected\n";
} else {
    echo "changes detected\n";
}
echo "---------------------------------------------------\n";

