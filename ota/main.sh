#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

echo "-----------------------------------------"
cd 
cd cydynni
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "cydynni:"$branch":"$commit
cd

cd demandshaper
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "demandshaper:"$branch":"$commit
cd

cd /var/www/emoncms
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "emoncms:"$branch":"$commit
cd

cd /var/www/emoncms/Modules/device
branch="$(git rev-parse --abbrev-ref HEAD)"
commit="$(git rev-parse HEAD)"
echo "emoncms-mod-device:"$branch":"$commit
cd
echo "-----------------------------------------"


cd demandshaper
git pull
cd 

cd /var/www/emoncms
git pull
cd 

cd /var/www/emoncms/Modules/device
git pull
cd

php /home/pi/emonpi/emoncmsdbupdate.php

if [ -f /home/pi/data/demandshaper.pid ]; then
  pid="$(cat /home/pi/data/demandshaper.pid)"
  kill $pid
fi
