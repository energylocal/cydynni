#!/bin/bash

# DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# cd $DIR
cd

apikey="$(php /home/pi/cydynni/ota/getapikey.php)"
local_ota_version="$(cat cydynni/ota/version)"

url="https://dashboard.energylocal.org.uk/cydynni/ota-version?hub=$local_ota_version&apikey=$apikey"
remote_ota_version="$(curl -s $url)"

if [ "$remote_ota_version" -ne "$local_ota_version" ]
then
  echo "Remote:"$remote_ota_version" Local:"$local_ota_version
  echo "Update available, starting update process.."
  # rpi-rw
  cd /home/pi/cydynni
  git pull
  cd /home/pi/cydynni/ota
  log=$(./main.sh)
  cd 
  # rpi-ro

  echo $"$log"
  url="https://dashboard.energylocal.org.uk/cydynni/ota-log-set?apikey=$apikey"
  $(curl -s -X POST -d "$log" $url)
fi
