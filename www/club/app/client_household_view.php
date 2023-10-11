            <div id="login-block" class="block">
                <div class="block-title bg-household"><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                    
                    <div class="bg-household" style="padding:20px">
                    
                        <div style="font-weight:bold; font-size:32px"><?php echo t("Log in"); ?></div>
                        <?php echo t("Please login to view account"); ?><br><br>
                
                        <form id="loginform">
                        <input id="username" type="text" placeholder="Username or email..." name="username"><br><br>
                        <input id="password" type="password" placeholder="Password..." name="password"><br>
                        <?php echo t("Remember me"); ?>: <input id="rememberme" type="checkbox"><br><br>
                        <button id="login" class="btn"><?php echo t("Login");?></button><br><br>
                        </form>
                        
                        <div id="passwordreset-start" style="display:inline-block; cursor:pointer;"><?php echo t("Forgotten your password?");?></div>
                        <br><br>
                        <div id="alert"></div>
                    </div>
                </div>
            </div>

            <div id="missing-data-block" class="block hide">
                <div class="block-title bg-household"><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                    
                    <div class="bg-household" style="padding:20px">
                        <h2><?php echo t("Missing household data"); ?></h2>
                        <p><?php echo t("The household consumption data is currently unavailable.") ?></p>
                        <p class="lead"><?php echo t("It will appear here as it becomes available."); ?></p>
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

            <div id="realtime-power" class="block" style="display:none">
                <div class="block-title bg-household"><?php echo t("Realtime Power Data"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                
                <div class="block-content">

                    <div class="bg-household" style="border-top: 1px solid rgba(255,255,255,0.2); border-bottom: 1px solid rgba(255,255,255,0.2); padding:20px">
                        <span id="power_value" style="font-size:32px"></span><span style="font-size:22px">W</span>
                    </div>
                                    
                    <div class="bg-household">
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

            <div id="your-score" class="block household-block">
              <div class="block-title bg-household"><?php echo t("Your Score and Savings"); ?><div class="triangle-dropdown hide" style="margin-left:10px"></div><div class="triangle-pushup show" style="margin-left:10px"></div>
              <div class="visnav-block"><select class="period-select"></select></div>
              </div>
              
              <div class="block-content" style="color:#c20000">
              
                <div class="bg-household">
                  <b><span class="household_date"></span></b>
                  <div style="font-size:22px; font-weight:bold; padding-top:5px"><span class="household_score">100</span>/100</div>
                  <!--<div style="font-size:22px; font-weight:bold; padding-top:5px"><span class="club_score"></span>/100</div>-->
                </div>
                
                <div class="no-padding">
                  <div class="triangle-wrapper">
                    <div class="triangle-down">
                      <div class="triangle-down-content triangle-household-bg"></div>
                    </div>
                  </div>
                </div>
                
                <br>
                <img id="household_star1" src="<?php echo $app_path; ?>images/star20red.png" style="width:45px">
                <img id="household_star2" src="<?php echo $app_path; ?>images/star20red.png" style="width:45px">
                <img id="household_star3" src="<?php echo $app_path; ?>images/star20red.png" style="width:45px">
                <img id="household_star4" src="<?php echo $app_path; ?>images/star20red.png" style="width:45px">
                <img id="household_star5" src="<?php echo $app_path; ?>images/star20red.png" style="width:45px">
                <br><br>
                <p class="household_score_description"></p>
                <!--<br><br><div class="household_status" style="height:40px"></div><br>-->
                <!--<br>
                <p><?php echo t("In total you used"); ?> <span class="household_totalkwh"></span> kWh, <?php echo t("costing"); ?>:</p>
 
                
                <br>
                <div class="circle bg-household">
                    <div class="circle-inner" style="padding-top:52px">
                        <div style="font-size:36px" class="household_totalcost">£0.00</div>
                    </div>
                </div>
                
                <br>
                <p><?php echo t("Compared with <a href='https://powercompare.co.uk/electricity-prices/' style='color:#c20000'>15.44p/kWh</a> reference price, you saved"); ?> <span class="household_costsaving"></span></p>
                <br>
              </div>
            </div>

            <div id="your-usage-price" class="block household-block">
                <div class="block-title bg-household2"><?php echo t("Your usage by price"); ?>: <span class="household_date"></span><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                -->
                    <!--
                    <div class="bg-household3">
                      <div class="bound" style="padding-bottom:20px"><?php echo t("Your electricity is provided on five different price bands. Here's how much of each you used on"); ?> <span class="household_date"></span></div>
                    </div>-->
                    
                    <br>
                    
                    <div style="padding:15px;">
                    
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold;"><?php echo t("ELECTRICITY"); ?></div>
                      <div style="font-size:22px"><span class="household_totalkwh"></span> kWh</div>
                      <div class="hrdiv"></div>
                      <div id="household_piegraph1_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="household_piegraph1_placeholder"></canvas>
                      </div>
                      <div id="household_hrbar1_bound" style="width:100%; height:50px; margin: 0 auto">
                          <canvas id="household_hrbar1_placeholder"></canvas>
                      </div>
                      <br>
                    </div>
                
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold;"><?php echo t("COST"); ?></div>
                      <div style="font-size:22px" class="household_elec_cost"></div>
                      <div class="hrdiv"></div>
                      <div id="household_piegraph2_bound" style="width:100%; height:300px; margin: 0 auto">
                          <canvas id="household_piegraph2_placeholder"></canvas>
                      </div>
                      <div id="household_hrbar2_bound" style="width:100%; height:50px; margin: 0 auto">
                          <canvas id="household_hrbar2_placeholder"></canvas>
                      </div>
                      <br>
                    </div>
                    
                    <div class="box3">
                      <div style="font-size:26px; font-weight:bold;" class="household_saving_title"><?php echo t("SAVING"); ?></div>
                      <div style="font-size:22px" class="household_saving"></div>
                      <div class="hrdiv"></div>
                      <div style="padding:15px; text-align:left; margin: 20px auto; max-width:270px; color:#333">
                        <table id="household_pie_legend" class="keytable"></table>
                      </div>
                    </div>
                    </div>
                    <div style="clear:both"></div>
                    
                    <div style="text-align:left; color:#333; font-size:14px; padding:0px 15px 15px 15px;">
                      <table style="width:100%">
                      <tr>
                        <td style="background-color:#f0f0f0; border:2px #fff solid; padding:10px"><?php echo t("Electricity charge");?> (<span class="household_totalkwh"></span> kWh)<br><?php echo t("Standing charge");?> (<span class="household_days"></span> <?php echo t("days at");?> <span class="tariff_standing_charge"></span>p/<?php echo t("day");?>)<br><?php echo t("VAT");?> @ 5%</td>
                        <td style="background-color:#f0f0f0; border:2px #fff solid; padding:10px"><span class="household_elec_cost"></span><br><span class="household_standing_charge"></span><br><span class="household_vat"></span></td>
                      </tr>
                      <tr>
                        <td style="background-color:#f0f0f0; border:2px #fff solid; padding:10px"><b><?php echo t("Total cost of electricity supply");?></b></td>
                        <td style="background-color:#f0f0f0; border:2px #fff solid; padding:10px"><b><span class="household_total_cost"></span></b></td>
                      </tr>
                      </table>
                    </div>  
                </div>
            </div>
            
            <?php if ($club_settings["club_id"]==1) { ?>
            <!--<div class="block">
                <div class="block-title bg-household2" style="text-align:center;">
                Please note: <span style="font-weight:normal"><?php echo t("The Bethesda club match tariff and time of use tariff is not currently live. Billing is based on a flat tariff"); ?></span>
                </div>
            </div>-->
            <?php } ?>
                        
            <div id="your-usage" class="block household-block">
                <div class="block-title bg-household3"><?php echo t("Your usage over time"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div>
                   <div class="visnav-block"><!--<select id="household_daily_period_select" class="period-select"></select>--><div class="visnav-household household-daily"><?php echo t("DAILY");?></div></div>
                </div>
                
                <div class="block-content">
                    <div style="padding:10px">
                        <div id="household_bargraph_bound" style="width:100%; height:405px;">
                            <div id="household_bargraph_placeholder" style="height:405px"></div>
                        </div>
                    </div>
                    
                    <!--<p style="font-size:12px" id="household-daily-note"><?php echo t("Click on a day to see half hourly consumption"); ?></p><br>-->
                    
                    <div style="padding:10px; background-color:#eee; color: #666; font-size:14px">
                        <?php echo t("Electricity use in window");?>: <b><span id="household_use_history_stats">---</span></b>
                    </div>
                </div>
            </div>
            

