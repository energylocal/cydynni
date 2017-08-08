
# Cyd Ynni Community Energy Hydro web app

## http://cydynni.org.uk/

Cyd Ynni : Ynni Lleol is a community project linking up households with local community hydro power projects.

The CydYnni app is a mobile friendly web app also packaged as an android and iOS app that shows when it is a good time to use electricity in the CydYnni project.

## CydYnni status and Hydro output

![1.png](public_html/images/screenshots/1.png)
![2.png](public_html/images/screenshots/2.png)
![3.png](public_html/images/screenshots/3.png)

## Household score, community score and half hourly consumption

![4.png](public_html/images/screenshots/4.png)
![5.png](public_html/images/screenshots/5.png)
![1.png](public_html/images/screenshots/6.png)

## Community time of use breakdown and tips

![1.png](public_html/images/screenshots/7.png)
![1.png](public_html/images/screenshots/8.png)

### Licence

This software is available under the GNU Affero General Public Licence see Licence and Copyright files.

### Basic API's

Live status:

    https://cydynni.org.uk/live
    
Hydro output history (feedid 1):

    https://cydynni.org.uk/feed/average.json?id=1&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=1800
    
Community consumption history (feedid 2):

    https://cydynni.org.uk/feed/average.json?id=2&start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS&interval=1800

Community daily summary:

    https://cydynni.org.uk/community/summary/day

Community monthly summaries:

    https://cydynni.org.uk/community/summary/monthly

Demand shaper:

    https://cydynni.org.uk/demandshaper

**User session required**

Household daily summary:

    https://cydynni.org.uk/household/summary/day

Household monthly summaries:

    https://cydynni.org.uk/household/summary/monthly

Household consumption history:

    https://cydynni.org.uk/data?start=UNIXTIME_MILLISECONDS&end=UNIXTIME_MILLISECONDS

### Installation notes

Install main CydYnni app: symlink to /var/www, e.g:

    ln -s /home/user/cydynni/public_html /var/www/cydynni
    
Install advanced history view: symlink to /var/www, e.g:

    ln -s /home/user/cydynni/dev_public_html /var/www/dev    

Install Cydynni app for emoncms apps module:

    ln -s /home/pi/cydynni/cydynni-module /var/www/emoncms/Modules/cydynni
    ln -s /home/pi/cydynni/cydynni-emoncms-app.php /var/www/emoncms/Modules/app/apps/cydynni.php
    
Add entry to available_apps.php

    "cydynni"=>array(
        "status"=>"Development",
        "title"=>"CydYnni",
        "description"=>"Hydro and community consumption"
    )
