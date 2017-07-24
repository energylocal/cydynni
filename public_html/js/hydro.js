/*

Hydro section

*/

var end = 0;
var start = 0;
var consumption_profile = [];
var forecast = [];
var hydro_data = [];
var hydroseries = [];

setInterval(hydro_load,60000);

function hydro_load() {

    start = Math.floor((start*0.001)/1800)*1800*1000;
    end = Math.ceil((end*0.001)/1800)*1800*1000;
    
    var history = "";
    if (end>0 && start>0) history = "?start="+start+"&end="+end;

    // bargraph_loading("hydro_bargraph_placeholder","rgba(39,78,63,0.7)");
    // $("#hydro_bargraph_placeholder").css("background-color","rgba(39,78,63,0.7)");
    var data = [];
    $.ajax({                                      
        url: path+"hydro"+history,
        dataType: 'json',
        async: true,                      
        success: function(result) {
            $("#hydro_bargraph_placeholder").css("background","none");
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","invalid response: "+result);
            } else {

                hydro_data = result;
                var forecast = [];
                
                if (hydro_data.length>0) {
                    
                    for (var z in hydro_data)
                        // hydro_data[z][1] = ((hydro_data[z][1] * 3600000) / 1800) * 0.001;
                        if (hydro_data[z][1]<0) hydro_data[z][1]=0;
                    
                    var last_power = hydro_data[hydro_data.length-2][1]*1;   
                    var power = hydro_data[hydro_data.length-1][1]*1;
                    var time = hydro_data[hydro_data.length-1][0]*1;
                    
                    var power_kw = ((power*3600000)/1800)*0.001;

                    // ----------------------------------------------------------------------------
                    // Calculate days, half hours behind
                    // ----------------------------------------------------------------------------
                    // Show day instead of "last 24 hour"
                    var d1 = new Date();
                    var t1 = d1.getTime()*0.001;

                    var d2 = new Date(hydro_data[hydro_data.length-1][0]);
                    var t2 = d2.getTime()*0.001;
                    
                    
                    var dayoffset = (t1-t2)/(3600*24);
                    console.log("Days behind: "+dayoffset);
                    
                    // Time of last hydro datapoint
                    var d3 = new Date(hydro_data[hydro_data.length-1][0]);
                    var t3 = d3.getTime()*0.001;
                    
                    // Calculate hours behind
                    var half_hours_behind = Math.floor((t1 - t3) / 1800);
                    console.log("Half hours behind: "+half_hours_behind);
                    
                    // ----------------------------------------------------------------------------
                    // HYDRO FORECAST USING YNNI PADARN PERIS DATA
                    // ----------------------------------------------------------------------------
                    var lasttime = hydro_data[hydro_data.length-1][0];
                    var lastvalue = hydro_data[hydro_data.length-1][1];
                    
                    forecast = [];
                    $.ajax({                                      
                        url: path+"hydro/estimate"+history+"&lasttime="+lasttime+"&lastvalue="+lastvalue+"&interval=1800",
                        dataType: 'json',
                        async: false,                      
                        success: function(ccdata) {
                        forecast = ccdata;
                        
                    }});
                    
                    if (live.hydro!=undefined) { 
                        forecastlive_hydro_kw = live.hydro;
                        // ----------------------------------------------------------------------------
                        // ----------------------------------------------------------------------------
                        $("#power").html(Math.round(power_kw));
                        $("#kWhHH").html(power.toFixed(1));
                        if (forecastlive_hydro_kw>=50) {
                            $("#hydrostatus").html(t("HIGH"));
                            $("#hydro_summary").html(t("For next 12 hours: HIGH POWER"));
                        }
                        else if (forecastlive_hydro_kw>=30) {
                            $("#hydrostatus").html(t("MEDIUM"));
                            $("#hydro_summary").html(t("For next 12 hours: MEDIUM"));
                        }
                        else if (forecastlive_hydro_kw>=10) {
                            $("#hydrostatus").html(t("LOW"));
                            $("#hydro_summary").html(t("For next 12 hours: LOW"));
                        }
                        else {
                            $("#hydrostatus").html(t("VERY LOW"));
                            $("#hydro_summary").html(t("For next 12 hours: VERY LOW"));
                        }
                        
                        $("#power-forecast").html(Math.round(forecastlive_hydro_kw));
                        
                    }
                    
                    var hour = d2.getHours(); var month = d2.getMonth(); var day = d2.getDate();
                    if (hour>=12) hour=(hour-12)+"pm"; else hour=hour+"am";
                    var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
                    
                    if (dayoffset<1) {
                        $("#hydro-graph-date-1").html(t("Yesterday"));
                        $("#hydro-graph-date").html(t("Yesterday")+" ("+day+" "+t(months[month])+"):");
                    } else {
                        $("#hydro-graph-date-1").html(day+" "+t(months[month]));
                        $("#hydro-graph-date").html(day+" "+t(months[month]));
                    }
                    
                    
                } else {
                    $("#hydrostatus").html(t("NO DATA"));
                }

                hydroseries = [];
                hydroseries.push({
                    data: forecast, color: "#d3dbd8",
                    bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 1.0, lineWidth:0}
                });
                hydroseries.push({
                    data: hydro_data, color: "#678278",
                    bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 1.0, lineWidth:0}
                });
                //hydroseries.push({
                //    data: consumption_profile, color: "#aaa",
                //    bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 0.3, lineWidth:0}
                //});
                hydro_resize(panel_height);
                
                cydynnistatus_update();
            }
        }
    });
}

function hydro_draw() {

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

    var plot = $.plot($('#hydro_bargraph_placeholder'),hydroseries,options);
    $('#hydro_bargraph_placeholder').append("<div id='bargraph-label' style='position:absolute;left:50px;top:30px;color:#666;font-size:12px'></div>");
}

function hydro_resize(panel_height) {
    
    var window_width = $(window).width();
    flot_font_size = 12;
    if (window_width<450) flot_font_size = 10;

    var h = panel_height-120;
    width = $("#hydro_bargraph_placeholder_bound").width();
    $("#hydro_bargraph_placeholder").width(width);
    $('#hydro_bargraph_placeholder_bound').height(h);
    $('#hydro_bargraph_placeholder').height(h);
    height = h;
    hydro_draw();
    
}

$(".hydro-left").click(function() {
    var time_window = end - start;
    end -= time_window * 0.5;
    start -= time_window * 0.5;
    hydro_load();
});

$(".hydro-right").click(function() {
    var time_window = end - start;
    end += time_window * 0.5;
    start += time_window * 0.5;
    hydro_load();
});

$(".day").click(function() {
    end = 0;
    start = 0;
    hydro_load();
});

$(".week").click(function() {
    end = +new Date;
    start = end - (3600000*24.0*7);
    hydro_load();
});

$(".month").click(function() {
    end = +new Date;
    start = end - (3600000*24.0*30);
    hydro_load();
});

$('#hydro_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    start = ranges.xaxis.from;
    end = ranges.xaxis.to;
    hydro_load();
});

$('#hydro_bargraph_placeholder').bind("plothover", function (event, pos, item) {
    if (item) {
        var z = item.dataIndex;
        
        if (previousPoint != item.datapoint) {
            previousPoint = item.datapoint;

            $("#tooltip").remove();
            var itemTime = item.datapoint[0];
            var elec_kwh = hydroseries[item.seriesIndex].data[z][1];
            var note = "";
            if (item.seriesIndex==0) note = "Forecast ";

            var d = new Date(itemTime);
            var days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
            var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
            var mins = d.getMinutes();
            if (mins==0) mins = "00";
            var date = d.getHours()+":"+mins+" "+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate();
            tooltip(item.pageX, item.pageY, date+"<br>"+note+(elec_kwh).toFixed(1)+" kWh", "#fff");
        }
    } else $("#tooltip").remove();
});
