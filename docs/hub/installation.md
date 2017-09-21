# Hub: Installation Guide

The CydYnni hub software stack builds on top of the OpenEnergyMonitor emonSD software stack used on the emonpi and emonbase hardware units. This installation guide describes the process of modifying the stock emonSD software stack for use as a CydYnni Hub.

**You will need:**

- RaspberryPi 3 + power supply and case
- 8GB Micro SD Card with emonSD image

To download and install emonSD image see:

[emonSD pre built SD card Download](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log#emonsd-07nov16)

Insert the SD card and power up the RaspberryPi for the first time with Ethernet connected, wait 5 minutes for the emonSD image to perform an initial over the air update.

Connect to the Hub via SSH:

    ssh pi@emonpi.local
    
Default emonSD password: emonpi2016

## Hub Emoncms requirements

**1. Switch to emoncms device-support branch**

The device-support branch provides support for device configuration in the emoncms input list extending and providing support for the device module installed in step 2 below.

    rpi-rw
    cd /var/www/emoncms
    git pull
    git checkout device-support
    
**2. Install emoncms device module**

The device module is used for device auto-configuration on the hub. It handles the automatic setup of inputs and feeds when it detects a known device such as the CydYnni smart-meter gateway.

    cd /var/www/emoncms/Modules
    git clone https://github.com/emoncms/device.git
    cd /var/www/emoncms/Modules/device
    git checkout device-integration
    
Modify emoncms settings.php to use the integrated device UI:

    nano /var/www/emoncms/settings.php

Add line to bottom of settings.php:

    $ui_version_2 = true;
     
**3. Install emoncms Demand Shaper module**

The demand shaper module uses a day ahead power availability forecast and user set schedules to determine the best time to run household loads.

Download or git clone the demandshaper repository in the home folder:

    cd
    git clone https://github.com/emoncms/demandshaper.git
    
Link the 'demandshaper-module' into the emoncms Modules folder:

    ln -s /home/pi/demandshaper/demandshaper-module /var/www/emoncms/Modules/demandshaper

Copy smartplug device template to device module:

    cp /home/pi/demandshaper/demandshaper-module/smartplug.json /var/www/emoncms/Modules/device/data/smartplug.json

Add demand shaper background process to crontab:

    crontab -e
    * * * * * php /home/pi/demandshaper/run.php 2>&1

**4. Add UDP Broadcast to cron for hub detection:**

    crontab -e
    * * * * * php /home/pi/emonpi/UDPBroadcast/broadcast.php 2>&1
    
**5. Update emoncms database**

    php /home/pi/emonpi/emoncmsdbupdate.php

### CydYnni App front-end

The following steps detail how to install the CydYnni App frontend on the hub. The CydYnni front-end sits as a user interface layer on top of the emonSD + emoncms stack as installed above.

Install the cydynni repository:

    cd
    git clone https://github.com/trystanlea/cydynni.git
    
Create a symbolic link of the public_html_hub directory to /var/www

    ln -s /home/pi/cydynni/public_html_hub /var/www/cydynni
    ln -s /var/www/cydynni /var/www/html/cydynni

Create a symbolic link of the emoncms cydynni module into the emoncms Modules folder:

    ln -s /home/pi/cydynni/cydynni-module /var/www/emoncms/Modules/cydynni
    ln -s /home/pi/cydynni/cydynni-emoncms-app.php /var/www/emoncms/Modules/app/apps/cydynni.php

Add entry to available_apps.php

    "cydynni"=>array(
        "status"=>"Development",
        "title"=>"CydYnni",
        "description"=>"Hydro and community consumption"
    )

Update emoncms database:

    php /home/pi/emonpi/emoncmsdbupdate.php

Add CydYnni syncronisation script (period download of hydro, community and smart meter data) to crontab:

    sudo crontab -e
    */5 * * * * php /home/pi/cydynni/scripts-hub/sync.php 2>&1

### Testing

Thats it for now! Login to the hub with your cydynni account at:

    http://emonpi.local/cydynni
    
The hub will now link to the remote cydynni.org.uk account and download a local copy of the cydynni hydro and community data as well as any historic smart meter data.

To test the demand shaper and schedule appliances, login to emoncms on the hub:

    http://emonpi.local/emoncms
    
Navigate to Setup > Inputs 
