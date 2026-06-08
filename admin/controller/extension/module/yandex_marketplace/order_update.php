<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOrderUpdate extends Controller {

    public function updateYandexMarketStatus() {

        $this->load->model('extension/module/yandex_beru');
        $market_order_id = $this->model_extension_module_yandex_beru->gerMarketOrderId($this->request->get["order_id"]);

        $this->api = new yandex_beru();
                
        $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
        $component = $this->api->loadComponent('orderInfo');
        $component->setOrderId((int)$market_order_id);
        $out = $this->api->sendData($component);
        
        switch ($out['order']['delivery']['dispatchType']) {
            case "BUYER":
                $delivery_type = "Доставка покупателю\r\n";
                break;
            case "MARKET_PARTNER_OUTLET":
                $delivery_type = "Доставка в ПВЗ партнёра Маркета\r\n";
                break;
            case "MARKET_BRANDED_OUTLET":
                $delivery_type = "Доставка в брендированный ПВЗ Маркета\r\n";
                break;
            case "SHOP_OUTLET":
                $delivery_type = "Доставка в ПВЗ Магазина\r\n";
                break;
            case "DROPOFF":
                $delivery_type = "Доставка в дропофф\r\n";
                break;
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `shipping_method` = '" . $delivery_type . "'");
                
        $comment = $this->db->query("SELECT `comment` FROM `" . DB_PREFIX . "order` WHERE `order_id`= '" . $this->request->get["order_id"] . "'"); 
        $comment = $comment->row;
      
        $pattern = '/Способ доставки:(.*)[^\n]/i';
        $replacement = "Способ доставки: $delivery_type";
        $text = preg_replace($pattern, $replacement, $comment['comment']);

        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `comment` = '" . $text . "' WHERE `order_id`= '" . $this->request->get["order_id"] . "'"); 
        
        $this->info();
                
    }


    public function getStoragePeriodYM() {

        $this->load->model('extension/module/yandex_beru');
        $outlet_id = $this->model_extension_module_yandex_beru->getOutletIdByOrderId($this->request->get['order_id']);
        $market_order_id = $this->model_extension_module_yandex_beru->gerMarketOrderId($this->request->get["order_id"]);
        $order_type = $this->model_extension_module_yandex_beru->getMarketOrderType($this->request->get["order_id"]);
    
        if($order_type == 'DBS'){
            $this->api = new yandex_beru();
                        
            $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            
            $component = $this->api->loadComponent('orderInfo');
            $component->setOrderId((int)$market_order_id);
            $order = $this->api->sendData($component);

            if(isset($order['order'])){
                $settings_dbs = $this->model_extension_module_yandex_beru->getShippingsDBS();
                $shippings_info = json_decode($settings_dbs['value'], 1);
                $comment = $this->creatureСommentDBS($order['order'], $this->request->get["order_id"]);
                $order_info = $this->formatOrderInfo($order['order'], $comment, $shippings_info);
                $order_id = $this->model_extension_module_yandex_beru->getShopOrderId($order['order']['id']);

                $this->model_extension_module_yandex_beru->editOrder($this->request->get["order_id"], $order_info);
            }
        }elseif($order_type == 'FBS'){
            $this->api = new yandex_beru();
                        
            $this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
            
            $component = $this->api->loadComponent('orderInfo');
            $component->setOrderId((int)$market_order_id);
            $order = $this->api->sendData($component);

            if(isset($order['order'])){
                $comment = $this->creatureСommentFBS($order['order'], $this->request->get["order_id"]);
                $order_info = $this->formatOrderInfoFBS($order['order'], $comment);
                $order_id = $this->model_extension_module_yandex_beru->getShopOrderId($order['order']['id']);

                $this->model_extension_module_yandex_beru->editOrder($this->request->get["order_id"], $order_info);
            }
        }
        
    }

    private function creatureСommentDBS($info, $order_id){
		$this->load->language('extension/module/yandex_market');
		$this->load->model('extension/module/yandex_beru');

        $log = new Log ('cart_dbs.log');

        $comment = "Идентификатор заказа в системе Yandex: " . $info['id'] . "\r\n";

        if($info['fake'] === false){
            $comment .= "Тип заказа: настоящий заказ \r\n";
        } else {
            $comment .= "Тип заказа: отладочный заказ Маркета \r\n";
        }

        if($info['paymentType'] == "PREPAID"){
            $comment .= "Тип оплаты заказа: оплата при оформлении заказа \r\n";
        } else {
            $comment .= "Тип оплаты заказа: оплата при получении заказа \r\n";
        }

        if($info['paymentMethod'] == "YANDEX"){
            $comment .= "Способ оплаты заказа: банковской картой \r\n";
            if($info['taxSystem'] == 'ECHN'){
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: единый сельскохозяйственный налог (ЕСХН) \r\n";
            } elseif($info['taxSystem'] == "ENVD") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: единый налог на вмененный доход (ЕНВД) \r\n";
            } elseif($info['taxSystem'] == "OSN") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: общая система налогообложения (ОСН) \r\n";
            } elseif($info['taxSystem'] == "PSN") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: патентная система налогообложения (ПСН) \r\n";
            } elseif($info['taxSystem'] == "USN") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: упрощенная система налогообложения (УСН) \r\n";
            } elseif($info['taxSystem'] == "USN_MINUS_COST") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: упрощенная система налогообложения, доходы, уменьшенные на величину расходов (УСН «Доходы минус расходы») \r\n";
            } else {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: не определено \r\n";
            }

        } elseif($info['paymentMethod'] == "APPLE_PAY") {
            $comment .= "Способ оплаты заказа: Apple Pay \r\n";
        } elseif($info['paymentMethod'] == "GOOGLE_PAY") {
            $comment .= "Способ оплаты заказа: Google Pay \r\n";
        } elseif($info['paymentMethod'] == "CARD_ON_DELIVERY") {
            $comment .= "Способ оплаты заказа: банковской картой \r\n";
        } elseif($info['paymentMethod'] == "CASH_ON_DELIVERY") {	
            $comment .= "Способ оплаты заказа: банковской картой \r\n";
        } else {
            $comment .= "Способ оплаты заказа: не определено \r\n";
        }
		
		if(isset($info['delivery']['liftType']) && isset($info['delivery']['liftPrice'])){
			$comment .= "Способ подъема на этаж: " . $this->language->get('lift_type_DBS_' . $info['delivery']['liftType']) . " \r\n";
			$comment .= "Итоговая стоимость подъема на этаж: " . $info['delivery']['liftPrice'] . " руб. \r\n";
			
		}

        switch ($info['delivery']['dispatchType']) {
        case "BUYER":
            $comment .= "Способ доставки: Доставка покупателю\r\n";
            break;
        case "MARKET_PARTNER_OUTLET":
            $comment .= "Способ доставки: Доставка в ПВЗ партнёра Маркета\r\n";
            break;
        case "MARKET_BRANDED_OUTLET":
            $comment .= "Способ доставки: Доставка в брендированный ПВЗ Маркета\r\n";
            break;
        case "SHOP_OUTLET":
            $comment .= "Способ доставки: Доставка в ПВЗ Магазина\r\n";
            break;
        case "DROPOFF":
            $comment .= "Способ доставки: Доставка в дропофф\r\n";
            break;
        }

        $delivery_courier = $this->model_extension_module_yandex_beru->getDeliveryCourier($order_id);
		if(!empty($delivery_courier)){
            $comment .= "Доставка курьером: \r\n";
            $comment .= "   Ф.И.О курьера: ". $delivery_courier['fullName'] ."\r\n";
            $comment .= "   Телефон курьера: ". $delivery_courier['phone'] ."\r\n";
            $comment .= "   Добавочный номер: ". $delivery_courier['phoneExtension'] ."\r\n";
            $comment .= "   Номер машины: ". $delivery_courier['vehicleNumber'] ."\r\n";
            $comment .= "   Описание машины: ". $delivery_courier['vehicleDescription'] ."\r\n";
		}
        
        $vehicle_number = $this->model_extension_module_yandex_beru->getVehicleNumber($order_id);
		if(!empty($vehicle_number)){
            $comment  .= "Номер машины: " . $order_info['vehicleNumber'] . "\r\n";
		}
        
		$eac = $this->model_extension_module_yandex_beru->getElectronicAcceptanceCertificate($order_id);
		if(!empty($eac)){
            $comment  .= "Код подтверждения (EAC Code) для курьера: " . $eac . "\r\n";
		}
		
        $settings_dbs = $this->model_extension_module_yandex_beru->getShippingsDBS();

        $shippings_info = json_decode($settings_dbs['value'], 1);
		
		if(isset($info['notes'])){
        	$comment .= "Комментарий: " . $this->db->escape($info['notes']) . "\r\n";
		}


        return $comment;

    }
    
    private function creatureСommentFBS($info, $order_id){
		$this->load->language('extension/module/yandex_market');
		$this->load->model('extension/module/yandex_beru');

        $log = new Log ('cart_dbs.log');

        $comment = "Идентификатор заказа в системе Yandex: " . $info['id'] . "\r\n";

        if($info['fake'] === false){
            $comment .= "Тип заказа: настоящий заказ \r\n";
        } else {
            $comment .= "Тип заказа: отладочный заказ Маркета \r\n";
        }

        if($info['paymentType'] == "PREPAID"){
            $comment .= "Тип оплаты заказа: оплата при оформлении заказа \r\n";
        } else {
            $comment .= "Тип оплаты заказа: оплата при получении заказа \r\n";
        }

        if($info['paymentMethod'] == "YANDEX"){
            $comment .= "Способ оплаты заказа: банковской картой \r\n";
            if($info['taxSystem'] == 'ECHN'){
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: единый сельскохозяйственный налог (ЕСХН) \r\n";
            } elseif($info['taxSystem'] == "ENVD") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: единый налог на вмененный доход (ЕНВД) \r\n";
            } elseif($info['taxSystem'] == "OSN") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: общая система налогообложения (ОСН) \r\n";
            } elseif($info['taxSystem'] == "PSN") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: патентная система налогообложения (ПСН) \r\n";
            } elseif($info['taxSystem'] == "USN") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: упрощенная система налогообложения (УСН) \r\n";
            } elseif($info['taxSystem'] == "USN_MINUS_COST") {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: упрощенная система налогообложения, доходы, уменьшенные на величину расходов (УСН «Доходы минус расходы») \r\n";
            } else {
                $comment .= "Система налогообложения (СНО) магазина на момент оформления заказа: не определено \r\n";
            }

        } elseif($info['paymentMethod'] == "APPLE_PAY") {
            $comment .= "Способ оплаты заказа: Apple Pay \r\n";
        } elseif($info['paymentMethod'] == "GOOGLE_PAY") {
            $comment .= "Способ оплаты заказа: Google Pay \r\n";
        } elseif($info['paymentMethod'] == "CARD_ON_DELIVERY") {
            $comment .= "Способ оплаты заказа: банковской картой \r\n";
        } elseif($info['paymentMethod'] == "CASH_ON_DELIVERY") {	
            $comment .= "Способ оплаты заказа: банковской картой \r\n";
        } else {
            $comment .= "Способ оплаты заказа: не определено \r\n";
        }
		
		if(isset($info['delivery']['liftType']) && isset($info['delivery']['liftPrice'])){
			$comment .= "Способ подъема на этаж: " . $this->language->get('lift_type_DBS_' . $info['delivery']['liftType']) . " \r\n";
			$comment .= "Итоговая стоимость подъема на этаж: " . $info['delivery']['liftPrice'] . " руб. \r\n";
			
		}

        $delivery_courier = $this->model_extension_module_yandex_beru->getDeliveryCourier($order_id);
        $delivery_courier = json_decode($delivery_courier, 1);

		if(!empty($delivery_courier['delivery_courier'])){
            $comment .= "Доставка курьером: \r\n";
            $comment .= "Ф.И.О курьера: ". $delivery_courier['delivery_courier']['fullName'] ."\r\n";
            $comment .= "Телефон курьера: ". $delivery_courier['delivery_courier']['phone'] ."\r\n";
            $comment .= "Добавочный номер: ". $delivery_courier['delivery_courier']['phoneExtension'] ."\r\n";
            $comment .= isset($delivery_courier['delivery_courier']['vehicleNumber']) ? "Номер машины: ". $delivery_courier['delivery_courier']['vehicleNumber'] ."\r\n" : '';
            $comment .= isset($delivery_courier['delivery_courier']['vehicleDescription']) ? "Описание машины: ". $delivery_courier['delivery_courier']['vehicleDescription'] ."\r\n" : '';
		}
        
        if(isset($delivery_courier['substatus'])){
            switch ($delivery_courier['substatus']) {
                case "СOURIER_SEARCH":
                    $comment .= "Статус курьера: поиск курьера.\r\n";
                    break;
                case "COURIER_FOUND":
                    $comment .= "Статус курьера: курьер назначен.\r\n";
                    break;
                case "COURIER_IN_TRANSIT_TO_SENDER":
                    $comment .= "Статус курьера: курьер едет за заказом.\r\n";
                    break;
                case "COURIER_ARRIVED_TO_SENDER":
                    $comment .= "Статус курьера: курьер приехал за заказом.\r\n";
                    break;
                case "COURIER_NOT_FOUND":
                    $comment .= "Статус курьера: курьер не найден.\r\n";
                    break;
            }
        }
        
        $vehicle_number = $this->model_extension_module_yandex_beru->getVehicleNumber($order_id);
		if(!empty($vehicle_number)){
            $comment  .= "Номер машины: " . $order_info['vehicleNumber'] . "\r\n";
		}
        
		$eac = $this->model_extension_module_yandex_beru->getElectronicAcceptanceCertificate($order_id);
		if(!empty($eac)){
            $comment  .= "Код подтверждения (EAC Code) для курьера: " . $eac . "\r\n";
		}
		
		if(isset($info['notes'])){
        	$comment .= "Комментарий: " . $this->db->escape($info['notes']) . "\r\n";
		}


        return $comment;

    }
    
    private function formatOrderInfo($info, $comment,$shippings_info){
        $this->load->language('extension/module/yandex_market');
        $this->load->model('extension/module/yandex_beru');

        $total_price        = 0;
        $total_subsidy      = 0;
        $total_buyer_price  = 0;
       
        foreach ($info['items'] as $product){

            $products[] = array(
                'product_id'    => $product['offerId'],
                'quantity'      => $product['count'],
                'price'         => $product['price']
            );

            $total_price += ($product['price'] * $product['count']);

            if(!empty($product['promos'])){
                foreach ($product['promos'] as $promo){
                    $total_subsidy += $promo['subsidy'];
                  }
            }
        }

        $total_price += $info['delivery']['price'];

        $order_products = $this->getProductInfo($products);
		
		$settings_dbs = $this->model_extension_module_yandex_beru->getShippingsDBS();

        $shippings_info = json_decode($settings_dbs['value'], 1);

		$delivery_info = "";
		
		if(isset($shippings_info['shippings'][$info['delivery']['id']]['name'])){
        	$delivery_info .= "Доставка: " . $shippings_info['shippings'][$info['delivery']['id']]['name'] . "\r\n";
		}
		
		$shipmentDate = date('d-m-Y', strtotime('+ ' .  $shippings_info['shippings'][$info['delivery']['id']]['shipmentDate'] . ' day', strtotime(date("d-m-Y"))));
		
		$order_totals[] = [
			'code' => 'ym_delivery',
			'title' => 'Доставка "'.$shippings_info['shippings'][$info['delivery']['id']]['name'].'"',
			'value' => $info['delivery']['price'],
			'sort_order' => '8',
		];
		
		if(isset($info['delivery']['liftType'])){
			if(isset($info['delivery']['liftPrice'])){
				$liftPrice = $info['delivery']['liftPrice'];
			}else{
				$liftPrice = 0;
			}
			
			$order_totals[] = [
				'code'       => 'ym_delivery_lift',
				'title'      => "Способ подъема на этаж: " . $this->language->get('lift_type_DBS_' . $info['delivery']['liftType']),
				'value'      => $liftPrice,
				'sort_order' => '9',
			];	
			
			$total_price += $liftPrice;
		}
		
        $order_totals[] = [
			'code' => 'total',
			'title' => 'Итого',
            'value' => $total_price,
            'total_subsidy' => $total_subsidy,
            'total_buyer_price' => $total_buyer_price,
			'sort_order' => '10',
		];

        if ($this->request->server['HTTPS']) {
			$store_url = HTTPS_SERVER;
		} else {
			$store_url = HTTP_SERVER;
        }
        
		switch ($info['delivery']['dispatchType']) {
            case "BUYER":
                $delivery_type = "Доставка покупателю";
                break;
            case "MARKET_PARTNER_OUTLET":
                $delivery_type = "Доставка в ПВЗ партнёра Маркета";
                break;
            case "MARKET_BRANDED_OUTLET":
                $delivery_type = "Доставка в брендированный ПВЗ Маркета";
                break;
            case "SHOP_OUTLET":
                $delivery_type = "Доставка в ПВЗ Магазина";
                break;
            case "DROPOFF":
                $delivery_type = "Доставка в дропофф";
                break;
        }       
     
		if(isset($info['delivery']['dates']['fromDate']) && isset($info['delivery']['dates']['toDate'])){
        	$delivery_info .= "Даты доставки: с " . $info['delivery']['dates']['fromDate'] . " по " . $info['delivery']['dates']['toDate'] . "\r\n";
		}
		if(isset($info['delivery']['address']['country'])){
        	$delivery_info .= "\tСтрана: " . $info['delivery']['address']['country'] . "\r\n";
		}

		$regionCompletely = $this->regionCompletely($info['delivery']['region']);
        
        $outlet_info = '';
		
		if(isset($info['delivery']['outletId'])){
            $this->api = new yandex_beru();
                    
            $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            $component = $this->api->loadComponent('outletInfo');
            $component->setOutletId($info['delivery']['outletId']);
            $out = $this->api->sendData($component);

            $storage_period_days = $out["outlet"]["storagePeriod"];
            $to_date = $info['delivery']['dates']['toDate'];
            $storagePeriod = "+" . $storage_period_days . " days";
            $storage_date = date("d-m-Y", strtotime($storagePeriod, strtotime($to_date)));
            
            $outlet_info = "Название точки продаж: " . $out["outlet"]["name"] . "\r\n";
            $outlet_info .= "Адрес: " . $out["outlet"]["address"]["city"] . ", " . $out["outlet"]["address"]["street"] . ", " . $out["outlet"]["address"]["number"] . "\r\n";
            if(isset($info['delivery']['outletStorageLimitDate'])){
                $outlet_info .= "Срок хранения до: " . $info['delivery']['outletStorageLimitDate'] . "\r\n";
            }
            $outlet_info .= "Срок хранения точки продаж: " . $storage_period_days . "\r\n";
		}
        
        if($info['delivery']['dispatchType'] == 'MARKET_BRANDED_OUTLET'){
            $order_info = $this->model_extension_module_yandex_beru->getOrderByMarketId($info['id']);
            $outlet_info = $order_info['shipping_address_2'];
        }
		
        $delivery_info .= "Регион: " . $regionCompletely . "\r\n";
		
		if(isset($info['delivery']['outletId']) || !empty($info['delivery']['outletId'])){
            $delivery_info .= "\tИндентификатор точки продаж: " . $info['delivery']['outletId'] . "\r\n";
		}
		if(isset($info['delivery']['address']['city'])){
        	$delivery_info .= "\tГород: " . $info['delivery']['address']['city'] . "\r\n";
		}
		if(isset($info['delivery']['address']['subway'])){
			 $delivery_info .= "\tМетро: " . $info['delivery']['address']['subway'] . "\r\n";
		}
        if(isset($info['delivery']['address']['house'])){
        	$delivery_info .= "\tДом: " . $info['delivery']['address']['house'] . "\r\n";
		}
		if(isset($info['delivery']['address']['block'])){
        	$delivery_info .= "\tДомофон: " . $info['delivery']['address']['block'] . "\r\n";
		}
		if(isset($info['delivery']['address']['floor'])){
			$delivery_info .= "\tЭтаж: " . $info['delivery']['address']['floor'] . "\r\n";
		}
		
		$order_data = [
			'invoice_prefix' => $this->config->get('config_invoice_prefix'),
			'store_id' => $this->config->get('config_store_id'),
			'store_name' => $this->config->get('config_name'),
			'store_url' => $store_url,
			'customer_id' => '',
			'customer_group_id' => '',
			'firstname' => isset($info['buyer']['firstName']) ? $info['buyer']['firstName'] : 'Яндекс_DBS',
			'lastname' => isset($info['buyer']['lastName']) ? $info['buyer']['lastName'] : '',
			'email' => $this->config->get('config_email'),
			'custom_field' => '',
			'payment_firstname' => 'Яндекс_DBS',
			'payment_lastname' => '',
			'payment_company' => '',
			'payment_address_1' => '',
			'payment_address_2' => '',
            'payment_city' => '',
            'payment_postcode' => '',
            'payment_country' => '',
            'payment_country_id' => '',
            'payment_zone' => '',
            'payment_zone_id' => '',
			'payment_address_format' => '',
            'payment_custom_field' => '',
			'payment_method' => $info['paymentMethod'],
			'payment_code' => '',
			'shipping_firstname' => 'Яндекс_DBS',
			'shipping_lastname' => '',
			'shipping_company' => '',
			'shipping_address_1' => $delivery_info,
			'shipping_address_2' => $outlet_info,
			'shipping_city' => '',
			'shipping_postcode' => '',
			'shipping_country' => '',
			'shipping_country_id' => '',
			'shipping_zone' => '',
			'shipping_zone_id' => '',
			'shipping_address_format' => '',
			'shipping_custom_field' => '',
			'shipping_method' => $delivery_type,
			'shipping_code' => '',
			'comment' => $comment,
			'total' => $total_price,
			'affiliate_id' => '',
			'commission' => '',
			'marketing_id' => '',
			'tracking' => '',
			'language_id' => '',
			'currency_id' => '',
			'currency_code' => 'RUB',// на яндекс RUR
			'currency_value' => '1',//По умолчанию 1
			'ip' => '',
			'forwarded_ip' => '',
			'user_agent' => '',
			'accept_language' => '',
			'products' => $order_products,
			'vouchers' => array(),
			'totals' => $order_totals,
			'shipmentDate' => $shipmentDate,
		];
		return $order_data;
    }
    
    private function formatOrderInfoFBS($info, $comment){
        $this->load->language('extension/module/yandex_market');
        $this->load->model('extension/module/yandex_beru');

        $total_price        = 0;
        $total_subsidy      = 0;
        $total_buyer_price  = 0;
       
        foreach ($info['items'] as $product){

            $products[] = array(
                'product_id'    => $product['offerId'],
                'quantity'      => $product['count'],
                'price'         => $product['price']
            );

            $total_price += ($product['price'] * $product['count']);

            if(!empty($product['promos'])){
                foreach ($product['promos'] as $promo){
                    $total_subsidy += $promo['subsidy'];
                  }
            }
        }

        $total_price += $info['delivery']['price'];

        $order_products = $this->getProductInfo($products);

		$delivery_info = "";
		
		$shipmentDate = $info['delivery']['shipments'][0]['shipmentDate'];

		$order_totals[] = [
			'code' => 'ym_delivery',
			'title' => 'Доставка "'.$info['delivery']['deliveryPartnerType'].'"',
			'value' => $info['delivery']['price'],
			'sort_order' => '8',
		];
		
		if(isset($info['delivery']['liftType'])){
			if(isset($info['delivery']['liftPrice'])){
				$liftPrice = $info['delivery']['liftPrice'];
			}else{
				$liftPrice = 0;
			}
			
			$order_totals[] = [
				'code'       => 'ym_delivery_lift',
				'title'      => "Способ подъема на этаж: " . $this->language->get('lift_type_DBS_' . $info['delivery']['liftType']),
				'value'      => $liftPrice,
				'sort_order' => '9',
			];	
			
			$total_price += $liftPrice;
		}
		
        $order_totals[] = [
			'code' => 'total',
			'title' => 'Итого',
            'value' => $total_price,
            'total_subsidy' => $total_subsidy,
            'total_buyer_price' => $total_buyer_price,
			'sort_order' => '10',
		];
		
        if ($this->request->server['HTTPS']) {
			$store_url = HTTPS_SERVER;
		} else {
			$store_url = HTTP_SERVER;
        }
     
		if(isset($info['delivery']['dates']['fromDate']) && isset($info['delivery']['dates']['toDate'])){
        	$delivery_info .= "Даты доставки: с " . $info['delivery']['dates']['fromDate'] . " по " . $info['delivery']['dates']['toDate'] . "\r\n";
		}
		if(isset($info['delivery']['address']['country'])){
        	$delivery_info .= "\tСтрана: " . $info['delivery']['address']['country'] . "\r\n";
		}

		$regionCompletely = $this->regionCompletely($info['delivery']['region']);
		
        $delivery_info .= "Регион: " . $regionCompletely . "\r\n";
		
		if(isset($info['delivery']['address']['city'])){
        	$delivery_info .= "\tГород: " . $info['delivery']['address']['city'] . "\r\n";
		}
		if(isset($info['delivery']['address']['subway'])){
			 $delivery_info .= "\tМетро: " . $info['delivery']['address']['subway'] . "\r\n";
		}
        if(isset($info['delivery']['address']['house'])){
        	$delivery_info .= "\tДом: " . $info['delivery']['address']['house'] . "\r\n";
		}
		if(isset($info['delivery']['address']['block'])){
        	$delivery_info .= "\tДомофон: " . $info['delivery']['address']['block'] . "\r\n";
		}
		if(isset($info['delivery']['address']['floor'])){
			$delivery_info .= "\tЭтаж: " . $info['delivery']['address']['floor'] . "\r\n";
		}
		
		$order_data = [
			'invoice_prefix' => $this->config->get('config_invoice_prefix'),
			'store_id' => $this->config->get('config_store_id'),
			'store_name' => $this->config->get('config_name'),
			'store_url' => $store_url,
			'customer_id' => '',
			'customer_group_id' => '',
			'firstname' => 'Яндекс_FBS',
			'lastname' => '',
			'email' => $this->config->get('config_email'),
			'custom_field' => '',
			'payment_firstname' => 'Яндекс_FBS',
			'payment_lastname' => '',
			'payment_company' => '',
			'payment_address_1' => '',
			'payment_address_2' => '',
            'payment_city' => '',
            'payment_postcode' => '',
            'payment_country' => '',
            'payment_country_id' => '',
            'payment_zone' => '',
            'payment_zone_id' => '',
			'payment_address_format' => '',
            'payment_custom_field' => '',
			'payment_method' => $info['paymentMethod'],
			'payment_code' => '',
			'shipping_firstname' => 'Яндекс_DBS',
			'shipping_lastname' => '',
			'shipping_company' => '',
			'shipping_address_1' => $delivery_info,
			'shipping_address_2' => '',
			'shipping_city' => '',
			'shipping_postcode' => '',
			'shipping_country' => '',
			'shipping_country_id' => '',
			'shipping_zone' => '',
			'shipping_zone_id' => '',
			'shipping_address_format' => '',
			'shipping_custom_field' => '',
			'shipping_method' => $info['delivery']['serviceName'],
			'shipping_code' => '',
			'comment' => $comment,
			'total' => $total_price,
			'affiliate_id' => '',
			'commission' => '',
			'marketing_id' => '',
			'tracking' => '',
			'language_id' => '',
			'currency_id' => '',
			'currency_code' => 'RUB',// на яндекс RUR
			'currency_value' => '1',//По умолчанию 1
			'ip' => '',
			'forwarded_ip' => '',
			'user_agent' => '',
			'accept_language' => '',
			'products' => $order_products,
			'vouchers' => array(),
			'totals' => $order_totals,
			'shipmentDate' => $shipmentDate,
		];
		return $order_data;
    }
    
    private function regionCompletely($region_array, $region_name = ''){

    	$region_name .= $region_array['name'] . ' ';

    	if(!empty($region_array['parent'])){

    		return $this->regionCompletely($region_array['parent'], $region_name);

    	} else {

    		return $region_name;

    	}

    }
    
    private function getProductInfo($products){
        $this->load->model('catalog/product');
        $this->load->model('extension/module/yandex_beru');

        $result_products = array();

        foreach ($products as $product) {

                $offer_key = $this->model_extension_module_yandex_beru->getKeyByShopSku($product['product_id']);
                
                $offer_key_data = explode('-',$offer_key);
                $product_id = array_shift($offer_key_data);
                
                $product_options = array();

                if(!empty($offer_key_data)){
                
                    $options = array_chunk($offer_key_data, 2);

                    foreach($options as $option){

                        $option_value = $this->model_extension_module_yandex_beru->getProductOptionValue($product_id, $option[0], $option[1]);
                        
                        $product_options[] = [
                            'product_option_id' => $option_value['product_option_id'],
                            'product_option_value_id' => $option_value['product_option_value_id'],
                            'name' => $option_value['option_name'],
                            'value' => $option_value['name'],
                            'type' => $option_value['type'],
                        ];
                    }

                }
        
            $product_info = $this->model_catalog_product->getProduct($product['product_id']);

            $result_products[] = array(

                'product_id' => $product['product_id'],
                'name' => $product_info['name'],
                'model' =>  $product_info['model'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'total' => (int)$product['price']*(int)$product['quantity'],
                'tax' => '',
                'reward' => '',
                'option' => $product_options,
            );

        }
        return $result_products;
 
    }
}
