<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOrderItems extends Controller {
	private $error = array();
	
	private $reasons = ['PARTNER_REQUESTED_REMOVE', 'USER_REQUESTED_REMOVE'];

	public function index() {
		$this->api = new yandex_beru();
		
		$this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
		
		$this->load->language('sale/order');
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->load->model('tool/upload');
		$this->load->model('sale/order');
		$this->load->model('extension/module/yandex_beru');
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['products'] = array();
		
		$order_info = array();
		
		$data['reasons'] = array();
		
		foreach($this->reasons as $reason){
			$data['reasons'][] = [
				'reason_id' => $reason,
				'name'      => $this->language->get('order_change_reason_'. $reason),
			];
		}
		
		if(!empty($this->request->get['order_id'])){
			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
			
			$market_order_id = $this->model_extension_module_yandex_beru->gerMarketOrderId($this->request->get['order_id']);
			
			$data['order_id'] = $this->request->get['order_id'];
		}else{
			$data['order_id'] = 0;
			
			$market_order_id = 0;
		}
		
		if(!empty($this->request->post['reason_id'])){
			$data['reason_id'] = $this->request->post['reason_id'];
		}else{
			$data['reason_id'] = false;
		}
		
		$data['error_reason'] = '';
		
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
				"reason" => $data['reason_id'],
			];
			
			$component->setData($put_data);
			
			$response = $this->api->sendData($component); 
			
			if(is_array($response) || $response == ''){//верные данные всегда массив, ошибки строка.
				$data['refresh_page'] = true;
			} else {
				$code_error = array("BAD_REQUEST", "CANCELLATION_REQUESTED", "DELETED_ITEMS_EXCEEDS_THRESHOLD", "INVALID_CIS_CODE", "ITEM_DUPLICATE", "ITEM_NOT_FOUND", "ITEMS_ADDITION_NOT_SUPPORTED", "NOT_FOUND", "OTHER_REMOVE_ITEM_ERROR", "PAYMENT_PROHIBITS_DELETE", "PROMO_PROHIBITS_DELETE", "STATUS_NOT_ALLOWED", "TOO_FEW_CISES_FOR_ITEM_CODE", "TOO_MANY_CISES_FOR_ITEM");
				
				$text_code_error = array(
					$this->language->get('error_item_BAD_REQUEST'),
					$this->language->get('error_item_CANCELLATION_REQUESTED'),
					$this->language->get('error_item_DELETED_ITEMS_EXCEEDS_THRESHOLD'),
					$this->language->get('error_item_INVALID_CIS_CODE'),
					$this->language->get('error_item_ITEM_DUPLICATE'),
					$this->language->get('error_item_ITEM_NOT_FOUND'),
					$this->language->get('error_item_ITEMS_ADDITION_NOT_SUPPORTED'),
					$this->language->get('error_item_NOT_FOUND'),
					$this->language->get('error_item_OTHER_REMOVE_ITEM_ERROR'),
					$this->language->get('error_item_PAYMENT_PROHIBITS_DELETE'),
					$this->language->get('error_item_PROMO_PROHIBITS_DELETE'),
					$this->language->get('error_item_STATUS_NOT_ALLOWED'),
					$this->language->get('error_item_TOO_FEW_CISES_FOR_ITEM_CODE'),
					$this->language->get('error_item_TOO_MANY_CISES_FOR_ITEM'),
				);
				
				$data['error_products'] = str_replace($code_error, $text_code_error, $response);

			}
		}
		
		if (isset($this->error['products'])) {
			$data['error_products'] = $this->error['products'];
		}
		
		if (isset($this->error['reason'])) {
			$data['error_reason'] = $this->error['reason'];
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
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/order_items_modal', $data));	
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

        $this->load->model('catalog/product');
		
		$result_product = [];
		
        $product_info = $this->model_catalog_product->getProduct($item['offerId']);
		
		if(!empty($product_info)){
			$result_products = [
				'product_id' => $item['offerId'],
				'name'       => $product_info['name'],
				'model'      => $product_info['model'],
				'quantity'   => $item['count'],
				'price'      => $item['price'],
				'total'      => round($item['price'] * $item['count'],2),
				'tax'        => '',
				'reward'     => '',
				'option'     => [],
			];
		}

        return $result_product;
    }
	
	private function getTotals($order_data){
		$this->load->language('extension/module/yandex_marketplace');
		$totals = [];
		
		if(!empty($order_data['delivery'])){
			$totals[] = [
				'code' => 'ym_delivery',
				'title' => 'Яндекс: '.  $order_data['delivery']['serviceName'],
				'value' => $order_data['deliveryTotal'],
				'sort_order' => '8',
			];
		}
		
		$lift_total = 0;
		
		if(isset($info['delivery']['liftType'])){
			if(isset($order_data['delivery']['liftPrice'])){
				$liftPrice = $order_data['delivery']['liftPrice'];
			}else{
				$liftPrice = 0;
			}
			
			$order_totals[] = [
				'code'       => 'ym_delivery_lift',
				'title'      => "Способ подъема на этаж: " . $this->language->get('lift_type_DBS_' . $order_data['delivery']['liftType']),
				'value'      => $liftPrice,
				'sort_order' => '9',
			];	
			
			$lift_total = $liftPrice;
			
		}
		
		$totals[] = [
			'code' => 'total',
			'title' => 'Итого',
			'value' => round(($order_data['itemsTotal']+$order_data['subsidyTotal']+$order_data['deliveryTotal']+$lift_total),2),
			'total_buyer_price' => $order_data['itemsTotal'],
			'total_subsidy' => $order_data['subsidyTotal'],
			'sort_order' => '10',
		];
		
		return $totals;
	}
	
	protected function validate() {
		if(!empty($this->request->post['reason_id'])){
			if(!in_array($this->request->post['reason_id'], $this->reasons)){
				$this->error['reason'] = 'Некорректная причина отмены заказа';
			}
		}else{
			$this->error['reason'] = 'Необходима причина изменения заказа';
		}
		
		if(empty($this->request->post['products'])){
			$this->error['products'] = 'Нельзя удалить товар или изменить его количество, если он последний в заказе';
		}
		
		return !$this->error;
	}
}