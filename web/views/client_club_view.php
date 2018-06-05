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
                  <img id="club_star1" src="<?php echo $path; ?>images/star20yellow.png" style="width:45px">
                  <img id="club_star2" src="<?php echo $path; ?>images/star20yellow.png" style="width:45px">
                  <img id="club_star3" src="<?php echo $path; ?>images/star20yellow.png" style="width:45px">
                  <img id="club_star4" src="<?php echo $path; ?>images/star20yellow.png" style="width:45px">
                  <img id="club_star5" src="<?php echo $path; ?>images/star20yellow.png" style="width:45px">
                
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
                        <div style="font-size:36px" class="club_generation_value" >£00.00</div>
                    </div>
                </div>
                <br>
                
                <div style="background-color:#ffb401; color:#fff; padding:20px">
                    <div class="bound"><?php echo t("in the local area by using your local resource ".$club_settings["generator"]." power!"); ?></div>
                </div>
                  
                </div>
            </div>
            <div class="block">
                <div class="block-title bg-club2"><?php echo t("Club breakdown"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                
                    <div class="bg-club2">
                      <div class="bound"><?php echo t("How much of the electricity the club used, came from the ".$club_settings["generator"]."."); ?></div>
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
                      <div id="generation_droplet_bound" style="margin: 0 auto">
                        <canvas id="generation_droplet_placeholder"></canvas>
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
                          <?php foreach ($tariffs[$club] as $key=>$tariff) { ?>
                          <tr>
                            <td><div class="key" style="background-color:<?php echo $tariff['color']; ?>"></div></td>
                            <td><b><?php echo t($tariff['name']." Price");?> </b><br><span id="club_<?php echo $key; ?>_kwh"></span> kWh @ <?php echo $tariff['cost']*100; ?> p/kWh<br><?php echo t("Costing");?> £<span id="club_<?php echo $key; ?>_cost"></span></td>
                          </tr>
                          <?php } ?>
                        </table>
                      </div>
                    </div>
                    
                    <div style="clear:both"></div>

                    <div class="bg-club2" style="padding:20px">
                      <div class="bound"><?php echo t("The bigger the percentage of ".$club_settings["generator"].", the more money stays in the local club."); ?></div>
                    </div>
                    
                </div>
            </div>