/*

Household page

*/

var end = 0;
var start = 0;

var household_pie1_data = [];
var household_pie2_data = [];

var household_pie3_data_cost = [];
var household_pie3_data_energy = [];

var household_generation_use = 0;
var householdseries = [];

var household_overnight_data = [];
var household_morning_data = [];
var household_evening_data = [];
var household_midday_data = [];

var household_data = [];

var household_view = "piechart";

function household_summary_load()
{    
  $.ajax({                                      
      url: path+club_name+"/household/summary/day",
      dataType: 'json',                  
      success: function(result) {
          
          // 1. Determine score
          // Calculated as amount of power consumed at times off peak times and from generation
          var score = Math.round(100*((result.kwh.overnight + result.kwh.midday + result.kwh.generation) / result.kwh.total));
          
          if (score>20) $("#household_star1").attr("src",path+"images/starred.png");
          if (score>40) setTimeout(function() { $("#household_star2").attr("src",path+"images/starred.png"); }, 100);
          if (score>60) setTimeout(function() { $("#household_star3").attr("src",path+"images/starred.png"); }, 200);
          if (score>80) setTimeout(function() { $("#household_star4").attr("src",path+"images/starred.png"); }, 300);
          if (score>90) setTimeout(function() { $("#household_star5").attr("src",path+"images/starred.png"); }, 400);
          
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
            {name:t("HYDRO"), value: result.kwh.generation, color:"#29aae3"} 
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
            {name:t("MORNING"), generation: result.generation.morning*0.07, import: result.kwh.morning*0.12, color:"#ffdc00"},
            {name:t("MIDDAY"), generation: result.generation.midday*0.07, import: result.kwh.midday*0.10, color:"#4abd3e"},
            {name:t("EVENING"), generation: result.generation.evening*0.07, import: result.kwh.evening*0.14, color:"#c92760"},
            {name:t("OVERNIGHT"), generation: result.generation.overnight*0.07, import: result.kwh.overnight*0.0725, color:"#274e3f"} 
          ];
          
          // household pie chart
          household_pie3_data_energy = [
            {name:t("MORNING"), generation: result.generation.morning, import: result.kwh.morning, color:"#ffdc00"},
            {name:t("MIDDAY"), generation: result.generation.midday, import: result.kwh.midday, color:"#4abd3e"},
            {name:t("EVENING"), generation: result.generation.evening, import: result.kwh.evening, color:"#c92760"},
            {name:t("OVERNIGHT"), generation: result.generation.overnight, import: result.kwh.overnight, color:"#274e3f"} 
          ];
          
          $("#household_generation_kwh").html(result.kwh.generation);
          $("#household_morning_kwh").html(result.kwh.morning);
          $("#household_midday_kwh").html(result.kwh.midday);
          $("#household_evening_kwh").html(result.kwh.evening);
          $("#household_overnight_kwh").html(result.kwh.overnight);

          $("#household_generation_cost").html((result.kwh.generation*0.07).toFixed(2));
          $("#household_morning_cost").html((result.kwh.morning*0.12).toFixed(2));
          $("#household_midday_cost").html((result.kwh.midday*0.10).toFixed(2));
          $("#household_evening_cost").html((result.kwh.evening*0.14).toFixed(2));
          $("#household_overnight_cost").html((result.kwh.overnight*0.0725).toFixed(2));
          
          household_generation_use = result.kwh.generation;
          household_pie_draw();
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
    // piegraph2("household_piegraph2_placeholder",household_pie2_data,household_generation_use,options);

    piegraph3("household_piegraph2_placeholder",household_pie3_data_cost,options);


    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: 50
    };
    
    hrbar("household_hrbar1_placeholder",household_pie3_data_energy,options); 
    hrbar("household_hrbar2_placeholder",household_pie3_data_cost,options); 
    // generation droplet
    // generationdroplet("generation_droplet_placeholder",(community_generation_use*1).toFixed(1),{width: width,height: height});
}

function household_bargraph_load() {
    var history = "";
    if (end>0 && start>0) history = "?start="+start+"&end="+end;
  
    var data = [];
    $.ajax({                                      
        url: path+club_name+"/data"+history,
        dataType: 'json',
        async: true,                      
        success: function(result) {
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","invalid response: "+result);
            } else {

                household_data = result;
                var total = 0

                for (var z in household_data) {    
                    var time = household_data[z][0];    
                    var d = new Date(time);
                    var hour = d.getHours();
                    
                    var use = household_data[z][1];
                    
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
                
                console.log("Total kWh in window: "+total.toFixed(2));  
                
                var barwidth = 0.75*3600*0.5*1000;
                
                householdseries = [];

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
                
                //householdseries.push({
                //    data: household_data, color: "#e62f31",
                //    bars: { show: true, align: "center", barWidth: 0.75*3600*0.5*1000, fill: 1.0, lineWidth:0}
                //});
                
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
