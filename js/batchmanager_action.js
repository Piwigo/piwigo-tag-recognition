// Work in progress : Batch manager action in async

jQuery('form').on('submit', function(e) {
  if (typeof(elements) != "undefined") {
    return true;
  }

  if (jQuery('[name="selectAction"]').val() == 'tag_recognition') {
    e.preventDefault();

    $('.tr-icon-robot').addClass('loading')
    $('#applyAction').attr('disabled', 'true');

    elements = Array();

    if (jQuery('input[name=setSelected]').is(':checked')) {
      elements = all_elements;
    }
    else {
      jQuery('input[name="selection[]"]').filter(':checked').each(function() {
        elements.push(jQuery(this).val());
      });
    }

    let nbDone = 0;

    const updateText = () => {
      $('.tr-batch-manager-info p').html(str_tr_loading.replace('%d1', nbDone).replace('%d2', elements.length))
    }

    updateText();

    
    const promises = [];
    
    elements.forEach(id => {
      console.log({
        method: 'pwg.tagRecognition.generateAndAssignTags',
        pwg_token: $("input[name=pwg_token]").val(),
        imageId: id,
        language: $('#tr-limit').val(),
        limit : parseInt($('#tr-language').val()),
      });
      promises.push(new Promise((res,rej) => {
        jQuery.ajax({
          url: "ws.php?format=json",
          type:"POST",
          dataType: 'json',
          data: {
            method: 'pwg.tagRecognition.generateAndAssignTags',
            pwg_token: $("input[name=pwg_token]").val(),
            imageId: id,
            language: $('#tr-language').val(),
            limit : parseInt($('#tr-limit').val()),
          },
          success: function(data) {
            nbDone++;
            updateText();
            res(data);
          },
          error: function(data) {
            rej(data);
          }
        });
      }))
    });

    Promise.all(promises)
      .then(() => {
        $('.tr-icon-robot').removeClass('loading');
        $('.tr-icon-robot').addClass('happy');
        $('.tr-batch-manager-info p').html(str_tr_sucess);
        $('#applyAction').removeAttr('disabled');
      })
      .catch((e) => {
        $('.tr-icon-robot').removeClass('loading');
        $('.tr-icon-robot').addClass('dead');
        $('.tr-batch-manager-info p').html(str_tr_error);
        $('#applyAction').removeAttr('disabled');
      });
  }
});