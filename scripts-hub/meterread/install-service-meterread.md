# MeterRead

Reads data from EDMI Advanced Meter, posts to emoncms

## Install meterread service

Install the service using the following commands:
```
sudo ln -s /opt/emoncms/modules/cydynni/scripts-hub/meterread/meterread.service /lib/systemd/system
sudo systemctl enable meterread.service
sudo systemctl start meterread.service
systemctl status meterread.service
```

View the log with:
`journalctl -f -u meterread`

Install device template:

    ln /opt/emoncms/modules/cydynni/edmi-am.json /var/www/emoncms/Modules/device/data
