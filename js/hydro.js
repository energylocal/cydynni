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
    /*
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
    */
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
    
    var feedid = 114934;    

    var data = [];
    $.ajax({                                      
        url: path+"hydro",
        dataType: 'json',
        async: true,                      
        success: function(result) {
            if (!result || result===null || result==="" || result.constructor!=Array) {
                console.log("ERROR","feed.getdata invalid response: "+result);
            } else {

                var hydro_data = result;
                
                // Solar values less than zero are invalid
                for (var z in hydro_data)
                    hydro_data[z][1] = ((hydro_data[z][1] * 3600000) / 1800) * 0.001;
                    if (hydro_data[z][1]<0) hydro_data[z][1]=0;
                
                var last_power = hydro_data[hydro_data.length-2][1]*1;   
                var power = hydro_data[hydro_data.length-1][1]*1;
                var time = hydro_data[hydro_data.length-1][0]*1;
                
                console.log("hydro power: "+power);
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
                
                var forecast = forecaster(time,power,last_power);

                hydroseries = [];
                hydroseries.push({data:hydro_data, color:"rgba(39,78,63,0.7)"});
                hydroseries.push({data:forecast, color:"rgba(39,78,63,0.2)"});
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

function forecaster(time,power,lastpower) {

    var ThRising = 2.0; // kWh/HH
    var Gfloor = 1.0; // kWh/HH
    var Drate = -0.08; // kWh/HH/HH
    var Delta = 22; 
    var DeltaSquared = Delta * Delta;
    var Dratelin = -0.02;
    var forecastlength = 24;
    var timeinc = 1800000;

    time = time*1;
    power = power*1;
    lastpower = lastpower*1;
    
    DeltaSquared = Delta * Delta;
    var forecast = [];

    var forecast_case = 0;

    // shutdown
    if (power==0) {
        forecast_case = 5;
        // Forecast: zero output
        for (var z=0; z<forecastlength; z++) {
            var ft = time+z*timeinc;
            forecast.push([ft,0]);
        }        
    }
    // Hydro output increasing: assume flat
    else if ((power-lastpower)>ThRising) {
        forecast_case = 4;
        // Forecast: increasing, assume flat
        for (var z=0; z<forecastlength; z++) {
            var ft = time+z*timeinc;
            forecast.push([ft,power]);
        }    
    }
    // Medium power
    else if (power>5) {
        forecast_case = 3;
        // Forecast: (Geni-Gfloor)/Delta^2*Drate+Geni
        forecast.push([time,power]);
        for (var z=1; z<forecastlength; z++) {
            var fv = ((forecast[z-1][1] - Gfloor) / (DeltaSquared * Drate)) + forecast[z-1][1];
            var ft = time+z*timeinc;
            forecast.push([ft,fv]);
        }    
    }
    // Low power
    else if (power>2) {
        forecast_case = 1;
        // Forecast: (Geni-Gfloor)/Delta^2*Drate+Geni
        forecast.push([time,power]);
        for (var z=1; z<forecastlength; z++) {
            var fv = ((forecast[z-1][1] - Gfloor) / (DeltaSquared * Drate)) + forecast[z-1][1];
            var ft = time+z*timeinc;
            forecast.push([ft,fv]);
        }
    }
    // Lowest power
    else if (power<=2) {
        forecast_case = 2;
        // Forecast: Geni + Dratelin
        for (var z=0; z<forecastlength; z++) {
            var ft = time+z*timeinc;
            forecast.push([ft,power + Dratelin]);
        }
    }
    
    return forecast;
}
