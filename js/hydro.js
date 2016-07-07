var apikey = "892268eb10dd998c50f7cfbfc6f75f24";

width = $("#placeholder_bound").width();
$("#placeholder").attr('width',width);

var series = [];
var power = 0;
var kwh = 0;

setInterval(update,10000);
setInterval(slowupdate,60000);

function update()
{
    var feedid = 119861;
    $.ajax({                                      
        url: path+"value",
        data: "id="+feedid+"&apikey="+apikey,
        dataType: 'json',
        async: true,                      
        success: function(data_in) { 
            power = 1*data_in;
            $("#power").html(power.toFixed(1));
            if (power>=50) {
                $("#hydrostatus").html("HIGH");
                $("#hydro_summary").html("HIGH POWER");
            }
            else if (power>=25) {
                $("#hydrostatus").html("MEDIUM");
                $("#hydro_summary").html("MEDIUM POWER");
            }
            else if (power<25) {
                $("#hydrostatus").html("LOW");
                $("#hydro_summary").html("LOW POWER");
            }
        }
    });
}

function slowupdate() {
    load();
}

function load() {

    var end = +new Date;
    var start = end - (3600000*24.0*1);
    var interval = 3600;
    var intervalms = interval * 1000;
    end = Math.floor(end / intervalms) * intervalms;
    start = Math.floor(start / intervalms) * intervalms;
    
    var feedid = 120883;    
    var apikeystr = "";
    if (apikey!="") apikeystr = "?apikey="+apikey;
    
    var data = [];
    $.ajax({                                      
        url: path+"average"+apikeystr,                         
        data: "id="+feedid+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing=1&limitinterval=1",
        dataType: 'json',
        async: true,                      
        success: function(result) {
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","feed.getdata invalid response: "+result);
            } else {

                var hydro_data = result;
                // Solar values less than zero are invalid
                for (var z in hydro_data)
                    if (hydro_data[z][1]<0) hydro_data[z][1]=0;

                series = [];
                series.push({data:hydro_data, color:"rgba(39,78,63,0.5)"});
                
                draw();
            }
        }
    });
}

function draw() {
    bargraph("placeholder",series);
}

function graph_resize(h) {
    width = $("#placeholder_bound").width();
    $("#placeholder").attr('width',width);
    $('#placeholder_bound').attr("height",h);
    $('#placeholder').attr("height",h);
    height = h; 
    draw(); 
}

function bargraph(element,series) 
{
    var padding = 0;
    
    var c = document.getElementById(element);  
    var ctx = c.getContext("2d");
    
    ctx.strokeStyle = "#ccc";
    ctx.clearRect(0,0,width,height);

    var xmin = undefined;
    var xmax = undefined;
    var ymin = undefined;
    var ymax = undefined;
        
    for (var s in series) 
    {
        var data = series[s].data;
        for (z in data)
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
    
    ymin = 0;
    ymax *=1.0;
    
    var interval = 1;
    if (data.length>1) interval = data[1][0] - data[0][0];
    var barwidth = ((0.75*interval) / (xmax - xmin)) * width;

    var plot_width = width - padding*2 - barwidth;
    var plot_height = height - padding*2;
        
    for (var s in series) 
    {
        ctx.fillStyle = series[s].color;
        var data = series[s].data;
        
        for (var z in data) {
            var x = ((data[z][0] - xmin) / (xmax - xmin)) * plot_width;
            var y = plot_height - (((data[z][1] - ymin) / (ymax - ymin)) * plot_height);
            ctx.fillRect(padding+x,padding+y,barwidth,plot_height-y); 
        }
    }
}
