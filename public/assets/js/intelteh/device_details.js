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

    $('.hide_btn').click(function() { /* ALERT HIDE */
        $(this).parent().fadeOut(300);
        return false;
    });

    setInterval(function() {
        /////////////////////////// DATA
        $.getJSON(page_url + 'device_details/' + device_id + '/data_feed.json', {troll:Math.random()}, function(dadata, s, r) {
			if (r.getResponseHeader('Requires-Auth') == 1) {
				$(location).attr('href', page_url);
				return false;
			}
            $.each(dadata, function(i, bdata) {
              if (i.match(/^\w+_value$|^device_date$/)) {
                if ($('#'+i).html() != bdata) {
                    $('#'+i).fadeOut(300, function() {
                        $(this).html(bdata).fadeIn(300);
                    });
                }
              }
              /*else if (i.match(/^\w+_image$/)) {
                  if (bdata == 'disabled') {
                    if ($('#'+i+' img').length) {
                        $('#'+i+' img').fadeOut(300, function() { $(this).remove(); });
                    }
                  }
                  else if ($('#'+i+' img').attr('src') != bdata) {
                    $('#'+i).addClass('detimage_zombie');
                    if ($('#'+i+' img').length) {
                        $('#'+i+' img').fadeOut(300, function() {
                            $(this).remove();
                            var img = new Image();
                            $(img).load(function() {
                                $(this).hide();
                                $('#'+i).removeClass('detimage_zombie').append(this);
                                $(this).fadeIn(600);
                            }).attr('src', bdata);
                        });
                    }
                    else {
                        var img = new Image();
                        $(img).load(function() {
                            $(this).hide();
                            $('#'+i).removeClass('detimage_zombie').append(this);
                            $(this).fadeIn(600);
                        }).attr('src', bdata);
                    }
                  }
              }*/
            });
        });
        /////////////////
        /////////////////////////// ALARMS
        var notif = '';
        var arridalarm = [];
        $.getJSON(page_url + 'device_details/' + device_id + '/alarm_feed.json', {troll:Math.random()}, function(jadata, s, r) {
			if (r.getResponseHeader('Requires-Auth') == 1) {
				$(location).attr('href', page_url);
				return false;
			}
            $.each(jadata, function(i, adata) {
                arridalarm[adata.id] = 1;
                if (!$('#notifications #' + adata.id).length){
                    notif =
                        '<div id="'+ adata.id +'" class="error grid_12">\
                            <h3>'+ adata.type +' aktivan na '+ adata.sensor +'!</h3>\
                            <!-- Notification ID '+ adata.id +' -->\
                        </div>';
                    $(notif).appendTo('#notifications').fadeIn(300).corner('5px');
                }
            });
            $('#notifications').children().each(function() {
                if (!arridalarm[$(this).attr('id')]){
                    $(this).fadeOut(300, function() { $(this).remove(); });
                }
            });
        });
        /////////////////
    },60000);

    $('.tipTop').tipsy({gravity: 's', fade: true}); /* TOOLTIP CLASS */
    $('.tipBot').tipsy({gravity: 'n', fade: true}); /* TOOLTIP CLASS */
    $('.tipLeft').tipsy({gravity: 'e', fade: true}); /* TOOLTIP CLASS */
    $('.tipRight').tipsy({gravity: 'w', fade: true}); /* TOOLTIP CLASS */

    $('.ie #userbar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .titlebar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .block_cont').corner('bottom 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .error, .ie .warning, .ie .success, .ie .information').corner('5px'); /* IE BORDER-RADIUS FIX */
});
