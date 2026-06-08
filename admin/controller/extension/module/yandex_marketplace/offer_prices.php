<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOfferPrices extends Controller {
	private $error ='';

	public function index() {

		$this->load->model('extension/module/yandex_beru');
		$this->load->language('extension/module/yandex_marketplace');

		$data = array();

		if(!empty($this->session->data['success_update_price'])){

			$data['success_message'] = $this->session->data['success_update_price'];

			unset($this->session->data['success_update_price']);

		} elseif(!empty($this->session->data['error_update_price'])) {

			$data['error_message'] .= $this->session->data['error_update_price'];
			
			unset($this->session->data['error_update_price']);
		}

		if (!empty($this->request->post)) {//обрабатываем данные отправленные с формы

			$this->api = new yandex_beru();
		
			$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			$component = $this->api->loadComponent('offerPricesUpdates');

			$push_data = array();


			foreach ($this->request->post['suggestion_keys'] as $offer) {

				if(!empty($offer['check'])){

					if($this->config->get('config_currency') == "RUB"){

						$currency = "RUR";
	
					} else {
	
						$currency = $this->config->get('config_currency');
	
					}
	
					$push_data['offers'][] = array(
						'marketSku' => $offer['marketSkuName'],
						'price'		=> array(
							"currencyId"	=> $currency,
							"value"			=> $offer['price']
						),
	
					);

				}

			}

			$component->setData($push_data);
			
			$response = $this->api->sendData($component); 

			if(is_array($response)){//верные данные всегда массив, ошибки строка.

				$data['success_message'] = "Все цены успешно обновлены";
				$this->model_extension_module_yandex_beru->logPrice($push_data['offers']);
				foreach ($push_data['offers'] as $offer) {
					if($this->config->get('yandex_beru_offer_prices_type') == 'msku'){
						$this->model_extension_module_yandex_beru->updatePriceByMSKU($offer['price']['value'], $offer['marketSku']);
					} else {
						$this->model_extension_module_yandex_beru->updatePriceByShopSku($offer['price']['value'], $offer['shopSku']);
					}
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

		
		$this->load->language('extension/module/yandex_marketplace');
		$this->document->setTitle($this->language->get('heading_title_prices'));
		$this->load->model('extension/module/yandex_beru');
		$this->getList($data);
	}

	protected function getList($data) {
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$this->load->language('extension/module/yandex_marketplace');

		$data['user_token'] = $this->session->data['user_token'];

		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		$data['log'] = $this->url->link('extension/module/yandex_marketplace/offer_prices/historyPriceChanges', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);


		$data['updateRecomendPrice'] = $this->url->link('extension/module/yandex_marketplace/offer_prices/updateRecomendPrice', 'user_token=' . $this->session->data['user_token'], true);
		
		$this->api = new yandex_beru();
		
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		$component_recomendPrice = $this->api->loadComponent('offerPricesSuggestions');


		//фильтр и пагинация

		$url = "";
		
		if (isset($this->request->get['filter_marketSkuName'])) {
			$url .= '&filter_marketSkuName=' . $this->request->get['filter_marketSkuName'];
		} 
		
		if (isset($this->request->get['filter_shopSku'])) {
			$url .= '&filter_shopSku=' . $this->request->get['filter_shopSku'];
		} 

		if (isset($this->request->get['filter_price_from'])) {
			$url .= '&filter_price_from=' . $this->request->get['filter_price_from'];
		} 

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		} 

		if (isset($this->request->get['filter_price_zero'])) {
			$url .= '&filter_price_zero=' . $this->request->get['filter_price_zero'];
		} 
	
		if (isset($this->request->get['page'])) {
			$url .='&page=' .  $this->request->get['page'];
		}
		
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
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
		
		if (isset($this->request->get['filter_price_to'])) {
			$filter_price_to = $this->request->get['filter_price_to'];
		} else {
			$filter_price_to = NULL;
		}

		if (isset($this->request->get['filter_price_from'])) {
			$filter_price_from = $this->request->get['filter_price_from'];
		} else {
			$filter_price_from = NULL;
		}

		if (isset($this->request->get['filter_price_zero'])) {

			$filter_price_zero = $this->request->get['filter_price_zero'];

			$filter_price_from = 0;
			$filter_price_to = 0;

		} else {

			$filter_price_zero = '';

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
		
		$filter_data = [
			'filter_loaded' 		=> true,
			'filter_marketSkuName' 	=> $filter_marketSkuName, 
			'filter_shopSku' 		=> $filter_shopSku,
			'filter_price_to'		=> $filter_price_to,
			'filter_price_from'		=> $filter_price_from,
			'page'					=> $page,
			'start' 				=> ($page - 1) * $limit,
			'limit' 				=> $limit
		];


		$data['filter_marketSkuName'] = $filter_marketSkuName; 
		$data['filter_shopSku'] = $filter_shopSku;
		$data['filter_price_to'] = $filter_price_to;
		$data['filter_price_from'] = $filter_price_from;
		$data['filter_price_zero'] = $filter_price_zero;
	
		
		$offers_total = $this->model_extension_module_yandex_beru->getTotalOffers($filter_data);

		$pagination = new Pagination();
		$pagination->total = $offers_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/module/yandex_marketplace/offer_status', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($offers_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($offers_total - $limit)) ? $offers_total : ((($page - 1) * $limit) + $limit), $offers_total, ceil($offers_total / $limit));

		//фильтр и пагинация

		//Получаем товары загруженные на беру

		if($this->config->get('yandex_beru_offer_prices_type') == 'msku'){
			$data['updateOfferPrice'] = $this->url->link('extension/module/yandex_marketplace/offer_prices/updateOfferPrice', 'user_token=' . $this->session->data['user_token'] . '&type=MSKU', true);
			$results = $this->model_extension_module_yandex_beru->getProductCards($filter_data);

			if(!empty($results)){

				foreach ($results as $result) {

					$product_card = $this->model_extension_module_yandex_beru->getProductCard($result['yandex_sku']);

					$post_data['offers'][] = ["marketSku" => $product_card['yandex_sku']];
		
				}

				$get_data = array();


					foreach($results as $result){

						$product_card = $this->model_extension_module_yandex_beru->getProductOfferCart($result['yandex_sku']);

				
						$check_products_data = array(
							'name'       => $product_card['marketSkuName'],
							'marketSkuName'      	=> $product_card['yandex_sku'],
							'price'      			=> $product_card['offer_price'],
							'minPriceOnBeru'   		=> $this->currency->format($product_card['minPriceOnBeru'], $this->config->get('config_currency')),
							'maxPriceOnBeru'  		=> $this->currency->format($product_card['maxPriceOnBeru'], $this->config->get('config_currency')),
							'defaultPriceOnBeru'    => $this->currency->format($product_card['defaultPriceOnBeru'], $this->config->get('config_currency')),
							'byboxPriceOnBeru'   	=> $this->currency->format($product_card['byboxPriceOnBeru'], $this->config->get('config_currency')),
							'outlierPrice'   		=> $this->currency->format($product_card['outlierPrice'], $this->config->get('config_currency')),
						);
						
						$data['check_products'][] = $check_products_data;
		
					}
			}
		} else {
			$data['updateOfferPrice'] = $this->url->link('extension/module/yandex_marketplace/offer_prices/updateOfferPrice', 'user_token=' . $this->session->data['user_token'] . '&type=shopSku', true);
			$results = $this->model_extension_module_yandex_beru->getOffers($filter_data, 'yandex_sku');

			if(!empty($results)){

			$filter_column = array('image', 'yandex_sku', 'yandex_category', 'status', 'marketSkuName', 'offer_price', 'product_id', 'minPriceOnBeru', 'maxPriceOnBeru', 'defaultPriceOnBeru', 'byboxPriceOnBeru','outlierPrice');

			foreach ($results as $result) {

				$offer = $this->model_extension_module_yandex_beru->getOffer($result['shopSku']);

				$post_data['offers'][] = ["marketSku" => $offer['yandex_sku']];

			}

			$get_data = array();

			foreach($results as $result){

				$offer = $this->model_extension_module_yandex_beru->getFullOfferInfo($result['shopSku'],$filter_column);

				if (is_file(DIR_IMAGE . $offer['image'])) {
					$image = $this->model_tool_image->resize($offer['image'], 40, 40);
				} else {
					$image = $this->model_tool_image->resize('no_image.png', 40, 40);
				}


				$check_products_data = array(
					'image'      			=> $image,
					'name'      	 		=> $offer['marketSkuName'],
					'sku'     			 	=> $result['shopSku'],
					'marketSkuName'      	=> $offer['yandex_sku'],
					'key'      				=> $offer['yandex_sku'],
					'price'      			=> $offer['offer_price'],
					'minPriceOnBeru'   		=> $this->currency->format($offer['minPriceOnBeru'], $this->config->get('config_currency')),
					'maxPriceOnBeru'  		=> $this->currency->format($offer['maxPriceOnBeru'], $this->config->get('config_currency')),
					'defaultPriceOnBeru'    => $this->currency->format($offer['defaultPriceOnBeru'], $this->config->get('config_currency')),
					'byboxPriceOnBeru'   	=> $this->currency->format($offer['byboxPriceOnBeru'], $this->config->get('config_currency')),
					'outlierPrice'   		=> $this->currency->format($offer['outlierPrice'], $this->config->get('config_currency')),
				);
				
				$data['check_products'][] = $check_products_data;
			}

			}


		}

		$data['error_message'] = $this->error;
	
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		

		if($this->config->get('yandex_beru_offer_prices_type') == 'msku'){
			
			$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/offer_prices_msku', $data));

		} else {

			$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/offer_prices_shop_sku', $data));

		}

	}

	private function getValidatedSku($sku){
		return preg_replace('/[^a-zA-ZА-Яа-я0-9\,\.\/\(\)\[\]\-\=]/', '-',$sku);
	}

	private function getCustomerPrice($get_data, $array_data = array()){//получаем все цены, если несколько страниц собираем в один массив

		$this->api = new yandex_beru();
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		$component_price = $this->api->loadComponent('offerPrices');
		$component_price->setData($get_data);

		$data = $this->api->sendData($component_price); //текущая пользовтельская цена на товар



		if(is_array($data)){//верные данные всегда массив, ошибки строка.
			
			$array_data = array();

			if(!empty($data['result']['offers'][0])){

				foreach ($data['result']['offers'] as $offer) {

					$array_data['result']['offers'][] = $offer;
		
				}

			}
			
			if(!empty($data['result']['paging']['nextPageToken']) && !empty($data['result']['offers'][0])){

				$get_data['page_token'] = $data['result']['paging']['nextPageToken'];

				$intermediate_result = $this->getCustomerPrice($get_data, $array_data);

				return $intermediate_result;


			} 

			return $array_data;
		
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

			$this->error = str_replace($code_error, $text_code_error, $data);

			return false;

		}
	}

	public function updateRecomendPrice(){

		$this->load->model('extension/module/yandex_beru');
		$this->load->language('extension/module/yandex_marketplace');
		$this->api = new yandex_beru();

		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		$component_recomendPrice = $this->api->loadComponent('offerPricesSuggestions');

		$filter_data = [
			'filter_loaded' => true
		];

		$results_all = $this->model_extension_module_yandex_beru->getOffers($filter_data);


		$results_1000 =  array_chunk($results_all,1000);

		foreach ($results_1000 as $results) {
			if(!empty($results)){

				foreach ($results as $result) {
	
					$offer = $this->model_extension_module_yandex_beru->getOffer($result['shopSku']);
	
					$post_data['offers'][] = ["marketSku" => $offer['yandex_sku']];
	
				}
	
				$get_data = array();
	
				$component_recomendPrice->setData($post_data);
				
				$recomendPrice = $this->api->sendData($component_recomendPrice); //список цен для товаров прошедших модерацию

				if(is_array($recomendPrice)){//верные данные всегда массив, ошибки строка.
			
					foreach ($recomendPrice['result']['offers'] as $offer) {
	
						$this->model_extension_module_yandex_beru->updateRecomendPrice($offer['marketSku'], $offer['priceSuggestion']);	
				
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
		}

		$this->session->data['success_update_price'] = $this->language->get('text_success_update_price');
	
		$this->response->redirect($this->url->link('extension/module/yandex_marketplace/offer_prices', 'user_token=' . $this->session->data['user_token'], true));
		
	}

	public function historyPriceChanges(){

		$this->load->model('extension/module/yandex_beru');
		$this->load->language('extension/module/yandex_marketplace');

		$data['user_token'] = $this->session->data['user_token'];
		$data['cancel'] = $this->url->link('extension/module/yandex_marketplace/offer_prices', 'user_token=' . $this->session->data['user_token'], true);

		$url = "";
		
		if (isset($this->request->get['filter_date'])) {
			$url .= '&filter_date=' . $this->request->get['filter_date'];
		} 

		if (isset($this->request->get['page'])) {
			$url .='&page=' .  $this->request->get['page'];
		}
		
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		if (isset($this->request->get['filter_date_from'])) {
			$filter_date_from = $this->request->get['filter_date_from'];
		} else {
			$filter_date_from = NULL;
		}

		if (isset($this->request->get['filter_date_to'])) {
			$filter_date_to = $this->request->get['filter_date_to'];
		} else {
			$filter_date_to = NULL;
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

		$filter_data = [
			'filter_date_form'		=> $filter_date_from,
			'filter_date_to' 		=> $filter_date_to, 
			'page'					=> $page,
			'start' 				=> ($page - 1) * $limit,
			'limit' 				=> $limit
		];

		$data['filter_date_from'] 	= $filter_date_from;
		$data['filter_date_to'] 	= $filter_date_to;


		$prices_total = $this->model_extension_module_yandex_beru->getTotalHistoryPrice($filter_data);

		$pagination = new Pagination();
		$pagination->total = $prices_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/module/yandex_marketplace/offer_prices/historyPriceChanges/', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($prices_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($prices_total - $limit)) ? $prices_total : ((($page - 1) * $limit) + $limit), $prices_total, ceil($prices_total / $limit));

		$results = $this->model_extension_module_yandex_beru->getHistoryPrice($filter_data);

		foreach ($results as $result) {

			if(!empty($result['username'])){

				$username = $result['username'];

			} else {

				$username =  $this->language->get('text_delete_user');

			}

			if(!empty($result['marketSkuName'])){ //если связь товар/оффер удалена, выводим название на момент изменения 

				$offer_name = $result['marketSkuName'];

			} else {

				$offer_name = $result['offer_name'];

			}
			
			$data['logs'][] = array(
				'offer_id'      	=> $result['offer_id'],
				'offer_name_old'    => $result['offer_name'],
				'offer_name'    	=> $offer_name,
				'price'   			=> $result['price'],
				'username'   		=> $username,
				'date_update'		=> $result['date_update'],
			);

		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/log_price', $data));

	}

	public function updateOfferPrice(){
		$this->load->model('extension/module/yandex_beru');
		$this->load->language('extension/module/yandex_marketplace');

		$get_data = array();

		$offers = $this->getCustomerPrice($get_data);

		foreach ($offers as $offer) {

			if($this->request->get['type'] == "MSKU"){
				$this->model_extension_module_yandex_beru->updatePriceByMSKU($offer['price']['value'], $offer['marketSku']);
			} else {
				$this->model_extension_module_yandex_beru->updatePriceByShopSku($offer['price']['value'], $offer['shopSku']);
			}

		}

		$this->response->redirect($this->url->link('extension/module/yandex_marketplace/offer_prices', 'user_token=' . $this->session->data['user_token'], true));

	}


}