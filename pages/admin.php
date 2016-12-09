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
      
      .edit-email-save {
          float:right; 
          font-size:12px;
          cursor:pointer;
          padding:2px;
          display:none;
          margin-top:4px;
      }
      
      .edit-email-input {
          width:280px;
      }

      .edit-email-input:selected {
          border-bottom:1px #000 solid;
      }
    </style> 
   
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
      <p>Run household breakdown check: <button id="check-all-households">Start</button></p>
      <table class="table" style="table-layout: fixed; width: 100%">
        <tr>
          <th style="width:80px">User</th>
          <th style="width:330px">Email <span style="font-size:12px">(Click to edit)</span></th>
          <th style="width:300px">MPAN</th>
          <th style="width:350px">Token</th>
          <th style="width:140px">Welcome Email</th>
          <th style="width:50px">Hits</th>
          <th></th>
        </tr>
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
console.log(path);
var session = JSON.parse('<?php echo json_encode($session); ?>');
var users = [];

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
            users = result;
        
            var out = "";
            for (var z in result) {
                out += "<tr>";
                var admin = ""; if (result[z].admin==1) admin = " (admin)";
                out += "<td>"+result[z].id+admin+"</td>";
                out += "<td class='td-email'>";
                  out += "<input type='text' value='"+result[z].email+"' class='edit-email-input' style='border:0; color:#3b6358; padding:0px' userid='"+result[z].id+"'>";
                  out += "<button class='edit-email-save' userid='"+result[z].id+"'>Save</button>";
                out += "</td>";
                // text-wrap:normal;word-wrap:break-word
                out += "<td><div style=''>"+result[z].MPAN+"</div></td>";
                out += "<td><div style='overflow:hidden'>"+result[z].apikey+"</div></td>";
                out += "<td>"+result[z].welcomedate+" <button class='registeremail' userid='"+result[z].id+"' style='font-size:12px'>Send</button></td>";
                out += "<td>"+result[z].hits+"</td>";
                out += "<td>";
                out += "Check: <button class='check-household-breakdown' userid='"+result[z].id+"' style='font-size:12px'>Household Data</button> <span style='overflow:hidden' class='household-breakdown' userid='"+result[z].id+"'></span>";
                out += "</td>";
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
            alert(result)
        }
    });
});

$("body").on("click",".check-household-breakdown",function(){
    var userid = $(this).attr("userid");
    $.ajax({
        url: path+"admin/check-household-breakdown",
        data: "userid="+userid,
        dataType: 'text',
        success: function(result) {
            $(".household-breakdown[userid="+userid+"]").html(result);
        }
    });
});

$(".logout").click(function() { logout(); });

$("#check-all-households").click(function() {
  for (var z in users) {
    var userid = users[z]["id"];
    $.ajax({
        url: path+"admin/check-household-breakdown",
        data: "userid="+userid,
        async:false,
        dataType: 'text',
        success: function(result) {
            $(".household-breakdown[userid="+userid+"]").html(result);
        }
    });
  }
});

$("body").on("keyup",".edit-email-input",function(){
    var userid = $(this).attr("userid");
    $(".edit-email-save[userid="+userid+"]").show();
});

$("body").on("click",".edit-email-save",function(){
    var userid = $(this).attr("userid");
    var email = $(".edit-email-input[userid="+userid+"]").val();
    
    $.ajax({
        url: path+"admin/change-user-email",
        data: "userid="+userid+"&email="+email,
        async:true,
        dataType: 'text',
        success: function(result) {
           alert(result);
        }
    });
    $(".edit-email-save[userid="+userid+"]").hide();
});



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
