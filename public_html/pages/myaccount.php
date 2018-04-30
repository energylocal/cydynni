<div class="page" page="myaccount" style="display:none">
  <div class="pagesection" style="color:rgb(41,171,226)">
    <div style="height:10px; background-color:rgb(41,171,226)"></div>
    <div class="togglelang">CY</div>
    <div class="logout" style="float:right; padding-top:14px; padding-right:14px">
      <img src="images/logout.png" style="width:24px"/>
    </div>
    <div class="title">
      <?php echo t("My Account");?>
    </div>
  </div>
  <div class="panel" style="color:rgb(41,171,226)">
    <div class="panel-inner">
      <div style="text-align:left">
      <p><b>Email:</b><br><span id="user-email"></span></p>
      <br>
      <p><b><?php echo t("Change password");?></b><br>
      <p><?php echo t("Current password");?><br>
      <input id="change-password-current" type="password"></p>
      <p><?php echo t("New password");?><br>
      <input id="change-password-new" type="password"></p>
      <p><?php echo t("Confirm new password"); ?><br>
      <input id="change-password-new-confirm" type="password"></p>  
      <button id="change-password" class="btn"><?php echo t("Change");?></button>   
      <span id="change-password-alert" style="padding-left:10px"></span>   
      </div>
    </div>
  </div>
</div>
