<?php global $path, $translation, $lang; ?>

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
    <link rel="stylesheet" type="text/css" href="theme/style.css" />
    <link rel="stylesheet" type="text/css" href="theme/forms.css" />
    <link rel="stylesheet" type="text/css" href="theme/buttons.css" />
    <!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
  </head>
  <body>

  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->

  <!-- OK TO USE? TAB ------------------------------------------------------->
  <div class="view" view="hydro">

  <div class="accordion" style="color:rgb(39,201,63)"><div style="height:10px; background-color:rgb(39,201,63)"></div><div class="title" style="display:inline-block"><?php echo t("OK to use?"); ?></div><div id="cydynni_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div><div class="togglelang" style="float:right; padding:14px">CY</div></div>
  <div class="panel" style="#fff">
    <div class="panel-inner">
      <p id="status-pre"><?php echo t("If possible");?></p>

      <img id="status-img" src="images/waiting-icon.jpg" style="width:100px">
      <div id="status-title" class="status"><?php echo t("WAIT");?></div>
      <p id="status-until"></p>
      <p id="status-next"></p>
    </div>
  </div>

  <!-- TARIFF TAB ------------------------------------------------------->

  <div class="accordion" style="color:rgb(33,145,110)"><div style="height:10px; background-color:rgb(33,145,110)"></div><div class="title" style="display:inline-block"><?php echo t("Electricity Prices");?></div><div id="tariff_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
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
    </div>
  </div>

  <!-- HYDRO TAB ------------------------------------------------------->

  <div class="accordion" style="color:rgb(39,78,63)"><div style="height:10px; background-color:rgb(39,78,63)"></div><div class="title" style="display:inline-block">Hydro</div><div id="hydro_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
  <div class="panel">
    <div class="panel-inner">
      <div style="height:80px; overflow:hidden">
        <div class="status"><span id="hydrostatus"></span></div>
        <?php echo t("Currently generating");?> <b><span id="power"></span> kW</b>
      </div>

      <div style="text-align:center">
      <div style="margin-bottom:5px"><?php echo t("Last 24 hours");?>:</div>
      <div id="placeholder_bound" style="height:100%">
        <canvas id="placeholder"></canvas>
      </div>
      </div>
    </div>
  </div>

  </div>

  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->

  <div class="view" view="household" style="display:none">
    <!-- STATUS TAB ------------------------------------------------------->
    <div class="accordion" style="color:rgb(41,171,226)"><div style="height:10px; background-color:rgb(41,171,226)"></div><div class="togglelang" style="float:right; padding:14px">CY</div><div id="logout" style="float:right; padding:14px"><?php echo t("Logout");?></div><div class="title" style="display:inline-block"><?php echo t("Performance");?></div><div id="household_status_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
    <div class="panel" style="color:rgb(41,171,226)">
      <div class="panel-inner">

        <div id="household-status-block">
          <p><?php t("Over the last week you scored");?>: <b><span id="household_score"></span></b>/100</p>
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
          <h2><?php echo t("Welcome!");?></h2>
          <p><?php echo t("Please sign in to see your energy data");?></p>
          <p>
            <input id="email" type="text" placeholder="Email..." style="border: 1px solid rgb(41,171,226)"><br><br>
            <input id="password" type="password" placeholder="Password..." style="border: 1px solid rgb(41,171,226)"><br><br>
            <button id="login" class="btn"><?php echo t("Login");?></button><br>
            <div id="passwordreset-start" style="font-size:14px; color:rgba(255,255,255,0.8); cursor:pointer; color:rgb(41,171,226)"><?php echo t("Forgotten password?");?></div>
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
    <div class="accordion" style="color:rgb(100,171,255)"><div style="height:10px; background-color:rgb(100,171,255)"></div><div class="title" style="display:inline-block"><?php echo t("Saving");?></div><div id="household_saving_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
    <div class="panel"  style="">
      <div class="panel-inner" style="color:rgb(100,171,255)">
        <p><?php echo t("You have used");?> <b><span class="totalkwh"></span> kWh</b> <?php echo t("in the last week<br>Costing");?> <b>£<span class="totalcost"></span></b></p>
        <p><?php echo t("You have saved");?> <b>£<span class="costsaving"></span></b> <?php echo t("compared to standard flat rate price");?></p>
      </div>
    </div>

    <!-- BREAKDOWN TAB ------------------------------------------------------->
    <div class="accordion" style="color:rgb(0,71,121)">
        <div style="height:10px; background-color:rgb(0,71,121)"></div>
        <button id="view-household-bargraph" style="float:right; margin:10px"><?php echo t("View Bar Graph");?></button>
        <button id="view-household-piechart" style="float:right; margin:10px; display:none"><?php echo t("View Pie Chart");?></button>
        <div class="title"><?php echo t("Breakdown");?></div></div>
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
        <div id="household_bargraph_bound">
          <canvas id="household_bargraph_placeholder"></canvas>
        </div>
        </div>
      </div>
    </div>
  </div>

  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->

  <div class="view" view="community" style="display:none;">
    <!-- STATUS TAB ------------------------------------------------------->
    <div class="accordion" style="color:rgb(234,200,0)"><div style="height:10px; background-color:rgb(235,200,0)"></div><div class="title" style="display:inline-block"><?php echo t("Status"); ?></div><div id="community_status_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div><div class="togglelang" style="float:right; padding:14px">CY</div></div>
    <div class="panel" style="color:rgb(235,200,0)">
      <div class="panel-inner">
        <p><?php t("Over the last week we scored");?>: <b><span id="community_score"></span></b>/100</p>
        <!--<p><b><span id="community_prclocal">--</span>%</b> local or off-peak power<br><span style="font-size:12px">In the last 7 days</span></p>-->
        <img id="community_star1" src="images/star20yellow.png" style="width:45px">
        <img id="community_star2" src="images/star20yellow.png" style="width:45px">
        <img id="community_star3" src="images/star20yellow.png" style="width:45px">
        <img id="community_star4" src="images/star20yellow.png" style="width:45px">
        <img id="community_star5" src="images/star20yellow.png" style="width:45px">
        <p id="community_statusmsg"></p>
      </div>
    </div>

    <!-- SAVING TAB ------------------------------------------------------->
    <div class="accordion" style="color:rgb(255,117,0);"><div style="height:10px; background-color:rgb(255,117,0)"></div><div class="title" style="display:inline-block"><?php echo t("Cost"); ?></div><div id="community_saving_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
    <div class="panel" style="color:rgb(255,117,0);">
      <div class="panel-inner">
        <p><?php echo t("We have used"); ?> <b><span class="community_totalkwh"></span> kWh</b> <?php echo t("in the last week<br>Costing"); ?> <b>£<span class="community_totalcost"></span></b></p>
        <!--<p>We have saved <b>£<span class="community_costsaving"></span></b> compared to standard flat rate price</p>-->
      </div>
    </div>

    <!-- BREAKDOWN TAB ------------------------------------------------------->
    <div class="accordion" style="color:rgb(142,77,0);">
      <div style="height:10px; background-color:rgb(142,77,0)"></div>
      <button id="view-community-bargraph" style="float:right; margin:10px"><?php echo t("View Bar Graph");?></button>
      <button id="view-community-piechart" style="float:right; margin:10px; display:none"><?php echo t("View Pie Chart");?></button>
      <div class="title"><?php echo t("Breakdown");?></div>
    </div>
    <div class="panel" style="color:rgb(142,77,0);">
      <div class="panel-inner">
        <div id="community_piegraph" style="text-align:left">
        <?php echo t("Time of use & hydro");?>:<br>
        <div style="text-align:center">
        <div id="community_piegraph_bound">
          <canvas id="community_piegraph_placeholder"></canvas>
        </div>
        </div>
        </div>

        <div id="community_bargraph" style="display:none; text-align:left">
        <div style="margin-bottom:5px"><?php echo t("Community Half-hourly Demand");?>:</div>
        <div id="community_bargraph_bound">
          <canvas id="community_bargraph_placeholder"></canvas>
        </div>
        </div>

      </div>
    </div>
  </div>

  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->

  <div class="view" view="tips" style="display:none; color:#fff;">
    <!-- STATUS TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:#284e3f"><div class="togglelang" style="float:right; padding:14px">CY</div><div class="title"><?php echo t("Tips");?></div></div>
    <div class="panel" style="background-color:#284e3f">
      <div class="panel-inner">
        <!-- TIP 1 -->
        <div class="tip" tipid=1 style="width:320px; margin: 0 auto;">
          <img src="images/light-bulb-3.png" class="tipimage">
          <h1><?php t("LED LIGHTS");?></h1>
          <div style="text-align:left">
          <p><?php t("LED lights can cut your lighting costs by up to 90%. There’s more information on our website and in the info pack on installing them in your house"); ?></p>
          </div>
        </div>
        <!-- TIP 2 -->
        <div class="tip" tipid=2 style="width:320px; margin: 0 auto; display:none">
          <img src="images/washing-machine.png" class="tipimage">
          <h1><?php t("WASHING MACHINE");?></h1>
          <div style="text-align:left">
          <p><?php t("The time you run your washing machine can be moved to avoid morning and evening peaks and take advantage of hydro power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am)");?></p>
          </div>
        </div>
        <!-- TIP 3 -->
        <div class="tip" tipid=3 style="width:320px; margin: 0 auto; display:none">
          <img src="images/dishwasher.png" class="tipimage">
          <h1><?php t("DISHWASHER");?></h1>
          <div style="text-align:left">
          <p><?php t("The time you run your dishwasher can be moved to avoid morning and evening peaks and take advantage of hydro power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am)");?></p>
          </div>
        </div>
        <!-- TIP 4 -->
        <div class="tip" tipid=4 style="width:320px; margin: 0 auto; display:none">
          <img src="images/slow-cooker.png" class="tipimage">
          <h1><?php t("SLOW COOKING");?></h1>
          <div style="text-align:left">
          <p><?php t("Slow cookers are very energy efficient, make tasty dinners and help you avoid using electricity during the evening peak (4 - 8pm) when you might otherwise be using an electric oven.");?></p>
          </div>
        </div>
        <!-- TIP 5 -->
        <div class="tip" tipid=5 style="width:320px; margin: 0 auto; display:none">
          <img src="images/lamp-6.png" class="tipimage">
          <h1><?php t("LIGHTS");?></h1>
          <div style="text-align:left">
          <p><?php t("Switching off lights and appliance when not in use is a simple and effective way to use less electricity. You can make a special effort to do this during the morning and evening peaks.");?></p>
          </div>
        </div>
        <!-- TIP 6 -->
        <div class="tip" tipid=6 style="width:320px; margin: 0 auto; display:none">

          <img src="images/stove.png" class="tipimage">
          <h1><?php t("COOKING");?></h1>
          <div style="text-align:left">
          <p><?php t("Putting a lid on your pan when you're cooking traps the heat inside so you don’t need to have the hob on as high. A simple and effective way to use less electricity.");?></p>
          </div>
        </div>
        <!-- TIP 7 -->
        <div class="tip" tipid=7 style="width:320px; margin: 0 auto; display:none">

          <img src="images/fridge-2.png" class="tipimage">
          <h1><?php t("FRIDGE/FREEZER");?></h1>
          <div style="text-align:left">
          <p><?php t("Try to minimise how often and how long you need to open the doors. Wait for cooked food to cool before putting it in the fridge. Older fridges and freezers can be very inefficient and costly to run.");?></p>
          </div>
        </div>

        <div style="width:340px; margin: 0 auto">
          <div id="previous-tip" style="float:left; padding:10px; background-color:#527165; cursor:pointer"><b>&#60; <?php t("PREVIOUS");?></b></div>
          <div id="next-tip" style="float:right; padding:10px; background-color:#527165; cursor:pointer"><b><?php t("NEXT TIP");?> ></b></div>
        </div>

      </div>
    </div>

  </div>

  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->

  <div class="icon-bar" style="">
    <a class="icon-bar-item" title="Ok to Use?" view="hydro" href="#hydro"><img src="images/el-clock-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="My Household" view="household" href="#household"><img src="images/el-person-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="Community performance" view="community" href="#community"><img src="images/el-group-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="Tips" view="tips" href="#tips"><img src="images/el-bulb-icon.png" style="width:22px"></a>
  </div>

  </body>
</html>


<script language="javascript" type="text/javascript" src="js/bargraph.js"></script>
<script language="javascript" type="text/javascript" src="js/pie.js"></script>
<script language="javascript" type="text/javascript" src="js/hydro.js"></script>
<script language="javascript" type="text/javascript" src="js/household.js"></script>
<script language="javascript" type="text/javascript" src="js/community.js"></script>
<script language="javascript" type="text/javascript" src="js/user.js"></script>
<script>

var path = "<?php echo $path; ?>";
var session = JSON.parse('<?php echo json_encode($session); ?>');
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";
if (lang=="cy") {
    $(".togglelang").html("EN");
} else {
    $(".togglelang").html("CY");
}



var accordionheight = 64;
var iconbarheight = 52;

var tipid = 1;

if (!session) {
  $("#household-status-block").hide();
  $("#logout").hide();
} else {
  $("#login-block").hide();
  $("#logout").show();
  household_load();
}

var panel_height = $(window).height() - accordionheight*3 - iconbarheight;

var req = parse_location_hash(window.location.hash);
var view = "hydro";
if (req[0]=="household") view = "household";
if (req[0]=="community") view = "community";
if (req[0]=="tips") view = "tips";

$(".view").hide();
$(".view[view="+view+"]").show();

$(".view[view=hydro] .panel").first().height(panel_height).attr("active",1);
$(".view[view=household] .panel").first().height(panel_height).attr("active",1);
$(".view[view=community] .panel").first().height(panel_height).attr("active",1);
$(".view[view=tips] .panel").first().height(panel_height+accordionheight*2).attr("active",1);

$(".view").each(function() {
   $(this).find(".panel-summary").first().hide();
});

$(".accordion").click(function() {
  if (view=="household" && !session) {

  } else {
    // Hide and disable all panels
    $(".view[view="+view+"] .panel").attr("active",0);
    $(".view[view="+view+"] .panel").height(0);
    $(".panel-summary").show();
    $(this).find(".panel-summary").hide();
    // Show only clicked panel
    $(this).next().attr("active",1);

    panel_height = $(window).height() - accordionheight*3 - iconbarheight;
    $(this).next().height(panel_height);

    draw_panel();
  }
});

$(window).resize(function(){
  panel_height = $(window).height() - accordionheight*3 - iconbarheight;

  if (panel_height<200) panel_height = 200;

  $(".panel[active=1]").height(panel_height);
  $(".view[view=tips] .panel[active=1]").height(panel_height+accordionheight*2);

  draw_panel();
});

$(".icon-bar-item").click(function(){
  view = $(this).attr("view");
  $(".view").hide();
  $(".view[view="+view+"]").show();
  draw_panel();
});

function draw_panel() {
  if (view=="hydro") graph_resize(panel_height-120);
  if (view=="household") {
      household_pie_draw();
      household_bargraph_resize(panel_height-40);
  }
  if (view=="community") {
      community_pie_draw();
      community_bargraph_resize(panel_height-40);
  }
}

$(".togglelang").click(function(){
    var ilang = $(this).html();
    if (ilang=="CY") {
        $(this).html("EN");
        window.location = "?lang=cy#"+view;
    } else {
        $(this).html("CY");
        lang="cy";
        window.location = "?lang=en#"+view;
    }
});

// Hydro
update();
load();
community_load();

status_update();
setInterval(status_update,10000);
function status_update() {
  var time = new Date();

  var hour = time.getHours();
  var minutes = time.getMinutes();

  $("#status-next").html("");

  var tariff = 0;
  if ((hour>=6) && (hour<11)) tariff = "morning";
  if ((hour>=11) && (hour<16)) tariff = "midday";
  if ((hour>=16) && (hour<20)) tariff = "evening";
  if ((hour>=20) || (hour<6)) tariff = "overnight";

  if (tariff=="morning") $("#status-img").attr("src","images/waiting-icon-small.jpg");
  if (tariff=="midday") $("#status-img").attr("src","images/new-tick-small.jpg");
  if (tariff=="evening") $("#status-img").attr("src","images/waiting-icon-small.jpg");
  if (tariff=="overnight") $("#status-img").attr("src","images/new-tick-small.jpg");

  // If morning peak then wait until midday tariff
  if (tariff=="morning") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Morning Price"));

      var time_to_wait = (11 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      
      $("#status-until").html(t("until")+" <b>11<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? cheaper around midday"));

      $("#status-next").html(t("After that the next best time to use power<br>is <b>8pm - 6am.</b>"));
      $("#cydynni_summary").html(t("Wait until 11am"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="midday") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));
      $("#tariff_summary").html(t("Now")+": "+t("Midday Price"));

      var time_to_wait = (16 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? midday price currently available"));
      $("#cydynni_summary").html(t("Ok until 4pm"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="evening") {
      $("#status-pre").html(t("If possible"));
      $("#status-title").html(t("WAIT"));
      $("#tariff_summary").html(t("Now")+": "+t("Evening Price"));

      var time_to_wait = (20 - (hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      $("#status-until").html(t("until")+" <b>8<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+" "+t("FROM NOW")+")</span><br>"+t("Why? overnight price coming up"));
      $("#cydynni_summary").html(t("Wait until 8pm"));
  }

  // If evening peak then wait until overnight tariff
  if (tariff=="overnight") {
      $("#status-pre").html(t("Now is a good time to use electricity"));
      $("#status-title").html(t("GO!"));

      $("#tariff_summary").html(t("Now")+": "+t("Overnight Price"));

      if (hour>6) {
          var time_to_wait = (24-(hour+1)+6)+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      } else {
          var time_to_wait = (6-(hour+1))+" "+t("HOURS")+", "+(60-minutes)+" "+t("MINS");
      }
      $("#status-until").html(t("until")+" <b>6<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span><br>"+t("Why? overnight price currently available"));
      $("#cydynni_summary").html(t("Ok until 6am"));
  }

  $(".tariff-img").hide();
  $(".tariff-img[tariff="+tariff+"]").show();
}

$("#previous-tip").click(function(){
    tipid--;
    if (tipid<1) tipid = 7;
    $(".tip").hide();
    $(".tip[tipid="+tipid+"]").show();
});

$("#next-tip").click(function(){
    tipid++;
    if (tipid>7) tipid = 1;
    $(".tip").hide();
    $(".tip[tipid="+tipid+"]").show();
});

function t(s) {

    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}

function parse_location_hash(hash)
{
    hash = hash.substring(1);
    hash = hash.replace("?","/");
    hash = hash.replace("&","/");
    hash = hash.split("/");
    return hash;
}

</script>
