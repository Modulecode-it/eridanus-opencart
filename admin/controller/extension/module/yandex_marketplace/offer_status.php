<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOfferStatus extends Controller {
	private $error = '';

	public function index() {
		$this->api = new yandex_beru();
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		
		$this->load->language('extension/module/yandex_marketplace');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/yandex_beru');
		
		$this->document->addStyle('view/stylesheet/yandex_beru.css');

		$this->getList();
	}

	protected function getList() {
		
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		
		$url = "";
		
		if (isset($this->request->get['filter_marketSkuName'])) {
			$url .= '&filter_marketSkuName=' . $this->request->get['filter_marketSkuName'];
		} 
		
		if (isset($this->request->get['filter_shopSku'])) {
			$url .= '&filter_shopSku=' . $this->request->get['filter_shopSku'];
		} 
		
		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		} 
	
		if (isset($this->request->get['page'])) {
			$url .='&page=' .  $this->request->get['page'];
		}
		
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}
		
		
		if(!empty($this->request->post['shopSkus'])){

			$get_data['shop_sku']= array();
			
			foreach($this->request->post['shopSkus'] as $shopSku){
				if($shopSku){	
					$get_data['shop_sku'][] = $shopSku;	
				}
			}
			$component = $this->api->loadComponent('offerMappingEntries');
		
			$component->setData($get_data);
			$response = $this->api->sendData($component);

			if(is_array($response)){//верные данные всегда массив, ошибки строка.

				foreach($response['result']['offerMappingEntries'] as $offer){
					$this->model_extension_module_yandex_beru->updateOfferStatus($offer['offer']['shopSku'], $offer['offer']['processingState']['status']);	
				}

			
			} else {
	
				$code_error = array("PARTIAL_CONTENT", "BAD_REQUEST", "UNAUTHORIZED", "FORBIDDEN", "NOT_FOUND", "METHOD_NOT_ALLOWED", "UNSUPPORTED_MEDIA_TYPE", "ENHANCE_YOUR_CALM", "INTERNAL_SERVER_ERROR","SERVICE_UNAVAILABLE", "UNKNOWN_ERROR");
				$text_code_error = array(
					$this->language->get('error_PARTIAL_CONTENT'),
					$this->language->get('error_BAD_REQUEST'),
					$this->language->get('error_UNAUTHORIZED'),
					$this->language->get('error_FORBIDDEN'),
					$this->language->get('error_NOT_FOUND'),
					$this->language->get('error_METHOD_NOT_ALLOWED'),
					$this->language->get('error_UNSUPPORTED_MEDIA_TYPE'),
					$this->language->get('error_ENHANCE_YOUR_CALM'),
					$this->language->get('error_INTERNAL_SERVER_ERROR'),
					$this->language->get('error_SERVICE_UNAVAILABLE'),
					$this->language->get('error_UNKNOWN_ERROR'),
				);
	
				$this->error = str_replace($code_error, $text_code_error, $response);
		
			}
			

		}
		
		if (isset($this->request->get['filter_marketSkuName'])) {
			$filter_marketSkuName = $this->request->get['filter_marketSkuName'];
		} else {
			$filter_marketSkuName = '';
		}
		
		if (isset($this->request->get['filter_shopSku'])) {
			$filter_shopSku = $this->request->get['filter_shopSku'];
		} else {
			$filter_shopSku = '';
		}
		
		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}
	
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		if (isset($this->request->get['limit'])) {
			$limit = $this->request->get['limit'];
		} else {
			$limit = $this->config->get('config_limit_admin');
		}
		
		
		//Получаем товары загруженные на беру
		$filter_data = [
			'filter_loaded' => true,
			'filter_marketSkuName' => $filter_marketSkuName, 
			'filter_shopSku' => $filter_shopSku,
			'filter_status'	=>	$filter_status,
			'page'	=> $page,
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];
		
		$filter_offer = ['product_id','image','shopSku','name','model','yandex_sku','marketSkuName','marketCategoryName','beru_status'];
		
		$offers_total = $this->model_extension_module_yandex_beru->getTotalOffers($filter_data);
		
		$results = $this->model_extension_module_yandex_beru->getOffers($filter_data);

		foreach($results as $result){
			$offer_info = $this->model_extension_module_yandex_beru->getFullOfferInfo($result['shopSku'],$filter_offer);
			
			$offer_errors = $this->model_extension_module_yandex_beru->findOfferErrors($result['shopSku'], 'shopSku');
			
			if (is_file(DIR_IMAGE . $offer_info['image'])) {
				$image = $this->model_tool_image->resize($offer_info['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}
			
			$checked_products_data = array(
				'product_id' => $offer_info['product_id'],
				'image' => $image,
				'name' => $offer_info['name'],
				'model' => $offer_info['model'],
				'shopSku' =>$result['shopSku'],
				'beru_status' =>  $this->language->get('status_'.$offer_info['beru_status']),
				'yandex_sku' =>$offer_info['yandex_sku'],
				'marketSkuName' =>$offer_info['marketSkuName'],
				'beru_link' => 'https://pokupki.market.yandex.ru/product/'.$offer_info['yandex_sku'],
				'marketCategoryName' => $offer_info['marketCategoryName'],
				'edit' => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $offer_info['product_id'] , true)
			);
			$data['checked_products'][] = $checked_products_data;
		}
		
		$data['action_status_refresh'] =  $this->url->link('extension/module/yandex_marketplace/offer_status', 'user_token=' . $this->session->data['user_token'] . $url, true);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['filter_marketSkuName'] = $filter_marketSkuName; 
		$data['filter_shopSku'] = $filter_shopSku;
		$data['filter_status'] = $filter_status;
		
		$pagination = new Pagination();
		$pagination->total = $offers_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/module/yandex_marketplace/offer_status', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($offers_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($offers_total - $limit)) ? $offers_total : ((($page - 1) * $limit) + $limit), $offers_total, ceil($offers_total / $limit));
		
		
		$component = $this->api->loadComponent('info');
		
		$publicationStatuses = $component->getPublicationStatuses();
		
		$data['publicationStatuses'][] = [
			'value' => '',
			'text' => $this->language->get('text_select')
		];
		
		foreach($publicationStatuses as $publicationStatus){
			$data['publicationStatuses'][] = [
				'value' =>$publicationStatus,
				'text' => $this->language->get('text_'.$publicationStatus),
			];
		}

		if (isset($this->error)) {
			$data['error_warning'] = $this->error;
		} else {
			$data['error_warning'] = '';
		}

		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/offer_status', $data));	
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/yandex_marketplace')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		return !$this->error;
	}
}