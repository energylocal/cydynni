<?php global $path, $session; ?>
<link rel="stylesheet" href="<?php echo $path; ?>Lib/misc/sidebar.css">

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

.badge-circle{
    border-radius: 50%;
    width: 1em;
    height: 1em;
    overflow: hidden;
    margin: .2em 0 0 .2em;
}

#clublist li button.edit_club{
    opacity: 0.1;
    transition: all .3s ease-out;
    border: 1px solid transparent !important;
    float: right;
    background: transparent;
    color: #ccc;
    border-radius: 0.2em;
    line-height: 1.3;
    margin-right:1px;
    padding-top: 2px;
}
#clublist li:hover button.edit_club{
    opacity: 0.6;
}
#clublist button.edit_club:hover{
    opacity: 1!important;
    border: 1px solid #fff9 !important;
}

</style>




<div id="wrapper">
    <div class="sidenav">
        <div class="sidenav-inner">
            <h4 style="color:white"><a id="all-clubs" href="<?php echo $path ?>cydynni/admin" title="Show all Users">Cydynni Clubs</a></h4>
            <ul id="clublist" class="sidenav-menu">
                <li><a href="#" data-href="<?php echo $path; ?>cydynni/admin/clubs/new" data-toggle="modal" data-target="#newClubModal"><i class="icon-plus icon-white"></i> New</a></li>
            </ul>
        </div><!-- /.sidenav-inner-->
    </div><!-- /.sidenav-->
    <div style="padding:2em 1em">
        <button type="button" class="btn btn-primary pull-right" data-toggle="modal" data-target="#newUserModal">
            <span class="icon icon-user icon-white"></span> Add User</a>
        </button>
        <h2>User list</h2>
        <p>Run household breakdown check: <button id="check-all-households">Start</button></p>
        <div class="alert hidden"></div>
        <div class="accordion" id="userlist"></div>
    </div>
</div><!-- /.wrapper -->





<!-- TEMPLATES -->

<!-- list of clubs template-->
<template id="clublist-item">
    <li class="added">
        <button class="edit_club" type="button" data-toggle="modal" data-target="#editClubModal" data-club_id="0">Edit</button>
        <a href="<?php echo $path; ?>cydynni/admin/clubs/"></a>
    </li>
</template>

<!-- list of users template -->
<template id="userlist-item">
    <div class="accordion-group">
        <div class="accordion-heading">
            <a class="accordion-toggle" data-toggle="collapse" data-parent="#userlist" href="#">
                <h4 class="pull-right club-name label bg-color"></h4>
                <h4><span class="username"></span>
                &lt;<span class="email"></span>&gt;</h4>
            </a>
        </div>
        <div class="accordion-body collapse">
            <div class="accordion-inner">
                <div class="row">
                    <div class="span5">
                        <h4 class="text-center">User</h4>
                        <dl class="dl-horizontal">
                            <dt>Username</dt><dd class="username"></dd>
                            <dt>MPAN</dt><dd class="mpan"></dd>
                            <dt>Token</dt><dd class="token"></dd>
                            <dt>Report Key</dt><dd class="apikey_read"></dd>
                            <dt>Welcome Email</dt><dd class="welcomedate"></dd>
                            <dt>Report Email</dt><dd class="reportdate"></dd>
                            <dt></dt><dd><a class="btn btn-info link btn-small edit-user-button" data-toggle="modal" data-target="#editUserModal">Edit User</a></dd>
                        </dl>
                    </div>
                    <div class="span4">
                        <h4 class="text-center">Club</h4>
                        <dl class="dl-horizontal">
                            <dt>id</dt><dd class="club-id"></dd>
                            <dt>name</dt><dd class="club-name"></dd>
                            <dt>slug</dt><dd class="club-slug"></dd>
                            <dt>generator</dt><dd class="generator"></dd>
                            <dt>root_token</dt><dd class="root_token"></dd>
                            <dt>api_prefix</dt><dd class="api_prefix"></dd>
                            <dt>languages</dt><dd class="languages"></dd>
                            <dt>generation_feed</dt><dd class="generation_feed"></dd>
                            <dt>consumption_feed</dt><dd class="consumption_feed"></dd>
                            <dt><div class="bg-color badge-circle pull-right"></div>color</dt><dd class="color"></dd>
                            <!-- <dt></dt><dd><a class="btn btn-info btn-small" data-toggle="modal" data-target="#editClubModal">Edit Club</a></dd> -->
                        </dl>
                    </div>
                    <div class="span3">
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


<!-- MODALS -->


<!-- NEW USER Modal -->
<div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="newUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="create-user" style="margin:0" action="<?php echo $path ?>cydynni/admin/clubs/new" method="POST" class="form-horizontal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    <h5 class="modal-title" id="newUserModalLabel">Register New User</h5>
                </div>
                <div class="modal-body">

                    <div class="control-group">
                        <label class="control-label" for="new_user_email">Email</label>
                        <div class="controls">
                            <input type="email" name="email" id="new_user_email" class="input-xlarge" data-lpignore="true" placeholder="Email Address">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="new_user_username">Username</label>
                        <div class="controls">
                            <input type="text" name="username" id="new_user_username" data-lpignore="true" placeholder="Username">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="new_user_password">Password</label>
                        <div class="controls">
                            <input type="text" name="password" id="new_user_password" data-lpignore="true">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="new_user_club_id">Club</label>
                        <div class="controls">
                            <select name="club_id" id="new_user_club_id">
                                <option selected value="">Choose a club&hellip;</option>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="new_user_token">Meter Token</label>
                        <div class="controls">
                            <input type="text" name="token" id="new_user_token" data-lpignore="true" placeholder="Meter Token">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="new_user_mpan">MPAN</label>
                        <div class="controls">
                            <input type="text" name="token" id="new_user_mpan" data-lpignore="true" placeholder="API KEY">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Create account</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- EDIT USER Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="edit-user" style="margin:0" action="<?php echo $path ?>cydynni/admin/users" method="POST" class="form-horizontal">
        <input name="userid" type="hidden">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    <h5 class="modal-title" id="newUserModalLabel">Edit existing User</h5>
                </div>
                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label" for="edit_user_email">Email</label>
                        <div class="controls">
                            <input type="email" name="email" id="edit_user_email" class="input-xlarge" data-lpignore="true" placeholder="Email Address">
                            <input type="hidden" name="email-original">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="edit_user_username">Username</label>
                        <div class="controls">
                            <input type="text" name="username" id="edit_user_username" data-lpignore="true" placeholder="Username">
                            <input type="hidden" name="username-original">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="edit_user_club_id">Club</label>
                        <div class="controls">
                            <select name="club_id" id="edit_user_club_id">
                                <option>Choose a club&hellip;</option>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="edit_user_token">Meter Token</label>
                        <div class="controls">
                            <input type="text" name="token" id="edit_user_token" data-lpignore="true" placeholder="Meter Token">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="edit_user_mpan">MPAN</label>
                        <div class="controls">
                            <input type="text" name="mpan" id="edit_user_mpan" data-lpignore="true" placeholder="API KEY">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="message" style="position:absolute"></span>
                    <span class="loader" style="margin-top:1.2em;position:absolute"><img src="data:image/gif;base64,R0lGODlhEgAPAPIAAPX19ZeXl5eXl7i4uNjY2AAAAAAAAAAAACH+GkNyZWF0ZWQgd2l0aCBhamF4bG9hZC5pbmZvACH5BAAFAAAAIf8LTkVUU0NBUEUyLjADAQAAACwAAAAAEgAPAAADHAi63P4wykmrvThXIS7n1tcBAwWSQwkQBKVqTgIAIfkEAAUAAQAsAAAAABIADwAAAx4Iutz+MMpJq23iAsF11sowXKJolSNAUKZKrBcMPgkAIfkEAAUAAgAsAAAAABIADwAAAxwIutz+MEogxLw4q6HB+B3XKQShlWWGmio7vlMCACH5BAAFAAMALAAAAAASAA8AAAMXCLrcvuLJ+cagOGtHtiKgB3RiaZ5oiiUAIfkEAAUABAAsAAAAABIADwAAAxQIuty+48knJCEz6827/2AojiSYAAAh+QQABQAFACwAAAAAEgAPAAADFAi63L7kyTemvTgvobv/YCiOJJAAACH5BAAFAAYALAAAAAASAA8AAAMTCLrc/jAqIqu9duDNu4/CJ45XAgAh+QQABQAHACwAAAAAEgAPAAADFAi63P4wykmrBeTqzTsbHiUIIZcAACH5BAAFAAgALAAAAAASAA8AAAMXCLrc/jDKSau9OOvtiBSYICrDQIFckwAAOwAAAAAAAAAAAA==" alt="" /></span>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>




<!-- NEW CLUB Modal -->
<div class="modal fade" id="newClubModal" tabindex="-1" role="dialog" aria-labelledby="newClubModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="create-club" style="margin:0" action="<?php echo $path ?>cydynni/admin/clubs/new" method="POST" class="form-horizontal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    <h2 class="modal-title" id="newClubModalLabel">Create a new Club</h2>
                </div>
                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label" for="new_club_name">Name</label>
                        <div class="controls">
                            <input type="text" name="name" class="input-xlarge" id="new_club_name" data-lpignore="true" placeholder="Club Name">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="new_club_slug">Short Name</label>
                        <div class="controls">
                            <input type="text" name="slug" id="new_club_slug" data-lpignore="true" placeholder="URL friendly name">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="new_club_generator">Generator</label>
                        <div class="controls">
                            <input type="text" name="generator" id="new_club_generator" data-lpignore="true" placeholder="Generator Type">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="new_club_root_token">Root Token</label>
                        <div class="controls">
                            <input type="text" name="root_token" id="new_club_root_token" data-lpignore="true" placeholder="ePower Root Token">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="new_club_api_prefix">API prefix</label>
                        <div class="controls">
                            <input type="text" name="api_prefix" id="new_club_api_prefix" data-lpignore="true" placeholder="API prefix">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label">Languages</label>
                        <div class="controls">
                            <label class="checkbox inline">
                                <input name="languages[]" value="cy" type="checkbox"> CY
                            </label>
                            <label class="checkbox inline">
                                <input name="languages[]" value="en" type="checkbox"> EN
                            </label>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="new_club_generation_feed">Generation Feed</label>
                        <div class="controls">
                            <input type="text" name="generation_feed" id="new_club_generation_feed" data-lpignore="true" placeholder="Generation Feed ID">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="new_club_consumption_feed">Consumption Feed</label>
                        <div class="controls">
                            <input type="text" name="consumption_feed" id="new_club_consumption_feed" data-lpignore="true" placeholder="Consumption Feed ID">
                        </div>
                    </div>

                    <div class="control-group" style="margin-bottom:0">
                        <label class="control-label" for="new_club_color">Color</label>
                        <div class="controls">
                            <input type="color" name="color" id="new_club_color" data-lpignore="true">
                            <p class="help-block">Custom Club Colour</p>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <span class="message" style="position:absolute"></span>
                    <span class="loader" style="margin-top:1.2em;position:absolute"><img src="data:image/gif;base64,R0lGODlhEgAPAPIAAPX19ZeXl5eXl7i4uNjY2AAAAAAAAAAAACH+GkNyZWF0ZWQgd2l0aCBhamF4bG9hZC5pbmZvACH5BAAFAAAAIf8LTkVUU0NBUEUyLjADAQAAACwAAAAAEgAPAAADHAi63P4wykmrvThXIS7n1tcBAwWSQwkQBKVqTgIAIfkEAAUAAQAsAAAAABIADwAAAx4Iutz+MMpJq23iAsF11sowXKJolSNAUKZKrBcMPgkAIfkEAAUAAgAsAAAAABIADwAAAxwIutz+MEogxLw4q6HB+B3XKQShlWWGmio7vlMCACH5BAAFAAMALAAAAAASAA8AAAMXCLrcvuLJ+cagOGtHtiKgB3RiaZ5oiiUAIfkEAAUABAAsAAAAABIADwAAAxQIuty+48knJCEz6827/2AojiSYAAAh+QQABQAFACwAAAAAEgAPAAADFAi63L7kyTemvTgvobv/YCiOJJAAACH5BAAFAAYALAAAAAASAA8AAAMTCLrc/jAqIqu9duDNu4/CJ45XAgAh+QQABQAHACwAAAAAEgAPAAADFAi63P4wykmrBeTqzTsbHiUIIZcAACH5BAAFAAgALAAAAAASAA8AAAMXCLrc/jDKSau9OOvtiBSYICrDQIFckwAAOwAAAAAAAAAAAA==" alt="" /></span>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Create Club</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- EDIT CLUB Modal -->
<div class="modal fade" id="editClubModal" tabindex="-1" role="dialog" aria-labelledby="editClubModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="edit-club" style="margin:0" action="<?php echo $path ?>cydynni/admin/clubs" method="POST" class="form-horizontal">
        <input type="hidden" name="club_id" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                    <h2 class="modal-title" id="editClubModalLabel">Edit Club</h2>
                </div>
                <div class="modal-body">
                    <div class="control-group">
                        <label class="control-label" for="edit_club_name">Name</label>
                        <div class="controls">
                            <input type="text" name="name" class="input-xlarge" id="edit_club_name" data-lpignore="true" placeholder="Club Name">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="edit_club_slug">Short Name</label>
                        <div class="controls">
                            <input type="text" name="slug" id="edit_club_slug" data-lpignore="true" placeholder="URL Friendly Name">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="edit_club_generator">Generator</label>
                        <div class="controls">
                            <input type="text" name="generator" id="edit_club_generator" data-lpignore="true" placeholder="Generator Type">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="edit_club_root_token">Root Token</label>
                        <div class="controls">
                            <input type="text" name="root_token" id="edit_club_root_token" data-lpignore="true" placeholder="ePower Root Token">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="edit_club_api_prefix">API prefix</label>
                        <div class="controls">
                            <input type="text" name="api_prefix" id="edit_club_api_prefix" data-lpignore="true" placeholder="API prefix">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label">Languages</label>
                        <div class="controls">
                            <label class="checkbox inline">
                                <input name="languages[]" value="cy" type="checkbox"> CY
                            </label>
                            <label class="checkbox inline">
                                <input name="languages[]" value="en" type="checkbox"> EN
                            </label>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="edit_club_generation_feed">Generation Feed</label>
                        <div class="controls">
                            <input type="text" name="generation_feed" id="edit_club_generation_feed" data-lpignore="true" placeholder="Generation Feed ID">
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="edit_club_consumption_feed">Consumption Feed</label>
                        <div class="controls">
                            <input type="text" name="consumption_feed" id="edit_club_consumption_feed" data-lpignore="true" placeholder="Consumption Feed ID">
                        </div>
                    </div>

                    <div class="control-group" style="margin-bottom:0">
                        <label class="control-label" for="edit_club_color">Color</label>
                        <div class="controls">
                            <input type="color" name="color" id="edit_club_color" data-lpignore="true">
                            <p class="help-block">Custom Club Colour</p>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <span class="message" style="position:absolute"></span>
                    <span class="loader" style="margin-top:1.2em;position:absolute"><img src="data:image/gif;base64,R0lGODlhEgAPAPIAAPX19ZeXl5eXl7i4uNjY2AAAAAAAAAAAACH+GkNyZWF0ZWQgd2l0aCBhamF4bG9hZC5pbmZvACH5BAAFAAAAIf8LTkVUU0NBUEUyLjADAQAAACwAAAAAEgAPAAADHAi63P4wykmrvThXIS7n1tcBAwWSQwkQBKVqTgIAIfkEAAUAAQAsAAAAABIADwAAAx4Iutz+MMpJq23iAsF11sowXKJolSNAUKZKrBcMPgkAIfkEAAUAAgAsAAAAABIADwAAAxwIutz+MEogxLw4q6HB+B3XKQShlWWGmio7vlMCACH5BAAFAAMALAAAAAASAA8AAAMXCLrcvuLJ+cagOGtHtiKgB3RiaZ5oiiUAIfkEAAUABAAsAAAAABIADwAAAxQIuty+48knJCEz6827/2AojiSYAAAh+QQABQAFACwAAAAAEgAPAAADFAi63L7kyTemvTgvobv/YCiOJJAAACH5BAAFAAYALAAAAAASAA8AAAMTCLrc/jAqIqu9duDNu4/CJ45XAgAh+QQABQAHACwAAAAAEgAPAAADFAi63P4wykmrBeTqzTsbHiUIIZcAACH5BAAFAAgALAAAAAASAA8AAAMXCLrc/jDKSau9OOvtiBSYICrDQIFckwAAOwAAAAAAAAAAAA==" alt="" /></span>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Save Chanages</button>
                </div>
            </div>
        </form>
    </div>
</div>



<!-- END OF MODALS -->






<!-- JAVASCRIPT -->
<script>
    var path = "<?php echo $path; ?>";
    var session = <?php echo json_encode($session); ?>;
</script>
<script src="<?php echo $path; ?>Lib/misc/sidebar.js"></script>
<script src="<?php echo $path; ?>Modules/cydynni/cydynni.js"></script>
<script>
//initialise the sidebar
init_sidebar({
    menu_element: "#cydynni_menu",
    sidebar_visible: false
});
</script>