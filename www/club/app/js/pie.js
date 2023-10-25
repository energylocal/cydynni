var droplet = new Image();
droplet.src = typeof path == 'undefined' ? "Modules/club/app/images/droplet.png" : path+"Modules/club/app/images/droplet.png";
droplet.onload = function () { }

var pie_generator_color = "#29aae3";
    
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

function piegraph1(element,data,options) {
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
        
        var prclabelx = midx+Math.sin(tl)*size*0.34;
        var prclabely = midy+Math.cos(tl)*size*0.34;
        var valuelabelx = midx+Math.sin(tl)*size*0.4;
        var valuelabely = midy+Math.cos(tl)*size*0.4;
        
        if (data[z].value>0) {
            // Percentage label
            var prc = 100*(data[z].value/total);
            ctx.fillStyle = "#333";
            ctx.font=textsize_prc+"px Arial";
            if (prc>=1.0) ctx.fillText(Math.round(prc)+"%",prclabelx,prclabely+5);
        }
    }
}

function piegraph2(element,data,generation,options) {
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

    var prc = (generation/(total+generation));
    //var prc = 0.5;
    
    // Math.PI * 
    
    var r = size*0.2875;
    var fullarea = Math.PI * r*r;
    var prcarea = prc * fullarea;
    var cr = Math.sqrt(prcarea / Math.PI);

    ctx.fillStyle = pie_generator_color;
    ctx.beginPath();
    ctx.arc(midx,midy,cr,0,2*Math.PI,true);
    ctx.fill();
    ctx.closePath();
    ctx.stroke();

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
        
        var prclabelx = midx+Math.sin(tl)*size*0.34;
        var prclabely = midy+Math.cos(tl)*size*0.34;
        var valuelabelx = midx+Math.sin(tl)*size*0.4;
        var valuelabely = midy+Math.cos(tl)*size*0.4;
        
        if (data[z].value>0) {
            // Percentage label
            var prc = 100*(data[z].value/(total+generation));
            ctx.fillStyle = "#333";
            ctx.font=textsize_prc+"px Arial";
            if (prc>=1.0) ctx.fillText(Math.round(prc)+"%",prclabelx,prclabely+5);
        }
    }
    
    var prc = 100*(generation/(total+generation));
    ctx.fillStyle = "#fff";
    ctx.font=textsize_prc+"px Arial";
    ctx.fillText(Math.round(prc)+"%",midx,midy+5);
}

function piegraph3(element,data,options) {
    // Pie chart size based on width
    var size = options.width;
    var midx = options.width * 0.5;
    var midy = options.height * 0.5;
    
    var generation = 1;
    
    // Calculate total of pie chart segments 
    var total = 0; for (z in data) total += data[z].generation + data[z].import;
    
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
        x += data[z].generation + data[z].import;
        
        var lastl = l
        l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
        
        alpha -= alphainc;
        ctx.fillStyle = data[z].color;
        
        ctx.beginPath();
        ctx.arc(midx,midy,size*0.2875,lastl,l,false);
        ctx.arc(midx,midy,size*0.0001,l,lastl,true);
        ctx.fill();
        ctx.closePath();
        ctx.stroke();
        
        // generation part
        var prc = data[z].generation / (data[z].generation+data[z].import);

        var r = size*0.2875;
        var fullarea = Math.PI * r*r;
        var prcarea = prc * fullarea;
        var cr = Math.sqrt(prcarea / Math.PI);
        
        ctx.fillStyle = pie_generator_color;
        ctx.beginPath();
        ctx.arc(midx,midy,cr,lastl,l,false);
        ctx.arc(midx,midy,size*0.0001,l,lastl,true);
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
        var val = data[z].generation + data[z].import;
        x += val;
        
        var lastl = l
        l = (2 * Math.PI * (x / total)) - (Math.PI *0.5);
        var tl = (l + lastl)*-0.5 + (Math.PI *0.5);
        
        var prclabelx = midx+Math.sin(tl)*size*0.34;
        var prclabely = midy+Math.cos(tl)*size*0.34;
        var valuelabelx = midx+Math.sin(tl)*size*0.4;
        var valuelabely = midy+Math.cos(tl)*size*0.4;
        
        if (val>0) {
            // Percentage label
            var prc = 100*(val/total);
            if (data[z].color=="#ffdc00") data[z].color = "#ddba00";
            ctx.fillStyle = data[z].color; //"#333";
            
            ctx.font="bold "+textsize_prc+"px Arial";
            if (prc>=1.0) ctx.fillText(Math.round(prc)+"%",prclabelx,prclabely+5);
        }
    }
}

function hrbar(element,data,options) {

    var width = options.width;
    var height = options.height;
    var padding = 10;
    
    var segments = {};
    
    // Calculate total of segments 
    var total = 0; for (var z in data) total += data[z].generation + data[z].import;
    
    segments.generation = {val:0, color:pie_generator_color};
    
    for (var z in data) {
        segments[z] = {val:data[z].import, color:data[z].color};
        segments.generation.val += data[z].generation;
    }
    
    // Context and clear
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    ctx.clearRect(0,0,options.width,options.height);
    
    // Background grey
    ctx.fillStyle = "#eee";
    ctx.fillRect(0,0,width,height);
    
    ctx.strokeStyle = "#fff";
    ctx.lineWidth = 2;

    var x = 0;
    var l = 0;
    for (z in segments) {
        var segw = segments[z].val;
        x += segw;
        
        var lastl = l
        l = (width-(padding*2)) * (x / total);
        
        if (segw>0.0) {
            ctx.fillStyle = segments[z].color;
            ctx.fillRect(padding+lastl,padding,l-lastl,height-(padding*2));
            ctx.strokeRect(padding+lastl,padding,l-lastl,height-(padding*2));
        }
    }
}



