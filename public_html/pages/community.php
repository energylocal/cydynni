<div class="page" page="community" style="display:none;">
  <!-- STATUS TAB ------------------------------------------------------->
  <div class="pagesection" style="color:rgb(234,200,0)">
    <div style="height:10px; background-color:rgb(235,200,0)"></div>
    <div class="title"><?php echo t("Status"); ?></div>
    <div class="summary_bound"><div id="community_status_summary" class="panel-summary"></div></div>
    <div class="togglelang">CY</div>
  </div>
  <div class="panel" style="color:rgb(235,200,0)">
    <div class="panel-inner">
      <p><span id="community_score_text"><?php echo t("Over the last week we scored"); ?></span>: <b><span id="community_score"></span></b>/100</p>
      <img id="community_star1" src="images/star20yellow.png" style="width:45px">
      <img id="community_star2" src="images/star20yellow.png" style="width:45px">
      <img id="community_star3" src="images/star20yellow.png" style="width:45px">
      <img id="community_star4" src="images/star20yellow.png" style="width:45px">
      <img id="community_star5" src="images/star20yellow.png" style="width:45px">
      <p id="community_statusmsg"></p>
    </div>
  </div>

  <!-- SAVING TAB ------------------------------------------------------->
  <div class="pagesection" style="color:rgb(255,117,0);">
    <div style="height:10px; background-color:rgb(255,117,0)"></div>
    <div class="title"><?php echo t("Value"); ?></div>
    <div class="summary_bound"><div id="community_value_summary" class="panel-summary"></div></div>
  </div>
  <div class="panel" style="color:rgb(255,117,0);">
    <div class="panel-inner">
      <p><?php echo t("Value of hydro power retained in the community"); ?> <b>£<span class="community_hydro_value"></span></b></p>
      <!--<p>We have saved <b>£<span class="community_costsaving"></span></b> compared to standard flat rate price</p>-->
    </div>
  </div>

  <!-- BREAKDOWN TAB ------------------------------------------------------->
  <div class="pagesection" name="communityhistory" style="color:rgb(142,77,0);">
    <div style="height:10px; background-color:rgb(142,77,0)"></div>
    <div id="view-community-bargraph" style="float:right; margin:10px; display:none; padding-top:3px"><img src="images/bargraphiconbrown.png" style="width:24px" /></div>
    <div id="view-community-piechart" style="float:right; margin:10px; padding-top:3px"><img src="images/piechartbrown.png" style="width:24px" /></div>
    <div class="title"><?php echo t("Breakdown");?></div>
    
    <div class="visnav-community-block" style="display:none">
      <div class="visnav-community community-left"><</div>
      <div class="visnav-community community-right">></div>
      <div class="visnav-community community-year"><?php echo t("Y");?></div>
      <div class="visnav-community community-month"><?php echo t("M");?></div>
      <div class="visnav-community community-week"><?php echo t("W");?></div>
      <!--<div class="visnav-community community-day"><?php echo t("D");?></div>-->
    </div>
  </div>
  <div class="panel" style="color:rgb(142,77,0);">
    <div class="panel-inner">
      <div id="community_piegraph" style="display:none; text-align:left">
      <?php echo t("Time of use & hydro");?>:<br>
      <div style="text-align:center">
      <div id="community_piegraph_bound">
        <canvas id="community_piegraph_placeholder"></canvas>
      </div>
      </div>
      </div>

      <div id="community_bargraph" style="text-align:left">
      <div style="margin-bottom:5px"><?php echo t("Community Half-hourly Demand");?>: <span id="community-graph-date"></span></div>
     
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
          <span class="legend-label" >Hydro</span>
      </div>
    <!--
        <div id="legendholder" style="padding:5px 0px 0px 5px"></div>
         -->
        <div id="community_bargraph_bound" style="width:100%; height:500px;">
          <div id="community_bargraph_placeholder" style="height:500px"></div>
        </div>
      </div>

    </div>
  </div>
</div>
