<div class="page" page="household" style="display:none">
  <!-- STATUS TAB ------------------------------------------------------->
  <div class="pagesection" style="color:rgb(41,171,226)">
    <div style="height:10px; background-color:rgb(41,171,226)"></div>
    
    <div class="logout" style="float:right; padding-top:15px; padding-right:14px">
      <img src="images/logout.png" style="width:24px"/>
    </div>
    <div class="myaccount" style="float:right; padding-top:14px; padding-right:14px; cursor:pointer"><img src="images/el-person-icon.png" style="width:24px"/></div>
    <div class="togglelang">CY</div>
    <div class="title"><?php echo t("Performance");?></div>
    <div class="summary_bound"><div id="household_status_summary" class="panel-summary"></div></div>
  </div>
  <div class="panel" style="color:rgb(41,171,226)">
    <div class="panel-inner">

      <div id="household-status-block">
        <p><span id="household_score_text"><?php echo t("Over the last week you scored"); ?></span>: <b><span id="household_score"></span></b>/100</p>
        <!--<p><b><span id="prclocal">--</span>%</b> local or off-peak power<br><span style="font-size:12px">In the last 7 days</span></p>-->
        <img id="star1" src="images/star20blue.png" style="width:45px">
        <img id="star2" src="images/star20blue.png" style="width:45px">
        <img id="star3" src="images/star20blue.png" style="width:45px">
        <img id="star4" src="images/star20blue.png" style="width:45px">
        <img id="star5" src="images/star20blue.png" style="width:45px">
        <p id="statusmsg"></p>
        <!--Read more about what this means here-->
      </div>

      <div id="login-block" style="text-align:center">
        <div class="login-box">
        <div class="login-title"><?php echo t("Welcome!");?></div>
        <p><?php echo t("Please sign in to see your energy data");?></p>
        <p>
          <input id="email" type="text" placeholder="Email..." style="border: 1px solid rgb(41,171,226)"><br><br>
          <input id="password" type="password" placeholder="Password..." style="border: 1px solid rgb(41,171,226)"><br>
          
          <table style="border:0px"><tr>
          <td style="border:0px; text-align:right"><button id="login" class="btn"><?php echo t("Login");?></button></td>
          <td style="border:0px"><div id="passwordreset-start" style="display:inline-block; font-size:14px; color:rgba(255,255,255,0.8); cursor:pointer; color:rgb(41,171,226)"><?php echo t("Forgotten<br>password?");?></div></td>
          </tr></table>
        </p>
        <div id="alert"></div>
        </div>
      </div>

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

    </div>
  </div>

  <!-- SAVING TAB ------------------------------------------------------->
  <div class="pagesection" style="color:rgb(100,171,255)">
    <div style="height:10px; background-color:rgb(100,171,255)"></div>
    <div class="title"><?php echo t("Saving");?></div>
    <div class="summary_bound"><div id="household_saving_summary" class="panel-summary"></div></div>
  </div>
  <div class="panel"  style="">
    <div class="panel-inner" style="color:rgb(100,171,255)">
      <p><?php echo t("You used");?> <b><span class="totalkwh"></span> kWh</b> <span id="household-used-date"></span> <b>£<span class="totalcost"></span></b></p>
      <p><?php echo t("You saved");?> <b>£<span class="costsaving"></span></b> <?php echo t("compared with 12p/kWh reference price");?></p>
    </div>
  </div>

  <!-- BREAKDOWN TAB ------------------------------------------------------->
  <div class="pagesection" name="householdhistory" style="color:rgb(0,71,121)">
      <div style="height:10px; background-color:rgb(0,71,121)"></div>
      <div id="view-household-bargraph" style="float:right; margin:10px; padding-top:3px"><img src="images/bargraphiconblue.png" style="width:24px" /></div>
      <div id="view-household-piechart" style="float:right; margin:10px; display:none; padding-top:3px"><img src="images/piechartblue.png" style="width:24px" /></div>
      <div class="title"><?php echo t("Breakdown");?></div>
      
      <div class="visnav-household-block" style="display:none">
        <div class="visnav-household household-month">MONTH</div>
        <div class="visnav-household household-week">WEEK</div>
        <div class="visnav-household household-day">DAY</div>
      </div>
  </div>    
  <div class="panel">
    <div class="panel-inner" style="color:rgb(0,71,121)">

      <div id="household_piegraph" style="text-align:left">
        <?php echo t("Time of use & hydro");?>:<br>
        <div style="text-align:center">
          <div id="household_piegraph_bound">
            <canvas id="household_piegraph_placeholder"></canvas>
          </div>
        </div>
      </div>

      <div id="household_bargraph" style="display:none; text-align:left">
      <div style="margin-bottom:5px"><?php echo t("Half-hourly Demand");?>:</div>
      <!--
      <div id="household_bargraph_bound">
        <canvas id="household_bargraph_placeholder"></canvas>
      </div>
      -->
      <div id="household_bargraph_placeholder_bound" style="width:100%; height:500px;">
        <div id="household_bargraph_placeholder" style="height:500px"></div>
      </div>
      </div>
    </div>
  </div>
</div>
