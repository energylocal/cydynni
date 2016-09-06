var household_pie_data = [];
var householdseries = [];

function household_load()
{
  $.ajax({                                      
      url: path+"household/data",
      dataType: 'json',                  
      success: function(result) {
          var prc = Math.round(100*((result.overnightkwh + result.middaykwh) / result.totalkwh));
          $("#prclocal").html(prc);
          $("#household_score").html(prc);
          if (prc>20) $("#star1").attr("src","images/starblue.png");
          if (prc>40) setTimeout(function() { $("#star2").attr("src","images/starblue.png"); }, 100);
          if (prc>60) setTimeout(function() { $("#star3").attr("src","images/starblue.png"); }, 200);
          if (prc>80) setTimeout(function() { $("#star4").attr("src","images/starblue.png"); }, 300);
          if (prc>90) setTimeout(function() { $("#star5").attr("src","images/starblue.png"); }, 400);
          
          setTimeout(function() {
              if (prc<30) {
                  $("#statusmsg").html(t("You are using power in a very expensive way"));
                  $("#household_status_summary").html(t("MISSING OUT"));
              }
              if (prc>=30 && prc<70) {
                  $("#statusmsg").html(t("You’re doing ok at using hydro & cheaper power.<br>Can you move more of your use away from peak times?"));
                  $("#household_status_summary").html(t("DOING OK"));
              }
              if (prc>=70) {
                  $("#statusmsg").html(t("You’re doing really well at using hydro & cheaper power"));
                  $("#household_status_summary").html(t("DOING WELL"));
              }
          }, 400);
          
          $(".morningkwh").html(result.morningkwh.toFixed(1));
          $(".middaykwh").html(result.middaykwh.toFixed(1));
          $(".eveningkwh").html(result.eveningkwh.toFixed(1));
          $(".overnightkwh").html(result.overnightkwh.toFixed(1));
          
          var totalcost = 0;
          totalcost += result.morningkwh * 0.12;
          totalcost += result.middaykwh * 0.10;
          totalcost += result.eveningkwh * 0.14;
          totalcost += result.overnightkwh * 0.0725;
          $(".totalcost").html(totalcost.toFixed(2));
          $(".totalkwh").html(result.totalkwh.toFixed(1));
          
          var totalcostflatrate = result.totalkwh * 0.12;
          var costsaving = totalcostflatrate - totalcost;
          $(".costsaving").html(costsaving.toFixed(2));
          $("#household_saving_summary").html("£"+costsaving.toFixed(2)+" "+t("LAST WEEK"));
          
          var data = [
            {name:t("MORNING"), value: result.morningkwh, color:"rgba(0,71,121,0.8)"},
            {name:t("MIDDAY"), value: result.middaykwh, color:"rgba(0,71,121,0.6)"},
            {name:t("EVENING"), value: result.eveningkwh, color:"rgba(0,71,121,0.9)"},
            {name:t("OVERNIGHT"), value: result.overnightkwh, color:"rgba(0,71,121,0.4)"},
            // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
          ];
          
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

    piegraph("household_piegraph_placeholder",household_pie_data,options);
}


function household_bargraph_load() {

    var end = +new Date;
    var start = end - (3600000*24.0*1);
    var interval = 1800;
    var intervalms = interval * 1000;
    end = Math.floor(end / intervalms) * intervalms;
    start = Math.floor(start / intervalms) * intervalms;
      
    var data = [];
    $.ajax({                                      
        url: path+"average?apikey="+session.apikey,                         
        data: "id="+session.feedid+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing=1&limitinterval=1",
        dataType: 'json',
        async: true,                      
        success: function(result) {
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","feed.getdata invalid response: "+result);
            } else {

                var hydro_data = result;
                // Solar values less than zero are invalid
                for (var z in hydro_data)
                    if (hydro_data[z][1]<0) hydro_data[z][1]=0;

                householdseries = [];
                householdseries.push({data:hydro_data, color:"rgba(0,71,121,0.7)"});
                
                household_bargraph_draw();
            }
        }
    });
}

function household_bargraph_draw() {
    bargraph("household_bargraph_placeholder",householdseries, " W");
}

function household_bargraph_resize(h) {
    width = $("#household_bargraph_bound").width();
    $("#household_bargraph_placeholder").attr('width',width);
    $('#household_bargraph_bound').attr("height",h);
    $('#household_bargraph_placeholder').attr("height",h);
    height = h;
    household_bargraph_draw();
}

$("#view-household-bargraph").click(function(){
    $("#view-household-bargraph").hide();
    $("#view-household-piechart").show();
    
    $("#household_piegraph").hide();
    $("#household_bargraph").show();
    
    household_bargraph_load();
});

$("#view-household-piechart").click(function(){
    $("#view-household-bargraph").show();
    $("#view-household-piechart").hide();
    
    $("#household_piegraph").show();
    $("#household_bargraph").hide();
});
