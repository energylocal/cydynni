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

    wget https://raw.githubusercontent.com/energylocal/cydynni/master/scripts-hub/install.sh
    chmod +x install.sh && ./install.sh

### Testing

Thats it for now! Login to the hub with your cydynni account at:

    http://emonpi.local
    
The hub will now link to the remote cydynni.org.uk account and download a local copy of the cydynni hydro and community data as well as any historic smart meter data.
