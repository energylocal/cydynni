<?php 

global $path,$translation, $lang; 
$path = "";
$v = 1;

?>
<!doctype html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CydYnni Dashboard</title>
    
    <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>

    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.min.js"></script> 
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.time.min.js"></script> 
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.selection.min.js"></script> 
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.stack.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/date.format.js"></script> 
    <script type="text/javascript" src="<?php echo $path; ?>lib/vis.helper.js?v=<?php echo $v; ?>"></script>

    <script language="javascript" type="text/javascript" src="lib/feed.js?v=<?php echo $v; ?>"></script>
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Montserrat&amp;lang=en" />
    <link rel="stylesheet" type="text/css" href="<?php echo $path; ?>style.css?v=<?php echo $v; ?>" />  
</head>
<body>

<div class="container">

  <div class="app-title"><?php echo t("Energy<br>Dashboard"); ?></div>
  <img src='images/<?php echo t("EnergyLocalEnglish"); ?>.png' style="width:260px; padding:10px"><br>

  <!---------------------------------------------------------------------------------->
  <!-- LIVE OVERVIEW                                                                -->
  <!---------------------------------------------------------------------------------->
  <div class="col1"><div class="col1-inner">
      
    <div class="block-bound">
      <span class="bluenav togglelang" >EN</span>
      <div class="block-title" style="float:right">(<?php echo t("Estimated"); ?>)</div>
      <div class="block-title"><?php echo t("NOW"); ?>: <span id="tariff"></span> (<span id="price"></span>)</div>
    </div>

    <div style="background-color:#fff; color:#333; padding:10px;">
      <table style="width:100%">
        <tr>
          <td style="width:40%">
            <div class="electric-title"><?php echo t("HYDRO"); ?></div>
            <div class="power-value"><span id="hydro_now">0</span> kW</div>
          </td>
          <td style="text-align:right">
            <div class="electric-title"><?php echo t("COMMUNITY"); ?></div>
            <div class="power-value"><span id="community_now">0</span> kW</div>
          </td>
        </tr>
      </table>
    </div>
    
  </div></div>

  <!---------------------------------------------------------------------------------->
  <!-- HISTORIC VIEW                                                                -->
  <!---------------------------------------------------------------------------------->
  <div class="col1"><div class="col1-inner">
    
    <div class="block-bound">
      
      <div class="powergraph-navigation" >
        <span class="bluenav" id="right" >></span>
        <span class="bluenav" id="left" ><</span>
        <span class="bluenav" id="zoomout" >-</span>
        <span class="bluenav" id="zoomin" >+</span>
        <span class="bluenav time" time='365'>Y</span>
        <span class="bluenav time" time='30'>M</span>
        <span class="bluenav time" time='7'>W</span>
      </div>
        
      <div class="block-title"><?php echo t("HISTORY"); ?></div>
         
    </div>
    <div style="background-color:rgba(68,179,226,0.1)">
      <div id="legendholder" style="padding:5px 0px 0px 5px"></div>
      <div style="padding:10px;">
        <div id="placeholder_bound">
          <div id="placeholder"></div>
        </div>
      </div>
    </div>
    <br>
 
  <!---------------------------------------------------------------------------------->
  <!-- HISTORIC VIEW STATS                                                          -->
  <!---------------------------------------------------------------------------------->
    <div class="block-bound">
      <div class="block-title"><?php echo t("IN THE SELECTED GRAPH WINDOW"); ?></div>
    </div>
    
    <div style="background-color:#fff; color:#333; padding:10px;">
      <table style="width:100%">
        <tr>
          <td style="text-align:left">
              <div class="totals"><?php echo t("Total hydro"); ?>: <b><span id="total_hydro"></span></b> kWh</div>
              <div class="totals"><?php echo t("Used hydro"); ?>: <b><span id="total_used_hydro"></span></b> kWh</div>
              <div class="totals"><?php echo t("Community"); ?>: <b><span id="total_community"></span></b> kWh</div>
          </td>
          <td style="text-align:center">
              <div class="electric-title"><?php echo t("DEMAND FROM<br>HYDRO"); ?></div>
              <div class="power-value"><span id="prc_supply_hydro">0</span>%</div>
          </td>
          <td style="text-align:center">
              <div class="electric-title"><?php echo t("HYDRO CAPACITY<br>FACTOR"); ?></div>
              <div class="power-value"><span id="hydro_capacity_factor">0</span>%</div>
          </td>
        </tr>
      </table>
    </div>

  </div></div>

</div>


<div id="footer">
    <?php echo t('Powered by '); ?>
    <a href="http://openenergymonitor.org">OpenEnergyMonitor.org</a>
</div>

</body>
</html>
        
<script>

var path = "https://dev.cydynni.org.uk/";
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";
var apikey = "";
// Faster initial load of current status, loaded via ajax from this point on, see below
var live = <?php echo json_encode($live); ?>;

var flot_font_size = 12;

// Change mode between kW and kWh
var units = "kW";

update();

// Initial view range 24 hours
view.end = +new Date;
view.start = view.end - (3600000*24.0*7);

var communityseries = [];

// Used for plot hover
var previousPoint = false;

// Load initial graph dimentions
resize();
// Initial load
load();

// Auto update live status
setInterval(update,60000);
function update() {

    $.ajax({                                      
        url: path+"live",
        dataType: 'json',
        async: true,                      
        success: function(result) {
            live = result;

            var hydro_now = live.hydro*1;
            var community_now = live.community*1;
            $("#hydro_now").html(hydro_now.toFixed(1));
            $("#community_now").html(community_now.toFixed(1));
            $("#tariff").html(t(live.tariff.toUpperCase()+" TARIFF"));
            
            var price = "";
            if (live.tariff=="morning") price = 12;
            if (live.tariff=="midday") price = 10;
            if (live.tariff=="evening") price = 14;
            if (live.tariff=="overnight") price = 7.25;
            if (live.tariff=="hydro") price = 7.0;
            
            $("#price").html(price+"p/kWh");
    }});
}

//------------------------------------------------------------------------------------
// Load:
//------------------------------------------------------------------------------------
function load() {

    var npoints = 200;
    interval = ((view.end - view.start) * 0.001) / npoints;
    interval = round_interval(interval);
    
    // Limit interval to 1800s
    if (interval<1800) interval = 1800;
    var intervalms = interval * 1000;
    
    // Start and end time rounding
    view.end = Math.floor(view.end / intervalms) * intervalms;
    view.start = Math.floor(view.start / intervalms) * intervalms;

    // Load data from server
    var hydro_data = feed.getaverage(1,view.start,view.end,interval,0,1);
    var community_data = feed.getaverage(2,view.start,view.end,interval,0,1);
    
    console.log(hydro_data.length+" "+community_data.length);
    
    // -------------------------------------------------------------------------
    // Colour code graph
    // -------------------------------------------------------------------------

    // kWh scale
    var scale = 1;
    if (units=="kWh") scale = (interval / 1800);
    if (units=="kW") scale = 2;

    var morning_data = [];
    var midday_data = [];
    var evening_data = [];
    var overnight_data = [];
    exported_hydro_data = [];
    used_hydro_data = [];
    
    var total_hydro = 0;
    var total_used_hydro = 0;
    var total_community = 0;
    var total_time = 0;
    
    var hydro = 0;
    var community = 0;

    for (var z in community_data) {    
        var time = community_data[z][0];    
        var d = new Date(time);
        var hour = d.getHours();
        
        if (hydro_data[z]!=undefined) 
            hydro = hydro_data[z][1] * scale;
            
        if (community_data[z]!=undefined) 
            community = community_data[z][1] * scale;
        
        var overnight = 0;
        var morning = 0;
        var midday = 0;
        var evening = 0;
        var exported_hydro = 0;
        var used_hydro = 0;

        // When available hydro is more than community consumption
        if (hydro>community) {
            // Hydro export
            exported_hydro = hydro - community;
            // Hydro used
            used_hydro = community;
            // No imported power at tariff periods:

        } else {
            // Hydro used
            used_hydro = hydro;
            // Grid import
            var grid_import = community - hydro;
            // Import times
            if (hour<6) overnight = grid_import;
            if (hour>=6 && hour<11) morning = grid_import;
            if (hour>=11 && hour<16) midday = grid_import;
            if (hour>=16 && hour<20) evening = grid_import;
            if (hour>=20) overnight = grid_import;
        }

        overnight_data[z] = [time,overnight];
        morning_data[z] = [time,morning];
        midday_data[z] = [time,midday];
        evening_data[z] = [time,evening];
        exported_hydro_data[z] = [time,exported_hydro];
        used_hydro_data[z] = [time,used_hydro];
        
        if (units=="kW") {
            total_hydro += hydro * (interval/3600);
            total_community += community * (interval/3600);
            total_used_hydro += used_hydro * (interval/3600);
        } else {
            total_hydro += hydro;
            total_community += community;
            total_used_hydro += used_hydro;
        }
        total_time += interval;
    }    
    
    // ----------------------------------------------------------------------------
    // estimate
    // ----------------------------------------------------------------------------
    hydro_estimate = [];
    community_estimate = [];
    
    var lasttime = 0;
    var lastvalue = 0;
    for (var z in hydro_data) {
        if (hydro_data[z][1]!=null) {
            lasttime = hydro_data[z][0];
            lastvalue = hydro_data[z][1];
        } 
    }
    
    if ((((new Date()).getTime()-view.end)<3600*1000*48) && ((view.end-lasttime)*0.001)>1800) {
        // ----------------------------------------------------------------------------
        // HYDRO estimate USING YNNI PADARN PERIS DATA
        // ----------------------------------------------------------------------------
        $.ajax({                                      
            url: path+"hydro/estimate?start="+view.start+"&end="+view.end+"&interval="+interval+"&lasttime="+lasttime+"&lastvalue="+lastvalue,
            dataType: 'json', async: false, success: function(result) {
            hydro_estimate = result;
            
            for (var z in hydro_estimate) {
                hydro_estimate[z][1] = hydro_estimate[z][1] * scale;
            }
        }});
        
        // ----------------------------------------------------------------------------
        // CONSUMPTION estimate
        // ----------------------------------------------------------------------------
        var d1 = new Date();
        var t1 = d1.getTime()*0.001;
        if (view.end>0) t1 = view.end * 0.001;
        var d3 = new Date(lasttime);
        var t3 = d3.getTime()*0.001;
        var divisions_behind = Math.floor((t1 - t3) / interval);
        
        var community_estimate_raw = [];
        
        var time = hydro_estimate[0][0];
        
        $.ajax({                                      
            url: path+"community/estimate?lasttime="+lasttime+"&interval="+interval,
            dataType: 'json',
            async: false,                      
            success: function(result) {
                var community_estimate_raw = result;
                var l = community_estimate_raw.length;
                
                community_estimate = [];
                for (var h=0; h<divisions_behind; h++) {
                    community_estimate.push([time+(h*interval*1000),community_estimate_raw[h%l]*scale]);
                }
        }});
        
        // var hydro_now = hydro_estimate[hydro_estimate.length-1][1];
        // var community_now = community_estimate[community_estimate.length-1][1];
       
    }
    // ----------------------------------------------------------------------------
    
    communityseries = [];
    
    var widthprc = 0.75;
    var barwidth = widthprc*interval*1000;
    
    // Actual
    communityseries.push({
        stack: true, data: used_hydro_data, color: "#29aae3", label: t("Used Hydro"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: overnight_data, color: "#014c2d", label: t("Overnight Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: morning_data, color: "#ffb401", label: t("Morning Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: midday_data, color: "#4dac34", label: t("Midday Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: evening_data, color: "#e6602b", label: t("Evening Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: exported_hydro_data, color: "#a5e7ff", label: t("Exported Hydro"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });

    // estimate
    communityseries.push({
        data: hydro_estimate, color: "#dadada", label: t("Hydro estimate"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        data: community_estimate, color: "#aaa", label: t("Community estimate"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 0.4, lineWidth:0}
    });
    
    draw();
    
    var full_output = (total_time/3600) * 100
    $("#hydro_capacity_factor").html(Math.round((total_hydro/full_output)*100));
    
    $("#total_hydro").html(Math.round(total_hydro));
    $("#total_community").html(Math.round(total_community));
    $("#total_used_hydro").html(Math.round(total_used_hydro));
    $("#prc_supply_hydro").html(Math.round((total_used_hydro/total_community)*100));
    // $("#prc_hydro_used").html(Math.round((total_used_hydro/total_hydro)*100));
}

//------------------------------------------------------------------------------------
// Draw is seperated out so that it can be redrawn on resize
//------------------------------------------------------------------------------------
function draw() {

    var options = {
        legend: { show: true, noColumns: 8, container: $('#legendholder') },
        xaxis: { 
            mode: "time", 
            timezone: "browser", 
            font: {size:flot_font_size, color:"#666"}, 
            // labelHeight:-5
            reserveSpace:false,
            min: view.start,
            max: view.end
        },
        yaxis: { 
            font: {size:flot_font_size, color:"#666"}, 
            // labelWidth:-5
            reserveSpace:false,
            min:0
        },
        selection: { mode: "x" },
        grid: {
            show:true, 
            color:"#aaa",
            borderWidth:0,
            hoverable: true, 
            clickable: true
        }
    }
    
    if (units=="kW") options.yaxis.max = 100;
    
    $.plot("#placeholder",communityseries, options);
}

//------------------------------------------------------------------------------------
// Historic graph Events
//------------------------------------------------------------------------------------
$("#zoomout").click(function () {view.zoomout(); load();});
$("#zoomin").click(function () {view.zoomin(); load();});
$('#right').click(function () {view.panright(); load();});
$('#left').click(function () {view.panleft(); load();});
$('.time').click(function () {view.timewindow($(this).attr("time")); load();});

$('#placeholder').bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    load();
});

$('#placeholder').bind("plothover", function (event, pos, item) {
    if (item) {
        var z = item.dataIndex;
        var selected_series = communityseries[item.seriesIndex].label;
        
        if (previousPoint != item.datapoint) {
            previousPoint = item.datapoint;

            $("#tooltip").remove();
            
            // Date and time
            var itemTime = item.datapoint[0];
            var d = new Date(itemTime);
            var days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
            var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
            var mins = d.getMinutes();
            if (mins==0) mins = "00";
            var date = d.getHours()+":"+mins+" "+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate();
            
            var out = date+"<br>";
                        
            // Non estimate part of the graph
            if (selected_series!=t("Hydro estimate") && selected_series!=t("Community estimate")) {

                // Draw non estimate tooltip
                var total_consumption = 0;
                for (var i in communityseries) {
                    var series = communityseries[i];
                    // Only show tooltip item if defined and more than zero
                    if (series.data[z]!=undefined && series.data[z][1]>0) {
                        if (series.label!=t("Hydro estimate") && series.label!=t("Community estimate")) {
                            out += series.label+ ": "+(series.data[z][1]*1).toFixed(1)+units+"<br>";
                            if (series.label!=t("Exported Hydro")) total_consumption += series.data[z][1]*1;
                        }
                    }
                }
                if (total_consumption) out += t("Total consumption: ")+(total_consumption).toFixed(1)+units;
            
            } else {
                // Print estimate amounts
                out += communityseries[6].label+ ": "+(communityseries[6].data[z][1]*1).toFixed(1)+units+"<br>";
                out += communityseries[7].label+ ": "+(communityseries[7].data[z][1]*1).toFixed(1)+units+"<br>";
            }
            tooltip(item.pageX,item.pageY,out,"#fff");
        }
    } else $("#tooltip").remove();
});

$(window).resize(function(){ 
    resize(); 
    draw(); 
});

function resize() {
    var window_width = $('#placeholder_bound').width();
    var width = window_width;
    var controls_height = $("#controls").height();
    
    flot_font_size = 12;
    if (window_width<450) flot_font_size = 10;
    
    var height =  window_width * 0.45;
    
    $('#placeholder').width(window_width);
    $('#placeholder_bound').height(height);
    $('#placeholder').height(height);
    
    if (width<=500) {
        $(".electric-title").css("font-size","16px");
        $(".power-value").css("font-size","38px");
    } else if (width<=724) {
        $(".electric-title").css("font-size","18px");
        $(".power-value").css("font-size","52px");
    } else {
        $(".electric-title").css("font-size","22px");
        $(".power-value").css("font-size","52px");
    }
}

function round_interval(interval) {
    var outinterval = 1800;
    if (interval>3600*1) outinterval = 3600*1;
    
    if (interval>3600*2) outinterval = 3600*2;
    if (interval>3600*3) outinterval = 3600*3;
    if (interval>3600*4) outinterval = 3600*4;
    if (interval>3600*5) outinterval = 3600*5;
    if (interval>3600*6) outinterval = 3600*6;
    if (interval>3600*12) outinterval = 3600*12;
    
    if (interval>3600*24) outinterval = 3600*24;
    
    if (interval>3600*36) outinterval = 3600*36;
    if (interval>3600*48) outinterval = 3600*48;
    if (interval>3600*72) outinterval = 3600*72;

    return outinterval;
}

//------------------------------------------------------------------------------------
// TRANSLATION CONTROL
//------------------------------------------------------------------------------------
if (lang=="cy") {
    $(".togglelang").html("EN");
} else {
    $(".togglelang").html("CY");
}

// Language selection
$(".togglelang").click(function(){
    var ilang = $(this).html();
    if (ilang=="CY") {
        $(this).html("EN");
        window.location = "?iaith=cy";
    } else {
        $(this).html("CY");
        lang="cy";
        window.location = "?iaith=en";
    }
});

// Javascript text translation function
function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}
</script>

