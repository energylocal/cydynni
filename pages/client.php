<?php global $path, $translation, $lang; 

$v = 1;

?>

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
    <link rel="stylesheet" type="text/css" href="theme/style.css?v=<?php echo $v; ?>" />
    <link rel="stylesheet" type="text/css" href="theme/forms.css?v=<?php echo $v; ?>" />
    <link rel="stylesheet" type="text/css" href="theme/buttons.css?v=<?php echo $v; ?>" />
    <!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
  </head>
  <body>

  <?php 
  echo view("pages/hydro.php",array());
  echo view("pages/household.php",array());
  echo view("pages/myaccount.php",array());
  echo view("pages/community.php",array());
  echo view("pages/tips.php",array());
  ?>
  
  <div class="icon-bar" style="">
    <a class="icon-bar-item" title="Ok to Use?" page="hydro" href="#hydro"><img src="images/el-clock-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="My Household" page="household" href="#household"><img src="images/el-person-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="Community performance" page="community" href="#community"><img src="images/el-group-icon.png" style="width:22px"></a>
    <a class="icon-bar-item" title="Tips" page="tips" href="#tips"><img src="images/el-bulb-icon.png" style="width:22px"></a>
  </div>

  </body>
</html>

<script language="javascript" type="text/javascript" src="js/bargraph.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/pie.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/user.js?v=<?php echo $v; ?>"></script>

<script language="javascript" type="text/javascript" src="js/cydynnistatus.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/hydro.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/household.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/community.js?v=<?php echo $v; ?>"></script>
<script language="javascript" type="text/javascript" src="js/tips.js?v=<?php echo $v; ?>"></script>

<script>

var path = "<?php echo $path; ?>";
var session = JSON.parse('<?php echo json_encode($session); ?>');
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";

// Navigation control
var req = parse_location_hash(window.location.hash);
// Default page
var page = "hydro";
// Valid pages
if (req[0]=="household") page = "household";
if (req[0]=="community") page = "community";
if (req[0]=="tips") page = "tips";
if (req[0]=="myaccount") page = "myaccount";
// Auto redirect if not logged in
if (page=="myaccount" && !session) {
    page = "household";
    window.location = "#household";
}

var panel_summary_show_width = 450;
var tipid = 1;
var pagesectionheight = 64;
var iconbarheight = 52;
var panel_height = $(window).height() - pagesectionheight*3 - iconbarheight;
window_width = $(window).width();

// -----------------------------------------------------------------------
// View initialisation
// -----------------------------------------------------------------------
// Email address on household account page
$("#user-email").html(session.email);
// Language selection top-right
if (lang=="cy") {
    $(".togglelang").html("EN");
} else {
    $(".togglelang").html("CY");
}

// 1. Hide all pages
$(".page").hide();
// 2. Show selected page
$(".page[page="+page+"]").show();
// 3. Set starting height and active status of first section for each page
$(".page[page=hydro] .panel").first().height(panel_height).attr("active",1);
$(".page[page=household] .panel").first().height(panel_height).attr("active",1);
$(".page[page=community] .panel").first().height(panel_height).attr("active",1);
$(".page[page=tips] .panel").first().height(panel_height+pagesectionheight*2).attr("active",1);
$(".page[page=myaccount] .panel").first().height(panel_height+pagesectionheight*2).attr("active",1);
// 4. Hide panel summaries for first section - as these sections are open
$(".page").each(function() {
   $(this).find(".panel-summary").first().hide();
});

// Household page view control
if (!session) {
  $("#household-status-block").hide();
  $(".logout").hide();
  $(".myaccount").hide();
} else {
  $("#login-block").hide();
  $(".logout").show();
  household_load();
  setInterval(household_load,60000);
}

hydro_load();
community_load();
resize();

// cydynni.js
cydynnistatus_update();
setInterval(cydynnistatus_update,10000);

setInterval(community_load,10000);

// -----------------------------------------------------------------------
// Navigation and page selection
// -----------------------------------------------------------------------

// Page navigation
$(".icon-bar-item").click(function(){
    page = $(this).attr("page");
    // Hide all pages
    $(".page").hide();
    // Show selected page
    $(".page[page="+page+"]").show();
    draw_panel();
});

// Page section navigation (typically 3 per page)
$(".pagesection").click(function() {
    if (page=="household" && !session) {
        // User login page
    } else if (page=="myaccount") {
        // My account page
    } else {
        // Hide and disable all panels
        $(".page[page="+page+"] .panel").attr("active",0);
        $(".page[page="+page+"] .panel").height(0);
        
        $(".panel-summary").show();
        $(this).find(".panel-summary").hide();
        
        // Show only clicked panel
        $(this).next().attr("active",1);

        panel_height = $(window).height() - pagesectionheight*3 - iconbarheight;
        $(this).next().height(panel_height);

        draw_panel();
    }
});

// Language selection
$(".togglelang").click(function(){
    var ilang = $(this).html();
    if (ilang=="CY") {
        $(this).html("EN");
        window.location = "?lang=cy#"+page;
    } else {
        $(this).html("CY");
        lang="cy";
        window.location = "?lang=en#"+page;
    }
});

$(window).resize(function(){
    resize();
});

function resize() {
    panel_height = $(window).height() - pagesectionheight*3 - iconbarheight;
    window_width = $(window).width();
    if (panel_height<200) panel_height = 200;
    
    if (window_width<450) {
      $(".summary_bound").hide();
    } else {
      $(".summary_bound").show();
    }

    $(".panel[active=1]").height(panel_height);
    $(".page[page=tips] .panel[active=1]").height(panel_height+pagesectionheight*2);
    $(".page[page=myaccount] .panel[active=1]").height(panel_height+pagesectionheight*2);
    draw_panel();
    
    var ph = (panel_height - $("#panel-inner-status").height())*0.3;
    if (ph<20) ph=0;
    $("#panel-inner-status").css("margin-top",ph);
}

// -----------------------------------------------------------------------
// Core functions
// -----------------------------------------------------------------------

function draw_panel() {
    if (page=="hydro") hydro_resize(panel_height);
    if (page=="household") household_resize(panel_height);
    if (page=="community") community_resize(panel_height);
}

// Javascript text translation function
function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}

// Parse location hash
function parse_location_hash(hash)
{
    hash = hash.substring(1);
    hash = hash.replace("?","/");
    hash = hash.replace("&","/");
    hash = hash.split("/");
    return hash;
}

</script>
