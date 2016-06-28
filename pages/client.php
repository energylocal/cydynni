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
  
  <div class="accordion" style="background-color:#27c93f"><div class="title">OK to use? </div></div>
  <div style="background-color:#27c93f" class="panel">
    <div class="panel-inner">
      <p id="status-pre">If possible</p>

      <img id="status-img" src="images/el-dont-use-icon.png" style="width:100px">
      <div id="status-title" class="status">WAIT</div>
      <p id="status-until"></p>
      <p id="status-next"></p>
    </div>
  </div>
  
  <!-- TARIFF TAB ------------------------------------------------------->

  <div class="accordion" style="background-color:#22a835"><div class="title">Tariffs</div></div>
  <div class="panel"  style="background-color:#22a835">
    <div class="panel-inner">
      <div class="tariff-block">
        <img class="tariff-img" tariff="morning" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">6AM - 11AM</div>
        <div class="tariff-desc">MORNING PEAK - 12 PENCE PER UNIT</div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="midday" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">11AM - 4PM</div>
        <div class="tariff-desc">MIDDLE OF THE DAY - 10 PENCE PER UNIT</div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="evening" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">4PM - 8PM</div>
        <div class="tariff-desc">EVENING PEAK - 14 PENCE PER UNIT</div>
      </div>
      <div class="tariff-block">
        <img class="tariff-img" tariff="overnight" src="images/now.png" style="width:40px; margin-right:10px; float:left">
        <div class="tariff-time">8PM - 6AM</div>
        <div class="tariff-desc">OVERNIGHT - 7.25 PENCE PER UNIT</div>
      </div>
    </div>
  </div>
  
  <!-- HYDRO TAB ------------------------------------------------------->

  <div class="accordion" style="background-color:#1b872a"><div class="title">Hydro</div></div>
  <div class="panel" style="background-color:#1b872a">
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
    <div class="accordion" style="background-color:#29abe2"><div id="logout" style="float:right; padding:14px">Logout</div><div class="title">Status</div></div>
    <div style="background-color:#29abe2" class="panel">
      <div class="panel-inner">
        
        <div id="household-status-block">
          <p><b><span id="prclocal">--</span>%</b> local or off-peak power<br><span style="font-size:12px">In the last 7 days</span></p>
          <img id="star1" src="images/star20.png" style="width:45px">
          <img id="star2" src="images/star20.png" style="width:45px">
          <img id="star3" src="images/star20.png" style="width:45px">
          <img id="star4" src="images/star20.png" style="width:45px">
          <img id="star5" src="images/star20.png" style="width:45px">
          <p id="statusmsg"></p>
        </div>
        
        <div id="login-block" style="text-align:center">
          <div class="login-box">
          <h2>Login</h2>
          <p>Please login to view household data</p>
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
    <div class="accordion" style="background-color:#1988b7; color:#333;"><div class="title">Saving</div></div>
    <div class="panel"  style="background-color:#1988b7">
      <div class="panel-inner">
        <p>We have used <b><span class="totalkwh"></span> kWh</b> in the last week<br>Costing <b>£<span class="totalcost"></span></b></p>
        <p>We have saved <b>£<span class="costsaving"></span></b> compared to standard flat rate price</p>
      </div>
    </div>
    
    <!-- BREAKDOWN TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:#146f95"><div class="title">Breakdown</div></div>
    <div class="panel" style="background-color:#146f95">
      <div class="panel-inner">
        
        <style> .bd {margin-bottom:5px;} </style>
        <canvas id="piegraph" width=400 height=400 ></canvas>
        
      </div>
    </div>
  </div>
  
  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->
  
  <div class="view" view="bethesda" style="display:none">
    <!-- STATUS TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:#ffdc00"><div class="title">Status</div></div>
    <div style="background-color:#ffdc00" class="panel">
      <div class="panel-inner">
        <p><b><span id="community_prclocal">--</span>%</b> local or off-peak power<br><span style="font-size:12px">In the last 7 days</span></p>
        <img id="community_star1" src="images/star20.png" style="width:45px">
        <img id="community_star2" src="images/star20.png" style="width:45px">
        <img id="community_star3" src="images/star20.png" style="width:45px">
        <img id="community_star4" src="images/star20.png" style="width:45px">
        <img id="community_star5" src="images/star20.png" style="width:45px">
        <p id="community_statusmsg"></p>
      </div>
    </div>
    
    <!-- SAVING TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:#ffc800"><div class="title">Saving</div></div>
    <div class="panel"  style="background-color:#ffc800">
      <div class="panel-inner">
        <p>You have used <b><span class="community_totalkwh"></span> kWh</b> in the last week<br>Costing <b>£<span class="community_totalcost"></span></b></p>
        <p>You have saved <b>£<span class="community_costsaving"></span></b> compared to standard flat rate price</p>
      </div>
    </div>
    
    <!-- BREAKDOWN TAB ------------------------------------------------------->
    <div class="accordion" style="background-color:#ffb400"><div class="title">Breakdown</div></div>
    <div class="panel" style="background-color:#ffb400">
      <div class="panel-inner">
        <style> .bd {margin-bottom:5px;} </style>
        <canvas id="community_piegraph" width=400 height=400 ></canvas>
      </div>
    </div>
  </div>

  <!---------------------------------------------------------------------------------------------------------------------------------->
  <!---------------------------------------------------------------------------------------------------------------------------------->
  
  <div class="icon-bar">
    <a class="icon-bar-item" view="hydro" href="#hydro"><img src="images/el-clock-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" view="household" href="#household"><img src="images/el-person-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" view="bethesda" href="#bethesda"><img src="images/el-group-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" view="tips" href="#tips"><img src="images/el-bulb-icon.png" style="width:22px"></a>
  </div>

  </body>
</html>

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

$(".accordion").click(function() {
  if (view=="household" && !session) {

  } else {
    // Hide and disable all panels
    $(".view[view="+view+"] .panel").attr("active",0);
    $(".view[view="+view+"] .panel").height(0);
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
  
      var time_to_wait = (11 - (hour+1))+" HOURS, "+(60-minutes)+" MINUTES";
      $("#status-until").html("until <b>11<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span>");
      
      $("#status-next").html("After that the next best time to use power<br>is <b>8pm - 6am.</b>");
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="midday") {
      $("#status-pre").html("Now is a good time to use electricity");
      $("#status-title").html("GO!");
      
      var time_to_wait = (16 - (hour+1))+" HOURS, "+(60-minutes)+" MINUTES";
      $("#status-until").html("until <b>4<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span>");
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="evening") {
      $("#status-pre").html("If possible");
      $("#status-title").html("WAIT");
      
      var time_to_wait = (20 - (hour+1))+" HOURS, "+(60-minutes)+" MINUTES";
      $("#status-until").html("until <b>8<span style='font-size:12px'>PM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span>");
  }
  
  // If evening peak then wait until overnight tariff
  if (tariff=="overnight") {
      $("#status-pre").html("Now is a good time to use electricity");
      $("#status-title").html("GO!");
      
      if (hour>6) {
          var time_to_wait = (24-(hour+1)+6)+" HOURS, "+(60-minutes)+" MINUTES";
      } else {
          var time_to_wait = (6-(hour+1))+" HOURS, "+(60-minutes)+" MINUTES";
      }
      $("#status-until").html("until <b>6<span style='font-size:12px'>AM</span></b> <span style='font-size:12px'>("+time_to_wait+")</span>");
  }
  
  $(".tariff-img").hide();
  $(".tariff-img[tariff="+tariff+"]").show();
}

</script>

