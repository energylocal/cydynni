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
                // if (session.admin) { window.location = "admin"; return false; }
                $("#login-block").hide();
                $("#household-status-block").show();
                $("#logout").show();
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

$("#logout").click(function() {
    $.ajax({                   
        url: path+"/logout",
        dataType: 'text',
        success: function(result) {
            $("#login-block").show();
            $("#household-status-block").hide();
            $("#logout").hide();
            session = false;
        }
    });
});
