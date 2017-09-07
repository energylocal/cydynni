<?php $v = 1; 
function t($c) {return $c;}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Cyd Ynni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    
    <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
    
    <script type="text/javascript" src="lib/flot/jquery.flot.min.js"></script> 
    <script type="text/javascript" src="lib/flot/jquery.flot.time.min.js"></script> 
    <script type="text/javascript" src="lib/flot/jquery.flot.selection.min.js"></script> 
    <script type="text/javascript" src="lib/flot/jquery.flot.stack.min.js"></script>
    <script type="text/javascript" src="lib/flot/date.format.js"></script> 
    <script type="text/javascript" src="lib/vis.helper.js"></script> 
    <script type="text/javascript" src="lib/feed.js"></script> 
    
    
    <script language="javascript" type="text/javascript" src="js/community.js?v=<?php echo $v; ?>"></script>
    
    
    <link rel="stylesheet" type="text/css" href="style.css" />

  </head>
  <body>

<div class="wrap">
    <div class="app"><div class="app-inner">
            
        <div style="overflow:hidden">
            <div class="app-title">Energy<br>Dashboard</div>
            <img class="logo-full" src='images/EnergyLocalEnglish.png' style="height:80px; padding:10px">
            <img class="logo-mobile" src='images/logo.png' style="height:80px; padding:10px">
            <br>
        </div>
        
        <ul class="navigation">
            <li name="forecast"><div><img src="images/forecast.png"><div class="nav-text">CydYnni<br>Forecast</div></div></li>
            <li name="household"><div><img src="images/household.png"><div class="nav-text">Your<br>Score</div></div></li>
            <li name="community"><div><img src="images/community.png"><div class="nav-text">Community<br>Score</div></div></li>
            <li name="tips"><div><img src="images/tips.png"><div class="nav-text" style="padding-top:15px">Tips</div></div></li>
        </ul>
        
        <div class="page" name="forecast">
            <div class="block expand" style="background-color:#39aa1a">
                <div class="block-title">Forecast</div>
                <div class="block-content">

                  <div style="padding-top:5px; padding-bottom:5px">
                      <div class="legend-label-box" style="background-color:#ffb401"></div>
                      <span class="legend-label"><?php echo t("Morning");?></span>
                      <div class="legend-label-box" style="background-color:#4dac34"></div>
                      <span class="legend-label"><?php echo t("Midday");?></span>
                      <div class="legend-label-box" style="background-color:#e6602b"></div>
                      <span class="legend-label"><?php echo t("Evening");?></span>
                      <div class="legend-label-box" style="background-color:#014c2d"></div>
                      <span class="legend-label"><?php echo t("Overnight");?></span>
                      <div class="legend-label-box" style="background-color:#29aae3"></div>
                      <span class="legend-label" >Hydro</span>
                  </div>
                  
                  <div id="community_bargraph_bound" style="width:100%; height:500px;">
                    <div id="community_bargraph_placeholder" style="height:500px"></div>
                  </div>

                </div>
            </div>
            <div class="block" style="background-color:#088400">
                <div class="block-title">Forecast</div>
                <div class="block-content">
                    <p>Test</p>
                </div>
            </div>
            <div class="block" style="background-color:#005b0b">
                <div class="block-title">Forecast</div>
                <div class="block-content">
                    <p>Test</p>
                </div>
            </div>
        </div>
        
        <div class="page" name="household">
            <div class="block" style="background-color:#c20000">
                <div class="block-title">Household</div>
                <div class="block-content">
                    <p>Test</p>
                </div>
            </div>
            <div class="block" style="background-color:#e62f31">
                <div class="block-title">Household</div>
                <div class="block-content">
                    <p>Test</p>
                </div>
            </div>
            <div class="block" style="background-color:#f47677">
                <div class="block-title">Household</div>
                <div class="block-content">
                    <p>Test</p>
                </div>
            </div>
        </div>
        
        <div class="page" name="community">
            <div class="block" style="background-color:#ffb401">The Community</div>
            <div class="block" style="background-color:#ff7900">Extra Electricity</div>
            <div class="block" style="background-color:#ec4f00">Extra Electricity</div>
        </div>
        
        <div class="page" name="tips">
            <div class="block" style="background-color:#014656">Tips</div>
        </div>
        
        <div style="clear:both; height:85px"></div>

    </div></div>
</div>

<div class="app"><div class="app-inner">
    <div class="footer">
        <div style="float:right; font-weight:bold">Contact Us</div>
        <div>Energy Local</div>
    </div>
</div></div>
    
</body>
</html>

<script>

var path = "http://localhost/cydynni/";

show_page("forecast");

$(".navigation li").click(function() {
    var page = $(this).attr("name");
    show_page(page);
});

$(".block").click(function() {
    $(this).toggleClass("expand");
});

function show_page(page) {
    // Highlighted selected menu
    $(".navigation li > div").removeClass("active");
    $(".navigation li[name="+page+"] > div").addClass("active");
    // Show relevant page
    $(".page").hide();
    $(".page[name="+page+"]").show();
}

// Flot
var flot_font_size = 12;
var previousPoint = false;

community_bargraph_load();

function t(c) {return c;}

</script>
