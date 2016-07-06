
function piegraph(element,data,options) {
    var size = options.width;
    var midx = options.width * 0.5;
    var midy = options.height * 0.5;
    
    var total = 0;
    for (z in data) total += data[z].value;
    
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    
    //ctx.fillStyle = "rgba(255,255,255,0.2)";
    //ctx.fillRect(0,0,options.width,options.height);
    
    var x = 0;
    var l = -Math.PI *0.5;
    var alphainc = 0.6/data.length;
    var alpha = 1.0;

    ctx.textAlign = "center";
    ctx.font=Math.round(0.0375*size)+"px Arial";
    
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 2;
    for (z in data) {
      x += data[z].value;
      
      var lastl = l
      l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
    
      var tl = (l + lastl)*-0.5 + (Math.PI *0.5);
      var labelx = midx+Math.sin(tl)*size*0.4;
      var labely = midy+Math.cos(tl)*size*0.4;
      
      ctx.fillStyle = options.color;
      ctx.fillText(data[z].name,labelx,labely-3);
      ctx.fillText(data[z].value.toFixed(1)+" kWh",labelx,labely+15);
    
      alpha -= alphainc;
      ctx.fillStyle = data[z].color;
      ctx.beginPath();
      ctx.arc(midx,midy,size*0.2875,lastl,l,false);
      ctx.arc(midx,midy,size*0.125,l,lastl,true);
      ctx.fill();
      ctx.stroke();
    }
    
    ctx.fillStyle = options.color;

    ctx.fillText(options.centertext,midx,midy-3);
    ctx.fillText(Math.round(total)+" kWh",midx,midy+15);
}

