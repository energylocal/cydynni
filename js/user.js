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
                $("#user-email").html(session.email);
                // if (session.admin) { window.location = "admin"; return false; }
                $("#login-block").hide();
                $("#household-status-block").show();
                $(".logout").show();
                $(".myaccount").show();
                household_load();
            } else {
                $("#alert").html(result);
            }
        }
    });
});

$("#register").click(function() {
    var email = $("#email").val();
    var password = $("#password").val();

    $.ajax({                                      
        url: path+"/register",                         
        data: "email="+email+"&password="+password,
        dataType: 'text',
        success: function(result) {
            $("#alert").html(result);
        }
    });
});

$(".logout").click(function() {
    $.ajax({                   
        url: path+"/logout",
        dataType: 'text',
        success: function(result) {
            $("#login-block").show();
            $("#household-status-block").hide();
            $(".logout").hide();
            $(".myaccount").hide();
            session = false;
            window.location = "";
        }
    });
});

$("#passwordreset-start").click(function() {
    $("#login-block").hide();
    $("#passwordreset-block").show();
    $("#passwordreset-title").html("Please enter email address to reset password");
    $("#passwordreset-cancel").html("Cancel");
});

$("#passwordreset-cancel").click(function() {
    $("#passwordreset-block").hide();
    $("#login-block").show();
});

$("#passwordreset").click(function() {
    var email = $("#passwordreset-email").val();
    $("#passwordreset").hide();
    $("#passwordreset-email").hide();
    $("#passwordreset-alert").html("");
    $("#passwordreset-title").html("Password reset in progress..");
    $.ajax({                                      
        url: path+"/passwordreset",                         
        data: "email="+email,
        dataType: 'text',
        success: function(result) {
            if (result!="Email sent") {
                $("#passwordreset").show();
                $("#passwordreset-email").show();
                $("#passwordreset-alert").html(result);
                $("#passwordreset-title").html("Please enter email address to reset password");
            } else {
                $("#passwordreset-title").html("Password recovery email sent! please check your email inbox");
                $("#passwordreset-cancel").html("Return to Login");
            }
            
        }
    });
});

$(".myaccount").click(function() {
  page = "myaccount";
  window.location ="#myaccount"
  $(".page").hide();
  $(".page[page="+page+"]").show();
});

$("#change-password").click(function() {
    var current_password = $("#change-password-current").val();
    var new_password = $("#change-password-new").val();
    console.log(current_password);
    console.log(new_password);
    $("#change-password-alert").html("Request sent");
    $.ajax({   
        type: "POST",           
        url: path+"changepassword",                         
        data: "old="+current_password+"&new="+new_password,
        dataType: 'text',
        success: function(result) {
            console.log(result);
            $("#change-password-alert").html(result);
        }
    });
});
