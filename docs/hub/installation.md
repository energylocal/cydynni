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

