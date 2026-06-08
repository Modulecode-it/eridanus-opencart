$(document).ready(function() {
    
    var key = "user_token";
    var value = [];
	var query = String(document.location).split('?');

	if (query[1]) {
		var part = query[1].split('&');

		for (i = 0; i < part.length; i++) {
			var data = part[i].split('=');

			if (data[0] && data[1]) {
				value[data[0]] = data[1];
			}
		}
	} 
    
	$.ajax({
		url: 'index.php?route=extension/module/yandex_marketplace/versions_info/modalVersionInfo&user_token='+value[key]+'&setting_page=true',
		dataType: 'html',
		beforeSend: function() {
			$('#button-setting').button('loading');
		},
		complete: function() {
			$('#button-setting').button('reset');
		},
		success: function(html) {
            console.log(html);
            if(html){
                $('#modal-developer').remove();
                $('body').prepend('<div id="modal-developer" class="modal">' + html + '</div>');
                $('#modal-developer').modal('show');
            }
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});	
  });  
