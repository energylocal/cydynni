<?php global $path, $translation, $lang; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Cyd Ynni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    
    <style>
    body {
      background-color:#eee;
      text-align:left;
      font-family: Montserrat, Veranda, sans-serif;
      padding:0;
      margin:0;
    }

    .page {
      margin: 0 auto;
      max-width:960px;
      background-color:#fff;
    }

    .inner {
      padding:20px;
    }

    h1, h2, h3 { 
      color:#666;
      margin:0px;
    }

    .title {
      font-size:24px;
      color:#333;
    }
    
    .summary {
      font-size:16px;
      font-weight:bold;
      color:#666;
    }

    hr {
      display: block;
      background: transparent;
      border:none;
      border-top: 2px solid #00b0f0; 
      margin-top:8px;
      margin-bottom:8px;
    }

    p {
      color:#666;
    }
    
    .key {width:25px; height:25px; border: 2px solid #fff}

    .keytable td {
        padding-bottom:10px;
        padding-right:10px;
        vertical-align:top;
        font-size:14px;
    }

    </style>
  </head>

  <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
  <script language="javascript" type="text/javascript" src="js/report.js"></script>

  <body>
    <div class="page">
      <div style="background-color:#d2279c; height:15px"></div>
      <div class="inner">
        <div class="title"><b>May:</b> Where your electricity came from this month</div>
        <br><br>
        
        <div style="text-align:center">
        <table>
          <tr>
          <td style="width:33%; vertical-align:top">
            <h2>Hydro Power</h2>
            <p style="font-size:12px">Anytime your electricity use is<br>matched to the hydro</p>
            <div id="hydro_droplet_bound_m1">
              <canvas id="hydro_droplet_placeholder_m1"></canvas>
            </div>
          </td>
          <td style="width:33%; vertical-align:top">
            <h2>Extra Electricity</h2>
            <p style="font-size:12px">Use not matched to the hydro</p>
            <div id="piegraph_bound_m1">
              <canvas id="piegraph_placeholder_m1"></canvas>
            </div>
          </td>
          <td style="width:33%; vertical-align:top">
            <div style="background-color:#eee; padding:15px; text-align:left; width:248px">
              <table class="keytable">
                <tr>
                  <td><div class="key" style="background-color:#29abe2"></div></td>
                  <td><b>Hydro Power</b><br>@ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#274e3f"></div></td>
                  <td><b>Overnight Price</b><br>8pm - 6am @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#4abd3e"></div></td>
                  <td><b>Midday Price</b><br>11am till 4pm and 9pm -<br>11pm @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#ffdc00"></div></td>
                  <td><b>Morning Price</b><br>11am till 4pm and 9pm -<br>11pm @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#c92760"></div></td>
                  <td><b>Evening Price</b><br>4pm till 8pm @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
              </table>
            </div>
          </td>
          </tr>
        </table>
        <br><br>
        <div class="summary">In May you used a total of X kWh of electricity costing you £X</div>
        <p>You will also pay a £5 standing charge over this time.</p>
        </div>

      </div>
    </div>
    <br>

    <div class="page">
      <div style="background-color:#d2279c; height:15px"></div>
      <div class="inner">
        <div class="title"><b>April:</b> Where your electricity came from this month</div>
        <br><br>
        
        <div style="text-align:center">
        <table>
          <tr>
          <td style="width:33%; vertical-align:top">
            <h2>Hydro Power</h2>
            <p style="font-size:12px">Anytime your electricity use is<br>matched to the hydro</p>
            <div id="hydro_droplet_bound_m2">
              <canvas id="hydro_droplet_placeholder_m2"></canvas>
            </div>
          </td>
          <td style="width:33%; vertical-align:top">
            <h2>Extra Electricity</h2>
            <p style="font-size:12px">Use not matched to the hydro</p>
            <div id="piegraph_bound_m2">
              <canvas id="piegraph_placeholder_m2"></canvas>
            </div>
          </td>
          <td style="width:33%; vertical-align:top">
            <div style="background-color:#eee; padding:15px; text-align:left; width:248px">
              <table class="keytable">
                <tr>
                  <td><div class="key" style="background-color:#29abe2"></div></td>
                  <td><b>Hydro Power</b><br>@ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#274e3f"></div></td>
                  <td><b>Overnight Price</b><br>8pm - 6am @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#4abd3e"></div></td>
                  <td><b>Midday Price</b><br>11am till 4pm and 9pm -<br>11pm @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#ffdc00"></div></td>
                  <td><b>Morning Price</b><br>11am till 4pm and 9pm -<br>11pm @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
                <tr>
                  <td><div class="key" style="background-color:#c92760"></div></td>
                  <td><b>Evening Price</b><br>4pm till 8pm @ Xp/kWh<br>You used X kWh costing £X</td>
                </tr>
              </table>
            </div>
          </td>
          </tr>
        </table>
        <br><br>
        <div class="summary">In April you used a total of X kWh of electricity costing you £X</div>
        <p>You will also pay a £5 standing charge over this time.</p>
        </div>

      </div>
    </div>
    <br>


    <div class="page">
      <div style="background-color:#d2279c; height:15px"></div>
      <div class="inner">
      
        <div style="width:49%; float:left; text-align:center;">
        <h2>Your energy use</h2>
        <p><b>Over the month you scored x/100</b><br>We’re doing really well at using the<br>hydro and cheaper power</p>

        <img src="images/bluebadge.png" style="width:45px">
        <img id="star1" src="images/star20blue.png" style="width:45px">
        <img id="star2" src="images/star20blue.png" style="width:45px">
        <img id="star3" src="images/star20blue.png" style="width:45px">
        <img id="star4" src="images/star20blue.png" style="width:45px">
        <img id="star5" src="images/star20blue.png" style="width:45px">
        <br><br>
        
        <p><b>Last month you scored x/100</b></p>
        <img id="star1" src="images/star20blue.png" style="width:35px">
        <img id="star2" src="images/star20blue.png" style="width:35px">
        <img id="star3" src="images/star20blue.png" style="width:35px">
        <img id="star4" src="images/star20blue.png" style="width:35px">
        <img id="star5" src="images/star20blue.png" style="width:35px">
        
        <p><b>Your overall score is x/100</b></p>
        <img id="star1" src="images/star20blue.png" style="width:35px">
        <img id="star2" src="images/star20blue.png" style="width:35px">
        <img id="star3" src="images/star20blue.png" style="width:35px">
        <img id="star4" src="images/star20blue.png" style="width:35px">
        <img id="star5" src="images/star20blue.png" style="width:35px">
        <br><br>
        
        </div>
        <div style="width:49%; float:left; text-align:center;">
        <h2>Our community power</h2>
        <p><b>Over the month we scored x/100</b><br>We’re doing really well at using the hydro<br>and cheaper power</p>

        <img src="images/yellowbadge.png" style="width:45px;">
        <img id="community_star1" src="images/star20yellow.png" style="width:45px">
        <img id="community_star2" src="images/star20yellow.png" style="width:45px">
        <img id="community_star3" src="images/star20yellow.png" style="width:45px">
        <img id="community_star4" src="images/star20yellow.png" style="width:45px">
        <img id="community_star5" src="images/star20yellow.png" style="width:45px">
        <br><br>
        
        <p><b>Last month we scored x/100</b></p>
        <img id="community_star1" src="images/star20yellow.png" style="width:35px">
        <img id="community_star2" src="images/star20yellow.png" style="width:35px">
        <img id="community_star3" src="images/star20yellow.png" style="width:35px">
        <img id="community_star4" src="images/star20yellow.png" style="width:35px">
        <img id="community_star5" src="images/star20yellow.png" style="width:35px">
        
        <p><b>Our overall score is x/100</b></p>
        <img id="community_star1" src="images/star20yellow.png" style="width:35px">
        <img id="community_star2" src="images/star20yellow.png" style="width:35px">
        <img id="community_star3" src="images/star20yellow.png" style="width:35px">
        <img id="community_star4" src="images/star20yellow.png" style="width:35px">
        <img id="community_star5" src="images/star20yellow.png" style="width:35px">
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

// ----------------------------------------------------------------------------
// Month 1
// ----------------------------------------------------------------------------

var width = $("#piegraph_bound_m1").width();
var height = $("#piegraph_bound_m1").height();
if (width>400) width = 400;
$("#piegraph_placeholder_m1").attr('width',width);
var height = width*0.9;
$('#piegraph_bound_m1').attr("height",height);
$('#piegraph_placeholder_m1').attr("height",height);

var options = {
  color: "#3b6358",
  centertext: "THIS WEEK",
  width: width,
  height: height
};  

var data = [
  {name:t("MORNING"), value: 50, color:"#ffdc00"},
  {name:t("MIDDAY"), value: 80, color:"#4abd3e"},
  {name:t("EVENING"), value: 40, color:"#c92760"},
  {name:t("OVERNIGHT"), value: 100, color:"#274e3f"}
  // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
];
    
piegraph("piegraph_placeholder_m1",data,200,options);


var width = $("#hydro_droplet_bound_m1").width();
var height = $("#hydro_droplet_bound_m1").height();
if (width>400) width = 400;
$("#hydro_droplet_placeholder_m1").attr('width',width);
var height = width*0.9;
$('#hydro_droplet_bound_m1').attr("height",height);
$('#hydro_droplet_placeholder_m1').attr("height",height);

hydrodroplet("hydro_droplet_placeholder_m1",200,{width: width,height: height});

// ----------------------------------------------------------------------------
// Month 2
// ----------------------------------------------------------------------------

var width = $("#piegraph_bound_m2").width();
var height = $("#piegraph_bound_m2").height();
if (width>400) width = 400;
$("#piegraph_placeholder_m2").attr('width',width);
var height = width*0.9;
$('#piegraph_bound_m2').attr("height",height);
$('#piegraph_placeholder_m2').attr("height",height);

var options = {
  color: "#3b6358",
  centertext: "THIS WEEK",
  width: width,
  height: height
};  

var data = [
  {name:t("MORNING"), value: 50, color:"#ffdc00"},
  {name:t("MIDDAY"), value: 80, color:"#4abd3e"},
  {name:t("EVENING"), value: 40, color:"#c92760"},
  {name:t("OVERNIGHT"), value: 100, color:"#274e3f"}
  // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
];
    
piegraph("piegraph_placeholder_m2",data,200,options);


var width = $("#hydro_droplet_bound_m2").width();
var height = $("#hydro_droplet_bound_m2").height();
if (width>400) width = 400;
$("#hydro_droplet_placeholder_m2").attr('width',width);
var height = width*0.9;
$('#hydro_droplet_bound_m2').attr("height",height);
$('#hydro_droplet_placeholder_m2').attr("height",height);

hydrodroplet("hydro_droplet_placeholder_m2",200,{width: width,height: height});

// ----------------------------------------------------------------------------
// Stars
// ----------------------------------------------------------------------------


var prc = 80;
if (prc>20) $("#star1").attr("src","images/starblue.png");
if (prc>40) setTimeout(function() { $("#star2").attr("src","images/starblue.png"); }, 100);
if (prc>60) setTimeout(function() { $("#star3").attr("src","images/starblue.png"); }, 200);
if (prc>80) setTimeout(function() { $("#star4").attr("src","images/starblue.png"); }, 300);
if (prc>90) setTimeout(function() { $("#star5").attr("src","images/starblue.png"); }, 400);
          
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
