
            <div class="block">
                <div class="block-title" style="background-color:#39aa1a"><?php echo t("Good time to use?"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                  <div style="background-color:#39aa1a; color:#fff">
                  
                    <div id="status-pre" style="padding:10px;"></div>
                    <img id="status-img" src="<?php echo $app_path; ?>images/new-tick.png"/>
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
                  <div class="visnav-club club-day"><?php echo t("DAY");?></div>
                </div>
                
                
                </div>
                <div class="block-content">

                  <div style="background-color:#088400; color:#fff">
                    <div id="generation-status" style="font-size:32px; font-weight:bold"><?php echo t("HIGH"); ?></div>
                    <?php echo t("Forecasting"); ?> <span id="generation-power">0</span> kW <?php echo t("now"); ?>
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
                      <span class="legend-label" ><?php echo t(ucfirst($club_settings["generator"])); ?></span>
                    </div>
                    
                    <div id="club_bargraph_bound" style="width:100%; height:405px;">
                      <div id="club_bargraph_placeholder" style="height:405px"></div>
                    </div>
                  </div>
                  
                  <div style="background-color:#088400; color:#fff; padding:20px">
                  <div id="status-summary"><?php echo t(ucfirst($club_settings["generator"])." output is currently exceeding club consumption"); ?></div>
                  <span style="font-size:14px; color:rgba(255,255,255,0.8)"><?php echo t("Light and dark grey portion indicates estimated ".$club_settings["generator"]." output and club consumption up to the present time"); ?></span>
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
                  <div id="tariff-now-title" style="font-size:26px; font-weight:bold; color:#29aae3"><?php echo t(strtoupper($club_settings["generator"])."<br>PRICE"); ?></div>
                  <div id="tariff-now-circle" class="circle bg-generation">
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

                  <div id="generation-tariff-box" class="box5" style="color:#29aae3">
                      <div style="font-size:26px; font-weight:bold"><?php echo t(strtoupper($club_settings["generator"])."<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Your local electricity"); ?></div>

                      <div class="circle bg-generation">
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
