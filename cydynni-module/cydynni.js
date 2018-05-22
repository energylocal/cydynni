/*
REQUIRES GLOBAL path & session VARIABLES
*/
var users = [];
if (session.admin==1) {
    load_users();
    load_clubs();
}
$(function(){
    //FILTER USER LIST WHEN CLUBS CLICKED
    document.getElementById('clublist').addEventListener('click',function(event){
        if(event.target && event.target.nodeName == 'A' && event.target.classList.contains('added')){
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

    $('form .loader').hide();//hide all ajax loaders within forms as page loads
    $('#create-club').on('submit', function(event){
        event.preventDefault();//stop default form submission
        $form = $(this);//set a form jquery object
        loader = $form.find('.loader');//show loading animation
        message = $form.find('.message');//show user feedback
        timeout = false;
        //ASYNC FUNCTIONS
        saved = function( data, textStatus, jqXHR ) {
            if(data.hasOwnProperty('success') && !data.success){
                message.text('Not saved! Your session has timed out. Please login.').fadeIn();
            }else{
                message.text('Saved').fadeIn();
            }
        }
        failed = function(jqXHR, textStatus, errorThrown){ message.text('error: '+errorThrown).fadeIn(); }
        finished = function(data, textStatus, jqXHR){
            loader.fadeOut('fast');
            timeout = window.setTimeout(function(){ message.fadeOut('fast')}, 2500 );
        }
        //PRE AJAX FUNCTIONS
        loader.fadeIn();
        message.hide().text('');
        clearTimeout(timeout);
        //AJAX REQUEST AND PROMISE CALLBACKS
        $.post(event.target.action, $(this).serialize())
        .done(saved)
        .fail(failed)
        .always(finished);
    });

    $('#edit-club').on('submit', function(event){
        event.preventDefault();//stop default form submission
        $form = $(this);//set a form jquery object
        loader = $form.find('.loader');//show loading animation
        message = $form.find('.message');//show user feedback
        timeout = false; //used to interrupt animations
        //ASYNC FUNCTIONS
        saved = function( data, textStatus, jqXHR ) {
            if(data.hasOwnProperty('success')&&!data.success){
                message.text('Not saved! Your session has timed out. Please login.').fadeIn();
            }else{
                $form.find(':text,:checkbox').prop("disabled", true);
                message.text('Saved').fadeIn();
                window.setTimeout(function(){
                    $form.parents('.modal').modal('hide');
                }, 2000);
            }
        }
        failed = function(jqXHR, textStatus, errorThrown){ message.text('error: '+errorThrown).fadeIn(); }
        finished = function(data, textStatus, jqXHR){
            loader.fadeOut('fast');
            timeout = window.setTimeout(function(){ message.fadeOut('fast')}, 2000 );
        }
        //PRE AJAX FUNCTIONS
        loader.fadeIn();
        message.hide().text('');
        clearTimeout(timeout);
        //AJAX REQUEST AND PROMISE CALLBACKS
        $.ajax({
            url: event.target.action,
            data: $(this).serialize(),
            method: 'PUT'
        })
        .done(saved)
        .fail(failed)
        .always(finished);
    });

})

//load clubs into a dropdown
$('#newUserModal,#editUserModal').on('show', function(event){
    $modal = $(this);
    $select = $modal.find('[name="club_id"]');
    $.get( path + 'cydynni/admin/clubs/')
    .done(function(clubs) {
        clubs.forEach(function(club) {
            $(`<option class="added" value="${club.id}">${club.name}</option>`).appendTo($select);
        });
        $select.val($select.data('value'));
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
    event.preventDefault();
    $form = $(this);//set a form jquery object
    loader = $form.find('.loader');//show loading animation
    message = $form.find('.message');//show user feedback
    timeout = false;
    //ASYNC FUNCTIONS
    saved = function( data, textStatus, jqXHR ) {
        if(data.hasOwnProperty('success')&&!data.success){
            message.text('Not saved! Your session has timed out. Please login.').fadeIn();
        }else{
            $form.find(':text,:checkbox').prop("disabled", true);
            message.text('Saved').fadeIn();
            window.setTimeout(function(){
                $form.parents('.modal').modal('hide');
            }, 2000);
        }
    }
    failed = function(jqXHR, textStatus, errorThrown){ message.text('error: '+errorThrown).fadeIn(); }
    finished = function(data, textStatus, jqXHR){
        loader.fadeOut('fast');
        timeout = window.setTimeout(function(){ message.fadeOut('fast')}, 2500 );
    }
    //PRE AJAX FUNCTIONS
    loader.fadeIn();
    message.hide().text('');
    clearTimeout(timeout);
    //AJAX REQUEST AND PROMISE CALLBACKS
    $.ajax({
        url: event.target.action,
        data: $(this).serialize(),
        method: 'PUT'
    })
    .done(saved)
    .fail(failed)
    .always(finished);

});


function setInputValue(form, name, value){
    input = form.querySelector('[name="'+name+'"]');
    if(input){
        input.value = value;
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
function edit_club(club_id){
    //populate club details in the modal forms
    form = document.getElementById('edit-club');
    form.querySelector('[name="club_id"]').value = club_id;
    $.get(path+'cydynni/admin/clubs/'+club_id)
    .success(function(data){
        for (var i in data) {
            setInputValue(form, i, data[i]);
        }
    })
    $(form).find(':text,:checkbox').removeAttr("disabled");
}
/**
 * @param <int> user_id
 */
function edit_user(user_id){
    //populate user details in the modal form
    form = document.getElementById('edit-user');
    form.querySelector('[name="user_id"]').value = user_id;
    $.get(path+'cydynni/admin/user/'+user_id)
    .success(function(data){
        for (var i in data) {
            setInputValue(form, i, data[i]);
        }
        $(form).find('[name="club_id"]').data('value',data.clubs_id);
        $(form).attr('action', $(form).attr('action')+"/"+data.clubs_id);
    })
}

//call the edit_club function on click of the modal overlay trigger
$('#clublist').on('click', '.edit_club', function(event){
    edit_club($(this).data('club_id'));
})

$('#userlist').on('click', '.edit-user-button', function(event){
    edit_user($(this).data('user_id'));
});

function load_clubs(){
    //populate sidebar menu
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
                .attr('data-slug', club.slug)
                .addClass('added');
        });
    })
    .fail(function() {
        console.log( "error" );
    })
    .always(function(){
        $('#clublist').fadeIn();
    });

}
function load_users(club_id) {
    $("#login-block").hide();
    $("#admin-block").show();
    club_id = club_id || 0;
    $('#userlist').html('');
    //populate user list
    $.ajax({
        url: club_id.length>0 ? path+"cydynni/admin/users/"+club_id : path+"cydynni/admin/users",
        dataType: 'json',
        success: function(result) {
            users = result;
            if(result.length>0){
                for (var z in result) {
                    template = $('#userlist-item').clone();
                    //append copy of template with replaced values for each user
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

                    .find('.club-name').text(result[z].club.name).end()
                    .find('.club-slug').text(result[z].club.slug).end()
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
                    .find('.accordion-body').attr('id','collapse'+result[z].id).end()
                    .find('.edit-user-button').data('user_id',result[z].id);
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
