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
  <body style="background-color:#29abe2; color: #fff; text-align:left">
    
    <div class="container">
    <br><br>
    <div id="login-block">
      
      <div class="login-box">
        <h2>Admin Login</h2>
        <p>
          <input id="email" type="text" placeholder="Email..."><br><br>
          <input id="password" type="password" placeholder="Password..."><br><br>

          <button id="login" class="btn">Login</button>
        </p>
        <div class="alert"></div>
      </div>
    </div>
    
    <div id="admin-block" style="display:none">
      <div id="logout">Logout</div>
      <div class="title">Admin</div>
      
      <div style="padding:20px; color:#fff">
      
        <table class="table">
          <tr><th>User ID</th><th>Email</th><th>Apikey</th><th>Feedid</th><th>Admin</th></tr>
          <tbody id="users"></tbody>
        </table>
        <br><br>
        <p><b>Create new user:</b></p>
        <p>
          <input id="register-email" type="text" placeholder="Email..."><br><br>
          <input id="register-password" type="password" placeholder="Password..."><br><br>
          <input id="apikey" type="text" placeholder="Emoncms.org read apikey"><br><br>
          <input id="feedid" type="text" placeholder="Emoncms.org consumption feedid"><br><br>
          <button id="register" class="btn">Create account</button>
        </p>
        <div class="alert"></div>

      </div>
    </div>
    
    </div>
  
  </body>
</html>

<script>
var path = "<?php echo $path; ?>";
var session = JSON.parse('<?php echo json_encode($session); ?>');

if (session.admin) load();

function load() {
    $("#login-block").hide();
    $("#admin-block").show();

    $.ajax({
        url: path+"admin/users",
        dataType: 'json',
        success: function(result) {
            var out = "";
            for (var z in result) {
                out += "<tr><td>"+result[z].id+"</td><td>"+result[z].email+"</td><td>"+result[z].apikey+"</td><td>"+result[z].feedid+"</td><td>"+result[z].admin+"</td></tr>";
            }
            $("#users").html(out);
        }
    });
}

$("#login").click(function() {
    var email = $("#email").val();
    var password = $("#password").val();

    $.ajax({
        url: path+"/login",
        data: "email="+email+"&password="+password,
        dataType: 'json',
        success: function(result) {
            if (result.userid!=undefined) {
                session = result;
                $(".alert").html("");
                if (session.admin) {
                    load();
                } else {
                    logout();
                    $(".alert").html("Administrator access only");
                }
            } else {
                $(".alert").html(result);
            }
        }
    });
});

$("#register").click(function() {
    var email = $("#register-email").val();
    var password = $("#register-password").val();
    var apikey = $("#apikey").val();
    var feedid = $("#feedid").val();
    $.ajax({
        url: path+"register",
        data: "email="+email+"&password="+password+"&apikey="+apikey+"&feedid="+feedid,
        dataType: 'text',
        success: function(result) {
            $(".alert").html(result);
        }
    });
});

$("#logout").click(function() { logout(); });

function logout() {
    $.ajax({
        url: path+"/logout",
        dataType: 'text',
        success: function(result) {
            $("#login-block").show();
            $("#welcome-block").hide();
            $("#admin-block").hide();
        }
    });
}
</script>
