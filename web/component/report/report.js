var logout = document.getElementById("logout");

logout.addEventListener("click", function () {
    deleteAllCookies();
    $.get(url + "logout");
    loadComponent("login", {});
});

