#!/bin/bash

# DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# cd $DIR
cd

remote_ota_version="$(curl -s 'https://raw.githubusercontent.com/TrystanLea/cydynni/master/ota/version')"
local_ota_version="$(cat cydynni/ota/version)"

echo "Remote OTA version:"$remote_ota_version
echo "Local OTA version:"$local_ota_version

if [ "$remote_ota_version" -eq "$local_ota_version" ]
then
  echo "Local and Remote OTA versions are the same"
else 
  echo "Update available, starting update process.."
  rpi-rw
  cd cydynni
  git pull origin master
  cd ota
  ./main.sh
  cd
  rpi-ro
fi
