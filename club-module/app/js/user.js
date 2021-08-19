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
          
              if (result.startingpage!=undefined && result.startingpage=="club\/admin") {
                  startingpage = "admin/clubs";
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
        url: path+"cydynni/passwordreset",                         
        data: "email="+email,
        dataType: 'json',
        success: function(result) {
            if (result.success!=undefined) {
                if (result.success) {
                    if (result.message!="Password recovery email sent!") {
                        $("#passwordreset").show();
                        $("#passwordreset-email").show();
                        $("#passwordreset-alert").html(result.message);
                        $("#passwordreset-title").html(t("Please enter email address to reset password"));
                    } else {
                        $("#passwordreset-title").html(t("Password recovery email sent! please check your email inbox"));
                        $("#passwordreset-cancel").html(t("Return to Login"));
                    }
                } else {
                    $("#passwordreset-alert").html(result.message);
                }
            } else {
                $("#passwordreset-alert").html(result);
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
