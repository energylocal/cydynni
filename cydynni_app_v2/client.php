<?php

global $path, $translation, $lang; 
$v = 2;

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
    
    <link rel="stylesheet" type="text/css" href="style.css?v=<?php echo $v; ?>" />

  </head>
  <body>

<div class="wrap">
    <div class="app"><div class="app-inner">
            
        <div style="overflow:hidden">
            <div class="app-title"><?php echo t("Energy<br>Dashboard"); ?></div>
            
            <img class="logo-full" src='images/EnergyLocalEnglish.png' style="height:80px; padding:10px">
            <img class="logo-mobile" src='images/logo.png' style="height:80px; padding:10px">
            <div class="togglelang" style="float:right"></div>
            <br>
        </div>
        
        <ul class="navigation">
            <li name="forecast"><div><img src="images/forecast.png"><div class="nav-text"><?php echo t("CydYnni<br>Forecast"); ?></div></div></li>
            <li name="household"><div><img src="images/household.png"><div class="nav-text"><?php echo t("Your<br>Score"); ?></div></div></li>
            <li name="community"><div><img src="images/community.png"><div class="nav-text"><?php echo t("Community<br>Score"); ?></div></div></li>
            <li name="tips"><div><img src="images/tips.png"><div class="nav-text" style="padding-top:15px"><?php echo t("Tips"); ?></div></div></li>
        </ul>
        
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->

        
        <div class="page" name="forecast">

            <div class="block">
                <div class="block-title" style="background-color:#39aa1a"><?php echo t("Good time to use?"); ?><div class="triangle-dropdown hide"></div></div>
                <div class="block-content">
                  <div style="background-color:#39aa1a; color:#fff">
                  
                    <div id="status-pre" style="height:16px; padding:10px;"></div>
                    <img id="status-img" src="images/new-tick.png"/>
                    <div id="status-title" style="font-size:32px; font-weight:bold; height:32px"></div>
                    <div id="status-until" style="height:16px; padding:10px;"></div><br><br>
                    
                  </div>
                </div>
            </div>
        
            <div class="block">
                <div class="block-title" style="background-color:#088400"><?php echo t("Current Forecast"); ?><div class="triangle-dropdown hide"></div></div>
                <div class="block-content">

                  <div style="background-color:#088400; color:#fff">
                    <div id="hydro-status" style="font-size:32px; font-weight:bold"><?php echo t("HIGH"); ?></div>
                    <?php echo t("Forecasting"); ?> <span id="hydro-power">0</span> kW <?php echo t("now"); ?> 
                  </div>
                  
                  <div class="no-padding">
                    <div class="triangle-wrapper"> 
                      <div class="triangle-down">
                        <div class="triangle-down-content triangle-forecast2-bg"></div>
                      </div>
                    </div>
                  </div>

                  <div style="padding:10px">
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
                      <span class="legend-label" ><?php echo t("Hydro"); ?></span>
                    </div>
                    
                    <div id="community_bargraph_bound" style="width:100%; height:405px;">
                      <div id="community_bargraph_placeholder" style="height:405px"></div>
                    </div>
                  </div>
                  
                  <div style="background-color:#088400; color:#fff; padding:20px">
                  <?php echo t("Hydro output is currently exceeding community consumption"); ?><br>
                  <span style="font-size:14px; color:rgba(255,255,255,0.8)"><?php echo t("Light and dark grey portion indicates estimated hydro output and community consumption up to the present time"); ?></span>
                  </div>

                </div>
            </div>
                        
            <div class="block">
                <div class="block-title" style="background-color:#005b0b"><?php echo t("Top up electricity"); ?><div class="triangle-dropdown"></div></div>
                <div class="block-content hide">
                  <div style="background-color:#005b0b; color:#fff">
                    <?php echo t("Sometimes we need electricity from the grid to<br>top up the power produced by the hydro."); ?><br><br>
                    <b><?php echo t("You're currently on the"); ?></b>
                  </div>
                  
                  <div class="no-padding">
                    <div class="triangle-wrapper"> 
                      <div class="triangle-down">
                        <div class="triangle-down-content triangle-topup-bg"></div>
                      </div>
                    </div>
                  </div>
                    
                  <br>
                  <div id="tariff-now-title" style="font-size:26px; font-weight:bold; color:#29aae3"><?php echo t("HYDRO<br>PRICE"); ?></div>
                  <div id="tariff-now-circle" class="circle bg-hydro">
                      <div class="circle-inner">
                          <div id="tariff-now-price" style="font-size:36px">7p</div>
                          <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                      </div>
                  </div>
                  <br>
                  
                  <div style="background-color:#005b0b; color:#fff">
                    <div style="padding:20px">
                      <b><?php echo t("and the price at other times..."); ?></b>
                    </div>
                  </div>
                  <br>
                  
                  <div class="box4" style="color:#ffb401">
                      <div style="font-size:26px; font-weight:bold"><?php echo t("MORNING<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours"); ?></div>

                      <div class="circle bg-morning">
                          <div class="circle-inner">
                              <div style="font-size:36px">12p</div>
                              <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:24px; font-weight:bold">6am - 11am</div>
                  </div>
                  
                  <div class="box4" style="color:#4dac34">
                      <div style="font-size:26px; font-weight:bold"><?php echo t("MIDDAY<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours"); ?></div>

                      <div class="circle bg-midday">
                          <div class="circle-inner">
                              <div style="font-size:36px">10p</div>
                              <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:24px; font-weight:bold">11am - 4pm</div>
                  </div>
                  
                  <div class="box4" style="color:#e6602b">
                      <div style="font-size:26px; font-weight:bold"><?php echo t("EVENING<br>PRICE");?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours");?></div>
                      <div class="circle bg-evening">
                          <div class="circle-inner">
                              <div style="font-size:36px">14p</div>
                              <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>
                      <div style="font-size:24px; font-weight:bold">4pm - 8pm</div>
                  </div>
                  
                  <div class="box4" style="color:#014c2d">
                      <div style="font-size:26px; font-weight:bold"><?php echo t("OVERNIGHT<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours"); ?></div>
                      
                      <div class="circle bg-overnight">
                          <div class="circle-inner">
                              <div style="font-size:36px">7.25p</div>
                              <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>
                      
                      <div style="font-size:24px; font-weight:bold">8pm - 6am</div>
                  </div>
                  

                  
                  <div style="clear:both"></div>
                  <br>
                  
                  <div style="background-color:#005b0b; color:#fff; margin-bottom:10px">
                    <div style="padding:20px">
                      <?php echo t("If you wait til 8pm, you'll be on the<br>off peak tariff - that's 30% cheaper"); ?>
                    </div>
                  </div>
                
                  <div style="background-color:#29aae3; color:#fff">
                    <div style="padding:20px">
                      <?php echo t("Your local, clean energy"); ?><br>
                      <div style="font-size:26px; font-weight:bold"><?php echo t("HYDRO PRICE"); ?></div>
                    </div>
                  </div>
                
                  <br>
                  <div class="circle bg-hydro">
                      <div class="circle-inner">
                          <div style="font-size:36px">7p</div>
                          <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                      </div>
                  </div>
                  <br>
                  
                  <div style="background-color:#29aae3; color:#fff">
                    <div style="padding:20px">
                      <?php echo t("Check the Hydro tab to see when it's running!"); ?>
                    </div>
                  </div>
                  
                </div>
            </div>
        </div>

<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->

        
        <div class="page" name="household">
            <div class="block">
              <div class="block-title bg-household">Your Score and Savings<div class="triangle-dropdown hide"></div></div>
              
              <div class="block-content" style="color:#c20000">
              
                <div class="bg-household">
                  <b>Over the last seven days you<br>scored:</b>
                  <div style="font-size:22px; font-weight:bold; padding-top:5px">80/100</div>
                </div>
                
                <div class="no-padding">
                  <div class="triangle-wrapper"> 
                    <div class="triangle-down">
                      <div class="triangle-down-content triangle-household-bg"></div>
                    </div>
                  </div>
                </div>
                <br>
                <img id="household_star1" src="images/starred.png" style="width:45px">
                <img id="household_star2" src="images/starred.png" style="width:45px">
                <img id="household_star3" src="images/star20red.png" style="width:45px">
                <img id="household_star4" src="images/star20red.png" style="width:45px">
                <img id="household_star5" src="images/star20red.png" style="width:45px">
              
                <p>Your doing really well at using hydro & cheaper power</p>
                
                <div class="bg-household" style="height:100px">
                  <div style="padding:5px">
                    <p>Your used 7.3 kWh on June 24.<br>It cost £0.68</p>
                    <p>Compared with 12p/kWh reference price, you<br>saved:</p>
                  </div>
                </div>
                
                <div class="no-padding">
                  <div class="triangle-wrapper"> 
                    <div class="triangle-down">
                      <div class="triangle-down-content triangle-household-bg"></div>
                    </div>
                  </div>
                </div>
                
                <br>
                <div class="circle bg-household">
                    <div class="circle-inner" style="padding-top:52px">
                        <div style="font-size:36px">£0.25</div>
                    </div>
                </div>
                <br>
                
              </div>
            </div>
            
            <div class="block">
                <div class="block-title" style="background-color:#e62f31">Your usage over the last 24 hrs<div class="triangle-dropdown hide"></div></div>
                <div class="block-content">
                    <p>Test</p>
                </div>
            </div>
            <div class="block">
                <div class="block-title" style="background-color:#f47677">Your usage by price<div class="triangle-dropdown hide"></div></div>
                <div class="block-content">
                    <p>Test</p>
                </div>
            </div>
        </div>
       
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->
        
        <div class="page" name="community">
            <div class="block">
                <div class="block-title" style="background-color:#ffb401"><?php echo t("Community score and savings"); ?><div class="triangle-dropdown hide"></div></div>
                
                <div class="block-content" style="color:#ffb401">
                
                  <div style="background-color:#ffb401; color:#fff">
                    <b><?php echo t("Over the last seven days you<br>scored:"); ?></b>
                    <div style="font-size:22px; font-weight:bold; padding-top:5px"><span id="community_score">50</span>/100</div>
                  </div>
                  
                  <div class="no-padding">
                    <div class="triangle-wrapper"> 
                      <div class="triangle-down">
                        <div class="triangle-down-content triangle-community-bg"></div>
                      </div>
                    </div>
                  </div>
                  <br>
                  <img id="community_star1" src="images/staryellow.png" style="width:45px">
                  <img id="community_star2" src="images/staryellow.png" style="width:45px">
                  <img id="community_star3" src="images/star20yellow.png" style="width:45px">
                  <img id="community_star4" src="images/star20yellow.png" style="width:45px">
                  <img id="community_star5" src="images/star20yellow.png" style="width:45px">
                
                  <p id="community_statusmsg"></p>
                  
                <div style="background-color:#ffb401; color:#fff; height:50px">
                  <div style="padding:5px">
                    <p><?php echo t("Together you've kept"); ?></p>
                  </div>
                </div>
                
                <div class="no-padding">
                  <div class="triangle-wrapper"> 
                    <div class="triangle-down">
                      <div class="triangle-down-content triangle-community-bg"></div>
                    </div>
                  </div>
                </div>
                
                <br>
                <div class="circle bg-community">
                    <div class="circle-inner" style="padding-top:52px">
                        <div style="font-size:36px">£12.68</div>
                    </div>
                </div>
                <br>
                
                <div style="background-color:#ffb401; color:#fff">
                  <div style="padding:5px">
                    <p><?php echo t("in the local area by using your local resource<br>hydro power!"); ?></p>
                  </div>
                </div>
                  
                </div>
            </div>
            <div class="block">
                <div class="block-title" style="background-color:#ff7900"><?php echo t("Community breakdown"); ?><div class="triangle-dropdown hide"></div></div>
                <div class="block-content">

                    <div class="box3">
                      <div id="hydro_droplet_bound" style="margin: 0 auto">
                        <canvas id="hydro_droplet_placeholder"></canvas>
                      </div>
                    </div>
                    
                    <div class="box3">
                      <div id="community_piegraph1_bound" style="width:100%; height:405px; margin: 0 auto">
                          <canvas id="community_piegraph1_placeholder"></canvas>
                      </div>
                    </div>
                
                    <div class="box3">
                      <div id="community_piegraph2_bound" style="width:100%; height:405px; margin: 0 auto">
                          <canvas id="community_piegraph2_placeholder"></canvas>
                      </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->

        <div class="page" name="tips">
            <div class="block">
                <div class="block-title bg-tips"><?php echo t("Tips"); ?><div class="triangle-dropdown hide"></div></div>
                <div class="block-content bg-tips" style="padding:20px">
                Tips content to be added...
                </div>
            </div>
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

<script language="javascript" type="text/javascript" src="js/cydynnistatus.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/pie.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/community.js?v=<?php echo $v; ?>"></script>

<script>

var path = "<?php echo $path; ?>";
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";

// Language selection top-right
if (lang=="cy") {
    $(".togglelang").html("EN");
} else {
    $(".togglelang").html("CY");
}

show_page("forecast");

$(".navigation li").click(function() {
    var page = $(this).attr("name");
    show_page(page);
});

$(".block-title").click(function() {
    $(this).parent().find(".block-content").slideToggle( "slow" );
    $(this).find(".triangle-dropdown").toggle();
});

function show_page(page) {

    // Highlighted selected menu
    $(".navigation li > div").removeClass("active");
    $(".navigation li[name="+page+"] > div").addClass("active");
    // Show relevant page
    $(".page").hide();
    $(".page[name="+page+"]").show();
}

$(window).resize(function(){
    resize();
});

function resize() {
    window_height = $(window).height();
    window_width = $(window).width();
    community_bargraph_resize();
    community_pie_draw();
}

// Flot
var flot_font_size = 12;
var previousPoint = false;

cydynnistatus_update();
community_summary_load();
community_bargraph_load();

// ----------------------------------------------------------------------
// Translation
// ----------------------------------------------------------------------

// Language selection
$(".togglelang").click(function(){
    var ilang = $(this).html();
    if (ilang=="CY") {
        $(this).html("EN");
        window.location = "?lang=cy";
    } else {
        $(this).html("CY");
        lang="cy";
        window.location = "?lang=en";
    }
});

// Javascript text translation function
function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}

</script>
