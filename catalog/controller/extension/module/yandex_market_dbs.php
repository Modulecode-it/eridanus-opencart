<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketdbs extends Controller {

    public function cart(){
        
        $log = new Log('yandex_beru_cart_dbs.log');
        $log->write(print_r($this->request->get,1));
        $log->write(print_r(file_get_contents('php://input'),1));
        
        $this->load->model('extension/module/yandex_beru');
		
		if ($this->validate()) {
			$cart_info = json_decode(file_get_contents('php://input'), 1);

			$settings_dbs = $this->model_extension_module_yandex_beru->getShippings();

			$shippings_info = json_decode($settings_dbs['value'], 1);

			$products_shipping = array();

			$todays_date = date("d-m-Y");

			if($cart_info['cart']['delivery']['region']['name'] == "Москва"){
				$region_id = $cart_info['cart']['delivery']['region']['id'];
			} else {
				$region_id = $this->parseRegion($cart_info['cart']['delivery']['region']);
			}

			foreach ($shippings_info['shippings'] as $shipping_id => $shipping_info) {
				if(in_array($region_id, $shipping_info['shipping_zone'])){
					if(empty($shipping_info['products'])){
						$shipping_info['products'] = array();
					}
				
					$products_shipping[$shipping_id] = $this->model_extension_module_yandex_beru->getProductsShipping($shipping_info['products'], $shipping_info['filter']);
			   } else {
					unset($shippings_info['shippings'][$shipping_id]);
			   }
			}

			$check_product = array();

			foreach ($products_shipping as $shipping_id => $product_shipping) {
				$check_product[$shipping_id] = 0;
				
				foreach ($cart_info['cart']['items'] as $item) {
					$result = in_array($item['offerId'], $product_shipping);
					
					if($result === true){
						$check_product[$shipping_id]++;
					} 
				}
			}

			$count_items = count($cart_info['cart']['items']);

			foreach ($check_product as $shipping_id => $count) {
				if ($count_items > $count){
					unset($shippings_info['shippings'][$shipping_id]);
				}
			}

			$paymentMethods['YANDEX']           = 0;
			$paymentMethods['APPLE_PAY']        = 0;
			$paymentMethods['GOOGLE_PAY']       = 0;
			$paymentMethods['CARD_ON_DELIVERY'] = 0;
			$paymentMethods['CASH_ON_DELIVERY'] = 0;

			$count_shipping = count($shippings_info);

			foreach ($shippings_info['shippings'] as $key => $shipping_info) {
				$result_YANDEX = in_array('YANDEX', $shipping_info['paymentMethods']);
				
				if($result_YANDEX === true){
					$paymentMethods['YANDEX']++;
				} 
				
				$result_APPLE_PAY = in_array('APPLE_PAY', $shipping_info['paymentMethods']);
				
				if($result_APPLE_PAY === true){
					$paymentMethods['APPLE_PAY']++;
				} 
				
				$result_GOOGLE_PAY = in_array('GOOGLE_PAY', $shipping_info['paymentMethods']);
				
				if($result_GOOGLE_PAY === true){
					$paymentMethods['GOOGLE_PAY']++;
				} 
				
				$result_CARD_ON_DELIVERY = in_array('CARD_ON_DELIVERY', $shipping_info['paymentMethods']);
				
				if($result_CARD_ON_DELIVERY === true){
					$paymentMethods['CARD_ON_DELIVERY']++;
				} 
				
				$result_CASH_ON_DELIVERY = in_array('CASH_ON_DELIVERY', $shipping_info['paymentMethods']);
				
				if($result_CASH_ON_DELIVERY === true){
					$paymentMethods['CASH_ON_DELIVERY']++;
				} 

			}

			$result_paymentMethods = array();

			if($paymentMethods['YANDEX'] <= $count_shipping){
			  array_push($result_paymentMethods, 'YANDEX');          
			}
			
			if($paymentMethods['APPLE_PAY'] <= $count_shipping){
				array_push($result_paymentMethods, 'APPLE_PAY');    
			}
			
			if($paymentMethods['GOOGLE_PAY'] <= $count_shipping){
				array_push($result_paymentMethods, 'GOOGLE_PAY');          
			}
			
			if($paymentMethods['CARD_ON_DELIVERY'] <= $count_shipping){
				array_push($result_paymentMethods, 'CARD_ON_DELIVERY');          
			}
			
			if($paymentMethods['CASH_ON_DELIVERY'] <= $count_shipping){
				array_push($result_paymentMethods, 'CASH_ON_DELIVERY');          
			}

		
			if(!empty($this->config->get('yandex_beru_weekend_days_of_week'))){
				$weekend_days_of_week = $this->config->get('yandex_beru_weekend_days_of_week');
			}else{
				$weekend_days_of_week = [];
			}

			$result_shippings = array();

			foreach ($shippings_info['shippings'] as $shipping_key => $shipping) {
				$working_days_diff = $shipping['toDate'] - $shipping['fromDate'];
			
				$days = 1;

				$from_date = false;
				
				$to_date = false;

				$working_days = 0;

				$from_date_days = $shipping['fromDate'];
				//Проверить есть ли хотябы один рабочий день в неделе
				//Если есть проверять до тех пор пока количество рабочих дней станет равно указанному числу fromDate/toDate

				if(count($weekend_days_of_week) < 7){
					while($days <= 365){
						$fromDateCheck = strtotime('+ ' . $days . ' day', strtotime($todays_date));

						$day_of_week = date("N", $fromDateCheck);
	//					Если день недели не выходной
						if(!in_array($day_of_week, $weekend_days_of_week)){
							$fromDateDay = date('d', $fromDateCheck);

							$fromDateMonth = date('m', $fromDateCheck);

							$holiday = $this->model_extension_module_yandex_beru->checkForHoliday($fromDateDay, $fromDateMonth, $this->config->get('yandex_beru_weekend_days_of_week'));

							if(!$holiday){
								$working_days++;		
							}
						}

						if($working_days == $from_date_days){
							$fromDate = date('d-m-Y', strtotime('+ ' .  $days . ' day', strtotime($todays_date)));

							break(1);
						}

						$days++;
					}

				}
				
				if($fromDate){
					
					$toDate =  date('d-m-Y', strtotime('+ ' .  $working_days_diff . ' day', strtotime($fromDate)));

					$result_shipping = array(
						'id'            => (string)$shipping_key,
						'price'         => 1,
						'serviceName'   => $shipping['name'],
						'type'          => $shipping['type'],
						'dates'         => array(
							'fromDate'      =>  $fromDate,
							'toDate'        =>  $toDate,
						),
					);

					//добавление параметра outlets при типе самовывозе PICKUP
                    $this->api = new yandex_beru();
                    
                    $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
                    $component = $this->api->loadComponent('outlets');
                    
                    $out = $this->api->sendData($component); 
                    
                    if($shipping['type'] == "PICKUP"){
                        foreach($out['outlets'] as $value){
                            $result_shipping["outlets"] []= [ 
                                "code" => $value['shopOutletCode'],
                            ];
                        }
                    }
					
					if(!empty($shipping['intervals'])){
						$delivery_days = $shipping['toDate'] - $shipping['fromDate'];

						for ($i = 0; $i <= $delivery_days; $i++) {
							foreach($shipping['intervals'] as $interval){
								$result_shipping['intervals'][] = array(
									'date'     => date('d-m-Y', strtotime('+ ' .  ($shipping['fromDate']+$i) . ' day', strtotime($todays_date))),
									'fromTime' => $interval['from'],
									'toTime' => $interval['to'],
								);
							}
						}
					}

					$result_shippings[] = $result_shipping;
				}
			}

			if(!empty($cart_info['cart']['address'])){
				$address = $cart_info['cart']['address'];
			}

			if(!empty($this->config->get('yandex_beru_check_5_dbs'))){ //Самопроверка. Отправляем нулевое кол-во товаров
				$items = array();
			} else {
				foreach ($cart_info['cart']['items'] as $item_key => $item) {
					$quantity = $this->model_extension_module_yandex_beru->getQuantity($item['offerId']);
	
					if($cart_info['cart']['items'] > $quantity){
						$result_quantity = $quantity;
					} else{
						$result_quantity = $cart_info['cart']['items'];
					}
	
					$items[] = array(
						'feedId'        => $item['feedId'],
						'offerId'       => $item['offerId'],
						'delivery'      => true,
						'count'         => (int)$result_quantity
					);
				}
			}
		
			$cart = array(
				'cart'  => array(
					'deliveryCurrency'  => "RUR",
					'deliveryOptions'   => $result_shippings,
					'address' => !empty($cart_info['cart']['address']) ? $cart_info['cart']['address'] : '',                  
					'items'             => $items,
					'paymentMethods'    => $result_paymentMethods,
				),

			);

			$this->response->addHeader('Content-Type: application/json');
			$this->response->addHeader('User-Agent: Yandex-Modul-OpenCart');
			$this->response->setOutput(json_encode($cart));
        } else {
			header('HTTP/1.1 403 Forbidden');
		}
    }


    private function parseRegion($region){

        if($region['type'] != "SUBJECT_FEDERATION" and !empty($region['parent']) ){

            return $this->parseRegion($region['parent']);

        } else {

            return $region['id'];

        }

       // SUBJECT_FEDERATION
    }

    // https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-stocks-docpage/
	public function stocks(){
		
		if ($this->validate()) {
			$this->load->model('extension/module/yandex_beru');

			$request = json_decode(file_get_contents('php://input'));

			$warehouse_id = $request->warehouseId;
			$skus = array();

			foreach ($request->skus as $sku) {

				if(!empty($this->config->get('yandex_beru_check_5_dbs'))){ //Самопроверка. Отправляем нулевое кол-во товаров

					$skus[] = array(
						'sku' => $sku,
						'warehouseId' => $warehouse_id,
						'items' => array(
							array(
								'type' => 'FIT',
								'count' => 0,
								'updatedAt' => date(DATE_ATOM)
							)
						)
					);
	
				} else {

					$skus[] = array(
						'sku' => $sku,
						'warehouseId' => $warehouse_id,
						'items' => array(
							array(
								'type' => 'FIT',
								'count' => $this->model_extension_module_yandex_beru->getQuantityBySKU($sku),
								'updatedAt' => date(DATE_ATOM)
							)
						)
					);

				}

			}

			$response = array(
				'skus' => $skus
			);

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
		
		return ($auth_token == $this->config->get('yandex_beru_auth_token_DBS') && $this->config->get('yandex_beru_status_DBS') == '1');	
	}
}
