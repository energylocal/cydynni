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
view.end = (+new Date) + (3600000 * 24.0);
view.start = view.end - (3600000 * 24.0 * 12);

// var tariffs = club_settings.tariffs;
var day_view = 1;

function club_summary_load() {

    let start = Math.round(view.start * 0.001);
    let end = Math.round(view.end * 0.001);

    $.ajax({
        url: path + "data/summary?clubid=" + clubid + "&start=" + start + "&end=" + end,
        dataType: 'json',
        success: function (result) {
            if (result.demand == undefined) {
                console.log("ERROR", "invalid club summary response: ", result);
                // Default zero result
                result = {
                    "demand": { "overnight": 0.0, "daytime": 0.0, "evening": 0.0, "total": 0.0 },
                    "import": { "overnight": 0.0, "daytime": 0.0, "evening": 0.0, "total": 0.0 },
                    "generation": { "overnight": 0.0, "daytime": 0.0, "evening": 0.0, "total": 0.0 },
                    "generation_cost": { "overnight": 0.0, "daytime": 0.0, "evening": 0.0, "total": 0.0 },
                    "import_cost": { "overnight": 0.0, "daytime": 0.0, "evening": 0.0, "total": 0.0 },
                    "cost": { "overnight": 0.0, "daytime": 0.0, "evening": 0.0, "total": 0.0 },
                    "days": 0
                }
            }
            draw_club_summary(result);
        }
    });
}

function draw_club_summary(result) {

    club_pie_data_cost = [];
    club_pie_data_energy = [];

    // COST
    for (var tariff_name in result.cost) {
        if (tariff_name != 'total') {
            club_pie_data_cost.push({
                name: t(ucfirst(tariff_name)),
                generation: result.generation_cost[tariff_name],
                import: result.import_cost[tariff_name],
                color: tariff_colors[tariff_name.toLowerCase()]
            });
        }
    }

    // ENERGY
    for (var tariff_name in result.demand) {
        if (tariff_name != 'total') {
            club_pie_data_energy.push({
                name: t(ucfirst(tariff_name)),
                generation: result.generation[tariff_name],
                import: result.import[tariff_name],
                color: tariff_colors[tariff_name.toLowerCase()]
            });
        }
    }

    // Create aggregated legend item for hydro
    var legend = "";

    if (result.generation.total != undefined) {
        legend += '<tr>'
        legend += '<td><div class="key" style="background-color:' + club_settings.generator_color + '"></div></td>'
        legend += '<td><b>' + t(ucfirst(club_settings.generator)) + '</b><br>'
        legend += result.generation.total.toFixed(2) + " kWh "
        if (result.generation.total > 0) legend += "@" + (100 * result.generation_cost.total / result.generation.total).toFixed(2) + " p/kWh"
        legend += "<br>"
        legend += t("Costing") + " £" + result.generation_cost.total.toFixed(2) + '</td>'
        legend += '</tr>'
    }
    // CHART KEY VALUES FOR EACH TARIFF:
    // populate tariff totals for club in pie chart key
    for (var tariff_name in result.import) {
        if (tariff_name != 'total') {
            var tariff_cost = result.import_cost[tariff_name];
            var tariff_kwh = result.import[tariff_name];
            
            var tariff_unitcost = false;
            if (tariff_kwh > 0.0) tariff_unitcost = tariff_cost / tariff_kwh;

            // Legend for each import tariff band
            legend += '<tr>'
            legend += '<td><div class="key" style="background-color:' + tariff_colors[tariff_name.toLowerCase()] + '"></div></td>'
            legend += '<td><b>' + t(ucfirst(tariff_name)) + '</b><br>'
            legend += tariff_kwh.toFixed(2) + " kWh";
            if (tariff_unitcost !== false) legend += " @" + (100 * tariff_unitcost).toFixed(1) + " p/kWh<br>"; else legend += "<br>";
            legend += t("Costing") + " £" + tariff_cost.toFixed(2) + '</td>'
            legend += '</tr>'
        }
    }
    var unit_price = 100 * result.cost.total / result.demand.total

    legend += '<tr>'
    legend += '<td></td>'
    legend += '<td><b>' + t("Average Price") + ':</b><br>' + unit_price.toFixed(1) + " p/kWh</td>"
    legend += '</tr>'

    $("#club_pie_legend").html(legend);
    club_pie_draw();

    // ------------------------------------------------------------------------------------- 
    // Draw score
    // -------------------------------------------------------------------------------------
    if (result.generation_cost == undefined) return false;

    var generation_value = result.generation_cost.total;
    var total_day_cost = result.cost.total;

    var total_low_cost = result.generation_cost.total;
    if (result.import_cost.overnight != undefined) total_low_cost += result.import_cost.overnight

    var total_low_cost_demand = result.generation.total;
    if (result.import.overnight != undefined) total_low_cost_demand += result.import.overnight

    var score = 100;
    if (result.demand.total > 0) {
        score = Math.round(100 * (total_low_cost_demand / result.demand.total));
    }
    $(".club_score").html(score);

    var star_icon_on = "staryellow";
    var star_icon_off = "star20yellow";

    if (club == "repower") {
        star_icon_on = "sunyellow";
        star_icon_off = "sun20yellow";
    }

    if (score >= 20) cstar1 = star_icon_on; else cstar1 = star_icon_off;
    if (score >= 40) cstar2 = star_icon_on; else cstar2 = star_icon_off;
    if (score >= 60) cstar3 = star_icon_on; else cstar3 = star_icon_off;
    if (score >= 80) cstar4 = star_icon_on; else cstar4 = star_icon_off;
    if (score >= 90) cstar5 = star_icon_on; else cstar5 = star_icon_off;

    $("#club_star1").attr("src", app_path + "images/" + cstar1 + ".png");
    setTimeout(function () { $("#club_star2").attr("src", app_path + "images/" + cstar2 + ".png"); }, 100);
    setTimeout(function () { $("#club_star3").attr("src", app_path + "images/" + cstar3 + ".png"); }, 200);
    setTimeout(function () { $("#club_star4").attr("src", app_path + "images/" + cstar4 + ".png"); }, 300);
    setTimeout(function () { $("#club_star5").attr("src", app_path + "images/" + cstar5 + ".png"); }, 400);

    setTimeout(function () {
        if (score < 30) {
            $("#club_statusmsg").html(t("We are not using much " + club_settings.generator + " at the moment"));
        }
        if (score >= 30 && score < 70) {
            $("#club_statusmsg").html(t("We could do more to make the most of the " + club_settings.generator + " power and power at cheaper times of day. Can we move more electricity use away from peak times?"));
        }
        if (score >= 70) {
            $("#club_statusmsg").html(t("We’re doing really well using the " + club_settings.generator + " and cheaper power"));
        }
    }, 400);

    // 2nd ssection showing total consumption and cost
    var generation_value_str = "";
    if (generation_value > 10) {
        generation_value_str = "£" + (generation_value).toFixed(0);
    } else {
        generation_value_str = "£" + (generation_value).toFixed(2);
    }

    $(".club_generation_value").html(generation_value_str);
    $("#club_value_summary").html(generation_value_str + " " + t("kept in the club"));

    if (result.demand.total != undefined) $(".club_totalkwh").html(result.demand.total.toFixed(2));
    $(".club_totalcost").html("£" + result.cost.total.toFixed(2));

    // Saving calculation
    var saving = (result.demand.total * club_settings.unitprice_comparison) - result.cost.total;
    if (saving > 0) {
        $(".club_saving").html("£" + saving.toFixed(2));
    } else {
        $(".club_saving").html("£0");
    }

    // GENERATION TARIFF:
    // populate aggregated totals for club generation
    if (result.generation.total != undefined) $("#club_generation_kwh").html(result.generation.total.toFixed(0));
    $("#club_generation_cost").html(generation_value.toFixed(2));
}

function club_pie_draw() {

    width = 300;
    height = 300;

    $("#club_piegraph1_placeholder").attr('width', width);
    $("#club_piegraph2_placeholder").attr('width', width);
    $('#club_piegraph1_placeholder').attr("height", height);
    $('#club_piegraph2_placeholder').attr("height", height);

    var options = {
        color: "#3b6358",
        centertext: "THIS WEEK",
        width: width,
        height: height
    };

    pie_generator_color = club_settings.generator_color;
    piegraph3("club_piegraph1_placeholder", club_pie_data_energy, options);
    piegraph3("club_piegraph2_placeholder", club_pie_data_cost, options);

    var options = {
        color: "#3b6358",
        centertext: "THIS WEEK",
        width: width,
        height: 50
    };

    hrbar("club_hrbar1_placeholder", club_pie_data_energy, options);
    hrbar("club_hrbar2_placeholder", club_pie_data_cost, options);
}


function club_bargraph_load() {

    var npoints = 200;
    interval = ((view.end - view.start) * 0.001) / npoints;
    interval = round_interval(interval);

    // Limit interval to 1800s
    if (interval < 1800) interval = 1800;
    var intervalms = interval * 1000;

    if (['year', 'month', 'fortnight', 'week', 'day'].indexOf(date_selected) != -1) {
        $(".club_date").html(t("In the last %s, we scored:").replace('%s', t(date_selected)));
    } else if (date_selected == "custom") {
        $(".club_date").html(t("For the range selected in the graph") + ":");
        $(".club_breakdown").html(t("How much of the electricity the club used, came from the %s for the range selected").replace("%s", ucfirst(club_settings.generator)));
    }

    club_summary_load();

    view.start = Math.floor(view.start / intervalms) * intervalms
    view.end = Math.ceil(view.end / intervalms) * intervalms

    var generation_data = feed.getaverage(generation_feed, view.start, view.end, interval, 0, 0);
    var club_data = feed.getaverage(consumption_feed, view.start, view.end, interval, 0, 0);

    var gen_forecast_data = [];
    var demand_forecast_data = [];

    if (club_settings.generation_forecast_feed != undefined && club_settings.consumption_forecast_feed != undefined) {
        gen_forecast_data = feed.getaverage(club_settings.generation_forecast_feed, view.start, view.end, interval, 0, 0);
        demand_forecast_data = feed.getaverage(club_settings.consumption_forecast_feed, view.start, view.end, interval, 0, 0);
    }


    if (generation_data.success != undefined) $("#local_electricity_forecast").hide();

    // -------------------------------------------------------------------------
    // Colour code graph
    // -------------------------------------------------------------------------

    // kWh scale
    var scale = 1;
    if (units == "kWh") scale = (interval / 1800);
    if (units == "kW") scale = 2;

    var data = {};
    data.daytime = [];
    data.evening = [];
    data.overnight = [];
    data.export = [];
    data.selfuse = [];
    data.price = [];
    data.standard = [];

    data.gen_forecast = [];
    data.demand_forecast = [];

    last_actual_reading_time = 0;

    for (var z in club_data) {
        var time = club_data[z][0];
        var d = new Date(time);
        var hour = d.getHours();

        // ------------------------------------------------
        var gen_forecast = null;
        if (gen_forecast_data[z] != undefined) {
            gen_forecast = gen_forecast_data[z][1] * scale * club_settings['gen_scale'];
        }
        var demand_forecast = null;
        if (demand_forecast_data[z] != undefined) {
            demand_forecast = demand_forecast_data[z][1] * scale;
        }
        // ------------------------------------------------

        var generation = 0;
        if (generation_data[z] != undefined && generation_data[z][1] !== null) {
            generation = generation_data[z][1] * scale * club_settings['gen_scale'];
        } else if (gen_forecast !== null) {
            generation = gen_forecast
        }

        if (generation_feed == 1471) {
            if (generation > 40.0) generation = 40.0;
            generation *= 0.5;
        }

        var consumption = 0;
        if (club_data[z][1] !== null) {
            consumption = club_data[z][1] * scale;
            last_actual_reading_time = club_data[z][0]
        } else if (demand_forecast !== null) {
            consumption = demand_forecast
        }

        var exported_generation = 0;
        var used_generation = 0;

        var imprt = 0.0;
        var exprt = 0.0;
        if (generation <= consumption) imprt = consumption - generation; else exprt = generation - consumption;
        var selfuse = consumption - imprt;

        var unit_price = 0.0;

        for (var x in tariffs) {
            var on_tariff = false;
            var sh = 1 * tariffs[x].start;
            var eh = 1 * tariffs[x].end;

            if (sh < eh && (hour >= sh && hour < eh)) on_tariff = true;
            if (sh > eh && (hour >= sh || hour < eh)) on_tariff = true;
            if (sh == eh) on_tariff = true;

            if (data[tariffs[x].name] == undefined) {
                data[tariffs[x].name] = [];
            }

            if (on_tariff) {
                unit_price = (tariffs[x].import * imprt + tariffs[x].generator * selfuse) / consumption

                data[tariffs[x].name][z] = [time, imprt];
            } else {
                data[tariffs[x].name][z] = [time, 0];
            }
        }
        data.export[z] = [time, exprt];
        data.selfuse[z] = [time, selfuse];
        data.price[z] = [time, unit_price];


    }

    clubseries = [];

    var widthprc = 0.75;
    var barwidth = widthprc * interval * 1000;
    // Actual
    clubseries.push({
        stack: true, data: data.selfuse, color: generator_color, label: t("Used " + ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth: 0 }
    });

    // add series data for each tariff
    for (x in tariffs) {
        clubseries.push({
            stack: true, data: data[tariffs[x].name], color: tariffs[x].color, label: t(ucfirst(tariffs[x].name) + " Tariff"),
            bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth: 0 }
        });
    }

    clubseries.push({
        stack: true, data: data.export, color: export_color, label: t("Unused " + ucfirst(club_settings.generator)),
        bars: { show: true, align: "center", barWidth: barwidth, fill: 1.0, lineWidth: 0 }
    });

    if (showClubPrice) {

        clubseries.push({
            data: data.price, color: "#fb1a80", label: t("Price"), yaxis: 2,
            lines: { show: true }
        });
    }

    club_bargraph_draw();
}

function club_bargraph_resize() {

    var window_width = $(window).width();
    flot_font_size = 12;
    if (window_width < 450) flot_font_size = 10;

    width = $("#club_bargraph_bound").width();

    var h = 400; if (width < 400) h = width;

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
            font: { size: flot_font_size, color: "#666" },
            // labelHeight:-5
            reserveSpace: false,
            min: view.start,
            max: view.end,
            tickFormatter: function (val, axis) {
                // use momentjs to format timeseries tick labels in specific format
                // adjust the format dependant on how zoomed in you are
                var d = new Date(val),
                    unit_name = axis.tickSize[1],
                    units = axis.tickSize[0], // number of units between ticks
                    format = 'MMM DD';

                if (unit_name === 'hour' && units === 12) format = 'MMM D<br>ddd ha';
                else if (unit_name === 'hour' || unit_name === 'minute') format = 'h:mma<br>ddd, MMM D';
                else if (unit_name === 'day' && units < 4) format = 'MMM DD';
                else if (unit_name === 'day' || (unit_name === 'hour' && units === 12)) format = '';
                else if (unit_name === 'month') format = 'MMM Y';
                // shorten the "Dydd " prefix for all Welsh day names
                return moment(d).format(format).replace('Dydd', '').trim();
            }
        },
        yaxes: [
            { font: { size: flot_font_size, color: "#666" }, reserveSpace: false, show: false, min: 0 },
            { font: { size: flot_font_size, color: "#666" }, reserveSpace: false, show: false, min: 0 }
        ],
        selection: { mode: "x" },
        grid: {
            show: true,
            color: "#aaa",
            borderWidth: 0,
            hoverable: true,
            clickable: true
        }
    }

    var current_hh = Math.floor((new Date()).getTime() / 1800000) * 1800000;

    var markings = [
        { color: "#f0f0f0", xaxis: { from: last_actual_reading_time + 900000 } },
        { color: "#666", lineWidth: 2, xaxis: { from: last_actual_reading_time + 900000, to: last_actual_reading_time + 900000 } },
        { color: "#ff0000", lineWidth: 2, xaxis: { from: current_hh - 900000, to: current_hh - 900000 } }
    ];

    options.grid.markings = markings;

    var days_behind = ((current_hh - last_actual_reading_time) / 86400000).toFixed(1);

    // if (units=="kW" && generation_feed==1) options.yaxis.max = 100;

    if ($("#club_bargraph_placeholder").width() > 0) {
        var plot = $.plot("#club_bargraph_placeholder", clubseries, options);

        o = plot.pointOffset({ x: last_actual_reading_time + 900000, y: 0 });
        var forecast_text = t("Actual readings are %s days behind.\n\nBlack line and grey section indicates\nforecasted generation and consumption.\n\nRed line indicates the current time.").replace("%s", days_behind);
        $("#club_bargraph_placeholder").append("<div style='position:absolute;left:" + (o.left + 18) + "px;top:13px;color:#666;font-size:smaller; cursor:pointer' title='" + forecast_text + "'>" + t("Estimate") + "</div>");
        ;

        // $("#club_bargraph_placeholder").append("<div style='position:absolute;left:" + (o.left - 6) + "px;top:15px;color:#666;font-size:smaller'>Actual</div>");



        var ctx = plot.getCanvas().getContext("2d");
        ctx.beginPath();
        o.left += 4;
        o.top = 26
        ctx.moveTo(o.left, o.top);
        ctx.lineTo(o.left, o.top - 10);
        ctx.lineTo(o.left + 10, o.top - 5);
        ctx.lineTo(o.left, o.top);
        ctx.fillStyle = "#000";
        ctx.fill();


        o = plot.pointOffset({ x: current_hh + 900000, y: 0 });
        $("#club_bargraph_placeholder").append("<div style='position:absolute;left:" + (o.left + 18) + "px;top:33px;color:#ff6666;font-size:smaller; cursor:pointer' title='" + forecast_text + "'>" + t("Forecast") + "</div>")

        var ctx = plot.getCanvas().getContext("2d");
        ctx.beginPath();
        o.left += 4;
        o.top = 46
        ctx.moveTo(o.left, o.top);
        ctx.lineTo(o.left, o.top - 10);
        ctx.lineTo(o.left + 10, o.top - 5);
        ctx.lineTo(o.left, o.top);
        ctx.fillStyle = "#ff0000";
        ctx.fill();

    }
}

function round_interval(interval) {
    var outinterval = 1800;
    if (interval > 3600 * 1) outinterval = 3600 * 1;

    if (interval > 3600 * 2) outinterval = 3600 * 2;
    if (interval > 3600 * 3) outinterval = 3600 * 3;
    if (interval > 3600 * 4) outinterval = 3600 * 4;
    if (interval > 3600 * 5) outinterval = 3600 * 5;
    if (interval > 3600 * 6) outinterval = 3600 * 6;
    if (interval > 3600 * 12) outinterval = 3600 * 12;

    if (interval > 3600 * 24) outinterval = 3600 * 24;

    if (interval > 3600 * 36) outinterval = 3600 * 36;
    if (interval > 3600 * 48) outinterval = 3600 * 48;
    if (interval > 3600 * 72) outinterval = 3600 * 72;

    return outinterval;
}

$(".club-left").click(function (event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    view.end -= time_window * 0.2;
    view.start -= time_window * 0.2;
    club_bargraph_load();
    club_bargraph_draw();
});

$(".club-right").click(function (event) {
    event.stopPropagation();
    var time_window = view.end - view.start;
    view.end += time_window * 0.2;
    view.start += time_window * 0.2;
    club_bargraph_load();
    club_bargraph_draw();
});

$('.visnav-club').click(function (event) {
    var range = Object.values(event.target.classList).join('').replace('visnav-club', '').replace('club-', '');
    $(".club_breakdown").html(t("How much of the electricity the club used, came from the %s in the last %s").replace("%s", ucfirst(club_settings.generator)).replace("%s", t(range)) + ":");
    $(".club_date").html(t("In the last %s, we scored:").replace('%s', t(range)));
});

$('#club_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    view.start = ranges.xaxis.from;
    view.end = ranges.xaxis.to;
    date_selected = "custom";
    $(".period-select").val("custom");
    club_bargraph_load();
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
            var out = moment(d).format('h:mma ddd, MMM Do') + "<br>";

            // Non estimate part of the graph
            if (selected_series != t(ucfirst(club_settings.generator) + " estimate") && selected_series != t("Club estimate")) {

                // Draw non estimate tooltip
                var total_consumption = 0;
                for (var i in clubseries) {
                    var series = clubseries[i];
                    // Only show tooltip item if defined and more than zero
                    if (series.data[z] != undefined && series.data[z][1] > 0) {
                        var translated_label = series.label;
                        // captialize special cases of translated strings that are added into sentenses
                        var selected_tariff_name = selected_series.toLowerCase().replace('tariff', '').trim();
                        if (lang === 'cy_GB') selected_tariff_name = ucfirst(selected_tariff_name);

                        if (/^Used/.test(translated_label)) {
                            translated_label = t('Used %s').replace('%s', club_settings.generator);
                        } else if (/^Unused/.test(translated_label)) {
                            translated_label = t('Unused %s').replace('%s', club_settings.generator);
                        } else if (/Tariff$/.test(translated_label)) {
                            translated_label = t('%s tariff').replace('%s', t(selected_tariff_name).toLowerCase());
                        }
                        if (series.label != t(ucfirst(club_settings.generator) + " estimate") && series.label != t("Club estimate")) {
                            if (series.label != t("Price")) {
                                out += ucfirst(translated_label) + ": " + (series.data[z][1] * 1).toFixed(1) + units + "<br>";
                            } else {
                                out += ucfirst(translated_label) + ": " + (series.data[z][1] * 1).toFixed(1) + " p/kWh<br>";
                            }
                            if (series.label != t("Unused " + ucfirst(club_settings.generator))) total_consumption += series.data[z][1] * 1;
                        }
                    }
                }
                if (total_consumption) out += t("Total consumption") + ": " + (total_consumption).toFixed(1) + units;

            } else {
                // Print estimate amounts
                out += clubseries[5].label + ": " + (clubseries[5].data[z][1] * 1).toFixed(1) + units + "<br>";
                out += clubseries[6].label + ": " + (clubseries[6].data[z][1] * 1).toFixed(1) + units + "<br>";
            }
            tooltip(item.pageX, item.pageY, out, "#fff");
        }
    } else $("#tooltip").remove();
});

// show/hide club price
$(function () {
    $("#showClubPriceInput").on("input", function (event) {
        showClubPrice = event.target.checked;
        $('#club-price-legend').toggleClass('hide', !showClubPrice);
        club_bargraph_load();
        club_bargraph_draw();
    })
});
