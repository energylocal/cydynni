var path = "";
var apikey = "892268eb10dd998c50f7cfbfc6f75f24";

var timeWindow = (3600000*24.0*30);
var interval = 60;
var intervalms = interval * 1000;

var mode = "daily";

view.end = +new Date;
view.end = Math.floor(view.end / intervalms) * intervalms;
view.start = view.end - timeWindow;
view.start = Math.floor(view.start / intervalms) * intervalms;

var width = $("#placeholder_bound").width();
$("#placeholder").attr('width',width);
graph_bars.width = width;

var options = {
    series: { },
    xaxis: { min: view.start, max: view.end, mode: "time", timezone: "browser" },
    selection: { mode: "x" },
    legend: {position: "nw"},
    grid: {hoverable: true, clickable: true}
};
var series = [];

update();
load();

var power = 0;
var kwh = 0;

setInterval(update,10000);
setInterval(slowupdate,60000);

function update()
{
  power = get_value(120883,apikey);
  kwh = get_value(120884,apikey);
  $("#power").html(power.toFixed(0));
  if (power>=50) $("#hydrostatus").html("HIGH");
  else if (power>=25) $("#hydrostatus").html("MEDIUM");
  else if (power<25) $("#hydrostatus").html("LOW");
}

function slowupdate() {
  load();
}

function load() {

    interval = 86400;
    var intervalms = interval * 1000;
    //view.end = Math.floor(view.end / intervalms) * intervalms;
    //view.start = Math.ceil(view.start / intervalms) * intervalms;

    var result = get_data_mode(120884,view.start,view.end,mode,apikey);

    var d = new Date();
    d.setHours(0,0,0,0);
    var startofday = d.getTime();

    var data = [];
    // remove nan values from the end.
    for (z in result) {
      if (result[z][1]!=null && result[z][0]<=startofday) {
        data.push(result[z]);
      }
    }
    
    var lastday = data[data.length-1][0];
    if (lastday==startofday) {
        var interval = 86400;
        // last day in kwh data matches start of today from the browser's perspective
        // which means its safe to append today kwh value
        var next = lastday + (interval*1000);
        if (kwh!=undefined) {
            data.push([next,kwh*1.0]);
        }
    }
    

    var daily = [];

    for (var z=1; z<data.length; z++) {
        var day = data[z][1]-data[z-1][1];
        if (day>=0) daily.push([data[z-1][0],day]);
    }
    
    var kwh_today = daily[daily.length-1][1];
    $("#kwh_today").html(Math.round(kwh_today));
    $("#number_of_houses").html(Math.floor(kwh_today/9.0));
    
    series = [];
    series.push({data:daily, yaxis:1, color:"#76b77f",bars: { show: true, align: "center", barWidth: 0.75*86400*1000, fill: 1.0}, grid:{color:'#fff', tickColor:"#fff"}});

    draw();
}

function draw() {
    options.xaxis.min = view.start;
    options.xaxis.max = view.end;
    // $.plot("#placeholder",series, options);
    
    graph_bars.draw('placeholder',[series[0].data]);
}

function graph_resize(h) {
  var width = $("#placeholder_bound").width();
  $("#placeholder").attr('width',width);
  graph_bars.width = width;
  $('#placeholder_bound').attr("height",h);
  $('#placeholder').attr("height",h);
  graph_bars.height = h; 
  draw(); 
}

function get_data_interval(feedid,start,end,interval,skipmissing,limitinterval,apikey)
{
  var data = [];
  $.ajax({                                      
    url: path+"data",
    data: "id="+feedid+"&start="+start+"&end="+end+"&interval="+interval+"&skipmissing="+skipmissing+"&limitinterval="+limitinterval+"&apikey="+apikey,
    dataType: 'json',
    async: false,                      
    success: function(data_in) { data = data_in; } 
  });
  return data;
}

function get_data_mode(feedid,start,end,mode,apikey)
{
  var data = [];
  $.ajax({                                      
    url: path+"data",
    data: "id="+feedid+"&start="+start+"&end="+end+"&mode="+mode+"&apikey="+apikey,
    dataType: 'json',
    async: false,                      
    success: function(data_in) { data = data_in; } 
  });
  return data;
}

function get_value(feedid,apikey)
{
  var value = null;
  $.ajax({                                      
    url: path+"value",
    data: "id="+feedid+"&apikey="+apikey,
    dataType: 'json',
    async: false,                      
    success: function(data_in) { value = 1*data_in; }
  });
  return value;
}
