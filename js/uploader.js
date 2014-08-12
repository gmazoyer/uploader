$('#files').fileinput({
  maxFileSize: 2097152,
  maxFilesNum: 10,
  previewFileType: 'any',
  mainTemplate: '<div class="input-group {class}">{caption}<div class="input-group-btn">{remove}{upload}{browse}</div></div>{preview}'
});

$('form').ajaxForm({
  beforeSubmit: function() {
    $('#expiration').attr('disabled', '');
    $('#files').fileinput('disable');
    $('.loading').removeClass('hide');
  },
  uploadProgress: function(event, position, total, percent) {
    $('#progress').css('width', percent+'%').html(percent+'%');
  },
  success: function(response) {
    $('#expiration').removeAttr('disabled');
    $('#files').fileinput('enable');
    $('.loading').addClass('hide');

    if (response.length == 6) {
      var url = document.URL;
      var last = url.substr(-1);

      if (last != '/') {
        url += '/' + response;
      } else {
        url += response;
      }

      $(location).attr('href', url);
    }
  }
});
