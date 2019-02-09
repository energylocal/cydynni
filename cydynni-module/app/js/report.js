var droplet = new Image();
droplet.src = "Modules/cydynni/app/images/droplet.png";
droplet.onload = function () { }
    
function generationdroplet(element,value,options)
{
    // Droplet size based on width
    var size = options.width;
    var midx = options.width * 0.5;
    var midy = options.height * 0.5;

    // Context and clear
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    ctx.clearRect(0,0,options.width,options.height);

    
    // Background grey
    ctx.fillStyle = "#eee";
    ctx.beginPath();
    ctx.arc(midx,midy,size*0.40,0,2*Math.PI,false);
    ctx.fill();
    
    var w = options.width*0.7;
    ctx.drawImage(droplet,midx-(w/2),midy-(w*0.6),w,w);
    
    ctx.textAlign = "center";
    
    ctx.fillStyle = "#fff";
    ctx.font=Math.round(0.05*size)+"px Arial";
    ctx.fillText("HYDRO",midx,midy-4);
    ctx.font=Math.round(0.05*size)+"px Arial";
    ctx.fillText(value+" kWh",midx,midy+12);
}

function piegraph(element,data,options) {
    // Pie chart size based on width
    var size = options.width;
    var midx = options.width * 0.5;
    var midy = options.height * 0.5;
    
    // Calculate total of pie chart segments 
    var total = 0; for (z in data) total += data[z].value;
    
    // Context and clear
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    ctx.clearRect(0,0,options.width,options.height);
    
    var alphainc = 0.6/data.length;
    var alpha = 1.0;
    var textsize = Math.round(0.04*size);
    var textsize_prc = Math.round(0.05*size);

    ctx.textAlign = "center";
    ctx.font=textsize+"px Arial";
    
    // Background grey
    ctx.fillStyle = "#eee";
    ctx.beginPath();
    ctx.arc(midx,midy,size*0.40,0,2*Math.PI,false);
    ctx.fill();
    
    // -----------------------------------------------------------------
    // Pie chart segments
    // -----------------------------------------------------------------
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 2;

    var x = 0;
    var l = -Math.PI *0.5;
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
        ctx.closePath();
        ctx.stroke();
    }

    // -----------------------------------------------------------------
    // Labels
    // -----------------------------------------------------------------
    ctx.fillStyle = "#fff";

    var x = 0;
    var l = -Math.PI *0.5;
    for (z in data) {
        x += data[z].value;
        
        var lastl = l
        l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
        var tl = (l + lastl)*-0.5 + (Math.PI *0.5);
        
        var prclabelx = midx+Math.sin(tl)*size*0.15;
        var prclabely = midy+Math.cos(tl)*size*0.15;
        var valuelabelx = midx+Math.sin(tl)*size*0.4;
        var valuelabely = midy+Math.cos(tl)*size*0.4;
        
        if (data[z].value>0) {
            // Percentage label
            var prc = 100*(data[z].value/total);
            ctx.fillStyle = "#fff";
            ctx.font=textsize_prc+"px Arial";
            ctx.fillText(Math.round(prc)+"%",prclabelx,prclabely+5);
          
              /*
            // Value label
            var textlength = data[z].name.length*textsize*0.7;
            
            // Shift labels away from edges if near
            if (valuelabelx<textlength*0.5) 
                valuelabelx = textlength*0.5;
            if (valuelabelx>((options.width*0.75) - textlength*0.5)) 
                valuelabelx = (options.width*0.75)-textlength*0.5;
            if (valuelabely>(window.height)) 
                valuelabely = (window.height);
            
            ctx.font=textsize+"px Arial";
            ctx.fillStyle = "rgba(255,255,255,0.3)";
            ctx.fillRect(valuelabelx-(textlength/2),valuelabely-20,textlength,40);
            ctx.fillStyle = "#333";
            ctx.fillText(data[z].name,valuelabelx,valuelabely-3);
            ctx.fillText(data[z].value.toFixed(1)+" kWh",valuelabelx,valuelabely+15);
            */
        }
    }
}

