/*

Hydro CydYnni Status page

*/

function cydynnistatus_update() {

  var tariff = {};

  $.ajax({                                      
      url: path+"live",
      dataType: 'json',
      async: false,                      
      success: function(result) {
          live = result;
  }});
  
  tariff = live.tariff;
  tariff = "evening";

  var time = new Date();

  var hour = time.getHours();
  var minutes = time.getMinutes();

  $("#status-next").html("");
  
  if (tariff=="morning") $("#status-img").attr("src","images/waiting-icon.png");
  if (tariff=="midday") $("#status-img").attr("src","images/new-tick.png");
  if (tariff=="evening") $("#status-img").attr("src","images/waiting-icon.png");
  if (tariff=="overnight") $("#status-img").attr("src","images/new-tick.png");
  if (tariff=="hydro") $("#status-img").attr("src","images/new-tick.png");
  
  
  // If morning peak then wait until midday tariff
  if (tariff=="morning") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Morning Price"));

      var time_to_wait = (11 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      
      $("#status-until").html(t("until")+" <b>11<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? cheaper around midday"));

      // $("#status-next").html(t("After that the next best time to use power<br>is <b>8pm - 6am.</b>"));
      // $("#cydynni_summary").html(t("Wait until 11am"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="midday") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#tariff_summary").html(t("Now")+": "+t("Midday Price"));

      var time_to_wait = (16 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? midday price currently available"));
      // $("#cydynni_summary").html(t("Ok until 4pm"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="evening") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Evening Price"));

      var time_to_wait = (20 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>8<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? overnight price coming up"));
      // $("#cydynni_summary").html(t("Wait until 8pm"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="overnight") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));

      $("#tariff_summary").html(t("Now")+": "+t("Overnight Price"));

      if (hour>6) {
          var time_to_wait = (24-(hour+1)+6)+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      } else {
          var time_to_wait = (6-(hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      }
      $("#status-until").html(t("until")+" <b>6<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? overnight price currently available"));
      // $("#cydynni_summary").html(t("Ok until 6am"));
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="hydro") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#tariff_summary").html(t("Now")+": "+t("Hydro Price"));
      $("#status-until").html(t("Why? Plenty of hydro currently available"));
      // $("#cydynni_summary").html(t("Hydro available"));
  }

  //$(".tariff-img").hide();
 // $(".tariff-img[tariff="+tariff+"]").show();
 
  if (live.hydro>=50) {
      $("#hydro-status").html(t("HIGH"));
  } else if (live.hydro>=30) {
      $("#hydro-status").html(t("MEDIUM"));
  } else if (live.hydro>=10) {
      $("#hydro-status").html(t("LOW"));
  } else {
      $("#hydro-status").html(t("VERY LOW"));
  }

  $("#hydro-power").html(Math.round(live.hydro));
}

