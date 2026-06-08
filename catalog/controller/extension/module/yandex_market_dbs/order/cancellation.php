<?php
require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketDbsOrderCancellation extends Controller {
	public function notify(){
		$this->load->model('extension/module/yandex_beru');
        $this->load->model('checkout/order');
		$this->api = new yandex_beru();	

		if ($this->validate()) {
			$status_info = json_decode(file_get_contents('php://input'), 1);
			
			$current_market_status = $this->model_extension_module_yandex_beru->getOrderMarketStatusByMarketOrderId($status_info['order']['id']);
			
			if (!empty($current_market_status['status']) && in_array($current_market_status['status'],['PICKUP','DELIVERY'])) {
				$cencellation_substatuses = $this->getInfo()->getUserCencellationSubstatuses();

				if(!empty($status_info['order']['substatus']) && in_array($status_info['order']['substatus'], $cencellation_substatuses)) {
					$shop_order_id = $this->model_extension_module_yandex_beru->getShopOrderId($status_info['order']['id']);

					$this->model_extension_module_yandex_beru->createOrderCancellation($shop_order_id, $status_info['order']['substatus'], 'DBS', $status_info['order']['id']);
				}
			}
		} else {
			header('HTTP/1.1 403 Forbidden');
		}
	}
	
	private function getInfo() {

		static $instance;

		if (!$instance) {
			$instance = $this->api->loadComponent('info');
		}

		return $instance;
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