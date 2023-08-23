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
          
          if (live.status=="green") {
              trafficlight('green');
              $("#status-pre").html(t("Yes! Low cost electricity available"));
              // $("#status-pre").html(t("Now is a good time to use electricity"));
          } else if (live.status=="amber") {
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
          
          var tariff_end = 1*current_tariff.end.split(":")[0];
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
          } else if (live.generation>=levels[club].medium) {
              $("#generation-status").html(t("MEDIUM"));
          } else if (live.generation>=levels[club].low) {
              $("#generation-status").html(t("LOW"));
          } else {
              $("#generation-status").html(t("VERY LOW"));
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
