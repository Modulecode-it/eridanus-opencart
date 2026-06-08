<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOrderItemsFBS extends Controller {
	private $error = array();
	
	public function index() {
		$this->api = new yandex_beru();
		
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		
		$this->load->language('sale/order');
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->load->model('tool/upload');
		$this->load->model('sale/order');
		$this->load->model('extension/module/yandex_beru');
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['products'] = array();
		
		$order_info = array();
		
		if(!empty($this->request->get['order_id'])){
			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
			
			$market_order_id = $this->model_extension_module_yandex_beru->gerMarketOrderId($this->request->get['order_id']);
			
			$data['order_id'] = $this->request->get['order_id'];
		}else{
			$data['order_id'] = 0;
			
			$market_order_id = 0;
		}
		
		$data['error_products'] = '';
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $market_order_id && $this->validate()) {
			$component = $this->api->loadComponent('campaignsIdOrdersIdItems');
			
			$component->setOrder($market_order_id);
			
			$items = array();
			
			foreach($this->request->post['products'] as $item_id => $post_product){
				$items[] = [
					"id"    => $item_id,
					"count" => $post_product['quantity'],
				];
			}
			
			$put_data = [
				"items"  => $items,
			];
			
			$component->setData($put_data);
			
			$response = $this->api->sendData($component); 
			
			if(is_array($response) || $response == ''){//верные данные всегда массив, ошибки строка.
				$data['refresh_page'] = true;
			} else {
				$code_error = array("BAD_REQUEST", "ITEM_NOT_FOUND", "INVALID_ITEM", "ITEM_DUPLICATE", "ITEMS_ADDITION_NOT_SUPPORTED", "CANNOT_REMOVE_LAST_ITEM", "CANNOT_REMOVE_ITEM", "STATUS_NOT_ALLOWED", "OTHER_REMOVE_ITEM_ERROR");
				
				$text_code_error = array(
					$this->language->get('error_item_BAD_REQUEST'),
					$this->language->get('error_item_ITEM_NOT_FOUND'),
					$this->language->get('error_item_INVALID_ITEM'),
					$this->language->get('error_item_ITEM_DUPLICATE'),
					$this->language->get('error_item_ITEMS_ADDITION_NOT_SUPPORTED'),
					$this->language->get('error_item_CANNOT_REMOVE_LAST_ITEM'),
					$this->language->get('error_item_CANNOT_REMOVE_ITEM'),
					$this->language->get('error_item_STATUS_NOT_ALLOWED_FBS'),
					$this->language->get('error_item_OTHER_REMOVE_ITEM_ERROR'),
				);
				
				$data['error_products'] = str_replace($code_error, $text_code_error, $response);

			}
		}
		
		if (isset($this->error['products'])) {
			$data['error_products'] = $this->error['products'];
		}
		
		if(!empty($order_info)){
			$order_component = $this->api->loadComponent('order');
			
			$order_component->setOrder($market_order_id);
			
			$order_response = $this->api->sendData($order_component);

			if(!empty($order_response['order'])){
				$this->refreshOrder($data['order_id'], $order_response['order']);
			}
			
			foreach ($order_response['order']['items'] as $product) {
				$data['products'][] = array(
					'yanex_item_id'    => $product['id'],
					'name'    	 	   => $product['offerName'],
					'quantity'		   => $product['count'],
					'price'    		   => $this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format(($product['price'] * $product['count']), $order_info['currency_code'], $order_info['currency_value']),
				);
			}
		}
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/order_items_modal_fbs', $data));	
	}
	
	private function refreshOrder($order_id, $order_data){
		$order_products = [];
		
		$totals = $this->getTotals($order_data);
		
		foreach($order_data['items'] as $item){
			$order_product = $this->getProductInfo($item);
			
			if($order_product){
				$order_products[] = $order_product; 	
			}
		}

		if(!empty($totals)){
			$this->model_extension_module_yandex_beru->refreshOrderTotals($order_id, $totals);
		}
		
		if(!empty($order_products)){
			$this->model_extension_module_yandex_beru->refreshOrderProducts($order_id, $order_products);
		}
	}
	
	private function getProductInfo($item){
		
		$this->load->model('extension/module/yandex_beru');
	
		$offer_key = $this->model_extension_module_yandex_beru->getKeyByShopSku($item['offerId']);
		
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
				'name'       => $product_data['name'],
				'model'      => $product_data['model'],
				'quantity'   => $item['count'],
				'price'      => $item['price'],
				'total'      => round(($item['price'] * $item['count']), 2),
				'tax'        => '',
				'reward'     => '',
				'option'     => $product_options,
			];
			
		}else{
			return false;
		}
	}
	
	private function getTotals($order_data){
		$totals = [];
		
		if(!empty($order_data['delivery'])){
			$totals[] = [
				'code' => 'ym_delivery',
				'title' => 'Яндекс: '.  $order_data['delivery']['serviceName'],
				'value' => $order_data['deliveryTotal'],
				'sort_order' => '8',
			];
		}
		
		$totals[] = [
			'code' => 'total',
			'title' => 'Итого',
			'value' => round(($order_data['itemsTotal']+$order_data['subsidyTotal']+$order_data['deliveryTotal']),2),
			'total_buyer_price' => $order_data['itemsTotal'],
			'total_subsidy' => $order_data['subsidyTotal'],
			'sort_order' => '9',
		];
		
		return $totals;
	}
	
	protected function validate() {
		
		if(empty($this->request->post['products'])){
			$this->error['products'] = 'Нельзя удалить товар или изменить его количество, если он последний в заказе';
		}
		
		return !$this->error;
	}
}