#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

echo "-----------------------------------------"

cd /opt/emoncms/modules/cydynni
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "cydynni:"$branch":"$commit

cd /opt/emoncms/modules/demandshaper
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

/opt/openenergymonitor/EmonScripts/update/service-runner-update.sh > /var/log/emoncms/emonpiupdate.log

cd /opt/emoncms/modules/demandshaper
git pull
cd

cd /opt/emoncms/modules/remoteaccess-client
git pull
cd

echo "emoncms db update: "
php /opt/openenergymonitor/EmonScripts/common/emoncmsdbupdate.php

echo "restarting services: "
sudo systemctl restart emoncms_mqtt.service
sudo systemctl restart demandshaper.service
sudo systemctl restart feedwriter.service
sudo systemctl restart remoteaccess.service
