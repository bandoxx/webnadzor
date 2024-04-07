/*
          
Copyright (C) 2013-2014 by Intelteh d.o.o.
http://www.intelteh.hr/

*/
var logindata, message = '';
var loading = 'Učitavam..';
var redirecting = 'Prijavljeni ste, preusmjeravam..';
var redirecting_president = 'Prijavljeni ste kao operator, preusmjeravam..';
var error = 'Netočni podaci! Molimo pokušajte ponovo [<span id="timer"></span>]';
var unkwnerror = 'Nepoznata greška! Molimo pokušajte ponovo [<span id="timer"></span>]';
$(document).ready(function() {

    $('.ie #userbar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .titlebar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .block_cont').corner('bottom 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .error, .ie .warning, .ie .success, .ie .information').corner('5px'); /* IE BORDER-RADIUS FIX */
    $('#loginbox').hide().fadeIn(1000); /* LOGIN FADE */
    $("#loginform form").validate({ /* LOGIN VALIDATE */
        submitHandler: function(form) {
            $('#loginform form').hide();
            $('#logininfo').html(loading).fadeIn(600);
			logindata = {'username':$('#username').val(),
						'password':$('#password').val(),
						'troll':Math.random()};
			$.post(page_url + 'login/set', logindata)
			.always(function(data) {
				if (data == 'ok' || data == 'president') {
					message = redirecting;
					if (data == 'president') {
						message = redirecting_president;
					}
					$('#logininfo').fadeOut(500).hide(0, function() {
						$(this).html(message).fadeIn(600, function() {
						   setTimeout(function() {
							   $(location).attr('href', page_url);
						   }, 500);
						});
					});
				}
				else {
					message = unkwnerror;
					if (data == 'fail') {
						message = error;
					}
					$('#logininfo').fadeOut(500).hide(0, function() {
						$(this).html(message).fadeIn(500);
						var timer, counter = 5;
						$('#timer').html(counter);
						timer = setInterval(function() {
						$('#timer').html(--counter);
						if (counter == 0) {
							$('#logininfo').fadeOut(500).hide(0, function() {
								$('#loginform form').fadeIn(500);
							});
							clearInterval(timer);
						};
						}, 1000);
					});
				}
			});
            return false;
        }
    });
});
