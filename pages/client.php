<?php global $path; ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyd Ynni</title>
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
  
  <div class="accordion" style="background-color:rgb(39,201,63)"><div class="title" style="display:inline-block">OK to use?</div><div id="cydynni_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
  <div class="panel" style="background-color:rgb(39,201,63)">
    <div class="panel-inner">
      <p id="status-pre">If possible</p>

      <img id="status-img" src="images/el-dont-use-icon.png" style="width:100px">
      <div id="status-title" class="status">WAIT</div>
      <p id="status-until"></p>
      <p id="status-next"></p>
    </div>
  </div>
  
  <!-- TARIFF TAB ------------------------------------------------------->

  <div class="accordion" style="background-color:rgb(33,145,110)"><div class="title" style="display:inline-block">Electricity Prices</div><div id="tariff_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
  <div class="panel" style="background-color:rgb(33,145,110)">
    <div class="panel-inner">
      <div class="tariff-block">
        <img class="tariff-img" tariff="morning" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">6AM - 11AM</div>
        <div class="tariff-desc">MORNING PEAK PRICE - 12 PENCE PER UNIT</div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="midday" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">11AM - 4PM</div>
        <div class="tariff-desc">MIDDAY PRICE - 10 PENCE PER UNIT</div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="evening" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">4PM - 8PM</div>
        <div class="tariff-desc">EVENING PEAK PRICE - 14 PENCE PER UNIT</div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="overnight" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">8PM - 6AM</div>
        <div class="tariff-desc">OVERNIGHT PRICE - 7.25 PENCE PER UNIT</div>
      </div>
    </div>
  </div>
  
  <!-- HYDRO TAB ------------------------------------------------------->

  <div class="accordion" style="background-color:rgb(39,78,63)"><div class="title" style="display:inline-block">Hydro</div><div id="hydro_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
  <div class="panel" style="background-color:rgb(39,78,63)">
    <div class="panel-inner">
    
      <div style="height:120px; overflow:hidden">
          <div class="status"><span id="hydrostatus"></span></div>
          <p>Currently generating <b><span id="power"></span> kW</b></p>
          
          <p>LAST 24 HOURS</p>
      </div>
        <div id="placeholder_bound" style="height:100%">
              <canvas id="placeholder"></canvas>
        </div>
    </div>
  </div>
  
  </div>
  
  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->
  
  <div class="view" view="household" style="display:none">
    <!-- STATUS TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:rgb(41,171,226)"><div id="logout" style="float:right; padding:14px">Logout</div><div class="title" style="display:inline-block">Performance</div><div id="household_status_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
    <div style="background-color:rgb(41,171,226)" class="panel">
      <div class="panel-inner">
        
        <div id="household-status-block">
          <p>Over the last week you scored: <b><span id="household_score"></span></b>/100</p>
          <!--<p><b><span id="prclocal">--</span>%</b> local or off-peak power<br><span style="font-size:12px">In the last 7 days</span></p>-->
          <img id="star1" src="images/star20.png" style="width:45px">
          <img id="star2" src="images/star20.png" style="width:45px">
          <img id="star3" src="images/star20.png" style="width:45px">
          <img id="star4" src="images/star20.png" style="width:45px">
          <img id="star5" src="images/star20.png" style="width:45px">
          <p id="statusmsg"></p>
        </div>
        
        <div id="login-block" style="text-align:center">
          <div class="login-box">
          <h2>Welcome!</h2>
          <p>Please sign in to see your energy data</p>
          <p>
            <input id="email" type="text" placeholder="Email..."><br><br>
            <input id="password" type="password" placeholder="Password..."><br><br>
            <button id="login" class="btn">Login</button>
          </p>
          <div id="alert"></div>
          </div>
        </div>
        
      </div>
    </div>
    
    <!-- SAVING TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:rgb(100,171,255)"><div class="title" style="display:inline-block">Saving</div><div id="household_saving_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
    <div class="panel"  style="background-color:rgb(100,171,255)">
      <div class="panel-inner">
        <p>You have used <b><span class="totalkwh"></span> kWh</b> in the last week<br>Costing <b>£<span class="totalcost"></span></b></p>
        <p>You have saved <b>£<span class="costsaving"></span></b> compared to standard flat rate price</p>
      </div>
    </div>
    
    <!-- BREAKDOWN TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:rgb(0,71,121)"><div class="title">Breakdown</div></div>
    <div class="panel" style="background-color:rgb(0,71,121)">
      <div class="panel-inner">
        
        <style> .bd {margin-bottom:5px;} </style>
        <canvas id="piegraph" width=400 height=400 ></canvas>
        
      </div>
    </div>
  </div>
  
  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->
  
  <div class="view" view="bethesda" style="display:none; color:#3b6358;">
    <!-- STATUS TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:rgb(255,220,0)"><div class="title" style="display:inline-block">Status</div><div id="community_status_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
    <div style="background-color:rgb(255,220,0)" class="panel">
      <div class="panel-inner">
        <p>Over the last week we scored: <b><span id="community_score"></span></b>/100</p>
        <!--<p><b><span id="community_prclocal">--</span>%</b> local or off-peak power<br><span style="font-size:12px">In the last 7 days</span></p>-->
        <img id="community_star1" src="images/star20.png" style="width:45px">
        <img id="community_star2" src="images/star20.png" style="width:45px">
        <img id="community_star3" src="images/star20.png" style="width:45px">
        <img id="community_star4" src="images/star20.png" style="width:45px">
        <img id="community_star5" src="images/star20.png" style="width:45px">
        <p id="community_statusmsg"></p>
      </div>
    </div>
    
    <!-- SAVING TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:rgb(255,117,0); color:#fff;"><div class="title" style="display:inline-block">Cost</div><div id="community_saving_summary" class="panel-summary" style="display:inline-block; font-size:14px"></div></div>
    <div class="panel"  style="background-color:rgb(255,117,0); color:#fff;">
      <div class="panel-inner">
        <p>We have used <b><span class="community_totalkwh"></span> kWh</b> in the last week<br>Costing <b>£<span class="community_totalcost"></span></b></p>
        <!--<p>We have saved <b>£<span class="community_costsaving"></span></b> compared to standard flat rate price</p>-->
      </div>
    </div>
    
    <!-- BREAKDOWN TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:rgb(142,77,0); color:#fff;"><div class="title">Breakdown</div></div>
    <div class="panel" style="background-color:rgb(142,77,0); color:#fff;">
      <div class="panel-inner">
        <style> .bd {margin-bottom:5px;} </style>
        <canvas id="community_piegraph" width=400 height=400 ></canvas>
      </div>
    </div>
  </div>

  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->
  
  <div class="view" view="tips" style="display:none; color:#fff;">
    <!-- STATUS TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:#284e3f"><div class="title">Tips</div></div>
    <div class="panel" style="background-color:#284e3f">
      <div class="panel-inner">
        <!-- TIP 1 -->
        <div class="tip" tipid=1 style="width:320px; margin: 0 auto;">
          <img src="images/light-bulb-3.png" class="tipimage">
          <h1>LED LIGHTS</h1>
          <div style="text-align:left">
          <p>LED lights can cut your lighting costs by up to 90%. There’s more information on our website and in the info pack on installing them in your house</p>
          </div>
        </div>
        <!-- TIP 2 -->
        <div class="tip" tipid=2 style="width:320px; margin: 0 auto; display:none">
          <img src="images/washing-machine.png" class="tipimage">
          <h1>WASHING MACHINE</h1>
          <div style="text-align:left">
          <p>The time you run your washing machine can be moved to avoid morning and evening peaks and take advantage of hydro power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am)</p>
          </div>
        </div>
        <!-- TIP 3 -->
        <div class="tip" tipid=3 style="width:320px; margin: 0 auto; display:none">
          <img src="images/dishwasher.png" class="tipimage">
          <h1>DISHWASHER</h1>
          <div style="text-align:left">
          <p>The time you run your dishwasher can be moved to avoid morning and evening peaks and take advantage of hydro power and the cheaper prices in the daytime (11am - 4pm) and overnight (8pm - 6am)</p>
          </div>
        </div>
        <!-- TIP 4 -->
        <div class="tip" tipid=4 style="width:320px; margin: 0 auto; display:none">
          <img src="images/slow-cooker.png" class="tipimage">
          <h1>SLOW COOKING</h1>
          <div style="text-align:left">
          <p>Slow cookers are very energy efficient, make tasty dinners and helping you avoid using electricity during the evening peak (4 - 8pm) when you might otherwise being using an electric oven. </p>
          </div>
        </div>
        <!-- TIP 5 -->
        <div class="tip" tipid=5 style="width:320px; margin: 0 auto; display:none">
          <img src="images/lamp-6.png" class="tipimage">
          <h1>LIGHTS</h1>
          <div style="text-align:left">
          <p>Switching off lights and appliance when not in use is a simple and effective way to use less electricity. You can make a special effort to do this during the morning and evening peaks.</p>
          </div>
        </div>
        <!-- TIP 6 -->
        <div class="tip" tipid=6 style="width:320px; margin: 0 auto; display:none">
          
          <img src="images/stove.png" class="tipimage">
          <h1>COOKING</h1>
          <div style="text-align:left">
          <p>Putting a lid on your pan when you're cooking traps the heat inside so you don’t need to have the hob on as high. A simple and effective way to use less electricity.</p>
          </div>
        </div>
        <!-- TIP 7 -->
        <div class="tip" tipid=7 style="width:320px; margin: 0 auto; display:none">
          
          <img src="images/fridge-2.png" class="tipimage">
          <h1>FRIDGE/FREEZER</h1>
          <div style="text-align:left">
          <p>Try to minimise how often and how long you need to open the doors. Wait for cooked food to cool before putting it in the fridge. Older fridges and freezers can be very inefficient and costly to run.</p>
          </div>
        </div>
           
        <div style="width:320px; margin: 0 auto">
          <div id="previous-tip" style="float:left; padding:10px; background-color:#527165; cursor:pointer"><b>&#60; PREVIOUS</b></div>
          <div id="next-tip" style="float:right; padding:10px; background-color:#527165; cursor:pointer"><b>NEXT TIP ></b></div>
        </div>
          
      </div>
    </div>
    
  </div>
  
  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->
  
  <div class="icon-bar">
    <a class="icon-bar-item" title="Ok to Use?" view="hydro" href="#hydro"><img src="images/el-clock-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="My Household" view="household" href="#household"><img src="images/el-person-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="Community performance" view="bethesda" href="#bethesda"><img src="images/el-group-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="Tips" view="tips" href="#tips"><img src="images/el-bulb-icon.png" style="width:22px"></a>
  </div>

  </body>
</html>

<script language="javascript" type="text/javascript" src="js/pie.js"></script>
<script language="javascript" type="text/javascript" src="js/hydro.js"></script>
<script language="javascript" type="text/javascript" src="js/household.js"></script>
<script language="javascript" type="text/javascript" src="js/community.js"></script>
<script language="javascript" type="text/javascript" src="js/user.js"></script>
<script>

var path = "<?php echo $path; ?>";
var session = JSON.parse('<?php echo json_encode($session); ?>');
console.log(session);

var accordionheight = 54;
var iconbarheight = 51;

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
var view = "hydro";
var height = $(window).height() - accordionheight*3 - iconbarheight;
$(".view[view=hydro] .panel").first().height(height);
$(".view[view=household] .panel").first().height(height);
$(".view[view=bethesda] .panel").first().height(height);
$(".view[view=tips] .panel").first().height(height+accordionheight*2);

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
    panel_height = $(window).height() - accordionheight*3 - iconbarheight;
    $(this).next().attr("active",1);
    $(this).next().height(panel_height);
    if (view=="hydro") graph_resize(panel_height-40-120);
  }
});

$(window).resize(function(){
  panel_height = $(window).height() - accordionheight*3 - iconbarheight;
  $(".panel[active=1]").height(panel_height);
  if (view=="hydro") graph_resize(panel_height-40-120);
});

$(".icon-bar-item").click(function(){
  view = $(this).attr("view");
  $(".view").hide();
  $(".view[view="+view+"]").show();
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
  
  if (tariff=="morning") $("#status-img").attr("src","images/el-dont-use-icon.png");
  if (tariff=="midday") $("#status-img").attr("src","images/el-use-icon.png");
  if (tariff=="evening") $("#status-img").attr("src","images/el-dont-use-icon.png");
  if (tariff=="overnight") $("#status-img").attr("src","images/el-use-icon.png");
  
  // If morning peak then wait until midday tariff
  if (tariff=="morning") {
      $("#status-pre").html("If possible");
      $("#status-title").html("WAIT");
      $("#tariff_summary").html("NOW: MORNING PEAK");
  
      var time_to_wait = (11 - (hour+1))+" HOURS, "+(60-minutes)+" MINS";
      $("#status-until").html("until <b>11<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+" FROM NOW)</span>");
      
      $("#status-next").html("After that the next best time to use power<br>is <b>8pm - 6am.</b>");
      $("#cydynni_summary").html("WAIT "+time_to_wait);
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="midday") {
      $("#status-pre").html("Now is a good time to use electricity");
      $("#status-title").html("GO!");
      $("#tariff_summary").html("NOW: MIDDAY");
      
      var time_to_wait = (16 - (hour+1))+" HOURS, "+(60-minutes)+" MINS";
      $("#status-until").html("until <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span>");
      $("#cydynni_summary").html(time_to_wait+" MORE");
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="evening") {
      $("#status-pre").html("If possible");
      $("#status-title").html("WAIT");
      $("#tariff_summary").html("NOW: EVENING PEAK");
      
      var time_to_wait = (20 - (hour+1))+" HOURS, "+(60-minutes)+" MINS";
      $("#status-until").html("until <b>8<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+" FROM NOW)</span>");
      $("#cydynni_summary").html("WAIT "+time_to_wait);
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="overnight") {
      $("#status-pre").html("Now is a good time to use electricity");
      $("#status-title").html("GO!");
      
      $("#tariff_summary").html("NOW: OVERNIGHT");
      
      if (hour>6) {
          var time_to_wait = (24-(hour+1)+6)+" HOURS, "+(60-minutes)+" MINS";
      } else {
          var time_to_wait = (6-(hour+1))+" HOURS, "+(60-minutes)+" MINS";
      }
      $("#status-until").html("until <b>6<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span>");
      $("#cydynni_summary").html(time_to_wait+" MORE");
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
</script>

