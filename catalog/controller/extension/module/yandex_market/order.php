<?php

// Настройка API
// https://partner.market.yandex.ru/supplier/21658724/api/settings

// Песочница для создания тестовых заказов
// https://partner.market.yandex.ru/supplier/21658724/sandbox

// Список заказов
//https://partner.market.yandex.ru/supplier/21658724/orders

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketOrder extends Controller {
	// Передача заказа и запрос на принятие заказа
	// https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-order-accept-docpage/
	public function accept() {
		$log = new Log('yandex_beru_order_accept.log');
		
		$this->load->model('checkout/order');
		$this->load->model('extension/module/yandex_beru');
		
		if ($this->validate()) {
			$log->write(print_r($this->request->get,1));
			$log->write(print_r(file_get_contents('php://input'),1));

			$request = json_decode(file_get_contents('php://input'));
			$log->write(print_r($request,1));
			$order = array();
			
			$order_info = $this->prepareOffer($request);
			$log->write(print_r($order_info,1)); 
			
			$order_id = $this->model_extension_module_yandex_beru->getShopOrderId($request->order->id);
			
			if($order_id){
				if(!empty($this->config->get('yandex_beru_check_5_fbs'))){ //Самопроверка. Отправляем нулевое кол-во товаров 
					$order = array(
						'accepted' => false,
						'reason' => 'OUT_OF_DATE'
					);
				} else { 
					$order = array(
						'accepted' => true,
						'id' => (string)$order_id
					);
				}
			}else{
				$order_id = $this->model_checkout_order->addOrder($order_info);

				if($order_id){

					$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$this->config->get('payment_cod_order_status_id') . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

					$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$this->config->get('payment_cod_order_status_id') . "', notify = '0', comment = '', date_added = NOW()");

					$log->write(print_r($request->order->delivery->shipments[0]->id,1));
					$this->model_extension_module_yandex_beru->setOrderShipmentId($order_id, $request->order->delivery->shipments[0]->id);
					$this->model_extension_module_yandex_beru->setMarketOrderId($order_id, $request->order->id);

					$log->write(print_r($request->order->delivery->shipments[0]->shipmentDate,1));
					$this->model_extension_module_yandex_beru->setMarketShipmentDate($order_id, $request->order->delivery->shipments[0]->shipmentDate);

					$this->model_extension_module_yandex_beru->setMarketOrderType($order_id, 'FBS');
					//Если выполнено то принимаем заказ

					if(!empty($this->config->get('yandex_beru_check_5_fbs'))){ //Самопроверка. Отправляем нулевое кол-во товаров 
						$order = array(
							'accepted' => false,
							'reason' => 'OUT_OF_DATE'
						);
					} else { 
						$order = array(
							'accepted' => true,
							'id' => (string)$order_id
						);
					}
				} else {
				//Если нет то отклоняем
					$order = array(
						'accepted' => false,
						'reason' => 'OUT_OF_DATE'
					);
				}
				
			}
			
			$response = array(
				'order' => $order
			);

			$log->write(print_r(json_encode($response),1));

			$this->response->addHeader('Content-Type: application/json');
			$this->response->addHeader('User-Agent: Yandex-Modul-OpenCart');
			$this->response->setOutput(json_encode($response));
		} else {
			header('HTTP/1.1 403 Forbidden');
		}

		/*
		Ошибка 400 Bad Request
		Если магазин считает запрос, поступающий от маркетплейса Беру, некорректным, магазин должен вернуть статус ответа 400 с описанием причины ошибки в теле ответа. Такие ответы будут анализироваться на предмет нарушений и недоработок API со стороны маркетплейса Беру.

		Ошибка 500 Internal Server Error
		В случае технической ошибки на стороне магазина он должен вернуть статус ответа 500. Магазины с большим количеством таких ответов могут быть отключены от маркетплейса Беру.
		*/
	}
	
	public function prepareOffer($response){
		$order_products = array();
		$order_totals = array();

		$total_price = 0;
		$total_buyer_price =0;
		$total_subsidy =0;
		foreach($response->order->items as $item){
			$product_option = array();
			$order_product = $this->getProductInfo($item);
			
			
			if($order_product){
				$order_products[] = $order_product; 	
			}

		
			$total_price += ($item->price * $item->count);
			$total_buyer_price += $item->{"buyer-price"};
			$total_subsidy += $item->subsidy;
			
		} 
		$order_totals[] = [
			'code' => 'total',
			'title' => 'Итого',
			'value' => $total_price,
			'total_buyer_price' => $total_buyer_price,
			'total_subsidy' => $total_subsidy,
			'sort_order' => '9',
		];
		if ($this->request->server['HTTPS']) {
			$store_url = HTTPS_SERVER;
		} else {
			$store_url = HTTP_SERVER;
		}
		$order_data = [
			'invoice_prefix' => $this->config->get('config_invoice_prefix'),
			'store_id' => $this->config->get('config_store_id'),
			'store_name' => $this->config->get('config_name'),
			'store_url' => $store_url,
			'customer_id' => '',
			'customer_group_id' => '',
			'firstname' => 'Яндекс',
			'lastname' => '',
			'email' => $this->config->get('config_email'),
			'telephone' => '',
			'custom_field' => '',
			'payment_firstname' => 'Яндекс',
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
			'payment_method' => '',
			'payment_code' => '',
			'shipping_firstname' => 'Яндекс',
			'shipping_lastname' => '',
			'shipping_company' => '',
			'shipping_address_1' => '',
			'shipping_address_2' => '',
			'shipping_city' => '',
			'shipping_postcode' => '',
			'shipping_country' => '',
			'shipping_country_id' => '',
			'shipping_zone' => '',
			'shipping_zone_id' => '',
			'shipping_address_format' => '',
			'shipping_custom_field' => '',
			'shipping_method' => '',
			'shipping_code' => '',
			'comment' => '',
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
		];

		return $order_data;
	}
	
	public function getProductInfo($item){
		
		$this->load->model('extension/module/yandex_beru');
	
		$offer_key = $this->model_extension_module_yandex_beru->getKeyByShopSku($item->offerId);

		if($offer_key){
			
			$offer_key_data = explode('-',$offer_key);
			$product_id = array_shift($offer_key_data);
			
			$product_data = $this->model_extension_module_yandex_beru->getProductData($product_id);
			
			$order_product = array();
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

			return [
				'product_id' => $product_id,
				'name' => $product_data['name'],
				'model' =>  $product_data['model'],
				'quantity' => $item->count,
				'price' => $item->price,
				'total' => $item->price,
				'tax' => '',
				'reward' => '',
				'option' => $product_options,
			];
			
		}else{
			return false;
		}
		
		
	}
	
	// Уведомление о смене статуса заказа
	// https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-order-status.html/
	public function status() {
        $log = new Log('yandex_beru_order_status.log');
        $log->write(print_r($this->request->get,1));
        $log->write(print_r(file_get_contents('php://input'),1));
        $log->write(file_get_contents('php://input'),1);
	
		$this->load->model('extension/module/yandex_beru');
		
		if ($this->validate()) {

			if(json_decode(file_get_contents('php://input'))){
                $request = json_decode(file_get_contents('php://input'), 1);
			} else {
                $request = file_get_contents('php://input');
                $request = json_decode($request);
			}

			if(!empty($request)) {
				$eac = isset($request['order']['electronicAcceptanceCertificateCode']) ? $request['order']['electronicAcceptanceCertificateCode'] : '';
				$vehicle_number = isset($request['order']['vehicleNumber']) ? $request['order']['vehicleNumber'] : '';
				
				if(isset($request['order']['delivery']['courier']) || isset($request['order']['substatus'])){
                    $delivery_courier_info = array(
                        'substatus' => $request['order']['substatus'],
                        'delivery_courier' => isset($request['order']['delivery']['courier']) ? $request['order']['delivery']['courier'] : '',
                    );
                    $delivery_courier = json_encode($delivery_courier_info, JSON_UNESCAPED_UNICODE);
				}else{
                    $delivery_courier = '';
				}
                
                
				$this->model_extension_module_yandex_beru->changeShopStatus($request['order']['id'], $request['order']['status'], $request['order']['substatus'], $eac, $vehicle_number, $delivery_courier);
			}

		} else {
			header('HTTP/1.1 403 Forbidden');
		}

//         if ($this->validate()) {
// 			$request = file_get_contents('php://input');
// 
// 			if(!empty($request)) {
// 				$eac = isset($request->order->electronicAcceptanceCertificateCode) ? $request->order->electronicAcceptanceCertificateCode : '';
// 				$vehicle_number = isset($request->order->vehicleNumber) ? $request->order->vehicleNumber : '';
// 				
// 				if(isset($request->order->delivery->courier) || isset($request->order->substatus)){
//                     $delivery_courier_info = array(
//                         'substatus' => $request->order->substatus,
//                         'delivery_courier' => isset($request->order->delivery->courier) ? $request->order->delivery->courier : '',
//                     );
//                     $delivery_courier = json_encode($delivery_courier_info, JSON_UNESCAPED_UNICODE);
// 				}else{
//                     $delivery_courier = '';
// 				}
//                 
// 				$this->model_extension_module_yandex_beru->changeShopStatus($request->order->id, $request->order->status, $request->order->substatus, $eac, $vehicle_number, $delivery_courier);
// 			}
// 
// 		} else {
// 			header('HTTP/1.1 403 Forbidden');
// 		}
	}

	private function validate() {
		$auth_token = '';
			
		if (!isset($headers['AUTHORIZATION'])) {
			if (function_exists('apache_request_headers')) {
				$requestHeaders = apache_request_headers();
				$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
				if (isset($requestHeaders['Authorization'])) {
					$auth_token = trim($requestHeaders['Authorization']);
				}
			}
		}
		
		if(!empty($this->request->get['auth-token'])){
			$auth_token = $this->request->get['auth-token'];
		}
		
		return ($auth_token == $this->config->get('yandex_beru_auth_token') && $this->config->get('yandex_beru_status') == '1');	
	}
}
?>
