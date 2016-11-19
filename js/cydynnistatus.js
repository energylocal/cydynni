function cydynnistatus_update() {
  var time = new Date();

  var hour = time.getHours();
  var minutes = time.getMinutes();

  $("#status-next").html("");

  var tariff = 0;
  if ((hour>=6) && (hour<11)) tariff = "morning";
  if ((hour>=11) && (hour<16)) tariff = "midday";
  if ((hour>=16) && (hour<20)) tariff = "evening";
  if ((hour>=20) || (hour<6)) tariff = "overnight";

  if (tariff=="morning") $("#status-img").attr("src","images/waiting-icon-small.jpg");
  if (tariff=="midday") $("#status-img").attr("src","images/new-tick-small.jpg");
  if (tariff=="evening") $("#status-img").attr("src","images/waiting-icon-small.jpg");
  if (tariff=="overnight") $("#status-img").attr("src","images/new-tick-small.jpg");

  // If morning peak then wait until midday tariff
  if (tariff=="morning") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Morning Price"));

      var time_to_wait = (11 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      
      $("#status-until").html(t("until")+" <b>11<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? cheaper around midday"));

      $("#status-next").html(t("After that the next best time to use power<br>is <b>8pm - 6am.</b>"));
      $("#cydynni_summary").html(t("Wait until 11am"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="midday") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#tariff_summary").html(t("Now")+": "+t("Midday Price"));

      var time_to_wait = (16 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? midday price currently available"));
      $("#cydynni_summary").html(t("Ok until 4pm"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="evening") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Evening Price"));

      var time_to_wait = (20 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>8<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? overnight price coming up"));
      $("#cydynni_summary").html(t("Wait until 8pm"));
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
      $("#cydynni_summary").html(t("Ok until 6am"));
  }

  $(".tariff-img").hide();
  $(".tariff-img[tariff="+tariff+"]").show();
}

