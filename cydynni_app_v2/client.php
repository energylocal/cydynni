<?php

global $path, $translation, $lang;
$v = time();

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Cyd Ynni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes"
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="images/icon/ms-icon-144x144.png">
    <meta name="theme-color" content="#006400">
    <link rel="apple-touch-icon" sizes="57x57" href="images/icon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="images/icon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="images/icon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="images/icon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="images/icon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="images/icon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="images/icon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="images/icon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="images/icon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="images/icon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="images/icon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/icon/favicon-16x16.png">
    <link rel="manifest" href="manifest.json">

    <!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->
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
        <div class="oembluebar">
            <div class="oembluebar-inner">
                <div id="dashboard" class="oembluebar-item" active>CydYnni Dashboard</div>
                <div id="reports" class="oembluebar-item">Reports</div>

                <div id="logout" class="oembluebar-item" style="float:right">Logout</div>
                <div id="togglelang" class="oembluebar-item" style="float:right"></div>
            </div>
        </div>
        <div class="wrap">
            <div class="app">
                <div class="app-inner">
                    <div class="title-wrapper">
                        <img class="logo-full" src='images/EnergyLocalEnglish.png'>
                        <img class="logo-mobile" src='images/logo.png'>
                        <div class="app-title">
                        <div class="app-title-content"><?php echo t("Energy<br>Dashboard"); ?>
                        </div>
                    </div>
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
                    <?php echo t("Sometimes we need electricity from the grid to top up the power produced by the hydro."); ?><br><br>
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
                      <?php echo t("If you wait til 8pm, you'll be on the off peak tariff - that's 30% cheaper"); ?>
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
            <!--
            <div id="passwordreset-block" style="text-align:center; display:none">
              <div class="login-box">
              <p id="passwordreset-title"></p>
              <p>
                <input id="passwordreset-email" type="text" placeholder="Email..." style="border: 1px solid rgb(41,171,226)"><br><br>
                <button id="passwordreset" class="btn"><?php echo t("Reset password");?></button> <button id="passwordreset-cancel" class="btn"><?php echo t("Cancel");?></button><br>
              </p>
              <div id="passwordreset-alert"></div>
              </div>
            </div>
            -->
            
            <div id="login-block" class="block">
                <div class="block-title bg-household"><div class="triangle-dropdown hide"></div></div>
                <div class="block-content">
                    
                    <div class="bg-household" style="padding:20px">
                    
                        <div style="font-weight:bold; font-size:32px">Log in</div>
                        Please login to view account<br><br>
                    
                        <input id="email" type="text" placeholder="Email..."><br><br>
                        <input id="password" type="password" placeholder="Password..."><br>
                        
                        <br>
                        <button id="login" class="btn"><?php echo t("Login");?></button><br><br>
                        <div id="passwordreset-start" style="display:inline-block; cursor:pointer;"><?php echo t("Forgotten your password?");?></div>
                        <br><br>
                        <div id="alert"></div>
                    </div>
                </div>
            </div>
            
            <div class="block household-block">
              <div class="block-title bg-household">Your Score and Savings<div class="triangle-dropdown hide" style="margin-left:10px"></div></div>
              
              <div class="block-content" style="color:#c20000">
              
                <div class="bg-household">
                  <b>Over the last seven days you scored:</b>
                  <div style="font-size:22px; font-weight:bold; padding-top:5px"><span class="household_score"></span>/100</div>
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
              
                <p class="household_status"></p>
                
                <div class="bg-household" style="height:100px">
                  <div style="padding:5px">
                    <p>Your used <span class="household_totalkwh"></span> kWh on <span class="household_date"></span>. It cost £<span class="household_totalcost"></span></p>
                    <p>Compared with 12p/kWh reference price, you saved:</p>
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
                        <div style="font-size:36px" class="household_costsaving">£0.00</div>
                    </div>
                </div>
                <br>
                
              </div>
            </div>
            
            <div class="block household-block">
                <div class="block-title bg-household2">Your usage over the last 24 hrs<div class="triangle-dropdown hide"></div></div>
                <div class="block-content">
                
                    <div class="bg-household2">
                      <div class="bound"><?php echo t("Here's what your electricity use looked like on"); ?><br><b><span class="household_date"></span> 2017</b></div>
                    </div>
                    
                    <div class="no-padding">
                      <div class="triangle-wrapper">
                        <div class="triangle-down">
                          <div class="triangle-down-content triangle-household2-bg"></div>
                        </div>
                      </div>
                    </div>
                
                    <div style="padding:10px">
                        <div id="household_bargraph_bound" style="width:100%; height:405px;">
                            <div id="household_bargraph_placeholder" style="height:405px"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block household-block">
                <div class="block-title bg-household3"><?php echo t("Your usage by price"); ?><div class="triangle-dropdown hide"></div></div>
                <div class="block-content">
                
                    <div class="bg-household3">
                      <div class="bound" style="padding-bottom:20px"><?php echo t("Your electricity is provided on five different price bands. Here's how much of each you've used over the last 24 hours."); ?></div>
                    </div>
                    
                    <br>
                    
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold; color:#f47677"><?php echo t("OPTION 1"); ?></div>
                      <div id="household_piegraph1_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="household_piegraph1_placeholder"></canvas>
                      </div>
                    </div>
                
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold; color:#f47677"><?php echo t("OPTION 2"); ?></div>
                      <div id="household_piegraph2_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="household_piegraph2_placeholder"></canvas>
                      </div>
                    </div>
                    
                    <div class="box3">
                      <div style="padding:15px; text-align:left; margin: 0 auto; max-width:270px">
                        <table class="keytable">
                          <tr>
                            <td><div class="key" style="background-color:#29abe2"></div></td>
                            <td><b><?php echo t("Hydro Power");?></b><br><span id="household_hydro_kwh"></span> kWh @ 7.0 p/kWh<br><?php echo t("Costing");?> £<span id="household_hydro_cost"></span></td>
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

                    <div class="bg-household3" style="padding:20px">
                      <div class="bound"><?php echo t("Head to the tips section or get in touch with your Energy Local club to see how you can shift more of your use to cheaper times."); ?></div>
                    </div>
                    
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
                    <b><?php echo t("Over the last seven days you scored:"); ?></b>
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
                  <img id="community_star1" src="images/star20yellow.png" style="width:45px">
                  <img id="community_star2" src="images/star20yellow.png" style="width:45px">
                  <img id="community_star3" src="images/star20yellow.png" style="width:45px">
                  <img id="community_star4" src="images/star20yellow.png" style="width:45px">
                  <img id="community_star5" src="images/star20yellow.png" style="width:45px">
                
                  <br><br>
                  <div class="bound" id="community_statusmsg"></div><br>
                  
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
                        <div style="font-size:36px" class="community_hydro_value" >£00.00</div>
                    </div>
                </div>
                <br>
                
                <div style="background-color:#ffb401; color:#fff; padding:20px">
                    <div class="bound"><?php echo t("in the local area by using your local resource hydro power!"); ?></div>
                </div>
                  
                </div>
            </div>
            <div class="block">
                <div class="block-title bg-community2"><?php echo t("Community breakdown"); ?><div class="triangle-dropdown hide"></div></div>
                <div class="block-content">
                
                    <div class="bg-community2">
                      <div class="bound"><?php echo t("How much of the electricity the community used, came from the hydro."); ?></div>
                    </div>
                    
                    <div class="no-padding">
                      <div class="triangle-wrapper">
                        <div class="triangle-down">
                          <div class="triangle-down-content triangle-community2-bg"></div>
                        </div>
                      </div>
                    </div>
                    <br>

                    <!--
                    <div class="box3">
                      <div id="hydro_droplet_bound" style="margin: 0 auto">
                        <canvas id="hydro_droplet_placeholder"></canvas>
                      </div>
                    </div>-->
                    
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold; color:#ff7900"><?php echo t("OPTION 1"); ?></div>
                      <div id="community_piegraph1_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="community_piegraph1_placeholder"></canvas>
                      </div>
                    </div>
                
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold; color:#ff7900"><?php echo t("OPTION 2"); ?></div>
                      <div id="community_piegraph2_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="community_piegraph2_placeholder"></canvas>
                      </div>
                    </div>
                    
                    <div class="box3">
                      <div style="padding:15px; text-align:left; margin: 0 auto; max-width:270px">
                        <table class="keytable">
                          <tr>
                            <td><div class="key" style="background-color:#29abe2"></div></td>
                            <td><b><?php echo t("Hydro Power");?></b><br><span id="community_hydro_kwh"></span> kWh @ 7.0 p/kWh<br><?php echo t("Costing");?> £<span id="community_hydro_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#ffdc00"></div></td>
                            <td><b><?php echo t("Morning Price");?></b> 6am - 11am<br><span id="community_morning_kwh"></span> kWh @ 12p/kWh<br><?php echo t("Costing");?> £<span id="community_morning_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#4abd3e"></div></td>
                            <td><b><?php echo t("Midday Price");?></b> 11am - 4pm<br><span id="community_midday_kwh"></span> kWh @ 10p/kWh<br><?php echo t("Costing");?> £<span id="community_midday_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#c92760"></div></td>
                            <td><b><?php echo t("Evening Price");?></b> 4pm - 8pm<br><span id="community_evening_kwh"></span> kWh @ 14p/kWh<br><?php echo t("Costing");?> £<span id="community_evening_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#274e3f"></div></td>
                            <td><b><?php echo t("Overnight Price");?></b> 8pm - 6am<br><span id="community_overnight_kwh"></span> kWh @ 7.25p/kWh<br><?php echo t("Costing");?> £<span id="community_overnight_cost"></span></td>
                          </tr>
                        </table>
                      </div>
                    </div>
                    
                    <div style="clear:both"></div>

                    <div class="bg-community2" style="padding:20px">
                      <div class="bound"><?php echo t("The bigger the percentage of hydro, the more money stays in."); ?></div>
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
                    <figure class="tips-appliance show-fig">
                        <img src="images/dishwasher.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("DISHWASHER") ?></h2>
                            </div>
                            <?php echo t("The time you run your dishwasher can be moved to avoid morning and evening peaks and take advantage of hydro power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am).")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="images/lamp.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("LED LIGHTS") ?></h2>
                            </div>
                            <?php echo t("Switching off lights and appliance when not in use is a simple and effective way to use less electricity. You can make a special effort to do this during the morning and evening peaks.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="images/stove.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("COOKING") ?></h2>
                            </div>
                            <?php echo t("Putting a lid on your pan when you're cooking traps the heat inside so you don’t need to have the hob on as high. A simple and effective way to use less electricity.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="images/slowcooker.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("SLOW COOKING") ?></h2>
                            </div>
                            <?php echo t("Slow cookers are very energy efficient, make tasty dinners and help you avoid using electricity during the evening peak (4 - 8pm) when you might otherwise be using an electric oven.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="images/washingmachine.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("WASHING MACHINE") ?></h2>
                            </div>
                            <?php echo t("The time you run your washing machine can be moved to avoid morning and evening peaks and take advantage of hydro power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am).")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="images/fridge.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("FRIDGES & FREEZERS") ?></h2>
                            </div>
                            <?php echo t("Try to minimise how often and how long you need to open the doors. Wait for cooked food to cool before putting it in the fridge. Older fridges and freezers can be very inefficient and costly to run.")
                            ?>
                        </figcaption>
                    </figure>
                    <figure class="tips-appliance">
                        <img src="images/lightbulb.png">
                        <figcaption>
                            <div class="tips-appliance-name">
                                <h2><?php echo t("LIGHTS") ?></h2>
                            </div>
                            <?php echo t("LED lights can cut your lighting costs by up to 90%. There’s more information on our website and in the info pack on installing them in your house.")
                            ?>
                        <figcaption>
                    </figure>
                    
                    <div>
                        <div class="tips-arrow-outer-wrapper">
                            <div class="tips-arrow-inner-wrapper leftclick">
                                <div class="tips-leftarrow"></div>
                                <div class="tips-directions"><?php echo t("PREVIOUS"); ?></div>
                            </div>
                            <div class="tips-arrow-inner-wrapper rightclick">
                                <div class="tips-directions"><?php echo t("NEXT TIP"); ?></div>
                                <div class="tips-rightarrow"></div>
                            </div>
                        </div>
                    </div>
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
<script language="javascript" type="text/javascript" src="js/household.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/community.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/user.js?v=<?php echo $v; ?>"></script>
<script>

var path = "<?php echo $path; ?>";
var session = <?php echo json_encode($session); ?>;
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";

// Language selection top-right
if (lang=="cy") {
    $("#togglelang").html("English");
} else {
    $("#togglelang").html("Cymraeg");
}

if (!session.write) {
  $("#login-block").show();
  $(".household-block").hide();
  
  $("#logout").hide();
  $("#reports").hide();
} else {
  $("#login-block").hide();
  $(".household-block").show();
  
  $("#logout").show();
  $("#reports").show();
}

show_page("forecast");

$(".navigation li").click(function() {
    var page = $(this).attr("name");
    show_page(page);
});

$(".block-title").click(function() {
    $(this).parent().find(".block-content").slideToggle("slow");
    $(this).find(".triangle-dropdown").toggle();
});

function show_page(page) {

    // Highlighted selected menu
    $(".navigation li > div").removeClass("active");
    $(".navigation li[name="+page+"] > div").addClass("active");
    // Show relevant page
    $(".page").hide();
    $(".page[name="+page+"]").show();

    if (page=="forecast") {
        community_pie_draw();
        community_bargraph_resize();
    }
    
    if (page=="household") {
        household_pie_draw();
        household_bargraph_resize();
    }
}

$(window).resize(function(){
    resize();
});

function resize() {
    window_height = $(window).height();
    window_width = $(window).width();
    
    community_pie_draw();
    community_bargraph_resize();
    
    household_pie_draw();
    household_bargraph_resize();
}

// Flot
var flot_font_size = 12;
var previousPoint = false;

cydynnistatus_update();

community_summary_load();
community_bargraph_load();

if (session.write) {
    household_summary_load();
    household_bargraph_load();
}

resize();
// ----------------------------------------------------------------------
// Translation
// ----------------------------------------------------------------------

// Language selection
$("#togglelang").click(function(){
    var ilang = $(this).html();
    if (ilang=="Cymraeg") {
        $(this).html("English");
        window.location = "?lang=cy";
    } else {
        $(this).html("Cymraeg");
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

// ----------------------------------------------------------------------
// Tips
// ----------------------------------------------------------------------

/*
function tipscheck() { // From beginning to end, does not loop
    if ($(".tips-appliance:last").hasClass("show-fig")) {
        $(".rightclick").addClass("tips-noshow");
    }
    else if ($(".tips-appliance:first").hasClass("show-fig")) {
        $(".leftclick").addClass("tips-noshow");
    }
    else {
        $(".leftclick, .rightclick").removeClass("tips-noshow");
    }
}

$(".leftclick").click(function(){
    $(".show-fig").removeClass("show-fig").prev().addClass("show-fig");
    tipscheck();
});

$(".rightclick").click(function(){
    $(".show-fig").removeClass("show-fig").next().addClass("show-fig");
    tipscheck();
});

tipscheck();
*/


$(".leftclick").click(function(){
    $(".figholder").removeClass("figholder");
    $(".show-fig").removeClass("show-fig").addClass("figholder");
        if ( $(".figholder").prev().hasClass("tips-appliance") ) {
            $(".figholder").prev().addClass("show-fig");
        }
        else {
            $(".tips-appliance:last").addClass("show-fig");
        }
});

$(".rightclick").click(function(){
    $(".figholder").removeClass("figholder");
    $(".show-fig").removeClass("show-fig").addClass("figholder");
        if ( $(".figholder").next().hasClass("tips-appliance") ) {
            $(".figholder").next().addClass("show-fig");
        }
        else {
            $(".tips-appliance:first").addClass("show-fig");
        }
});

$("#reports").click(function(){
    window.location = "report";
});

</script>
