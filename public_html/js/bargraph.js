
function bargraph_loading(element,color) 
{
    if (width==undefined) return false;
    if (height==undefined) return false;
    
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    
    ctx.strokeStyle = "#ccc";
    ctx.clearRect(0,0,width,height);
    
    ctx.strokeStyle = color;
    ctx.strokeRect(1,1,width-2,height-2);
}

function bargraph(element,series,units,color) 
{
    if (series[0]==undefined) return false;
    if (width==undefined) return false;
    if (height==undefined) return false;
    
    var padding = 5;
    
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    
    ctx.strokeStyle = "#ccc";
    ctx.clearRect(0,0,width,height);
    ctx.fillStyle = "#fff";
    ctx.fillRect(0,0,width,height);
    
    ctx.strokeStyle = color;
    ctx.strokeRect(1,1,width-2,height-2);
    // -------------------------------------------------------------------------
    // Find min and max from dataset
    // -------------------------------------------------------------------------
    var xmin = undefined;
    var xmax = undefined;
    var ymin = undefined;
    var ymax = undefined;
        
    for (var s in series) 
    {
        var data = series[s].data;
        for (var z in data)
        {
            if (xmin==undefined) xmin = data[z][0];
            if (xmax==undefined) xmax = data[z][0];
            if (ymin==undefined) ymin = data[z][1];
            if (ymax==undefined) ymax = data[z][1];
                        
            if (data[z][1]>ymax) ymax = data[z][1];
            if (data[z][1]<ymin) ymin = data[z][1];
            if (data[z][0]>xmax) xmax = data[z][0];
            if (data[z][0]<xmin) xmin = data[z][0];               
        }
    }
    
    ymax *= 1.1;
    if (ymax==0) ymax = 1;
    
    // -------------------------------------------------------------------------
    for (var s in series) 
    {
        ctx.fillStyle = series[s].color;
        var data = series[s].data;
        
        ymin = 0;
        //ymax = 50;
        
        var interval = 1;
        if (data.length>1) interval = data[1][0] - data[0][0];
        var barwidth = ((0.75*interval) / (xmax - xmin)) * width;

        var plot_width = width - padding*2 - barwidth;
        var plot_height = height - padding*2;
        
        for (var z in data) {
            var x = ((data[z][0] - xmin) / (xmax - xmin)) * plot_width;
            
            var y = 0;
            if ((ymax-ymin)>0) {
                y = plot_height - ((((data[z][1] - ymin) / (ymax - ymin)) * plot_height)+1);
            }
            if (data[z][2]!=undefined) ctx.fillStyle = data[z][2];
            ctx.fillRect(padding+x,padding+y,barwidth,plot_height-y);
        }
    }
    
    // -------------------------------------------------------------------------
    // Hourly ticks
    // -------------------------------------------------------------------------
    var ticksize = 3600*1000*4;
    var tstart = Math.floor(xmin / ticksize) * ticksize;
    var tend = Math.ceil(xmax / ticksize) * ticksize;
    
    ctx.fillStyle = color;
    ctx.beginPath();
    var xspacing = ((ticksize) / (xmax - xmin)) * plot_width;
    
    ctx.textAlign="center"; 
    var daymonth = "";
    for (var t=tstart; t<=tend; t+=ticksize) {
        var x = ((t - xmin) / (xmax - xmin)) * plot_width;
        if (x>0) {
            x = padding+x+(barwidth/2);
            ctx.moveTo(x,2);
            ctx.lineTo(x,7);
            // Time label
            var d = new Date(t);
            var hour = d.getHours();
            var month = d.getMonth();
            var day = d.getDate();
            if (hour>=12) hour=(hour-12)+"pm"; else hour=hour+"am";
            if (hour=="0pm") hour = "noon";
            if (hour=="0am") hour = "midnight";
            
            var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
            
            if (xspacing>70) {
                var lastdaymonth = daymonth;
                daymonth = day+" "+bargraph_t(months[month])+", ";
                
                if (lastdaymonth!=daymonth) {
                    ctx.fillText(daymonth+hour,x+5,18);
                } else {
                    ctx.fillText(hour,x+5,18);
                }
            } else {
                ctx.fillText(hour,x+5,18);
            }
        }
    }
    ctx.stroke();
    
    // -------------------------------------------------------------------------
    // Y-axis ticks
    // -------------------------------------------------------------------------
    ctx.textAlign="left"; 
    var ticksize = 1;
    var dp = 0;
    var ydiff = ymax-ymin;
    if (ydiff>10) { ticksize = Math.round((ymax-ymin)/10); dp = 0;} 
    if (ydiff<=10.0) { ticksize = 1.0; dp = 0; }
    if (ydiff<=5.0) { ticksize = 0.5; dp = 1; }
    if (ydiff<=2.0) { ticksize = 0.2; dp = 1; }
    if (ydiff<=1.0) { ticksize = 0.1; dp = 1; }
    
    var start = Math.floor(ymin / ticksize) * ticksize;
    var end = Math.ceil(ymax / ticksize) * ticksize;
    start += ticksize;
    end -= ticksize;
    
    ctx.fillStyle = "rgba(255,255,255,0.5)";
    ctx.fillRect(2,2,40,height-4);
    ctx.fillStyle = color;
    // ctx.font = "bold 10pt Arial";
    
    ctx.beginPath();
    for (var v=start; v<=end; v+=ticksize) {
        var y = plot_height - ((((v - ymin) / (ymax - ymin)) * plot_height)+1);
        ctx.moveTo(2,padding+y);
        ctx.lineTo(7,padding+y);
        
        ctx.fillText((v).toFixed(dp)+units,5,padding+y-5);
    }
    ctx.stroke();
    // -------------------------------------------------------------------------
}

// Javascript text translation function
function bargraph_t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}
