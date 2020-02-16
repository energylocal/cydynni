/*

Status page

*/

function cydynnistatus_update() {

  var tariff = {}, live = {};

  $.ajax({                                      
      url: path+"cydynni/live/"+club,
      dataType: 'json',
      async: false,                      
      success: function(result) {
          live = result;
  }});
  
  tariff = live.tariff || '';

  var time = new Date();

  var hour = time.getHours();
  var minutes = time.getMinutes();

  $("#status-next").html("");
  
  if (tariff=="morning") $("#status-img").attr("src",app_path+"images/waiting-icon.png");
  if (tariff=="midday") $("#status-img").attr("src",app_path+"images/new-tick.png");
  if (tariff=="daytime") $("#status-img").attr("src",app_path+"images/new-tick.png");
  if (tariff=="evening") $("#status-img").attr("src",app_path+"images/waiting-icon.png");
  if (tariff=="overnight") $("#status-img").attr("src",app_path+"images/new-tick.png");
  if (tariff=="generation") $("#status-img").attr("src",app_path+"images/new-tick.png");
  
  
  // If morning peak then wait until midday tariff
  /*
  if (tariff=="morning") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Morning Price"));

      var time_to_wait = (11 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      
      $("#status-until").html(t("until")+" <b>11<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? cheaper around midday"));

      $("#tariff-now-title").html(t("MORNING<br>PRICE")).css("color",tariffs.morning.color);
      $("#tariff-now-circle").css("background-color",tariffs.morning.color);
      $("#tariff-now-price").html((tariffs.morning.cost*100)+"p");
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="midday") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#tariff_summary").html(t("Now")+": "+t("Midday Price"));

      var time_to_wait = (16 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? midday price currently available"));
      
      $("#tariff-now-title").html(t("MIDDAY<br>PRICE")).css("color",tariffs.midday.color);
      $("#tariff-now-circle").css("background-color",tariffs.midday.color);
      $("#tariff-now-price").html((tariffs.midday.cost*100)+"p");
  }*/

  // If evening peak then wait until overnight tariff
  if (tariff=="daytime") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#tariff_summary").html(t("Now")+": "+t("Day Time Price"));

      var time_to_wait = (16 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? day time price currently available"));
      
      $("#tariff-now-title").html(t("DAY <br>PRICE")).css("color",tariffs.midday.color);
      $("#tariff-now-circle").css("background-color",tariffs.midday.color);
      $("#tariff-now-price").html((tariffs.midday.cost*100)+"p");
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="evening") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Evening Price"));

      var time_to_wait = (20 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>8<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? overnight price coming up"));

      $("#tariff-now-title").html(t("EVENING<br>PRICE")).css("color",tariffs.evening.color);
      $("#tariff-now-circle").css("background-color",tariffs.evening.color);
      $("#tariff-now-price").html((tariffs.evening.cost*100)+"p");
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="overnight") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));

      $("#tariff_summary").html(t("Now")+": "+t("Overnight Price"));

      if (hour>7) {
          var time_to_wait = (24-(hour+1)+7)+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      } else {
          var time_to_wait = (7-(hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      }
      $("#status-until").html(t("until")+" <b>6<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? overnight price currently available"));

      $("#tariff-now-title").html(t("OVERNIGHT<br>PRICE")).css("color",tariffs.overnight.color);      
      $("#tariff-now-circle").css("background-color",tariffs.overnight.color);
      $("#tariff-now-price").html((tariffs.overnight.cost*100)+"p");
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="generation") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#tariff_summary").html(t("Now")+": "+t(ucfirst(club_settings.generator)+" Price"));
      $("#status-until").html(t("Why? Plenty of "+club_settings.generator+" currently available")); // +"<br>"+t("Estimated unit cost: ")+live.unit_price+" p/kWh"
      
      $("#tariff-now-title").html(t(club_settings.generator.toUpperCase()+"<br>PRICE")).css("color",tariffs.generation.color);
      $("#tariff-now-circle").css("background-color",tariffs.generation.color);
      $("#tariff-now-price").html((tariffs.generation.cost*100)+"p");
  }
  
  //$("#"+tariff+"-tariff-box").hide();

  //$(".tariff-img").hide();
 // $(".tariff-img[tariff="+tariff+"]").show();

  var levels = {
      bethesda: {high:50,medium:30,low:10},
      towerpower: {high:3,medium:1,low:0.5}
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
    $('#status-summary').text(t('Hydro output is currently exceeding club consumption'));
  } else if (generation == consumption) {
    $('#status-summary').text(t('Hydro output currently matches club consumption'));
  } else {
    $('#status-summary').text(t('Hydro output is currently lower than club consumption'));
  }
  
}

