var currentLocation = false;

if ("geolocation" in navigator) {
  navigator.geolocation.getCurrentPosition(function (position) {
    currentLocation = [position.coords.latitude, position.coords.longitude];
  });
} else {
  console.log("no geolocation");
}

var mkModal = function (size) {
  var modal = document.getElementById("measoftModal");
  if (!modal) {
    var width = 600;
    var height = 400;

    if (size.width && size.height) {
      width = size.width;
      height = size.height;
    }
  
    var modal = document.createElement("div");    
    var mapBlock = document.createElement("div");
    mapBlock.id = "measoftmapblock";
    modal.appendChild(mapBlock);
    return modal;
  }
  return modal;
};



var quotePvz = function (e) {
  var city_f = document.getElementById("input-shipping-city") || document.getElementById("shipping_address_city") || document.querySelector("input[name='city']");
  var zip_f = document.getElementById("input-shipping-postcode") || document.getElementById("shipping_address_postcode") || document.querySelector("input[name='postcode']");
    let pvz_acceptcash = $("#pvz_acceptcash").val();
    let pvz_acceptcard = $("#pvz_acceptcard").val();
    $("input[name='pvz_acceptcash']").val(pvz_acceptcash);
    $("input[name='pvz_acceptcard']").val(pvz_acceptcard);
  $.ajax({
    type: "post",
    url: "/index.php?route=extension/shipping/measoft/quote",
    data: {
      pvz_acceptcash: pvz_acceptcash,
      pvz_acceptcard: pvz_acceptcard,
      pvzid: e,
      city: city_f.value || "",
      zipcode: zip_f.value || "",
      pvzname: document.getElementById("pvzname").value
    },
    dataType: "json",
    success: function (result) {
      var mea_d = document.getElementById("mea_description");
      var cost = result.cost
        ? result.cost
        : result.empty;
      mea_d.innerHTML = document.getElementById("pvzname").value + " - " + cost;
      jQuery("#measoftModal").hide();
      var checkbox = document.getElementById("measoftcouriershipping.standard");
      if (checkbox && checkbox.dataset.onchange === "reloadAll") {
        checkbox.dispatchEvent(new Event("change"));
      }
	  
	  if (typeof uniCheckoutUpdate !== "undefined") { 
		uniCheckoutUpdate();
	  }

      var def_btn = document.querySelector("#button-shipping-method");
      if (def_btn) 
        def_btn.removeAttribute("disabled");
      }
    });
};


function mapint() {

  cityto_value = '';
  if ($("#input-shipping-city").length) {
    cityto_value = "input-shipping-city";
  } else if ($("#shipping_address_city").length) {
    cityto_value = "shipping_address_city";
  } else if ($("input[name='city']").length) {
    cityto_value = "input[name='city']";
  }

  if (!cityto_value) {    
    window.setTimeout(mapint, 100);
    return;
  }
	
  $.ajax({
    type: "get",
    url: "/index.php?route=extension/shipping/measoft/getSettings",
    dataType: "json",
    success: function (ks2008client) {
      var weight = ks2008client.weight || 0.1;
      var width = ks2008client.width || 600;
      var height = ks2008client.height || 400;

      document.body.appendChild(mkModal({height: height, width: width}));

      if (!ks2008client.id) {
        alert("Индентификационный номер клиента не заполнен в настройках.");
        return;
      }

		shipping_city_value='';
		if($("#input-shipping-city").length){
			shipping_city_value="input-shipping-city";
		}else if($("#shipping_address_city").length) {
			shipping_city_value="shipping_address_city";
		} else if ($("input[name='city']").length) {
			shipping_city_value = "input[name='city']";
		}
		
		
		
		if (shipping_city_value != "") {
		
		 
        var measoftObject = measoftMap.config({
          pvzCodeSelector: ".pvzcode",
          mapSearchZoom: 10,
          pvzNameSelector: "#pvzname",
          mapBlock: "measoftmapblock",
          townBlock: shipping_city_value,
          client_id: ks2008client.id,
		  client_code: ks2008client.code,
          mapSize: {
            width: width,
            height: height
          },
          centerCoords: currentLocation || [
            "55.73", "37.60"
          ],
          showMapButton: "1",
		  windowFixedPosition: "1",
          showMapButtonCaption: "Выбор на карте",
          filter: {
            maxweight: weight
          },
          allowedFilterParams: [
            "acceptcash", "acceptcard", "acceptfitting"
          ],
          choicePvzCallback: quotePvz
        });
      } else {
        var measoftObject = measoftMap.config({
          pvzCodeSelector: ".pvzcode",
          mapSearchZoom: 10,
          pvzNameSelector: "#pvzname",
          mapBlock: "measoftmapblock",
          client_id: ks2008client.id,
		  client_code: ks2008client.code,
          mapSize: {
            width: width,
            height: height
          },
          centerCoords: currentLocation || [
            "55.73", "37.60"
          ],
          showMapButton: "1",
		  windowFixedPosition: "1",
          showMapButtonCaption: "Выбор на карте",
          filter: {
            maxweight: weight
          },
          allowedFilterParams: [
            "acceptcash", "acceptcard", "acceptfitting"
          ],
          choicePvzCallback: quotePvz
        });
      }

      measoftObject.init();
    }
  });
}

var showModalMea = function () {
	
	let selectedTarif = $('input[type=radio][name=shipping_method]:checked').val();
	let inputVal = 'measoftcouriershipping.standard';
    if(selectedTarif != inputVal) {       
        $("input[value='"+inputVal+"']").click();
        //return 0;
    }

	measoftMap.open('start');

  if (measoftMap.map && currentLocation) {
    measoftMap.map.setView(currentLocation, 10, {
      animate: true,
      pan: {
        duration: 0.5
      }
    });
  }
};

$(document).ready(function () {
  mapint();

  if (document.getElementById("accordion")) {
    let meamapToDelete = document.querySelector(".meamap");
    if (meamapToDelete) {
      meamapToDelete.parentNode.removeChild(meamapToDelete);
    }
  }


  setInterval(function () {
    let buttonSelector = '';
    if ($('#simplecheckout_button_confirm').length)
      buttonSelector = '.simplecheckout-button-block.buttons';
    else if ($('#button-confirm').length)
      buttonSelector = '#button-confirm';
	else if ($('#confirm_checkout').length)
      buttonSelector = '#confirm_checkout';


    $(".measoft_nopvz_choosen").remove();
    $(".measoft_wrongpvz_choosen").remove();
    $(".measoft_emptypvz_choosen").remove();
    $(buttonSelector).show();

	
    if ($(buttonSelector).length && $("input[name='shipping_method']:checked").length && $("input[name='shipping_method']:checked").val() == 'measoftcouriershipping.standard') {


      if ($('#pvzname').length && $('#pvzname').val() == '') {
        $(buttonSelector).hide().after('<p class="measoft_nopvz_choosen measoft_wrongpvz">Для оформления заказа нужно выбрать ПВЗ</p>');
      }

      if ($('#measoft_empty_pvz_price').length) {
        $(buttonSelector).hide().after('<p class="measoft_emptypvz_choosen measoft_wrongpvz">Доставка в выбранный ПВЗ невозможна. Выберите другой ПВЗ</p>');
      } else {

        if ($('input[name="payment_method"]:checked').length && $('input[name="pvz_acceptcash"]').val() !== '' && $('input[name="pvz_acceptcard"]').val() !== '') {

          let payment_selected = $('input[name="payment_method"]:checked').val();
          let pvz_acceptcash = parseInt($('input[name="pvz_acceptcash"]').val());
          let pvz_acceptcard = parseInt($('input[name="pvz_acceptcard"]').val());

          let go_order = false;
          if (
              (pvz_acceptcash == 1 && $("input[name='shipping_measoftcourier_payment_cash[]'][value='" + payment_selected + "']").length)
              ||
              (pvz_acceptcard == 1 && $("input[name='shipping_measoftcourier_payment_card[]'][value='" + payment_selected + "']").length)
              ||
              $("input[name='shipping_measoftcourier_payment_none[]'][value='" + payment_selected + "']").length
          ) {
            go_order = true;
          }
          if (!go_order) {
            $(buttonSelector).hide().after('<p class="measoft_wrongpvz_choosen measoft_wrongpvz">Данный ПВЗ не подходит для выбранного способа оплаты</p>');
          }

        }
      }


    }


  }, 100);


  
});

