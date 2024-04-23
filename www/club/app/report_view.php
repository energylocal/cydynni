<?php global $path, $translation, $session; 
$v=4;
$app_path = $path."Modules/club/app/";
?>
<link rel="stylesheet" type="text/css" href="<?php echo $app_path; ?>css/style.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $app_path; ?>css/report.css" />
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/pie.js?v=<?php echo $v; ?>"></script>

  
<div class="page">
  <div style="background-color:#d2279c; height:15px;margin-top:20px"></div>
  <div class="inner">
    <div class="title"><b><span class="m1-name"></span>:</b> <?php echo t("Where your electricity came from this month"); ?></div>
    <div id="estimated_days" style="color:#666;position:absolute"></div>
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
          <table class="keytable"></table>
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

<script>
var path = "<?php echo $path; ?>";
var app_path = "<?php echo $app_path; ?>";
var club = "<?php echo $club; ?>";
var club_settings = <?php echo json_encode($club_settings);?>;
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var session = <?php echo json_encode($session); ?>;
var lang = session.lang;

var max_wrapper_width = 960;

var household = {};
var clubdata = {};

var data = {};
var generation = 0;

var selected_month = false;
var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
if (location.hash) selected_month = location.hash.slice(1);

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
    url: path+club+"/household-summary-monthly?apikey="+session.apikey_read,
    dataType: 'json',      
    success: function(result) {
        if (result=="Invalid data") alert("There was an error reading the monthly data for your report, please contact contact@energylocal.co.uk or try again later.");
        else {
            household = result;
            $.ajax({
                url: path+club+"/club-summary-monthly?apikey="+session.apikey_read,
                dataType: 'json',      
                success: function(result) {  
                    clubdata = result;
                    load();
                }
            });
        }
    }
})

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
    
    pie_generator_color = club_settings.generator_color;
    piegraph3("household_piegraph1_placeholder",household_pie3_data_energy,options);
    piegraph3("household_piegraph2_placeholder",household_pie3_data_cost,options);

    var options = {
      color: "#3b6358",
      centertext: "THIS WEEK",
      width: width,
      height: 50
    };
    
    hrbar("household_hrbar1_placeholder",household_pie3_data_energy,options); 
    hrbar("household_hrbar2_placeholder",household_pie3_data_cost,options); 
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
    
    var tariff_colors = {morning:"#ffdc00",midday:"#ffb401",daytime:"#ffb401",evening:"#e6602b",overnight:"#014c2d",standard:"#c20000"};
    
    // household pie chart
    household_pie3_data_cost = []
    household_pie3_data_energy = []
    
    var keytable = "";

    if (month.generation.total>0) {
        keytable += '<tr>'
        keytable += '<td><div class="key" style="background-color:'+club_settings.generator_color+'"></div></td>'
        keytable += '<td><b>'+t(ucfirst(club_settings.generator)+" Price")+'</b><br>'
        keytable += month.generation.total.toFixed(1)+' kWh @ '+(100*month.generation_cost.total/month.generation.total).toFixed(2)+'p/kWh<br>'
        keytable += t("Costing")+' £'+(month.generation_cost.total).toFixed(2)+'</td>'
        keytable += '</tr>'
    }
   
    ['morning','midday','daytime','evening','overnight','standard'].forEach(function(name) {
        if (month.import[name]!=undefined) {
            household_pie3_data_cost.push({name:t(name.toUpperCase()), generation: month.generation_cost[name], import: month.import_cost[name], color:tariff_colors[name]});
            household_pie3_data_energy.push({name:t(name.toUpperCase()), generation: month.generation[name], import: month.import[name], color:tariff_colors[name]});
            
            if (month.import[name]>0) {
                keytable += '<tr>'
                keytable += '<td><div class="key" style="background-color:'+tariff_colors[name]+'"></div></td>'
                keytable += '<td><b>'+t(ucfirst(name)+" Price")+'</b><br>'
                keytable += month.import[name].toFixed(1)+' kWh @ '+(100*month.import_cost[name]/month.import[name]).toFixed(2)+'p/kWh<br>'
                keytable += t("Costing")+' £'+(month.import_cost[name]).toFixed(2)+'</td>'
                keytable += '</tr>'
            }
        }
    });
    
    $(".keytable").html(keytable);
    
    $("#household_generation_kwh").html(month.generation.total.toFixed(1));
    $("#household_generation_cost").html((month.generation_cost.total).toFixed(2));

    var days = month.days;
    var elec_cost = month.cost.total;
    var standing_charge = 0.178*days; // TODO - check if this is used / correct
    var vat = (elec_cost+standing_charge)*0.05;
    var total_cost = elec_cost + standing_charge + vat;

    $("#days").html(days);
    $("#elec_cost").html((elec_cost).toFixed(2));
    $("#standing_charge").html((standing_charge).toFixed(2));
    $("#vat").html((vat).toFixed(2));
    $("#total_cost").html((total_cost).toFixed(2));
    
    var low_cost_power = 0
    low_cost_power += month.generation_cost.total
    low_cost_power += month.import_cost.overnight
    if (month.import_cost.midday!=undefined) low_cost_power += month.import_cost.midday
    if (month.import_cost.daytime!=undefined) low_cost_power += month.import_cost.daytime
    
    var score = Math.round(100*(low_cost_power / (month.import_cost.total+month.generation_cost.total)));
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
        $(".message").html(t("You’re doing ok at using "+club_settings.generator+" & cheaper power.<br>Can you move more of your use away from peak times?"));
    } else if (score>=70) {
        $(".message").html(t("You’re doing really well at using "+club_settings.generator+" & cheaper power"));
    }

    // ---------------------------------------------
    
    var month = clubdata[selected_month];   
    var eid = 1;   

    var low_cost_power = 0
    low_cost_power += month.generation_cost.total
    low_cost_power += month.import_cost.overnight
    if (month.import_cost.midday!=undefined) low_cost_power += month.import_cost.midday
    if (month.import_cost.daytime!=undefined) low_cost_power += month.import_cost.daytime
    
    var score_club = Math.round(100*(low_cost_power / (month.import_cost.total+month.generation_cost.total)));
    
    $(".club_score").html(score_club);
    
    for (var i=1; i<6; i++) $("#club_star"+i).attr("src",app_path+"images/star20yellow.png"); // reset stars
    if (score_club>=20) $("#club_star1").attr("src",app_path+"images/staryellow.png");
    if (score_club>=40) setTimeout(function() { $("#club_star2").attr("src",app_path+"images/staryellow.png"); }, 100);
    if (score_club>=60) setTimeout(function() { $("#club_star3").attr("src",app_path+"images/staryellow.png"); }, 200);
    if (score_club>=80) setTimeout(function() { $("#club_star4").attr("src",app_path+"images/staryellow.png"); }, 300);
    if (score_club>=90) setTimeout(function() { $("#club_star5").attr("src",app_path+"images/staryellow.png"); }, 400);
    
    if (score_club<30) {
        $(".club_message").html(t("We are using power in a very expensive way"));
    }
    if (score_club>=30 && score_club<70) {
        $(".club_message").html(t("We could do more to make the most of the "+club_settings.generator+" power and power at cheaper times of day. Can we move more electricity use away from peak times?"));
    }
    if (score_club>=70) {
        $(".club_message").html(t("We’re doing really well using the "+club_settings.generator+" and cheaper power"));
    }

    household_pie_draw();
}

$(window).resize(function(){
    // draw();
});

$(window).on('hashchange',function(){
   selected_month = location.hash.slice(1);
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

function ucfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
</script>

