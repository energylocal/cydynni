<?php 

/* 

  Example of loading two emoncms feeds and showing them on a flot graph
  - Graph panning and zooming
  - Uses vis.helper.js
  - Uses feed.js

*/

global $path; 
$path = "";
$v = 1;

?>

<script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>

<script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.min.js"></script> 
<script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.time.min.js"></script> 
<script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.selection.min.js"></script> 
<script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.stack.min.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>lib/flot/date.format.js"></script> 
<script type="text/javascript" src="<?php echo $path; ?>lib/vis.helper.js?v=<?php echo $v; ?>"></script>

<script language="javascript" type="text/javascript" src="lib/feed.js?v=<?php echo $v; ?>"></script>

<style>

body {
  height:100%;
  margin:0;
  padding:0;
  
  font-family: Montserrat, Veranda, sans-serif;
}

.block-bound {
  color:#fff;
  background-color: #29aae3;
}

.block-title {
  font-weight:bold;
  padding:10px;
}


.col1 {
  width:100%;
}
.col1-inner { padding:10px; }

.col2 {
  width:50%;
  float:left;
}
.col2-inner { padding:10px; }

.bluenav {
  float:right;
  display:block;
  border-left: 1px solid rgba(255,255,255,0.5);
  font-weight:bold;
  font-size:14px;
  padding:11px;
  cursor:pointer;
  min-width:30px;
  text-align:center;
}

.bluenav:hover {
  background-color:rgba(255,255,255,0.2);
}

#controls-inner {
  padding-top:10px;
  padding-bottom:10px;
}

</style>

<body>

<div class="col1"><div class="col1-inner">
  <div class="block-bound">
  
    <div class="bargraph-navigation" style="display:none">
      <!--<div class="bluenav bargraph-other">OTHER</div>-->
      <div class="bluenav bargraph-alltime">ALL TIME</div>
      <div class="bluenav bargraph-month">MONTH</div>
      <div class="bluenav bargraph-week">WEEK</div>
    </div>
    
    <div class="powergraph-navigation" >
      <span class="bluenav" id="right" >></span>
      <span class="bluenav" id="left" ><</span>
      <span class="bluenav" id="zoomout" >-</span>
      <span class="bluenav" id="zoomin" >+</span>
      <span class="bluenav time" time='365'>Y</span>
      <span class="bluenav time" time='30'>M</span>
      <span class="bluenav time" time='7'>W</span>
    </div>
      
    <div class="block-title">HISTORY</div>
       
  </div>
  <div style="background-color:rgba(68,179,226,0.1)">
    <div id="legendholder" style="padding:5px 0px 0px 5px"></div>
    <div style="padding:10px;">
      <div id="placeholder_bound">
        <div id="placeholder"></div>
      </div>
    </div>
  </div>

</div></div>

</body>
        
<script>
var path = "https://dev.cydynni.org.uk/";
var apikey = "";
var window_height = 0;
var flot_font_size = 12;

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
    var hydro_data = feed.getaverage(1,view.start,view.end,interval,1,1);
    var community_data = feed.getaverage(2,view.start,view.end,interval,1,1);
    
    // -------------------------------------------------------------------------
    // Colour code graph
    // -------------------------------------------------------------------------

    var morning_data = [];
    var midday_data = [];
    var evening_data = [];
    var overnight_data = [];
    exported_hydro_data = [];
    used_hydro_data = [];

    for (var z in community_data) {    
        var time = community_data[z][0];    
        var d = new Date(time);
        var hour = d.getHours();

        // Set defaults to no power
        var val = 0;
        overnight_data[z] = [time,val];
        morning_data[z] = [time,val];
        midday_data[z] = [time,val];
        evening_data[z] = [time,val];
        overnight_data[z] = [time,val];
        exported_hydro_data[z] = [time,val];
        used_hydro_data[z] = [time,val];
        
        // When available hydro is more than community consumption
        if (hydro_data[z][1]>community_data[z][1]) {
            // Hydro export
            exported_hydro_data[z][1] = hydro_data[z][1] - community_data[z][1];
            // Hydro used
            used_hydro_data[z][1] = community_data[z][1];
            // No imported power at tariff periods:

        } else {
            // Hydro export
            exported_hydro_data[z] = [time,0];
            // Hydro used
            used_hydro_data[z] = [time,hydro_data[z][1]];
            // Grid import
            var grid_import = community_data[z][1] - hydro_data[z][1];
            // Import times
            if (hour<6) overnight_data[z] = [time,grid_import];
            if (hour>=6 && hour<11) morning_data[z] = [time,grid_import];
            if (hour>=11 && hour<16) midday_data[z] = [time,grid_import];
            if (hour>=16 && hour<20) evening_data[z] = [time,grid_import];
            if (hour>=20) overnight_data[z] = [time,grid_import];
        }
    }    
    
    // ----------------------------------------------------------------------------
    // FORECAST
    // ----------------------------------------------------------------------------
    hydro_forecast = [];
    community_forecast = [];
    
    var lasttime = 0;
    var lastvalue = 0;
    for (var z in hydro_data) {
        if (hydro_data[z][1]!=null) {
            lasttime = hydro_data[z][0];
            lastvalue = hydro_data[z][1];
        } 
    }
    
    if (((view.end-lasttime)*0.001)>1800) {
        // ----------------------------------------------------------------------------
        // HYDRO FORECAST USING YNNI PADARN PERIS DATA
        // ----------------------------------------------------------------------------
        $.ajax({                                      
            url: path+"hydro/forecast?start="+view.start+"&end="+view.end+"&interval="+interval+"&lasttime="+lasttime+"&lastvalue="+lastvalue,
            dataType: 'json', async: false, success: function(result) {
            hydro_forecast = result;
        }});
        
        // ----------------------------------------------------------------------------
        // CONSUMPTION FORECAST
        // ----------------------------------------------------------------------------
        var d1 = new Date();
        var t1 = d1.getTime()*0.001;
        if (view.end>0) t1 = view.end * 0.001;
        var d3 = new Date(lasttime);
        var t3 = d3.getTime()*0.001;
        var divisions_behind = Math.floor((t1 - t3) / interval);
        
        var community_forecast_raw = [];
        
        var time = lasttime;
        $.ajax({                                      
            url: path+"community/forecast?lasttime="+lasttime+"&interval="+interval,
            dataType: 'json',
            async: false,                      
            success: function(result) {
                var community_forecast_raw = result;
                var l = community_forecast_raw.length;
                
                community_forecast = [];
                for (var h=0; h<divisions_behind-1; h++) {
                    community_forecast.push([time+((h+1)*interval*1000),community_forecast_raw[h%l]]);
                }
        }});
    }
    // ----------------------------------------------------------------------------
    
    communityseries = [];
    
    var widthprc = 0.75;
    var barwidth = widthprc*interval*1000;

    // Forecast
    communityseries.push({
        data: hydro_forecast, color: "#dadada", label: "Hydro Forecast",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        data: community_forecast, color: "#aaa", label: "Community Forecast",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 0.4, lineWidth:0}
    });
    
    // Actual
    communityseries.push({
        stack: true, data: used_hydro_data, color: "#29aae3", label: "Used Hydro",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: overnight_data, color: "#014c2d", label: "Overnight Tariff",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: morning_data, color: "#ffb401", label: "Morning Tariff",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: midday_data, color: "#4dac34", label: "Midday Tariff",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: evening_data, color: "#e6602b", label: "Evening Tariff",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    communityseries.push({
        stack: true, data: exported_hydro_data, color: "#a5e7ff", label: "Exported Hydro",
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });

    draw();
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
            min:0,
            max:50
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
    
    
    $.plot("#placeholder",communityseries, options);
}

//------------------------------------------------------------------------------------
// Events
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
            
            // Non forecast part of the graph
            if (selected_series!="Hydro Forecast" && selected_series!="Community Forecast") {
                // Draw non forecast tooltip
                var total_consumption = 0;
                for (var i in communityseries) {
                    var series = communityseries[i];
                    // Only show tooltip item if defined and more than zero
                    if (series.data[z]!=undefined && series.data[z][1]>0) {
                        if (series.label!="Hydro Forecast" && series.label!="Community Forecast") {
                            out += series.label+ ": "+(series.data[z][1]*1).toFixed(1)+"kWh<br>";
                            if (series.label!="Exported Hydro") total_consumption += series.data[z][1]*1;
                        }
                    }
                }
                if (total_consumption) out += "Total consumption: "+(total_consumption).toFixed(1)+"kWh";
            
            } else {
                // Print forecast amounts
                out += communityseries[0].label+ ": "+(communityseries[0].data[z][1]*1).toFixed(1)+"kWh<br>";
                out += communityseries[1].label+ ": "+(communityseries[1].data[z][1]*1).toFixed(1)+"kWh<br>";
            }
            tooltip(item.pageX,item.pageY,out,"#fff");
        }
    } else $("#tooltip").remove();
});

$(window).resize(function(){ 
    window_height = $(this).height();
    resize(); 
    draw(); 
});

function resize() {
    var window_width = $('#placeholder_bound').width();
    var controls_height = $("#controls").height();
    
    flot_font_size = 12;
    if (window_width<450) flot_font_size = 10;
    
    var height =  window_width * 0.45;
    
    $('#placeholder').width(window_width);
    $('#placeholder_bound').height(height);
    $('#placeholder').height(height);
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
</script>

