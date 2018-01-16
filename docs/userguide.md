## Setup Guide

### 1. Power up the CydYnni hub

After a short while a WIFI Access Point should appear called 'EmonPi'.<br>Connect to this network, open an internet browser and enter the following address:

    http://192.168.42.1

### 2. Connect to WIFI network

Once the page at the address above loads it will give the following options, or if the hub is connected with an ethernet cable there will also be the option to connect over ethernet.

Select 'Connect to WIFI network' to connect to your home WIFI network.
    
![setup1.png](images/setup1.png)

The hub will now show a list of available WIFI networks, select the WIFI network you wish to connect to:

![setup2.png](images/setup2.png)

Enter the WIFI network passkey if applicable:

![setup3.png](images/setup3.png)

After clicking connect the following message is shown. Your hub is now rebooting and will then attempt to connect to the network selected.

![setup4.png](images/setup4.png)

### 3. Login with CydYnni Account

After a couple of minutes, connect back to your home WIFI network and navigate to cydynni hub address:

    http://cydynni.local/
    
or:

    http://cydynni/
    
You will now be greated with the CydYnni hub login page. Enter your CydYnni username and password.

![setup5.png](images/setup5.png)

### 4. Explore your hub dashboard

The first page presents the CydYnni forecast showing when its a good time to use electricity or not and the recent history of club consumption and generator output.

![setup7.png](images/setup7.png)

The second page shows your score, which relates to how well your managing to match your use to local electricity and cheap times for extra electricity.

![setup8.png](images/setup8.png)

The 3rd page shows the club score and overview.

![setup9.png](images/setup9.png)

The 4rth tab shows a list of devices.

![setup10.png](images/setup10.png)

### 5. WIFI Relay Setup

Install and power up the WIFI Relay.

After a short while a WIFI Access Point should appear called 'emonESP_xxxxxx'.<br>Connect to this network, open an internet browser and enter the following address:

    http://192.168.4.1
    
![setup11.png](images/setup11.png)

Select the WIFI network you wish to connect to, enter the passkey and click connect. After 20-30s the interface will show that the module has connected and its IP address:

![setup12.png](images/setup12.png)

Connect back to you home network and navigate to the devices page, after a couple of minutes a notice will appear asking whether to allow device at the given ip  address to connect:

![setup13.png](images/setup13.png)

Click allow and wait another minute for the wifirelay device to appear. 

Clicking on the wifirelay device will bring up the scheduler interface:

![setup14.png](images/setup14.png)
