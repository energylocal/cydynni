#!/bin/bash

emoncms_dir=/opt/emoncms
openenergymonitor_dir=/opt/openenergymonitor
emoncms_www=/var/www/emoncms

echo "--------------------------------------------------------------------------------"
echo "Install cydynni"
echo "--------------------------------------------------------------------------------"
cd $emoncms_dir/modules
git clone https://github.com/energylocal/cydynni.git
ln -s $emoncms_dir/modules/cydynni/cydynni-module /var/www/emoncms/Modules/cydynni
cp $emoncms_dir/modules/cydynni/defaults/settings.ini $emoncms_www
cd

echo "--------------------------------------------------------------------------------"
echo "Emoncms: Switch to energylocal fork"
echo "--------------------------------------------------------------------------------"
cd /var/www/emoncms
git remote set-url origin https://github.com/energylocal/emoncms.git
git pull
git checkout energylocal-hub
cd

# hide menu for app and dashboard module
rm /var/www/emoncms/Modules/app/app_menu.php
rm /var/www/emoncms/Modules/dashboard/dashboard_menu.php

git -C /var/www/emoncms/Modules/device pull

echo "--------------------------------------------------------------------------------"
echo "Setup module: Switch to energylocal fork"
echo "--------------------------------------------------------------------------------"
cd $emoncms_dir/modules/demandshaper
git remote set-url origin https://github.com/energylocal/demandshaper.git
git pull
git checkout master
cd

echo "--------------------------------------------------------------------------------"
echo "Setup module: Switch to energylocal fork"
echo "--------------------------------------------------------------------------------"
cd /var/www/emoncms/Modules/setup
git remote set-url origin https://github.com/energylocal/setup.git
git pull
git checkout master
cd

echo "--------------------------------------------------------------------------------"
echo "Setup module: Switch to energylocal fork"
echo "--------------------------------------------------------------------------------"
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

cd
echo "--------------------------------------------------------------------------------"
echo "Meter read service"
echo "--------------------------------------------------------------------------------"
sudo apt-get install nmap -y

ln $emoncms_dir/modules/cydynni/edmi-am.json /var/www/emoncms/Modules/device/data
sudo ln -s $emoncms_dir/modules/cydynni/scripts-hub/meterread/meterread.service /lib/systemd/system
sudo systemctl enable meterread.service
sudo systemctl start meterread.service
systemctl status meterread.service

echo "--------------------------------------------------------------------------------"
echo "Meter read service"
echo "--------------------------------------------------------------------------------"
cd $emoncms_dir/modules/cydynni/scripts-hub
provisionkey=$(date +%s | sha256sum | base64 | head -c 8)
echo '<?php $key="'$provisionkey'";' > provisionkey.php
cd

echo "--------------------------------------------------------------------------------"
echo "Install new crontab configuration"
echo "--------------------------------------------------------------------------------"
sudo crontab $emoncms_dir/modules/cydynni/defaults/root_crontab
crontab $emoncms_dir/modules/cydynni/defaults/pi_crontab

# echo "--------------------------------------------------------------------------------"
# echo "Modify hostname"
# echo "--------------------------------------------------------------------------------"
# sudo nano /etc/hosts
# sudo nano /etc/hostname

echo "--------------------------------------------------------------------------------"
echo "Disable SSH"
echo "--------------------------------------------------------------------------------"

cd
rm install.sh
