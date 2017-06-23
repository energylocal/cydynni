<?php global $path, $translation, $lang; ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>CydYnni Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="pages/report.css" />
  </head>

  <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
  <script language="javascript" type="text/javascript" src="js/report.js"></script>

  <body>
    <br>
    <div class="page">
      <div style="background-color:#d2279c; height:15px"></div>
      <div class="inner">
        <div class="title"><b><span class="m1-name"></span>:</b> <?php echo t("Where your electricity came from this month"); ?></div>
        <br><br>
        
        <div style="text-align:center">
        <!-- BOX3:HYDRO ============================ -->
        <div class="column box3">
            <h2><?php echo t("Hydro Power"); ?></h2>
            <p><?php echo t("Any time your electricity use is<br>matched to the hydro"); ?></p>
            <div id="hydro_droplet_bound_m1">
              <canvas id="hydro_droplet_placeholder_m1"></canvas>
            </div>
        </div>
        <!-- BOX3:IMPORTED ============================ -->
        <div class="column box3">
            <h2><?php echo t("Extra Electricity"); ?></h2>
            <p><?php echo t("Use not matched to the hydro"); ?></p>
            <div id="piegraph_bound_m1">
              <canvas id="piegraph_placeholder_m1"></canvas>
            </div>
        </div>
        <!-- BOX3:DETAIL ============================ -->
        <div class="column box3">
            <div style="background-color:#eee; padding:15px; text-align:left">
              <table class="keytable">
                <tr>
                  <td><div class="key" style="background-color:#29abe2"></div></td>
                  <td><b><?php echo t("Hydro Power");?></b><br>@ 7.0 p/kWh<br><?php echo t("You used");?> <span id="m1_hydro_kwh"></span> kWh <?php echo t("costing");?> £<span id="m1_hydro_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#ffdc00"></div></td>
                  <td><b><?php echo t("Morning Price");?></b><br>6am <?php echo t("till"); ?> 11am<br>@ 12.0 p/kWh<br><?php echo t("You used");?> <span id="m1_morning_kwh"></span> kWh <?php echo t("costing");?> £<span id="m1_morning_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#4abd3e"></div></td>
                  <td><b><?php echo t("Midday Price");?></b><br>11am <?php echo t("till"); ?> 4pm<br>@ 10.0 p/kWh<br><?php echo t("You used");?> <span id="m1_midday_kwh"></span> kWh <?php echo t("costing");?> £<span id="m1_midday_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#c92760"></div></td>
                  <td><b><?php echo t("Evening Price");?></b><br>4pm <?php echo t("till"); ?> 8pm<br>@ 14.0 p/kWh<br><?php echo t("You used");?> <span id="m1_evening_kwh"></span> kWh <?php echo t("costing");?> £<span id="m1_evening_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#274e3f"></div></td>
                  <td><b><?php echo t("Overnight Price");?></b><br>8pm <?php echo t("till"); ?> 6am<br>@ 7.25 p/kWh<br><?php echo t("You used");?> <span id="m1_overnight_kwh"></span> kWh <?php echo t("costing");?> £<span id="m1_overnight_cost"></span></td>
                </tr>
              </table>
            </div>
        </div>
        <div style="clear:both"></div>
        <!-- =================================== -->
        <br><br>

        <div style="text-align:left; padding-left:20px">
          <h3><?php echo t("Cost breakdown: ");?><span class="m1-name"></span></h3>
          <table style="width:100%">
          <tr>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><?php echo t("Electricity charge");?> (<span id="m1_total_kwh"></span> kWh)<br><?php echo t("Standing charge");?> (<span id="m1_days"></span> <?php echo t("days at");?> 17.8p/<?php echo t("day");?>)<br><?php echo t("VAT");?> @ 5%<td>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px">£<span id="m1_elec_cost"></span><br>£<span id="m1_standing_charge"></span><br>£<span id="m1_vat"></span></td>
          </tr>
          <tr>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><b><?php echo t("Total cost of electricity supply");?></b><td>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><b>£<span id="m1_total_cost"></span></b></td>
          </tr>
          </table>
        </div>

        </div>

      </div>
    </div>
    <!------------------------------------------------>
    <br>
    <div class="page">
      <div style="background-color:#d2279c; height:15px"></div>
      <div class="inner">
        <div class="title"><b><span class="m2-name"></span>:</b> <?php echo t("Where your electricity came from last month"); ?></div>
        <br><br>
        
        <div style="text-align:center">
        <!-- BOX3:HYDRO ============================ -->
        <div class="column box3">
            <h2><?php echo t("Hydro Power"); ?></h2>
            <p><?php echo t("Any time your electricity use is<br>matched to the hydro"); ?></p>
            <div id="hydro_droplet_bound_m2">
              <canvas id="hydro_droplet_placeholder_m2"></canvas>
            </div>
        </div>
        <!-- BOX3:IMPORTED ============================ -->
        <div class="column box3">
            <h2><?php echo t("Extra Electricity"); ?></h2>
            <p><?php echo t("Use not matched to the hydro"); ?></p>
            <div id="piegraph_bound_m2">
              <canvas id="piegraph_placeholder_m2"></canvas>
            </div>
        </div>
        <!-- BOX3:DETAIL ============================ -->
        <div class="column box3">
            <div style="background-color:#eee; padding:15px; text-align:left">
              <table class="keytable">
                <tr>
                  <td><div class="key" style="background-color:#29abe2"></div></td>
                  <td><b><?php echo t("Hydro Power");?></b><br>@ 7.0 p/kWh<br><?php echo t("You used");?> <span id="m2_hydro_kwh"></span> kWh <?php echo t("costing");?> £<span id="m2_hydro_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#ffdc00"></div></td>
                  <td><b><?php echo t("Morning Price");?></b><br>6am <?php echo t("till"); ?> 11am<br>@ 12.0 p/kWh<br><?php echo t("You used");?> <span id="m2_morning_kwh"></span> kWh <?php echo t("costing");?> £<span id="m2_morning_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#4abd3e"></div></td>
                  <td><b><?php echo t("Midday Price");?></b><br>11am <?php echo t("till"); ?> 4pm<br>@ 10.0 p/kWh<br><?php echo t("You used");?> <span id="m2_midday_kwh"></span> kWh <?php echo t("costing");?> £<span id="m2_midday_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#c92760"></div></td>
                  <td><b><?php echo t("Evening Price");?></b><br>4pm <?php echo t("till"); ?> 8pm<br>@ 14.0 p/kWh<br><?php echo t("You used");?> <span id="m2_evening_kwh"></span> kWh <?php echo t("costing");?> £<span id="m2_evening_cost"></span></td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#274e3f"></div></td>
                  <td><b><?php echo t("Overnight Price");?></b><br>8pm <?php echo t("till"); ?> 6am<br>@ 7.25 p/kWh<br><?php echo t("You used");?> <span id="m2_overnight_kwh"></span> kWh <?php echo t("costing");?> £<span id="m2_overnight_cost"></span></td>
                </tr>
              </table>
            </div>
        </div>
        <div style="clear:both"></div>
        <!-- =================================== -->
        <br><br>
        
        <div style="text-align:left; padding-left:20px;">
          <h3><?php echo t("Cost breakdown: ");?><span class="m2-name"></span></h3>
          <table style="width:100%">
          <tr>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><?php echo t("Electricity charge");?> (<span id="m2_total_kwh"></span> kWh)<br><?php echo t("Standing charge");?> (<span id="m2_days"></span> <?php echo t("days at");?> 17.8p/<?php echo t("day");?>)<br><?php echo t("VAT");?> @ 5%<td>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px">£<span id="m2_elec_cost"></span><br>£<span id="m2_standing_charge"></span><br>£<span id="m2_vat"></span></td>
          </tr>
          <tr>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><b><?php echo t("Total cost of electricity supply");?></b><td>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><b>£<span id="m2_total_cost"></span></b></td>
          </tr>
          </table>
        </div>
        
        </div>

      </div>
    </div>
    <br>
    
    <div class="page">
      <div style="background-color:#d2279c; height:15px"></div>
      <div class="inner">
      
        <div class="column box2" style="text-align:center;">
        <h2><?php echo t("Your energy use");?></h2>
        <p><b><?php echo t("Over the month you scored");?> <span class="m1_score"></span>/100</b><br><span class="m1_message"></span></p>

        <img src="images/bluebadge.png" style="width:45px">
        <img id="m1_star1" src="images/star20blue.png" style="width:45px">
        <img id="m1_star2" src="images/star20blue.png" style="width:45px">
        <img id="m1_star3" src="images/star20blue.png" style="width:45px">
        <img id="m1_star4" src="images/star20blue.png" style="width:45px">
        <img id="m1_star5" src="images/star20blue.png" style="width:45px">
        <br><br>
        
        <p><b><?php echo t("Last month you scored");?> <span class="m2_score"></span>/100</b></p>
        <img id="m2_star1" src="images/star20blue.png" style="width:35px">
        <img id="m2_star2" src="images/star20blue.png" style="width:35px">
        <img id="m2_star3" src="images/star20blue.png" style="width:35px">
        <img id="m2_star4" src="images/star20blue.png" style="width:35px">
        <img id="m2_star5" src="images/star20blue.png" style="width:35px">
        
        <p><b><?php echo t("Your overall score is");?> <span class="t_score"></span>/100</b></p>
        <img id="t_star1" src="images/star20blue.png" style="width:35px">
        <img id="t_star2" src="images/star20blue.png" style="width:35px">
        <img id="t_star3" src="images/star20blue.png" style="width:35px">
        <img id="t_star4" src="images/star20blue.png" style="width:35px">
        <img id="t_star5" src="images/star20blue.png" style="width:35px">
        <br><br>
        
        </div>
        <div class="column box2" style="text-align:center;">
        <h2><?php echo t("Our community power"); ?></h2>
        <p><b><?php echo t("Over the month we scored"); ?> <span class="m1_community_score"></span>/100</b><br><span class="m1_community_message"></span></p>

        <img src="images/yellowbadge.png" style="width:45px;">
        <img id="m1_community_star1" src="images/star20yellow.png" style="width:45px">
        <img id="m1_community_star2" src="images/star20yellow.png" style="width:45px">
        <img id="m1_community_star3" src="images/star20yellow.png" style="width:45px">
        <img id="m1_community_star4" src="images/star20yellow.png" style="width:45px">
        <img id="m1_community_star5" src="images/star20yellow.png" style="width:45px">
        <br><br>
        
        <p><b><?php echo t("Last month we scored");?> <span class="m2_community_score"></span>/100</b></p>
        <img id="m2_community_star1" src="images/star20yellow.png" style="width:35px">
        <img id="m2_community_star2" src="images/star20yellow.png" style="width:35px">
        <img id="m2_community_star3" src="images/star20yellow.png" style="width:35px">
        <img id="m2_community_star4" src="images/star20yellow.png" style="width:35px">
        <img id="m2_community_star5" src="images/star20yellow.png" style="width:35px">
        
        <p><b><?php echo t("Our overall score is");?> <span class="t_community_score"></span>/100</b></p>
        <img id="t_community_star1" src="images/star20yellow.png" style="width:35px">
        <img id="t_community_star2" src="images/star20yellow.png" style="width:35px">
        <img id="t_community_star3" src="images/star20yellow.png" style="width:35px">
        <img id="t_community_star4" src="images/star20yellow.png" style="width:35px">
        <img id="t_community_star5" src="images/star20yellow.png" style="width:35px">
        <br><br>
        
        </div>
        
        <div style="clear:both"></div>
        
      </div>
    </div>
    <br><br>
  </body>

</html>

<script>
var path = "<?php echo $path; ?>";
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";
var session = JSON.parse('<?php echo json_encode($session); ?>');

var data_m1 = {};
var hydro_m1 = 0;

var data_m2 = {};
var hydro_m2 = 0;

// ----------------------------------------------------------------------------
// Month 1
// ----------------------------------------------------------------------------
$(window).resize(function(){
    draw();
});

$.ajax({                                      
    url: path+"household/monthlydata",
    dataType: 'json',      
    success: function(result) {
    
        if (result=="Invalid data") alert("There was an error reading the monthly data for your report, please contact cydynni@energylocal.co.uk or try again later.");
    
        var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        $(".m1-name").html(t(months[result[0].month-1]));
        
        // 1. Determine score
        // Calculated as amount of power consumed at times off peak times and from hydro
        var score_m1 = Math.round(100*((result[0].kwh.overnight + result[0].kwh.midday + result[0].kwh.hydro) / result[0].kwh.total));

        data_m1 = [
          {name:t("MORNING"), value: result[0].kwh.morning, color:"#ffdc00"},
          {name:t("MIDDAY"), value: result[0].kwh.midday, color:"#4abd3e"},
          {name:t("EVENING"), value: result[0].kwh.evening, color:"#c92760"},
          {name:t("OVERNIGHT"), value: result[0].kwh.overnight, color:"#274e3f"}
          // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
        ];
        hydro_m1 = result[0].kwh.hydro;
        
        draw();
        
        // Month one kwh
        $("#m1_hydro_kwh").html(result[0].kwh.hydro.toFixed(1));
        $("#m1_morning_kwh").html(result[0].kwh.morning.toFixed(1));
        $("#m1_midday_kwh").html(result[0].kwh.midday.toFixed(1));
        $("#m1_evening_kwh").html(result[0].kwh.evening.toFixed(1));
        $("#m1_overnight_kwh").html(result[0].kwh.overnight.toFixed(1));
        $("#m1_total_kwh").html(result[0].kwh.total.toFixed(0));
        
        // Month one costs
        $("#m1_hydro_cost").html((result[0].kwh.hydro*0.07).toFixed(2));
        $("#m1_morning_cost").html((result[0].kwh.morning*0.12).toFixed(2));
        $("#m1_midday_cost").html((result[0].kwh.midday*0.10).toFixed(2));
        $("#m1_evening_cost").html((result[0].kwh.evening*0.14).toFixed(2));
        $("#m1_overnight_cost").html((result[0].kwh.overnight*0.0725).toFixed(2));

        //                   1  2  3  4  5  6  7  8  9  10 11 12
        var days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
        var m1_days = days_in_month[result[0].month-1];
        var m1_elec_cost = (result[0].kwh.hydro*0.07)+(result[0].kwh.morning*0.12)+(result[0].kwh.midday*0.10)+(result[0].kwh.evening*0.14)+(result[0].kwh.overnight*0.0725);
        var m1_standing_charge = 0.178*m1_days;
        var m1_vat = (m1_elec_cost+m1_standing_charge)*0.05;
        var m1_total_cost = m1_elec_cost + m1_standing_charge + m1_vat;
        
        $("#m1_days").html(m1_days);
        $("#m1_elec_cost").html((m1_elec_cost).toFixed(2));
        $("#m1_standing_charge").html((m1_standing_charge).toFixed(2));
        $("#m1_vat").html((m1_vat).toFixed(2));
        $("#m1_total_cost").html((m1_total_cost).toFixed(2));
        
        $(".m1_score").html(score_m1);
        var prc = score_m1;
        if (prc>20) $("#m1_star1").attr("src","images/starblue.png");
        if (prc>40) setTimeout(function() { $("#m1_star2").attr("src","images/starblue.png"); }, 100);
        if (prc>60) setTimeout(function() { $("#m1_star3").attr("src","images/starblue.png"); }, 200);
        if (prc>80) setTimeout(function() { $("#m1_star4").attr("src","images/starblue.png"); }, 300);
        if (prc>90) setTimeout(function() { $("#m1_star5").attr("src","images/starblue.png"); }, 400);
        
        if (score_m1<30) {
            $(".m1_message").html(t("You are using power in a very expensive way"));
        }
        if (score_m1>=30 && score_m1<70) {
            $(".m1_message").html(t("You’re doing ok at using hydro & cheaper power.<br>Can you move more of your use away from peak times?"));
        }
        if (score_m1>=70) {
            $(".m1_message").html(t("You’re doing really well at using hydro & cheaper power"));
        }
        // -------------------------------------------------------------------------------------------
        
        var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        $(".m2-name").html(t(months[result[1].month-1]));
        
        // 1. Determine score
        // Calculated as amount of power consumed at times off peak times and from hydro
        var score_m2 = Math.round(100*((result[1].kwh.overnight + result[1].kwh.midday + result[1].kwh.hydro) / result[1].kwh.total));

        data_m2 = [
          {name:t("MORNING"), value: result[1].kwh.morning, color:"#ffdc00"},
          {name:t("MIDDAY"), value: result[1].kwh.midday, color:"#4abd3e"},
          {name:t("EVENING"), value: result[1].kwh.evening, color:"#c92760"},
          {name:t("OVERNIGHT"), value: result[1].kwh.overnight, color:"#274e3f"}
          // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
        ];
        hydro_m2 = result[1].kwh.hydro;
        
        draw();
        
        // Month one kwh
        $("#m2_hydro_kwh").html(result[1].kwh.hydro.toFixed(1));
        $("#m2_morning_kwh").html(result[1].kwh.morning.toFixed(1));
        $("#m2_midday_kwh").html(result[1].kwh.midday.toFixed(1));
        $("#m2_evening_kwh").html(result[1].kwh.evening.toFixed(1));
        $("#m2_overnight_kwh").html(result[1].kwh.overnight.toFixed(1));
        $("#m2_total_kwh").html(result[1].kwh.total.toFixed(0));
        
        // Month one costs
        $("#m2_hydro_cost").html((result[1].kwh.hydro*0.07).toFixed(2));
        $("#m2_morning_cost").html((result[1].kwh.morning*0.12).toFixed(2));
        $("#m2_midday_cost").html((result[1].kwh.midday*0.10).toFixed(2));
        $("#m2_evening_cost").html((result[1].kwh.evening*0.14).toFixed(2));
        $("#m2_overnight_cost").html((result[1].kwh.overnight*0.0725).toFixed(2));
        
        //                   1  2  3  4  5  6  7  8  9  10 11 12
        var days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
        var m2_days = days_in_month[result[1].month-1];
        var m2_elec_cost = (result[1].kwh.hydro*0.07)+(result[1].kwh.morning*0.12)+(result[1].kwh.midday*0.10)+(result[1].kwh.evening*0.14)+(result[1].kwh.overnight*0.0725);
        var m2_standing_charge = 0.178*m2_days;
        var m2_vat = (m2_elec_cost+m2_standing_charge)*0.05;
        var m2_total_cost = m2_elec_cost + m2_standing_charge + m2_vat;
        
        $("#m2_days").html(m2_days);
        $("#m2_elec_cost").html((m2_elec_cost).toFixed(2));
        $("#m2_standing_charge").html((m2_standing_charge).toFixed(2));
        $("#m2_vat").html((m2_vat).toFixed(2));
        $("#m2_total_cost").html((m2_total_cost).toFixed(2));
        
        
        
        
        $(".m2_score").html(score_m2);
        var prc = score_m2;
        if (prc>20) $("#m2_star1").attr("src","images/starblue.png");
        if (prc>40) setTimeout(function() { $("#m2_star2").attr("src","images/starblue.png"); }, 100);
        if (prc>60) setTimeout(function() { $("#m2_star3").attr("src","images/starblue.png"); }, 200);
        if (prc>80) setTimeout(function() { $("#m2_star4").attr("src","images/starblue.png"); }, 300);
        if (prc>90) setTimeout(function() { $("#m2_star5").attr("src","images/starblue.png"); }, 400);
        
        var score = Math.round((score_m1 + score_m2) / 2.0);
        $(".t_score").html(score);
        var prc = score;
        if (prc>20) $("#t_star1").attr("src","images/starblue.png");
        if (prc>40) setTimeout(function() { $("#t_star2").attr("src","images/starblue.png"); }, 100);
        if (prc>60) setTimeout(function() { $("#t_star3").attr("src","images/starblue.png"); }, 200);
        if (prc>80) setTimeout(function() { $("#t_star4").attr("src","images/starblue.png"); }, 300);
        if (prc>90) setTimeout(function() { $("#t_star5").attr("src","images/starblue.png"); }, 400);
        
        
    }
});

load_community_segment();
// ----------------------------------------------------------------------------
// Community segment
// ----------------------------------------------------------------------------
function load_community_segment()
{
    $.ajax({                                      
        url: path+"community/monthlydata",
        dataType: 'json',      
        success: function(result) {

            console.log("Community hydro M0: "+result[0].kwh.hydro);
            console.log("Community total M0: "+result[0].kwh.total);
            console.log("Community hydro M1: "+result[1].kwh.hydro);
            console.log("Community total M1: "+result[1].kwh.total);            
            
            var score_community_m1 = Math.round(100*((result[0].kwh.overnight + result[0].kwh.midday + result[0].kwh.hydro) / result[0].kwh.total));
            $(".m1_community_score").html(score_community_m1);
            var prc_community = score_community_m1;
            if (prc_community>20) $("#m1_community_star1").attr("src","images/staryellow.png");
            if (prc_community>40) setTimeout(function() { $("#m1_community_star2").attr("src","images/staryellow.png"); }, 100);
            if (prc_community>60) setTimeout(function() { $("#m1_community_star3").attr("src","images/staryellow.png"); }, 200);
            if (prc_community>80) setTimeout(function() { $("#m1_community_star4").attr("src","images/staryellow.png"); }, 300);
            if (prc_community>90) setTimeout(function() { $("#m1_community_star5").attr("src","images/staryellow.png"); }, 400);
            
            if (score_community_m1<30) {
                $(".m1_community_message").html(t("We are using power in a very expensive way"));
            }
            if (score_community_m1>=30 && score_community_m1<70) {
                $(".m1_community_message").html(t("We could do more to make the most of the hydro power and power at cheaper times of day. Can we move more electricity use away from peak times?"));
            }
            if (score_community_m1>=70) {
                $(".m1_community_message").html(t("We’re doing really well using the hydro and cheaper power"));
            }
            
            var score_community_m2 = Math.round(100*((result[1].kwh.overnight + result[1].kwh.midday + result[1].kwh.hydro) / result[1].kwh.total));
            $(".m2_community_score").html(score_community_m2);
            var prc_community = score_community_m2;
            if (prc_community>20) $("#m2_community_star1").attr("src","images/staryellow.png");
            if (prc_community>40) setTimeout(function() { $("#m2_community_star2").attr("src","images/staryellow.png"); }, 100);
            if (prc_community>60) setTimeout(function() { $("#m2_community_star3").attr("src","images/staryellow.png"); }, 200);
            if (prc_community>80) setTimeout(function() { $("#m2_community_star4").attr("src","images/staryellow.png"); }, 300);
            if (prc_community>90) setTimeout(function() { $("#m2_community_star5").attr("src","images/staryellow.png"); }, 400);
            
            var score_community = Math.round((score_community_m1 + score_community_m2) / 2.0);
            $(".t_community_score").html(score_community);
            var prc_community = score_community;
            if (prc_community>20) $("#t_community_star1").attr("src","images/staryellow.png");
            if (prc_community>40) setTimeout(function() { $("#t_community_star2").attr("src","images/staryellow.png"); }, 100);
            if (prc_community>60) setTimeout(function() { $("#t_community_star3").attr("src","images/staryellow.png"); }, 200);
            if (prc_community>80) setTimeout(function() { $("#t_community_star4").attr("src","images/staryellow.png"); }, 300);
            if (prc_community>90) setTimeout(function() { $("#t_community_star5").attr("src","images/staryellow.png"); }, 400);
        }
    });
}

function draw() {
    var width = $("#piegraph_bound_m1").width();
    var height = $("#piegraph_bound_m1").height();
    if (width>400) width = 400;
    var height = width*0.9;
    
    $("#piegraph_placeholder_m1").attr('width',width);
    $('#piegraph_bound_m1').attr("height",height);
    $('#piegraph_placeholder_m1').attr("height",height);
    
    $("#hydro_droplet_placeholder_m1").attr('width',width);
    $('#hydro_droplet_bound_m1').attr("height",height);
    $('#hydro_droplet_placeholder_m1').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    };
    
    // Pie chart
    piegraph("piegraph_placeholder_m1",data_m1,options);

    // Hydro droplet
    hydrodroplet("hydro_droplet_placeholder_m1",(hydro_m1*1).toFixed(1),{width: width,height: height});
    
    // ------------------------------------------------------------------------------
    
    var width = $("#piegraph_bound_m2").width();
    var height = $("#piegraph_bound_m2").height();
    if (width>400) width = 400;
    var height = width*0.9;
    
    $("#piegraph_placeholder_m2").attr('width',width);
    $('#piegraph_bound_m2').attr("height",height);
    $('#piegraph_placeholder_m2').attr("height",height);
    
    $("#hydro_droplet_placeholder_m2").attr('width',width);
    $('#hydro_droplet_bound_m2').attr("height",height);
    $('#hydro_droplet_placeholder_m2').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    };
    
    // Pie chart
    piegraph("piegraph_placeholder_m2",data_m2,options);

    // Hydro droplet
    hydrodroplet("hydro_droplet_placeholder_m2",(hydro_m2*1).toFixed(1),{width: width,height: height});
}


// ----------------------------------------------------------------------------
// Stars
// ----------------------------------------------------------------------------
var prc = 60;
if (prc>20) $("#community_star1").attr("src","images/staryellow.png");
if (prc>40) setTimeout(function() { $("#community_star2").attr("src","images/staryellow.png"); }, 100);
if (prc>60) setTimeout(function() { $("#community_star3").attr("src","images/staryellow.png"); }, 200);
if (prc>80) setTimeout(function() { $("#community_star4").attr("src","images/staryellow.png"); }, 300);
if (prc>90) setTimeout(function() { $("#community_star5").attr("src","images/staryellow.png"); }, 400);

function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}
</script>
