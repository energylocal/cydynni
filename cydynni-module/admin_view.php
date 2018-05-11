<?php global $path, $session; ?>

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


<div id="admin-block">
    <button type="button" class="btn btn-primary pull-right" data-toggle="modal" data-target="#newUserModal">
        <span class="icon icon-user icon-white"></span> Add User</a>
    </button>

    <h2>User list</h2>
    <p>Run household breakdown check: <button id="check-all-households">Start</button></p>
    <div class="alert hidden"></div>

    <div class="accordion" id="userlist"></div>

    <template id="userlist-item">
        <div class="accordion-group">
            <div class="accordion-heading">
                <a class="accordion-toggle" data-toggle="collapse" data-parent="#userlist" href="#">
                    <h4 class="pull-right welcomedate label label-info"></h4>
                    <h4><span class="username"></span>
                    &lt;<span class="email"></span>&gt;</h4>
                </a>
            </div>
            <div class="accordion-body collapse">
                <div class="accordion-inner">
                    <div class="row">
                        <div class="span4">
                            <h4 class="text-center">User</h4>
                            <dl class="dl-horizontal">
                                <dt>Username</dt><dd class="username"></dd>
                                <dt>MPAN</dt><dd class="mpan"></dd>
                                <dt>Token</dt><dd class="token"></dd>
                                <dt>Report Key</dt><dd class="apikey_read"></dd>
                                <dt>Welcome Email</dt><dd class="welcomedate"></dd>
                                <dt>Report Email</dt><dd class="reportdate"></dd>
                                <dt>Hits</dt><dd class="hits"></dd>
                                <dt></dt><dd><a class="btn btn-info link btn-small" data-toggle="modal" data-target="#editUserModal">Edit User</a></dd>
                            </dl>
                        </div>
                        <div class="span4">
                            <h4 class="text-center">Club</h4>
                            <dl class="dl-horizontal">
                                <dt>id</dt><dd class="club-id"></dd>
                                <dt>name</dt><dd class="club-name"></dd>
                                <dt>generator</dt><dd class="generator"></dd>
                                <dt>root_token</dt><dd class="root_token"></dd>
                                <dt>api_prefix</dt><dd class="api_prefix"></dd>
                                <dt>languages</dt><dd class="languages"></dd>
                                <dt>generation_feed</dt><dd class="generation_feed"></dd>
                                <dt>consumption_feed</dt><dd class="consumption_feed"></dd>
                                <dt><div class="bg-color pull-right"></div>color</dt><dd class="color"></dd>
                                <dt></dt><dd><a class="btn btn-info btn-small" data-toggle="modal" data-target="#editClubModal">Edit Club</a></dd>
                                
                            </dl>
                        </div>
                        <div class="span2">
                            <h4>Emails</h4>
                            <ul class="unstyled">
                                <li style="margin-bottom:1em">
                                    <button class="btn btn-success registeremail">Send Welcome Email</button>
                                </li>
                                <li style="margin-bottom:1em">
                                    <button class="btn btn-info reportemail">Send Report Email</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>


    <!-- NEW USER Modal -->
    <div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="newUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUserModalLabel">Add User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <p><b>Create new user:</b></p>
            <p>
                <input id="register-email" type="text" placeholder="Email..."><br><br>
                <input id="register-password" type="password" placeholder="Password..."><br><br>
                <input id="apikey" type="text" placeholder="Meter Token"><br><br>
                <input id="feedid" type="text" placeholder="Meter UID"><br><br>
            </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="register" type="button" class="btn btn-primary">Create account</button>
            </div>
            </div>
        </div>
    </div>
    <!-- NEW CLUB Modal -->
    <div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="newUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUserModalLabel">Add Club</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <p><b>Create new Club:</b></p>
            <p>
                <input id="register-email" type="text" placeholder="Email..."><br><br>
                <input id="register-password" type="password" placeholder="Password..."><br><br>
                <input id="apikey" type="text" placeholder="Meter Token"><br><br>
                <input id="feedid" type="text" placeholder="Meter UID"><br><br>
            </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="register" type="button" class="btn btn-primary">Create account</button>
            </div>
            </div>
        </div>
    </div>
    <!-- EDIT USER Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <p><b>Edit user:</b></p>
            <p>
                <input id="register-email" type="text" placeholder="Email..."><br><br>
                <input id="register-password" type="password" placeholder="Password..."><br><br>
                <input id="apikey" type="text" placeholder="Meter Token"><br><br>
                <input id="feedid" type="text" placeholder="Meter UID"><br><br>
            </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="register" type="button" class="btn btn-primary">Save Changes</button>
            </div>
            </div>
        </div>
    </div>
    <!-- EDIT CLUB Modal -->
    <div class="modal fade" id="editClubModal" tabindex="-1" role="dialog" aria-labelledby="editClubModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClubModalLabel">Edit Club</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <p><b>Edit Club:</b></p>
            <p>
                <input id="register-email" type="text" placeholder="Email..."><br><br>
                <input id="register-password" type="password" placeholder="Password..."><br><br>
                <input id="apikey" type="text" placeholder="Meter Token"><br><br>
                <input id="feedid" type="text" placeholder="Meter UID"><br><br>
            </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="register" type="button" class="btn btn-primary">Save Changes</button>
            </div>
            </div>
        </div>
    </div>
</div>


<script>
var path = "<?php echo $path; ?>";
var session = <?php echo json_encode($session); ?>;

var users = [];
if (session.admin==1) {
    load();
}

function load() {
    $("#login-block").hide();
    $("#admin-block").show();

    $.ajax({
        url: path+"cydynni/admin/users",
        dataType: 'json',
        success: function(result) {
            users = result;
            for (var z in result) {
                //append copy of template with replaced values for each user
                template = $('#userlist-item').clone();
                $(template.html()).appendTo('#userlist')
                .attr('href',result[z].id)
                .find('.email').text(result[z].email).end()
                .find('.token').text(result[z].token).end()
                .find('.apikey_read').text(result[z].apikey_read).end()
                .find('.welcomedate').text(result[z].welcomedate).end()
                .find('.reportdate').text(result[z].reportdate).end()
                .find('.hits').text(result[z].hits).end()
                .find('.username').text(result[z].username).end()
                .find('.mpan').text(result[z].mpan).end()
                
                .find('.registeremail').data('userid',result[z].id).end()
                .find('.reportemail').data('userid',result[z].id).end()
                .find('.edituser').data('userid',result[z].id).end()
                .find('.editclub').data('clubid',result[z].club.id).end()

                .find('.generator').text(result[z].club.generator).end()
                .find('.root_token').text(result[z].club.root_token).end()
                .find('.api_prefix').text(result[z].club.api_prefix).end()
                .find('.languages').text(result[z].club.languages).end()
                .find('.generation_feed').text(result[z].club.generation_feed).end()
                .find('.consumption_feed').text(result[z].club.consumption_feed).end()
                .find('.color').text(result[z].club.color).end()
                .find('.bg-color').css('background-color', result[z].club.color).end()
                .find('.club-id').text(result[z].club.id).end()
                .find('.club-name').text(result[z].club.name).end()

                .find('.accordion-toggle').attr('href','#collapse'+result[z].id).end()
                .find('.accordion-body').attr('id','collapse'+result[z].id)
            }
        },
        error: function(xhr, message, error){
            console.log(error,message);
        }
    });
}
$(document).click('#userList', function(event){
    expandable = event.target.tagName == 'A' ? $(event.target) : $(event.target).parents('a').first();
    if(event.target.tagName == 'A') expandable.toggleClass('expanded');
    event.preventDefault();
});
// $(document).on('click', 'button.edituser', function(event){
//     event.preventDefault();
//     event.stopPropagation();
//     alert('edit user '+$(event.target).data('userid'));
// });
// $(document).on('click', 'button.editclub', function(event){
//     event.preventDefault();
//     event.stopPropagation();
//     alert('edit club '+$(event.target).data('clubid'));
// });

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
    var userid = $(this).data("userid");
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
            $(".household-breakdown[data-userid="+userid+"]").html(result);
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
            $(".household-breakdown[data-userid="+userid+"]").html(result);
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
    var value = $(".edit-input[key="+key+"][data-userid="+userid+"]").val();

    $.ajax({
        url: path+"admin/change-user-"+key,
        data: "userid="+userid+"&"+key+"="+value,
        async:true,
        dataType: 'text',
        success: function(result) {
           alert(result);
        }
    });
    $(".edit-save[key="+key+"][data-userid="+userid+"]").hide();
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

<style>
.list-group, .list-group * {box-sizing: border-box;}
.list-group {
    display: -ms-flexbox;
    display: flex;
    -ms-flex-direction: column;
    flex-direction: column;
    padding-left: 0;
    margin-bottom: 0;
}

.list-group-item {
    position: relative;
    display: block;
    padding: .75rem 1.25rem;
    margin-bottom: -1px;
    background-color: #fff;
    border: 1px solid rgba(0,0,0,.125);
}
.list-group-item:first-child {
    border-top-left-radius: .25rem;
    border-top-right-radius: .25rem;
}
.list-group-item:last-child{
    margin-bottom: 0;
    border-bottom-right-radius: .25rem;
    border-bottom-left-radius: .25rem;
}


.list-group-item:focus, .list-group-item:hover {
    z-index: 1;
    text-decoration: none;
}
.list-group-item-action:focus, .list-group-item-action:hover {
    color: #495057;
    text-decoration: none;
    background-color: #f8f9fa;
}
button:focus {
    outline: 1px dotted;
    outline: 5px auto -webkit-focus-ring-color;
}

.list-group-item .hidden{transition:all .3s ease-out;opacity:0;display:block;visibility:visible;height:0px;overflow:hidden}
.list-group-item.expanded .hidden{opacity:1;height:17em}


.modal{display:none}
.modal.in{display:block}

.bg-color{
    border-radius: 50%;
    width: 1em;
    height: 1em;
    overflow: hidden;
    margin: .2em 0 0 .2em;
}
</style>