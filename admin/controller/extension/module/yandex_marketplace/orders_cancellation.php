<?php
require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOrdersCancellation extends Controller {
	private $error = '';
	private $api;

	public function index() {
		$this->load->language('sale/order');
		$this->load->language('extension/module/yandex_marketplace');
		$this->document->setTitle($this->language->get('heading_title_load'));
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->document->addStyle('view/stylesheet/yandex_beru.css');
		$this->getList();
	}
	
	public function getList(){
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_yandex_beru'),
			'href' => $this->url->link('extension/module/yandex_marketplace', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_orders_cancellation'),
			'href' => $this->url->link('extension/module/yandex_marketplace/orders_cancellation', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		if (isset($this->error)) {
			$data['error_warning'] = $this->error;
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		$data['heading_title'] = $this->language->get('heading_orders_cancellation');
		$data['user_token'] = $this->session->data['user_token'];
		
		$results = $this->model_extension_module_yandex_beru->getCancellationOrders();
	
		foreach ($results as $result) {
			$now_date = new DateTime($result['now_date']);
			$notify_date = new DateTime($result['notify_date']);
			$cancel_date = $notify_date->add(new DateInterval("PT48H"));
			$diff_date = $cancel_date->getTimestamp() - $now_date->getTimestamp();		
			$hours_diff = floor($diff_date / 60 / 60 );
			$min_diff = floor($diff_date/60)-($hours_diff*60);
			
			$data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'order_status'  => $result['order_status'] ? $result['order_status'] : $this->language->get('text_missing'),
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'time_to_cancel'=> $hours_diff . 'ч ' . $min_diff . 'м',
				'shipping_code' => $result['shipping_code'],
				'cancel_status' => $this->language->get('order_status_'.$result['cancel_status']),
				'view'          => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] , true),
				'edit'          => $this->url->link('sale/order/edit', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] , true)
			);
		}
		
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/orders_cancellation_list', $data));
	}
	
	public function reject(){
		$this->load->language('extension/module/yandex_marketplace');
		$this->load->model('extension/module/yandex_beru');
		$this->api = new yandex_beru();
		$json = [];
		
		if(!empty($this->request->post)) {
			
			$cancellation_order = $this->model_extension_module_yandex_beru->getCancellationOrder($this->request->post['order_id']);
			
			if($cancellation_order['order_type'] == 'FBS') {
				$this->api->setAuth($this->config->get('yandex_beru_oauth'), $this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			}else{
				$this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'), $this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
			}
			
			$component = $this->api->loadComponent('cancellation');
			
			if(!empty($cancellation_order)){
				//запрос на  отмену
				$component->setOrder($cancellation_order['market_order_id']);
				
				$put_data = [
				  "accepted" => false,
				  "reason"   => "ORDER_DELIVERED"
				];
				
				$component->setData($put_data);
				$response = $this->api->sendData($component);
				
				if(is_array($response)){//верные данные всегда массив, ошибки строка.

					$json['success_message'] = "Запрос успешно выполнен";
					
					$this->model_extension_module_yandex_beru->deleteCancellationOrder($this->request->post['order_id']);

				} else {

					$code_error = array("BAD_REQUEST", "FORBIDDEN", "NOT_FOUND");
					
					$text_code_error = array(
						$this->language->get('error_BAD_REQUEST'),
						$this->language->get('error_FORBIDDEN'),
						$this->language->get('error_CANCELLTION_NOT_FOUND'),
					);
					
					$json['error_message'] = str_replace($code_error, $text_code_error, $response);

				}
			}
			
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			
		}
	}
	
	public function accept(){
		$this->load->model('extension/module/yandex_beru');
		$this->api = new yandex_beru();
		$json = [];
		
		if(!empty($this->request->post)) {
			
			$cancellation_order = $this->model_extension_module_yandex_beru->getCancellationOrder($this->request->post['order_id']);
			
			if($cancellation_order['order_type'] == 'FBS') {
				$this->api->setAuth($this->config->get('yandex_beru_oauth'), $this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			}else{
				$this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'), $this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
			}
			
			$component = $this->api->loadComponent('cancellation');
			
			if(!empty($cancellation_order)){
				//запрос на  отмену
				$component->setOrder($cancellation_order['market_order_id']);
				
				$put_data = [
				  "accepted" => true,
				];
				
				$component->setData($put_data);
				$response = $this->api->sendData($component);
				
				if(is_array($response)){//верные данные всегда массив, ошибки строка.

					$json['success_message'] = "Запрос успешно выполнен";
					
					$this->model_extension_module_yandex_beru->deleteCancellationOrder($this->request->post['order_id']);

				} else {

					$code_error = array("BAD_REQUEST", "FORBIDDEN", "NOT_FOUND");
					
					$text_code_error = array(
						$this->language->get('error_BAD_REQUEST'),
						$this->language->get('error_FORBIDDEN'),
						$this->language->get('error_NOT_FOUND'),
					);
					
					$json['error_message'] = str_replace($code_error, $text_code_error, $response);

				}
			}
			
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			
		}
		
	}
}