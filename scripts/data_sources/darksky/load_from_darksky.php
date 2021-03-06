<?php

require "/opt/emoncms/modules/cydynni/scripts/lib/load_emoncms.php";

$key = $settings["darksky"]["key"];
$lat = $settings["darksky"]["lat"]; 
$long = $settings["darksky"]["lon"];
$feedid = $settings["darksky"]["feedid"];

$date = new DateTime();
$date->setTimezone(new DateTimeZone("Europe/London"));
$date->setTimestamp(time());
$date->modify("midnight");

for ($i=0; $i<3; $i++) {

    echo $date->format("c")."\n";
    echo "-------------------------\n";
    $time = $date->getTimestamp();

    $data = json_decode(file_get_contents("https://api.darksky.net/forecast/$key/$lat,$long,$time?units=si&exclude=currently,minutely,daily,alerts,flags"));

    foreach ($data->hourly->data as $hour) {
        print $hour->time." ".$hour->precipProbability." ".$hour->precipIntensity." ".$hour->humidity."\n";

        $feed->insert_data($feedid,$hour->time,$hour->time,$hour->precipIntensity);
        /*
        $feed->insert_data(406450,$hour->time,$hour->time,$hour->precipProbability);
        $feed->insert_data(406451,$hour->time,$hour->time,$hour->humidity);
        $feed->insert_data(406452,$hour->time,$hour->time,$hour->temperature);
        $feed->insert_data(406453,$hour->time,$hour->time,$hour->dewPoint);
        $feed->insert_data(406454,$hour->time,$hour->time,$hour->pressure);
        $feed->insert_data(406455,$hour->time,$hour->time,$hour->windSpeed);
        $feed->insert_data(406456,$hour->time,$hour->time,$hour->windGust);
        $feed->insert_data(406457,$hour->time,$hour->time,$hour->cloudCover);
        $feed->insert_data(406458,$hour->time,$hour->time,$hour->uvIndex);
        $feed->insert_data(406459,$hour->time,$hour->time,$hour->visibility);
        $feed->insert_data(406460,$hour->time,$hour->time,$hour->ozone);
        */
    }
    echo "\n";
    $date->modify("+1 day");
    sleep(1);
}

