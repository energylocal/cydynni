<?php global $path, $session; ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyd Ynni</title>

    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <link rel="stylesheet" type="text/css" href="css/admin.css" />

    <!--[if IE]><script language="javascript" type="text/javascript" src="lib/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="<?php echo $path; ?>lib/jquery-1.11.3.min.js"></script>
    
    <style>
      .container {
        width: 95%;
      }
      
      .edit-save {
          float:right; 
          font-size:12px;
          cursor:pointer;
          padding:2px;
          display:none;
          margin-top:4px;
      }
      
      .edit-input {
          width:230px;
          border:0; 
          color:#3b6358; 
          padding:0px
      }

      .edit-input:selected {
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
          <th style="width:220px">Username</th>
          <th style="width:260px">Email <span style="font-size:12px">(Click to edit)</span></th>
          <th style="width:130px">MPAN</th>
          <th style="max-width:350px">Token</th>
          <th style="max-width:100px">Report Key</th>
          <th style="width:140px">Welcome Email</th>
          <th style="width:140px">Report Email</th>
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
var session = <?php echo json_encode($session); ?>;

var users = [];

if (session.admin==1) {
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
                out += "<td><a href='admin/switchuser?userid="+result[z].id+"'>"+result[z].id+"</a>"+admin+"</td>";

                out += "<td class='td-username'>";
                  out += "<input type='text' value='"+result[z].username+"' class='edit-input' key='username' userid='"+result[z].id+"'>";
                  out += "<button class='edit-save' key='username' userid='"+result[z].id+"'>Save</button>";
                out += "</td>";
                
                out += "<td class='td-email'>";
                  out += "<input type='text' value='"+result[z].email+"' class='edit-input' key='email' userid='"+result[z].id+"'>";
                  out += "<button class='edit-save' key='email' userid='"+result[z].id+"'>Save</button>";
                out += "</td>";
                // text-wrap:normal;word-wrap:break-word
                out += "<td><div style=''>"+result[z].mpan+"</div></td>";
                out += "<td><div style='overflow:hidden'>"+result[z].token+"</div></td>";
                out += "<td><div style='overflow:hidden'>"+result[z].apikey_read+"</div></td>";
                
                // Register date
                var bgcolor = "#ccffcc"; if (result[z].welcomedate=="not sent") bgcolor = "#ffcccc";
                out += "<td><span style='font-size:12px; background-color:"+bgcolor+"'>"+result[z].welcomedate+"</span> ";
                out += "<button class='registeremail' userid='"+result[z].id+"' style='font-size:12px'>Send</button></td>";
                
                // Report date
                var bgcolor = "#ccffcc"; if (result[z].reportdate=="not sent") bgcolor = "#ffcccc";
                out += "<td><span style='font-size:12px; background-color:"+bgcolor+"'>"+result[z].reportdate+"</span> ";
                out += "<button class='reportemail' userid='"+result[z].id+"' style='font-size:12px'>Send</button></td>";
                
                out += "<td>"+result[z].hits+"</td>";
                out += "<td style='overflow:hidden'><pre>"+JSON.stringify(result[z].testdata)+"</pre></td>";
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
        type: 'POST',
        url: path+"login",
        data: "email="+email+"&password="+password,
        dataType: 'json',
        success: function(result) {
            if (result.admin) {
                window.location = path+"admin";
            } else {
                $(".alert").html("Administrator access only");
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
        url: path+"admin/registeremail",
        data: "userid="+userid,
        dataType: 'text',
        success: function(result) {
            alert(result)
        }
    });
});

$("body").on("click",".reportemail",function(){
    var userid = $(this).attr("userid");
    $.ajax({
        url: path+"admin/sendreport",
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

$("body").on("keyup",".edit-input",function(){
    $(this).parent().find(".edit-save").show();
});

$("body").on("click",".edit-save",function(){
    var key = $(this).attr("key");
    var userid = $(this).attr("userid");
    var value = $(".edit-input[key="+key+"][userid="+userid+"]").val();
    
    $.ajax({
        url: path+"admin/change-user-"+key,
        data: "userid="+userid+"&"+key+"="+value,
        async:true,
        dataType: 'text',
        success: function(result) {
           alert(result);
        }
    });
    $(".edit-save[key="+key+"][userid="+userid+"]").hide();
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
