<?php

global $path, $translation, $lang, $tariffs;
$v = 30;

$app_path = $path."Modules/club/app/";

?>
<style>body { line-height:unset !important; }</style>
<link rel="stylesheet" type="text/css" href="<?php echo $app_path; ?>css/style.css?v=<?php echo $v; ?>" />
<!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->    
<script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.min.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.time.min.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.stack.min.js"></script>
<script type="text/javascript" src="<?php echo $path; ?>Lib/flot/date.format.js"></script>
<script type="text/javascript" src="<?php echo $app_path; ?>js/vis.helper.js"></script>
<script type="text/javascript" src="<?php echo $app_path; ?>js/feed.js"></script>

<div class="app">
    <ul class="navigation">
        <li name="forecast"><div><img src="<?php echo $app_path; ?>images/forecast.png"><div class="nav-text"><?php echo t($club_settings["name"]."<br>Forecast"); ?></div></div></li>
        <li name="household"><div><img src="<?php echo $app_path; ?>images/household.png"><div class="nav-text"><?php echo t("Your<br>Score"); ?></div></div></li>
        <li name="club"><div><img src="<?php echo $app_path; ?>images/club.png"><div class="nav-text"><?php echo t("Club<br>Score"); ?></div></div></li>
        <li name="tips"><div><img src="<?php echo $app_path; ?>images/tips.png"><div class="nav-text" style="padding-top:15px"><?php echo t("Tips"); ?></div></div></li>
    </ul>

    <div class="page" name="forecast">
        <?php echo view("Modules/club/app/client_forecast_view.php", array(
            'app_path'=>$app_path, 
            'club'=>$club,
            'tariffs'=>$tariffs,
            'club_settings'=>$club_settings,
            'tariffs_table'=>$tariffs_table
        )); ?>
    </div>

    <div class="page" name="household">
        <?php echo view("Modules/club/app/client_household_view.php", array(
            'app_path'=>$app_path, 
            'club'=>$club,
            'tariffs'=>$tariffs,
            'club_settings'=>$club_settings
        )); ?>
    </div>
   
    <div class="page" name="club">
        <?php echo view("Modules/club/app/client_club_view.php", array(
            'app_path'=>$app_path, 
            'club'=>$club,
            'tariffs'=>$tariffs,
            'club_settings'=>$club_settings
        )) ?>
    </div>
    
    <div class="page" name="tips">
        <?php echo view("Modules/club/app/client_tips_view.php", array(
            'app_path'=>$app_path, 
            'club_settings'=>$club_settings
        )) ?>
    </div>

    <div class="footer">
        <div style="float:right; font-weight:bold"><a href="mailto:mary@energylocal.co.uk"><?php echo t("Contact Us");?></a> | <a href="http://www.energylocal.co.uk/faqs/"><?php echo t("FAQ");?></a></div>
        <div>Energy Local</div>
        <div style="float:right; font-weight:normal; font-size:12px; padding-top:5px"><a href="https://github.com/energylocal">Open Source on GitHub</a></div>
        <div style="font-weight:normal; font-size:14px; padding-top:25px"><a href="<?php echo $path; ?>find"><i class="icon-search icon-white"></i> <?php echo t("Find Devices"); ?></a></div>
    </div>

</div>

<script>
var path = "<?php echo $path; ?>";
var app_path = "<?php echo $app_path; ?>";
var club = "<?php echo $club; ?>";
var club_path = [path, club, '/'].join('');
var is_hub = <?php echo $is_hub ? 'true':'false'; ?>;
</script>

<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/clubstatus.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/pie.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/household.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/club.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/user.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/jquery.history.js"></script>

<script>
var club_settings = <?php echo json_encode($club_settings);?>;
var emoncmspath = window.location.protocol+"//"+window.location.hostname+"/emoncms/";

var generation_feed = club_settings.generation_feed;
var consumption_feed = club_settings.consumption_feed;
var languages = club_settings.languages;
var session = <?php echo json_encode($session); ?>;

var generator_color = '<?php echo $club_settings["generator_color"]; ?>';


var apikeystr = "";
if (session.read) {
    apikeystr = "&apikey="+session.apikey_read;
}

var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";

var tariffs <?php echo isset($tariffs[$club]) ? '='.json_encode($tariffs[$club]): ''; ?>;
// Language selection top-right

if (languages.length>1) {
    if (lang=="cy_GB") {
        $("#togglelang").html("English");
    } else {
        $("#togglelang").html("Cymraeg");
    }
}

if (!session.read) {
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

if (page=="") page = "forecast";

if (page=="forecast") show_page("forecast");
else if (page=="household") show_page("household");
else if (page=="club") show_page("club");
else if (page=="tips") show_page("tips");
else show_page("forecast");

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

        var combined_data = [].concat(household_result, household_data);
        var data_available = combined_data.length > 0;
        
        $('#your-score, #your-usage, #your-usage-price').toggleClass('hide', !data_available);
        $('#missing-data-block').toggleClass('hide', session.admin !== 1 || data_available);
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

clubstatus_update();

club_summary_load();
club_bargraph_load();

if (session.read) {
    household_summary_load();
    household_bargraph_load();
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
        lang="cy_GB";
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
