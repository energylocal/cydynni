<?php

global $path, $translation, $lang;
$v = 26;

$tariffs = array(
    "bethesda"=>array(
        "generation"=>array("name"=>"Hydro","cost"=>0.07,"color"=>"#29aae3"),
        "morning"=>array("name"=>"Morning","cost"=>0.12,"color"=>"#ffdc00"),
        "midday"=>array("name"=>"Midday","cost"=>0.10,"color"=>"#4abd3e"),
        "evening"=>array("name"=>"Evening","cost"=>0.14,"color"=>"#c92760"),
        "overnight"=>array("name"=>"Overnight","cost"=>0.0725,"color"=>"#274e3f")
    ),
    "towerpower"=>array(
        "generation"=>array("name"=>"Solar","cost"=>0.07,"color"=>"#29aae3"),
        "morning"=>array("name"=>"Morning","cost"=>0.12,"color"=>"#ffdc00"),
        "midday"=>array("name"=>"Midday","cost"=>0.12,"color"=>"#4abd3e"),
        "evening"=>array("name"=>"Evening","cost"=>0.12,"color"=>"#c92760"),
        "overnight"=>array("name"=>"Overnight","cost"=>0.12,"color"=>"#274e3f")
    )
);

$emoncms_path = str_replace("/cydynni/","/emoncms/",$path);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Cyd Ynni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo $path; ?>images/icon/ms-icon-144x144.png">
    <meta name="theme-color" content="#006400">
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo $path; ?>images/icon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo $path; ?>images/icon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $path; ?>images/icon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo $path; ?>images/icon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $path; ?>images/icon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo $path; ?>images/icon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo $path; ?>images/icon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $path; ?>images/icon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $path; ?>images/icon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="<?php echo $path; ?>images/icon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $path; ?>images/icon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo $path; ?>images/icon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $path; ?>images/icon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo $path; ?>manifest.json">

    <!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>lib/jquery-1.11.3.min.js"></script>
    
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.time.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.selection.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/jquery.flot.stack.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>lib/flot/date.format.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>lib/vis.helper.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>lib/feed.js"></script>
    
    <link rel="stylesheet" type="text/css" href="<?php echo $path; ?>css/style.css?v=<?php echo $v; ?>" />
    
    </head>
    <body>
        <div class="oembluebar">
            <div class="oembluebar-inner">
                <div id="dashboard" class="oembluebar-item active" ><?php echo t("Dashboard"); ?></div>
                <?php if(!IS_HUB): ?><div id="reports" class="oembluebar-item"><?php echo t("Reports"); ?></div><?php endif; ?>

                <div id="logout" class="oembluebar-item" style="float:right"><img src="<?php echo $path; ?>images/logout.png" height="18px"/></div>
                <div id="account" class="oembluebar-item" style="float:right"><img src="<?php echo $path; ?>images/el-person-icon.png" height="18px"/></div>
                <div id="togglelang" class="oembluebar-item" style="float:right"></div>

            </div>
        </div>
        <div class="wrap">
            <div class="app">
                <div class="app-inner">
                    <div class="title-wrapper">
                        <img class="logo-full" src='<?php echo $path; ?>images/<?php echo t("EnergyLocalEnglish.png"); ?>'>
                        <img class="logo-mobile" src='<?php echo $path; ?>images/logo.png'>
                        <div class="app-title">
                        <div class="app-title-content"><?php echo t("Energy<br>Dashboard"); ?>
                        </div>
                    </div>
                </div>
                <ul class="navigation">
                    <li name="forecast"><div><img src="<?php echo $path; ?>images/forecast.png"><div class="nav-text"><?php echo t($club_settings["name"]."<br>Forecast"); ?></div></div></li>
                    <li name="household"><div><img src="<?php echo $path; ?>images/household.png"><div class="nav-text"><?php echo t("Your<br>Score"); ?></div></div></li>
                    <li name="club"><div><img src="<?php echo $path; ?>images/club.png"><div class="nav-text"><?php echo t("Club<br>Score"); ?></div></div></li>
                    <?php if(!IS_HUB):?>
                    <li name="tips"><div><img src="<?php echo $path; ?>images/tips.png"><div class="nav-text" style="padding-top:15px"><?php echo t("Tips"); ?></div></div></li>
                    <?php else : ?>
                    <li name="devices"><div><img src="<?php echo $path; ?>images/devices.png"><div class="nav-text" style="padding-top:15px"><?php echo t("Devices"); ?></div></div></li>
                    <?php endif; ?>
                </ul>

        <div class="page" name="forecast">
            <?php include("client_forecast_view.php") ?>
        </div>

        <div class="page" name="household">
            <?php include("client_household_view.php") ?>
        </div>
       
        <div class="page" name="club">
            <?php include("client_club_view.php") ?>
        </div>
        <?php if (!IS_HUB): ?>
        <div class="page" name="tips">
            <?php include("client_tips_view.php") ?>
        </div>
        <?php else : ?>
        <div class="page" name="devices">
            <?php include("client_devices_view.php") ?>
        </div>    
        <?php endif; ?>
        <div style="clear:both; height:85px"></div>

    </div></div>
</div>

<div class="app"><div class="app-inner">
    <div class="footer">
        <div style="float:right; font-weight:bold"><a href="mailto:mary@energylocal.co.uk"><?php echo t("Contact Us");?></a> | <a href="http://www.energylocal.co.uk/faqs/"><?php echo t("FAQ");?></a></div>
        
        <div>Energy Local</div>
    </div>
</div></div>


<div class="scheduler-template hide">
  <div class="scheduler-inner">
    <div class="scheduler-startsin"><span class='startsin'></span></div>
    <div class="scheduler-title"><?php echo t("Schedule") ?></div>

    <div class="scheduler-inner2">
      <div class="scheduler-controls">
      
        <!---------------------------------------------------------------------------------------------------------------------------->
        <!-- CONTROLS -->
        <!---------------------------------------------------------------------------------------------------------------------------->
        <div name="active" state=0 class="input scheduler-checkbox"></div>
          <div class="scheduler-checkbox-label"><?php echo t("Active") ?></div>
          <div style='clear:both'></div>
        <br>
        
        <div style="display:inline-block; width:120px;"><?php echo t("Run period") ?>:</div>
          <input class="input timepicker-hour" data-lpignore="true" type="number" min="0" max="23" step="1" name="period-hour" style="width:65px" /> <?php echo t("hrs") ?>
          <input class="input timepicker-minute" data-lpignore="true" type="number" min="0" max="59" step="30" name="period-minute" style="width:65px" /> <?php echo t("mins") ?>
        <br><br>

        <div style="display:inline-block; width:120px;"><?php echo t("Complete by") ?>:</div>
          <input class="input timepicker-hour" data-lpignore="true" type="number" min="0" max="23" step="1" name="end-hour" style="width:65px" /> : 
          <input class="input timepicker-minute" data-lpignore="true" type="number" min="0" max="59" step="30" name="end-minute" style="width:65px" />
        <br>
        <br>
        <div name="interruptible" state=0 class="input scheduler-checkbox"></div>
          <div class="scheduler-checkbox-label"><?php echo t("Ok to interrupt schedule") ?></div>
          <div style='clear:both'></div>
        <br>
        
        <div name="runonce" state=0 class="input scheduler-checkbox"></div>
          <div class="scheduler-checkbox-label"><?php echo t("Run once") ?></div>
          <div style='clear:both'></div>
        <br>
        
        <p>Repeat:</p>
        <div class="weekly-scheduler-days">
          <div name="repeat" day=0 val=0 class="input weekly-scheduler weekly-scheduler-day"><div style="padding-top:15px">Mon</div></div>
          <div name="repeat" day=1 val=0 class="input weekly-scheduler weekly-scheduler-day"><div style="padding-top:15px">Tue</div></div>
          <div name="repeat" day=2 val=0 class="input weekly-scheduler weekly-scheduler-day"><div style="padding-top:15px">Wed</div></div>
          <div name="repeat" day=3 val=0 class="input weekly-scheduler weekly-scheduler-day"><div style="padding-top:15px">Thu</div></div>
          <div name="repeat" day=4 val=0 class="input weekly-scheduler weekly-scheduler-day"><div style="padding-top:15px">Fri</div></div>
          <div name="repeat" day=5 val=0 class="input weekly-scheduler weekly-scheduler-day"><div style="padding-top:15px">Sat</div></div>
          <div name="repeat" day=6 val=0 class="input weekly-scheduler weekly-scheduler-day"><div style="padding-top:15px">Sun</div></div>
        </div>
        <br>
        <!---------------------------------------------------------------------------------------------------------------------------->
      </div>

      <button class="scheduler-save btn">Save</button><button class="scheduler-clear btn" style="margin-left:10px"><?php echo t('Clear') ?></button>
      <span id="scheduler-notification"></span>
      <br><br>
      <div class="schedule-output-heading"><div class="triangle-dropdown hide"></div><div class="triangle-pushup"></div><?php echo t('Schedule Output') ?></div>

      <div class="schedule-output-box">
        <div id="schedule-output"></div>
        <div id="placeholder_bound" style="width:100%; height:300px">
          <div id="placeholder" style="height:300px"></div>
        </div>
        <?php echo t('Higher bar height equals more power available') ?>
        
      </div> <!-- schedule-output-box -->   
      <br>
      <span class="">Demand shaper signal: </span>
      <select name="signal" class="input scheduler-select" style="margin-top:10px">
          <option value="carbonintensity">UK Grid Carbon Intensity</option>
          <option value="cydynni">Energy Local: Bethesda</option>
          <option value="economy7">Economy 7</option>
      </select>   
    </div> <!-- schedule-inner2 -->
  </div> <!-- schedule-inner -->
</div>

<!-- The Modal -->
<div id="DeviceDeleteModal" class="modal">
  <!-- Modal content -->
  <div class="modal-content">
    <span class="device-delete-modal-cancel modal-close">&times;</span>
    <h3>Delete Device</h3>
    <p>Are you sure you want to delete device <span id="device-delete-modal-name"></span>?</p>
    <button class="device-delete-modal-cancel btn">Cancel</button> <button class="device-delete-modal-delete btn">Delete</button>
  </div>
</div>


<script>
var path = "<?php echo $path; ?>";
var club = "<?php echo $club; ?>";
var club_path = [path, club, '/'].join('');
var is_hub = <?php echo IS_HUB ? 'true':'false'; ?>;
</script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/cydynnistatus.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/pie.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/household.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/club.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/user.js?v=<?php echo $v; ?>"></script>
<?php if(IS_HUB): ?>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/devices.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $emoncms_path; ?>Modules/demandshaper/scheduler.js?v=<?php echo $v; ?>"></script>
<?php endif; ?>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/jquery.history.js"></script>

<script>
var club_settings = <?php echo json_encode($club_settings);?>;
var emoncmspath = window.location.protocol+"//"+window.location.hostname+"/emoncms/";

var generation_feed = club_settings.generation_feed;
var consumption_feed = club_settings.consumption_feed;
var languages = club_settings.languages;
var session = <?php echo json_encode($session); ?>;

// Device 
<?php if (IS_HUB): ?>
auth_check();
<?php endif; ?>

var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";

var tariffs = <?php echo json_encode($tariffs[$club]); ?>;

// Language selection top-right

if (languages.length>1) {
    if (lang=="cy") {
        $("#togglelang").html("English");
    } else {
        $("#togglelang").html("Cymraeg");
    }
}

if (!session.write) {
  $("#login-block").show();
  $(".household-block").hide();
  
  $("#account").hide();
  $("#logout").hide();
  $("#reports").hide();
} else {
  $("#login-block").hide();
  $(".household-block").show();
  
  $("#logout").show();
  $("#account").show();
  $("#reports").show();
}

//show tab related to the page name shown after the ? (or show first tab)
var url_string = location.href
var url = new URL(url_string);

console.log(session);

var page = "";

if (url.searchParams!=undefined) {
    var entries = url.searchParams.entries();
    for(var entry of entries) { if(entry[0]!=="lang") page = entry[0]; }
} else {
    page = url.search.replace("?","");
}

if (page!=""){
    show_page(page);
}else{
    show_page("forecast");
}

$(".navigation li").click(function() {
    var page = $(this).attr("name");
    History.pushState({}, page, "?"+page);  
});

$(".block-title").click(function() {
    $(this).parent().find(".block-content").slideToggle("slow");
    $(this).find(".triangle-dropdown").toggle();
    $(this).find(".triangle-pushup").toggle();
});

function show_page(page) {

    // Highlighted selected menu
    $(".navigation li > div").removeClass("active");
    $(".navigation li[name="+page+"] > div").addClass("active");
    // Show relevant page
    $(".page").hide();
    $(".page[name="+page+"]").show();

    if (page=="forecast") {
        club_pie_draw();
        club_bargraph_resize();
    }
    
    if (page=="household") {
        household_pie_draw();
        household_bargraph_resize();
        household_powergraph_draw();
    }
}

$(window).resize(function(){
    resize();
});

function resize() {
    window_height = $(window).height();
    window_width = $(window).width();
    
    club_pie_draw();
    club_bargraph_resize();
    
    household_pie_draw();
    household_bargraph_resize();
    household_powergraph_draw();
}

// Flot
var flot_font_size = 12;
var previousPoint = false;

cydynnistatus_update();

club_summary_load();
club_bargraph_load();

if (session.write) {
    household_summary_load();
    household_bargraph_load();
<?php if (IS_HUB): ?>
    device_load();
<?php endif; ?>
}

resize();
// ----------------------------------------------------------------------
// Translation
// ----------------------------------------------------------------------

// Language selection
$("#togglelang").click(function(){
    var ilang = $(this).html();
    if (ilang=="Cymraeg") {
        $(this).html("English");
        window.location = "?lang=cy";
    } else {
        $(this).html("Cymraeg");
        lang="cy";
        window.location = "?lang=en";
    }
});

// ----------------------------------------------------------------------
// Tips
// ----------------------------------------------------------------------

$(".leftclick").click(function(){
    $(".figholder").removeClass("figholder");
    $(".show-fig").removeClass("show-fig").addClass("figholder");
        if ( $(".figholder").prev().hasClass("tips-appliance") ) {
            $(".figholder").prev().addClass("show-fig");
        }
        else {
            $(".tips-appliance:last").addClass("show-fig");
        }
});

$(".rightclick").click(function(){
    $(".figholder").removeClass("figholder");
    $(".show-fig").removeClass("show-fig").addClass("figholder");
        if ( $(".figholder").next().hasClass("tips-appliance") ) {
            $(".figholder").next().addClass("show-fig");
        }
        else {
            $(".tips-appliance:first").addClass("show-fig");
        }
});

$("#dashboard").click(function(){ window.location = path+club+"?lang="+lang; });
$("#reports").click(function(){ window.location = path+club+"/report?lang="+lang; });
$("#account").click(function(){ window.location = path+club+"/account?lang="+lang; });

// Javascript text translation function
function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}

function ucfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Bind to StateChange Event
History.Adapter.bind(window,'statechange',function(){ // Note: We are using statechange instead of popstate
    var State = History.getState(); // Note: We are using History.getState() instead of event.state
    show_page(State.title);
});

</script>

</body>
</html>
