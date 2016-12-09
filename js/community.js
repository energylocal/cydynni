/*

Community page

*/

var community_pie_data = [];
var communityseries = [];
var community_score = -1;
var community_hydro_use = 0;

function community_load()
{
  $.ajax({                                      
      url: path+"community/data",
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
    
    var data = [];
    $.ajax({                                      
        url: path+"community/halfhourlydata",
        dataType: 'json',
        async: true,                      
        success: function(result) {
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","invalid response: "+result);
            } else {

                var community_data = result
                var total = 0;
                for (var z in community_data) {
                   total += community_data[z][1];
                }
                console.log("Total kWh in window: "+total.toFixed(2));
                
                communityseries = [];
                communityseries.push({data:community_data, color:"rgba(142,77,0,0.7)"});
                community_bargraph_draw();
            }
        }
    });
}

function community_resize(panel_height) 
{
    community_pie_draw();
    community_bargraph_resize(panel_height-40);

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
    width = $("#community_bargraph_bound").width();
    $("#community_bargraph_placeholder").attr('width',width);
    $('#community_bargraph_bound').attr("height",h);
    $('#community_bargraph_placeholder').attr("height",h);
    height = h
    community_bargraph_draw();
}

function community_bargraph_draw() {
    bargraph("community_bargraph_placeholder",communityseries," kWh");
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
