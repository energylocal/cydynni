/*

Household page

*/

var end = 0;
var start = 0;

var household_pie_data = [];
var household_hydro_use = 0;
var householdseries = [];
var household_data = [];

var household_view = "piechart";

function household_load()
{
    if (household_view=="piechart") household_pie_load(); 
    if (household_view=="bargraph") household_bargraph_load(); 
}

function household_pie_load()
{    
  $.ajax({                                      
      url: path+"household/data",
      dataType: 'json',                  
      success: function(result) {
          
          // 1. Determine score
          // Calculated as amount of power consumed at times off peak times and from hydro
          var score = Math.round(100*((result.kwh.overnight + result.kwh.midday + result.kwh.hydro) / result.kwh.total));

          // Display score as number of stars
          // setTimeout fn used to animate 
          
          if (result.dayoffset==1) {
              $("#household_score_text").html(t("Yesterday you scored"));
          } else {
              $("#household_score_text").html(t("You scored")+" "+t("on")+" "+t(result.month)+" "+result.day);
          }
          
          $("#household_score").html(score);
          if (score>20) $("#star1").attr("src","images/starblue.png");
          if (score>40) setTimeout(function() { $("#star2").attr("src","images/starblue.png"); }, 100);
          if (score>60) setTimeout(function() { $("#star3").attr("src","images/starblue.png"); }, 200);
          if (score>80) setTimeout(function() { $("#star4").attr("src","images/starblue.png"); }, 300);
          if (score>90) setTimeout(function() { $("#star5").attr("src","images/starblue.png"); }, 400);
          
          // Show status summary ( below score stars )
          setTimeout(function() {
              if (score<30) {
                  $("#statusmsg").html(t("You are using power in a very expensive way"));
                  $("#household_status_summary").html(t("MISSING OUT"));
              }
              if (score>=30 && score<70) {
                  $("#statusmsg").html(t("You’re doing ok at using hydro & cheaper power.<br>Can you move more of your use away from peak times?"));
                  $("#household_status_summary").html(t("DOING OK"));
              }
              if (score>=70) {
                  $("#statusmsg").html(t("You’re doing really well at using hydro & cheaper power"));
                  $("#household_status_summary").html(t("DOING WELL"));
              }
          }, 400);
          
          // 2nd ssection showing total consumption and cost
          $(".totalcost").html(result.cost.total.toFixed(2));
          $(".totalkwh").html(result.kwh.total.toFixed(1));
          
          if (result.dayoffset==1) {
              $("#household-used-date").html(t("yesterday. Costing"));
          } else {
              $("#household-used-date").html(t("on")+" "+t(result.month)+" "+result.day+". "+t("Costing"));
          }
          
          // Saving calculation
          var totalcostflatrate = result.kwh.total * 0.12;
          var costsaving = totalcostflatrate - result.cost.total;
          $(".costsaving").html(costsaving.toFixed(2));
          
          // Summary for saving section
          if (result.dayoffset==1) {
              $("#household_saving_summary").html("£"+costsaving.toFixed(2)+" "+t("YESTERDAY"));
          } else {
              $("#household_saving_summary").html("£"+costsaving.toFixed(2)+" "+t(result.month)+" "+result.day);    
          }
          
          // Pie graph
          var data = [
            {name:t("MORNING"), value: result.kwh.morning, color:"#ffdc00"},
            {name:t("MIDDAY"), value: result.kwh.midday, color:"#29abe2"},
            {name:t("EVENING"), value: result.kwh.evening, color:"#c92760"},
            {name:t("OVERNIGHT"), value: result.kwh.overnight, color:"#274e3f"} 
          ];
          
          household_hydro_use = result.kwh.hydro;
          household_pie_data = data;
          household_pie_draw();
      } 
  });
}

function household_pie_draw() {
    var width = $("#household_piegraph_bound").width();
    if (width>400) width = 400;
    $("#household_piegraph_placeholder").attr('width',width);
    var height = width*0.9;
    $('#household_piegraph_bound').attr("height",height);
    $('#household_piegraph_placeholder').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    }; 

    piegraph("household_piegraph_placeholder",household_pie_data,household_hydro_use,options);
}


function household_bargraph_load() {
    var history = "";
    if (end>0 && start>0) history = "?start="+start+"&end="+end;
  
    var data = [];
    $.ajax({                                      
        url: path+"data"+history,
        dataType: 'json',
        async: true,                      
        success: function(result) {
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","invalid response: "+result);
            } else {

                household_data = result;
                var total = 0;
                for (var z in household_data) {
                   total += household_data[z][1];
                }
                console.log("Total kWh in window: "+total.toFixed(2));
                householdseries = [];
                
                householdseries.push({
                    data: household_data, color: "#4f80a3",
                    bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 1.0, lineWidth:0}
                });
                
                // householdseries.push({data:household_data, color:"rgba(0,71,121,0.7)"});
                household_bargraph_draw();
            }
        }
    });
}

function household_resize(panel_height) {
    household_pie_draw();
    household_bargraph_resize(panel_height-40);
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

    var plot = $.plot($('#household_bargraph_placeholder'),householdseries,options);
    $('#household_bargraph_placeholder').append("<div id='bargraph-label' style='position:absolute;left:50px;top:30px;color:#666;font-size:12px'></div>");

    //bargraph("household_bargraph_placeholder",householdseries, " kWh","rgba(0,71,121,0.7)");
}

function household_bargraph_resize(h) {

    var window_width = $(window).width();
    flot_font_size = 12;
    if (window_width<450) flot_font_size = 10;
    
    // width = $("#household_bargraph_bound").width();
    // $("#household_bargraph_placeholder").attr('width',width);
    // $('#household_bargraph_bound').attr("height",h);
    // $('#household_bargraph_placeholder').attr("height",h);
    // height = h;
    // household_bargraph_draw();

    var h = panel_height-40;
    width = $("#household_bargraph_placeholder_bound").width();
    $("#household_bargraph_placeholder").width(width);
    $('#household_bargraph_placeholder_bound').height(h);
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

$(".household-day").click(function() {
    end = 0;
    start = 0;
    household_bargraph_load();
});

$(".household-week").click(function() {
    end = +new Date;
    start = end - (3600000*24.0*7);
    household_bargraph_load();
});

$(".household-month").click(function() {
    end = +new Date;
    start = end - (3600000*24.0*30);
    household_bargraph_load();
});

$('#household_bargraph_placeholder').bind("plotselected", function (event, ranges) {
    start = ranges.xaxis.from;
    end = ranges.xaxis.to;
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
            var date = d.getHours()+":"+mins+" "+days[d.getDay()]+", "+months[d.getMonth()]+" "+d.getDate();
            tooltip(item.pageX, item.pageY, date+"<br>"+(elec_kwh).toFixed(1)+" kWh", "#fff");
        }
    } else $("#tooltip").remove();
});
