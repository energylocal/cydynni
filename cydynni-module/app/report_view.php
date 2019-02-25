<?php global $path, $translation, $session; 
$v=2;
$app_path = $path."Modules/cydynni/app/";
?>
<link rel="stylesheet" type="text/css" href="<?php echo $app_path; ?>css/style.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $app_path; ?>css/report.css" />
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/pie.js?v=<?php echo $v; ?>"></script>
<link rel="stylesheet" href="<?php echo $path; ?>Lib/misc/sidebar.css?v=<?php echo $v; ?>">

<div id="wrapper">
  <div class="sidenav">
    <div class="sidenav-inner">
      <ul class="sidenav-menu">
      </ul>
    </div>
  </div>

  <div style="height:10px"></div>
  
  <div class="page">
    <div style="background-color:#d2279c; height:15px"></div>
    <div class="inner">
      <div class="title"><b><span class="m1-name"></span>:</b> <?php echo t("Where your electricity came from this month"); ?></div>
      <div id="estimated_days" style="color:#666"></div>
      <br><br>
      
      <div style="text-align:center">
        <div class="box3">
          <h2><?php echo t("ELECTRICITY"); ?></h2>
          <div id="household_piegraph1_bound" style="width:100%; height:300px; margin: 0 auto">
              <canvas id="household_piegraph1_placeholder"></canvas>
          </div>
          <div id="household_hrbar1_bound" style="width:100%; height:50px; margin: 0 auto">
              <canvas id="household_hrbar1_placeholder"></canvas>
          </div>
          <br>
        </div>
    
        <div class="box3">
          <h2><?php echo t("COST"); ?></h2>
          <div id="household_piegraph2_bound" style="width:100%; height:300px; margin: 0 auto">
              <canvas id="household_piegraph2_placeholder"></canvas>
          </div>
          <div id="household_hrbar2_bound" style="width:100%; height:50px; margin: 0 auto">
              <canvas id="household_hrbar2_placeholder"></canvas>
          </div>
          <br>
        </div>
        
        <div class="box3">
          <div style="padding:15px; text-align:left; margin: 0 auto; max-width:270px">
            <table class="keytable">
              <tr>
                <td><div class="key" style="background-color:#29abe2"></div></td>
                <td><b><?php echo t("Hydro Power");?></b><br><span id="household_generation_kwh"></span> kWh @ 7.0 p/kWh<br><?php echo t("Costing");?> £<span id="household_generation_cost"></span></td>
              </tr>
              <tr>
                <td><div class="key" style="background-color:#ffdc00"></div></td>
                <td><b><?php echo t("Morning Price");?></b> 6am - 11am<br><span id="household_morning_kwh"></span> kWh @ 12p/kWh<br><?php echo t("Costing");?> £<span id="household_morning_cost"></span></td>
              </tr>
              <tr>
                <td><div class="key" style="background-color:#4abd3e"></div></td>
                <td><b><?php echo t("Midday Price");?></b> 11am - 4pm<br><span id="household_midday_kwh"></span> kWh @ 10p/kWh<br><?php echo t("Costing");?> £<span id="household_midday_cost"></span></td>
              </tr>
              <tr>
                <td><div class="key" style="background-color:#c92760"></div></td>
                <td><b><?php echo t("Evening Price");?></b> 4pm - 8pm<br><span id="household_evening_kwh"></span> kWh @ 14p/kWh<br><?php echo t("Costing");?> £<span id="household_evening_cost"></span></td>
              </tr>
              <tr>
                <td><div class="key" style="background-color:#274e3f"></div></td>
                <td><b><?php echo t("Overnight Price");?></b> 8pm - 6am<br><span id="household_overnight_kwh"></span> kWh @ 7.25p/kWh<br><?php echo t("Costing");?> £<span id="household_overnight_cost"></span></td>
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
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><?php echo t("Electricity charge");?> (<span id="total_kwh"></span> kWh)<br><?php echo t("Standing charge");?> (<span id="days"></span> <?php echo t("days at");?> 17.8p/<?php echo t("day");?>)<br><?php echo t("VAT");?> @ 5%<td>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px">£<span id="elec_cost"></span><br>£<span id="standing_charge"></span><br>£<span id="vat"></span></td>
          </tr>
          <tr>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><b><?php echo t("Total cost of electricity supply");?></b><td>
            <td style="background-color:#eee; border:2px #fff solid; padding:10px"><b>£<span id="total_cost"></span></b></td>
          </tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="page">
    <div style="background-color:#d2279c; height:15px"></div>
    <div class="inner">
    
      <div class="column box2" style="text-align:center;">
      <h2><?php echo t("Your energy use");?></h2>
      <p><b><?php echo t("Over the month you scored");?> <span class="score"></span>/100</b><br><span class="message"></span></p>

      <img src="<?php echo $app_path; ?>images/bluebadge.png" style="width:45px">
      <img id="star1" src="<?php echo $app_path; ?>images/star20blue.png" style="width:45px">
      <img id="star2" src="<?php echo $app_path; ?>images/star20blue.png" style="width:45px">
      <img id="star3" src="<?php echo $app_path; ?>images/star20blue.png" style="width:45px">
      <img id="star4" src="<?php echo $app_path; ?>images/star20blue.png" style="width:45px">
      <img id="star5" src="<?php echo $app_path; ?>images/star20blue.png" style="width:45px">
      <br><br>
      
      </div>
      <div class="column box2" style="text-align:center;">
      <h2><?php echo t("Our club power"); ?></h2>
      <p><b><?php echo t("Over the month we scored"); ?> <span class="club_score"></span>/100</b><br><span class="club_message"></span></p>

      <img src="<?php echo $app_path; ?>images/yellowbadge.png" style="width:45px;">
      <img id="club_star1" src="<?php echo $app_path; ?>images/star20yellow.png" style="width:45px">
      <img id="club_star2" src="<?php echo $app_path; ?>images/star20yellow.png" style="width:45px">
      <img id="club_star3" src="<?php echo $app_path; ?>images/star20yellow.png" style="width:45px">
      <img id="club_star4" src="<?php echo $app_path; ?>images/star20yellow.png" style="width:45px">
      <img id="club_star5" src="<?php echo $app_path; ?>images/star20yellow.png" style="width:45px">
      
      </div>
      
      <div style="clear:both"></div>
      
    </div>
  </div>
</div>
<script type="text/javascript" src="<?php echo $path; ?>Lib/misc/sidebar.js?v=<?php echo $v; ?>"></script>

<script>
if (window.init_sidebar!=undefined) init_sidebar({menu_element:"#cydynni_report_menu"});

var path = "<?php echo $path; ?>";
var app_path = "<?php echo $app_path; ?>";
var club = "<?php echo $club; ?>";
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var session = <?php echo json_encode($session); ?>;
var lang = session.lang;

var max_wrapper_width = 960;

var household = {};
var clubdata = {};

var data = {};
var generation = 0;

var selected_month = 0;
var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];

// Language selection top-right
if (lang=="cy_GB") {
    $("#togglelang").html("English");
} else {
    $("#togglelang").html("Cymraeg");
}

if (!session.write) {
  $("#logout").hide();
  $("#account").hide();
} else {
  $("#logout").show();
  $("#account").show();
}

$.ajax({                                      
    url: path+"cydynni/household-summary-monthly?apikey="+session.apikey_read,
    dataType: 'json',      
    success: function(result) {
        if (result=="Invalid data") alert("There was an error reading the monthly data for your report, please contact cydynni@energylocal.co.uk or try again later.");
        else {
            household = result;
            
            var out = "";
            for (var i=0; i<result.length; i++) {
                out += "<li><a href='#"+i+"'>"+t(months[result[i].month-1])+" "+result[i].year+"</a></li>";
            }
            $(".sidenav-menu").html(out);
        
            $.ajax({                                      
                url: path+"cydynni/club-summary-monthly?apikey="+session.apikey_read,
                dataType: 'json',      
                success: function(result) {  
                    clubdata = result;
                    load();
                }
            });
        }
    }
});

function household_pie_draw() {

    width = 300;
    height = 300;

    $("#household_piegraph1_placeholder").attr('width',width);
    $("#household_piegraph2_placeholder").attr('width',width);
    $('#household_piegraph1_placeholder').attr("height",height);
    $('#household_piegraph2_placeholder').attr("height",height);
    
    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: height
    };
    
    piegraph3("household_piegraph1_placeholder",household_pie3_data_energy,options);

   
    // Pie chart
    // piegraph2("household_piegraph2_placeholder",household_pie2_data,household_generation_use,options);

    piegraph3("household_piegraph2_placeholder",household_pie3_data_cost,options);


    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: 50
    };
    
    hrbar("household_hrbar1_placeholder",household_pie3_data_energy,options); 
    hrbar("household_hrbar2_placeholder",household_pie3_data_cost,options); 
    // generation droplet
    // generationdroplet("generation_droplet_placeholder",(community_generation_use*1).toFixed(1),{width: width,height: height});
}

function load()
{
    // ---------------------------------------------
    
    var month = household[selected_month];
    var eid = 1;

    $(".m"+eid+"-name").html(t(months[month.month-1]));
    
    if (month.estimate>0) {
        $("#estimated_days").html(t("Your report for this month includes")+" "+month.estimate+" "+t("estimated days."));
    } else {
        $("#estimated_days").html("");
    }
    
    // household pie chart
    household_pie3_data_cost = [
      {name:t("MORNING"), generation: month.generation.morning*0.07, import: month.import.morning*0.12, color:"#ffdc00"},
      {name:t("MIDDAY"), generation: month.generation.midday*0.07, import: month.import.midday*0.10, color:"#4abd3e"},
      {name:t("EVENING"), generation: month.generation.evening*0.07, import: month.import.evening*0.14, color:"#c92760"},
      {name:t("OVERNIGHT"), generation: month.generation.overnight*0.07, import: month.import.overnight*0.0725, color:"#274e3f"} 
    ];
    
    // household pie chart
    household_pie3_data_energy = [
      {name:t("MORNING"), generation: month.generation.morning, import: month.import.morning, color:"#ffdc00"},
      {name:t("MIDDAY"), generation: month.generation.midday, import: month.import.midday, color:"#4abd3e"},
      {name:t("EVENING"), generation: month.generation.evening, import: month.import.evening, color:"#c92760"},
      {name:t("OVERNIGHT"), generation: month.generation.overnight, import: month.import.overnight, color:"#274e3f"} 
    ];
    
    $("#household_generation_kwh").html(month.generation.total.toFixed(1));
    $("#household_morning_kwh").html(month.import.morning.toFixed(1));
    $("#household_midday_kwh").html(month.import.midday.toFixed(1));
    $("#household_evening_kwh").html(month.import.evening.toFixed(1));
    $("#household_overnight_kwh").html(month.import.overnight.toFixed(1));

    $("#household_generation_cost").html((month.generation.total*0.07).toFixed(2));
    $("#household_morning_cost").html((month.import.morning*0.12).toFixed(2));
    $("#household_midday_cost").html((month.import.midday*0.10).toFixed(2));
    $("#household_evening_cost").html((month.import.evening*0.14).toFixed(2));
    $("#household_overnight_cost").html((month.import.overnight*0.0725).toFixed(2));

    //                   1  2  3  4  5  6  7  8  9  10 11 12
    var days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
    var days = days_in_month[month.month-1];
    var elec_cost = (month.generation.total*0.07)+(month.import.morning*0.12)+(month.import.midday*0.10)+(month.import.evening*0.14)+(month.import.overnight*0.0725);
    var standing_charge = 0.178*days;
    var vat = (elec_cost+standing_charge)*0.05;
    var total_cost = elec_cost + standing_charge + vat;

    $("#days").html(days);
    $("#elec_cost").html((elec_cost).toFixed(2));
    $("#standing_charge").html((standing_charge).toFixed(2));
    $("#vat").html((vat).toFixed(2));
    $("#total_cost").html((total_cost).toFixed(2));

    var score = Math.round(100*((month.import.overnight + month.import.midday + month.generation.total) / month.demand.total));
    $(".score").html(score);

    for (var i=1; i<6; i++) $("#star"+i).attr("src",app_path+"images/star20blue.png"); // reset stars
    if (score>=20) $("#star1").attr("src",app_path+"images/starblue.png");
    if (score>=40) setTimeout(function() { $("#star2").attr("src",app_path+"images/starblue.png"); }, 100);
    if (score>=60) setTimeout(function() { $("#star3").attr("src",app_path+"images/starblue.png"); }, 200);
    if (score>=80) setTimeout(function() { $("#star4").attr("src",app_path+"images/starblue.png"); }, 300);
    if (score>=90) setTimeout(function() { $("#star5").attr("src",app_path+"images/starblue.png"); }, 400);

    if (score<30) {
        $(".message").html(t("You are using power in a very expensive way"));
    } else if (score>=30 && score<70) {
        $(".message").html(t("You’re doing ok at using hydro & cheaper power.<br>Can you move more of your use away from peak times?"));
    } else if (score>=70) {
        $(".message").html(t("You’re doing really well at using hydro & cheaper power"));
    }

    // ---------------------------------------------
    
    var month = clubdata[selected_month];   
    var eid = 1;   
    
    var score_club = Math.round(100*((month.import.overnight + month.import.midday + month.generation.total) / month.demand.total));
    $(".club_score").html(score_club);
    var prc_club = score_club;
    
    for (var i=1; i<6; i++) $("#club_star"+i).attr("src",app_path+"images/star20yellow.png"); // reset stars
    if (prc_club>=20) $("#club_star1").attr("src",app_path+"images/staryellow.png");
    if (prc_club>=40) setTimeout(function() { $("#club_star2").attr("src",app_path+"images/staryellow.png"); }, 100);
    if (prc_club>=60) setTimeout(function() { $("#club_star3").attr("src",app_path+"images/staryellow.png"); }, 200);
    if (prc_club>=80) setTimeout(function() { $("#club_star4").attr("src",app_path+"images/staryellow.png"); }, 300);
    if (prc_club>=90) setTimeout(function() { $("#club_star5").attr("src",app_path+"images/staryellow.png"); }, 400);
    
    if (score_club<30) {
        $(".club_message").html(t("We are using power in a very expensive way"));
    }
    if (score_club>=30 && score_club<70) {
        $(".club_message").html(t("We could do more to make the most of the hydro power and power at cheaper times of day. Can we move more electricity use away from peak times?"));
    }
    if (score_club>=70) {
        $(".club_message").html(t("We’re doing really well using the hydro and cheaper power"));
    }

    household_pie_draw();
}

$(window).resize(function(){
    // draw();
});

$(window).on('hashchange',function(){
   selected_month = parseInt(location.hash.slice(1));
   load();
});

// Language selection
$("#togglelang").click(function(){
    var ilang = $(this).html();
    if (ilang=="Cymraeg") {
        $(this).html("English");
        window.location = "?lang=cy";
    } else {
        $(this).html("Cymraeg");
        lang="cy_GB";
        window.location = "?lang=en";
    }
});

function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}
</script>

