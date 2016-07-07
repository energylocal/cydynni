var household_pie_data = [];

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
                  $("#statusmsg").html("You are using power in a very expensive way");
                  $("#household_status_summary").html("MISSING OUT");
              }
              if (prc>=30 && prc<70) {
                  $("#statusmsg").html("Are you making the most of hydro? Can you adjust some activities away from peak times?");
                  $("#household_status_summary").html("DOING OK");
              }
              if (prc>=70) {
                  $("#statusmsg").html("You are doing really well to use hydro power and at cheaper times");
                  $("#household_status_summary").html("DOING WELL");
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
          $(".costsaving").html(costsaving.toFixed(1));
          $("#household_saving_summary").html("£"+costsaving.toFixed(2)+" LAST WEEK");
          
          var data = [
            {name:"MORNING", value: result.morningkwh, color:"rgba(0,71,121,0.8)"},
            {name:"MIDDAY", value: result.middaykwh, color:"rgba(0,71,121,0.6)"},
            {name:"EVENING", value: result.eveningkwh, color:"rgba(0,71,121,0.9)"},
            {name:"OVERNIGHT", value: result.overnightkwh, color:"rgba(0,71,121,0.4)"},
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
    $("#household_piegraph").attr('width',width);
    var height = width*0.9;
    $('#household_piegraph_bound').attr("height",height);
    $('#household_piegraph').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    }; 

    piegraph("household_piegraph",household_pie_data,options);
}
