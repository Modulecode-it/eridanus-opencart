/* @copyright Copyright &copy; Компания MEAsoft, 2014 */

jQuery(function () {
  new measoftPlugin();
});

var pvzchanged = false;

function measoftPlugin() {
  var route = getURLVar("route");

  if (route != "sale/order/edit" && route != "sale/order/info" && route != "sale/order/update") {
    return;
  }

  var disabled = route != "sale/order/edit";

  var wrapper,
    formStatus,
    formSend;
  var container = jQuery(".panel-body");
  if (container.length == 0) {
    container = jQuery(".box .content");
  } else if (container.length > 1) {
    container = jQuery(container[0]);
  }

  var urlBase = location.origin + location.pathname.replace("/index.php", "");
  var urlPlugin = urlBase + "/view/stylesheet/measoftcourier";

  // отображение контейнера Measoft на странице
  $.ajax({
    type: "get",
    url: "/admin/index.php?route=extension/shipping/measoftcourier/ajax&action=courierTemplate&user_token=" + getURLVar("user_token") + "&order_id=" + getURLVar("order_id"),
    dataType: "html",
    success: function (result, textStatus, jqXHR) {
      jQuery("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: urlPlugin + "/style.css"
      }).appendTo("head");
      container.prepend(result);
      wrapper = jQuery(".measoft-wrapper");
      formStatus = jQuery(".measoft-form-status");
      formSend = jQuery(".measoft-form-send");	  
	  if(disabled) {formSend.hide();}
      wrapper.find("input[name=user_token]").attr("value", getURLVar("user_token"));
      wrapper.find("input[name=order_id]").attr("value", getURLVar("order_id"));
      checkStatus();
    }
  });

  // проверка статуса
  function checkStatus() {
    $.ajax({
      url: formStatus.attr("action"),
      type: formStatus.attr("method"),
      data: formStatus.serialize(),
      dataType: "json",
      beforeSend: function (jqXHR, settings) {
        wrapper.addClass("process");
      },
      success: function (result, textStatus, jqXHR) {
        wrapper.removeClass("process");
        formStatus.find(".status").html(result.data.message);

        if (result.data.action == "show_form_send") {
			if(!disabled){
				formSend.show();
			}
          
          document.querySelector(".pvzcode").onchange = e => {
            pvzchanged = true;
            saveSession({pvz_id: e.target.value,skip_check:0});
          };
          document.getElementById("pvzname").onchange = e => {
            saveSession({pvz_name: e.target.value,skip_check:0});
          };
        } else {
          formSend.hide();
        }
      }
    });
  }

  // отправка заказа
  jQuery(container).on("submit", ".measoft-form-send", function () {
    if (!confirm("Отправить заказ?")) {
      return false;
    }
    $.ajax({
      url: formSend.attr("action"),
      type: formSend.attr("method"),
      data: formSend.serialize(),
      dataType: "json",
      beforeSend: function (jqXHR, settings) {
        wrapper.addClass("process");
      },
      success: function (result, textStatus, jqXHR) {
        wrapper.removeClass("process");
        if (result.data.message) {
          alert(result.data.message);
        }
        if (result.data.action == "reload_status") {
          checkStatus();
        }
      }
    });
    return false;
  });

  var lastTabEdit = false;
  var pvzIdsaved = false;
  var pvzNamesaved = false;

  var saveSession = function (e) {
    $.ajax({
      type: "post",
      url: "index.php?route=sale/order/saveSession&user_token=" + getURLVar("user_token") + "&order_id=" + getURLVar("order_id"),
      data: {
        data: JSON.stringify(e)
      },
      success: function (r) {
        if (e.pvz_id || e.skip_check) {
          pvzIdsaved = e.pvz_id;
        }
        if (e.pvz_name || e.skip_check) {
          pvzNamesaved = true;
        }

        if (lastTabEdit && ((pvzIdsaved && pvzNamesaved) ||  e.skip_check)) {
          initMethodsRecalc();
        }
        if ((pvzIdsaved && pvzNamesaved) || e.skip_check) {
          alert("Не забудьте сохранить заказ");
        }
      }
    });
  };

  var initMethodsRecalc = function () {
    $.ajax({
      url: "/index.php?route=api/shipping/methods&api_token=" + api_token + "&store_id=" + $("select[name='store_id'] option:selected").val() + "&order_id=" + $("input[name='order_id']").val() + "&pvz_id=" + pvzIdsaved,
      dataType: "json",
      success: function (json) {
        html = '<option value="">--Выберите--</option>';
        if (json["shipping_methods"]) {
          for (i in json["shipping_methods"]) {
            html += '<optgroup label="' + json["shipping_methods"][i]["title"] + '">';

            if (!json["shipping_methods"][i]["error"]) {
              for (j in json["shipping_methods"][i]["quote"]) {
                if (json["shipping_methods"][i]["quote"][j]["code"] == $("select[name='shipping_method'] option:selected").val()) {
                  html += '<option value="' + json["shipping_methods"][i]["quote"][j]["code"] + '" selected="selected">' + json["shipping_methods"][i]["quote"][j]["title"] + " - " + json["shipping_methods"][i]["quote"][j]["text"] + "</option>";
                } else {
                  html += '<option value="' + json["shipping_methods"][i]["quote"][j]["code"] + '">' + json["shipping_methods"][i]["quote"][j]["title"] + " - " + json["shipping_methods"][i]["quote"][j]["text"] + "</option>";
                }
              }
            } else {
              html += '<option value="" style="color: #F00;" disabled="disabled">' + json["shipping_method"][i]["error"] + "</option>";
            }

            html += "</optgroup>";
          }
        }

        $("select[name='shipping_method']").html(html);
        pvzIdsaved = false;
        pvzNamesaved = false;
      },
      error: function (xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  };

  function pvzMap() {
    if (!ks2008client.id) {
      alert("ID клиента не заполнено в настройках.");
      return;
    }
	
	if (!ks2008client.code) {
                alert("_MEASOFT_CLIENT_CODE_ не заполнен в файле конфигураций.");
                return;
    }
    var weight = 0.1;
    if (ks2008client.weight) {
      weight = ks2008client.weight;
    } else {
      alert("Поле вес не заполнено в настройках.");
      return;
    }

    var width = 600;
    var height = 400;

    if (ks2008client.width && ks2008client.height) {
      width = ks2008client.width;
      height = ks2008client.height;
    }

    var measoftObject = measoftMap.config({
      pvzCodeSelector: ".pvzcode",
      mapSearchZoom: 10,
      pvzNameSelector: "#pvzname",
      mapBlock: "measoftmapblock",
      client_id: ks2008client.id, // Сюда нужно указать код extra курьерской службы
	  client_code: ks2008client.code,
      mapSize: {
        // Размер карты
        width: width,
        height: height
      },
      centerCoords: [
        "55.755814", "37.617635"
      ],
      showMapButton: "1",
      showMapButtonCaption: "Выбор на карте",
      filter: {
        maxweight: weight
      },
      allowedFilterParams: [
        "acceptcash", "acceptcard", "acceptfitting"
      ],
      choicePvzCallback: function () {
        var tabTotal = document.getElementById("tab-total");
        lastTabEdit = tabTotal && tabTotal.classList.contains('active');
      }
    }).init();
  }

if (!disabled) {
  if ($("#measoftMapBlock").length > 0) {
    pvzMap();
  } else {
    setTimeout(function () {
      pvzMap();
    }, 2000);
  }
}
jQuery("body").on("click", "#ks2008_clean_pvz", function () {
  jQuery("#pvzname").val("");
  jQuery(".pvzcode").val("");
  
  saveSession({pvz_id: "",skip_check:1});
  saveSession({pvz_name: "",skip_check:1});
  
  
});

}
