# Web App Installation

Installation guide for CydYnni web app, as installed on cydynni.org.uk

**--Work in Progress**

Install main CydYnni app: symlink to /var/www, e.g:

    ln -s /home/user/cydynni/public_html /var/www/cydynni
    
Install advanced history view: symlink to /var/www, e.g:

    ln -s /home/user/cydynni/dev_public_html /var/www/history

Install Cydynni app for emoncms apps module:

    ln -s /home/pi/cydynni/cydynni-module /var/www/emoncms/Modules/cydynni
    ln -s /home/pi/cydynni/cydynni-emoncms-app.php /var/www/emoncms/Modules/app/apps/cydynni.php
    
Add entry to available_apps.php

    "cydynni"=>array(
        "status"=>"Development",
        "title"=>"CydYnni",
        "description"=>"Hydro and community consumption"
    )
