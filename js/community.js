var community_pie_data = [];

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
                  $("#community_statusmsg").html("We are using power in a very expensive way");
                  $("#community_status_summary").html("As a community we are MISSING OUT");
              }
              if (prc>=30 && prc<70) {
                  $("#community_statusmsg").html("We could do more to make the most of the hydro power and power at cheaper times of day. Can we move more electricity use away from peak times?");
                  $("#community_status_summary").html("As a community we are <b>DOING OK</b>");
              }
              if (prc>=70) {
                  $("#community_statusmsg").html("We’re doing really well using the hydro and cheaper power");
                  $("#community_status_summary").html("As a community we are <b>DOING WELL</b>");
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
          $("#community_saving_summary").html("£"+totalcost.toFixed(2)+" LAST WEEK");
          
          var data = [
            {name:"MORNING", value: result.morningkwh, color:"#ffdc00"},
            {name:"MIDDAY", value: result.middaykwh, color:"#29abe2"},
            {name:"EVENING", value: result.eveningkwh, color:"#c92760"},
            {name:"OVERNIGHT", value: result.overnightkwh, color:"#274e3f"},
            // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
          ];
          
          community_pie_data = data;
          community_pie_draw();
      } 
  });
}

function community_pie_draw() {
    var width = $("#community_piegraph_bound").width();
    if (width>400) width = 400;
    $("#community_piegraph").attr('width',width);
    var height = width*0.9;
    $('#community_piegraph_bound').attr("height",height);
    $('#community_piegraph').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    };  
    
    piegraph("community_piegraph",community_pie_data,options);
}
