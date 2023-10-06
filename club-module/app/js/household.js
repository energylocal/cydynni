/*

Household page

*/

var household_power_end = +new Date;
var household_power_start = household_power_end - (3600000*12.0);
var household_power_feedid = false;
var household_updater = false;

var household_pie_data_cost = [];
var household_pie_data_energy = [];

var householdseries = [];
var householdpowerseries = [];

var household_tariff_data = {};

var household_realtime_data = {};
var household_data = [];
var household_result = [];

var mode = "daily";

var household_firstload = true;

function household_summary_load()
{
  if (session.feeds.hub_use!=undefined) {
      household_power_feedid = session.feeds.hub_use
  } else if (session.feeds.meter_power!=undefined) {
      household_power_feedid = session.feeds.meter_power
  }
  
  if (household_power_feedid) {
      household_realtime(function(){
          household_powergraph_load();
      });
      clearInterval(household_updater);
      household_updater = setInterval(household_realtime,5000);
  } else {
      $("#realtime-power").hide();
  }
}

// -------------------------------------------------------------------------------------------

function household_draw_summary_day(day) {
    if (day==undefined) return false;
    
    var d = new Date(day[0]*1000);
    var ext = "";
    if (d.getDate()==1) ext = "st";
    if (d.getDate()==2) ext = "nd";
    if (d.getDate()==3) ext = "rd";
    if (d.getDate()>3) ext = "th";
    if (lang=="cy_GB") ext = "";
    $(".household_date").html(t("On the %s you scored").replace('%s', d.getDate()+ext+" "+t(months_long[d.getMonth()])));

    draw_summary(convert_summary_to_key_format(day))
}

// -------------------------------------------------------------------------------------------

function household_draw_summary_range() { 

    if(['year','month','fortnight','week','day'].indexOf(date_selected) != -1) {  
        $(".household_date").html(t("In the last %s, you scored:").replace('%s', t(date_selected)));
    } else if (date_selected=="custom") {
        $(".household_date").html(t("For the range selected in the graph")+":");
    }

    $.ajax({
        url: path+club+"/household-summary?start="+view.start+"&end="+view.end,
        dataType: 'json',
        success: function(result) {
            if (result.demand==undefined) {
                console.log("ERROR","invalid household-daily-summary response: ", result);
                // Default zero result
                result = {
                    "demand": {"overnight":0.0,"daytime":0.0,"evening":0.0,"total":0.0},
                    "import": {"overnight":0.0,"daytime":0.0,"evening":0.0,"total":0.0},
                    "generation": {"overnight":0.0,"daytime":0.0,"evening":0.0,"total":0.0},
                    "generation_cost": {"overnight":0.0,"daytime":0.0,"evening":0.0,"total":0.0},
                    "import_cost": {"overnight":0.0,"daytime":0.0,"evening":0.0,"total":0.0},
                    "cost": {"overnight":0.0,"daytime":0.0,"evening":0.0,"total":0.0},
                    "days": 0
                }
            }
            draw_summary(result);
        }
    });
}

function draw_summary(result) {
      
    household_pie_data_cost = [];
    household_pie_data_energy = [];
    
    // COST
    let generationCost = club_settings.has_generator ? result.generation_cost[tariff_name]: 0;
    for (var tariff_name in result.cost) {
        if (tariff_name!='total') {
            household_pie_data_cost.push({
                name: t(ucfirst(tariff_name)),
                generation: generationCost,
                import: result.import_cost[tariff_name],
                color: tariff_colors[tariff_name]
            });
        }
    }

    // ENERGY
    let generationValue = club_settings.has_generator ? result.generation[tariff_name]: 0;
    for (var tariff_name in result.demand) {
        if (tariff_name!='total') {
            household_pie_data_energy.push({
                name: t(ucfirst(tariff_name)),
                generation: generationValue,
                import: result.import[tariff_name],
                color: tariff_colors[tariff_name]
            });
        }
    }

    // Create aggregated legend item for hydro
    var legend = "";
    if (club_settings.has_generator && result.generation.total != undefined) {
        legend += '<tr>'
        legend += '<td><div class="key" style="background-color:'+club_settings.generator_color+'"></div></td>'
        legend += '<td><b>'+t(ucfirst(club_settings.generator))+'</b><br>'
        legend += result.generation.total.toFixed(2)+" kWh "
        if (result.generation.total>0) legend += "@"+(100*result.generation_cost.total/result.generation.total).toFixed(2)+" p/kWh"
        legend += "<br>"
        legend += t("Costing")+" £"+result.generation_cost.total.toFixed(2)+'</td>'
        legend += '</tr>'
    }
    // CHART KEY VALUES FOR EACH TARIFF:
    // populate tariff totals for club in pie chart key
    for (var tariff_name in result.import) {
        if (tariff_name!='total') {
            var tariff_cost = result.import_cost[tariff_name];
            var tariff_kwh = result.import[tariff_name];
            var tariff_unitcost = false;
            if (tariff_kwh>0.0) tariff_unitcost = tariff_cost/tariff_kwh;
            
            // Legend for each import tariff band
            legend += '<tr>'
            legend += '<td><div class="key" style="background-color:'+tariff_colors[tariff_name]+'"></div></td>'
            legend += '<td><b>'+t(ucfirst(tariff_name))+'</b><br>'
            legend += tariff_kwh.toFixed(2)+" kWh";
            if (tariff_unitcost!==false) legend += " @"+(100*tariff_unitcost).toFixed(1)+" p/kWh<br>"; else legend += "<br>";
            legend += t("Costing")+" £"+tariff_cost.toFixed(2)+'</td>'
            legend += '</tr>'
        }
    }
    var unit_price = 100 * result.cost.total / result.demand.total

    legend += '<tr>'
    legend += '<td></td>'
    legend += '<td><b>'+t("Average Price")+':</b><br>'+unit_price.toFixed(1)+" p/kWh</td>"
    legend += '</tr>'

    $("#household_pie_legend").html(legend);
    household_pie_draw();

    // ------------------------------------------------------------------------------------- 
    // Draw score
    // -------------------------------------------------------------------------------------
    var household_score_description = "This means that %s% of your electricity came from "+club_settings.generator;
    
    var total_low_cost_demand = result.generation.total;
    if (result.import.overnight!=undefined) {
        total_low_cost_demand += result.import.overnight
        household_score_description += " or low-cost power";
    }
    var score = 100;
    if (result.demand.total>0) {
        score = Math.round(100*(total_low_cost_demand / result.demand.total));
    }
    $(".household_score").html(score);
    $(".household_score_description").html(t(household_score_description).replace("%s",score));

    var star_icon_on = "starred";
    var star_icon_off = "star20red";

    if (club=="repower") {
        star_icon_on = "sunyellow";
        star_icon_off = "sun20yellow";
    }

    if (score>=20) star1 = star_icon_on; else star1 = star_icon_off;
    if (score>=40) star2 = star_icon_on; else star2 = star_icon_off;
    if (score>=60) star3 = star_icon_on; else star3 = star_icon_off;
    if (score>=80) star4 = star_icon_on; else star4 = star_icon_off;
    if (score>=90) star5 = star_icon_on; else star5 = star_icon_off;
    
    $("#household_star1").attr("src",app_path+"images/"+star1+".png");
    setTimeout(function() { $("#household_star2").attr("src",app_path+"images/"+star2+".png"); }, 100);
    setTimeout(function() { $("#household_star3").attr("src",app_path+"images/"+star3+".png"); }, 200);
    setTimeout(function() { $("#household_star4").attr("src",app_path+"images/"+star4+".png"); }, 300);
    setTimeout(function() { $("#household_star5").attr("src",app_path+"images/"+star5+".png"); }, 400);
    
    var standing_charge = tariff_standing_charge*result.days;
    var vat = (result.cost.total+standing_charge)*0.05;
    var total_cost = result.cost.total + standing_charge + vat;

    if (result.demand.total!=undefined) $(".household_totalkwh").html(result.demand.total.toFixed(2));
    $(".household_elec_cost").html("£"+result.cost.total.toFixed(2));
    $(".household_standing_charge").html("£"+standing_charge.toFixed(2));
    $(".tariff_standing_charge").html((tariff_standing_charge*100).toFixed(2));
    $(".household_vat").html("£"+vat.toFixed(2));
    $(".household_total_cost").html("£"+total_cost.toFixed(2));
    $(".household_days").html(result.days);


    // Saving calculation
    var saving = (result.demand.total * club_settings.unitprice_comparison) - result.cost.total;
    if (saving>0) {
        // $(".household_saving_title").show();
        $(".household_saving").html("£"+saving.toFixed(2));
    } else {
        // $(".household_saving_title").hide();
        $(".household_saving").html("£0");
    }
}

// -------------------------------------------------------------------------------------------

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

// -------------------------------------------------------------------------------------------

function household_bargraph_load() {

    var npoints = 800;
    interval = ((view.end - view.start) * 0.001) / npoints;
    interval = round_interval(interval);
    
    if (mode=="daily") {
        $(".household-daily").hide();
        $("#household-daily-note").show();
        
        $.ajax({
            url: path+club+"/household-daily-summary?start="+view.start+"&end="+view.end+"&apikey="+session['apikey_read'],
            dataType: 'json',
            async: true,                      
            success: function(result) {
                if (!result || result===null || result==="" || result.constructor!=Array || result.length === 0) {
                    console.log("ERROR","invalid household-daily-summary response: ", result);
                    // Hide household dashboard and show missing data block
                    $('#missing-data-block').show();
                    $("#your-score").hide();
                    $("#your-usage").hide();
                } else {
                    household_result = result;
                    
                    if (household_firstload) {
                        household_firstload = false;
                    }
                    household_draw_summary_range();

                    // Create initial household_tariff_data array for each tariff band
                    household_tariff_data = {morning:[],midday:[],daytime:[],evening:[],overnight:[],generation:[],standard:[]}
                
                    // transpose daily summary array into flot graph ready format
                    var len = result.length;
                    var lastvalid = 0;
                    var kwh_in_window = 0;
                    var cost_in_window = 0;
                    
                    var use_totals = {};
                    var import_totals = {};
                    
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
                            var name = tariff_bands[tr].name;
                            hh[name] = result[z][2][tr];
                            if (import_totals[name]==undefined) import_totals[name] = 0;
                            import_totals[name] += result[z][2][tr];
                            if (use_totals[name]==undefined) use_totals[name] = 0;
                            use_totals[name] += result[z][1][tr];
                        }
                        
                        household_tariff_data.morning.push([time*1000,hh.morning]);
                        household_tariff_data.midday.push([time*1000,hh.midday]);
                        household_tariff_data.daytime.push([time*1000,hh.daytime]);
                        household_tariff_data.evening.push([time*1000,hh.evening]);
                        household_tariff_data.overnight.push([time*1000,hh.overnight]);
                        household_tariff_data.standard.push([time*1000,hh.standard]);
                        
                        var generation = result[z][1][tariff_bands.length] - result[z][2][tariff_bands.length]
                        if (club_settings.has_generator) {
                          household_tariff_data.generation.push([time*1000,generation]);
                        }
                        
                        kwh_in_window += result[z][1][result[z][1].length-1];
                        cost_in_window += result[z][6][result[z][6].length-1];
                    }
                    
                    console.log(import_totals);
                    console.log(use_totals);
                    
                    var unit_cost = 100 * cost_in_window / kwh_in_window;
                    
                    $("#household_use_history_stats").parent().parent().show();
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
                        stack: true, data: household_tariff_data['standard'], color: "#c20000",
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
        $("#household_use_history_stats").parent().parent().hide();
        
        $.ajax({                                      
            url: path+"feed/average.json?id="+session.feeds["use_hh_est"]+"&start="+view.start+"&end="+view.end+"&interval="+interval+"&apikey="+session['apikey_read'],
            dataType: 'json',
            async: true,                      
            success: function(result) {
                if (!result || result===null || result==="" || result.constructor!=Array) {
                    console.log("ERROR","invalid response: "+result);
                } else {
                    household_data = result;

                    $.ajax({                                      
                        url: path+"feed/average.json?id="+session.feeds["gen_hh"]+"&start="+view.start+"&end="+view.end+"&interval="+interval+"&apikey="+session['apikey_read'],
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

// -------------------------------------------------------------------------------------------

$('#household_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    date_selected = "custom";
    $(".period-select").val("custom");
    
    household_bargraph_load();
    club_bargraph_load();
    club_bargraph_draw(); 
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
                    if (club_settings.has_generator) {
                      out += "<tr><td><div class='legend-label-box' style='background-color:"+club_settings.generator_color+"'></div> "+t(club_settings.generator)+":</td><td>"+(household_result[z][1][tariff_bands.length]-household_result[z][2][tariff_bands.length]).toFixed(2)+" kWh</td></tr>"; 
                    }
                    for (var tr=0; tr<tariff_bands.length; tr++) {
                        out += "<tr><td><div class='legend-label-box' style='background-color:"+tariff_bands[tr].color+"'></div> "+t(ucfirst(tariff_bands[tr].name))+":</td><td>"+household_result[z][2][tr].toFixed(2)+" kWh</td></tr>";
                    }                    
                    
                    // household_draw_summary_day(household_result[z]);
                    
                    out += "</table>";
                    tooltip(item.pageX, item.pageY, out, "#fff");
                }
            } else {
                var date = d.getHours()+":"+mins+" "+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate();
                tooltip(item.pageX, item.pageY, date+"<br>"+(elec_kwh).toFixed(3)+" kWh", "#fff"); 
            }
        }
    } else {
        $("#tooltip").remove();
    }    
});

$('#household_bargraph_placeholder').bind("plotclick", function (event, pos, item) {
/*
    if (item) {
        view.start = item.datapoint[0];
        view.end = view.start + (3600*24*1000);
        mode = "halfhourly";
        
        household_bargraph_load();
        
        if (session.feeds.meter_power!=undefined) {
            household_power_start = view.start
            household_power_end = view.end
            household_powergraph_load()
        }
    }*/
});

$(".household-daily").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*30);
    date_selected = "month"
    mode = "daily";
    household_bargraph_load();

});

// ----------------------------------------------------------------------------------
// Power graph
// ----------------------------------------------------------------------------------
function household_realtime(callback=false) {
  $.ajax({                  
    url: path+'feed/timevalue.json',             
    data: "id="+household_power_feedid+"&apikey="+session['apikey_read'],
    dataType: 'json',               
    async: true,
    success: function(data) {  
	      household_realtime_data = data;
        $("#power_value").html(data.value);
        if (callback) callback();
    }
  });
}

function household_powergraph_load() {

  if (household_power_start>household_realtime_data.time*1000) household_power_start = household_realtime_data.time*1000 - (3600*24*7*1000);

  var npoints = 1200;
  var household_power_interval = ((household_power_end - household_power_start) * 0.001) / npoints;  
  household_power_interval = view.round_interval(household_power_interval);
      
  if (club_settings.club_id==2 && household_power_interval<60) household_power_interval = 60; 
      
  if (household_realtime_data.time*1000>=household_power_start) {
      // ------------------------------------------------------------------   
      $.ajax({                  
        url: path+'feed/average.json',             
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
      // ------------------------------------------------------------------   
  } else {
      $('#missing-data-block').show();
  }
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
    
    $("#household_use_history_stats").parent().parent().hide();
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

function convert_summary_to_key_format(day) {

    var time = day[0];
    
    // Work out which tariff version we are on
    var history_index = 0;
    for (var tr=0; tr<club_settings.tariff_history.length; tr++) {
        let s = club_settings.tariff_history[tr]['start'];
        let e = club_settings.tariff_history[tr]['end'];
        if (time>=s && time<e) history_index = tr;
    }
    var tariff_bands = club_settings.tariff_history[history_index].tariffs;
    
    var keys = [];
    for (var tr=0; tr<tariff_bands.length; tr++) {
        keys.push(tariff_bands[tr].name);
    }
    keys.push("total");
    
    
    var result = {
        time: day[0],
        days: 1,
        demand: {},
        import: {},
        generation: {},
        generation_cost: {},
        import_cost: {},
        cost: {}
    };
    
    for (var tr in keys) {
        var key = keys[tr];
        
        result.demand[key] = day[1][tr];
        result.import[key] = day[2][tr];
        result.generation[key] = day[3][tr];
        result.generation_cost[key] = day[4][tr];
        result.import_cost[key] = day[5][tr];
        result.cost[key] = day[6][tr];
    }
    
    return result;
}
