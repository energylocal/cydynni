#!/bin/bash

emoncms_dir=/opt/emoncms
openenergymonitor_dir=/opt/openenergymonitor

# --------------------------------------------------------------------------------
# 1. Install cydynni
# --------------------------------------------------------------------------------
cd $emoncms_dir/modules
git clone https://github.com/energylocal/cydynni.git
ln -s $emoncms_dir/modules/cydynni/cydynni-module /var/www/emoncms/Modules/cydynni

# --------------------------------------------------------------------------------
# 2. Emoncms: Switch to energylocal fork
# --------------------------------------------------------------------------------
cd /var/www/emoncms
git remote set-url origin https://github.com/energylocal/emoncms.git
git pull
git checkout energylocal-hub

# --------------------------------------------------------------------------------
# 3. Setup module: Switch to energylocal fork
# --------------------------------------------------------------------------------
cd $emoncms_dir/modules/demandshaper
git remote set-url origin https://github.com/energylocal/demandshaper.git
git pull
git checkout master

# --------------------------------------------------------------------------------
# 4. Setup module: Switch to energylocal fork
# --------------------------------------------------------------------------------
cd /var/www/emoncms/Modules/setup
git remote set-url origin https://github.com/energylocal/setup.git
git pull
git checkout master

# --------------------------------------------------------------------------------
# 5. Setup module: Switch to energylocal fork
# --------------------------------------------------------------------------------
cd $emoncms_dir/modules
git clone https://github.com/emoncms/remoteaccess-client
cd remoteaccess-client
cp remoteaccess.json.example remoteaccess.json
sudo chmod 666 remoteaccess.json
# link in web dir
ln -s $emoncms_dir/modules/remoteaccess-client/remoteaccess /var/www/emoncms/Modules/remoteaccess
# remote access service
sudo ln -s $emoncms_dir/modules/remoteaccess-client/remoteaccess.service /lib/systemd/system
sudo systemctl enable remoteaccess.service
sudo systemctl start remoteaccess

php $openenergymonitor_dir/EmonScripts/common/emoncmsdbupdate.php

# --------------------------------------------------------------------------------
# 6. Meter read service
# --------------------------------------------------------------------------------
sudo apt-get install nmap

ln $emoncms_dir/modules/cydynni/edmi-am.json /var/www/emoncms/Modules/device/data
sudo ln -s $emoncms_dir/modules/cydynni/scripts-hub/meterread/meterread.service /lib/systemd/system
sudo systemctl enable meterread.service
sudo systemctl start meterread.service
systemctl status meterread.service

# --------------------------------------------------------------------------------
# 7. Meter read service
# --------------------------------------------------------------------------------
cd $emoncms_dir/modules/cydynni/scripts-hub
echo '<?php $key="changeme";' > provisionkey.php

# --------------------------------------------------------------------------------
# 8. Modify hostname
# --------------------------------------------------------------------------------
# sudo nano /etc/hosts
# sudo nano /etc/hostname

# --------------------------------------------------------------------------------
# 9. Disable SSH
# --------------------------------------------------------------------------------


