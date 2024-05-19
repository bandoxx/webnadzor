function delete_confirm(icon_id) {
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
      'Obriši ikonu': function() {
        $.ajax({
          url: '/api/icons/' + icon_id,
          type: 'DELETE',
          success: function(result) {
            $(location).attr('href', "{{ path('app_icon_index') }}")
          },
          error: function(result) {
            alert('Greška! Molimo pokušajte ponovo');
          }
        });
      },
      'Odustani': function() {
        $(this).dialog('destroy');
      }
    }
  });
}

function edit_icon(icon_id) {
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
        let data = $('#editform').serializeArray();

        $.ajax({
          url: '/api/icons/' + icon_id,
          type: 'PATCH',
          data: {
            title: data[0].value
          },
          success: function(result) {
            $(location).attr('href', "{{ path('app_icon_index') }}")
          },
          error: function(result) {
            alert('Greška! Molimo pokušajte ponovo');
          }
        });
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
    onValidationComplete: function(form, status) {
      var form_data = $('#editform').serialize();
      if (status === true) {

        $.post({}, $.param({icon_id:icon_id,troll:Math.random()}) + "&" + form_data, function(eddata, s, r) {
          if (r.getResponseHeader('Requires-Auth') == 1) {
            $(location).attr('href', page_url);
            return false;
          }
          if (eddata == 'ok') {
            $(location).attr('href', page_url + 'icons_edit');
          }
          else {
            alert('Greška! Molimo pokušajte ponovo');
            console.log('response data: ' + eddata);
          }
        });
      }
    }
  });/* VALIDATION ENGINE */
}

$(document).ready(function() {

  $('.toggle').click(function() { /* BLOCK TOGGLE */
    $(this).toggleClass('show');
    $(this).parent().parent().children('.block_cont').slideToggle(300);
    $(this).parent().toggleClass('show');
    return false;
  });

  $('.delete').click(function() { /* DELETE ICON */
    delete_confirm($(this).parent().attr('id'));
    return false;
  });

  $('.edit').click(function() { /* EDIT ICON */
    edit_icon($(this).parent().attr('id'));
    return false;
  });

  $('#addform').validationEngine('attach');/* VALIDATION ENGINE */
  $('#iconadd').click(function() { /* ADD ICON */
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
        'Dodaj novu ikonu': function() {
          $('#addform').submit();
          return false;
        },
        'Odustani': function() {
          $(this).dialog('close');
        }
      },
      close: function() {
        $('#addform input').val('');
        $('#addform').validationEngine('hideAll');
        $(this).dialog('destroy');
      }
    });
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

  $('#table').wrap('<div class="table-wrap" />'); /* NORMAL TABLE */
  $('#table tr:even').addClass('even'); /* ODD/EVEN TABLEROW CLASS */
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