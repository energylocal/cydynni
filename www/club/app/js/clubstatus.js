/*

Status page

*/

function clubstatus_update() {

  var live = {};

    $.ajax({                                      
        url: path+club+"/live",
        dataType: 'json',
        async: true,                      
        success: function(result) {
            live = result;
            demandshaper_data = live.demandshaper_data_raw['DATA'][0];
            // sometimes the demandshaper data can cover more than 48hr 
            // in this case, reduce it to 48hr, with the current time as the midpoint
            if (demandshaper_data.length > 96) {
                const elementsToRemove = demandshaper_data.length - 96;
                demandshaper_data.splice(0, elementsToRemove);
            }

            // demand shaper data includes a period before and after the current time. each array entry represents 30mins
            // the period before the current time can vary, the period after the current time is always fixed at 24hr
            // finding the current time, working backwards from from the final entry
            demandshaper_length = demandshaper_data.length;
            demandshaper_current_value = demandshaper_data[demandshaper_length-49];

            // we now sort the array in ascending order, while being careful to retain the original array
            demandshaper_data_asc = demandshaper_data.slice();
            demandshaper_data_asc = demandshaper_data_asc.sort((a,b) => a - b);

            // we now take the length of the array, and generate two integers to roughly represent 25% and 50% of this length
            // note that rounding is acceptable for these values, and quarter*2 + half does not have to equal the original length
            quarter_length = Math.ceil(demandshaper_length/4);
            half_length = Math.floor(demandshaper_length/2);

            // we now use these integers to calculate two values which represent the "boundaries" - one between green and amber, the other between amber and red
            lower_boundary = (demandshaper_data_asc[half_length-1]+demandshaper_data_asc[half_length])/2;
            upper_boundary = (demandshaper_data_asc[demandshaper_length-quarter_length-1]+demandshaper_data_asc[demandshaper_length-quarter_length])/2;
            // lastly, we connect these boundaries to the traffic lights
            // if the current value is below the lower boundary (among the best 50% datapoints in the period), the traffic lights turn green
            // if the current value is above the lower boundary but below the upper boundary, the traffic lights turn amber
            // if the current value is above the upper boundary (among the worst 25% datapoints in the period), they turn red
            if (demandshaper_current_value < lower_boundary) {
                trafficlight('green');
                $("#status-pre").html(t("Yes! Low cost electricity available"));
            } else if (demandshaper_current_value < upper_boundary) {
                trafficlight('amber');
                $("#status-pre").html(t("Medium cost electricity"));
            } else {
                trafficlight('red');
                $("#status-pre").html(t("High cost electricity"));
            }

            var time = new Date();

            var hour = time.getHours();
            var minutes = time.getMinutes();

            // $("#status-next").html("");

            var current_tariff = false;
            for (var z in tariffs) {
                if (live.tariff==tariffs[z].name) current_tariff = tariffs[z];
            }
            
            
            var prc_gen = (100*(live.generation / live.club)).toFixed(0);

            // var tariff_name = live.tariff.toUpperCase()
            // if (tariff_name=="DAYTIME") tariff_name = "DAY TIME";
            // $("#status-title").html(t(tariff_name));
            
            if (live.club>0) {
                if (prc_gen>=1.0) {
                    $("#gen-prc").html(ucfirst(club_settings.generator)+" "+t("currently providing")+" "+t("approx")+" <b>"+prc_gen+"%</b> "+t("of club consumption."));
                } else {
                    $("#gen-prc").html(t("No local "+club_settings.generator+" currently available."));
                }
            } else {
                $("#gen-prc").html("");
            }
            
            var tariff_end = 0; // 1*current_tariff.end.split(":")[0];
            var hours_to_wait = tariff_end - (hour+1);
            if (hours_to_wait<0) hours_to_wait += 24;

            var time_to_wait = hours_to_wait+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
            if (tariff_end<=12) {
                am_pm = "AM";
            } else {
                tariff_end -= 12;
                am_pm = "PM";
            }
            
            $("#status-until").html(t("until")+" <b>"+tariff_end+"<span style='font-size:12px'>"+am_pm+"</span></b> <span style='font-size:12px'>("+time_to_wait+")</span>");
            
        // TODO move to config	
            var levels = {
                bethesda: {high:50,medium:30,low:10},
                totnes: {high:10,medium:5,low:3},
                towerpower: {high:3,medium:1,low:0.5},
                corwen: {high:50,medium:30,low:10},
                crickhowell: {high:50,medium:30,low:10},
                machynlleth: {high:50,medium:30,low:10},
                repower: {high:50,medium:30,low:10},
                roupellpark: {high:50,medium:30,low:10},
                redress: {high:50,medium:30,low:10},
                bridport: {high:40,medium:20,low:10},
                llandysul: {high:40,medium:20,low:10},
                test: {high:40,medium:20,low:10},
                dyffrynbanw: {high:8,medium:6,low:4}
                //economy7: {high:8,medium:6,low:4}
            }

            if (live.generation>=levels[club].high) {
                $("#generation-status").html(t("HIGH"));
                trafficlight('green');
                $("#status-pre").html(t("Yes! Low cost electricity available"));
            } else if (live.generation>=levels[club].medium) {
                $("#generation-status").html(t("MEDIUM"));
                trafficlight('amber');
                $("#status-pre").html(t("Medium cost electricity"));
            } else if (live.generation>=levels[club].low) {
                $("#generation-status").html(t("LOW"));
                trafficlight('amber');
                $("#status-pre").html(t("Medium cost electricity"));
            } else {
                $("#generation-status").html(t("VERY LOW"));
                trafficlight('red');
                $("#status-pre").html(t("High cost electricity"));
            }
            
            var generation = Math.round(live.generation||0);
            $("#generation-power").html(generation);
            var consumption = Math.round(live.club||0);
            
            if (generation > consumption ) {
                $('#status-summary').text(t(ucfirst(club_settings.generator)+' output is currently exceeding club consumption'));
            } else if (generation == consumption) {
                $('#status-summary').text(t(ucfirst(club_settings.generator)+' output currently matches club consumption'));
            } else {
                $('#status-summary').text(t(ucfirst(club_settings.generator)+' output is currently lower than club consumption'));
            }
        }
    });
}

function trafficlight(state) {
    switch(state) {
      case 'green':
        $("#tl-red").removeClass('tl-red-on').addClass('tl-red-off');
        $("#tl-amber").removeClass('tl-amber-on').addClass('tl-amber-off');
        $("#tl-green").removeClass('tl-green-off').addClass('tl-green-on');
        break;
      case 'amber':
        $("#tl-red").removeClass('tl-red-on').addClass('tl-red-off');
        $("#tl-amber").removeClass('tl-amber-off').addClass('tl-amber-on');
        $("#tl-green").removeClass('tl-green-on').addClass('tl-green-off'); 
        break;
      case 'red':
        $("#tl-red").removeClass('tl-red-off').addClass('tl-red-on');
        $("#tl-amber").removeClass('tl-amber-on').addClass('tl-amber-off');
        $("#tl-green").removeClass('tl-green-on').addClass('tl-green-off'); 
        break;
    }
}
