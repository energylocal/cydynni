<?php

global $path, $translation, $lang;
// for live :
//$v = 64;
// for dev :
$v = time();

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
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


<script src="<?php echo $path; ?>Lib/moment.min.js"></script>
<script>
    var _user = {lang:"<?php isset($_SESSION['lang'])?$_SESSION['lang']:''; ?>"};
</script>
<script src="<?php echo $path; ?>Lib/user_locale.js"></script>

<script type="text/javascript" src="<?php echo $app_path; ?>js/vis.helper.js"></script>
<script type="text/javascript" src="<?php echo $app_path; ?>js/feed.js"></script>

<div class="app">
    <ul class="navigation">
        <?php if ($club_settings["has_generator"]) { ?>
          <li name="forecast"><div><img src="<?php echo $app_path; ?>images/forecast.png"><div class="nav-text"><?php echo t($club_settings["name"]."<br>Overview"); ?></div></div></li>
        <?php } ?>
        <li name="household"><div><img src="<?php echo $app_path; ?>images/household.png"><div class="nav-text"><?php echo t("Your<br>Household"); ?></div></div></li>
        <?php if ($club_settings["has_generator"]) { ?>
          <li name="club" ><div><img src="<?php echo $app_path; ?>images/club.png"><div class="nav-text"><?php echo t("Your<br>Club"); ?></div></div></li>
        <?php } ?>
        <li name="tips"><div><img src="<?php echo $app_path; ?>images/tips.png"><div class="nav-text" style="padding-top:15px"><?php echo t("Tips"); ?></div></div></li>
    </ul>


    <?php if ($club_settings["has_generator"]) { ?>
    <div class="page" name="forecast">
        <?php echo view("Modules/club/app/client_forecast_view.php", array(
            'app_path'=>$app_path,
            'club'=>$club,
            'club_settings'=>$club_settings,
            'tariffs_table'=>$tariffs_table,
            'concise_tariffs_table' => $concise_tariffs_table,
            'is_advisor'=>$is_advisor
        )); ?>
    </div>
    <?php } ?>

    <div class="page" name="household">
        <?php echo view("Modules/club/app/client_household_view.php", array(
            'app_path'=>$app_path,
            'club'=>$club,
            'club_settings'=>$club_settings,
            'tariffs'=>$tariffs,
            'user_attributes'=>$user_attributes,
            'tariffs_table'=>$tariffs_table,
            'concise_tariffs_table' => $concise_tariffs_table,
        )); ?>
    </div>
   
    <div class="page" name="club">
        <?php echo view("Modules/club/app/client_club_view.php", array(
            'app_path'=>$app_path, 
            'club'=>$club,
            'club_settings'=>$club_settings,
            'tariffs'=>$tariffs
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
// menu.hide_l2();
var path = "<?php echo $path; ?>";
var app_path = "<?php echo $app_path; ?>";
var club = "<?php echo $club; ?>";
var club_path = [path, club, '/'].join('');
var is_hub = false;
var clubid = <?php echo $clubid; ?>;

var club_settings = <?php echo json_encode($club_settings);?>;

var tariff_standing_charge = <?php echo $standing_charge; ?>;
var tariffs = <?php echo json_encode($tariffs); ?>;

var available_reports = <?php echo json_encode($available_reports); ?>;

var tariff_colors = {
    "overnight": "#014c2d",
    "morning": "#ffdc00",
    "midday": "#ffb401",
    "daytime": "#ffb401",
    "evening": "#e6602b",
    "standard": "#c20000" //"#ffb401"
}

var months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
var months_long = ["January","February","March","April","May","June","July","August","September","October","November","December"];

var session = <?php echo json_encode($session); ?>;
var targetMax = <?php echo isset($user_attributes->targetMax) ? $user_attributes->targetMax : 0; ?>;
var targetMin = <?php echo isset($user_attributes->targetMin) ? $user_attributes->targetMin : 0; ?>;

</script>

<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/clubstatus.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/pie.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/household.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/household_settings.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/club.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/user.js?v="></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/jquery.history.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $app_path; ?>js/comparison.js?v=<?php echo $v; ?>"></script>


<script>
var emoncmspath = window.location.protocol+"//"+window.location.hostname+"/emoncms/";

var generation_feed = club_settings.generation_feed;
var consumption_feed = club_settings.consumption_feed;
var demandshaper_feed = club_settings.demandshaper_feed;
var languages = club_settings.languages;
var session = <?php echo json_encode($session); ?>;

var generator_color = '<?php echo $club_settings["generator_color"]; ?>';
var export_color = '<?php echo $club_settings["export_color"]; ?>';

var apikeystr = "";
if (session.read) {
    apikeystr = "&apikey="+session.apikey_read;
}

var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";

if (!session.read) {
  $("#login-block").show();
  $(".household-block").hide();
  
  $("#account").hide();
  $("#logout").hide();
  $("#reports").hide();
} else {
  $("#login-block").hide();
  $(".historic-block").show();
  
  $("#logout").show();
  $("#account").show();
  $("#reports").show();
}

//show tab related to the page name shown after the ? (or show first tab)
var url_string = location.href
var url = new URL(url_string);

var page = "";
var token = "";

if (url.searchParams!=undefined) {
    var entries = url.searchParams.entries();
    // for each param/value pair
    for (var entry of entries) { 
        // if param name isn't language or reset, must be page
        if (entry[0]!=="lang" && entry[0]!=="reset") {
            page = entry[0]; 
        }
        // if param name is reset, set reset token value
        if (entry[0]=="reset" && entry[1]) {
            token = entry[1];
        }
    }
} else {
    page = url.search.replace("?","");
}

if (page=="") {
  if (club_settings.has_generator) {
    page = "forecast";
  } else {
    page = "household";
  }
}

if (page=="forecast") {
  if (club_settings.has_generator) {
    show_page("forecast");
  } else {
    show_page("household");
  }
}
else if (page=="household") show_page("household");
else if (page=="reset") show_page("password-reset");
else if (page=="club") show_page("club");
else if (page=="tips") show_page("tips");
else show_page("forecast");

// if a password reset token is detected in the url:
if (token!=="") {
    // check that token is valid
    $.ajax({                                      
        url: path+"cydynni/passwordreset_check_token",                         
        data: "token="+token,
        dataType: 'json',
        success: function(result) {
            // if token exists and hasn't expired
            if (result['success'] == true) {
                $("#login-block").hide();
                $("#passwordreset-block-new").show(); 
                user_id = result['user_id']
            // if token has expired
            } else if (result.token_expired) {
                    $("#login-block").hide();
                    $("#passwordreset-block-new").show(); 
                    $("#passwordreset-new-input").hide();
                    $("#passwordreset-new-title").html(t("Password reset link has expired. Please repeat the password reset process to receive a new one."));
            // if token does not exist
            } else if (!result.token_exists) {
                $("#login-block").hide();
                $("#passwordreset-block-new").show(); 
                $("#passwordreset-new-input").hide();
                $("#passwordreset-new-title").html(t("Incorrect password reset link. Please verify that you have copied this link correctly."));
            }
        }
    }) 
}

$(".navigation li").click(function() {
    var page = $(this).attr("name");
    History.pushState({}, page, "?"+page);  
});

$(".block-title.hideable-block").click(function() {
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

    $("#lang-link-cy").attr("href", "?lang=cy&"+page);
    $("#lang-link-en").attr("href", "?lang=en&"+page);

    if (page=="forecast") {
        club_pie_draw();
        setTimeout(function(){
            // delaying this to run so that the translations have a chance to load (set to 0 seconds it still works!!??)
            club_bargraph_resize();
        }, 0)
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

// Fetch start time of consumption data
date_selected = "fortnight";
var out = '<option value="custom" style="display:none">'+t("Custom")+'</option>';
var period_select_options = ["week","fortnight","month","year"];
for (var z in period_select_options) {
    out += '<option value="'+period_select_options[z]+'">'+t(ucfirst(period_select_options[z]))+'</option>';
}

if (available_reports.length>0) {
    out = '<optgroup label="Last">'+out+'</optgroup>';
    out += '<optgroup label="Reports">';
    for (var z=available_reports.length-1; z>=0; z--) {
        var parts = available_reports[z].split('-');
        description = months[parts[1]-1]+" "+parts[0];
        out += '<option value="'+available_reports[z]+'">'+t(description)+'</option>';
    }
    out += '</optgroup>';
}

$(".period-select").html(out);
$(".period-select").val(date_selected);

// Flot
var flot_font_size = 12;
var previousPoint = false;

clubstatus_update();

club_summary_load();
club_bargraph_load();
// generate inner HTML for tariffs table body, insert into table
var tariffsTableHTML = generateTariffsTableHTML(1.05);
insertTariffsTableHTML(tariffsTableHTML)

if (session.read) {
    household_summary_load();
    household_bargraph_load();
    household_comparison_bargraph_load();
}

resize();

// ----------------------------------------------------------------------
// Period selection
// ----------------------------------------------------------------------
$(".period-select").click(function(event) {
    event.stopPropagation();
});

document.querySelectorAll('.household-view-scope button').forEach(button => {
  button.addEventListener('click', (e) => {
    const currentDate = new Date();
    switch (button.value) {
    case "historic":
      $("#your-score").show();
      $("#historic-period-select").show();
      $("#your-usage").show();
      $("#realtime-power").hide();
      $("#comparison").hide();
      $("#tariff-settings").hide();
      break;
    case "live":
      household_realtime_load();
      $("#historic-period-select").hide();
      $("#your-score").hide();
      $("#your-usage").hide();
      $("#comparison").hide();
      $("#tariff-settings").hide();
      break;
    case "comparison":
      $("#historic-period-select").show();
      $(".historic-block").hide();
      $("#your-score").hide();
      $("#realtime-power").hide();
      $("#tariff-settings").hide();
      $("#comparison").show();
      break;
    case 'tariff-settings':
      $("#historic-period-select").hide();
      $("#your-usage").hide();
      $("#realtime-power").hide();
      $("#comparison").hide();
      $("#tariff-settings").show();
      break;
    default:
      alert("Household view scope '"+button.value+"' not supported.");
    }
  })
})


function daysInMonth (year, month) { // Use 0 for January, 1 for February, etc.
  return new Date(year, month+1, 0).getDate();
}

$(".period-select").change(function(event) {
    event.stopPropagation();

    date_selected = $(this).val();
    view.end = +new Date;

    var period_length = 3600000*24.0*30;

    var club_date_text = t("In the last %s, we scored:").replace('%s', t(date_selected));
    var household_date_text = t("In the last %s, you scored:").replace('%s', t(date_selected));

    switch (date_selected) {
        case "day": view.start = view.end - (3600000*24.0*1); break;
        case "week": view.start = view.end - (3600000*24.0*7); break;
        case "fortnight": view.start = view.end - (3600000*24.0*14); break;
        case "month": view.start = view.end - (3600000*24.0*30); break;
        case "year": view.start = view.end - (3600000*24.0*365); break;
        default:
            var parts = date_selected.split('-');
            var month = (parts[1]*1)-1;
            var year = parts[0]*1;

            var date = new Date();
            date.setHours(0);
            date.setMinutes(0);
            date.setSeconds(0);
            date.setMilliseconds(0);
            date.setDate(1);
            date.setMonth(month);
            date.setYear(year);
            view.start = date.getTime();

            date.setDate(daysInMonth(year, month));
            view.end = date.getTime();

            club_date_text = t("In %s, we scored:").replace('%s', t(months_long[parts[1]-1])+" "+parts[0]);
            household_date_text = t("In %s, you scored:").replace('%s', t(months_long[parts[1]-1])+" "+parts[0]);
    }

    club_bargraph_load();
    club_bargraph_draw();
    $(".period-select").val(date_selected);
    
    $(".club_date").html(club_date_text);
    $(".household_date").html(household_date_text);
    
    // Copy to household
    household_bargraph_load();
    household_comparison_bargraph_load();
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
