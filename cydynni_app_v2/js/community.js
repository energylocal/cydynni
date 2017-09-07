/*

Community page

*/

var start = 0;
var end = 0;
var apikey = "";
var units = "kW";

var community_data = [];
var exported_hydro_data = [];
var used_hydro_data = [];
var communityseries = [];

var community_score = -1;
var community_hydro_use = 0;
var community_view = "bargraph";
var community_height = 0;

// Initial view range 24 hours
view.end = +new Date;
view.start = view.end - (3600000*24.0*7);

function community_bargraph_load() {

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

    for (var z in community_data) {    
        var time = community_data[z][0];    
        var d = new Date(time);
        var hour = d.getHours();
        
        var hydro = hydro_data[z][1] * scale;
        var community = community_data[z][1] * scale;
        
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
    
    community_bargraph_draw();
    
    // var full_output = (total_time/3600) * 100
    // $("#hydro_capacity_factor").html(Math.round((total_hydro/full_output)*100));
    
    // $("#total_hydro").html(Math.round(total_hydro));
    // $("#total_community").html(Math.round(total_community));
    // $("#total_used_hydro").html(Math.round(total_used_hydro));
    // $("#prc_supply_hydro").html(Math.round((total_used_hydro/total_community)*100));
    // $("#prc_hydro_used").html(Math.round((total_used_hydro/total_hydro)*100));
}

function community_resize(panel_height) 
{
    community_pie_draw();
    community_bargraph_resize(panel_height-70);

    var width = $(window).width();
    
    var shorter_summary = 480;

    if (community_score!=-1) {
        if (community_score<30) {
            if (width>shorter_summary) { 
                $("#community_status_summary").html(t("As a community we are MISSING OUT"));
            } else {
                $("#community_status_summary").html(t("We are MISSING OUT"));
            }
        }
        if (community_score>=30 && community_score<70) {
            if (width>shorter_summary) { 
                $("#community_status_summary").html(t("As a community we are <b>DOING OK</b>"));
            } else {
                $("#community_status_summary").html(t("We are <b>DOING OK</b>"));
            }
        }
        if (community_score>=70) {
            if (width>shorter_summary) { 
                $("#community_status_summary").html(t("As a community we are <b>DOING WELL</b>"));
            } else {
                $("#community_status_summary").html(t("We are <b>DOING WELL</b>"));
            }
        }
    }
}

function community_bargraph_resize(h) {

    var window_width = $(window).width();
    flot_font_size = 12;
    if (window_width<450) flot_font_size = 10;

    width = $("#community_bargraph_bound").width();
    $("#community_bargraph_placeholder").width(width);
    $('#community_bargraph_bound').height(h);
    $('#community_bargraph_placeholder').height(h);
    height = h;
    community_bargraph_draw();
}

function community_bargraph_draw() {

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
    
    if ($("#community_bargraph_placeholder").width()>0) {
    
    $.plot("#community_bargraph_placeholder",communityseries, options);
    
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

$(".community-left").click(function() {
    var time_window = view.end - view.start;
    view.end -= time_window * 0.5;
    view.start -= time_window * 0.5;
    community_bargraph_load();
});

$(".community-right").click(function() {
    var time_window = view.end - view.start;
    view.end += time_window * 0.5;
    view.start += time_window * 0.5;
    community_bargraph_load();
});

$(".community-day").click(function() {
    end = 0;
    start = 0;
    community_bargraph_load();
});

$(".community-week").click(function() {
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*7);
    community_bargraph_load();
});

$(".community-month").click(function() {
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*30);
    community_bargraph_load();
});

$(".community-year").click(function() {
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*365);
    community_bargraph_load();
});

$('#community_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    community_bargraph_load();
});

$('#community_bargraph_placeholder').bind("plothover", function (event, pos, item) {
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
