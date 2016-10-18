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
      text-align:center;
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
      font-size:32px;
      color:#666;
      padding:10px;
    }
    
    .summary {
      font-size:22px;
      color:#666;
      padding:10px;
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

    </style>
  </head>

  <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
  <script language="javascript" type="text/javascript" src="js/pie.js"></script>

  <body>
    <div class="page">
      <div class="inner">
        <div class="title">Where your electricity came from in May</div>
        <hr>
        <div id="community_piegraph_bound">
          <canvas id="community_piegraph_placeholder"></canvas>
        </div>
        <div class="summary">In May you used a total of 402 kWh of electricity costing you £43.68</div>
        <p>You will also pay a £5 standing charge over this time.</p>
        
        <br><br>
        
        <table style="width:100%">
          <tr>
            <td style="width:50%; text-align:left">
              <p>You’re doing ok at using hydro & cheaper power. Can you move more of your use away from peak times?</p>
            </td>
            <td style="width:50%">
              <img id="star1" src="images/star20blue.png" style="width:45px">
              <img id="star2" src="images/star20blue.png" style="width:45px">
              <img id="star3" src="images/star20blue.png" style="width:45px">
              <img id="star4" src="images/star20blue.png" style="width:45px">
              <img id="star5" src="images/star20blue.png" style="width:45px">
            </td>
          </tr>
        </table>
        
        <br><br>
        
        <table style="width:100%">
          <tr>
            <td style="width:50%; text-align:left">
              <p>We could do more to make the most of the hydro power and power at cheaper times of day. Can we move more electricity use away from peak times?</p>
            </td>
            <td style="width:50%">
              <img id="community_star1" src="images/star20yellow.png" style="width:45px">
              <img id="community_star2" src="images/star20yellow.png" style="width:45px">
              <img id="community_star3" src="images/star20yellow.png" style="width:45px">
              <img id="community_star4" src="images/star20yellow.png" style="width:45px">
              <img id="community_star5" src="images/star20yellow.png" style="width:45px">
            </td>
          </tr>
        </table>
        
      </div>
    </div>
  </body>

</html>

<script>
var path = "<?php echo $path; ?>";
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";

var width = $("#community_piegraph_bound").width();
var height = $("#community_piegraph_bound").height();
if (width>400) width = 400;
$("#community_piegraph_placeholder").attr('width',width);
var height = width*0.9;
$('#community_piegraph_bound').attr("height",height);
$('#community_piegraph_placeholder').attr("height",height);

var options = {
  color: "#3b6358",
  centertext: "THIS WEEK",
  width: width,
  height: height
};  

var data = [
  {name:t("MORNING"), value: 50, color:"#ffdc00"},
  {name:t("MIDDAY"), value: 80, color:"#29abe2"},
  {name:t("EVENING"), value: 40, color:"#c92760"},
  {name:t("OVERNIGHT"), value: 100, color:"#274e3f"}
  // {name:"HYDRO", value: 2.0, color:"rgba(255,255,255,0.2)"}   
];
    
piegraph("community_piegraph_placeholder",data,options);

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
