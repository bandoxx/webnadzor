$(document).ready(function() {

    $('.toggle').click(function() { /* BLOCK TOGGLE */
        $(this).toggleClass('show');
        $(this).parent().parent().children('.block_cont').slideToggle(300);
        $(this).parent().toggleClass('show');
        return false;
    });

    $('.tipTop').tipsy({gravity: 's', fade: true}); /* TOOLTIP CLASS */
    $('.tipBot').tipsy({gravity: 'n', fade: true}); /* TOOLTIP CLASS */
    $('.tipLeft').tipsy({gravity: 'e', fade: true}); /* TOOLTIP CLASS */
    $('.tipRight').tipsy({gravity: 'w', fade: true}); /* TOOLTIP CLASS */

    $('.ie #userbar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .titlebar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .block_cont').corner('bottom 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .error, .ie .warning, .ie .success, .ie .information').corner('5px'); /* IE BORDER-RADIUS FIX */

    $('#device_add').click(function() { /* ADD DEVICE */
      console.log('click');
        $('#add-form').dialog({
			autoOpen: true,
			show: {
				effect: 'drop',
				duration: 200
			},
			hide: {
				effect: 'drop',
				duration: 200
			},
            resizable: false,
            modal: true,
            buttons: {
                'Dodaj novu lokaciju': function() {
                    $('#addform').submit();
                    return false;
                },
                'Odustani': function() {
                    $(this).dialog('close');
                }
            },
            close: function() {
                $('#addform').validationEngine('hideAll');
                $('#addform label').removeClass('ui-state-active');
                $(this).dialog('destroy');
            }
        });
        $('#addform').validationEngine('attach', {
            onValidationComplete: function(form, status){
                var form_data = $('#addform').serialize();
                if (status === true) {

                    $.post(page_url + 'device_edit/device_add', $.param({troll:Math.random()}) + "&" + form_data, function(data, s, r) {
						if (r.getResponseHeader('Requires-Auth') == 1) {
							$(location).attr('href', page_url);
							return false;
						}
                        if (!isNaN(data)) {
                            $(location).attr('href', page_url + 'device_edit/' + data);
                        }
                        else {
                            alert('Greška! Molimo pokušajte ponovo');
                            console.log('response data: ' + data);
                        }
                    });

                }
            }
        });/* VALIDATION ENGINE */
        return false;
    });

    $('.radioui').buttonset();

    var otable = $('#otable').dataTable({
      pageLength: 10,
      language: {
        search: "Pretraga",
        loading: "Ucitavanje...",
        lengthMenu: '_MENU_ broj prikaza',
        emptyTable: 'Prazna tablica',
        info: 'Prikaz _START_ od _END_ od ukupno _TOTAL_ rezultata',
        infoEmpty: '',
        infoFiltered: '(filtrirano od _MAX_ rezultata)',
      },
      columnDefs: [
        { className: 'dt-center', targets: '_all' }
      ],
    }); /* DATA TABLES */

	/* UPDATE TABLE */
    setInterval(function() {
		otable.fnReloadAjax()
    },60000);

    var delete_parent, delete_id, delaction, delete_devnam = '';
    $('.delete').live('click', function(e) { /* DELETE DEVICE */

        delete_parent = $(this).parent();
        delete_id = $(delete_parent).attr('id');
        delete_devnam = $(delete_parent).data('devnam');

        $('#del_device_name').html(delete_devnam);
        $('#delform input[type=radio]').click(function(){
            $('#del_password').removeAttr('disabled');
        });

        $('#delete-confirm').dialog({
			autoOpen: true,
			show: {
				effect: 'drop',
				duration: 200
			},
			hide: {
				effect: 'drop',
				duration: 200
			},
            resizable: false,
            modal: true,
            buttons: {
                '!! POTVRDI !!': function() {
                    $('#delform').submit();
                    return false;
                },
                'Odustani': function() {
                    $(this).dialog('close');
                }
            },
            close: function() {
                $('#delform').validationEngine('hideAll');
                $('#delform input[type=password]').val('').attr('disabled', 'disabled');
                $('#delform label').removeClass('ui-state-active');
                $('#del_device_name').html('');
                $(this).dialog('destroy');
            }
        });
        $('#delform').validationEngine('attach', {
            onValidationComplete: function(form, status){
                var form_data = $('#delform').serialize();
                if (status === true) {
                    $.post(page_url + 'device_edit/' + delete_id + '/device_delete', $.param({troll:Math.random()}) + "&" + form_data,
                    function(data, s, r) {
						if (r.getResponseHeader('Requires-Auth') == 1) {
							$(location).attr('href', page_url);
							return false;
						}
                        if (data == 'ok') {
                            otable.fnReloadAjax();
                            $('#delete-confirm').dialog('close');
                        }
                        else {
                            alert('Greška! Molimo pokušajte ponovo');
                            console.log('response data: ' + data);
                        }
                    });
                }
            }
        });/* VALIDATION ENGINE */
        return false;
    });

	var iconljekarna = L.icon({
		iconUrl: page_url + 'data/images/mojaljekarna.png'
	});

	var map = L.map('map_canvas');

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	}).addTo(map);

	$.getJSON(page_url + 'overview/mapmarkers.json', {troll:Math.random()}, function(data) {
		var bounds = new L.LatLngBounds();
		$.each(data.places, function(i, item) {
			
			L.marker([item.lat, item.lng], {icon: iconljekarna}).addTo(map)
					.bindPopup(item.name);
					
			bounds.extend([item.lat, item.lng]);
			
		});
		map.fitBounds(bounds, {padding: [50,50]});
	});
});

