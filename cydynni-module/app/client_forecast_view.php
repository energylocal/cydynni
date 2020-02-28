
            <div class="block">
                <div class="block-title" style="background-color:#39aa1a"><?php echo t("Good time to use?"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                  <div style="background-color:#39aa1a; color:#fff">
                  
                    <div id="status-pre" style="padding:10px;"></div>
                    <img id="status-img" src="<?php echo $app_path; ?>images/new-tick.png"/>
                    <div id="status-title" style="font-size:32px; font-weight:bold; height:32px"></div>
                    <div id="status-until" style="height:16px; padding:10px;"></div><br>
                    
                  </div>
                </div>
            </div>
        
            <div id="local_electricity_forecast" class="block">
                <div class="block-title" style="background-color:#088400"><?php echo t("Local Electricity Forecast"); ?>
                
                <div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div>
                <div class="visnav-block">
                  <!--<div class="visnav-club club-zoomin">+</div>-->
                  <!--<div class="visnav-club club-zoomout">-</div>-->
                  <div class="visnav-club club-left"><</div><div class="visnav-club club-right">></div><div class="visnav-club club-year"><?php echo t("YEAR");?></div><div class="visnav-club club-month"><?php echo t("MONTH");?></div><div class="visnav-club club-week"><?php echo t("WEEK");?></div><div class="visnav-club club-day" style="border-right: 1px solid rgba(255,255,255,0.2);"><?php echo t("DAY");?></div>
                </div>
                
                
                </div>
                <div class="block-content">

                  <div style="background-color:#088400; color:#fff">
                    <div id="generation-status" style="font-size:32px; font-weight:bold"><?php echo t("HIGH"); ?></div>
                    <?php echo t("Generating"); ?> <span id="generation-power">0</span> kW <?php echo t("now"); ?>
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
                      <span class="legend-label"><?php echo t("Day");?></span>
                      <!-- <div class="legend-label-box" style="background-color:#4dac34"></div> -->
                      <!--<span class="legend-label"><?php echo t("Midday");?></span>-->
                      <div class="legend-label-box" style="background-color:#e6602b"></div>
                      <span class="legend-label"><?php echo t("Evening");?></span>
                      <div class="legend-label-box" style="background-color:#014c2d"></div>
                      <span class="legend-label"><?php echo t("Night");?></span>
                      <div class="legend-label-box" style="background-color:#29aae3"></div>
                      <span class="legend-label" ><?php echo t(ucfirst($club_settings["generator"])); ?></span>
                      <div class="legend-label-box" style="background-color:#fb1a80"></div>
                      <span class="legend-label" ><?php echo t("Price");?></span>
                    </div>
                    
                    <div id="club_bargraph_bound" style="width:100%; height:405px;">
                      <div id="club_bargraph_placeholder" style="height:405px"></div>
                    </div>
                  </div>
                  
                  <div style="background-color:#088400; color:#fff; padding:20px">
                  <div id="status-summary"><?php echo t(ucfirst($club_settings["generator"])." output is currently exceeding club consumption"); ?></div>
                  <!--<span style="font-size:14px; color:rgba(255,255,255,0.8)"><?php echo t("Light and dark grey portion indicates estimated ".$club_settings["generator"]." output and club consumption up to the present time"); ?></span>-->
                    
                  <!-- show/hide club price series on chart -->
                    <div id="showClubPrice" class="custom-control custom-checkbox d-flex justify-content-center pt-2" title="<?php echo t("Overlay the average club price offset by the available hydro") ?>">
                        <input type="checkbox" class="custom-control-input m-0 mr-2" id="showClubPriceInput">
                        <label class="custom-control-label m-0" for="showClubPriceInput"><strong><?php echo t("Show club price"); ?></strong></label>
                    </div>

                  </div>
                </div>
            </div>
            <!--
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
                          <div id="tariff-now-price" style="font-size:36px">11.5p</div>
                          <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                      </div>
                  </div>
                  <br>
                </div>
            </div>
            -->
<!--
            <div class="block">
                <div class="block-title" style="background-color:#005b0b"><?php echo t("Your prices for power"); ?><div class="triangle-dropdown show"></div><div class="triangle-pushup hide"></div></div>
                <div class="block-content hide">
                  <div style="background-color:#29aae3; padding:10px; color:#fff; margin:10px">
                      <div style="font-size:18px; color:#fff; font-weight:bold; padding:5px">Hydro</div>
                      <div style="font-size:18px; color:#d8f3ff; padding:5px">Night 8pm - 7am: 5.8 p/kWh</div>
                      <div style="font-size:18px; color:#d8f3ff; padding:5px">Day 7am - 4pm: 10.4 p/kWh</div>
                      <div style="font-size:18px; color:#d8f3ff; padding:5px">Evening 4pm - 8pm: 12.7 p/kWh</div>
                  </div>

                  <div style="background-color:#e6602b; padding:10px; color:#fff; margin:10px">
                      <div style="font-size:18px; color:#fff; font-weight:bold; padding:5px">Import</div>
                      <div style="font-size:18px; color:#ffe1d5; padding:5px">Night 8pm - 7am: 10.5 p/kWh</div>
                      <div style="font-size:18px; color:#ffe1d5; padding:5px">Day 7am - 4pm: 18.9 p/kWh</div>
                      <div style="font-size:18px; color:#ffe1d5; padding:5px">Evening 4pm - 8pm: 23.1 p/kWh</div>
                  </div>
                  <div style="height:1px; clear:both"></div>
                  
                </div>
            </div>
-->
            <div class="block">
                <table class="tariff table table-sm my-3">
                    <colgroup>
                        <col>
                        <col class="bg-info">
                        <col class="bg-danger">
                    </colgroup>
                    <thead>
                        <tr>
                        <th></th>
                        <th scope="col">Hydro</th>
                        <th scope="col"><?php echo t("Import") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tariffs_table as $t) : ?>
                        <tr<?=$t->rowClass?>>
                            <th scope="row">
                                <span class="<?=$t->css?> d-sm-inline d-lg-none"><?=$t->short?></span>
                                <span class="<?=$t->css?> d-none d-md-inline d-lg-inline"> <?=$t->name?></span> 
                                <br class="d-sm-none">
                                <span class="font-weight-light text-smaller-sm"><?=$t->start?> - <?=$t->end?></span>
                            </th>
                            <td><?=$t->sources->hydro?> <span class="font-weight-light d-none d-sm-inline"><?=$t->diff?></span></td>
                            <td><?=$t->sources->import?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!--
            <div class="block">
                <div class="block-title" style="background-color:#005b0b"><?php echo t("Your prices for power"); ?><div class="triangle-dropdown show"></div><div class="triangle-pushup hide"></div></div>
                <div class="block-content hide">

                  <br>

                  <div id="generation-tariff-box" class="box5" style="color:#29aae3">
                      <div style="font-size:26px; font-weight:bold"><?php echo t(strtoupper($club_settings["generator"])."<br>PRICE"); ?></div>
                      <div style="font-size:14px; padding:5px"><?php echo t("Your local electricity"); ?></div>

                      <div class="circle bg-generation">
                          <div class="circle-inner">
                              <div style="font-size:36px"><?php echo $tariffs[$club]["generation"]["cost"]*100;?>p</div>
                              <div style="font-size:22px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:22px; font-weight:bold"></div>
                  </div>
                    
                  <div style="margin-bottom:10px; color:#444;"><?php echo t("Prices for extra electricity (in the event your local electricity is not covering all of your needs)"); ?></div>  
                    
                  <div id="morning-tariff-box" class="box5" style="color:#ffb401">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("MORNING<br>PRICE"); ?></div>

                      <div class="circle-small bg-morning">
                          <div class="circle-small-inner">
                              <div style="font-size:32px"><?php echo $tariffs[$club]["morning"]["cost"]*100;?>p</div>
                              <div style="font-size:18px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:22px; font-weight:bold">6am - 11am</div>
                  </div>
                  
                  <div id="midday-tariff-box" class="box5" style="color:#4dac34">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("MIDDAY<br>PRICE"); ?></div>

                      <div class="circle-small bg-midday">
                          <div class="circle-small-inner">
                              <div style="font-size:32px"><?php echo $tariffs[$club]["midday"]["cost"]*100;?>p</div>
                              <div style="font-size:18px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>

                      <div style="font-size:24px; font-weight:bold">11am - 4pm</div>
                  </div>
                  
                  <div id="evening-tariff-box" class="box5" style="color:#e6602b">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("EVENING<br>PRICE");?></div>
                      <div class="circle-small bg-evening">
                          <div class="circle-small-inner">
                              <div style="font-size:32px"><?php echo $tariffs[$club]["evening"]["cost"]*100;?>p</div>
                              <div style="font-size:18px"><?php echo t("per unit"); ?></div>
                          </div>
                      </div>
                      <div style="font-size:22px; font-weight:bold">4pm - 8pm</div>
                  </div>
                  
                  <div id="overnight-tariff-box" class="box5" style="color:#014c2d">
                      <div style="font-size:22px; font-weight:bold"><?php echo t("OVERNIGHT<br>PRICE"); ?></div>
                      
                      <div class="circle-small bg-overnight">
                          <div class="circle-small-inner">
                              <div style="font-size:32px"><?php echo $tariffs[$club]["overnight"]["cost"]*100;?>p</div>
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
            -->
