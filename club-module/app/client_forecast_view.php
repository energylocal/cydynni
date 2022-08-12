<?php global $session; ?>
            <div class="block">
                <div class="block-title" style="background-color:#39aa1a"><?php echo t("Good time to use?"); ?><div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div></div>
                <div class="block-content">
                  <div style="background-color:#39aa1a; color:#fff">
                  
                    <div id="status-pre" style="padding:10px;"></div>
                    

                
                    <div class="trafficlight-body">
                    <div class="tl3"><div id="tl-red" class="trafficlight tl-red-off"></div></div>
                    <div class="tl3"><div id="tl-amber" class="trafficlight tl-amber-off"></div></div>
                    <div class="tl3"><div id="tl-green" class="trafficlight tl-green-off"></div></div>
                    </div>
                    <!--<img id="status-img" src="<?php echo $app_path; ?>images/new-tick.png"/>-->
                    <!--<div id="status-title" style="font-size:32px; font-weight:bold; height:32px"></div>-->
                    <!--<div id="status-until" style="height:16px; padding:10px;"></div>-->
                    <div id="gen-prc" style="height:16px; padding:10px;"></div><br>
                  </div>
                </div>
            </div>
            <?php /* if ($session['admin']) { ?>
            <div id="electricity_forecast" class="block">
                <div class="block-title" style="background-color:#088400"><?php echo t("Forecast"); ?></div>

                <div class="block-content">
                  <div style="background-color:#fff; color:#000">
                                  
                    <div id="club_forecast_bound" style="width:100%; height:405px;">
                      <div id="club_forecast_placeholder" style="height:405px"></div>
                    </div>
                  </div>
                </div>
            </div>
            <?php } */ ?>
            <div id="local_electricity_forecast" class="block">
                <div class="block-title" style="background-color:#088400"><?php echo t("Local generator output"); ?>
                
                <div class="triangle-dropdown hide"></div><div class="triangle-pushup show"></div>
                <div class="visnav-block">
                  <!--<div class="visnav-club club-zoomin">+</div>-->
                  <!--<div class="visnav-club club-zoomout">-</div>-->
                  <div class="visnav-club club-left"><</div><div class="visnav-club club-right">></div>
                  <select class="period-select"></select>
                </div>
                
                
                </div>
                <div class="block-content">

                  <div style="background-color:#088400; color:#fff">
                    <div id="generation-status" style="font-size:32px; font-weight:bold"><?php echo t("---"); ?></div>
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
                      <?php foreach ($tariffs_table as $t) : ?>
                      <div class="legend-label-box" style="background-color:<?=$t->color?>"></div>
                      <span class="legend-label"><?=t(ucfirst($t->name))?></span>
                      <?php endforeach; ?>
                      <div class="legend-label-box" style="background-color:<?php echo $club_settings["generator_color"]; ?>"></div>
                      <span class="legend-label" ><?php echo t(ucfirst($club_settings["generator"])); ?></span>
                      <span id="club-price-legend" class="hide">
                        <div class="legend-label-box" style="background-color:#fb1a80"></div>
                        <span class="legend-label" ><?php echo t("Average price");?></span>
                      </span>
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
                        <label class="custom-control-label m-0" for="showClubPriceInput"><strong><?php echo t("Show average club price"); ?></strong></label>
                    </div>

                  </div>
                </div>
            </div>
            
            <?php if ($club_settings["club_id"]==1) { ?>
            <!--
            <div class="block">
                <div class="block-title" style="background-color:#e6602b; text-align:center;">
                Please note: <span style="font-weight:normal"><?php echo t("The Bethesda club match tariff and time of use tariff is not currently live. Billing is based on a flat tariff"); ?></span>
                </div>
            </div>
            -->
            <?php } ?>
            <div class="block">
                <div class="block-title" style="background-color:#005b0b"><?php echo t("Your prices for power"); ?><div class="triangle-dropdown show"></div><div class="triangle-pushup hide"></div></div>
                <div class="block-content" style="padding: .6rem">
                    <table class="tariff table table-sm m-0">
                        <colgroup>
                            <col>
                            <col class="bg-info">
                            <col class="bg-danger">
                        </colgroup>
                        <thead>
                            <tr>
                            <th></th>
                            <th scope="col" style="background-color:<?=$club_settings["generator_color"]?>"><?=t(ucfirst($club_settings["generator"])); ?></th>
                            <th scope="col"><?php echo t("Extra electricity") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tariffs_table as $t) : ?>
                            <tr<?=$t->rowClass?>>
                                <th scope="row">
                                    <span class="d-sm-inline d-lg-none" style="color:<?=$t->color?>"><?=t(ucfirst($t->name))?></span>
                                    <span class="d-none d-md-inline d-lg-inline" style="color:<?=$t->color?>"> <?=t(ucfirst($t->name)." Price")?></span> 
                                    <br class="d-sm-none">
                                    <span class="font-weight-light text-smaller-sm"><?=$t->start?> - <?=$t->end?></span>
                                </th>
                                <td style="background-color:<?=$club_settings["generator_color"]?>"><?=number_format($t->generator*1.05,2).t('p')?></td>
                                <td style="background-color:#f0f0f0; color:<?=$t->color?>"><?=number_format($t->import*1.05,2).t('p')?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="padding:10px; color:#888; font-size:14px"><?=t('Unit prices include VAT');?></div>
                </div>
            </div>
