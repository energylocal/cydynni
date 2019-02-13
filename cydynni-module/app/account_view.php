<?php global $path, $translation, $lang; 
$v=1;
?>
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
      
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>js/user.js?v=<?php echo $v; ?>"></script>
