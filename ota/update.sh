#!/bin/bash

# DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# cd $DIR
cd

remote_ota_version="$(curl -s 'https://raw.githubusercontent.com/TrystanLea/cydynni/master/ota/version')"
local_ota_version="$(cat cydynni/ota/version)"

if [ "$remote_ota_version" -ne "$local_ota_version" ]
then
  echo "Remote:"$remote_ota_version" Local:"$local_ota_version
  echo "Update available, starting update process.."
  rpi-rw
  cd cydynni
  git pull origin master
  cd ota
  ./main.sh
  cd
  rpi-ro
fi
