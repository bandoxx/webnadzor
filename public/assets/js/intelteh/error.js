/*
          
Copyright (C) 2012-2013 by Intelteh d.o.o.
http://www.intelteh.hr/

*/
$(document).ready(function() {
    var timer, counter = 4;
    $("#timer").html(counter);
    timer = setInterval(function() {
        $("#timer").html(--counter);
        if (counter == 0) {
            $(location).attr('href', page_url);
            clearInterval(timer);
        };
    }, 1000);
});
