var community_pie_data = [];
var communityseries = [];

function community_load()
{
  $.ajax({                                      
      url: path+"community/data",
      dataType: 'json',                  
      success: function(result) {
          var prc = Math.round(100*((result.overnightkwh + result.middaykwh) / result.totalkwh));
          $("#community_prclocal").html(prc);
          
          $("#community_score").html(prc);
          
          if (prc>20) $("#community_star1").attr("src","images/staryellow.png");
          if (prc>40) setTimeout(function() { $("#community_star2").attr("src","images/staryellow.png"); }, 100);
          if (prc>60) setTimeout(function() { $("#community_star3").attr("src","images/staryellow.png"); }, 200);
          if (prc>80) setTimeout(function() { $("#community_star4").attr("src","images/staryellow.png"); }, 300);
          if (prc>90) setTimeout(function() { $("#community_star5").attr("src","images/staryellow.png"); }, 400);
          
          setTimeout(function() {
              if (prc<30) {
                  $("#community_statusmsg").html(t("We are using power in a very expensive way"));
                  $("#community_status_summary").html(t("As a community we are MISSING OUT"));
              }
              if (prc>=30 && prc<70) {
                  $("#community_statusmsg").html(t("We could do more to make the most of the hydro power and power at cheaper times of day. Can we move more electricity use away from peak times?"));
                  $("#community_status_summary").html(t("As a community we are <b>DOING OK</b>"));
              }
              if (prc>=70) {
                  $("#community_statusmsg").html(t("We’re doing really well using the hydro and cheaper power"));
                  $("#community_status_summary").html(t("As a community we are <b>DOING WELL</b>"));
              }
          }, 400);
          
          var totalcost = 0;
          totalcost += result.morningkwh * 0.12;
          totalcost += result.middaykwh * 0.10;
          totalcost += result.eveningkwh * 0.14;
          totalcost += result.overnightkwh * 0.0725;
          $(".community_totalcost").html(totalcost.toFixed(2));
          $(".community_totalkwh").html(result.totalkwh.toFixed(1));
          
          var totalcostflatrate = result.totalkwh * 0.12;
          var costsaving = totalcostflatrate - totalcost;
          $(".community_costsaving").html(costsaving.toFixed(2));
          $("#community_saving_summary").html("£"+totalcost.toFixed(2)+" "+t("LAST WEEK"));
          
          var data = [
            {name:t("MORNING"), value: result.morningkwh, color:"#ffdc00"},
            {name:t("MIDDAY"), value: result.middaykwh, color:"#29abe2"},
            {name:t("EVENING"), value: result.eveningkwh, color:"#c92760"},
            {name:t("OVERNIGHT"), value: result.overnightkwh, color:"#274e3f"},
            // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
          ];
          
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
    
    piegraph("community_piegraph_placeholder",community_pie_data,options);
}

function community_bargraph_load() {

    var end = +new Date;
    var start = end - (3600000*24.0*1);
    var interval = 1800;
    var intervalms = interval * 1000;
    end = Math.floor(end / intervalms) * intervalms;
    start = Math.floor(start / intervalms) * intervalms;
    
    var data = [];
    $.ajax({                                      
        url: path+"community/halfhourlydata",                         
        data: "start="+start+"&end="+end,
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

                communityseries = [];
                communityseries.push({data:hydro_data, color:"rgba(142,77,0,0.7)"});
                
                community_bargraph_draw();
            }
        }
    });
}

function community_bargraph_draw() {
    bargraph("community_bargraph_placeholder",communityseries," W");
}

function community_bargraph_resize(h) {
    width = $("#community_bargraph_bound").width();
    $("#community_bargraph_placeholder").attr('width',width);
    $('#community_bargraph_bound').attr("height",h);
    $('#community_bargraph_placeholder').attr("height",h);
    height = h;
    community_bargraph_draw();
}

$("#view-community-bargraph").click(function(){
    $("#view-community-bargraph").hide();
    $("#view-community-piechart").show();
    
    $("#community_piegraph").hide();
    $("#community_bargraph").show();
    
    community_bargraph_load();
});

$("#view-community-piechart").click(function(){
    $("#view-community-bargraph").show();
    $("#view-community-piechart").hide();
    
    $("#community_piegraph").show();
    $("#community_bargraph").hide();
});
