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

    $('#preview').click(function() { /* PREVIEW DATA */
        $('#exportform').attr('action', page_url + 'data_export/' + device_id)
        .submit();
    });

    $('#xls').click(function() { /* DOWNLOAD XLS */
        $('#exportform').attr('action', page_url + 'data_export/' + device_id + '/xls')
        .submit();
    });

    $('#pdf').click(function() { /* DOWNLOAD XLS */
        $('#exportform').attr('action', page_url + 'data_export/' + device_id + '/pdf')
        .submit();
    });

    $('.tipTop').tipsy({gravity: 's', fade: true}); /* TOOLTIP CLASS */
    $('.tipBot').tipsy({gravity: 'n', fade: true}); /* TOOLTIP CLASS */
    $('.tipLeft').tipsy({gravity: 'e', fade: true}); /* TOOLTIP CLASS */
    $('.tipRight').tipsy({gravity: 'w', fade: true}); /* TOOLTIP CLASS */

    $('.ie #userbar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .titlebar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .block_cont').corner('bottom 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .error, .ie .warning, .ie .success, .ie .information').corner('5px'); /* IE BORDER-RADIUS FIX */

    $('.dateinput').datepicker({
        'dateFormat': 'dd.mm.yy.',
        'constrainInput': true
    });

    $('.table').wrap('<div class="table-wrap" />'); /* NORMAL TABLE */
    $('.table tr:even').addClass('even'); /* ODD/EVEN TABLEROW CLASS */
    $('.data-table').dataTable({
        'sDom': '<"data-table-top"><"clear"><"table-wrap"rt><"clear"><"data-table-bottom"><"clear">',
        'bJQueryUI': true,
        'bPaginate': false,
        'bProcessing': true,
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
