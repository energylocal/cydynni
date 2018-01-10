<?php

global $path, $translation, $lang;
$v = 4;

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
    
    <link rel="stylesheet" type="text/css" href="css/style.css?v=<?php echo $v; ?>" />
    
    </head>
    <body>
        <div class="oembluebar">
            <div class="oembluebar-inner">
                <div id="dashboard" class="oembluebar-item active" ><?php echo t("Dashboard"); ?></div>
                <div id="reports" class="oembluebar-item"><?php echo t("Reports"); ?></div>

                <div id="logout" class="oembluebar-item" style="float:right"><img src="images/logout.png" height="18px"/></div>
                <div id="account" class="oembluebar-item" style="float:right"><img src="images/el-person-icon.png" height="18px"/></div>
                <div id="togglelang" class="oembluebar-item" style="float:right"></div>

            </div>
        </div>
        <div class="wrap">
            <div class="app">
                <div class="app-inner">
                    <div class="title-wrapper">
                        <img class="logo-full" src='images/<?php echo t("EnergyLocalEnglish.png"); ?>'>
                        <img class="logo-mobile" src='images/logo.png'>
                        <div class="app-title">
                        <div class="app-title-content"><?php echo t("Energy<br>Dashboard"); ?>
                        </div>
                    </div>
                </div>
                <ul class="navigation">
                    <li name="forecast"><div><img src="images/forecast.png"><div class="nav-text"><?php echo t("CydYnni<br>Forecast"); ?></div></div></li>
                    <li name="household"><div><img src="images/household.png"><div class="nav-text"><?php echo t("Your<br>Score"); ?></div></div></li>
                    <li name="club"><div><img src="images/club.png"><div class="nav-text"><?php echo t("Club<br>Score"); ?></div></div></li>
                    <!--<li name="tips"><div><img src="images/tips.png"><div class="nav-text" style="padding-top:15px"><?php echo t("Tips"); ?></div></div></li>-->
                    <li name="devices"><div><img src="images/devices.png"><div class="nav-text" style="padding-top:15px"><?php echo t("Devices"); ?></div></div></li>
                </ul>
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->

        <div class="page" name="forecast">

            <div class="block">
                <div class="block-title" style="background-color:#39aa1a"><?php echo t("Good time to use?"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                  <div style="background-color:#39aa1a; color:#fff">
                  
                    <div id="status-pre" style="padding:10px;"></div>
                    <img id="status-img" src="images/new-tick.png"/>
                    <div id="status-title" style="font-size:32px; font-weight:bold; height:32px"></div>
                    <div id="status-until" style="height:16px; padding:10px;"></div><br><br>
                    
                  </div>
                </div>
            </div>
        
            <div class="block">
                <div class="block-title" style="background-color:#088400"><?php echo t("Local Electricity Forecast"); ?>
                
                <div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div>
                <div class="visnav-block">
                  <div class="visnav-club club-left"><</div>
                  <div class="visnav-club club-right">></div>
                  <div class="visnav-club club-month"><?php echo t("MONTH");?></div>
                  <div class="visnav-club club-week"><?php echo t("WEEK");?></div>
                  <!--<div class="visnav-club club-day"><?php echo t("DAY");?></div>-->
                </div>
                
                
                </div>
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
                    
                    <div id="club_bargraph_bound" style="width:100%; height:405px;">
                      <div id="club_bargraph_placeholder" style="height:405px"></div>
                    </div>
                  </div>
                  
                  <div style="background-color:#088400; color:#fff; padding:20px">
                  <?php echo t("Hydro output is currently exceeding club consumption"); ?><br>
                  <span style="font-size:14px; color:rgba(255,255,255,0.8)"><?php echo t("Light and dark grey portion indicates estimated hydro output and club consumption up to the present time"); ?></span>
                  </div>

                </div>
            </div>

            <div class="block">
                <div class="block-title" style="background-color:#005b0b"><?php echo t("Current Tariff"); ?><div class="triangle-dropdown show"></div><div class="triangle-pushup hide"></div></div>
                <div class="block-content hide">
                  <div style="background-color:#005b0b; color:#fff">
                    <div class="bound">
                    <b><?php echo t("You're currently on the"); ?></b>
                    </div>
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
                </div>
            </div>
                                    
            <div class="block">
                <div class="block-title" style="background-color:#005b0b"><?php echo t("Your prices for power"); ?><div class="triangle-dropdown show"></div><div class="triangle-pushup hide"></div></div>
                <div class="block-content hide">

                  <br>

                  <div id="hydro-tariff-box" class="box5" style="color:#29aae3">
                      <div style="font-size:26px; font-weight:bold"><?php echo t("HYDRO<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Your local electricity"); ?></div>

                      <div class="circle bg-hydro">
                          <div class="circle-inner">
                              <div style="font-size:36px">7p</div>
                              <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:22px; font-weight:bold"></div>
                  </div>
                    
                  <div style="margin-bottom:10px; color:#444;"><?php echo t("Prices for extra electricity (in the event your local electricity is not covering all of your needs)"); ?></div>  
                    
                  <div id="morning-tariff-box" class="box5" style="color:#ffb401">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("MORNING<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours"); ?></div>

                      <div class="circle-small bg-morning">
                          <div class="circle-small-inner">
                              <div style="font-size:32px">12p</div>
                              <div style="font-size:18px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:22px; font-weight:bold">6am - 11am</div>
                  </div>
                  
                  <div id="midday-tariff-box" class="box5" style="color:#4dac34">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("MIDDAY<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours"); ?></div>

                      <div class="circle-small bg-midday">
                          <div class="circle-small-inner">
                              <div style="font-size:32px">10p</div>
                              <div style="font-size:18px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:24px; font-weight:bold">11am - 4pm</div>
                  </div>
                  
                  <div id="evening-tariff-box" class="box5" style="color:#e6602b">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("EVENING<br>PRICE");?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours");?></div>
                      <div class="circle-small bg-evening">
                          <div class="circle-small-inner">
                              <div style="font-size:32px">14p</div>
                              <div style="font-size:18px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>
                      <div style="font-size:22px; font-weight:bold">4pm - 8pm</div>
                  </div>
                  
                  <div id="overnight-tariff-box" class="box5" style="color:#014c2d">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("OVERNIGHT<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Starts in X hours"); ?></div>
                      
                      <div class="circle-small bg-overnight">
                          <div class="circle-small-inner">
                              <div style="font-size:32px">7.25p</div>
                              <div style="font-size:18px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>
                      
                      <div style="font-size:22px; font-weight:bold">8pm - 6am</div>
                  </div>
                  

                  
                  <div style="clear:both"></div>
                  <br>
                  
                  <div style="background-color:#005b0b; color:#fff; margin-bottom:10px">
                    <div style="padding:20px">
                      <?php echo t("Check the Local Electricity Forecast tab to see if it is high or low!"); ?>
                    </div>
                  </div>
                  
                </div>
            </div>
        </div>

<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->
<!------------------------------------------------------------------------------------------------------------------->

        
        <div class="page" name="household">
            
            <div id="login-block" class="block">
                <div class="block-title bg-household"><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                    
                    <div class="bg-household" style="padding:20px">
                    
                        <div style="font-weight:bold; font-size:32px"><?php echo t("Log in"); ?></div>
                        <?php echo t("Please login to view account"); ?><br><br>
                    
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

            <div id="passwordreset-block" class="block" style="display:none">
                <div class="block-title bg-household"><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">                    
                    <div class="bg-household" style="padding:20px">
                        <p id="passwordreset-title"></p>
                        <p>
                          <input id="passwordreset-email" type="text" placeholder="Email..." style="border: 1px solid rgb(41,171,226)"><br><br>
                          <button id="passwordreset" class="btn"><?php echo t("Reset password");?></button> <button id="passwordreset-cancel" class="btn"><?php echo t("Cancel");?></button><br>
                        </p>
                        <div id="passwordreset-alert"></div>
                    </div>
                </div>
            </div>

            <div class="block household-block">
              <div class="block-title bg-household"><?php echo t("Your Score and Savings"); ?><div class="triangle-dropdown hide" style="margin-left:10px"></div><div class="triangle-pushup show" style="margin-left:10px"></div></div>
              
              <div class="block-content" style="color:#c20000">
              
                <div class="bg-household">
                  <b><?php echo t("On the"); ?> <span class="household_date"></span> <?php echo t("you scored"); ?>:</b>
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
                    <p><?php echo t("You used"); ?> <span class="household_totalkwh"></span> kWh. <?php echo t("It cost"); ?> £<span class="household_totalcost"></span></p>
                    <p><?php echo t("Compared with 12p/kWh reference price, you saved"); ?>:</p>
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
                <div class="block-title bg-household2"><?php echo t("Your usage over time"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
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
                <div class="block-title bg-household3"><?php echo t("Your usage by price"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                
                    <div class="bg-household3">
                      <div class="bound" style="padding-bottom:20px"><?php echo t("Your electricity is provided on five different price bands. Here's how much of each you used on"); ?> <span class="household_date"></span> 2017</div>
                    </div>
                    
                    <br>
                    
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold; color:#f47677"><?php echo t("ELECTRICITY"); ?></div>
                      <div id="household_piegraph1_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="household_piegraph1_placeholder"></canvas>
                      </div>
                      <div id="household_hrbar1_bound" style="width:100%; height:50px; margin: 0 auto">
                          <canvas id="household_hrbar1_placeholder"></canvas>
                      </div>
                      <br>
                    </div>
                
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold; color:#f47677"><?php echo t("COST"); ?></div>
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
        
        <div class="page" name="club">
            <div class="block">
                <div class="block-title" style="background-color:#ffb401"><?php echo t("Club score and savings"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                
                <div class="block-content" style="color:#ffb401">
                
                  <div style="background-color:#ffb401; color:#fff">
                    <b><?php echo t("On the"); ?> <span class="club_date"></span> <?php echo t("we scored"); ?>:</b>
                    <div style="font-size:22px; font-weight:bold; padding-top:5px"><span id="club_score">50</span>/100</div>
                  </div>
                  
                  <div class="no-padding">
                    <div class="triangle-wrapper">
                      <div class="triangle-down">
                        <div class="triangle-down-content triangle-club-bg"></div>
                      </div>
                    </div>
                  </div>
                  <br>
                  <img id="club_star1" src="images/star20yellow.png" style="width:45px">
                  <img id="club_star2" src="images/star20yellow.png" style="width:45px">
                  <img id="club_star3" src="images/star20yellow.png" style="width:45px">
                  <img id="club_star4" src="images/star20yellow.png" style="width:45px">
                  <img id="club_star5" src="images/star20yellow.png" style="width:45px">
                
                  <br><br>
                  <div class="bound" id="club_statusmsg"></div><br>
                  
                <div style="background-color:#ffb401; color:#fff; height:50px">
                  <div style="padding:5px">
                    <p><?php echo t("Together we've kept"); ?></p>
                  </div>
                </div>
                
                <div class="no-padding">
                  <div class="triangle-wrapper">
                    <div class="triangle-down">
                      <div class="triangle-down-content triangle-club-bg"></div>
                    </div>
                  </div>
                </div>
                
                <br>
                <div class="circle bg-club">
                    <div class="circle-inner" style="padding-top:52px">
                        <div style="font-size:36px" class="club_hydro_value" >£00.00</div>
                    </div>
                </div>
                <br>
                
                <div style="background-color:#ffb401; color:#fff; padding:20px">
                    <div class="bound"><?php echo t("in the local area by using your local resource hydro power!"); ?></div>
                </div>
                  
                </div>
            </div>
            <div class="block">
                <div class="block-title bg-club2"><?php echo t("Club breakdown"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                
                    <div class="bg-club2">
                      <div class="bound"><?php echo t("How much of the electricity the club used, came from the hydro."); ?></div>
                    </div>
                    
                    <div class="no-padding">
                      <div class="triangle-wrapper">
                        <div class="triangle-down">
                          <div class="triangle-down-content triangle-club2-bg"></div>
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
                      <div style="font-size:26px; font-weight:bold; color:#ff7900"><?php echo t("ELECTRICITY"); ?></div>
                      <div id="club_piegraph1_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="club_piegraph1_placeholder"></canvas>
                      </div>
                      <div id="club_hrbar1_bound" style="width:100%; height:50px; margin: 0 auto">
                          <canvas id="club_hrbar1_placeholder"></canvas>
                      </div>
                      <br>
                    </div>
                
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold; color:#ff7900"><?php echo t("COST"); ?></div>
                      <div id="club_piegraph2_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="club_piegraph2_placeholder"></canvas>
                      </div>
                      <div id="club_hrbar2_bound" style="width:100%; height:50px; margin: 0 auto">
                          <canvas id="club_hrbar2_placeholder"></canvas>
                      </div>
                      <br>
                    </div>
                    
                    <div class="box3">
                      <div style="padding:15px; text-align:left; margin: 0 auto; max-width:270px">
                        <table class="keytable">
                          <tr>
                            <td><div class="key" style="background-color:#29abe2"></div></td>
                            <td><b><?php echo t("Hydro Power");?></b><br><span id="club_hydro_kwh"></span> kWh @ 7.0 p/kWh<br><?php echo t("Costing");?> £<span id="club_hydro_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#ffdc00"></div></td>
                            <td><b><?php echo t("Morning Price");?></b> 6am - 11am<br><span id="club_morning_kwh"></span> kWh @ 12p/kWh<br><?php echo t("Costing");?> £<span id="club_morning_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#4abd3e"></div></td>
                            <td><b><?php echo t("Midday Price");?></b> 11am - 4pm<br><span id="club_midday_kwh"></span> kWh @ 10p/kWh<br><?php echo t("Costing");?> £<span id="club_midday_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#c92760"></div></td>
                            <td><b><?php echo t("Evening Price");?></b> 4pm - 8pm<br><span id="club_evening_kwh"></span> kWh @ 14p/kWh<br><?php echo t("Costing");?> £<span id="club_evening_cost"></span></td>
                          </tr>
                          <tr>
                            <td><div class="key" style="background-color:#274e3f"></div></td>
                            <td><b><?php echo t("Overnight Price");?></b> 8pm - 6am<br><span id="club_overnight_kwh"></span> kWh @ 7.25p/kWh<br><?php echo t("Costing");?> £<span id="club_overnight_cost"></span></td>
                          </tr>
                        </table>
                      </div>
                    </div>
                    
                    <div style="clear:both"></div>

                    <div class="bg-club2" style="padding:20px">
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
                <div class="block-title bg-tips"><?php echo t("Tips"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
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
                            <?php echo t("LED lights can cut your lighting costs by up to 90%. There’s more information on our website and in the info pack on installing them in your house.")
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
                            <?php echo t("Switching off lights and appliances when not in use is a simple and effective way to use less electricity. You can make a special effort to do this during the morning and evening peaks.")
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
        
        
        <div class="page" name="devices">
            <div class="block">
                
                <div class="block-content" style="color:#ea510e">
                  
                  <!-- COPIED FROM emoncms/../device_view.php -->
                  
	                <div id="auth-check" class="hide">
	                    <i class="icon-exclamation-sign icon-white"></i> Device on ip address: <span id="auth-check-ip"></span> would like to connect 
	                    <button class="btn btn-small auth-check-btn auth-check-allow">Allow</button>
	                </div>
	
	                <div id="table"></div>
	
	                <div id="output"></div>

	                <div id="noinputs" class="alert alert-block hide">
			                <h4 class="alert-heading"><?php echo _('No inputs created'); ?></h4>
			                <p><?php echo _('Inputs are the main entry point for your monitoring device. Configure your device to post values here, you may want to follow the <a href="api">Input API helper</a> as a guide for generating your request.'); ?></p>
	                </div>
	
	                <div id="input-loader" class="ajax-loader"></div>
        
                  <!---------------------------------------------->
                  
                </div>
            </div>
        </div>    
        
        <div style="clear:both; height:85px"></div>

    </div></div>
</div>

<div class="app"><div class="app-inner">
    <div class="footer">
        <div style="float:right; font-weight:bold"><?php echo t("Contact Us");?></div>
        <div>Energy Local</div>
    </div>
</div></div>

</body>
</html>

<script language="javascript" type="text/javascript" src="js/cydynnistatus.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/pie.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/household.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/club.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/user.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/devices.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/scheduler.js?v=<?php echo $v; ?>"></script>
<script>

var path = "<?php echo $path; ?>";
var emoncmspath = window.location.protocol+"//"+window.location.hostname+"/emoncms/";

// Device 
auth_check();
setInterval(auth_check,5000);

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
  
  $("#account").hide();
  $("#logout").hide();
  $("#reports").hide();
} else {
  $("#login-block").hide();
  $(".household-block").show();
  
  $("#logout").show();
  $("#account").show();
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
    $(this).find(".triangle-pushup").toggle();
});

function show_page(page) {

    // Highlighted selected menu
    $(".navigation li > div").removeClass("active");
    $(".navigation li[name="+page+"] > div").addClass("active");
    // Show relevant page
    $(".page").hide();
    $(".page[name="+page+"]").show();

    if (page=="forecast") {
        club_pie_draw();
        club_bargraph_resize();
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
    
    club_pie_draw();
    club_bargraph_resize();
    
    household_pie_draw();
    household_bargraph_resize();
}

// Flot
var flot_font_size = 12;
var previousPoint = false;

cydynnistatus_update();

club_summary_load();
club_bargraph_load();

if (session.write) {
    household_summary_load();
    household_bargraph_load();
    device_load();
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

// ----------------------------------------------------------------------
// Tips
// ----------------------------------------------------------------------

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

$("#dashboard").click(function(){ window.location = "/?lang="+lang; });
$("#reports").click(function(){ window.location = "report?lang="+lang; });
$("#account").click(function(){ window.location = "account?lang="+lang; });

// Javascript text translation function
function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}
</script>
