/*

Household page

*/

var household_end = +new Date;
var household_start = household_end - (3600000*24.0*120);

var household_power_end = +new Date;
var household_power_start = household_power_end - (3600000*12.0);
var household_power_feedid = 0;
var household_updater = false;

var household_pie_data_cost = [];
var household_pie_data_energy = [];

var householdseries = [];
var householdpowerseries = [];

// var household_overnight_data = [];
// var household_morning_data = [];
// var household_evening_data = [];
// var household_midday_data = [];

var household_tariff_data = {};

var household_data = [];
var household_result = [];

var mode = "daily";

var household_firstload = true;

function household_summary_load()
{    

  if (session.feeds.hub_use!=undefined) {

      household_power_feedid = session.feeds.hub_use
      household_powergraph_load();
      
      household_realtime();
      clearInterval(household_updater);
      household_updater = setInterval(household_realtime,5000);
      
  } else if (session.feeds.meter_power!=undefined) {
      
      household_power_feedid = session.feeds.meter_power
      household_powergraph_load();
      
      household_realtime();
      clearInterval(household_updater);
      household_updater = setInterval(household_realtime,5000);
  
  } else {
      $("#realtime-power").hide(); 
  }
}

function household_draw_summary(day) {

    if (day==undefined) return false;
    var time = day[0];

    // Work out which tariff version we are on
    var history_index = 0;
    for (var tr=0; tr<club_settings.tariff_history.length; tr++) {
        let s = club_settings.tariff_history[tr]['start'];
        let e = club_settings.tariff_history[tr]['end'];
        if (time>=s && time<e) history_index = tr;
    }
    var tariff_bands = club_settings.tariff_history[history_index].tariffs;
    
    var d = new Date(time*1000);
    var months_long = ["January","February","March","April","May","June","July","August","September","October","November","December"];

    household_pie_data_cost = [];
    household_pie_data_energy = [];
    
    // Calculate total hydro consumption and total hydro cost made up of sub tariff bands
    var total_hydro = 0;
    var total_hydro_cost = 0;
    
    for (var tr=0; tr<tariff_bands.length; tr++) {
        total_hydro += day[1][tr]-day[2][tr]
        total_hydro_cost += (day[1][tr]-day[2][tr])*tariff_bands[tr].generator*0.01
    }
    
    // Create aggregated legend item for hydro
    var legend = "";
    legend += '<tr>'
    legend += '<td><div class="key" style="background-color:'+club_settings.generator_color+'"></div></td>'
    legend += '<td><b>'+t(ucfirst(club_settings.generator)+" Price")+'</b><br>'
    legend += total_hydro.toFixed(2)+" kWh "
    if (total_hydro>0) legend += "@"+(100*total_hydro_cost/total_hydro).toFixed(2)+" p/kWh"
    legend += "<br>"
    legend += t("Costing")+" £"+total_hydro_cost.toFixed(2)+'</td>'
    legend += '</tr>'
    
    var total_day_cost = total_hydro_cost
    var total_low_cost = total_hydro_cost
    // Assemble pie chart data
    for (var tr=0; tr<tariff_bands.length; tr++) {
        household_pie_data_energy.push({
            name:t(tariff_bands[tr].name.toUpperCase()), 
            generation: day[1][tr]-day[2][tr], 
            import: day[2][tr], 
            color:tariff_bands[tr].color
        });

        household_pie_data_cost.push({
            name:t(tariff_bands[tr].name.toUpperCase()), 
            generation: (day[1][tr]-day[2][tr])*tariff_bands[tr].generator*0.01,
            import: day[2][tr]*tariff_bands[tr].import*0.01,
            color:tariff_bands[tr].color
        });
        
        if(['overnight','morning','midday','daytime','standard'].indexOf(tariff_bands[tr].name) != -1) {  
           total_low_cost += day[2][tr]*tariff_bands[tr].import*0.01
        }
        
        // Legend for each import tariff band
        legend += '<tr>'
        legend += '<td><div class="key" style="background-color:'+tariff_bands[tr].color+'"></div></td>'
        legend += '<td><b>'+t(ucfirst(t(tariff_bands[tr].name)+" Price"))+'</b><br>'
        legend += day[2][tr].toFixed(2)+" kWh @"+(tariff_bands[tr].import.toFixed(2))+" p/kWh<br>"
        legend += t("Costing")+" £"+(day[2][tr]*tariff_bands[tr].import*0.01).toFixed(2)+'</td>'
        legend += '</tr>'
        
        total_day_cost += day[2][tr]*tariff_bands[tr].import*0.01;
    }
    
    $("#household_pie_legend").html(legend);  
    
    // 1. Determine score
    // Calculated as amount of power consumed at times off peak times and from generation
    var score = Math.round(100*(total_low_cost / total_day_cost));

    if (score>=20) star1 = "starred"; else star1 = "star20red";
    if (score>=40) star2 = "starred"; else star2 = "star20red";
    if (score>=60) star3 = "starred"; else star3 = "star20red";
    if (score>=80) star4 = "starred"; else star4 = "star20red";
    if (score>=90) star5 = "starred"; else star5 = "star20red";

    $("#household_star1").attr("src",app_path+"images/"+star1+".png");
    setTimeout(function() { $("#household_star2").attr("src",app_path+"images/"+star2+".png"); }, 100);
    setTimeout(function() { $("#household_star3").attr("src",app_path+"images/"+star3+".png"); }, 200);
    setTimeout(function() { $("#household_star4").attr("src",app_path+"images/"+star4+".png"); }, 300);
    setTimeout(function() { $("#household_star5").attr("src",app_path+"images/"+star5+".png"); }, 400);

    // Show status summary ( below score stars )
    setTimeout(function() {
        if (score<30) {
            $(".household_status").html(t("You are using power in a very expensive way"));
        }
        if (score>=30 && score<70) {
            $(".household_status").html(t("You’re doing ok at using "+club_settings.generator+" & cheaper power.<br>Can you move more of your use away from peak times?"));
        }
        if (score>=70) {
            $(".household_status").html(t("You’re doing really well at matching your use to local electricity and cheap times for extra electricity"));
        }
    }, 400);
    
    $(".household_score").html(score);
    
    var ext = "";
    if (d.getDate()==1) ext = "st";
    if (d.getDate()==2) ext = "nd";
    if (d.getDate()==3) ext = "rd";
    if (d.getDate()>3) ext = "th";
    if (lang=="cy_GB") ext = "";    

    $(".household_date").html(d.getDate()+ext+" "+t(months_long[d.getMonth()]));   
    
    var total_day_kwh = day[1][tariff_bands.length]; 
    $(".household_totalkwh").html(total_day_kwh.toFixed(2));
    $(".household_totalcost").html("£"+total_day_cost.toFixed(2));
    
    // Saving calculation
    var totalcostflatrate = total_day_kwh * 0.1544;
    var costsaving = totalcostflatrate - total_day_cost;
    $(".household_costsaving").html("£"+costsaving.toFixed(2));
    
    household_pie_draw();
}

function household_pie_draw() {

    width = 300;
    height = 300;

    $("#household_piegraph1_placeholder").attr('width',width);
    $("#household_piegraph2_placeholder").attr('width',width);
    $('#household_piegraph1_placeholder').attr("height",height);
    $('#household_piegraph2_placeholder').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    };

    pie_generator_color = club_settings.generator_color;
    piegraph3("household_piegraph1_placeholder",household_pie_data_energy,options);
    piegraph3("household_piegraph2_placeholder",household_pie_data_cost,options);


    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: 50
    };
    
    hrbar("household_hrbar1_placeholder",household_pie_data_energy,options); 
    hrbar("household_hrbar2_placeholder",household_pie_data_cost,options);
}

function household_bargraph_load() {

    var npoints = 800;
    interval = ((household_end - household_start) * 0.001) / npoints;
    interval = round_interval(interval);
    
    if (mode=="daily") {
        $(".household-daily").hide();
        $("#household-daily-note").show();
        
        $.ajax({                                      
            //url: path+"feed/data.json?id="+session.feeds["use_kwh"]+"&start="+household_start+"&end="+household_end+"&mode=daily&apikey="+session['apikey_read'],
            url: path+club+"/household-daily-summary?start="+household_start+"&end="+household_end+"&apikey="+session['apikey_read'],
            dataType: 'json',
            async: true,                      
            success: function(result) {
                if (!result || result===null || result==="" || result.constructor!=Array || result.length === 0) {
                    console.log("ERROR","invalid household-daily-summary response: ", result);
                } else {
                    household_result = result;
                    
                    if (household_firstload) {
                        household_firstload = false;
                        household_draw_summary(household_result[household_result.length-1]);
                    }

                    // Create initial household_tariff_data array for each tariff band
                    household_tariff_data = {morning:[],midday:[],daytime:[],evening:[],overnight:[],generation:[],standard:[]}
                    /*
                    for (var history_index=0; history_index<club_settings.tariff_history.length; history_index++) {
                        for (var tr=0; tr<club_settings.tariff_history[history_index].tariffs.length; tr++) {
                            var tariff_name = club_settings.tariff_history[history_index].tariffs[tr].name;
                            household_tariff_data[tariff_name] = []
                        }
                    }*/
                
                    // transpose daily summary array into flot graph ready format
                    var len = result.length;
                    var lastvalid = 0;
                    var kwh_in_window = 0;
                    var cost_in_window = 0;
                    
                    for (var z=0; z<len; z++) {
                    
                        var time = result[z][0];
                                          
                        // Work out which tariff version we are on
                        var history_index = 0;
                        for (var tr=0; tr<club_settings.tariff_history.length; tr++) {
                            let s = club_settings.tariff_history[tr]['start'];
                            let e = club_settings.tariff_history[tr]['end'];
                            if (time>=s && time<e) history_index = tr;
                        }
                        var tariff_bands = club_settings.tariff_history[history_index].tariffs;
                        
                        // populate this half hour array with values for each tariff band, default to 0 if band not present
                        let hh = {morning:0,midday:0,daytime:0,evening:0,overnight:0,standard:0}
                        for (var tr=0; tr<tariff_bands.length; tr++) {
                            hh[tariff_bands[tr].name] = result[z][2][tr];
                        }
                        
                        household_tariff_data.morning.push([time*1000,hh.morning]);
                        household_tariff_data.midday.push([time*1000,hh.midday]);
                        household_tariff_data.daytime.push([time*1000,hh.daytime]);
                        household_tariff_data.evening.push([time*1000,hh.evening]);
                        household_tariff_data.overnight.push([time*1000,hh.overnight]);
                        household_tariff_data.standard.push([time*1000,hh.standard]);
                        
                        var generation = result[z][1][tariff_bands.length] - result[z][2][tariff_bands.length]
                        household_tariff_data.generation.push([time*1000,generation]);
                        
                        kwh_in_window += result[z][1][result[z][1].length-1];
                        cost_in_window += result[z][3][result[z][3].length-1];
                    }
                    
                    var unit_cost = 100 * cost_in_window / kwh_in_window;
                    
                    $("#household_use_history_stats").html(kwh_in_window.toFixed(1)+" kWh, £"+cost_in_window.toFixed(0)+", "+unit_cost.toFixed(2)+"p/kWh");
                    
                    householdseries = [];
                    barwidth = 3600*24*1000*0.75;
                    
                    householdseries.push({
                        stack: true, data: household_tariff_data['generation'], color: club_settings.generator_color,
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });                      
                    householdseries.push({
                        stack: true, data: household_tariff_data['overnight'], color: "#014c2d",
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });
                    householdseries.push({
                        stack: true, data: household_tariff_data['morning'], color: "#ffdc00",
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });
                    householdseries.push({
                        stack: true, data: household_tariff_data['midday'], color: "#ffb401",
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });
                    householdseries.push({
                        stack: true, data: household_tariff_data['daytime'], color: "#ffb401",
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });
                    householdseries.push({
                        stack: true, data: household_tariff_data['evening'], color: "#e6602b",
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });   
                    householdseries.push({
                        stack: true, data: household_tariff_data['standard'], color: "#ffb401",
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });
                    household_bargraph_resize();
                }
            }
        });
    }
    else 
    {
        $(".household-daily").show();
        $("#household-daily-note").hide();
        
        $.ajax({                                      
            url: path+"feed/average.json?id="+session.feeds["use_hh_est"]+"&start="+household_start+"&end="+household_end+"&interval="+interval+"&apikey="+session['apikey_read'],
            dataType: 'json',
            async: true,                      
            success: function(result) {
                if (!result || result===null || result==="" || result.constructor!=Array) {
                    console.log("ERROR","invalid response: "+result);
                } else {
                    household_data = result;

                    $.ajax({                                      
                        url: path+"feed/average.json?id="+session.feeds["gen_hh"]+"&start="+household_start+"&end="+household_end+"&interval="+interval+"&apikey="+session['apikey_read'],
                        dataType: 'json',
                        async: true,                      
                        success: function(result) {
                            if (!result || result===null || result==="" || result.constructor!=Array) {
                                console.log("ERROR","invalid response: "+result);
                            } else {
                                gen_data = result;
                                
                                householdseries = [];
                                barwidth = interval*1000*0.75;

                                householdseries.push({
                                    stack: false, data: household_data, color: "#e62f31", label: t("Consumption"),
                                    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                                });
                                householdseries.push({
                                    stack: false, data: gen_data, color:club_settings.generator_color, label: t(ucfirst(club_settings.generator)),
                                    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                                });
                                household_bargraph_resize();
                                
                                /*
                                var total = 0
                                var len = household_data.length;
                                var lastvalid = 0;
                                for (var z=0; z<len; z++) {    
                                
                                    var time = household_data[z][0];  
                                    var use = household_data[z][1];
                                    var d = new Date(time);
                                    var hour = d.getHours();
                                    
                                    var overnight = 0;
                                    var morning = 0;
                                    var midday = 0;
                                    var evening = 0;

                                    // Import times
                                    if (hour<6) overnight = use;
                                    if (hour>=6 && hour<11) morning = use;
                                    if (hour>=11 && hour<16) midday = use;
                                    if (hour>=16 && hour<20) evening = use;
                                    if (hour>=20) overnight = use;

                                    household_overnight_data[z] = [time,overnight];
                                    household_morning_data[z] = [time,morning];
                                    household_midday_data[z] = [time,midday];
                                    household_evening_data[z] = [time,evening];
                                    total += use;
                                }
                                var now = +new Date;
                                console.log("Total kWh in window: "+total.toFixed(2));

                                householdseries.push({
                                    stack: true, data: household_overnight_data, color: "#274e3f", label: t("Overnight"),
                                    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                                });
                                householdseries.push({
                                    stack: true, data: household_morning_data, color: "#ffdc00", label: t("Morning"),
                                    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                                });
                                householdseries.push({
                                    stack: true, data: household_midday_data, color: "#4abd3e", label: t("Midday"),
                                    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                                });
                                householdseries.push({
                                    stack: true, data: household_evening_data, color: "#c92760", label: t("Evening"),
                                    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                                });*/
                            }
                        }
                    });
                }
            }
        });
    }
}

function household_bargraph_draw() {

    var options = {
        xaxis: { 
            mode: "time", 
            timezone: "browser", 
            font: {size:flot_font_size, color:"#666"}, 
            // labelHeight:-5
            reserveSpace:false
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
    
    if ($("#household_bargraph_placeholder").width()>0) {
        $.plot($('#household_bargraph_placeholder'),householdseries,options);
        $('#household_bargraph_placeholder').append("<div id='bargraph-label' style='position:absolute;left:50px;top:30px;color:#666;font-size:12px'></div>");
    }
}

function household_bargraph_resize() {

    var window_width = $(window).width();
    
    flot_font_size = 12;
    if (window_width<450) flot_font_size = 10;
    
    width = $("#household_bargraph_bound").width();

    var h = 400; if (width<400) h = width;
    
    $("#household_bargraph_placeholder").width(width);
    $('#household_bargraph_bound').height(h);
    $('#household_bargraph_placeholder').height(h);
    height = h;
    household_bargraph_draw();
}

$(".household-left").click(function(event) {
    event.stopPropagation();
    var time_window = household_end - household_start;
    household_end -= time_window * 0.5;
    household_start -= time_window * 0.5;
    household_bargraph_load();
});

$(".household-right").click(function(event) {
    event.stopPropagation();
    var time_window = household_end - household_start;
    household_end += time_window * 0.5;
    household_start += time_window * 0.5;
    household_bargraph_load();
});

$(".household-day").click(function(event) {
    event.stopPropagation();
    household_end = +new Date;
    household_start = household_end - (3600000*24.0*1);
    household_bargraph_load();
});

$(".household-week").click(function(event) {
    event.stopPropagation();
    household_end = +new Date;
    household_start = household_end - (3600000*24.0*7);
    household_bargraph_load();
});

$(".household-month").click(function(event) {
    event.stopPropagation();
    household_end = +new Date;
    household_start = household_end - (3600000*24.0*30);
    household_bargraph_load();
});

$(".household-year").click(function(event) {
    event.stopPropagation();
    household_end = +new Date;
    household_start = household_end - (3600000*24.0*365);
    household_bargraph_load();
});

$('#household_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    household_start = ranges.xaxis.from;
    household_end = ranges.xaxis.to;
    household_bargraph_load();
});

$('#household_bargraph_placeholder').bind("plothover", function (event, pos, item) {
    if (item) {
        var z = item.dataIndex;
        if (previousPoint != item.datapoint) {
            previousPoint = item.datapoint;

            $("#tooltip").remove();
            var itemTime = item.datapoint[0];
            var elec_kwh = householdseries[item.seriesIndex].data[z][1];

            var d = new Date(itemTime);
            var days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
            var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
            var mins = d.getMinutes();
            if (mins==0) mins = "00";
            if (mode=="daily") {
                if (household_result[z][0]==itemTime*0.001) {
                
                    // Work out which tariff version we are on
                    var history_index = 0;
                    for (var tr=0; tr<club_settings.tariff_history.length; tr++) {
                        let s = club_settings.tariff_history[tr]['start'];
                        let e = club_settings.tariff_history[tr]['end'];
                        if (household_result[z][0]>=s && household_result[z][0]<e) history_index = tr;
                    }
                    var tariff_bands = club_settings.tariff_history[history_index].tariffs;
                    
                    var out = "<table>";
                    out += "<tr><td>"+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate()+"</td></tr>";
                    out += "<tr><td>Tariff version: "+(history_index+1)+"</td></tr>";
                    out += "<tr><td>"+t("Total")+":</td><td>"+household_result[z][1][tariff_bands.length].toFixed(2)+" kWh</td></tr>";
                    out += "<tr><td><div class='legend-label-box' style='background-color:"+club_settings.generator_color+"'></div> "+t(club_settings.generator)+":</td><td>"+(household_result[z][1][tariff_bands.length]-household_result[z][2][tariff_bands.length]).toFixed(2)+" kWh</td></tr>"; 
                    for (var tr=0; tr<tariff_bands.length; tr++) {
                        out += "<tr><td><div class='legend-label-box' style='background-color:"+tariff_bands[tr].color+"'></div> "+t(ucfirst(tariff_bands[tr].name))+":</td><td>"+household_result[z][2][tr].toFixed(2)+" kWh</td></tr>";
                    }                      
                    
                    household_draw_summary(household_result[z]);
                    
                    out += "</table>";
                    tooltip(item.pageX, item.pageY, out, "#fff");
                }
            } else {
                var date = d.getHours()+":"+mins+" "+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate();
                tooltip(item.pageX, item.pageY, date+"<br>"+(elec_kwh).toFixed(3)+" kWh", "#fff"); 
            }
        }
    } else $("#tooltip").remove();
});

$('#household_bargraph_placeholder').bind("plotclick", function (event, pos, item) {
    if (item) {
        household_start = item.datapoint[0];
        household_end = household_start + (3600*24*1000);
        mode = "halfhourly";
        household_bargraph_load();
        
        if (session.feeds.meter_power!=undefined) {
            household_power_start = household_start
            household_power_end = household_end
            household_powergraph_load()
        }
    }
});

$(".household-daily").click(function(event) {
    event.stopPropagation();
    household_end = +new Date;
    household_start = household_end - (3600000*24.0*30);
    mode = "daily";
    household_bargraph_load();

});

// ----------------------------------------------------------------------------------
// Power graph
// ----------------------------------------------------------------------------------
function household_realtime() {
  $.ajax({                  
    url: path+'feed/timevalue.json',             
    data: "id="+household_power_feedid+"&apikey="+session['apikey_read'],
    dataType: 'json',               
    async: true,
    success: function(data) {  
	      household_realtime_data = data;
        $("#power_value").html(data.value);
    }
  });
}

function household_powergraph_load() {

  var npoints = 1200;
  var household_power_interval = ((household_power_end - household_power_start) * 0.001) / npoints;  
  household_power_interval = Math.round(household_power_interval/10)*10;
  if (household_power_interval<10) household_power_interval = 10;
    
  $.ajax({                  
    url: path+'feed/data.json',             
    data: "id="+household_power_feedid+"&start="+household_power_start+"&end="+household_power_end+"&interval="+household_power_interval+"&skipmissing=1&limitinterval=0&apikey="+session['apikey_read'],
    dataType: 'json',               
    async: true,
    success: function(data) {
        householdpowerseries = [];
        var t = 0;
        var kwh_in_window = 0.0;
        for (var z=1; z<data.length; z++) {
            t = (data[z][0] - data[z-1][0])*0.001
            kwh_in_window += (data[z][1] * t) / 3600000.0;
            if (data[z][1]!=null) householdpowerseries.push(data[z])
        }
        if (householdpowerseries.length>0) $("#realtime-power").show();
        $("#kwh_in_window").html(kwh_in_window.toFixed(2));
        household_powergraph_draw();
    }
  });
}

function household_powergraph_draw() {

    powergraph_width = $("#household_powergraph_bound").width();
    var h = 400; if ((powergraph_width*0.6)<400) h = powergraph_width*0.6;
    
    $("#household_powergraph_placeholder").width(powergraph_width);
    $('#household_powergraph_bound').height(h);
    $('#household_powergraph_placeholder').height(h);

    var options = {
        xaxis: { 
            mode: "time", 
            timezone: "browser", 
            font: {size:flot_font_size, color:"#666"}, 
            // labelHeight:-5
            reserveSpace:false,
            min: household_power_start,
            max: household_power_end
        },
        yaxis: { 
            font: {size:flot_font_size, color:"#666"}, 
            // labelWidth:-5
            reserveSpace:false
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
    
    if ($("#household_powergraph_placeholder").width()>0) {
        $.plot($('#household_powergraph_placeholder'),[{data:householdpowerseries, color: "#e62f31", lines: {show:true, fill:true}}],options);
        $('#household_powergraph_placeholder').append("<div id='powergraph-label' style='position:absolute;left:50px;top:30px;color:#666;font-size:12px'></div>");
    }
    
    $("#household_use_history_stats").html("");
}

$(".household-power-left").click(function(event) {
    event.stopPropagation();
    var time_window = household_power_end - household_power_start;
    household_power_end -= time_window * 0.25;
    household_power_start -= time_window * 0.25;
    household_powergraph_load();
});

$(".household-power-right").click(function(event) {
    event.stopPropagation();
    var time_window = household_power_end - household_power_start;
    household_power_end += time_window * 0.25;
    household_power_start += time_window * 0.25;
    household_powergraph_load();
});

$(".household-power-day").click(function(event) {
    event.stopPropagation();
    household_power_end = +new Date;
    household_power_start = household_power_end - (3600000*24.0*1);
    household_powergraph_load();
});

$(".household-power-week").click(function(event) {
    event.stopPropagation();
    household_power_end = +new Date;
    household_power_start = household_power_end - (3600000*24.0*7);
    household_powergraph_load();
});

$(".household-power-month").click(function(event) {
    event.stopPropagation();
    household_power_end = +new Date;
    household_power_start = household_power_end - (3600000*24.0*30);
    household_powergraph_load();
});

$('#household_powergraph_placeholder').bind("plotselected", function (event, ranges) {
    household_power_start = ranges.xaxis.from;
    household_power_end = ranges.xaxis.to;
    household_powergraph_load();
});

