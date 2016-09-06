var apikey = "892268eb10dd998c50f7cfbfc6f75f24";

width = $("#placeholder_bound").width();
$("#placeholder").attr('width',width);

var hydroseries = [];
var power = 0;
var kwh = 0;

setInterval(update,10000);
setInterval(slowupdate,60000);

function update()
{
    var feedid = 67087;
    $.ajax({                                      
        url: path+"value?apikey=8f5c2d146c0c338845d2201b8fe1b0e1",       
        data: "id="+feedid,
        dataType: 'json',
        async: true,                      
        success: function(data_in) { 
            power = 1*data_in / 75.0;
            $("#power").html(power.toFixed(1));
            if (power>=50) {
                $("#hydrostatus").html(t("HIGH"));
                $("#hydro_summary").html(t("For next 12 hours: HIGH POWER"));
            }
            else if (power>=30) {
                $("#hydrostatus").html(t("MEDIUM"));
                $("#hydro_summary").html(t("For next 12 hours: MEDIUM"));
            }
            else if (power>=10) {
                $("#hydrostatus").html(t("LOW"));
                $("#hydro_summary").html(t("For next 12 hours: LOW"));
            }
            else {
                $("#hydrostatus").html(t("VERY LOW"));
                $("#hydro_summary").html(t("For next 12 hours: VERY LOW"));
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
    var interval = 1800;
    var intervalms = interval * 1000;
    end = Math.floor(end / intervalms) * intervalms;
    start = Math.floor(start / intervalms) * intervalms;
    
    var feedid = 67087;    

    var data = [];
    $.ajax({                                      
        url: path+"average?apikey=8f5c2d146c0c338845d2201b8fe1b0e1",                         
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
                    hydro_data[z][1] = hydro_data[z][1] / 75.0;
                    if (hydro_data[z][1]<0) hydro_data[z][1]=0;

                hydroseries = [];
                hydroseries.push({data:hydro_data, color:"rgba(39,78,63,0.7)"});
                
                draw();
            }
        }
    });
}

function draw() {
    bargraph("placeholder",hydroseries," kW");
}

function graph_resize(h) {
    width = $("#placeholder_bound").width();
    $("#placeholder").attr('width',width);
    $('#placeholder_bound').attr("height",h);
    $('#placeholder').attr("height",h);
    height = h; 
    draw(); 
}
