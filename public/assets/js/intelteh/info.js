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

    var edit_form = '';
    $('#editinfo').click(function() { /* EDIT ICON */

        edit_form = $('#edit-form');

        $.each(['host', 'username', 'password'], function(i2, val) {
            $('#' + val).val(edit_form.data(val));
        });

        $(edit_form).dialog({
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
                $('#editform input').val('');
                $('#editform').validationEngine('hideAll');
                $(this).dialog('destroy');
            }
        });
        $('#editform').validationEngine('attach', {
            onValidationComplete: function(form, status){
                var form_data = $('#editform').serialize();
                if (status === true) {

                    $.post(page_url + 'info/edit', $.param({troll:Math.random()}) + "&" + form_data, function(eddata) {
                        if (eddata == 'ok') {
                            $(location).attr('href', page_url + 'info');
                        }
                        else {
                            alert('Greška! Molimo pokušajte ponovo');
                            console.log('response data: ' + eddata);
                        }
                    });

                }
            }
        });/* VALIDATION ENGINE */
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
