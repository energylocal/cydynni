/*

Status page

*/

function clubstatus_update() {

  var live = {};

  $.ajax({                                      
      url: path+"club/live/"+club,
      dataType: 'json',
      async: false,                      
      success: function(result) {
          live = result;
  }});

  var time = new Date();

  var hour = time.getHours();
  var minutes = time.getMinutes();

  $("#status-next").html("");
  
  if (live.tariff=="morning") $("#status-img").attr("src",app_path+"images/waiting-icon.png");
  if (live.tariff=="midday") $("#status-img").attr("src",app_path+"images/new-tick.png");
  if (live.tariff=="daytime") $("#status-img").attr("src",app_path+"images/new-tick.png");
  if (live.tariff=="evening") $("#status-img").attr("src",app_path+"images/waiting-icon.png");
  if (live.tariff=="overnight") $("#status-img").attr("src",app_path+"images/new-tick.png");
  if (live.tariff=="generation") $("#status-img").attr("src",app_path+"images/new-tick.png");

  var current_tariff = false;
  for (var z in tariffs) {
     if (live.tariff==tariffs[z].name) current_tariff = tariffs[z];
  }
  
  // Todo: review suggestion
  // Todo: add in half hourly tariff boundaries

  if (live.tariff=="daytime") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));

      var time_to_wait = (current_tariff.end.split(":")[0] - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? day time price currently available"));
  }

  else if (live.tariff=="evening") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));

      var time_to_wait = (current_tariff.end.split(":")[0] - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>8<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? overnight price coming up"));
  }

  else if (live.tariff=="overnight") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));

      if (hour>7) {
          var time_to_wait = (24-(hour+1)+tarcurrent_tariffiff.end.split(":")[0])+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      } else {
          var time_to_wait = (current_tariff.end.split(":")[0]-(hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      }
      $("#status-until").html(t("until")+" <b>6<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? overnight price currently available"));
  }
  
  else if (live.tariff=="generation") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#status-until").html(t("Why? Plenty of "+club_settings.generator+" currently available")); // +"<br>"+t("Estimated unit cost: ")+live.unit_price+" p/kWh"
  }

  var levels = {
      bethesda: {high:50,medium:30,low:10},
      towerpower: {high:3,medium:1,low:0.5},
      corwen: {high:50,medium:30,low:10},
      crickhowell: {high:50,medium:30,low:10},
      repower: {high:50,medium:30,low:10},
      redress: {high:50,medium:30,low:10}
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
  
  console.log(generation+" "+consumption);
  
  if (generation > consumption ) {
    $('#status-summary').text(t(ucfirst(club_settings.generator)+' output is currently exceeding club consumption'));
  } else if (generation == consumption) {
    $('#status-summary').text(t(ucfirst(club_settings.generator)+' output currently matches club consumption'));
  } else {
    $('#status-summary').text(t(ucfirst(club_settings.generator)+' output is currently lower than club consumption'));
  }
  
}

