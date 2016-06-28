
function piegraph(element,data,options) {
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
    ctx.font="15px Arial";
    
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 2;
    for (z in data) {
      x += data[z].value;
      
      var lastl = l
      l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
    
      var tl = (l + lastl)*-0.5 + (Math.PI *0.5);
      var labelx = mid+Math.sin(tl)*160;
      var labely = mid+Math.cos(tl)*160;
      
      ctx.fillStyle = options.color;
      ctx.fillText(data[z].name,labelx,labely-3);
      ctx.fillText(data[z].value.toFixed(1)+" kWh",labelx,labely+15);
    
      alpha -= alphainc;
      console.log(alpha);
      ctx.fillStyle = data[z].color;
      ctx.beginPath();
      ctx.arc(mid,mid,115,lastl,l,false);
      ctx.arc(mid,mid,50,l,lastl,true);
      ctx.fill();
      ctx.stroke();
    }
    
    ctx.fillStyle = options.color;

    ctx.fillText(options.centertext,mid,mid-3);
    ctx.fillText(Math.round(total)+" kWh",mid,mid+15);
}

