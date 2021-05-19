#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
echo $DIR

php /opt/emoncms/modules/sync/sync_run.php

php $DIR/process_power_feeds_run.php
php $DIR/mergefeeds_run.php
php $DIR/calculate_estimate.php
php $DIR/sharing_algorithm.php
