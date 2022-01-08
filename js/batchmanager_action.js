// Work in progress : Batch ma,ager action in async

jQuery('#applyAction').click(function(e) {
    if (typeof(elements) != "undefined") {
      return true;
    }
  
    if (jQuery('[name="selectAction"]').val() == 'metadata') {
      e.stopPropagation();
      jQuery('.bulkAction').hide();
      jQuery('#regenerationText').html(lang.syncProgressMessage);
      elements = Array();
  
      if (jQuery('input[name=setSelected]').is(':checked')) {
        elements = all_elements;
      }
      else {
        jQuery('input[name="selection[]"]').filter(':checked').each(function() {
          elements.push(jQuery(this).val());
        });
      }
  
      progressBar_max = elements.length;
      var todo = 0;
      var syncBlockSize = Math.min(
        Number((elements.length/2).toFixed()),
        1000
      );
      var image_ids = Array();
  
      jQuery('#applyActionBlock').hide();
      jQuery('.permitActionListButton').hide();
      jQuery('#confirmDel').hide();
      jQuery('#regenerationMsg').show();
      progress_bar_start();
      for (i=0;i<elements.length;i++) {
        image_ids.push(elements[i]);
        if (i % syncBlockSize != syncBlockSize - 1 && i != elements.length - 1) {
          continue;
        }
  
        (function(ids) {
          var thisBatchSize = ids.length;
          jQuery.ajax({
            url: "ws.php?format=json&method=pwg.images.syncMetadata",
            type:"POST",
            dataType: "json",
            data: {
              pwg_token: jQuery("input[name=pwg_token]").val(),
              image_id: ids
            },
            success: function(data) {
              todo += thisBatchSize;
              var isOk = data.stat && "ok" == data.stat;
              if (isOk && data.result.nb_synchronized != thisBatchSize)
              /*TODO: user feedback only data.nb_synchronized images out of thisBatchSize were sync*/;
              /*TODO: user feedback if isError*/
              jQuery('#regenerationStatus .badge-number').html(todo.toString() + "/" + progressBar_max.toString());
              progress_bar(todo, progressBar_max, false);
            },
            error: function(data) {
              todo += thisBatchSize;
              /*TODO: user feedback*/
              jQuery('#regenerationStatus .badge-number').html(todo.toString() + "/" + progressBar_max.toString());
              progress_bar(todo, progressBar_max, false);
            }
          });
        } )(image_ids);
        image_ids = Array();
      }
    }
});