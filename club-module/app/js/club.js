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
var showClubPrice = false;

// Initial view range 24 hours
view.end = +new Date;
view.start = view.end - (3600000*24.0*12);

// var tariffs = club_settings.tariffs;
var day_view = 1;

function club_summary_load() { 

$.ajax({
    url: path+"club/club-summary?start="+view.start+"&end="+view.end,
    dataType: 'json',
        success: function(result) {
            var generation_value = result.cost.total.selfuse;
            var score = 1.0 - ((result.cost.daytime.import+result.cost.evening.import)/(result.cost.total.import+result.cost.total.selfuse));
            score = Math.round(100*score);
            $(".club_score").html(score);

            if (score>=20) cstar1 = "staryellow"; else cstar1 = "star20yellow";
            if (score>=40) cstar2 = "staryellow"; else cstar2 = "star20yellow";
            if (score>=60) cstar3 = "staryellow"; else cstar3 = "star20yellow";
            if (score>=80) cstar4 = "staryellow"; else cstar4 = "star20yellow";
            if (score>=90) cstar5 = "staryellow"; else cstar5 = "star20yellow";

            $("#club_star1").attr("src",app_path+"images/"+cstar1+".png");
            setTimeout(function() { $("#club_star2").attr("src",app_path+"images/"+cstar2+".png"); }, 100);
            setTimeout(function() { $("#club_star3").attr("src",app_path+"images/"+cstar3+".png"); }, 200);
            setTimeout(function() { $("#club_star4").attr("src",app_path+"images/"+cstar4+".png"); }, 300);
            setTimeout(function() { $("#club_star5").attr("src",app_path+"images/"+cstar5+".png"); }, 400);

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
            for(x in club_settings.tariffs) {
                var tariff = club_settings.tariffs[x];
                club_pie_data_cost.push({
                    name: t(ucfirst(tariff.name)),
                    generation: result.cost[tariff.name].selfuse,
                    import: result.cost[tariff.name].import,
                    color: tariff.color
                });
            }
            
            // ENERGY
            for(x in club_settings.tariffs) {
                var tariff = club_settings.tariffs[x];
                club_pie_data_energy.push({
                    name: t(ucfirst(tariff.name)),
                    generation: result.kwh[tariff.name].selfuse,
                    import: result.kwh[tariff.name].import,
                    color: tariff.color
                });
            }
            
            // CHART KEY VALUES FOR EACH TARIFF:
            // populate tariff totals for club in pie chart key
            for (x in club_settings.tariffs) {
                var tariff = club_settings.tariffs[x];
                var tariff_cost = result.cost[tariff.name];
                var tariff_kwh = result.kwh[tariff.name];

                var tarriffKwhTotal = (tariff_kwh.import).toFixed(0);
                var tariffTotalCost = (tariff_cost.import).toFixed(2);
                var tariffUnitCost = '@' + (100*tariffTotalCost/tarriffKwhTotal).toFixed(1) + " p/kWh";

                $("#club_"+tariff.name+"_kwh").html(tarriffKwhTotal);
                $("#club_"+tariff.name+"_cost").html(tariffTotalCost);
                $("#club_"+tariff.name+"_unitcost").html(tariffUnitCost);
            }
            // GENERATION TARIFF:
            // populate aggrigated totals for club generation
            $("#club_generation_kwh").html(result.kwh.total.selfuse.toFixed(0));
            $("#club_generation_cost").html(result.cost.total.selfuse);
            // $("#club_generation_unitcost").html('@' + (100*result.cost.total.selfuse/result.kwh.total.selfuse).toFixed(1) + " p/kWh");
            club_pie_draw();
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

    club_summary_load();

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
  
    var data = {};
    data.daytime = [];
    data.evening = [];
    data.overnight = [];
    data.export = [];
    data.selfuse = [];
    data.price = [];

    for (var z in club_data) {
        var time = club_data[z][0];
        var d = new Date(time);
        var hour = d.getHours();

        var generation = 0;
        if (generation_data[z]!=undefined) generation = generation_data[z][1] * scale;
        var consumption = club_data[z][1] * scale;
        
        var exported_generation = 0;
        var used_generation = 0;
        
        var imprt = 0.0;
        var exprt = 0.0;
        if (generation<=consumption) imprt = consumption-generation; else exprt = generation-consumption;
        var selfuse = consumption - imprt;
        
        var unit_price = 0.0;
        
        for(var x in club_settings.tariffs) {
            var tariff = club_settings.tariffs[x];
            var on_tariff = false;
            var sh = 1*tariff.start.split(":")[0];
            var eh = 1*tariff.end.split(":")[0];
                    
            if (sh<eh && (hour>=sh && hour<eh)) on_tariff = true;
            if (sh>eh && (hour>=sh || hour<eh)) on_tariff = true;
            
            if (on_tariff) {
                unit_price = (tariff.import*imprt + tariff.generator*selfuse) / consumption
                
                data[tariff.name][z] = [time,imprt];
            } else {
                data[tariff.name][z] = [time,0];
            }
        }
        data.export[z] = [time,exprt];
        data.selfuse[z] = [time,selfuse];
        data.price[z] = [time,unit_price];
    }
    
    clubseries = [];

    var widthprc = 0.75;
    var barwidth = widthprc*interval*1000;
    // Actual
    clubseries.push({
        stack: true, data: data.selfuse, color: generator_color, label: t("Used "+ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });
    
    // add series data for each tariff
    for(x in club_settings.tariffs) {
        var tariff = club_settings.tariffs[x];
        clubseries.push({
            stack: true, data: data[tariff.name], color: tariff.color, label: t(ucfirst(tariff.name)+" Tariff"),
            bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
        });
    }
    
    clubseries.push({
        stack: true, data: data.export, color: export_color, label: t("Exported "+ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth:0}
    });

    if(showClubPrice) {

        clubseries.push({
            data: data.price, color: "#fb1a80", label: t("Price"), yaxis:2,
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
            max: view.end,
            tickFormatter: function (val, axis) {
                // use momentjs to format timeseries tick labels in specific format
                // adjust the format dependant on how zoomed in you are
                var d = new Date(val),
                    unit_name = axis.tickSize[1],
                    units = axis.tickSize[0], // number of units between ticks
                    format = 'MMM DD';

                if(unit_name==='hour' && units === 12) format = 'MMM D<br>ddd ha';
                else if(unit_name==='hour' || unit_name==='minute') format = 'h:mma<br>ddd, MMM D';
                else if(unit_name==='day' && units < 4) format = 'MMM DD';
                else if(unit_name==='day' || (unit_name==='hour' && units===12)) format = '';
                else if(unit_name==='month') format = 'MMM Y';
                // shorten the "Dydd " prefix for all Welsh day names
                return moment(d).format(format).replace('Dydd','').trim();
            }
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

$('.visnav-club').click(function(event){
    var range = Object.values(event.target.classList).join('').replace('visnav-club','').replace('club-','');
    $(".club_breakdown").html(t("How much of the electricity the club used, came from the %s in the last %s").replace("%s", ucfirst(club_settings.generator)).replace("%s", t(range)) + ":");
    $(".club_date").html(t("In the last %s, we scored:").replace('%s', t(range)));
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
    $(".club_date").html(t("For the range selected in the graph")+":");
    $(".club_breakdown").html(t("How much of the electricity the club used, came from the %s for the range selected").replace("%s", ucfirst(club_settings.generator)));
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
            // display translated dates in this format..."8:30am Sat, Jan 12th"
            var out = moment(d).format('h:mma ddd, MMM Do')+"<br>";

            // Non estimate part of the graph
            if (selected_series!=t(ucfirst(club_settings.generator)+" estimate") && selected_series!=t("Club estimate")) {

                // Draw non estimate tooltip
                var total_consumption = 0;
                for (var i in clubseries) {
                    var series = clubseries[i];
                    // Only show tooltip item if defined and more than zero
                    if (series.data[z]!=undefined && series.data[z][1]>0) {
                        var translated_label = series.label;
                        // captialize special cases of translated strings that are added into sentenses
                        var selected_tariff_name = selected_series.toLowerCase().replace('tariff','').trim();
                        if(lang==='cy_GB') selected_tariff_name = ucfirst(selected_tariff_name);

                        if(/^Used/.test(translated_label)) {
                            translated_label = t('Used %s').replace('%s', club_settings.generator);
                        } else if(/^Exported/.test(translated_label)) {
                            translated_label = t('Exported %s').replace('%s', club_settings.generator);
                        } else if(/Tariff$/.test(translated_label)) {
                            translated_label = t('%s tariff').replace('%s', t(selected_tariff_name).toLowerCase());
                        }
                        if (series.label!=t(ucfirst(club_settings.generator)+" estimate") && series.label!=t("Club estimate")) {
                            if (series.label!=t("Price")) {
                                out += ucfirst(translated_label) + ": "+(series.data[z][1]*1).toFixed(1)+units+"<br>";
                            } else {
                                out += ucfirst(translated_label) + ": "+(series.data[z][1]*1).toFixed(1)+" p/kWh<br>";
                            }
                            if (series.label!=t("Exported "+ucfirst(club_settings.generator))) total_consumption += series.data[z][1]*1;
                        }
                    }
                }
                if (total_consumption) out += t("Total consumption") +": "+(total_consumption).toFixed(1)+units;

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
        $('#club-price-legend').toggleClass('hide', !showClubPrice);
        club_bargraph_load();
        club_bargraph_draw();
    })
});
