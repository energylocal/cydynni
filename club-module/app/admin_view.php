<?php global $path, $session, $club_settings; 

$clubs = array();
foreach ($club_settings as $club) {
    $clubs["".$club["club_id"]] = $club["name"];
}

?>

<style>
input {
    margin:0px !important;
}

.input-append {
    margin:0px !important;
}
</style>

<h3>User list</h3>

<div class="input-prepend input-append" style="float:right">
    <span class="add-on">Add user:</span>
    <input type="text" id="add_user_username" style="width:150px" placeholder="Username"/>
    <input type="text" id="add_user_email" style="width:150px" placeholder="Email"/>
    <input type="text" id="add_user_mpan" style="width:150px" placeholder="MPAN"/>
    <button class="btn" id="add_user">Add</button>
</div>

<div class="input-prepend">
    <span class="add-on">Select Club:</span>
    <select id="select_club"></select>
</div>



<table class="table" style="table-layout: fixed; width: 100%">
  <tr>
    <th>User</th>
    <th style="width:250px">Username</th>
    <th style="width:260px">Email <span style="font-size:12px">(Click to edit)</span></th>
    <th>MPAN</th>
    <th>Welcome Email</th>
    <th>Report Email</th>
    <th>Feeds</th>
    <th>Hits</th>
    <th>Graph</th>
  </tr>
  <tbody id="users"></tbody>
</table>

<script>
var path = "<?php echo $path; ?>";
var session = <?php echo json_encode($session); ?>;

var users = [];

var clubs = <?php echo json_encode($clubs); ?>;
var out = "";
for (var z in clubs) {
    out += "<option value='"+z+"'>"+clubs[z]+"</option>";
}
$("#select_club").html(out);
var club_id = 1;

load();
function load() {

    $.ajax({
        url: path+"club/admin-users?club_id="+club_id,
        dataType: 'json',
        success: function(result) {
            users = result;
        
            var out = "";
            for (var z in result) {
                out += "<tr>";
                var admin = ""; if (result[z].admin==1) admin = " (admin)";
                out += "<td><a href='"+path+"club/admin-switchuser?userid="+result[z].userid+"'>"+result[z].userid+"</a>"+admin+"</td>";

                out += "<td class='td-username'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].username+"' class='edit-input' style='width:180px' key='username' userid='"+result[z].userid+"'>";
                  out += "<button class='btn edit-save hide' key='username' userid='"+result[z].userid+"'>Save</button></div>";
                out += "</td>";
                
                out += "<td class='td-email'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].email+"' class='edit-input' style='width:180px' key='email' userid='"+result[z].userid+"'>";
                  out += "<button class='btn edit-save hide' key='email' userid='"+result[z].userid+"'>Save</button></div>";
                out += "</td>";
                // text-wrap:normal;word-wrap:break-word
                out += "<td><div style=''>"+result[z].mpan+"</div></td>";
                /*out += "<td><div style='overflow:hidden'>"+result[z].token+"</div></td>";
                out += "<td><div style='overflow:hidden'>"+result[z].apikey_read+"</div></td>";*/
                
                // Register date
                var bgcolor = "#ccffcc"; if (result[z].welcomedate=="not sent") bgcolor = "#ffcccc";
                out += "<td><span style='font-size:12px; background-color:"+bgcolor+"'>"+result[z].welcomedate+"</span> ";
                out += "<button class='btn registeremail' userid='"+result[z].userid+"' style='font-size:12px'>Send</button></td>";
                
                // Report date
                var bgcolor = "#ccffcc"; if (result[z].reportdate=="not sent") bgcolor = "#ffcccc";
                out += "<td><span style='font-size:12px; background-color:"+bgcolor+"'>"+result[z].reportdate+"</span> ";
                out += "<button class='btn reportemail' userid='"+result[z].userid+"' style='font-size:12px'>Send</button></td>";

                out += "<td><div style=''>"+result[z].feeds+"</div></td>";
                out += "<td>"+result[z].hits+"</td>";
                out += "<td><a href='/graph/"+result[z].meter_power+"'>"+result[z].meter_power+"</a></td>";
                //out += "<td style='overflow:hidden'><pre>"+JSON.stringify(result[z].testdata)+"</pre></td>";
                out += "</tr>";
            }
            $("#users").html(out);
        }
    });
}

$("#select_club").change(function(){
    club_id = $(this).val();
    load();
});

$("#add_user").click(function(){
    var username = $("#add_user_username").val();
    var email = $("#add_user_email").val();
    var mpan = $("#add_user_mpan").val();
    
    $.ajax({
        type: 'POST',
        url: path+"club/admin-add-user",
        data: "club_id="+club_id+"&username="+username+"&email="+email+"&mpan="+mpan,
        dataType: 'json',
        success: function(result) {
            if (!result.success) alert(result.message);
            load();
        }
    });
});

$("body").on("click",".registeremail",function(){
    var userid = $(this).attr("userid");
    $.ajax({
        url: path+"club/admin-registeremail",
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
        url: path+"club/admin-sendreport",
        data: "userid="+userid,
        dataType: 'text',
        success: function(result) {
            alert(result)
        }
    });
});

$("body").on("keyup",".edit-input",function(){
    $(this).parent().find(".edit-save").show();
});

$("body").on("click",".edit-save",function(){
    var key = $(this).attr("key");
    var userid = $(this).attr("userid");
    var value = $(".edit-input[key="+key+"][userid="+userid+"]").val();
    
    $.ajax({
        url: path+"club/admin-change-user-"+key,
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
