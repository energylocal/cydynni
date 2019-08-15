#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

echo "-----------------------------------------"

cd /home/pi/cydynni
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "cydynni:"$branch":"$commit

cd /home/pi/demandshaper
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "demandshaper:"$branch":"$commit

cd /var/www/emoncms
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "emoncms:"$branch":"$commit

cd /var/www/emoncms/Modules/device
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "emoncms:device:"$branch":"$commit

echo "-----------------------------------------"

/home/pi/emonpi/service-runner-update.sh > /home/pi/data/emonpiupdate.log

cd /home/pi/demandshaper
git pull
cd

cd /home/pi/remoteaccess-client
git pull
cd

echo "emoncms db update: "
php /home/pi/emonpi/update/emoncmsdbupdate.php

echo "restarting services: "
sudo systemctl restart emoncms_mqtt.service
sudo systemctl restart demandshaper.service
sudo systemctl restart feedwriter.service
sudo systemctl restart remoteaccess.service
