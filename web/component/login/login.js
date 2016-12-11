
    var form = document.getElementById("login-form");
    var error = document.getElementById("login-error");


    form.addEventListener("submit", function (e) {
        e.preventDefault();
        //console.log(JSON.stringify(getFormData($(this))));
        $.post(url + 'login', getFormData($(this))).done(function (data) {
            //console.log(data);
            if (data.hasOwnProperty('error')) {
                //console.log("error_detected");
                error.innerHTML = '<div class="alert alert-danger">' + data.error.message + '</div>';
            } else if (data.hasOwnProperty('state')) {
                if (data.state == "loggedIn") {
                    loadComponent("report", data);
                }
            }
        }).fail(function (data) {
            error.innerHTML = '<div class="alert alert-danger">' + 'There are unidentified problems with service.' + '</div>';
            //console.log(data);
        });
        return false;

    });

