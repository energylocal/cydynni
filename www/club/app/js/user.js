$("#loginform").on("submit",function(event){
    event.preventDefault();

    var username = $("#username").val();
    var password = $("#password").val();
    var rememberme = $("#rememberme")[0].checked;
    if (rememberme) rememberme = 1; else rememberme = 0;

    var result = {};
    $.ajax({
      type: "POST",
      url: path+"club/login.json",
      data: "&username="+encodeURIComponent(username)+"&password="+encodeURIComponent(password)+"&rememberme="+rememberme,
      dataType: "text",
      async: false,
      success: function(data_in)
      {
          console.log(data_in)
          try {
              result = JSON.parse(data_in);
              if (result.success==undefined) result = data_in;
          } catch (e) {
              result = data_in;
          }
         
          if (result.success) {
              var startingpage = "club/?household";
          
              if (result.startingpage!=undefined && result.startingpage=="account\/list") {
                  startingpage = "account/list";
              }
              
              window.location.href = path+startingpage;
          } else {
              $("#alert").html(result.message);
          }
          
          /*
          session = result.session;

          $("#login-block").hide();
          $("#logout").show();
          $("#account").show();
          $("#reports").show();
          $(".household-block").show();
          $("#alert").html("");

          household_summary_load();
          household_bargraph_load();
          household_pie_draw();
          household_bargraph_resize();
          */
      },
      error: function (xhr, ajaxOptions, thrownError) {
        if(xhr.status==404) {
            result = "404 Not Found: Is modrewrite configured on your system?"
        } else {
            result = xhr.status+" "+thrownError;
        }
        $("#alert").html(result);
      }
    });
});

$("#logout").click(function(event) {
    event.stopPropagation();
    $.ajax({                   
        url: club_path+"logout",
        dataType: 'text',
        success: function(result) {
            window.location = "/";
            /*
            $("#login-block").show();
            $("#logout").hide();
            $("#account").hide();
            $("#reports").hide();
            $("#alert").html("");
            $(".household-block").hide();
            session = false;
            */
        }
    });
});

// Start of password reset - 
// displays necessary HTML elements for the user to enter their recovery email
$("#passwordreset-start").click(function() {
    $("#login-block").hide();
    $("#passwordreset-block").show();
    $("#passwordreset-title").html(t("Please enter email address to reset password"));
    $("#passwordreset-cancel").html(t("Cancel"));
});

$("#passwordreset-new-cancel").click(function() {
    $("#passwordreset-block").hide();
    $("#login-block").show();
});

// Second stage of password reset -
// takes users recovery email, 
// then sends an ajax request to generate a password reset token and supply this token via email
$("#passwordreset").click(function() {
    var email = $("#passwordreset-email").val();
    $("#passwordreset").hide();
    $("#passwordreset-email").hide();
    $("#passwordreset-alert").html("");
    $("#passwordreset-title").html(t("Password reset in progress.."));
    $.ajax({                                      
        // routes are laid out in club_controller.php
        // this ajax request is then routed to the 'passwordreset_generation' function in user_model.php
        type: "POST",
        url: path+"cydynni/passwordreset_generation",                         
        data: "email="+email,
        dataType: 'json',
        success: function(result) {
            if (result.success===undefined) {
                $("#passwordreset-alert").html(result);
                return;
            }
            if (!result.success) {
                $("#passwordreset-alert").html(result.message);
                return;
            }
            $("#passwordreset-title").html(t("Password recovery email sent! Please check your email inbox."));
            $("#passwordreset-cancel").html(t("Return to Login"));
                    
        },
        error: function(result) {
            alert("An error has occured when resetting the password. Please try again later or contact Energy Local.")
            console.log(JSON.stringify(result));
        }
    });
});

// Final stage of password reset - 
// takes users new password, checks that they have entered it correctly twice
// then sends an ajax request to change their password.
$("#passwordreset-new").click(function() {
    var new_password = $("#passwordreset-new-password").val();
    var new_password_confirm = $("#passwordreset-new-confirm").val();
    if (new_password == new_password_confirm) {
        $.ajax({                                 
            // routes are laid out in club_controller.php
           // this ajax request is then routed to the 'passwordreset_reset' function in user_model.php
            type: "POST",
            url: path+"cydynni/passwordreset_reset",                         
            data: {
		    "token": token,
		    "new_password": new_password,
	    },
            dataType: 'json',
            success: function(result) {
                if (result.success == true) {
                    $("#passwordreset-new-input").hide();
                    $("#passwordreset-new-title").html(t("Password successfully changed!"));
                } else if (result.duplicate) {
                    $("#passwordreset-new-title").html(t("Please enter a password that isn't the same as your current password."));
                } else if (!result.duplicate) {
                    $("#passwordreset-new-title").html(t("Password reset token cannot be found - it may have expired. Please restart the password reset process."));
                }
            }
        })
    } else {
        // if user has not correctly entered the same password twice - alert them, and let them try again
        $("#passwordreset-new-title").html(t("Passwords do not match. Please try again."));
    }
})

$(".myaccount").click(function() {
  page = "myaccount";
  window.location ="#myaccount"
  $(".page").hide();
  $(".page[page="+page+"]").show();
});

$("#change-password").click(function() {
    var current_password = $("#change-password-current").val();
    var new_password = $("#change-password-new").val();
    var new_password_confirm = $("#change-password-new-confirm").val();
    if(new_password!=new_password_confirm){
        $("#change-password-alert").html(t("New passwords don't match"));
    }else{
        $("#change-password-alert").html(t("Request sent"));
        $.ajax({   
            type: "POST",           
            url: club_path+"changepassword",                         
            data: "old="+current_password+"&new="+new_password,
            dataType: 'text',
            success: function(result) {
                $("#change-password-alert").html(t(result));
            }
        });
    }
});
