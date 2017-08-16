<?php


$households = array(400,570,500,500,550,1000);
$household_import = array();
$household_hydro = array();

$hydro = 3000;

// Initial conditions
$community = 0;
for ($i=0; $i<count($households); $i++) {
		$household_hydro[$i] = 0;
    $household_import[$i] = $households[$i];
    $community += $households[$i];
}

print "Hydro: $hydro, Community: $community\n";

if ($hydro>=$community) {

    print "households 100% hydro\n";

} else {

		$spare_hydro = $hydro;
		
		// Calculate number of users with import requirements
		$users_to_share = 0;
		for ($i=0; $i<count($household_import); $i++) {
		    if ($household_import[$i]>0) $users_to_share++;
		}
		
		$share_count = 0;
		while ($spare_hydro>0) 
		{
				// Calculate hydro share per user
				$hydro_share = $spare_hydro / $users_to_share;
				
				// Itterate through each household subtracting hydro share
				$spare_hydro = 0;
				$users_to_share = 0;
				for ($i=0; $i<count($household_import); $i++) {
				
						$balance = $household_import[$i];
				    
						if ($balance>0) {
								$balance -= $hydro_share;
								if ($balance<0) {
										$remainder = $balance * -1;
								    $spare_hydro += $remainder;
								    $balance = 0;
								} else {
								    $users_to_share++;
								}
				    }
				    
				    $household_import[$i] = $balance;
				}
				
				$share_count ++;
    }
    
		for ($i=0; $i<count($households); $i++) {
		    $household_hydro[$i] = $households[$i] - $household_import[$i];
		}
    
    print "household: ".json_encode($households)."\n";
    print "household hydro: ".json_encode($household_hydro)."\n";
    print "household import: ".json_encode($household_import)."\n";
    print "Share count: $share_count\n";
}

