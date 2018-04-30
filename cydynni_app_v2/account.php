<?php global $path, $translation, $lang; 
$v=1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>CydYnni Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="<?php echo $path; ?>style.css" />
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>lib/jquery-1.11.3.min.js"></script>
</head>

<body>
  <div class="oembluebar">
    <div class="oembluebar-inner">
      <div id="dashboard" class="oembluebar-item"><?php echo t("Dashboard"); ?></div>
      <div id="reports" class="oembluebar-item"><?php echo t("Reports"); ?></div>

      <div id="logout" class="oembluebar-item" style="float:right"><img src="<?php echo $path; ?>images/logout.png" height="18px"/></div>
      <div id="account" class="oembluebar-item" style="float:right"><img src="<?php echo $path; ?>images/el-person-icon.png" height="18px"/></div>
      <div id="togglelang" class="oembluebar-item" style="float:right"></div>      
    </div>
  </div>
  
  <div class="page">
    <div class="block"><br><br><br>
      <div style="font-weight:bold; font-size:32px"><?php echo t("My Account");?></div>
      <p><b><?php echo t("Email"); ?>:</b><br><span id="user-email"></span></p>
      <br>
      <p><b><?php echo t("Change password"); ?></b><br>
      <p><?php echo t("Current password"); ?><br>
      <input id="change-password-current" type="password"></p>
      <p><?php echo t("New password"); ?><br>
      <input id="change-password-new" type="password"></p>
      <p><?php echo t("Confirm new password"); ?><br>
      <input id="change-password-new-confirm" type="password"></p>  
      <button id="change-password" class="btn"><?php echo t("Change"); ?></button>   
      <span id="change-password-alert" style="padding-left:10px"></span>   
    </div>
  </div>
  
</body>

</html>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/user.js?v=<?php echo $v; ?>"></script>

<script>
var path = "<?php echo $path; ?>";
var club_name = "<?php echo $club; ?>";
var translation = <?php echo json_encode($translation,JSON_HEX_APOS);?>;
var lang = "<?php echo $lang; ?>";
var session = <?php echo json_encode($session); ?>;

// Language selection top-right
if (lang=="cy") {
    $("#togglelang").html("English");
} else {
    $("#togglelang").html("Cymraeg");
}

if (!session.write) {
  $("#logout").hide();
  $("#account").hide();
} else {
  $("#logout").show();
  $("#account").show();
  $("#user-email").html(session.email);
}

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

$("#logout").click(function(event) {
    event.stopPropagation();
    $.ajax({                   
        url: path+"/logout",
        dataType: 'text',
        success: function(result) {
            window.location = "/";
        }
    });
});

$("#dashboard").click(function(){ window.location = path+club_name+"?lang="+lang; });
$("#reports").click(function(){ window.location = path+club_name+"/report?lang="+lang; });
$("#account").click(function(){ window.location = path+club_name+"/account?lang="+lang; });

function t(s) {
    if (translation[lang]!=undefined && translation[lang][s]!=undefined) {
        return translation[lang][s];
    } else {
        return s;
    }
}
</script>
