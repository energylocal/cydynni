<?php
/*

Source code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
OpenEnergyMonitor VirtualSmartGrid - Open source virtual smart grid renewable energy aggregation and sharing concept with a focus on carbon metrics.

Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

require "household_process.php";

$redis = new Redis();
$connected = $redis->connect("127.0.0.1");

error_reporting(E_ALL);
ini_set('display_errors', 'on');

$q = "";
if (isset($_GET['q'])) $q = $_GET['q'];


$logger = new EmonLogger();
switch ($q)
{   
    case "":
        header('Content-Type: text/html');
        print file_get_contents("pages/hydro.html");
        break;
        
    case "household":
        header('Content-Type: text/html');
        print file_get_contents("pages/household.html");
        break;
        
    case "household/data":
        header('Content-Type: application/json');
        $apikey = "";
        $id = 1;
        print json_encode(get_household_data($apikey,$id));
        break;

    case "data":
        header('Content-Type: application/json');
        // Interval
        if (isset($_GET['interval']))
            print file_get_contents("https://emoncms.org/feed/data.json?id=".get("id")."&start=".get("start")."&end=".get("end")."&interval=".get("interval")."&skipmissing=".get("skipmissing")."&limitinterval=".get("limitinterval")."&apikey=".get("apikey"));
        // Mode
        if (isset($_GET['mode']))
            print file_get_contents("https://emoncms.org/feed/data.json?id=".get("id")."&start=".get("start")."&end=".get("end")."&mode=".get("mode")."&apikey=".get("apikey"));
        break;
        
    case "value":
        header('Content-Type: text/plain');
        print file_get_contents("https://emoncms.org/feed/value.json?id=".get("id")."&apikey=".get("apikey"));
}
    
function get($index) {
    $val = null;
    if (isset($_GET[$index])) $val = $_GET[$index];
    if (get_magic_quotes_gpc()) $val = stripslashes($val);
    return $val;
}

class EmonLogger {
    public function __construct() {}
    public function info ($message){ }
    public function warn ($message){ }
}

