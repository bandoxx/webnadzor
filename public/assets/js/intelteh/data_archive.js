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

    $('.tipTop').tipsy({gravity: 's', fade: true}); /* TOOLTIP CLASS */
    $('.tipBot').tipsy({gravity: 'n', fade: true}); /* TOOLTIP CLASS */
    $('.tipLeft').tipsy({gravity: 'e', fade: true}); /* TOOLTIP CLASS */
    $('.tipRight').tipsy({gravity: 'w', fade: true}); /* TOOLTIP CLASS */

    $('.ie #userbar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .titlebar').corner('top 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .block_cont').corner('bottom 5px'); /* IE BORDER-RADIUS FIX */
    $('.ie .error, .ie .warning, .ie .success, .ie .information').corner('5px'); /* IE BORDER-RADIUS FIX */

    $('.table').wrap('<div class="table-wrap" />'); /* NORMAL TABLE */
    $('.table tr:even').addClass('even'); /* ODD/EVEN TABLEROW CLASS */
    var otable = $('#otable').dataTable({
        'sDom': '<"data-table-top"><"clear"><"table-wrap"rt><"clear"><"data-table-bottom"><"clear">',
        'bJQueryUI': true,
        'bPaginate': false,
        'bProcessing': true,
        'sAjaxSource': page_url + 'data_archive/' + device_id + '/daily_tabledata.json',
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

    $('#daily').click(function() { /* DAILY ARCHIVE */
		if (!$(this).parent().hasClass('activepage')) {
			otable.fnReloadAjax(page_url + 'data_archive/' + device_id + '/daily_tabledata.json');
			$(this).parent().attr('class','activepage');
			$('#monthly').parent().attr('class','');
			$('#archive_typetitle').fadeOut(300, function() {
				$(this).html('Dnevna').fadeIn(300);
			});
			$('#archive_typeth').fadeOut(300, function() {
				$(this).html('Datum').fadeIn(300);
			});
		}
		return false;
    });

    $('#monthly').click(function() { /* DAILY ARCHIVE */
		if (!$(this).parent().hasClass('activepage')) {
			otable.fnReloadAjax(page_url + 'data_archive/' + device_id + '/monthly_tabledata.json');
			$(this).parent().attr('class','activepage');
			$('#daily').parent().attr('class','');
			$('#archive_typetitle').fadeOut(300, function() {
				$(this).html('Mjesečna').fadeIn(300);
			});
			$('#archive_typeth').fadeOut(300, function() {
				$(this).html('Mjesec').fadeIn(300);
			});
		}
		return false;
    });

});
