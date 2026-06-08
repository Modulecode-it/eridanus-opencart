<?php
require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketDbsOrder extends Controller {
    public function accept() {

        $log = new Log('yandex_beru_accept_dbs.log');
        $log->write(print_r($this->request->get,1));
        $log->write(print_r(file_get_contents('php://input'),1));
    
        $log = new Log ('cart_dbs.log');

        $this->load->model('extension/module/yandex_beru');
        $this->load->model('checkout/order');

        $accept_info = json_decode(file_get_contents('php://input'), 1);
		$log->write(print_r($accept_info,1));

        if(!empty($accept_info['order'])){

            $settings_dbs = $this->model_extension_module_yandex_beru->getShippings();

            $shippings_info = json_decode($settings_dbs['value'], 1);

            $comment = $this->creatureСomment($accept_info['order']);

            $order_info = $this->formatOrderInfo($accept_info['order'], $comment, $shippings_info);
		
			$order_id = $this->model_extension_module_yandex_beru->getShopOrderId($accept_info['order']['id']);
			
			
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
                if(isset($accept_info['order']['delivery']['outlet'])){
                    $this->model_extension_module_yandex_beru->setOrderOutletId($order_id, $accept_info['order']['delivery']['outlet']['id']);
				}
				if($order_id){
					$this->model_extension_module_yandex_beru->setMarketOrderId($order_id, $accept_info['order']['id']);
					$this->model_extension_module_yandex_beru->setMarketOrderType($order_id, 'DBS');

					if(!empty($this->config->get('yandex_beru_check_5_dbs'))){ //Самопроверка. Отправляем нулевое кол-во товаров {
						$order = array(
							'order'     => array(
								"accepted"  => false,
								"reason"    => "OUT_OF_DATE",
							)

						);
					} else {

						$order = array(
							'order'     => array(
								"accepted"     => true,
								"shipmentDate" => $order_info['shipmentDate'],
								"id"           => (string)$order_id,
							)
						);
					}
				} else {
					$order = array(
						'order'     => array(
							"accepted"  => false,
							"reason"    => "OUT_OF_DATE",
						)

					);
				}
			}
			
            $this->response->addHeader('Content-Type: application/json');
            $this->response->addHeader('User-Agent: Yandex-Modul-OpenCart');
            $this->response->setOutput(json_encode($order));


        }

    }

    public function status() {
    
        $log = new Log('yandex_beru_status_dbs.log');
        $log->write(print_r($this->request->get,1));
        $log->write(print_r(file_get_contents('php://input'),1));
    
		$this->load->model('extension/module/yandex_beru');
        $this->load->model('checkout/order');
		$this->api = new yandex_beru();	

        $status_info = json_decode(file_get_contents('php://input'), 1);
		
        $customer_id = $this->model_extension_module_yandex_beru->checkCustomer($status_info['order']['buyer']['phone']);

        if(empty($customer_id)){

            $customer_id = $this->model_extension_module_yandex_beru->addCustomer($status_info['order']['buyer']);
        }
        
		$this->model_extension_module_yandex_beru->editOrder($status_info['order'], $customer_id);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->addHeader('User-Agent: Yandex-Modul-OpenCart');


    }

    private function creatureСomment($info){
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

        $settings_dbs = $this->model_extension_module_yandex_beru->getShippings();

        $shippings_info = json_decode($settings_dbs['value'], 1);
		
		if(isset($info['notes'])){
        	$comment .= "Комментарий: " . $this->db->escape($info['notes']) . "\r\n";
		}


        return $comment;

    }

    private function getProducts($info){

        $products = array();

        foreach ($info['items'] as $product) {

            $products[] = array(
                'product_id'    => $product['offerId'],
                'quantity'      => $product['count'],
            );

        }

        return $products;

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
		
		$settings_dbs = $this->model_extension_module_yandex_beru->getShippings();

        $shippings_info = json_decode($settings_dbs['value'], 1);
		
		$delivery_info = "";
		
		if(isset($shippings_info['shippings'][$info['delivery']['shopDeliveryId']]['name'])){
        	$delivery_info .= "Доставка: " . $shippings_info['shippings'][$info['delivery']['shopDeliveryId']]['name'] . "\r\n";
		}
		
		$shipmentDate = date('d-m-Y', strtotime('+ ' .  $shippings_info['shippings'][$info['delivery']['shopDeliveryId']]['shipmentDate'] . ' day', strtotime(date("d-m-Y"))));
		
		$order_totals[] = [
			'code' => 'ym_delivery',
			'title' => 'Доставка "'.$shippings_info['shippings'][$info['delivery']['shopDeliveryId']]['name'].'"',
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

		$outlet_info = '';
		
		if(isset($info['delivery']['outlet']['id'])){
            $this->api = new yandex_beru();
                    
            $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            $component = $this->api->loadComponent('outletInfo');
            $component->setOutletId($info['delivery']['outlet']['id']);
            $out = $this->api->sendData($component);

            $to_date = $info['delivery']['dates']['toDate'];
            $storagePeriod = "+" . $out["outlet"]["storagePeriod"] . " days";
            $storage_date = date("d-m-Y", strtotime($storagePeriod, strtotime($to_date)));
            
            $outlet_info = "Название точки продаж: " . $out["outlet"]["name"] . "\r\n";
            $outlet_info .= "Адрес: " . $out["outlet"]["address"]["city"] . ", " . $out["outlet"]["address"]["street"] . "\r\n";
            $outlet_info .= "Срок хранения: " . $storage_date . "\r\n";
		}elseif(isset($info['delivery']['address'])){
            $outlet_info = "Адрес брендированного ПВЗ Маркета: \r\n";
            if(isset($info['delivery']['address']['postcode'])){
                $outlet_info .= "\tИндекс: " . $info['delivery']['address']['postcode'] . "\r\n";
            }
            if(isset($info['delivery']['address']['city'])){
                $outlet_info .= "\tГород: " . $info['delivery']['address']['city'] . "\r\n";
            }
            if(isset($info['delivery']['address']['street'])){
                $outlet_info .= "\tУлица: " . $info['delivery']['address']['street'] . "\r\n";
            }
            if(isset($info['delivery']['address']['house'])){
                $outlet_info .= "\tДом: " . $info['delivery']['address']['house'] . "\r\n";
            }
            if(isset($info['delivery']['address']['block'])){
                $outlet_info .= "\tДомофон: " . $info['delivery']['address']['block'] . "\r\n";
            }
		}
		
        $delivery_info .= "Регион: " . $regionCompletely . "\r\n";
		
		if(isset($info['delivery']['outlet']['id']) || !empty($info['delivery']['outlet']['id'])){
            $delivery_info .= "\tИндентификатор точки продаж: " . $info['delivery']['outlet']['id'] . "\r\n";
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
			'firstname' => 'Яндекс_DBS',
			'lastname' => '',
			'email' => $this->config->get('config_email'),
			'telephone' => '',
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
			'payment_method' => '',
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
			'shipping_method' => $shippings_info['shippings'][$info['delivery']['shopDeliveryId']]['name'],
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

    private function getProductInfo($products){

        $this->load->model('catalog/product');

        $result_products = array();

        foreach ($products as $product) {

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
                'option' => array(),
            );
        
        }

        return $result_products;
 
    }

    private function regionCompletely($region_array, $region_name = ''){

    	$region_name .= $region_array['name'] . ' ';

    	if(!empty($region_array['parent'])){

    		return $this->regionCompletely($region_array['parent'], $region_name);

    	} else {

    		return $region_name;

    	}

    }
	
	private function getInfo() {

		static $instance;

		if (!$instance) {
			$instance = $this->api->loadComponent('info');
		}

		return $instance;
	}

}
