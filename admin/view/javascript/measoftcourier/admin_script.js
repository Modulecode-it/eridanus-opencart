$(document).ready(function () {
$( "#measoftcourier_auto_client_id" ).click(function() {
   $.ajax({
       url: "/admin/index.php?route=extension/shipping/measoftcourier/ajax&action=autoclientcode&user_token=" + getURLVar("user_token"),   
      type: 'POST',
      data: $("#form-measoftcourier").serialize(),
      dataType: "json",
      beforeSend: function (jqXHR, settings) {
        $(".measoft-wrapper").addClass("process");
      },
      success: function (result, textStatus, jqXHR) {
		
        $(".measoft-wrapper").removeClass("process");
		if (result.data.action == "set_client_code") {
			 $("#input-client_code").val(result.data.message);
		}else{
			alert(result.data.message);	 
		}
        
      }
    });
});
});