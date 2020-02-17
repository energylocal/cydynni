#!/bin/bash
# -----------------------------------------------------------------------------------
# Migrate cydynni hub
# -----------------------------------------------------------------------------------

emoncms_dir=/opt/emoncms
openenergymonitor_dir=/opt/openenergymonitor
emoncms_www=/var/www/emoncms

if [ ! -d $openenergymonitor_dir ]; then
  sudo mkdir $openenergymonitor_dir
  sudo chown pi $openenergymonitor_dir
fi

if [ ! -d $emoncms_dir ]; then
  sudo mkdir $emoncms_dir
  sudo chown pi $emoncms_dir
  mkdir $emoncms_dir/modules
fi

echo "-----------------------------------------------------------------------------------"
echo "Move $openenergymonitor_dir"
echo "-----------------------------------------------------------------------------------"
if [ -d /home/pi/emonpi ]; then
  mv /home/pi/emonpi $openenergymonitor_dir
  mv /home/pi/emonhub $openenergymonitor_dir
  mv /home/pi/RFM2Pi $openenergymonitor_dir
fi
echo "-----------------------------------------------------------------------------------"
echo "Move and relink"
echo "-----------------------------------------------------------------------------------"
for module in "demandshaper" "postprocess" "sync" "backup" "cydynni" "usefulscripts" "remoteaccess-client"
do
  if [ -d /home/pi/$module ]; then
    # 1. Move
    echo "moving $module"
    mv /home/pi/$module $emoncms_dir/modules

    # 2. Update  
    cd $emoncms_dir/modules/$module
    git checkout .
    git pull

    # 3. Link
    if [ -d $emoncms_dir/modules/$module/$module-module ]; then
        echo "linking $module"
        ln -sf $emoncms_dir/modules/$module/$module-module $emoncms_www/Modules/$module
    fi
  fi
done
cd

# manually link remoteaccess module
ln -sf $emoncms_dir/modules/remoteaccess-client/remoteaccess $emoncms_www/Modules/remoteaccess

echo "-----------------------------------------------------------------------------------"
echo "Install repo's"
echo "-----------------------------------------------------------------------------------"
sudo apt-get install nmap -y

# Install EmonScripts
cd $openenergymonitor_dir
if [ ! -d $openenergymonitor_dir/EmonScripts ]; then
  git clone https://github.com/openenergymonitor/EmonScripts.git
fi

# Install setup module
rm -rf $emoncms_www/Modules/setup
cd $emoncms_www/Modules
git clone https://github.com/emoncms/setup.git

echo "-----------------------------------------------------------------------------------"
echo "Update repo's"
echo "-----------------------------------------------------------------------------------"
# Update cydynni module
cd $emoncms_dir/modules/cydynni
git checkout .
git pull

# Update emoncms
cd $emoncms_www
git checkout .
git pull

# Install new crontab configuration
sudo crontab $emoncms_dir/modules/cydynni/defaults/root_crontab
crontab $emoncms_dir/modules/cydynni/defaults/pi_crontab

# Reinstall edmi-am meter template
rm $emoncms_www/Modules/device/data/edmi-am.json
ln -s $emoncms_dir/modules/cydynni/edmi-am.json $emoncms_www/Modules/device/data

# Install new emoncms settings
cp $emoncms_dir/modules/cydynni/defaults/settings.old.ini $emoncms_www/settings.ini
if [ -f $emoncms_www/settings.php ]; then
  rm $emoncms_www/settings.php
fi

echo "-----------------------------------------------------------------------------------"
echo "Remove services to be reinstalled"
echo "-----------------------------------------------------------------------------------"
for service in "emonhub" "meterread" "remoteaccess" "demandshaper"
do
  echo "removing service $service"
  sudo systemctl disable $service
  sudo rm /lib/systemd/system/$service.service
  sudo rm /etc/systemd/system/$service.service
done

echo "-----------------------------------------------------------------------------------"
echo "Reinstall / restart services"
echo "-----------------------------------------------------------------------------------"
sudo systemctl daemon-reload
# restart services
sudo systemctl restart emoncms_mqtt
sudo systemctl restart feedwriter
sudo systemctl restart service-runner

# Reinstall meterreader service
sudo ln -s $emoncms_dir/modules/cydynni/scripts-hub/meterread/meterread.service /lib/systemd/system
sudo systemctl enable meterread.service
sudo systemctl restart meterread.service
sudo systemctl status meterread.service

# Reinstall remoteaccess-client service
sudo ln -s $emoncms_dir/modules/remoteaccess-client/remoteaccess.service /lib/systemd/system
sudo systemctl enable remoteaccess.service
sudo systemctl restart remoteaccess
sudo systemctl status remoteaccess
# Reinstall emonhub service
$openenergymonitor_dir/emonhub/install.sh

# Reinstall demandshaper service
$emoncms_dir/modules/demandshaper/install.sh

# -----------------------------------------------------------------------------------
# Other cleanup
# -----------------------------------------------------------------------------------
sudo ln -sf $openenergymonitor_dir/emonpi/stretch/motd /etc/motd
sudo ln -sf $openenergymonitor_dir/emonpi/stretch/rc.local /etc/rc.local
sudo ln -sf $openenergymonitor_dir/emonpi/wifi-check /usr/local/bin/wifi-check
sudo ln -sf $openenergymonitor_dir/emonpi/lcd /usr/share/emonPiLCD
sudo ln -sf $emoncms_dir/modules/usefulscripts/sdpart/sdpart_imagefile /usr/local/sbin/emonSDexpand
sudo ln -sf $openenergymonitor_dir/emonpi/wifiAP/wifiAP.sh /usr/local/sbin/wifiAP

sudo rm /etc/fstab
sudo cp $openenergymonitor_dir/emonpi/stretch/fstab /etc/fstab

sudo rm /etc/default/emonhub
# rm -rf /home/pi/huawei-hilink-status
# rm -rf /home/pi/lightwaverf-pi
# rm -rf /home/pi/oem_node-red

sudo service apache2 reload
