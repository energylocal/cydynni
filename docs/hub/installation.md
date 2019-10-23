# Hub: Installation Guide

The CydYnni hub software stack builds on top of the OpenEnergyMonitor emonSD software stack used on the emonpi and emonbase hardware units. This installation guide describes the process of modifying the stock emonSD software stack for use as a CydYnni Hub.

**You will need:**

- RaspberryPi 3 + power supply and case
- 16GB Micro SD Card with emonSD image

To download and install emonSD image see:

[emonSD pre built SD card Download: emonSD-17Oct19](https://github.com/openenergymonitor/emonpi/wiki/emonSD-pre-built-SD-card-Download-&-Change-Log)

Mount SD card on computer, add file called 'ssh' to boot partition to enable ssh.

Insert the SD card and power up the RaspberryPi for the first time with Ethernet connected, wait 5 minutes for the emonSD image to perform an initial over the air update.

Connect to the Hub via SSH:

    ssh pi@emonpi.local
    
Default emonSD password: emonpi2016

## Hub Emoncms requirements

Add UDP Broadcast for hub detection at the same time:

    crontab -e
    * * * * * php /opt/openenergymonitor/emonpi/UDPBroadcast/broadcast.php 2>&1

### CydYnni App front-end

The following steps detail how to install the CydYnni App frontend on the hub. The CydYnni front-end sits as a user interface layer on top of the emonSD + emoncms stack as installed above.

Install the cydynni repository:

    cd /opt/emoncms/modules
    git clone https://github.com/energylocal/cydynni.git
        
Define CydYnni UI as hub, add other energylocal specific settings:

    sudo nano /var/www/emoncms/settings.php
    
Add

    [cydynni]
    is_hub = true
    advanced_users = []
    enable_UDP_broadcast = true

Modify default routes:

    default_controller = "cydynni"
    default_action = ""
    default_controller_auth = "cydynni"
    default_action_auth = ""

Create a symbolic link of the emoncms cydynni module into the emoncms Modules folder:

    ln -s /opt/emoncms/modules/cydynni/cydynni-module /var/www/emoncms/Modules/cydynni

Update emoncms database:

    php /opt/openenergymonitor/EmonScripts/common/emoncmsdbupdate.php

Add CydYnni syncronisation script (period download of hydro, community and smart meter data) to crontab:

    sudo crontab -e
    */5 * * * * php /opt/emoncms/modules/cydynni/scripts-hub/sync.php 2>&1

### Emoncms modifications

Switch to energylocal fork

    cd /var/www/emoncms
    git remote set-url origin https://github.com/energylocal/emoncms.git
    git pull
    git checkout energylocal-hub

**Mods**

- Hide apps module menu item

### Emoncms setup module energylocal fork

Switch to energylocal fork

    cd /var/www/emoncms/Modules/setup
    git remote set-url origin https://github.com/energylocal/setup.git
    git pull
    git checkout master

### RemoteAccess Client Installation

Create remoteaccess.env settings file with emoncms.org username and password.

    cd /opt/emoncms/modules
    git clone https://github.com/emoncms/remoteaccess-client
    cd remoteaccess-client
    cp remoteaccess.json.example remoteaccess.json
    sudo chmod 666 remoteaccess.json

Install and start remoteaccess service:

    sudo ln -s /opt/emoncms/modules/remoteaccess-client/remoteaccess.service /lib/systemd/system
    sudo systemctl enable remoteaccess.service
    sudo systemctl start remoteaccess

    ln -s /opt/emoncms/modules/remoteaccess-client/remoteaccess /var/www/emoncms/Modules/remoteaccess
    
### OTA Update

    crontab -e
    */10 * * * * /opt/emoncms/modules/cydynni/ota/update.sh >> /var/log/emoncms/ota.log 2>&1

### Hostname

    sudo nano /etc/hosts
    sudo nano /etc/hostname
    
### NMAP and meterread

    sudo apt-get install nmap
    
    sudo ln -s /opt/emoncms/modules/cydynni/scripts-hub/meterread/meterread.service /lib/systemd/system
    sudo systemctl enable meterread.service
    sudo systemctl start meterread.service
    systemctl status meterread.service

    ln /opt/emoncms/modules/cydynni/edmi-am.json /var/www/emoncms/Modules/device/data
    
Change base ip address to relevant..

### Provision Key

    cd /opt/emoncms/modules/cydynni/scripts-hub
    echo '<?php $key="changeme";' > provisionkey.php
    crontab -e
    
    */5 * * * * php /opt/emoncms/modules/cydynni/scripts-hub/provision.php 2>&1
   
### Disable SSH

Remove ssh from /boot

### Testing

Thats it for now! Login to the hub with your cydynni account at:

    http://emonpi.local
    
The hub will now link to the remote cydynni.org.uk account and download a local copy of the cydynni hydro and community data as well as any historic smart meter data.
