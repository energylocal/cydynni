function household_load()
{
  $.ajax({                                      
      url: path+"household/data",
      dataType: 'json',                  
      success: function(result) {
          var prc = Math.round(100*((result.overnightkwh + result.middaykwh) / result.totalkwh));
          $("#prclocal").html(prc);
          
          if (prc>20) $("#star1").attr("src","images/star.png");
          if (prc>40) setTimeout(function() { $("#star2").attr("src","images/star.png"); }, 100);
          if (prc>60) setTimeout(function() { $("#star3").attr("src","images/star.png"); }, 200);
          if (prc>80) setTimeout(function() { $("#star4").attr("src","images/star.png"); }, 300);
          if (prc>90) setTimeout(function() { $("#star5").attr("src","images/star.png"); }, 400);
          
          setTimeout(function() {
              if (prc<33) $("#statusmsg").html("You're missing out<br>HELP ME IMPROVE");
              if (prc>=33 && prc<66) $("#statusmsg").html("You're doing <b>OK</b>");
              if (prc>=66) $("#statusmsg").html("You're doing <b>GREAT</b>");
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
          
          
          var data = [
            {name:"AM PEAK", value: result.morningkwh, color:"rgba(255,255,255,0.8)"},
            {name:"DAYTIME", value: result.middaykwh, color:"rgba(255,255,255,0.6)"},
            {name:"PM PEAK", value: result.eveningkwh, color:"rgba(255,255,255,0.9)"},
            {name:"NIGHT", value: result.overnightkwh, color:"rgba(255,255,255,0.4)"},
            // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
          ];
          
          piegraph("piegraph",data);
          
      } 
  });
}

function piegraph(element,data) {
    var size = 400;
    var mid = size * 0.5;

    var total = 0;
    for (z in data) total += data[z].value;
    
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
        
    var x = 0;
    var l = -Math.PI *0.5;
    var alphainc = 0.6/data.length;
    var alpha = 1.0;

    ctx.textAlign = "center";
    ctx.font="15px Arial"
    
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 2;
    for (z in data) {
      x += data[z].value;
      
      var lastl = l
      l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
    
      var tl = (l + lastl)*-0.5 + (Math.PI *0.5);
      var labelx = mid+Math.sin(tl)*160;
      var labely = mid+Math.cos(tl)*160;
      
      ctx.fillStyle = "#fff";
      ctx.fillText(data[z].name,labelx,labely-3);
      ctx.fillText(data[z].value.toFixed(1)+" kWh",labelx,labely+15);
    
      alpha -= alphainc;
      console.log(alpha);
      ctx.fillStyle = data[z].color;
      ctx.beginPath();
      ctx.arc(mid,mid,110,lastl,l,false);
      ctx.arc(mid,mid,45,l,lastl,true);
      ctx.fill();
      ctx.stroke();
    }
    
    ctx.fillStyle = "#fff";

    ctx.fillText("THIS WEEK",mid,mid-3);
    ctx.fillText(Math.round(total)+" kWh",mid,mid+15);
}

