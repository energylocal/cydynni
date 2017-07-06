/*

Community page

*/

var end = 0;
var start = 0;

var community_pie_data = [];
var community_data = [];
var exported_hydro_data = [];
var used_hydro_data = [];
var communityseries = [];

var community_score = -1;
var community_hydro_use = 0;
var community_view = "piechart";
var community_height = 0;

function community_load() {
    if (community_view=="piechart") community_pie_load(); 
    if (community_view=="bargraph") community_bargraph_load(); 
}

function community_pie_load()
{
  $.ajax({                                      
      url: path+"community/summary/day",
      dataType: 'json',                  
      success: function(result) {
          var score = Math.round(100*((result.kwh.overnight + result.kwh.midday + result.kwh.hydro) / result.kwh.total));
          
          if (result.dayoffset==1) {
              $("#community_score_text").html(t("Yesterday we scored"));
          } else {
              if (result.month==undefined) {
                  $("#community_score_text").html(t("We scored"));
              } else {
                  $("#community_score_text").html(t("We scored")+" "+t("on")+" "+t(result.month)+" "+result.day);
              }
          }
          
          $("#community_score").html(score);
          if (score>20) $("#community_star1").attr("src","images/staryellow.png");
          if (score>40) setTimeout(function() { $("#community_star2").attr("src","images/staryellow.png"); }, 100);
          if (score>60) setTimeout(function() { $("#community_star3").attr("src","images/staryellow.png"); }, 200);
          if (score>80) setTimeout(function() { $("#community_star4").attr("src","images/staryellow.png"); }, 300);
          if (score>90) setTimeout(function() { $("#community_star5").attr("src","images/staryellow.png"); }, 400);
          
          setTimeout(function() {
              if (score<30) {
                  $("#community_statusmsg").html(t("We are using power in a very expensive way"));
              }
              if (score>=30 && score<70) {
                  $("#community_statusmsg").html(t("We could do more to make the most of the hydro power and power at cheaper times of day. Can we move more electricity use away from peak times?"));
              }
              if (score>=70) {
                  $("#community_statusmsg").html(t("We’re doing really well using the hydro and cheaper power"));
              }
              community_resize();
          }, 400);
          
          // Hydro value retained in the community
          var hydro_value = result.kwh.hydro * 0.07;
          
          // 2nd ssection showing total consumption and cost
          $(".community_hydro_value").html((hydro_value).toFixed(2));
          $("#community_value_summary").html("£"+(hydro_value).toFixed(2)+" "+t("kept in the community"));
          
          // Community pie chart
          var data = [
            {name:t("MORNING"), value: result.kwh.morning, color:"#ffdc00"},
            {name:t("MIDDAY"), value: result.kwh.midday, color:"#29abe2"},
            {name:t("EVENING"), value: result.kwh.evening, color:"#c92760"},
            {name:t("OVERNIGHT"), value: result.kwh.overnight, color:"#274e3f"} 
          ];
          
          community_hydro_use = result.kwh.hydro
          community_pie_data = data;
          community_pie_draw();
      } 
  });
}

function community_pie_draw() {
    var width = $("#community_piegraph_bound").width();
    var height = $("#community_piegraph_bound").height();
    if (width>400) width = 400;
    $("#community_piegraph_placeholder").attr('width',width);
    var height = width*0.9;
    $('#community_piegraph_bound').attr("height",height);
    $('#community_piegraph_placeholder').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    };  
    
    piegraph("community_piegraph_placeholder",community_pie_data,community_hydro_use,options);
}

function community_bargraph_load() {

    var history = "";
    if (end>0 && start>0) history = "?start="+start+"&end="+end;
    
    var data = [];
    
    $.ajax({                                      
        url: path+"hydro"+history,
        dataType: 'json',
        async: true,                      
        success: function(result) {
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","invalid response: "+result);
            } else {

                hydro_data = result;
                if (hydro_data.length>0) {
                    for (var z in hydro_data)
                        if (hydro_data[z][1]<0) hydro_data[z][1]=0;
                }
                
                // -------------------------------------------------------------------------------
                $.ajax({                                      
                    url: path+"community/data"+history,
                    dataType: 'json',
                    async: true,                      
                    success: function(result) {
                        if (!result || result===null || result==="" || result.constructor!=Array) {
                            console.log("ERROR","invalid response: "+result);
                        } else {

                            community_data = result;
                            var total = 0;
                            for (var z in community_data) {
                               total += community_data[z][1];
                            }
                            console.log("Total kWh in window: "+total.toFixed(2));

                            // -------------------------------------------------------------------------
                            // Colour code graph
                            // -------------------------------------------------------------------------
                            var morning = "#ffdc00";
                            var midday = "#29abe2";
                            var evening = "#c92760";
                            var overnight = "#274e3f";
                            
                            var morning_data = [];
                            var midday_data = [];
                            var evening_data = [];
                            var overnight_data = [];
                            exported_hydro_data = [];
                            used_hydro_data = [];
                
                            for (var z in community_data) {    
                                var time = community_data[z][0];      
                                var value = community_data[z][1];     
                                var d = new Date(time);
                                var hour = d.getHours();
                                
                                if (hour<6) overnight_data.push([time,value]);
                                if (hour>=6 && hour<11) morning_data.push([time,value]);
                                if (hour>=11 && hour<16) midday_data.push([time,value]);
                                if (hour>=16 && hour<20) evening_data.push([time,value]);
                                if (hour>=20) overnight_data.push([time,value]);
                                
                                // Calculate exported hydro
                                used_hydro_data[z] = [time,hydro_data[z][1]];
                                exported_hydro_data[z] = [time,0];
                                // When available hydro is more than community consumption
                                if (hydro_data[z][1]>community_data[z][1]) {
                                    exported_hydro_data[z][1] = hydro_data[z][1];
                                    used_hydro_data[z][1] = community_data[z][1];
                                }
                            }
                            
                            // ----------------------------------------------------------------------------
                            // HYDRO FORECAST USING YNNI PADARN PERIS DATA
                            // ----------------------------------------------------------------------------
                            var lasttime = hydro_data[hydro_data.length-1][0];
                            var lastvalue = hydro_data[hydro_data.length-1][1];
                            
                            forecast = [];
                            $.ajax({                                      
                                url: path+"hydro/forecast"+history+"&lasttime="+lasttime+"&lastvalue="+lastvalue,
                                dataType: 'json',
                                async: false,                      
                                success: function(ccdata) {
                                forecast = ccdata;
                                
                            }});
                            
                            // ----------------------------------------------------------------------------
                            // CONSUMPTION FORECAST
                            // ----------------------------------------------------------------------------
                            var d1 = new Date();
                            var t1 = d1.getTime()*0.001;
                            if (end>0) t1 = end * 0.001;
                            var d3 = new Date(hydro_data[hydro_data.length-1][0]);
                            var t3 = d3.getTime()*0.001;
                            var half_hours_behind = Math.floor((t1 - t3) / 1800);
                            
                            var community_forecast_raw = [];
                            consumption_profile = [];
                            $.ajax({                                      
                                url: path+"community/forecast",
                                dataType: 'json',
                                async: false,                      
                                success: function(result) {
                                    var community_forecast_raw = result;
                                    
                                    consumption_profile = [];
                                    for (var h=0; h<half_hours_behind-1; h++) {
                                        consumption_profile.push([time+((h+1)*1800*1000),community_forecast_raw[h%48]]);
                                    }
                            }});
                            
                            
                            communityseries = [];
                            
                            var widthprc = 0.75;

                            // Forecast
                            communityseries.push({
                                data: forecast, color: "#d3dbd8",
                                bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 1.0, lineWidth:0}
                            });
                            communityseries.push({
                                data: consumption_profile, color: "#aaa",
                                bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 0.3, lineWidth:0}
                            });         
                            
                            communityseries.push({
                                data: exported_hydro_data, color: "#92cbe3",
                                bars: { show: true, align: "center", barWidth: widthprc*3600*0.5*1000, fill: 1.0, lineWidth:0}
                            });
                            communityseries.push({
                                data: overnight_data, color: "#274e3f",
                                bars: { show: true, align: "center", barWidth: widthprc*3600*0.5*1000, fill: 1.0, lineWidth:0}
                            });
                            communityseries.push({
                                data: morning_data, color: "#ffdc00",
                                bars: { show: true, align: "center", barWidth: widthprc*3600*0.5*1000, fill: 1.0, lineWidth:0}
                            });
                            communityseries.push({
                                data: midday_data, color: "#29abe2",
                                bars: { show: true, align: "center", barWidth: widthprc*3600*0.5*1000, fill: 1.0, lineWidth:0}
                            });
                            communityseries.push({
                                data: evening_data, color: "#c92760",
                                bars: { show: true, align: "center", barWidth: widthprc*3600*0.5*1000, fill: 1.0, lineWidth:0}
                            });
                            communityseries.push({
                                data: used_hydro_data, color: "#00cc00",
                                bars: { show: true, align: "center", barWidth: widthprc*3600*0.5*1000, fill: 1.0, lineWidth:0}
                            });
                            

                            community_bargraph_draw();
                            
                            // Show day instead of "last 24 hour"
                            var d1 = new Date();
                            var t1 = d1.getTime()*0.001;
                            var d2 = new Date(community_data[0][0]);
                            var t2 = d2.getTime()*0.001;
                            var dayoffset = Math.floor((t1-t2)/(3600*24));
                            console.log("Days behind: "+dayoffset);
                            
                            var hour = d2.getHours();
                            var month = d2.getMonth();
                            var day = d2.getDate();
                            if (hour>=12) hour=(hour-12)+"pm"; else hour=hour+"am";
                            var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
                            
                            if (dayoffset==1) {
                                $("#community-graph-date").html(t("Yesterday")+":");
                            } else {
                                $("#community-graph-date").html(day+" "+t(months[month]));
                            }
                        }
                    }
                });
            // -----------------------------------------------------------------------
            }
       }
    });
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
        
    // width = $("#community_bargraph_bound").width();
    // $("#community_bargraph_placeholder").attr('width',width);
    // $( '#community_bargraph_bound').attr("height",h);
    // $('#community_bargraph_placeholder').attr("height",h);
    // height = h
    // community_bargraph_draw();
}

function community_bargraph_draw() {

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

    var plot = $.plot($('#community_bargraph_placeholder'),communityseries,options);
    $('#community_bargraph_placeholder').append("<div id='bargraph-label' style='position:absolute;left:50px;top:30px;color:#666;font-size:12px'></div>");
}

$("#view-community-bargraph").click(function(){
    $("#view-community-bargraph").hide();
    $("#view-community-piechart").show();
    $("#community_piegraph").hide();
    $("#community_bargraph").show();
    community_view = "bargraph";
    community_bargraph_load();
});

$("#view-community-piechart").click(function(){
    $("#view-community-bargraph").show();
    $("#view-community-piechart").hide();
    $("#community_piegraph").show();
    $("#community_bargraph").hide();
    community_view = "piechart";
});

$(".community-left").click(function() {
    var time_window = end - start;
    end -= time_window * 0.5;
    start -= time_window * 0.5;
    community_bargraph_load();
});

$(".community-right").click(function() {
    var time_window = end - start;
    end += time_window * 0.5;
    start += time_window * 0.5;
    community_bargraph_load();
});

$(".community-day").click(function() {
    end = 0;
    start = 0;
    community_bargraph_load();
});

$(".community-week").click(function() {
    end = +new Date;
    start = end - (3600000*24.0*7);
    community_bargraph_load();
});

$(".community-month").click(function() {
    end = +new Date;
    start = end - (3600000*24.0*30);
    community_bargraph_load();
});

$('#community_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    start = ranges.xaxis.from;
    end = ranges.xaxis.to;
    community_bargraph_load();
});

$('#community_bargraph_placeholder').bind("plothover", function (event, pos, item) {
    if (item) {
        var z = item.dataIndex;
        
        if (previousPoint != item.datapoint) {
            previousPoint = item.datapoint;

            $("#tooltip").remove();
            var itemTime = item.datapoint[0];
            var elec_kwh = communityseries[item.seriesIndex].data[z][1];

            var d = new Date(itemTime);
            var days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
            var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
            var mins = d.getMinutes();
            if (mins==0) mins = "00";
            var date = d.getHours()+":"+mins+" "+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate();
            tooltip(item.pageX, item.pageY, date+"<br>"+(elec_kwh).toFixed(1)+" kWh", "#fff");
        }
    } else $("#tooltip").remove();
});
