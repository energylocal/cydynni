<?php global $path, $session; ?>

<style>
input {
    margin:0px !important;
}

.input-append {
    margin:0px !important;
}
</style>

<h3>User list</h3>
<p>Run household breakdown check: <button id="check-all-households">Start</button></p>
<table class="table" style="table-layout: fixed; width: 100%">
  <tr>
    <th style="width:80px">User</th>
    <th style="width:250px">Username</th>
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

<script>
var session = <?php echo json_encode($session); ?>;

var users = [];

load();
function load() {

    $.ajax({
        url: path+"cydynni/admin-users",
        dataType: 'json',
        success: function(result) {
            users = result;
        
            var out = "";
            for (var z in result) {
                out += "<tr>";
                var admin = ""; if (result[z].admin==1) admin = " (admin)";
                out += "<td><a href='"+path+"cydynni/admin-switchuser?userid="+result[z].id+"'>"+result[z].id+"</a>"+admin+"</td>";

                out += "<td class='td-username'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].username+"' class='edit-input' style='width:180px' key='username' userid='"+result[z].id+"'>";
                  out += "<button class='btn edit-save hide' key='username' userid='"+result[z].id+"'>Save</button></div>";
                out += "</td>";
                
                out += "<td class='td-email'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].email+"' class='edit-input' style='width:180px' key='email' userid='"+result[z].id+"'>";
                  out += "<button class='btn edit-save hide' key='email' userid='"+result[z].id+"'>Save</button></div>";
                out += "</td>";
                // text-wrap:normal;word-wrap:break-word
                out += "<td><div style=''>"+result[z].mpan+"</div></td>";
                out += "<td><div style='overflow:hidden'>"+result[z].token+"</div></td>";
                out += "<td><div style='overflow:hidden'>"+result[z].apikey_read+"</div></td>";
                
                // Register date
                var bgcolor = "#ccffcc"; if (result[z].welcomedate=="not sent") bgcolor = "#ffcccc";
                out += "<td><span style='font-size:12px; background-color:"+bgcolor+"'>"+result[z].welcomedate+"</span> ";
                out += "<button class='btn registeremail' userid='"+result[z].id+"' style='font-size:12px'>Send</button></td>";
                
                // Report date
                var bgcolor = "#ccffcc"; if (result[z].reportdate=="not sent") bgcolor = "#ffcccc";
                out += "<td><span style='font-size:12px; background-color:"+bgcolor+"'>"+result[z].reportdate+"</span> ";
                out += "<button class='btn reportemail' userid='"+result[z].id+"' style='font-size:12px'>Send</button></td>";
                
                out += "<td>"+result[z].hits+"</td>";
                //out += "<td style='overflow:hidden'><pre>"+JSON.stringify(result[z].testdata)+"</pre></td>";
                out += "</tr>";
            }
            $("#users").html(out);
        }
    });
}

$("body").on("click",".registeremail",function(){
    var userid = $(this).attr("userid");
    $.ajax({
        url: path+"cydynni/admin-registeremail",
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
        url: path+"cydynni/admin-sendreport",
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
        url: path+"cydynni/admin-check-household-breakdown",
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
        url: path+"cydynni/admin-check-household-breakdown",
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
        url: path+"cydynni/admin-change-user-"+key,
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
        url: path+"logout",
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
