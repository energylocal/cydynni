<div class="page" page="hydro">

  <div class="pagesection" style="color:rgb(39,201,63)">
    <div style="height:10px; background-color:rgb(39,201,63)"></div>
    <div class="title"><?php echo t("OK to use?"); ?></div>
    <div class="summary_bound"><div id="cydynni_summary" class="panel-summary"></div></div>
    <div class="togglelang">CY</div>
    <div style="clear:both"></div>
  </div>
  <div id="panel-status" class="panel" style="#fff">
    <div id="panel-inner-status" class="panel-inner">
      <p id="status-pre" style="margin-top:5px; margin-bottom:5px;"><?php echo t("If possible");?></p>
      <img id="status-img" src="images/waiting-icon-small2.jpg" style="width:75px; padding:10px">
      <div id="status-title" class="status"><?php echo t("WAIT");?></div>
      <p id="status-until" style="margin-top:5px; margin-bottom:5px;"></p>
      <p id="status-next" style="margin-top:5px; margin-bottom:5px;"></p>
    </div>
  </div>

  <!-- TARIFF TAB ------------------------------------------------------->

  <div class="pagesection" style="color:rgb(33,145,110)">
    <div style="height:10px; background-color:rgb(33,145,110)"></div>
    <div class="title"><?php echo t("Electricity Prices");?></div>
    <div class="summary_bound"><div id="tariff_summary" class="panel-summary"></div></div>
    <div style="clear:both"></div>
  </div>
  <div class="panel">
    <div class="panel-inner">
      <div class="tariff-block">
        <img class="tariff-img" tariff="morning" src="images/now2.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">6AM - 11AM</div>
        <div class="tariff-desc"><?php echo t("MORNING PRICE");?> - 12 <?php echo t("PENCE PER UNIT");?></div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="midday" src="images/now2.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">11AM - 4PM</div>
        <div class="tariff-desc"><?php echo t("MIDDAY PRICE");?> - 10 <?php echo t("PENCE PER UNIT");?></div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="evening" src="images/now2.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">4PM - 8PM</div>
        <div class="tariff-desc"><?php echo t("EVENING PRICE");?> - 14 <?php echo t("PENCE PER UNIT");?></div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="overnight" src="images/now2.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">8PM - 6AM</div>
        <div class="tariff-desc"><?php echo t("OVERNIGHT PRICE");?> - 7.25 <?php echo t("PENCE PER UNIT");?></div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="hydro" src="images/now2.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">HYDRO</div>
        <div class="tariff-desc"><?php echo t("HYDRO PRICE");?> - 7 <?php echo t("PENCE PER UNIT");?></div>
      </div>
    </div>
  </div>

  <!-- HYDRO TAB ------------------------------------------------------->

  <div class="pagesection" name="hydrohistory" style="color:rgb(39,78,63)">
    <div style="height:10px; background-color:rgb(39,78,63)"></div>
    <div class="title">Hydro</div>
    <div class="summary_bound"><div id="hydro_summary" class="panel-summary"></div></div>
    
    <div class="visnav-block" style="display:none">
      <div class="visnav-hydro hydro-left"><</div>
      <div class="visnav-hydro hydro-right">></div>
      <div class="visnav-hydro month"><?php echo t("MONTH");?></div>
      <div class="visnav-hydro week"><?php echo t("WEEK");?></div>
      <div class="visnav-hydro day"><?php echo t("DAY");?></div>
    </div>
    
    <div style="clear:both"></div>
  </div>
  <div class="panel">
    <div class="panel-inner">
    
      <div style="height:80px; overflow:hidden">
        <div class="status"><span id="hydrostatus"></span></div>
        <!-- (<span id="kWhHH"></span> kWh/<?php echo t("half-hour");?>)-->
        <?php echo t("Generating");?> <b><span id="power"></span> kW</b> <span id="hydro-graph-date-1"></span>, <?php echo t("forecasting"); ?> <span id="power-forecast"></span> kW <?php echo t("now"); ?></b>
      </div>
    
      <div style="text-align:center">
        <div style="margin-bottom:5px" id="hydro-graph-date"><?php echo t("Last 24 hours");?>:</div>
        <!--
        <div id="hydro_bargraph_placeholder_bound" style="height:100%">
          <canvas id="hydro_bargraph_placeholder"></canvas>
        </div>-->
        <div id="hydro_bargraph_placeholder_bound" style="width:100%; height:500px;">
          <div id="hydro_bargraph_placeholder" style="height:500px"></div>
        </div>
      </div>
    </div>
  </div>

</div>
