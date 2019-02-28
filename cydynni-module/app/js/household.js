/*

Household page

*/
var household_end = +new Date;
var household_start = household_end - (3600000*24.0*30);

var household_power_end = +new Date;
var household_power_start = household_power_end - (3600000*12.0);
var household_power_feedid = 0;
var household_updater = false;

var household_pie_data_cost = [];
var household_pie_data_energy = [];

var household_generation_use = 0;
var householdseries = [];
var householdpowerseries = [];

var household_overnight_data = [];
var household_morning_data = [];
var household_evening_data = [];
var household_midday_data = [];
var household_daily_data = [];

var household_data = [];
var household_result = [];

var household_view = "piechart";

var meterdataenable = false;
var mode = "daily";

function household_summary_load()
{    

  if (session.feeds.hub_use!=undefined) {
      $("#realtime-power").show();
      household_power_feedid = session.feeds.hub_use
      household_powergraph_load();
      
      household_realtime();
      clearInterval(household_updater);
      household_updater = setInterval(household_realtime,5000);
      
  } else if (session.feeds.meter_power!=undefined) {
      $("#realtime-power").show();
      household_power_feedid = session.feeds.meter_power
      household_powergraph_load();
      
      household_realtime();
      clearInterval(household_updater);
      household_updater = setInterval(household_realtime,5000);
  
  } else {
      $("#realtime-power").hide(); 
  }
  

  $.ajax({                                      
      url: path+"cydynni/household-summary-day"+apikeystr,
      dataType: 'json',                  
      success: function(result) {
          if (!result) return;
          if (result=="Invalid data") return;
          household_draw_summary(result)
      } 
  });
  
  if (meterdataenable) {
      $("#meterdatablock").show();
      household_update_live();
      setInterval(household_update_live,5000);
  } else {
      $("#meterdatablock").hide();
  }
  
}

function household_draw_summary(result) {
    // 1. Determine score
    // Calculated as amount of power consumed at times off peak times and from generation
    var score = Math.round(100*((result.kwh.overnight + result.kwh.midday + result.kwh.generation) / result.kwh.total));

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

    var ext = "";
    if (result.day==1) ext = "st";
    if (result.day==2) ext = "nd";
    if (result.day==3) ext = "rd";
    if (result.day>3) ext = "th";
    if (lang=="cy_GB") ext = "";

    $(".household_date").html(result.day+ext+" "+t(result.month));
    $(".household_score").html(score);
    $(".household_totalkwh").html(result.kwh.total.toFixed(1));
    $(".household_totalcost").html("£"+result.cost.total.toFixed(2));

    // Saving calculation
    var totalcostflatrate = result.kwh.total * 0.12;
    var costsaving = totalcostflatrate - result.cost.total;
    $(".household_costsaving").html("£"+costsaving.toFixed(2));

    household_pie_data_cost = [];
    household_pie_data_energy = [];

    for (var z in tariffs) {
        if (z!="generation") {
            household_pie_data_cost.push({
                name:t(z.toUpperCase()), 
                generation: result.generation[z]*tariffs.generation.cost, 
                import: result.kwh[z]*tariffs[z].cost, 
                color:tariffs[z].color
            });
            
            household_pie_data_energy.push({
                name:t(z.toUpperCase()), 
                generation: result.generation[z], 
                import: result.kwh[z], 
                color:tariffs[z].color
            });
        }

        $("#household_"+z+"_kwh").html(result.kwh[z].toFixed(2));
        $("#household_"+z+"_cost").html((result.kwh[z]*tariffs[z].cost).toFixed(2));
    }
        
    household_generation_use = result.kwh.generation;
    household_pie_draw();
}

function household_update_live() {
/*
  $.ajax({                                      
      url: path+"meter/live",
      dataType: 'json',                  
      success: function(result) {
      
          $(".meterdata-power").html((result.power*1000)+"W");
          $(".meterdata-kwh").html((result.kwh)+" kWh");
      }
  });*/
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
    
    var timevalue = false;

    var household_overnight_data = [];
    var household_morning_data = [];
    var household_midday_data = [];
    var household_evening_data = [];
    var household_daily_data = [];
    var household_hydro_data = [];

    var data = [];
    
    if (mode=="daily") {
        $(".household-daily").hide();
        $("#household-daily-note").show();
        //url = path+"cydynni/household-daily-summary";
        
        $.ajax({url: path+"feed/timevalue.json?id="+session.feeds["use_kwh"]+"&apikey="+session['apikey_read'], dataType: 'json', async: false, success: function(result) {
             timevalue = result;
        }});
        
        $.ajax({                                      
            //url: path+"feed/data.json?id="+session.feeds["use_kwh"]+"&start="+household_start+"&end="+household_end+"&mode=daily&apikey="+session['apikey_read'],
            url: path+"cydynni/household-daily-summary?start="+household_start+"&end="+household_end,
            dataType: 'json',
            async: true,                      
            success: function(result) {
                if (!result || result===null || result==="" || result.constructor!=Array) {
                    console.log("ERROR","invalid response: "+result);
                } else {
                    household_result = result;
                
                    var len = result.length;
                    var lastvalid = 0;

                    for (var z=0; z<len; z++) {    
                        /*
                        var time = result[z][0];
                        var use = result[z][1];
                        
                        if (z<len-1) {
                            if (result[z+1][1]!=null && result[z][1]!=null) {
                                 var delta = result[z+1][1] - result[z][1];
                                 household_daily_data.push([time,delta]);
                                 lastvalid = z;
                            }
                        }*/
                        
                        var time = result[z][0];
                        household_daily_data.push([time*1000,result[z][1][4]]);
                        
                        household_morning_data.push([time*1000,result[z][2][0]]);
                        household_midday_data.push([time*1000,result[z][2][1]]);
                        household_evening_data.push([time*1000,result[z][2][2]]);
                        household_overnight_data.push([time*1000,result[z][2][3]]);
                        
                        var generation = result[z][1][4] - result[z][2][4]
                        household_hydro_data.push([time*1000,generation]);
                    }
                    
                    /*
                    var now = +new Date;
                    if ((now-household_end)<3600) {
                        var delta = timevalue.value - result[lastvalid+1][1];
                        var time = result[lastvalid+1][0];
                        household_daily_data.push([time,delta]);
                    }*/
                    
                    householdseries = [];
                    barwidth = 3600*24*1000*0.75;
                    //householdseries.push({
                    //    stack: true, data: household_daily_data, color: "#e62f31",
                    //    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    //});

                    householdseries.push({
                        stack: true, data: household_hydro_data, color: "#29aae3", label: t("Hydro"),
                        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
                    });                      
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
            url: path+"feed/average.json?id="+session.feeds["halfhour_consumption"]+"&start="+household_start+"&end="+household_end+"&interval="+interval+"&apikey="+session['apikey_read'],
            dataType: 'json',
            async: true,                      
            success: function(result) {
                if (!result || result===null || result==="" || result.constructor!=Array) {
                    console.log("ERROR","invalid response: "+result);
                } else {

                    household_data = result;
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
                    
                    householdseries = [];
                    barwidth = interval*1000*0.75;
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
                    });
                    household_bargraph_resize();
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

// View change: show bar graph
$("#view-household-bargraph").click(function(){
    $("#view-household-bargraph").hide();
    $("#view-household-piechart").show();
    
    $("#household_piegraph").hide();
    $("#household_bargraph").show();
    household_view = "bargraph";
    household_bargraph_load();
});

// View change: show pie graph
$("#view-household-piechart").click(function(){
    $("#view-household-bargraph").show();
    $("#view-household-piechart").hide();
    
    $("#household_piegraph").show();
    $("#household_bargraph").hide();
    household_view = "piechart";
    household_pie_load(); 
});

$(".reports").click(function() {
    window.location = path+"report";
});

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
            var months_long = ["January","February","March","April","May","June","July","August","September","October","November","December"];
            var mins = d.getMinutes();
            if (mins==0) mins = "00";
            if (mode=="daily") {
                if (household_result[z][0]==itemTime*0.001) {
                    var out = "<table>";
                    out += "<tr><td>"+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate()+"</td></tr>";
                    out += "<tr><td>"+t("Total")+":</td><td>"+household_result[z][1][4].toFixed(2)+" kWh</td></tr>";  
                    out += "<tr><td>"+t("Hydro")+":</td><td>"+(household_result[z][1][4]-household_result[z][2][4]).toFixed(2)+" kWh</td></tr>"; 
                    out += "<tr><td>"+t("Morning")+":</td><td>"+household_result[z][2][0].toFixed(2)+" kWh</td></tr>";
                    out += "<tr><td>"+t("Midday")+":</td><td>"+household_result[z][2][1].toFixed(2)+" kWh</td></tr>";
                    out += "<tr><td>"+t("Evening")+":</td><td>"+household_result[z][2][2].toFixed(2)+" kWh</td></tr>";
                    out += "<tr><td>"+t("Overnight")+":</td><td>"+household_result[z][2][3].toFixed(2)+" kWh</td></tr>"; 
                    out += "</table>";                                       
                    
                    tooltip(item.pageX, item.pageY, out, "#fff");
                    
                    household_draw_summary({
                        day: d.getDate(),
                        month: months_long[d.getMonth()],
                        kwh:{
                            morning: household_result[z][2][0],
                            midday: household_result[z][2][1],
                            evening: household_result[z][2][2],
                            overnight: household_result[z][2][3],
                            generation: household_result[z][1][4]-household_result[z][2][4],
                            total: household_result[z][1][4]
                        },
                        generation:{
                            morning: household_result[z][1][0] - household_result[z][2][0],
                            midday: household_result[z][1][1] - household_result[z][2][1],
                            evening: household_result[z][1][2] - household_result[z][2][2],
                            overnight: household_result[z][1][3] - household_result[z][2][3]
                        },
                        cost:{
                            morning: household_result[z][3][0],
                            midday: household_result[z][3][1],
                            evening: household_result[z][3][2],
                            overnight: household_result[z][3][3],
                            total: household_result[z][3][4]
                        }
                    });
                }
            } else {
                var date = d.getHours()+":"+mins+" "+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate();
                tooltip(item.pageX, item.pageY, date+"<br>"+(elec_kwh).toFixed(1)+" kWh", "#fff"); 
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
        console.log(data);
        $("#power_value").html(data.value);
    }
  });
}

function household_powergraph_load() {

  var npoints = 1200;
  interval = ((household_power_end - household_power_start) * 0.001) / npoints;
  //interval = round_interval(interval);
  
  interval = Math.round(interval/10)*10;
  if (interval<10) interval = 10;
    
  $.ajax({                  
    url: path+'feed/average.json',             
    data: "id="+household_power_feedid+"&start="+household_power_start+"&end="+household_power_end+"&interval="+interval+"&skipmissing=1&limitinterval=0&apikey="+session['apikey_read'],
    dataType: 'json',               
    async: true,
    success: function(data) {  
        householdpowerseries = data;
        
        householdpowerseries = [];
        var t = 0;
        var kwh_in_window = 0.0;
        for (var z=1; z<data.length; z++) {
            t = (data[z][0] - data[z-1][0])*0.001
            kwh_in_window += (data[z][1] * t) / 3600000.0;
            if (data[z][1]!=null) householdpowerseries.push(data[z])
        }
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

