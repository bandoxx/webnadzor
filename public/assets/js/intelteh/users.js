/*
          
Copyright (C) 2012-2013 by Intelteh d.o.o.
http://www.intelteh.hr/

*/
$(document).ready(function() {

    $('.toggle').click(function() { /* BLOCK TOGGLE */
        $(this).toggleClass('show');
        $(this).parent().parent().children('.block_cont').slideToggle(300);
        $(this).parent().toggleClass('show');
        return false;
    });

    var delete_parent, delete_id, delete_username = '';
    $('.delete').click(function() { /* DELETE USER */
		delete_parent = $(this).parent();
        delete_id = $(delete_parent).attr('id');
        
        $('#del_username').html($(delete_parent).data('username'));
        
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
                'Obriši korisnika': function() {
                    $.post(page_url + 'users/delete', {user_id:delete_id,troll:Math.random()}, function(deldata, s, r) {
						if (r.getResponseHeader('Requires-Auth') == 1) {
							$(location).attr('href', page_url);
							return false;
						}
                        if (deldata == 'ok') {
                            $(location).attr('href', page_url + 'users');
                        }
                        else {
                            alert('Greška! Molimo pokušajte ponovo');
                            console.log('error data: ' + deldata);
                        }
                    });
                },
                'Odustani': function() {
                    $(this).dialog('close');
                }
            },
            close: function() {
                $('#del_username').html('');
                $(this).dialog('destroy');
            }
        });
        return false;
    });

	var adedit;
	$('#editform').validationEngine('attach', {
		onValidationComplete: function(form, status){
			var form_data = $('#editform').serialize();
			if (status === true) {
				adedit = [];
				$('#newallowed_devices').multiselect('getChecked').each(function() {
					adedit.push($(this).val());
				})
				adedit = adedit.join(',');
				$.post(page_url + 'users/edit', $.param({troll:Math.random(),
					user_id:edit_id,ad:adedit}) + "&" + form_data, function(data) {
					if (data == 'ok') {
						$(location).attr('href', page_url + 'users');
					}
					else {
						alert('Greška! Molimo pokušajte ponovo');
						console.log('response data: ' + data);
					}
				});

			}
		}
	});/* VALIDATION ENGINE */

    var edit_parent, edit_id, edit_username, edit_permissions, edit_ad = '';
    $('div.actionbar a.edit').click(function() { /* EDIT ICON */

        edit_parent = $(this).parent();

        edit_id = $(edit_parent).attr('id');
        edit_username = $(edit_parent).data('username');
        edit_permissions = $(edit_parent).data('permissions');
        edit_ad = $(edit_parent).data('ad').split(',');

        $('#edit-form').attr('title', 'Editing user - "' + edit_username + '"');
        
        if (edit_permissions == 1) {
            $('#r4').prop('checked', true).button('refresh');

            //$('#newallowed_devices').multiselect('enable');
            /*find('input[name="multiselect_newallowed_devices"]').each(function(){
				$(this).prop('checked', true);
                if ($.inArray($(this).val(), edit_ad)) {
                    $(this).prop('checked', true);
                }
            });*/
            //$('#newallowed_devices').multiselect('refresh');//.multiselect('disable');
        }
        else if (edit_permissions == 2) {
            $('#r5').prop('checked', true).button('refresh');
        }
        else if (edit_permissions == 3) {
            $('#r6').prop('checked', true).button('refresh');
        }
        
        $('#c1').click(function(){
            if ($('#c1').is(':checked')) {
                $('#editform input[type=password]').removeAttr('disabled');
            }
            else {
                $('#editform input[type=password]').attr('disabled', 'disabled');
            }
        });
        $('#c2').click(function(){
            if ($('#c2').is(':checked')) {
                $('#editform input[type=radio]').button('enable');
                if (edit_permissions == 1) {
                    $widget.multiselect('enable');
                }
            }
            else {
                $('#editform input[type=radio]').button('disable');
                $widget.multiselect('disable');
            }
        });
        $('#editform input[type=radio]').click(function(){
            if ($('#r4').is(':checked')) {
                $widget.multiselect('enable');
            }
            else {
                $widget.multiselect('disable');
            }
        });
        $('#edit-form').dialog({
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
                'Spremi promjene': function() {
                    $('#editform').submit();
                    return false;
                },
                'Odustani': function() {
                    $(this).dialog('close');
                }
            },
            close: function() {
                $('#editform').validationEngine('hideAll');
                $('#editform input[type=password]').val('').attr('disabled', 'disabled');
                $('#editform label').removeClass('ui-state-active');
                $('#editform input[type=radio]').button('disable');
                $widget.multiselect('uncheckAll');
                $widget.multiselect('disable')
                $(this).dialog('destroy');
            }
        });
        return false;
    });

	var ad;
    $('#addform').validationEngine('attach', {
        onValidationComplete: function(form, status){
            var form_data = $('#addform').serialize();
            if (status === true) {
                ad = [];
                $('#allowed_devices').multiselect('getChecked').each(function() {
                    ad.push($(this).val());
                })
                ad = ad.join(',');
                $.post(page_url + 'users/add', $.param({troll:Math.random(),ad:ad}) + "&" + form_data, function(data, s, r) {
					if (r.getResponseHeader('Requires-Auth') == 1) {
						$(location).attr('href', page_url);
						return false;
					}
                    if (data == 'ok') {
                        $(location).attr('href', page_url + 'users');
                    }
                    else {
                        alert('Greška! Molimo pokušajte ponovo');
                        console.log('response data: ' + data);
                    }
                });

            }
        }
    });/* VALIDATION ENGINE */
       
    $('#useradd').click(function() { /* ADD USER */
        $('#addform input[type=radio]').click(function(){
            if ($('#r1').is(':checked')) {
                $widget.multiselect('enable');
            }
            else {
                $widget.multiselect('disable');
            }
        });
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
                'Kreiraj korisnika': function() {
                    $('#addform').submit();
                    return false;
                },
                'Odustani': function() {
                    $(this).dialog('close');
                }
            },
            close: function() {               
                $('#addform').validationEngine('hideAll');
                $('#addform input[type=text], input[type=password]').val('');
                $('#addform label').removeClass('ui-state-active');
                $widget.multiselect('uncheckAll').multiselect('disable');
                $(this).dialog('destroy');
            }
        });
        return false;
    });

    $('.radioui').buttonset();
    var $widget = $(".multiselect").multiselect({header: false, selectedList: 1});	/* MULTIPLE SELECT */
    $widget.multiselect('uncheckAll');
    $widget.multiselect('disable');

    $(".select").multiselect({multiple: false, header: false, selectedList: 1});	/* SELECT */

    $('.tipTop').tipsy({gravity: 's', fade: true}); /* TOOLTIP CLASS */
    $('.tipBot').tipsy({gravity: 'n', fade: true}); /* TOOLTIP CLASS */
    $('.tipLeft').tipsy({gravity: 'e', fade: true}); /* TOOLTIP CLASS */
    $('.tipRight').tipsy({gravity: 'w', fade: true}); /* TOOLTIP CLASS */

    $('.ie #userbar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .titlebar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .block_cont').corner('bottom 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .error, .ie .warning, .ie .success, .ie .information').corner('5px'); /* IE BORDER-RADIUS FIX */

    $("a.fancy").fancybox(); /* FANCYBOX CLASS */

    $('.table').wrap('<div class="table-wrap" />'); /* NORMAL TABLE */
    $('.table tr:even').addClass('even'); /* ODD/EVEN TABLEROW CLASS */
    $('.data-table').dataTable({
        'sDom': '<"data-table-top"><"clear"><"table-wrap"rt><"clear"><"data-table-bottom"><"clear">', 
        'bJQueryUI': true,
        'bPaginate': false,
        'bInfo': false,
        'bFilter': false,
        'aoColumnDefs': [{
                bSortable: false, aTargets: ['sorting_disabled']
            }],
        'fnDrawCallback': function(oSettings) {
            if (oSettings.bSorted || oSettings.bFiltered) {
                for (var i = 0, iLen = oSettings.aiDisplay.length; i < iLen; i++) {
                    $('td:eq(0)', oSettings.aoData[oSettings.aiDisplay[i]].nTr).html(i+1);
                }
            }
        }
    }); /* DATA TABLES */

});
