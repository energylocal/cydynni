$("#login").click(function() {
    var email = $("#email").val();
    var password = $("#password").val();

    $.ajax({
        type: 'POST',                                    
        url: path+"login",                         
        data: "email="+email+"&password="+password,
        dataType: 'json',
        success: function(result) {
            if (result.userid!=undefined) {
                session = result;
                $("#user-email").html(session.email);
                
                $("#login-block").hide();
                $("#logout").show();
                $("#account").show();
                $("#reports").show();
                $(".household-block").show();

                household_summary_load();
                household_bargraph_load();
                household_pie_draw();
                household_bargraph_resize();
                
            } else {
                $("#alert").html(result);
            }
        }
    });
});

$("#logout").click(function(event) {
    event.stopPropagation();
    $.ajax({                   
        url: path+"/logout",
        dataType: 'text',
        success: function(result) {
            $("#login-block").show();
            $("#logout").hide();
            $("#account").hide();
            $("#reports").hide();
            $(".household-block").hide();
            session = false;
            // window.location = "";
        }
    });
});

$("#passwordreset-start").click(function() {
    $("#login-block").hide();
    $("#passwordreset-block").show();
    $("#passwordreset-title").html(t("Please enter email address to reset password"));
    $("#passwordreset-cancel").html(t("Cancel"));
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
    $("#passwordreset-title").html(t("Password reset in progress.."));
    $.ajax({                                      
        url: path+"/passwordreset",                         
        data: "email="+email,
        dataType: 'text',
        success: function(result) {
            if (result!="Email sent") {
                $("#passwordreset").show();
                $("#passwordreset-email").show();
                $("#passwordreset-alert").html(result);
                $("#passwordreset-title").html(t("Please enter email address to reset password"));
            } else {
                $("#passwordreset-title").html(t("Password recovery email sent! please check your email inbox"));
                $("#passwordreset-cancel").html(t("Return to Login"));
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
