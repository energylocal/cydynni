<?php
global $user;

if ($session["write"]) {
    $u = $user->get($session["userid"]);

    $credentials = array(
        "userid"=>$u->id,
        "username"=>$u->username,
        "password"=>$u->apikey_write
    );
}

?>

<div style="background-color:#eee; max-width:600px; padding:20px; margin-top:20px">

<h2>Setup Smartplug</h2>

<p>This tool can be used to pair a Smartplug with your EnergyLocal account.</p>
<p>Keep this window open when you switch to the WiFi access point created by your Smart plug and this page will automatically transfer the correct credentials.</p>

<p id="http-error-notice" style="display:none"><b><i class="icon-remove-sign" style="margin-top:-1px"></i> Mixed content protection is enabled in your browser</b><br>For this tool to work, please temporarily disable non-secure request protection in your browser. Click on the padlock icon next to the dashboard URL above, then 'Connection Secure' and finally 'Disable protection for now'.</p>

<p id="http-ok-notice" style="display:none"><b><i id="http-ok-icon" class="icon-ok-sign" style="margin-top:-1px"></i> Mixed content protection is disabled in your browser</b></p>

<p id="smartplug-not-detected-notice" style="display:none"><b><i class="icon-remove-sign" style="margin-top:-1px"></i> Smart plug not detected</b><br>Please switch to the WiFi access point created by the smart plug.</p>

<p id="smartplug-detected-notice" style="display:none"><b><i class="icon-ok-sign" style="margin-top:-1px"></i> Smart plug ready to configure</b></p>

<p id="smartplug-configured-notice" style="display:none"><b><i class="icon-ok-sign" style="margin-top:-1px"></i> Smart plug configured, please check devices page</b></p>

<p id="http-revert-notice" style="display:none"><b><i class="icon-remove-sign" style="margin-top:-1px"></i> Mixed content protection is disabled in your browser.</b><br>Please re-enable protection. Click on the padlock icon next to the dashboard URL above, then 'Connection not secure' and finally 'Enable protection'. </b></p>


<div id="configuration-options" style="display:none">
    <label>Pair with account:</label>
    <input type="text" value="<?php echo $u->username; ?>" disabled />

    <label>Available Networks:</label>
    <div class="input-append">
        <select id="networks" style="display:none"></select>
        <button class="btn" id="scan">Scan</button>
    </div>
    <label>Selected or custom SSID:</label>
    <input type="text" id="ssid"/>

    <label>Passkey:</label>
    <input type="text" id="pass"/>

    <br>
    <button class="btn" id="configure">Configure</button>
</div>

</div>

<script>


var credentials = <?php echo json_encode($credentials); ?>;

var path = "http://192.168.4.1/";

var timer = {
    timer_start1:0,
    timer_stop1:0,
    timer_start2:0,
    timer_stop2:0,
    voltage_output:0,
    time_offset:0
};

var mqtt = {
    enable: 1,
    server: "dashboard.energylocal.org.uk",
    port: 1883,
    topic: "user/"+credentials.userid,
    prefix: "",
    user: credentials.username,
    pass: credentials.password
}

var wifi = {
    ssid: "",
    pass: ""
}

var networks = false;

var http_ok = false;
var smart_plug_detected = false;
var smart_plug_configured = false;


check_for_smartplug();
var check_interval = setInterval(check_for_smartplug,2000);

function check_for_smartplug() {
    $.ajax({ 
        url: path+"emoncms/describe", dataType: 'text', async:true, timeout: 1000, success: function(result){

            if (!smart_plug_configured) {
                $("#http-ok-notice").show();
                http_ok = true;
            }
            
            $("#smartplug-not-detected-notice").hide();
            
            if (result=="smartplug") {
                smart_plug_detected = true;
                $("#smartplug-detected-notice").show();
                
                if (!smart_plug_configured) {
                    $("#configuration-options").show();
                }
                
                if (!networks) {
                    fetchWiFiList();
                }
                
            } else {
                // $("#smartplug-detected-notice").hide();
                // $("#configuration-options").hide(); 
            }
            
        },
        error: function(xhr, status, error) {
            
            console.log(xhr.readyState)
            console.log(xhr)
            console.log(status)
            console.log(error)
            
            if (!smart_plug_configured) $("#smartplug-not-detected-notice").show();
            $("#smartplug-detected-notice").hide();
            $("#configuration-options").hide();         
            
            if (error=="timeout") {
                $("#http-ok-notice").show();
                http_ok = true;
            } else {
                if (!http_ok) $("#http-error-notice").show();
            }
        }
    });
}

$("#configure").click(function() {

    wifi.ssid = $("#ssid").val();
    wifi.pass = $("#pass").val();
    

    $.ajax({ 
        url: path+"emoncms/describe", dataType: 'text', async:false, success: function(result){
            if (result=="smartplug") {

                /*
                $.ajax({ 
                    url: path+"status", dataType: 'json', async:false, success: function(result){
                        console.log(result)
                    }
                });

                $.ajax({
                    url: path+"config", dataType: 'json', async:false, success: function(result){
                        console.log(result)
                    }
                });*/

                saveTimer(timer,function(result) {
                    savemqtt(mqtt,function(result){
                        savenetwork(wifi,function(result){
                            $("#smartplug-configured-notice").show();
                            $("#smartplug-detected-notice").hide();           
                            $("#configuration-options").hide();
                            $("#http-ok-notice").hide();
                            $("#http-revert-notice").show();
                            smart_plug_configured = true;
                            clearInterval(check_interval);
                        });
                    });
                });
            }
        }
    });
});

$("#networks").change(function() {
    $("#ssid").val($("#networks").val());
});

$("#scan").click(function() {
    fetchWiFiList();
});

function fetchWiFiList() {
    $.ajax({
        url: path+"scan", dataType: 'json', success: function(result){
            networks = result;
            if (networks.length) {
                networks.sort(function(a,b){
                    if (a.rssi<b.rssi) {
                        return 1;
                    }
                    if (a.rssi>b.rssi) {
                        return -1;
                    }
                    return 0;
                });
                
                console.log(result)
                
                var out = "";
                for (var n in networks) {
                    out += "<option>"+networks[n].ssid+"</option>";
                }
                $("#networks").html(out).show();
                $("#ssid").val(networks[0].ssid);
            }
        }
    });
}

function saveTimer(data,callback) {
    $.ajax({
        url: path+"savetimer", data:data, dataType: 'text', success: function(result){
            console.log(result)
            callback(result);
        }
    });
}

function savenetwork(data,callback) {
    data.ssid = encodeURIComponent(data.ssid);
    data.pass = encodeURIComponent(data.pass);
    $.ajax({
        url: path+"savenetwork", data:data, dataType: 'text', success: function(result){
            console.log(result)
            callback(result);
        }
    });
}

function savemqtt(data,callback) {
    $.ajax({
        url: path+"savemqtt", data:data, dataType: 'text', success: function(result){
            console.log(result)
            callback(result);
        }
    });
}
</script>
