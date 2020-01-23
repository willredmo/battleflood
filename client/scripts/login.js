// For send requests to server
function MyXHR(getPost,d,context){
    //ajax shortcut
    return $.ajax({
        type: getPost,
        async: true,
        cache: false,
        url:'../server/mid.php',
        data:d,
        dataType:'json',
        context: context
    }).fail(function(err){
        console.log("AJAX error");
        console.log(err.responseText);
    });
}

$(document).ready(function() {
    // Init login

    $("#showCreate").click((e) => {
        $(".login-form").hide();
        $(".create-form").show();
    });

    $("#hideCreate").click((e) => {
        $(".login-form").show();
        $(".create-form").hide();
    });

    $("#loginButton").click((e) => {
        var data = {
            username: $(".login-form .username").val(),
            password: $(".login-form .password").val()
        }
        login(data);
    });
    
    $("#createButton").click((e) => {
        var data = {
            username: $(".create-form .username").val(),
            password: $(".create-form .password").val(),
            confirm: $(".create-form .confirm").val()
        }
        createAccount(data);
    });
});

function login(data) {
    MyXHR('post', { method: "login", a: "user", data: JSON.stringify(data) }, this).done((json) => {
        $(".login-form .password").val("");
        if (json.indexOf("Success") >= 0) {
            location.reload();
        } else {
            Snackbar.show({
                pos: "bottom-center",
                text: json,
                showAction: true,
                duration: 5000
            });
        }
    });
}

function createAccount(data) {
    MyXHR('post', { method: "createUser", a: "user", data: JSON.stringify(data) }, this).done((json) => {
        if (json.indexOf("Success") >= 0) {
            location.reload();
        } else {
            Snackbar.show({
                pos: "bottom-center",
                text: json,
                showAction: true,
                duration: 5000
            });
        }
    });
}