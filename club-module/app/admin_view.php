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
    <input type="text" id="add_user_password" style="width:150px" placeholder="Password"/>
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
    <th style="width:40px">User</th>
    <th style="width:165px">Username</th>
    <th style="width:185px">Email <span style="font-size:12px">(Click to edit)</span></th>
    <th>MPAN</th>
    <th>Serial No</th>
    <th>GUID</th>
    <th style="width:120px">Welcome Email</th>
    <th style="width:120px">Report Email</th>
    <th>Feeds</th>
    <th>Hits</th>
    <th>Graph</th>
  </tr>
  <tbody id="users"></tbody>
</table>

<script>
var path = "<?php echo $path; ?>";
var session = <?php echo json_encode($session); ?>;

var selected_club = localStorage.getItem('selected_club');
if (selected_club==null) selected_club = 1; else selected_club *= 1;

var users = [];

var clubs = <?php echo json_encode($clubs); ?>;
console.log(clubs)
var out = "";
for (var z in clubs) {
    var selected = "";
    if (selected_club==z) selected = "selected";
    out += "<option value='"+z+"' "+selected+">"+clubs[z]+"</option>";
}
$("#select_club").html(out);

load();
function load() {

    $.ajax({
        url: path+"club/admin-users?club_id="+selected_club,
        dataType: 'json',
        success: function(result) {
            users = result;
        
            var out = "";
            for (var z in result) {
                out += "<tr>";
                var admin = ""; if (result[z].admin==1) admin = " (A)";
                out += "<td><a href='"+path+"club/admin-switchuser?userid="+result[z].userid+"'>"+result[z].userid+"</a>"+admin+"</td>";

                out += "<td class='td-username'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].username+"' class='edit-input' style='width:160px' key='username' userid='"+result[z].userid+"'>";
                  out += "<button class='btn edit-save hide' key='username' userid='"+result[z].userid+"'>S</button></div>";
                out += "</td>";
                
                out += "<td class='td-email'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].email+"' class='edit-input' style='width:180px' key='email' userid='"+result[z].userid+"'>";
                  out += "<button class='btn edit-save hide' key='email' userid='"+result[z].userid+"'>S</button></div>";
                out += "</td>";
                
                out += "<td class='td-mpan'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].mpan+"' class='edit-input' style='width:120px' key='mpan' userid='"+result[z].userid+"'>";
                  out += "<button class='btn edit-save hide' key='mpan' userid='"+result[z].userid+"'>S</button></div>";
                out += "</td>";

                out += "<td class='td-serial'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].serial+"' class='edit-input' style='width:100px' key='serial' userid='"+result[z].userid+"'>";
                  out += "<button class='btn edit-save hide' key='serial' userid='"+result[z].userid+"'>S</button></div>";
                out += "</td>";
                
                out += "<td class='td-guid'>";
                  out += "<div class='input-append'><input type='text' value='"+result[z].guid+"' class='edit-input' style='width:100px' key='guid' userid='"+result[z].userid+"'>";
                  out += "<button class='btn edit-save hide' key='guid' userid='"+result[z].userid+"'>S</button></div>";
                out += "</td>";
                
                // text-wrap:normal;word-wrap:break-word
                // out += "<td><div style=''>"+result[z].mpan+"</div></td>";
                //out += "<td><div style=''>"+result[z].serial+"</div></td>";
                //out += "<td><div style=''>"+result[z].guid+"</div></td>";
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
                
                var now = (new Date()).getTime()*0.001;
                var last_updated_ago = (now - result[z].last_updated)/(3600*24)
                var last_updated_ago_str = last_updated_ago.toFixed(1)+" days"
                if (result[z].last_updated==0) last_updated_ago_str = ""
                
                var color = "#d4edda";
                if (last_updated_ago>7) color = "#fff3cd"
                if (last_updated_ago>30) color = "#f8d7da"
                
                out += "<td style='background-color:"+color+"'><a href='/graph/"+result[z].use_hh_est+"'>"+last_updated_ago_str+"</a></td>";
                //out += "<td style='overflow:hidden'><pre>"+JSON.stringify(result[z].testdata)+"</pre></td>";
                out += "</tr>";
            }
            $("#users").html(out);
        }
    });
}

$("#select_club").change(function(){
    selected_club = $(this).val();
    localStorage.setItem('selected_club',selected_club);
    load();
});

$("#add_user").click(function(){
    var username = $("#add_user_username").val();
    var password = $("#add_user_password").val();
    var email = $("#add_user_email").val();
    var mpan = $("#add_user_mpan").val();
    
    $.ajax({
        type: 'POST',
        url: path+"club/admin-add-user",
        data: "club_id="+selected_club+"&username="+username+"&password="+password+"&email="+email+"&mpan="+mpan,
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
