
function piegraph(element,data,options) {
    var size = options.width;
    var midx = options.width * 0.4;
    var midy = options.height * 0.5;
    
    var total = 0;
    for (z in data) total += data[z].value;
    
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    
    //ctx.fillStyle = "rgba(255,100,100,0.2)";
    //ctx.fillRect(0,0,options.width,options.height);
    
    ctx.clearRect(0,0,options.width,options.height);
    
    var x = 0;
    var l = -Math.PI *0.5;
    var alphainc = 0.6/data.length;
    var alpha = 1.0;
    var textsize = Math.round(0.0375*size);

    ctx.textAlign = "center";
    ctx.font=textsize+"px Arial";
    
    //-------------------------------------------------------
    // Hydro segment
    //-------------------------------------------------------
    midx = options.width * 0.85;
    
    ctx.strokeStyle = "#00aa00";
    ctx.fillStyle = "#00cc00";

    ctx.beginPath();
    ctx.arc(midx,midy,0.1*size,-0.40,(1.0*Math.PI)+0.40,false);
    ctx.lineTo(midx,midy-0.18*size);
    ctx.closePath();
    ctx.fill();
    ctx.stroke();
    
    ctx.fillStyle = "#fff";
    ctx.font=Math.round(0.0375*size*0.8)+"px Arial";
    ctx.fillText("HYDRO",midx,midy-8);
    ctx.font=Math.round(0.0375*size)+"px Arial";
    ctx.fillText(50+" kWh",midx,midy+8);
    //-------------------------------------------------------
    
    midx = options.width * 0.4;
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 2;
    for (z in data) {
      x += data[z].value;
      
      var lastl = l
      l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
    
      alpha -= alphainc;
      ctx.fillStyle = data[z].color;
      ctx.beginPath();
      ctx.arc(midx,midy,size*0.2875,lastl,l,false);
      ctx.arc(midx,midy,size*0.0,l,lastl,true);
      ctx.fill();
      ctx.stroke();
    }

    ctx.fillStyle = "#fff";
    var x = 0;
    var l = -Math.PI *0.5;
    for (z in data) {
      x += data[z].value;
      
      var lastl = l
      l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
    
      var tl = (l + lastl)*-0.5 + (Math.PI *0.5);
      var labelx = midx+Math.sin(tl)*size*0.15;
      var labely = midy+Math.cos(tl)*size*0.15;
      
      var prc = 100*(data[z].value/total);
      ctx.fillText(Math.round(prc)+"%",labelx,labely+5);
    }
    
    for (z in data) {
      x += data[z].value;
      
      var lastl = l
      l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
    
      var tl = (l + lastl)*-0.5 + (Math.PI *0.5);
      var labelx = midx+Math.sin(tl)*size*0.4;
      var labely = midy+Math.cos(tl)*size*0.4;
      
      var textlength = data[z].name.length*textsize*0.7;
      
      if (labelx<textlength*0.5) labelx = textlength*0.5;
      if (labelx>((options.width*0.75) - textlength*0.5)) labelx = (options.width*0.75)-textlength*0.5;
      
       if (labely>(window.height)) labely = (window.height);
      
      ctx.fillStyle = "rgba(255,255,255,0.3)";
      ctx.fillRect(labelx-(textlength/2),labely-20,textlength,40);
      ctx.fillStyle = options.color;
      ctx.fillText(data[z].name,labelx,labely-3);
      ctx.fillText(data[z].value.toFixed(1)+" kWh",labelx,labely+15);
    }

    
    
}

