<?php global $path, $translation, $lang; 
$v=1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>CydYnni Hub Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
</head>

  <body>
  
    <div class="oembluebar">
        <div class="oembluebar-inner">
            <div id="togglelang" class="oembluebar-item" style="float:right"></div>
        </div>
    </div>
    
  <div class="page">
    <div class="block"><br><br><br>
    
    <div id="login-block">
      <br><br>
      <div class="login-box">
        
        <?php if ($register) { ?>
        <h2>Welcome to your Cydynni Hub</h2>
        <p>Please enter your CydYnni online account details</p>
        <?php } else { ?>
        <h2>Please Login</h2>
        <?php } ?>
        
        <p>
          <input id="username" type="text" placeholder="Username..."><br><br>
          <input id="password" type="password" placeholder="Password..."><br><br>

          <button id="login" class="btn">Login</button>
        </p>
        <div id="alert"></div>
      </div>
    </div>
    
    <div id="admin-block" style="display:none">
      
    </div>

    <div id="wait-block" style="display:none">
        <h2>Account setup successful!</h2>
        <p>Please wait 5s while the hub downloads your data...</p>
    </div>
        
    </div>
    </div>
  
  </body>
</html>

<script>
var path = "<?php echo $path; ?>";
var session = <?php echo json_encode($session); ?>;
var register = <?php echo $register*1; ?>;

$("#login").click(function() {
    var username = $("#username").val();
    var password = $("#password").val();
    
    if (register) {
        console.log("register: "+username+" "+password);
        $.ajax({ type: 'POST', url: path+"register", data: "username="+username+"&password="+password, dataType: 'json', async: false, success: function(result){
            if (result.success) {
                $("#login-block").hide();
                $("#wait-block").show();
                
                setTimeout(function(){ window.location = path; },5000);
                
            } else {
                $("#alert").html(result.message);
            }
        }});
    } else {
        console.log("login: "+username+" "+password);
        $.ajax({ type: 'POST', url: path+"login", data: "username="+username+"&password="+password, dataType: 'json', async: false, success: function(result){
            if (result.success) {
                window.location = path;
            } else {
                $("#alert").html(result.message);
            }
        }});
    }
    
});

</script>
