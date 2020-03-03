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

// var tariffs = club_settings.tariffs;
var day_view = 1;

function club_summary_load() { }

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

    var generation_data = [];
    // Load data from server
    if (generation_feed==194360) {
        generation_data = feed.getdataremote(generation_feed,view.start,view.end,interval,1,1);
        for (var z in generation_data) {
        generation_data[z][1] = generation_data[z][1]*2;
        }
    } else {
        generation_data = feed.getaverage(generation_feed,view.start,view.end,interval,1,1);
    }
    var club_data = feed.getaverage(consumption_feed,view.start,view.end,interval,1,1);

    if (generation_data.success!=undefined) $("#local_electricity_forecast").hide();

    // -------------------------------------------------------------------------
    // Colour code graph
    // -------------------------------------------------------------------------

    // kWh scale
    var scale = 1;
    if (units=="kWh") scale = (interval / 1800);
    if (units=="kW") scale = 2;

    var morning_data = [];
    var daytime_data = [];
    var evening_data = [];
    var overnight_data = [];
    exported_generation_data = [];
    used_generation_data = [];

    var price_data = []

    var total_generation = 0;
    var total_used_generation = 0;
    var total_club = 0;
    var total_time = 0;
    
    var total_daytime_import = 0;
    var total_evening_import = 0;
    var total_overnight_import = 0;

    var total_daytime_gen = 0;
    var total_evening_gen = 0;
    var total_overnight_gen = 0;
    
    var total_consumption = 0;

    for (var z in club_data) {
        var time = club_data[z][0];
        var d = new Date(time);
        var hour = d.getHours();

        var generation = 0;
        if (generation_data[z]!=undefined) generation = generation_data[z][1] * scale;
        var consumption = club_data[z][1] * scale;

        var overnight = 0;
        // var morning = 0;
        var daytime = 0;
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
            if (hour<7) overnight = grid_import;
            // if (hour>=6 && hour<11) morning = grid_import;
            if (hour>=7 && hour<16) daytime = grid_import;
            if (hour>=16 && hour<20) evening = grid_import;
            if (hour>=20) overnight = grid_import;
        }
        
        overnight_data[z] = [time,overnight];
        // morning_data[z] = [time,morning];
        daytime_data[z] = [time,daytime];
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

        // ---------------------------------------------------
        // Price signal
        // ---------------------------------------------------
        var imprt = 0.0
        if (generation<=consumption) imprt = consumption-generation
        var selfuse = consumption - imprt

        var hydro_price = 0.0;
        var import_price = 0.0;
        // hydro price
        if (hour>=20.0 || hour<7.0) hydro_price = 5.8;
        if (hour>=7.0 && hour<16.0) hydro_price = 10.4;
        if (hour>=16.0 && hour<20.0) hydro_price = 12.7;
        hydro_cost = selfuse * hydro_price
        // import price
        if (hour>=20.0 || hour<7.0) import_price = 10.5;
        if (hour>=7.0 && hour<16.0) import_price = 18.9;
        if (hour>=16.0 && hour<20.0) import_price = 23.1;
        import_cost = imprt * import_price
        // unit price
        unit_price = (import_cost + hydro_cost) / consumption

        price_data.push([time,unit_price]);
        
        total_daytime_import += daytime;
        total_evening_import += evening;
        total_overnight_import += overnight;

        total_daytime_gen += used_generation;
        total_evening_gen += used_generation;
        total_overnight_gen += used_generation;
        
        total_consumption += import_cost + hydro_cost;
        
    }
    
    var generation_value = (total_daytime_gen*10.4+total_evening_gen*12.7+total_overnight_gen*5.8)*0.01;

    var score = 1.0 - ((total_daytime_import*18.9+total_evening_import*23.1)/total_consumption);
    score = Math.round(100*score);
    $(".club_score").html(score);

    if (score>=20) star1 = "staryellow"; else star1 = "star20yellow";
    if (score>=40) star2 = "staryellow"; else star2 = "star20yellow";
    if (score>=60) star3 = "staryellow"; else star3 = "star20yellow";
    if (score>=80) star4 = "staryellow"; else star4 = "star20yellow";
    if (score>=90) star5 = "staryellow"; else star5 = "star20yellow";

    $("#club_star1").attr("src",app_path+"images/"+star1+".png");
    setTimeout(function() { $("#club_star2").attr("src",app_path+"images/"+star2+".png"); }, 100);
    setTimeout(function() { $("#club_star3").attr("src",app_path+"images/"+star3+".png"); }, 200);
    setTimeout(function() { $("#club_star4").attr("src",app_path+"images/"+star4+".png"); }, 300);
    setTimeout(function() { $("#club_star5").attr("src",app_path+"images/"+star5+".png"); }, 400);

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

    // 2nd ssection showing total consumption and cost
    var generation_value_str = "";
    if (generation_value>10) {
        generation_value_str = "£"+(generation_value).toFixed(0);
    } else {
        generation_value_str = "£"+(generation_value).toFixed(2);
    }

    $(".club_generation_value").html(generation_value_str);
    $("#club_value_summary").html(generation_value_str+" "+t("kept in the club"));

    club_pie_data_cost = [];
    club_pie_data_energy = [];
    
    // COST
    club_pie_data_cost.push({
        name:t("Day"),
        generation: total_daytime_gen*0.01*10.4,
        import: total_daytime_import*0.01*18.9,
        color:"#ffb401"
    });
    club_pie_data_cost.push({
        name:t("Evening"),
        generation: total_evening_gen*0.01*12.7,
        import: total_evening_import*0.01*23.1,
        color:"#e6602b"
    });
    club_pie_data_cost.push({
        name:t("Overnight"),
        generation: total_overnight_gen*0.01*5.8,
        import: total_overnight_import*0.01*10.5,
        color:"#014c2d"
    });
    
    // ENERGY
    club_pie_data_energy.push({
        name:t("Day"),
        generation: total_daytime_gen,
        import: total_daytime_import,
        color:"#ffb401"
    });
    club_pie_data_energy.push({
        name:t("Evening"),
        generation: total_evening_gen,
        import: total_evening_import,
        color:"#e6602b"
    });
    club_pie_data_energy.push({
        name:t("Overnight"),
        generation: total_overnight_gen,
        import: total_overnight_import,
        color:"#014c2d"
    });
    
    /*
    if (result.kwh[z]!=null) {
        $("#club_"+z+"_kwh").html(result.kwh[z].toFixed(0));
        $("#club_"+z+"_cost").html((result.kwh[z]*tariffs[z].cost).toFixed(2));

        var unitcoststr = "";
        if (result.kwh[z]>0) unitcoststr = "@ "+(100*result.kwh[z]*tariffs[z].cost/result.kwh[z]).toFixed(2)+" p/kWh";
        $("#club_"+z+"_unitcost").html(unitcoststr);
    }
    */
    club_pie_draw();

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
    
    clubseries = [];

    var widthprc = 0.75;
    var barwidth = widthprc*interval*1000;
    // Actual
    clubseries.push({
        stack: true, data: used_generation_data, color: generator_color, label: t("Used "+ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: overnight_data, color: "#014c2d", label: t("Overnight Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    //clubseries.push({
    //    stack: true, data: morning_data, color: "#ffb401", label: t("Morning Tariff"),
    //    bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    //});
    clubseries.push({
        stack: true, data: daytime_data, color: "#ffb401", label: t("Daytime Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: evening_data, color: "#e6602b", label: t("Evening Tariff"),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    clubseries.push({
        stack: true, data: exported_generation_data, color: export_color, label: t("Exported "+ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });

    if(showClubPrice) {
        clubseries.push({
            data: price_data, color: "#fb1a80", label: t("Price"), yaxis:2,
            lines: { show: true }
        });
    }
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
        yaxes: [
            {font: {size:flot_font_size, color:"#666"},reserveSpace:false,show:false,min:0},
            {font: {size:flot_font_size, color:"#666"},reserveSpace:false,show:false,min:0}
        ],
        selection: { mode: "x" },
        grid: {
            show:true,
            color:"#aaa",
            borderWidth:0,
            hoverable: true,
            clickable: true
        }
    }

    // if (units=="kW" && generation_feed==1) options.yaxis.max = 100;

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

/*
$(".club-zoomout").click(function(event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    var middle = view.start + time_window / 2;
    time_window = time_window * 2;
    view.start = middle - (time_window/2);
    view.end = middle + (time_window/2);
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-zoomin").click(function(event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    var middle = view.start + time_window / 2;
    time_window = time_window * 0.5;
    view.start = middle - (time_window/2);
    view.end = middle + (time_window/2);
    club_bargraph_load();
    club_bargraph_draw();
});
*/

$(".club-left").click(function(event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    view.end -= time_window * 0.2;
    view.start -= time_window * 0.2;
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-right").click(function(event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    view.end += time_window * 0.2;
    view.start += time_window * 0.2;
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-day").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*1);
    club_bargraph_load();
    club_bargraph_draw();
    $(".club_date").html(t("In the last day, we scored:"));
});

$(".club-week").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*7);
    club_bargraph_load();
    club_bargraph_draw();
    $(".club_date").html(t("In the last week, we scored:"));
});

$(".club-month").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*30);
    club_bargraph_load();
    club_bargraph_draw();
    $(".club_date").html(t("In the last month, we scored:"));
});

$(".club-year").click(function(event) {
    event.stopPropagation();
    view.end = +new Date;
    view.start = view.end - (3600000*24.0*365);
    club_bargraph_load();
    club_bargraph_draw();
    $(".club_date").html(t("In the last year, we scored:"));
});

$('#club_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    club_bargraph_load();
    club_bargraph_draw();
    $(".club_date").html(t("For the range selected in the graph:"));
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
                            if (series.label!=t("Price")) {
                                out += series.label+ ": "+(series.data[z][1]*1).toFixed(1)+units+"<br>";
                            } else {
                                out += series.label+ ": "+(series.data[z][1]*1).toFixed(1)+" p/kWh<br>";
                            }
                            if (series.label!=t("Exported "+ucfirst(club_settings.generator))) total_consumption += series.data[z][1]*1;
                        }
                    }
                }
                if (total_consumption) out += t("Total consumption: ")+(total_consumption).toFixed(1)+units;

            } else {
                // Print estimate amounts
                out += clubseries[5].label+ ": "+(clubseries[5].data[z][1]*1).toFixed(1)+units+"<br>";
                out += clubseries[6].label+ ": "+(clubseries[6].data[z][1]*1).toFixed(1)+units+"<br>";
            }
            tooltip(item.pageX,item.pageY,out,"#fff");
        }
    } else $("#tooltip").remove();
});

// show/hide club price
$(function(){
    $("#showClubPriceInput").on("input", function(event) {
        showClubPrice = event.target.checked;
        club_bargraph_load();
        club_bargraph_draw();
    })
});
