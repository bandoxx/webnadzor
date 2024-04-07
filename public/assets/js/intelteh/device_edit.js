/*

Copyright (C) 2013 by Intelteh d.o.o.
http://www.intelteh.hr/

*/
$(document).ready(function() {

    $('.toggle').click(function() { /* BLOCK TOGGLE */
        $(this).toggleClass('show');
        $(this).parent().parent().children('.block_cont').slideToggle(300);
        $(this).parent().toggleClass('show');
        return false;
    });

    $('.checkboxui').buttonset();
    $('.select').multiselect({multiple: false, header: false, selectedList: 1});    /* SELECT */
    $('.ui-multiselect').width(290);

    $('.tipTop').tipsy({gravity: 's', fade: true}); /* TOOLTIP CLASS */
    $('.tipBot').tipsy({gravity: 'n', fade: true}); /* TOOLTIP CLASS */
    $('.tipLeft').tipsy({gravity: 'e', fade: true}); /* TOOLTIP CLASS */
    $('.tipRight').tipsy({gravity: 'w', fade: true}); /* TOOLTIP CLASS */

    $('.ie #userbar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .titlebar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .block_cont').corner('bottom 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .error, .ie .warning, .ie .success, .ie .information').corner('5px'); /* IE BORDER-RADIUS FIX */

	$('#location').leafletLocationPicker();

    $('#settingsform').validationEngine('attach', {
    onValidationComplete: function(form, status){
        var form_data = $('#settingsform').serialize();
        if (status === true) {

            var loading = 'Učitavam..';
            var ok = 'Promjene spremljene!';
            var error = 'Greška! Pokušajte ponovo.';

            $('#infomsg').hide().html(loading).fadeIn(1000, function() {
                $.post(page_url + 'device_edit/' + device_id + '/save_settings', $.param({troll:Math.random()}) + "&" + form_data, 
                function(data, s, r) {
					if (r.getResponseHeader('Requires-Auth') == 1) {
						$(location).attr('href', page_url);
						return false;
					}
                    if (data == 'ok') {
                        $('#infomsg').fadeOut(1000).hide(0, function() {
                           $(this).html(ok).fadeIn(1000, function() {
                               setTimeout(function() {
                                   $('#infomsg').fadeOut(1000, function() {
                                       $(this).hide();
                                   });
                               }, 4000);
                           });
                        });
                    }
                    else if (data == 'fail') {
                        $('#infomsg').fadeOut(1000).hide(0, function() {
                           $(this).html(error).fadeIn(1000, function() {
                               setTimeout(function() {
                                   $('#infomsg').fadeOut(1000, function() {
                                       $(this).hide();
                                   });
                               }, 4000);
                           });
                        });
                    }
                    else {
                        console.log('response data: ' + data);
                    }
                });
            });

        }
    }
    });/* VALIDATION ENGINE */

    $('#apply').click(function() {
        $('#settingsform').submit();
    });

});
