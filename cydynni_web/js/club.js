/*

Club page

*/

var start = 0;
var end = 0;
var apikey = "";
var units = "kW";

var club_data = [];
var exported_generation_data = [];
var used_generation_data = [];
var clubseries = [];

var club_pie_data_cost = [];
var club_pie_data_energy = [];

var club_score = -1;
var club_generation_use = 0;
var club_view = "bargraph";
var club_height = 0;

// Initial view range 24 hours
view.end = +new Date;
view.start = view.end - (3600000*24.0*12);


var day_view = 1;

function club_summary_load()
{
  $.ajax({                                      
      url: club_path+"/club/summary/day",
      dataType: 'json',                  
      success: function(result) {
          
          if (result!="Invalid data") {
          
              var score = Math.round(100*((result.kwh.overnight + result.kwh.midday + result.kwh.generation) / result.kwh.total));
              
              if (result.dayoffset==1) {
                  $("#club_score_text").html(t("Yesterday we scored"));
              } else {
                  if (result.month==undefined) {
                      $("#club_score_text").html(t("We scored"));
                  } else {
                      $("#club_score_text").html(t("We scored")+" "+t("on")+" "+t(result.month)+" "+result.day);
                  }
              }
              
              $("#club_score").html(score);
              if (score>20) $("#club_star1").attr("src",path+"images/staryellow.png");
              if (score>40) setTimeout(function() { $("#club_star2").attr("src",path+"images/staryellow.png"); }, 100);
              if (score>60) setTimeout(function() { $("#club_star3").attr("src",path+"images/staryellow.png"); }, 200);
              if (score>80) setTimeout(function() { $("#club_star4").attr("src",path+"images/staryellow.png"); }, 300);
              if (score>90) setTimeout(function() { $("#club_star5").attr("src",path+"images/staryellow.png"); }, 400);
              
              setTimeout(function() {
                  if (score<30) {
                      $("#club_statusmsg").html(t("We are using power in a very expensive way"));
                  }
                  if (score>=30 && score<70) {
                      $("#club_statusmsg").html(t("We could do more to make the most of the "+club_settings.generator+" power and power at cheaper times of day. Can we move more electricity use away from peak times?"));
                  }
                  if (score>=70) {
                      $("#club_statusmsg").html(t("We’re doing really well using the "+club_settings.generator+" and cheaper power"));
                  }
                  //club_resize();
              }, 400);
              
              // generation value retained in the club
              var generation_value = result.kwh.generation * tariffs.generation.cost;

              var ext = "";
              if (result.day==1) ext = "st";
              if (result.day==2) ext = "nd";
              if (result.day==3) ext = "rd";
              if (result.day>3) ext = "th";
              if (lang=="cy") ext = "";

              $(".club_date").html(result.day+t(ext)+" "+t(result.month));
              
              // 2nd ssection showing total consumption and cost
              $(".club_generation_value").html("£"+(generation_value).toFixed(2));
              $("#club_value_summary").html("£"+(generation_value).toFixed(2)+" "+t("kept in the club"));
              
              club_pie_data_cost = [];
              club_pie_data_energy = [];
              
              for (var z in tariffs) {
                  if (z!="generation") {
                      club_pie_data_cost.push({
                          name:t(z.toUpperCase()), 
                          generation: result.generation[z]*tariffs.generation.cost, 
                          import: result.kwh[z]*tariffs[z].cost, 
                          color:tariffs[z].color
                      });
                      
                      club_pie_data_energy.push({
                          name:t(z.toUpperCase()), 
                          generation: result.generation[z], 
                          import: result.kwh[z], 
                          color:tariffs[z].color
                      });
                  }
              
                  $("#club_"+z+"_kwh").html(result.kwh[z]);
                  $("#club_"+z+"_cost").html((result.kwh[z]*tariffs[z].cost).toFixed(2));
              }
                                         
              club_generation_use = result.kwh.generation
              
              club_pie_draw();
          } 
          else
          {
          
          }
      } 
  });
}

function club_pie_draw() {
    
    width = 300;
    height = 300;

    $("#club_piegraph1_placeholder").attr('width',width);
    $("#club_piegraph2_placeholder").attr('width',width);
    $('#club_piegraph1_placeholder').attr("height",height);
    $('#club_piegraph2_placeholder').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    };
    
    piegraph3("club_piegraph1_placeholder",club_pie_data_energy,options); 
    piegraph3("club_piegraph2_placeholder",club_pie_data_cost,options);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: 50
    };
    
    hrbar("club_hrbar1_placeholder",club_pie_data_energy,options); 
    hrbar("club_hrbar2_placeholder",club_pie_data_cost,options);
}


function club_bargraph_load() {

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
    var generation_data = feed.getaverage(generation_feed,view.start,view.end,interval,1,1);
    var club_data = feed.getaverage(consumption_feed,view.start,view.end,interval,1,1);
    
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
    exported_generation_data = [];
    used_generation_data = [];
    
    var total_generation = 0;
    var total_used_generation = 0;
    var total_club = 0;
    var total_time = 0;

    for (var z in club_data) {    
        var time = club_data[z][0];    
        var d = new Date(time);
        var hour = d.getHours();
        
        var generation = generation_data[z][1] * scale;
        var consumption = club_data[z][1] * scale;
        
        var overnight = 0;
        var morning = 0;
        var midday = 0;
        var evening = 0;
        var exported_generation = 0;
        var used_generation = 0;

        // When available generation is more than club consumption
        if (generation>consumption) {
            // generation export
            exported_generation = generation - consumption;
            // generation used
            used_generation = consumption;
            // No imported power at tariff periods:

        } else {
            // generation used
            used_generation = generation;
            // Grid import
            var grid_import = consumption - generation;
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
        exported_generation_data[z] = [time,exported_generation];
        used_generation_data[z] = [time,used_generation];
        
        if (units=="kW") {
            total_generation += generation * (interval/3600);
            total_club += consumption * (interval/3600);
            total_used_generation += used_generation * (interval/3600);
        } else {
            total_generation += generation;
            total_club += consumption;
            total_used_generation += used_generation;
        }
        total_time += interval;
    }    
    
    // ----------------------------------------------------------------------------
    // estimate
    // ----------------------------------------------------------------------------
    generation_estimate = [];
    club_estimate = [];
    
    var lasttime = 0;
    var lastvalue = 0;
    for (var z in generation_data) {
        if (generation_data[z][1]!=null) {
            lasttime = generation_data[z][0];
            lastvalue = generation_data[z][1];
        } 
    }
    
    if ((((new Date()).getTime()-view.end)<3600*1000*48) && ((view.end-lasttime)*0.001)>1800) {
        // ----------------------------------------------------------------------------
        // generation estimate USING YNNI PADARN PERIS DATA
        // ----------------------------------------------------------------------------
        if (lasttime==0) lasttime = view.start;
        
        $.ajax({                                      
            url: club_path+"/generation/estimate?start="+view.start+"&end="+view.end+"&interval="+interval+"&lasttime="+lasttime+"&lastvalue="+lastvalue,
            dataType: 'json', async: false, success: function(result) {
            generation_estimate = result;
            
            for (var z in generation_estimate) {
                generation_estimate[z][1] = generation_estimate[z][1] * scale;
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
        
        var club_estimate_raw = [];
        
        var time = lasttime;
        if (generation_estimate.length>0) {
            time = generation_estimate[0][0];
        }
        $.ajax({                                      
            url: club_path+"/club/estimate?lasttime="+lasttime+"&interval="+interval,
            dataType: 'json',
            async: false,                      
            success: function(result) {
                var club_estimate_raw = result;
                var l = club_estimate_raw.length;
                
                club_estimate = [];
                for (var h=0; h<divisions_behind; h++) {
                    club_estimate.push([time+(h*interval*1000),club_estimate_raw[h%l]*scale]);
                }
        }});
       
    }
    // ----------------------------------------------------------------------------
    
    clubseries = [];
    
    var widthprc = 0.75;
    var barwidth = widthprc*interval*1000;
    
    // Actual
    clubseries.push({
        stack: true, data: used_generation_data, color: "#29aae3", label: t("Used "+ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: overnight_data, color: "#014c2d", label: t("Overnight Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: morning_data, color: "#ffb401", label: t("Morning Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: midday_data, color: "#4dac34", label: t("Midday Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: evening_data, color: "#e6602b", label: t("Evening Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: exported_generation_data, color: "#a5e7ff", label: t("Exported "+ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });

    // estimate
    clubseries.push({
        data: generation_estimate, color: "#dadada", label: t(ucfirst(club_settings.generator)+" estimate"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        data: club_estimate, color: "#aaa", label: t("Club estimate"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 0.4, lineWidth:0}
    });
    
    // club_bargraph_draw();
}

function club_bargraph_resize() {

    var window_width = $(window).width();
    flot_font_size = 12;
    if (window_width<450) flot_font_size = 10;

    width = $("#club_bargraph_bound").width();
    
    var h = 400; if (width<400) h = width;
    
    $("#club_bargraph_placeholder").width(width);
    $('#club_bargraph_bound').height(h);
    $('#club_bargraph_placeholder').height(h);
    height = h;
    club_bargraph_draw();
}

function club_bargraph_draw() {

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
    
    if (units=="kW" && generation_feed==1) options.yaxis.max = 100;
    
    if ($("#club_bargraph_placeholder").width()>0) {
        $.plot("#club_bargraph_placeholder",clubseries, options);
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

$(".club-left").click(function(event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    view.end -= time_window * 0.5;
    view.start -= time_window * 0.5;
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-right").click(function(event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    view.end += time_window * 0.5;
    view.start += time_window * 0.5;
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-day").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*1);
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-week").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*7);
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-month").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*30);
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-year").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*365);
    club_bargraph_load();
    club_bargraph_draw();
});

$('#club_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    club_bargraph_load();
    club_bargraph_draw();
});

$('#club_bargraph_placeholder').bind("plothover", function (event, pos, item) {

    if (item) {
        var z = item.dataIndex;
        var selected_series = clubseries[item.seriesIndex].label;
        
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
            if (selected_series!=t(ucfirst(club_settings.generator)+" estimate") && selected_series!=t("Club estimate")) {

                // Draw non estimate tooltip
                var total_consumption = 0;
                for (var i in clubseries) {
                    var series = clubseries[i];
                    // Only show tooltip item if defined and more than zero
                    if (series.data[z]!=undefined && series.data[z][1]>0) {
                        if (series.label!=t(ucfirst(club_settings.generator)+" estimate") && series.label!=t("Club estimate")) {
                            out += series.label+ ": "+(series.data[z][1]*1).toFixed(1)+units+"<br>";
                            if (series.label!=t("Exported "+ucfirst(club_settings.generator))) total_consumption += series.data[z][1]*1;
                        }
                    }
                }
                if (total_consumption) out += t("Total consumption: ")+(total_consumption).toFixed(1)+units;
            
            } else {
                // Print estimate amounts
                out += clubseries[6].label+ ": "+(clubseries[6].data[z][1]*1).toFixed(1)+units+"<br>";
                out += clubseries[7].label+ ": "+(clubseries[7].data[z][1]*1).toFixed(1)+units+"<br>";
            }
            tooltip(item.pageX,item.pageY,out,"#fff");
        }
    } else $("#tooltip").remove();
});
