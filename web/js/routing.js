$.get(url+"report").done(function(data){
    //console.log(data);
    if(data.hasOwnProperty('report')){
        loadComponent("report",data);
    } else {
        loadComponent("login",{});
    }
});