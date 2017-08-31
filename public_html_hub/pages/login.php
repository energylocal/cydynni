<?php global $path, $session; ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyd Ynni</title>

    <link rel="stylesheet" type="text/css" href="theme/style.css" />
    <link rel="stylesheet" type="text/css" href="theme/forms.css" />
    <link rel="stylesheet" type="text/css" href="theme/buttons.css" />
    <link rel="stylesheet" type="text/css" href="theme/admin.css" />
    <!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
    
    
    <style>
      .container {
        width: 95%;
      }
      
    </style> 
   
  </head>
  <body>
  
    <div class="admin-top-nav">
      <div class="container">
        <div class="admin-top-nav-title">CydYnni App</div>
      </div>
    </div>
    
    <div class="container">
    <div class="inner">
    
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
                window.location = path;
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
