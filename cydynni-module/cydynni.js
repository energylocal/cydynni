/*
REQUIRES GLOBAL path & session VARIABLES
*/
$(function(){
    var users = [];
    if (session.admin==1) {
        load_users();
        load_clubs();
    }

    //FILTER USER LIST WHEN CLUBS CLICKED
    document.getElementById('clublist').addEventListener('click',function(event){
        if(event.target && event.target.nodeName == 'A'){
            event.preventDefault();
            //make all the siblings not active
            removeActiveChildrenFrom('#clublist');
            event.target.classList.add('active');
            club_id = event.target.dataset.slug || "";
            load_users(club_id);
        }
    })
    //SHOW ALL USERS by default OR WHEN THE all-clubs LINK CLICKED
    document.getElementById('all-clubs').addEventListener('click', function(event){
        event.preventDefault();
        load_users();
    });

    $('form .loader').hide();//hide all ajax loaders within forms as page loads



    /**
     * REMOVE 'active' CLASS FROM ALL CHILD ELEMENTS
     * @parm <string|htmlElement> parent - strings are used to querySelect a parent htmlElement
     */
    function removeActiveChildrenFrom(parent){
        //if string passed use that string to identify the parent
        active_className = 'active';
        parent = typeof parent === 'string' ? document.querySelector(parent) : parent;
        parent.querySelectorAll('.'+active_className).forEach(function(item){
            item.classList.remove(active_className);
        });
    }
   
    /**
     * CREATE, READ, UPDATE & DELETE clubs or members
     * 
     * uses the <form> action for the API endpoint url.
     * methods relate to actions: POST=CREATE, GET=READ, PUT=UPDATE, DELETE=DELETE
     * 
     * @param {event} event click|submit event
     * @param {string} method GET|POST|PUT|DELETE
     * @param {string} successMessage message to show the user on success
     * @param {string} errorMessage message to show the user on error
     */
    function crud(event, method, successMessage, errorMessage) {
        event.preventDefault();
        $form = event.target.form ? $(event.target.form) : $(event.target) // set a form jquery object
        loader = $form.find('.loader');//show loading animation
        message = $form.find('.message');//show user feedback
        timeout = false; //used to interrupt animations
        //ASYNC FUNCTIONS
        saved = function( data, textStatus, jqXHR ) {
            if(data.hasOwnProperty('success')&&!data.success){
                message.text(errorMessage).fadeIn();
            }else{
                $form.find(':text,:checkbox').prop("disabled", true);
                message.text(successMessage).fadeIn();
                window.setTimeout(function(){
                    load_clubs();
                    $form.parents('.modal').modal('hide');
                }, 2000);
            }
        }
        failed = function(jqXHR, textStatus, errorThrown){ message.text('error: '+errorThrown).fadeIn(); }
        finished = function(data, textStatus, jqXHR){
            loader.fadeOut('fast');
            timeout = window.setTimeout(function(){ 
                message.fadeOut('fast')
                $form.parent('.modal').modal('hide')
            }, 2000);
        }
        //PRE AJAX FUNCTIONS
        loader.fadeIn();
        message.hide().text('');
        clearTimeout(timeout);
        //AJAX REQUEST AND PROMISE CALLBACKS
        $.ajax({
            url: $form.attr('action'),
            data: $form.serialize(),
            method: method
        })
        .done(saved)
        .fail(failed)
        .always(finished);
    }

    $('#create-club').on('submit', function(event){
        crud(event,'POST','Deleted','Error: Not created. Try again.')
    });

    $('#delete-club').on('click', function(event){
        if(confirm('Are you sure you want to delete?')){
            crud(event,'DELETE','Deleted','Error: Not deleted. Try again.')
        }
    })

    $('#edit-club').on('submit', function(event){
        crud(event,'PUT','Saved','Error: Not saved. Try again.')
    });


    //load clubs into a dropdown
    $('#newUserModal, #editUserModal').on('show', function(event){
        modal = this;
        select = modal.querySelector('[name="club_id"]');
        $.get( path + 'cydynni/admin/clubs')
        .done(function(clubs) {
            clubs.forEach(function(club) {
                $(`<option class="added" value="${club.id}">${club.name}</option>`).appendTo($(select));
            });
            for(i=0; i<select.options.length; i++){
                if(select.options[i].value === select.dataset.value){
                    select.options[i].selected = true;
                }
            }
        })
        .fail(function() {
            console.log( "error" );
        })
        .always(function() {
            //hide loader
        });
    }); 

    //clear select values once modal is hidden
    $('#newUserModal,#editUserModal').on('hidden', function(event){
        $modal = $(this);
        $select = $modal.find('[name="club_id"]');
        $select.find('.added').remove();
    });

    $('#edit-user').on('submit', function(event){
        crud(event.target,'PUT','Saved','Error: Not Saved. please try again');
    });

    /**
     * set form input value based on input name attribute
     * 
     * @param {HTMLElement} form 
     * @param {string} name 
     * @param {string} value 
     */
    function setInputValue(form, name, value){
        input = form.querySelector('[name="'+name+'"]');
        if (!input){
            return;
        }else{
            if(input.tagName=='SELECT'){
                input.dataset.value = value;
            }else{
                input.value = value;
            }
        }
        group = form.querySelectorAll('[name="'+name+'[]"]');
        group.forEach(function(input){
            if(input.type=="checkbox"){
                if (value.indexOf(input.value)>-1){
                    input.checked = true;
                }
            }
        });
    }
    /**
     * @param <int> club_id
     */
    function showClubModal(club_id){
        //populate club details in the modal forms
        form = document.getElementById('edit-club');
        form.querySelector('[name="club_id"]').value = club_id;
        $.get(path+'cydynni/admin/clubs/'+club_id)
        .success(function(data){
            //data is array of clubs
            for (var i in data[0]) {
                setInputValue(form, i, data[0][i]);
            }
        })
        $(form).find(':text,:checkbox').removeAttr("disabled");
    }
    /**
     * @param <int> user_id
     */
    function showUserModal(userid){
        //populate user details in the modal form
        form = document.getElementById('edit-user');
        form.querySelector('[name="userid"]').value = userid;
        $.get(path+'cydynni/admin/users/'+userid)
        .success(function(data){
            cydynni_user = data[0]
            //data is array of users
            for (var i in cydynni_user) {
                if(typeof cydynni_user[i] != 'object'){
                    setInputValue(form, i, cydynni_user[i]);
                }
            }
            for (var k in cydynni_user.user) {
                setInputValue(form, k, cydynni_user.user[k]);
            }
            setInputValue(form, 'club_id', cydynni_user.clubs_id);
            setInputValue(form, 'email-original', cydynni_user.user.email);
            setInputValue(form, 'username-original', cydynni_user.user.username);
            form.querySelector('[name="club_id"]').dataset.value = cydynni_user.clubs_id;
        })
    }

    //call the edit_club function on click of the modal overlay trigger
    $('#clublist').on('click', '.edit_club', function(event){
        showClubModal($(this).data('club_id'));
    })

    //call the edit_user function on click of the modal overlay trigger
    $('#userlist').on('click', '.edit-user-button', function(event){
        showUserModal($(this).data('user_id'));
    });

    //populate sidebar menu
    function load_clubs(){
        $('#clublist').find('.added').each(function(item){
            $(this).remove();
        });

        $.get(path+"cydynni/admin/clubs")
        .done(function(result) {
            result.reverse();//items prepended to list
            result.forEach(function(club){
                template = $('#clublist-item').clone();
                item = $(template.html()).prependTo('#clublist');
                //set club id into club edit button
                item.find('button').data('club_id', club.id);
                //set link that filters uses by club
                item.find('a')
                    .attr('href', item.find('a').attr('href') + club.slug)
                    .text(club.name)
                    .attr('data-club_id', club.id)
                    .attr('data-slug', club.slug);
            });
        })
        .fail(function() {
            console.log( "error" );
        })
        .always(function(){
            $('#clublist').fadeIn();
        });
    }
    /**
     * load users as expandable list
     * this also loads club detail for each club
     * 
     * @param {int} club_id 
     */
    function load_users(club_id) {
        $("#login-block").hide();
        $("#admin-block").show();
        club_id = club_id || null;
        $('#userlist').html('');
        //populate user list
        url = path + 'cydynni/admin/users';
        url += club_id ? '/'+club_id : ''; 
        $.ajax({
            url: url,
            dataType: 'json',
            success: function(result) {
                users = result;
                if(result.length>0){
                    for (var z in result) {
                        template = $('#userlist-item').clone();
                        //append copy of template with replaced values for each user
                        club = result[z].club[0];
                        user = result[z].user;
                        if(!user) return;
                        cydynni_user = result[z];
                        
                        $(template.html()).appendTo('#userlist')
                        .attr('href',cydynni_user.userid)
                        .find('.email').text(user.email).end()
                        .find('.token').text(cydynni_user.token).end()
                        .find('.apikey_read').text(user.apikey_read).end()
                        .find('.welcomedate').text(user.welcomedate).end()
                        .find('.reportdate').text(cydynni_user.reportdate).end()
                        .find('.username').text(user.username).end()
                        .find('.mpan').text(cydynni_user.mpan).end()

                        .find('.registeremail').data('userid',cydynni_user.userid).end()
                        .find('.reportemail').data('userid',cydynni_user.userid).end()
                        .find('.edituser').data('userid',cydynni_user.userid).end()
                        .find('.editclub').data('clubid',club.id).end()

                        .find('.club-name').text(club.name).end()
                        .find('.club-slug').text(club.slug).end()
                        .find('.generator').text(club.generator).end()
                        .find('.root_token').text(club.root_token).end()
                        .find('.api_prefix').text(club.api_prefix).end()
                        .find('.languages').text(club.languages).end()
                        .find('.generation_feed').text(club.generation_feed).end()
                        .find('.consumption_feed').text(club.consumption_feed).end()
                        .find('.color').text(club.color).end()
                        .find('.bg-color').css('background-color', club.color).end()
                        .find('.club-id').text(club.id).end()
                        .find('.club-name').text(club.name).end()

                        .find('.accordion-toggle').attr('href','#collapse'+cydynni_user.userid).end()
                        .find('.accordion-body').attr('id','collapse'+cydynni_user.userid).end()
                        .find('.edit-user-button').data('user_id',cydynni_user.userid);
                    }
                }else{
                    //none found
                    template = $('#userlist-item').clone();
                    $('<h4>0 Results</h4>').appendTo('#userlist')
                }
            },
            error: function(xhr, message, error){
                console.log(error,message);
            }
        });
    }
    //expand the list of users on click of the title
    $(document).click('#userList', function(event){
        expandable = event.target.tagName == 'A' ? $(event.target) : $(event.target).parents('a').first();
        if(event.target.id == '#userList') {
            expandable.toggleClass('expanded');
            event.preventDefault();
        }
    });

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
});// end of jquery ready()
