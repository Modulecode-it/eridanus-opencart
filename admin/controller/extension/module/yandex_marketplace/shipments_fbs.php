<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceShipmentsFBS extends Controller {
	private $error = array();
	
	public function index() {
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->api = new yandex_beru();
		
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		
		$component = $this->api->loadComponent('info');
		
		$shipment_statuses = $component->getShipmentStatuses();
		
		foreach($shipment_statuses as $shipment_status){
			$data['shipment_statuses'][$shipment_status] = $this->language->get('shipment_status_'.$shipment_status);
		}
		
		$data['user_token'] = $this->session->data['user_token'];
		
		if (isset($this->request->get['dateFrom'])) {
			$data['dateFrom'] = $this->request->get['dateFrom'];
		} else {
			$data['dateFrom'] = date("d-m-Y");
		}
		
		if (isset($this->request->get['dateTo'])) {
			$data['dateTo'] = $this->request->get['dateTo'];
		} else {
			$data['dateTo'] = date("d-m-Y");
		}
		
		$put_data = [
			'dateFrom' => $data['dateFrom'],
			'dateTo'   => $data['dateTo'],
		];
		
		$data['filter_statuses'] = [];
		
		if (isset($this->request->get['filter_statuses'])) {
			foreach($this->request->get['filter_statuses'] as $filter_status){
				if(array_key_exists($filter_status, $data['shipment_statuses'])){
					$data['filter_statuses'][] = $filter_status;
					$put_data['statuses'][] = $filter_status;
				}
			}
		}
		
		if (isset($this->request->get['filter_order_id'])) {
			$data['filter_order_id'] = (int)$this->request->get['filter_order_id'];
			$put_data['orderIds'][] = (int)$this->request->get['filter_order_id'];
		} else {
			$data['orderId'] = '';
		}
	
		$component = $this->api->loadComponent('firstMileShipments');
		
		$component->setData($put_data);
		
		$response = $this->api->sendData($component);
	
		if(is_array($response)){
			if($response['status'] == "OK"){
				foreach($response['result']['shipments'] as $shipment){
					$shipment_type = '';
					
					$data['shipments'][] = [
						'id'           => $shipment['id'],
						'date'         => date_format(date_create($shipment['planIntervalFrom']), 'Y-m-d'),
						'draftCount'   => isset($shipment['draftCount'])?$shipment['draftCount']:0,
						'type'         => $this->language->get('shipment_type_'.$shipment['shipmentType']),
						'status'       => $this->language->get('shipment_status_'.$shipment['status'])
					];
				}
			}else{
				$this->error['warning'] = "Нет ответа от API Яндекса";
			}
			
		} else {
			$code_error = array("BAD_REQUEST", "INTERNAL_ERROR");
			
			$text_code_error = array(
				$this->language->get('error_BAD_REQUEST'),
				$this->language->get('error_INTERNAL_ERROR')
			);
	
			$this->error['warning'] = str_replace($code_error, $text_code_error, $response);
		
		}
		
		$data['heading_title_shipments'] = 'Отгрузки';
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/shipments_FBS', $data));	
	}
	
	public function shipmentModal(){
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->load->model('extension/module/yandex_beru');
		
		$json = [];
		
		$data['user_token'] = $this->session->data['user_token'];
		
		if(!empty($this->request->get['shipment_id'])){
			
			if(($this->request->server['REQUEST_METHOD'] == 'POST') && !empty($this->request->post['selected_orders'])) {
				$this->submitShipment($this->request->get['shipment_id'], $this->request->post);
				
				if(empty($this->error)){
					$data['success'] = $this->language->get('success_shipment_confirm');
				}
			}
			
			$orderIdsWithLabels = $this->getOrderIdsWithLabels($this->request->get['shipment_id']);
//			todo убрать
//			$orderIdsWithLabels[] = 62626029;
			$json['test2'] = $orderIdsWithLabels;
			
			if(!empty($this->request->post['selected_orders'])){
				$data['selected_orders'] = $this->request->post['selected_orders'];
			}else{
				$data['selected_orders'] = [];
			}
			
			$this->api = new yandex_beru();
			
			$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			
			$component = $this->api->loadComponent('firstMileShipment');

			$component->setShipmentId($this->request->get['shipment_id']);

			$response = $this->api->sendData($component);
			
			if(is_array($response)){
				if($response['status'] == "OK"){
					$data['act_link'] = $this->url->link('extension/module/yandex_marketplace/shipments_fbs/act', 'user_token=' . $this->session->data['user_token'] .'&shipment_id=' . $this->request->get['shipment_id'] , true);
					
					$shipping_data  = $response['result'];
					
					$data['shipment_id'] = $this->request->get['shipment_id'];
					
					$data['planIntervalFrom'] = $shipping_data['planIntervalFrom'];
					
					$data['planIntervalTo'] = $shipping_data['planIntervalTo'];
					
					$data['shipmentType'] = $this->language->get('shipment_type_'.$shipping_data['shipmentType']);
					
					$data['warehouse'] = $shipping_data['warehouse'];
					
					$data['warehouseTo'] = $shipping_data['warehouseTo'];
					
					$data['deliveryService'] = $shipping_data['deliveryService'];
					
					$data['currentStatus'] = $this->language->get('shipment_status_'. $shipping_data['currentStatus']['status']);
					
					$data['availableActions'] = $shipping_data['availableActions'];
					
					$order_ids = $shipping_data['orderIds'];
//					todo убрать
//					$order_ids = [62626029,62552629,62533414,62530744];
					
//					$data['availableActions'] = ['CONFIRM','DOWNLOAD_ACT'];
					
					foreach($order_ids as $market_order_id){
						$order_info = $this->model_extension_module_yandex_beru->getOrderByMarketId($market_order_id);
						
						if ($order_info) {
							$data['orders'][] = array(
								'order_id'        => $order_info['order_id'],
								'customer'        => $order_info['customer'],
								'market_order_id' => $order_info['market_order_id'],
								'labels'          => in_array($order_info['market_order_id'], $orderIdsWithLabels),
								'label_link'      => $this->url->link('sale/order/printLabels', 'user_token=' . $this->session->data['user_token'] . '&market_order_id=' . (int)$order_info['market_order_id'], true),
								'order_status'    => $order_info['order_status'] ? $order_info['order_status'] : $this->language->get('text_missing'),
								'total'           => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']),
								'date_added'      => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
								'date_modified'   => date($this->language->get('date_format_short'), strtotime($order_info['date_modified'])),
								'shipping_code'   => $order_info['shipping_code'],
								'view'            => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_info['order_id'], true),
								'edit'            => $this->url->link('sale/order/edit', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_info['order_id'], true)
							);
						}
					}
//					todo убрать
//					$json['test'] = $data['orders'];
					
					if (isset($this->error['warning'])) {
						$data['error_warning'] = $this->error['warning'];
					} else {
						$data['error_warning'] = '';
					}
					
					$json['success'] = $this->load->view('extension/module/yandex_marketplace/shipment_modal_FBS', $data);	
				}
			}else{
				$code_error = array("NOT_FOUND", "BAD_REQUEST", "INTERNAL_ERROR");

				$text_code_error = array(
					$this->language->get('error_NOT_FOUND'),
					$this->language->get('error_BAD_REQUEST'),
					$this->language->get('error_INTERNAL_ERROR')
				);

				$json['error'] = str_replace($code_error, $text_code_error, $response);

			}
		}else{
			$json['error'] = "Не указан shipment_id";
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
		
	}
	
	public function act(){
		$this->load->language('extension/module/yandex_marketplace');
		if(!empty($this->request->get['shipment_id'])){
			$this->api = new yandex_beru();
			$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));

			$component = $this->api->loadComponent('firstMileShipmentAct');
			
			$component->setShipmentId($this->request->get['shipment_id']);

			$response = $this->api->sendData($component);
			
			if(is_array($response)){
				print_r($response['pdf']);
			} else {
				
				$code_error = array("BAD_REQUEST", "INTERNAL_ERROR");
				$text_code_error = array(
					$this->language->get('error_BAD_REQUEST'),
					$this->language->get('error_INTERNAL_ERROR'),
				);

				$data['error_message'] = str_replace($code_error, $text_code_error, $response);

				$data['header'] = $this->load->controller('common/header');
				$data['column_left'] = $this->load->controller('common/column_left');
				$data['footer'] = $this->load->controller('common/footer');

				$this->response->setOutput($this->load->view('sale/error_pdf', $data));
			}
		}
	}
	
	public function itemList(){
		$data['title'] = 'Лист комплектации';
		$data['text_invoice'] = 'Лист комплектации заказов отгрузки';
		
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->load->model('extension/module/yandex_beru');
		
		$data['user_token'] = $this->session->data['user_token'];
		
		if(!empty($this->request->get['shipment_id'])){
			$data['shipment_id'] = $this->request->get['shipment_id'];
			
			$this->api = new yandex_beru();
			
			$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			
			$component = $this->api->loadComponent('firstMileShipment');

			$component->setShipmentId($this->request->get['shipment_id']);

			$response = $this->api->sendData($component);
			
			if(is_array($response)){
				if($response['status'] == "OK"){
					$shipping_data  = $response['result'];

					if(!empty($this->request->get['selected_orders'])){
						$selected_orders = $this->request->get['selected_orders'];
						
						$order_ids = array_intersect($shipping_data['orderIds'], $this->request->get['selected_orders']); 
					}else{
						$order_ids = $shipping_data['orderIds'];
					}
					
					$data['shipment_date'] = date_format(date_create($shipping_data['planIntervalFrom']), 'Y-m-d');
					
					foreach($order_ids as $market_order_id){

						$order_component = $this->api->loadComponent('order');
			
						$order_component->setOrder($market_order_id);

						$order_response = $this->api->sendData($order_component);
						
						$order_info = $this->model_extension_module_yandex_beru->getOrderByMarketId($market_order_id);
						
						foreach ($order_response['order']['items'] as $key => $product) {
							$data['items'][] = array(
								'shop_order_id'    => (empty($order_info['order_id']) || $key>0 )?'-':$order_info['order_id'],
								'market_order_id'  => ( $key>0 )?'-':$market_order_id,
								'offerId'          => $product['offerId'],
								'name'    	 	   => $product['offerName'],
								'count'		       => $product['count'],
							);
						}
					}
				}
			}else{
				$code_error = array("NOT_FOUND", "BAD_REQUEST", "INTERNAL_ERROR");

				$text_code_error = array(
					$this->language->get('error_NOT_FOUND'),
					$this->language->get('error_BAD_REQUEST'),
					$this->language->get('error_INTERNAL_ERROR')
				);

				$data['error'] = str_replace($code_error, $text_code_error, $response);

			}
		}else{
			$data['error'] = "Не указаны заказы и номер отгрузки";
		}
		
		
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/shipment_item_list', $data));
	}
	
	private function submitShipment($shimpent_id, $data){
		if(!empty($data)){
			$this->api = new yandex_beru();
			
			$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			
			$component = $this->api->loadComponent('firstMileShipmentsConfirm');

			$component->setShipmentId($this->request->get['shipment_id']);
			
			$post_data['externalShipmentId'] = $this->request->get['shipment_id'];
			
			if(!empty($data['selected_orders'])){
				foreach($data['selected_orders'] as $selected_order_market_id){
					$post_data['orderIds'][] = $selected_order_market_id;
				}
			}
			
			$component->setData($post_data);
			   
			$response = $this->api->sendData($component);
			
			if(!is_array($response)){
				
				$code_error = array("BAD_REQUEST","NOT_FOUND","ALREADY_CONFIRMED","CUTOFF_NOT_REACHED","NO_ORDERS","INTERNAL_ERROR");

				$text_code_error = array(
					$this->language->get('error_BAD_REQUEST'),
					$this->language->get('error_shipment_confirm_NOT_FOUND'),
					$this->language->get('error_shipment_confirm_ALREADY_CONFIRMED'),
					$this->language->get('error_shipment_confirm_CUTOFF_NOT_REACHED'),
					$this->language->get('error_shipment_confirm_NO_ORDERS'),
					$this->language->get('error_shipment_confirm_INTERNAL_ERROR'),
				);

				$this->error['warning'] = str_replace($code_error, $text_code_error, $response);
				
			}
		}else{
			$this->error['warning'] = $this->language->get('error_shipment_confirm_NO_ORDERS');
		}
	}
	
	private function getOrderIdsWithLabels($shipment_id){
		$this->api = new yandex_beru();
			
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			
		$component = $this->api->loadComponent('firstMileShipmentsOrdersInfo');

		$component->setShipmentId($shipment_id);

		$response = $this->api->sendData($component);
			
		if(is_array($response)){
			$orderIds = $response['result']['orderIdsWithLabels'];
		} else {
			$orderIds = [];
		}
				
		return $orderIds;
	}
}