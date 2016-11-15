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
    <link rel="stylesheet" type="text/css" href="theme/admin.css" />
    <!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
    
  </head>
  <body>
  
    <div class="admin-top-nav">
      <div class="container">
        <div class="admin-top-nav-title">CydYnni App Administration</div>
        <div class="logout">Logout</div>
      </div>
    </div>
    
    <div class="container">
    <div class="inner">
    
    <div id="login-block">
      <br><br>
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
      
      <h3>User list</h3>
      <table class="table" style="table-layout: fixed; width: 100%">
        <tr><th style="width:50px">User</th><th style="width:250px">Email</th><th>Token</th><th style="width:80px">UID</th><th style="width:50px">Admin</th></tr>
        <tbody id="users"></tbody>
      </table>
      <br><br>
      <p><b>Create new user:</b></p>
      <p>
        <input id="register-email" type="text" placeholder="Email..."><br><br>
        <input id="register-password" type="password" placeholder="Password..."><br><br>
        <input id="apikey" type="text" placeholder="Meter Token"><br><br>
        <input id="feedid" type="text" placeholder="Meter UID"><br><br>
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

if (session.admin) {
    load();
    $(".logout").show();
} else {
    $(".logout").hide();
}

function load() {
    $("#login-block").hide();
    $("#admin-block").show();

    $.ajax({
        url: path+"admin/users",
        dataType: 'json',
        success: function(result) {
            var out = "";
            for (var z in result) {
                out += "<tr><td>"+result[z].id+"</td><td>"+result[z].email+"</td><td style='text-wrap:normal;word-wrap:break-word'>"+result[z].apikey+"</td><td>"+result[z].feedid+"</td><td>"+result[z].admin+"</td>";
                out += "<td><button class='registeremail' userid='"+result[z].id+"' style='font-size:12px'>Send Welcome Email</button></td>";
                out += "</tr>";
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
                    $(".logout").show();
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
            load();
        }
    });
});

$("body").on("click",".registeremail",function(){
    var userid = $(this).attr("userid");
    $.ajax({
        url: path+"registeremail",
        data: "userid="+userid,
        dataType: 'text',
        success: function(result) {
            $(".alert").html(result);
        }
    });
});

$(".logout").click(function() { logout(); });

function logout() {
    $.ajax({
        url: path+"/logout",
        dataType: 'text',
        success: function(result) {
            $("#login-block").show();
            $("#welcome-block").hide();
            $("#admin-block").hide();
            $(".logout").hide();
        }
    });
}
</script>
