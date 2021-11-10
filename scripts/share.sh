#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
echo $DIR

# sync_str=$(cat $DIR/sync_string.json)
# redis-cli rpush sync-queue $sync_str
# php /opt/emoncms/modules/sync/sync_run.php

php $DIR/get_repower_solar.php

php $DIR/process_power_feeds_run.php
php $DIR/mergefeeds_run.php
php $DIR/calculate_estimate.php
php $DIR/sharing_algorithm.php
