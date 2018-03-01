/*

Household page

*/

var end = 0;
var start = 0;

end = +new Date;
start = end - (3600000*24.0*6);

var household_pie1_data = [];
var household_pie2_data = [];

var household_pie3_data_cost = [];
var household_pie3_data_energy = [];

var household_hydro_use = 0;
var householdseries = [];

var household_overnight_data = [];
var household_morning_data = [];
var household_evening_data = [];
var household_midday_data = [];

var household_data = [];

var household_view = "piechart";

var meterdataenable = false;

function household_summary_load()
{    
  $.ajax({                                      
      url: path+"household/summary/day",
      dataType: 'json',                  
      success: function(result) {
          
          // 1. Determine score
          // Calculated as amount of power consumed at times off peak times and from hydro
          var score = Math.round(100*((result.kwh.overnight + result.kwh.midday + result.kwh.hydro) / result.kwh.total));
          
          if (score>20) $("#household_star1").attr("src","images/starred.png");
          if (score>40) setTimeout(function() { $("#household_star2").attr("src","images/starred.png"); }, 100);
          if (score>60) setTimeout(function() { $("#household_star3").attr("src","images/starred.png"); }, 200);
          if (score>80) setTimeout(function() { $("#household_star4").attr("src","images/starred.png"); }, 300);
          if (score>90) setTimeout(function() { $("#household_star5").attr("src","images/starred.png"); }, 400);
          
          // Show status summary ( below score stars )
          setTimeout(function() {
              if (score<30) {
                  $(".household_status").html(t("You are using power in a very expensive way"));
              }
              if (score>=30 && score<70) {
                  $(".household_status").html(t("You’re doing ok at using hydro & cheaper power.<br>Can you move more of your use away from peak times?"));
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
          if (lang=="cy") ext = "";
          
          $(".household_date").html(result.day+t(ext)+" "+t(result.month));
          $(".household_score").html(score);
          $(".household_totalkwh").html(result.kwh.total.toFixed(1));
          $(".household_totalcost").html(result.cost.total.toFixed(2));
          
          // Saving calculation
          var totalcostflatrate = result.kwh.total * 0.12;
          var costsaving = totalcostflatrate - result.cost.total;
          $(".household_costsaving").html("£"+costsaving.toFixed(2));

          // household pie chart
          household_pie1_data = [
            {name:t("MORNING"), value: result.kwh.morning, color:"#ffdc00"},
            {name:t("MIDDAY"), value: result.kwh.midday, color:"#4abd3e"},
            {name:t("EVENING"), value: result.kwh.evening, color:"#c92760"},
            {name:t("OVERNIGHT"), value: result.kwh.overnight, color:"#274e3f"},
            {name:t("HYDRO"), value: result.kwh.hydro, color:"#29aae3"} 
          ];

          // household pie chart
          household_pie2_data = [
            {name:t("MORNING"), value: result.kwh.morning, color:"#ffdc00"},
            {name:t("MIDDAY"), value: result.kwh.midday, color:"#4abd3e"},
            {name:t("EVENING"), value: result.kwh.evening, color:"#c92760"},
            {name:t("OVERNIGHT"), value: result.kwh.overnight, color:"#274e3f"} 
          ];
          
          // household pie chart
          household_pie3_data_cost = [
            {name:t("MORNING"), hydro: result.hydro.morning*0.07, import: result.kwh.morning*0.12, color:"#ffdc00"},
            {name:t("MIDDAY"), hydro: result.hydro.midday*0.07, import: result.kwh.midday*0.10, color:"#4abd3e"},
            {name:t("EVENING"), hydro: result.hydro.evening*0.07, import: result.kwh.evening*0.14, color:"#c92760"},
            {name:t("OVERNIGHT"), hydro: result.hydro.overnight*0.07, import: result.kwh.overnight*0.0725, color:"#274e3f"} 
          ];
          
          // household pie chart
          household_pie3_data_energy = [
            {name:t("MORNING"), hydro: result.hydro.morning, import: result.kwh.morning, color:"#ffdc00"},
            {name:t("MIDDAY"), hydro: result.hydro.midday, import: result.kwh.midday, color:"#4abd3e"},
            {name:t("EVENING"), hydro: result.hydro.evening, import: result.kwh.evening, color:"#c92760"},
            {name:t("OVERNIGHT"), hydro: result.hydro.overnight, import: result.kwh.overnight, color:"#274e3f"} 
          ];
          
          $("#household_hydro_kwh").html(result.kwh.hydro);
          $("#household_morning_kwh").html(result.kwh.morning);
          $("#household_midday_kwh").html(result.kwh.midday);
          $("#household_evening_kwh").html(result.kwh.evening);
          $("#household_overnight_kwh").html(result.kwh.overnight);

          $("#household_hydro_cost").html((result.kwh.hydro*0.07).toFixed(2));
          $("#household_morning_cost").html((result.kwh.morning*0.12).toFixed(2));
          $("#household_midday_cost").html((result.kwh.midday*0.10).toFixed(2));
          $("#household_evening_cost").html((result.kwh.evening*0.14).toFixed(2));
          $("#household_overnight_cost").html((result.kwh.overnight*0.0725).toFixed(2));
          
          household_hydro_use = result.kwh.hydro;
          household_pie_draw();
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

function household_update_live() {

  $.ajax({                                      
      url: path+"meter/live",
      dataType: 'json',                  
      success: function(result) {
      
          $(".meterdata-power").html((result.power*1000)+"W");
          $(".meterdata-kwh").html((result.kwh)+" kWh");
      }
  });
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
    
    piegraph3("household_piegraph1_placeholder",household_pie3_data_energy,options);

   
    // Pie chart
    // piegraph2("household_piegraph2_placeholder",household_pie2_data,household_hydro_use,options);

    piegraph3("household_piegraph2_placeholder",household_pie3_data_cost,options);


    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: 50
    };
    
    hrbar("household_hrbar1_placeholder",household_pie3_data_energy,options); 
    hrbar("household_hrbar2_placeholder",household_pie3_data_cost,options); 
    // Hydro droplet
    // hydrodroplet("hydro_droplet_placeholder",(community_hydro_use*1).toFixed(1),{width: width,height: height});
}

function household_bargraph_load() {

    end = +new Date;
    start = end - (3600000*24.0*6);

    var history = "";
    if (end>0 && start>0) history = "&start="+start+"&end="+end+"&interval=1800";
  
    var data = [];
    $.ajax({                                      
        url: path+"feed/average.json?id=3&start="+start+"&end="+end+"&interval=1800&apikey="+session['apikey_read'],
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
                    data: household_data, color: "#e62f31",
                    bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 1.0, lineWidth:0}
                });
                
                household_bargraph_resize();
            }
        }
    });
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
