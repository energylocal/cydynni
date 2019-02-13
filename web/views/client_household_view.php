            
            <div id="login-block" class="block">
                <div class="block-title bg-household"><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                    
                    <div class="bg-household" style="padding:20px">
                    
                        <div style="font-weight:bold; font-size:32px"><?php echo t("Log in"); ?></div>
                        <?php echo t("Please login to view account"); ?><br><br>
                
                        <form id="loginform">
                        <input id="email" type="text" placeholder="Username or email..." name="username"><br><br>
                        <input id="password" type="password" placeholder="Password..." name="password"><br><br>
                        <button id="login" class="btn"><?php echo t("Login");?></button><br><br>
                        </form>
                        
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
                <img id="household_star1" src="<?php echo $path; ?>images/starred.png" style="width:45px">
                <img id="household_star2" src="<?php echo $path; ?>images/starred.png" style="width:45px">
                <img id="household_star3" src="<?php echo $path; ?>images/star20red.png" style="width:45px">
                <img id="household_star4" src="<?php echo $path; ?>images/star20red.png" style="width:45px">
                <img id="household_star5" src="<?php echo $path; ?>images/star20red.png" style="width:45px">
              
                <p class="household_status"></p>
                
                <div class="bg-household" style="height:50px">
                  <div style="padding:5px">
                    <p><?php echo t("You used"); ?> <span class="household_totalkwh"></span> kWh. <?php echo t("It cost"); ?>:</p>
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
                        <div style="font-size:36px" class="household_totalcost">£0.00</div>
                    </div>
                </div>
                <p><?php echo t("Compared with 12p/kWh reference price, you saved"); ?> <span class="household_costsaving"></span></p>
                
                <br>
              </div>
            </div>

            <div id="realtime-power" class="block" style="display:none">
                <div class="block-title bg-household2"><?php echo t("Realtime Power Data"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                
                <div class="block-content">

                    <div class="bg-household2" style="border-top: 1px solid #e94648; border-bottom: 1px solid #e94648; padding:20px">
                        <span id="power_value" style="font-size:32px"></span><span style="font-size:22px">W</span>
                    </div>
                                    
                    <div class="bg-household2">
                        <div class="visnav-block-household">
                        <div class="visnav-household household-power-left"><</div><div class="visnav-household household-power-right">></div><div class="visnav-household household-power-day"><?php echo t("DAY");?></div><div class="visnav-household household-power-week"><?php echo t("WEEK");?></div><div class="visnav-household household-power-month"><?php echo t("MONTH");?></div>
                        </div>
                        <div style="clear:both"></div>
                    </div>
                
                    <div style="padding:10px">
                        <div id="household_powergraph_bound" style="width:100%; height:405px;">
                            <div id="household_powergraph_placeholder" style="height:405px"></div>
                        </div>
                    </div>
                    
                    <div style="padding:10px; background-color:#eee; color: #666; font-size:14px">
                        <?php echo t("Electricity use in window");?>: <b><span id="kwh_in_window">2.1</span> kWh</b>
                    </div>
                </div>
            </div>
                        
            <div class="block household-block">
                <div class="block-title bg-household2"><?php echo t("Your usage over time"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                
                <div class="block-content">
                
                    <div class="bg-household2">
                        <div class="visnav-block-household">
                          <div class="visnav-household household-year"><?php echo t("YEAR");?></div><div class="visnav-household household-month"><?php echo t("MONTH");?></div><div class="visnav-household household-week"><?php echo t("WEEK");?></div><div class="visnav-household household-left"><</div><div class="visnav-household household-right">></div><div class="visnav-household household-daily"><?php echo t("DAILY");?></div>
                        </div>
                        <div style="clear:both"></div>
                    </div>
                
                    <div style="padding:10px">
                        <div id="household_bargraph_bound" style="width:100%; height:405px;">
                            <div id="household_bargraph_placeholder" style="height:405px"></div>
                        </div>
                    </div>
                    
                    <p style="font-size:12px" id="household-daily-note"><?php echo t("Click on a day to see half hourly consumption"); ?></p><br>
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
                          <?php foreach ($tariffs[$club] as $key=>$tariff) { ?>
                          <tr>
                            <td><div class="key" style="background-color:<?php echo $tariff['color']; ?>"></div></td>
                            <td><b><?php echo t($tariff['name']." Price");?> </b><br><span id="household_<?php echo $key; ?>_kwh"></span> kWh @ <?php echo $tariff['cost']*100; ?> p/kWh<br><?php echo t("Costing");?> £<span id="household_<?php echo $key; ?>_cost"></span></td>
                          </tr>
                          <?php } ?>
                        </table>
                      </div>
                    </div>
                    
                    <div style="clear:both"></div>

                    <div class="bg-household3" style="padding:20px">
                      <div class="bound"><?php echo t("Head to the tips section or get in touch with your Energy Local club to see how you can shift more of your use to cheaper times."); ?></div>
                    </div>
                    
                </div>
            </div>
