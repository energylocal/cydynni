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
     
**1. Install emoncms Demand Shaper module**

The demand shaper module uses a day ahead power availability forecast and user set schedules to determine the best time to run household loads.

Download or git clone the demandshaper repository in the home folder:

    cd
    git clone https://github.com/emoncms/demandshaper.git
    
Link the 'demandshaper-module' into the emoncms Modules folder:

    ln -s /home/pi/demandshaper/demandshaper-module /var/www/emoncms/Modules/demandshaper

Add demand shaper service

    sudo ln -s /home/pi/demandshaper/demandshaper.service /lib/systemd/system
    sudo systemctl enable demandshaper.service
    sudo systemctl start demandshaper

Add UDP Broadcast for hub detection at the same time:

    crontab -e
    * * * * * php /home/pi/emonpi/UDPBroadcast/broadcast.php 2>&1

### CydYnni App front-end

The following steps detail how to install the CydYnni App frontend on the hub. The CydYnni front-end sits as a user interface layer on top of the emonSD + emoncms stack as installed above.

Install the cydynni repository:

    cd
    git clone https://github.com/energylocal/cydynni.git
        
Define CydYnni UI as hub:

    sudo nano /var/www/emoncms/settings.php
    
Add

    define("IS_HUB",1);
    
Modify default routes:

    // Default controller and action if none are specified and user is anonymous
    $default_controller = "cydynni";
    $default_action = "";

    // Default controller and action if none are specified and user is logged in
    $default_controller_auth = "cydynni";
    $default_action_auth = "";


Create a symbolic link of the emoncms cydynni module into the emoncms Modules folder:

    ln -s /home/pi/cydynni/cydynni-module /var/www/emoncms/Modules/cydynni

Update emoncms database:

    php /home/pi/emonpi/emoncmsdbupdate.php

Add CydYnni syncronisation script (period download of hydro, community and smart meter data) to crontab:

    sudo crontab -e
    */5 * * * * php /home/pi/cydynni/scripts-hub/sync.php 2>&1

### Emoncms modifications

Switch to energylocal fork

    cd /var/www/emoncms
    git remote set-url origin https://github.com/energylocal/emoncms.git
    git pull
    git checkout energylocal-hub

**Mods**

- Hide apps module menu item
- Add $advanced_users = array();
    
### CydYnni setup module

    cd /var/www/emoncms/Modules/setup
    git pull
    git checkout cydynni
    
### OTA Update

    crontab -e
    */10 * * * * /home/pi/cydynni/ota/update.sh >> /home/pi/data/ota.log 2>&1

### Hostname

    sudo nano /etc/hosts
    sudo nano /etc/hostname
    sudo nano /var/www/html/index.php
    
### Disable SSH

Remove ssh from /boot

### Testing

Thats it for now! Login to the hub with your cydynni account at:

    http://emonpi.local/cydynni
    
The hub will now link to the remote cydynni.org.uk account and download a local copy of the cydynni hydro and community data as well as any historic smart meter data.

To test the demand shaper and schedule appliances, login to emoncms on the hub:

    http://emonpi.local/emoncms
    
Navigate to Setup > Inputs 
