jQuery(document).ready(function() {

    var refreshIntervalId = setInterval(function() {
        if (jQuery('input[value="measoftcourier.standard"]').length > 0) {
            var style = '';
            if (!jQuery('input[value="measoftcourier.standard"]').is(':checked')) {
                style = "style='display:none;'";
            }

            var html = "<div class='form-group meamap' " + style + "><div class='measoftWrapper'><div id='measoftmapblock'></div><input type='hidden' name='pvz_id' class='pvzcode'><input type='text' name='pvz_name' id='pvzname' readonly placeholder='Нажмите кнопку справа для выбора ПВЗ'><input type='hidden' readonly name='pvz_city' id='pvz_city'><button type=\"button\" id='ks2008_clean_pvz' class=\"btn btn-default btn-xs\" title=\"Очистить ПВЗ\"><img src='../../admin/view/image/measoftcourier/cross.png'></button></div></div>";

            jQuery('input[value="measoftcourier.standard"]').parent().parent().after(html);


            var weight = 0.1;

            if (ks2008client.weight) {
                weight = ks2008client.weight;
            }

            var width = 600;
            var height = 400;

            if (ks2008client.width && ks2008client.height) {
                width = ks2008client.width;
                height = ks2008client.height;
            }
            if (!ks2008client.id) {
                alert("_MEASOFT_CLIENT_ID_ не заполнен в файле конфигураций.");
                return;
            }
			
			if (!ks2008client.code) {
                alert("_MEASOFT_CLIENT_CODE_ не заполнен в файле конфигураций.");
                return;
            }

            // фильтр города
            var shipping_city_value = '';

            if (document.querySelector('.hidden_city_info')) {
                shipping_city_value = document.querySelector('.hidden_city_info').innerHTML;
                if (document.querySelector('#pvz_city')) {
                    document.querySelector('#pvz_city').value = shipping_city_value.trim();
                }
            }
            //  фильтр города

            if (shipping_city_value != '') {
                var measoftObject = measoftMap.config({
                    'pvzCodeSelector': '.pvzcode',
                    'mapSearchZoom': 10,
                    'pvzNameSelector': '#pvzname',
                    'mapBlock': 'measoftmapblock',
                    'townBlock': 'pvz_city',
                    'client_id': ks2008client.id, // Сюда нужно указать код extra курьерской службы
					'client_code': ks2008client.code,
                    'mapSize': { // Размер карты
                        'width': width,
                        'height': height
                    },
                    'centerCoords': ['55.755814', '37.617635'],
                    'showMapButton': '1',
                    'showMapButtonCaption': 'Выбор на карте',
                    'filter': {
                        'maxweight': weight
                    },
                    'allowedFilterParams': ['acceptcash', 'acceptcard', 'acceptfitting'],
                });

            } else {
                var measoftObject = measoftMap.config({
                    'pvzCodeSelector': '.pvzcode',
                    'mapSearchZoom': 10,
                    'pvzNameSelector': '#pvzname',
                    'mapBlock': 'measoftmapblock',
                    'client_id': ks2008client.id, // Сюда нужно указать код extra курьерской службы
					'client_code': ks2008client.code,
                    'mapSize': { // Размер карты
                        'width': width,
                        'height': height
                    },
                    'centerCoords': ['55.755814', '37.617635'],
                    'showMapButton': '1',
                    'showMapButtonCaption': 'Выбор на карте',
                    'filter': {
                        'maxweight': weight
                    },
                    'allowedFilterParams': ['acceptcash', 'acceptcard', 'acceptfitting'],
                });
            }

            measoftObject.init();
            clearInterval(refreshIntervalId);
        }
    }, 1000);

    if (document.querySelector('#collapse-shipping-method')) {
        setInterval(function() {
            var shippingMethodWrapper = document.querySelector('#collapse-shipping-method');

            if (document.querySelector('.measoftWrapper')) {
                var measoftWrapper = document.querySelector('.measoftWrapper');
                if (document.querySelector('#collapse-shipping-method')) {
                    measoftWrapper.style.width = (shippingMethodWrapper.offsetWidth - 31) + 'px';
                }
            }
            if (document.querySelector('#button-shipping-method') && document.querySelector('#pvzname')) {
                if (document.querySelector('#pvzname').value.trim() == '') {
                    document.querySelector('#pvzname').style.cssText = 'border: 1px solid #ff4a4a';
                    document.querySelector('#button-shipping-method').disabled = true;
                } else {
                    document.querySelector('#pvzname').style.cssText = 'border: 1px solid #666';
                    document.querySelector('#button-shipping-method').disabled = false;
                }
            }
        }, 1000);
    }
    jQuery('body').on('change', 'input[name="shipping_method"]', function() {
        if (jQuery(this).val() == 'measoftcourier.standard') {
            jQuery('.meamap').css('display', 'block');
        } else {
            jQuery('.meamap').css('display', 'none');
        }
    });

    jQuery('body').on('click', '#ks2008_clean_pvz', function() {
        jQuery('#pvzname').val('');
        jQuery('.pvzcode').val('');
    });

});
