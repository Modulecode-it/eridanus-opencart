<?php
require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceProductGroup extends Controller {
	private $error = [];
	private $api;

	public function index() {
		$this->load->language('extension/module/yandex_marketplace');
		$this->document->setTitle($this->language->get('heading_title_load'));
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->document->addStyle('view/stylesheet/yandex_beru.css');
		$this->getList();
	}

	public function add() {
		echo "<script>console.log('addshopSkus' );</script>";
		$this->load->language('extension/module/yandex_marketplace');

		$this->document->setTitle($this->language->get('heading_title_groups'));

		$this->load->model('extension/module/yandex_beru');

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
			$this->model_extension_module_yandex_beru->addProductGroup($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getForm();
	}

	public function edit() {
		echo "<script>console.log('editshopSkus' );</script>";
		$this->load->language('extension/module/yandex_marketplace');

		$this->document->setTitle($this->language->get('heading_title_groups'));

		$this->load->model('extension/module/yandex_beru');

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {	
			$this->model_extension_module_yandex_beru->editProductGroup($this->request->get['group_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getForm();
	}

	public function delete() {
		echo "<script>console.log('deleteshopSkus' );</script>";
		$this->load->language('extension/module/yandex_marketplace');

		$this->document->setTitle($this->language->get('heading_title_groups'));

		$this->load->model('extension/module/yandex_beru');

		if (isset($this->request->post['selected']) && $this->validateModify()) {
			foreach ($this->request->post['selected'] as $group_id) {
				$this->model_extension_module_yandex_beru->deleteProductGroup($group_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function dispatch() {
		echo "<script>console.log('dispatchshopSkus' );</script>";
		$this->load->language('extension/module/yandex_marketplace');

		$this->document->setTitle($this->language->get('heading_title_groups'));

		$this->load->model('extension/module/yandex_beru');

		if (isset($this->request->post['selected'])) {
			$url = '&groups=';
			foreach ($this->request->post['selected'] as $group_id) {
				$url .= $group_id . ',';
			}
			$url = substr($url,0,-1);
			
			$this->response->redirect($this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		echo "<script>console.log('getList' );</script>";
		
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
			'text' => $this->language->get('heading_title_load'),
			'href' => $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'], true)
		);

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['action_add'] = $this->url->link('extension/module/yandex_marketplace/product_group/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['action_delete'] = $this->url->link('extension/module/yandex_marketplace/product_group/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['action_dispatch'] = $this->url->link('extension/module/yandex_marketplace/product_group/dispatch', 'user_token=' . $this->session->data['user_token'] . $url, true);


		$data['product_groups'] = array();

		$filter_data = array(
			'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           => $this->config->get('config_limit_admin')
		);

		$group_total = $this->model_extension_module_yandex_beru->getTotalProductGroups();
		$results = $this->model_extension_module_yandex_beru->getProductGroups($filter_data);

		foreach ($results as $result) {
			$tmp = json_decode($result['filter_category']);
			if (!empty($tmp)) {
				$filter_category = implode('-', $tmp);
			}

			$tmp = json_decode($result['filter_option']);
			if (!empty($tmp)) {
				$filter_option = implode('-', $tmp);
			}
			unset($tmp);

			$url = '&group_id=' . $result['group_id'];
			$url .= !empty($result['filter_name']) ? '&filter_name=' . $result['filter_name'] : '';
			$url .= !empty($result['filter_model']) ? '&filter_model=' . $result['filter_model'] : '';
			$url .= isset($filter_category) ? '&filter_category=' . $filter_category : '';
			$url .= isset($filter_option) ? '&filter_option=' . $filter_option : '';
			$url .= isset($result['filter_price_from']) ? '&filter_price_from=' . $result['filter_price_from'] : '';
			$url .= isset($result['filter_price_to']) ? '&filter_price_to=' . $result['filter_price_to'] : '';
			$url .= isset($result['filter_quantity_from']) ? '&filter_quantity_from=' . $result['filter_quantity_from'] : '';
			$url .= isset($result['filter_quantity_to']) ? '&filter_quantity_to=' . $result['filter_quantity_to'] : '';
			$url .= isset($result['filter_status']) ? '&filter_status=' . $result['filter_status'] : '';

			$data['product_groups'][] = array(
				'group_id' 		=> $result['group_id'],
				'name'        	=> $result['name'],
				'edit'        	=> $this->url->link('extension/module/yandex_marketplace/product_group/edit', 'user_token=' . $this->session->data['user_token'] . $url, true)
			);
		}


		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$pagination = new Pagination();
		$pagination->total = $group_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($group_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($group_total - $this->config->get('config_limit_admin'))) ? $group_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $group_total, ceil($group_total / $this->config->get('config_limit_admin')));

		//if (isset($this->request->get['groups'])) {
		//	$url_groups = '&groups='.$this->request->get['groups'];
		//} else {
			$url_groups = "";
		//}

		$data['action'] 			= $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'] .$url_groups, true);
		$data['action_dispatch'] 	= $this->url->link('extension/module/yandex_marketplace/product_group/dispatch', 'user_token=' . $this->session->data['user_token'] . $url, true);
		if(!empty($this->request->post)){
			if(!empty($this->request->post['shopSkus'])){
				echo "<script>console.log('xxxxxx: ' );</script>";
				echo "<script>console.log('shopSku: " . $shopSku . "' );</script>";
				echo "<script>console.log('this: " . $this . "' );</script>";
				echo "<script>console.log('shopSkus11: " . $shopSkus . "' );</script>";
				echo "<script>console.log('shopSkus22: " . $this->request->post['shopSkus'] . "' );</script>";
				echo "<script>console.log('shopSkus33: " . json_encode($this->request->post['shopSkus']) . "' );</script>";
				$this->entries_updates($this->request->post['shopSkus']);
			}
		}
		
//		Список предложений
		if (isset($this->request->get['page_re'])) {
			$url_groups .= '&page_re=' .  $this->request->get['page_re'];
		}
		
		if (isset($this->request->get['limit_re'])) {
			$url_groups .= '&limit_re=' . $this->request->get['limit_re'];
		}
		
		if (isset($this->request->get['page_re'])) {
			$page_re = $this->request->get['page_re'];
		} else {
			$page_re = 1;
		}
		
		if (isset($this->request->get['limit_re'])) {
			$limit_re = $this->request->get['limit_re'];
		} else {
			$limit_re = $this->config->get('config_limit_admin');
		}
		
		$products_id = array();
		
		if(!empty($this->request->get['groups'])){

			$groups = explode(',',$this->request->get['groups']);	
			$products_id = $this->model_extension_module_yandex_beru->getProductsFromGroups($groups, $filter_data);
			
		}
		
		$filter_offer = ['product_id','image','shopSku','name','model','yandex_sku','marketSkuName','marketCategoryName','beru_status','key','category','vendor', 'ean', 'length', 'width', 'height', 'manufacturer'];
		
		$this->api = new yandex_beru();
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		$component = $this->api->loadComponent('offerMappingEntriesSuggestions');
		
		foreach($products_id as $product_id){
			$result = $this->model_catalog_product->getProduct($product_id);
			
			if(!empty($result)){
				$primary_options_combinations = $this->model_extension_module_yandex_beru->getPrimaryOptionsCombinations($product_id);
				
				foreach($primary_options_combinations as $primary_options_combination){
					$statuses = array();
					echo "<script>console.log('statuses: " . json_encode($statuses) . "' );</script>";
					$shopSku = $result['product_id'].$primary_options_combination;
					echo "<script>console.log('shopSku: " . $shopSku . "' );</script>";
					$offer_info = $this->model_extension_module_yandex_beru->getFullOfferInfo($shopSku, $filter_offer, 'key');
					
					
					
					
					echo "<script>console.log('offer_info: " . json_encode($offer_info) . "' );</script>";
					if(!$this->model_extension_module_yandex_beru->getOfferByKey($shopSku)){
						$this->model_extension_module_yandex_beru->addOffer(['key' =>$shopSku]);
					}
					//echo "<script>console.log('$offer_info['shopSku']: " . $offer_info['shopSku'] . "' );</script>";
					$post_data['offers'][0] = [
						//'shopSku'	=> $offer_info['shopSku'],
						'shopSku'	=> $offer_info['model'],
						'name'		=> $offer_info['name'],// . ' ' . $offer_info['model'] . ' ' . $offer_info['manufacturer'],
						'category'	=> $offer_info['category'],
						'vendor'	=> $offer_info['vendor'],
						//'vendorCode'	=> $offer_info['model'],
						'barcodes'	=> [$offer_info['ean']], //$offer_info['vendor'],
						//'marketSku'	=> $offer_info['model'],
					];
					//Детский рюкзак KAKOO Машинки - Мими AW0023-05 Animal World
					//offer_info: {"product_id":"15401","image":"catalog/Kakoo/AW0023-05.jpg","shopSku":"15401","name":"Детский рюкзак KAKOO Машинки - Мими","model":"AW0023-05","yandex_sku":"","marketSkuName":"","marketCategoryName":"","beru_status":"","key":"15401","category":"Рюкзаки AnimalWorld","vendor":"KAKOO"}
					//echo $post_data['offers'][0];
					echo "<script>console.log('post_data: " . $post_data['offers'][0] . "' );</script>";
					echo "<script>console.log('post_data: " . json_encode($post_data['offers'][0]) . "' );</script>";
					$offer_errors = $this->model_extension_module_yandex_beru->findOfferErrors($shopSku, 'key');

					$component->setData($post_data);
					echo "<script>console.log('component: " . $component . "' );</script>";
					echo "<script>console.log('component: " . json_encode($component) . "' );</script>";
					$response = $this->api->sendData($component);
					echo "<script>console.log('response: " . $response . "' );</script>";
					echo "<script>console.log('response: " . json_encode($response) . "' );</script>";
					//$offer_errors = $this->model_extension_module_yandex_beru->updateOfferShopSku($shopSku, $shopSku);
					//updateOfferShopSku					
					if(is_array($response)){
//						верные данные всегда массив, ошибки строка.
//						По вернувшимся предложениям обновляем таблицу					
						if(isset($response['result']['offers']['0'])){
							$response_offer = $response['result']['offers']['0'];
								
							$offer_info['shopSku'] = isset($response_offer['shopSku'])?$response_offer['shopSku']:'';
							$offer_info['yandex_sku'] = isset($response_offer['marketSku'])?$response_offer['marketSku']:'';
							$offer_info['marketSkuName'] = isset($response_offer['marketSkuName'])?$response_offer['marketSkuName']:'';
							$offer_info['marketCategoryName'] = isset($response_offer['marketCategoryName'])?$response_offer['marketCategoryName']:'';
$this->model_extension_module_yandex_beru->updateOffer($offer_info);							
//response: {"status":"OK","result":{"offers":[{"name":"Детский рюкзак KAKOO Машинки - Гоночный болид","shopSku":"AW0023-08","category":"Рюкзаки AnimalWorld","vendor":"","barcodes":["4627195953407"],"marketCategoryId":13858259,"marketCategoryName":"Рюкзаки и ранцы для школы","marketModelId":1458514709,"marketModelName":"Kakoo Детский рюкзак KAKOO Машинки - Гоночный болид AW0023-08 Animal World","marketSku":101474170308,"marketSkuName":"Детский рюкзак KAKOO Машинки - Гоночный болид AW0023-08 Animal World"}]}}

//update_data: {"marketSku":"","marketSkuName":"","marketCategoryName":"Рюкзаки и ранцы для школы","status":"IN_WORK","shopSku":"AW0023-05"}

							
						}
							
						foreach($this->api->error as $error){
							if(isset($error['error_response'])){
								$data['error_warning'] = $error['error_response'];
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
						
						$this->error['warning'] = str_replace($code_error, $text_code_error, $response);
					}
					
					
					if (is_file(DIR_IMAGE . $offer_info['image'])) {
						$image = $this->model_tool_image->resize($offer_info['image'], 40, 40);
					} else {
						$image = $this->model_tool_image->resize('no_image.png', 40, 40);
					}
					
					if($offer_info['yandex_sku']){
						$statuses[] = [
							'type' => 'success',
							'text' => $this->language->get('text_card_finded'),
						];
					}else{
						$statuses[] = [
							'type' => 'warning',
							'text' => $this->language->get('text_card_not_finded'),
						];
					}
					
					foreach($offer_errors as $offer_error){
						$statuses[] = [
							'type' => 'error',
							'text' => $this->language->get($offer_error),
						];
					}
					$check_products_data = array(
						'product_id' => $offer_info['product_id'],
						'image' => $image,
						'name' => $offer_info['name'],
						'model' => $offer_info['model'],
						'shopSku' => $offer_info['model'],//?$offer_info['shopSku']:$shopSku,
						'key' =>  $offer_info['key'],
						'statuses' => $statuses,
						'yandex_sku' =>$offer_info['yandex_sku'],
						'marketSkuName' =>$offer_info['marketSkuName'],
						'beru_link' => 'https://pokupki.market.yandex.ru/product/'.$offer_info['yandex_sku'],
						'marketCategoryName' => $offer_info['marketCategoryName'],
						'synch_status'	=>  $offer_info['beru_status']?true:false,
						'edit' => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $offer_info['product_id'] , true)
					);
					
					$data['check_products'][] = $check_products_data;
					$this->model_extension_module_yandex_beru->updateOfferShopSku($offer_info['product_id'], $offer_info['model']);
					//updateOfferShopSku
					echo "<script>console.log('check_products_data: " . $check_products_data . "' );</script>";
					echo "<script>console.log(" . json_encode($check_products_data) . ");</script>";
					//echo "<script>console.log('check_products_data: " . json_encode($check_products_data) . "');</script>";
				}
			}
		}
		if(!empty($data['check_products'])){

			$products_id_total = count($data['check_products']);
			$data['check_products'] = array_slice($data['check_products'], (($page_re - 1) * $limit_re), $limit_re);
					echo "<script>console.log('products_id_total: " . $products_id_total . "' );</script>";
					echo "<script>console.log('products_id_total: " . json_encode($products_id_total) . "' );</script>";
			$pagination_re = new Pagination();
			$pagination_re->total = $products_id_total;
			$pagination_re->page = $page_re;
			$pagination_re->limit = $limit_re;
			
			$pagination_re->url = $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'] . $url_groups . '&page_re={page}', true);

			$data['pagination_re'] = $pagination_re->render();

			$data['results_re'] = sprintf($this->language->get('text_pagination'), ($products_id_total) ? (($page_re - 1) * $limit_re) + 1 : 0, ((($page_re - 1) * $limit_re) > ($products_id_total - $limit_re)) ? $products_id_total : ((($page_re - 1) * $limit_re) + $limit_re), $products_id_total, ceil($products_id_total / $limit_re));
		}
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

	
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/group_list', $data));
	}
	
//	Обновляем данные предложения
	
	private function entries_updates($shopSkus){
		echo "<script>console.log('shopSkus2' );</script>";
		$this->load->model('extension/module/yandex_beru');
		echo "<script>console.log('shopSkus: " . $shopSkus . "' );</script>";
		echo "<script>console.log('shopSkus: " . json_encode($shopSkus) . "' );</script>";
		if(!empty($shopSkus)){
			
			$post_data['offerMappingEntries'] = array();
			$shopSkus_success = array();
			
			foreach($shopSkus as $shopSku){
		echo "<script>console.log('shopSkus: " . $shopSkus . "' );</script>";
		echo "<script>console.log('shopSku: " . $shopSku . "' );</script>";
		echo "<script>console.log('shopSkus: " . json_encode($shopSkus) . "' );</script>";
		
				$offer_data = $this->model_extension_module_yandex_beru->getUpdatesOfferInfo($shopSku);
				
				echo "<script>console.log('offer_data: " . json_encode($offer_data) . "' );</script>";
				
				$offer_errors = $this->model_extension_module_yandex_beru->findOfferErrors($shopSku);
				
				echo "<script>console.log('offer_errors: " . json_encode($offer_errors) . "' );</script>";
				
				if(!$offer_errors){
					$shopSkus_success[$shopSku] = $offer_data;
					$post_data['offerMappingEntries'][] = $offer_data;	
				}
				
			}
			echo "<script>console.log('shopSkus_3' );</script>";
			$this->api = new yandex_beru();
			$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
			$component = $this->api->loadComponent('offerMappingEntriesUpdates');
			
			echo "<script>console.log('post_data: " . $post_data . "' );</script>";
			echo "<script>console.log('post_data: " . json_encode($post_data) . "' );</script>";
			
			$component->setData($post_data);
			
			echo "<script>console.log('component: " . $component . "' );</script>";
			echo "<script>console.log('component: " . json_encode($component) . "' );</script>";
			
			$response = $this->api->sendData($component);
			
			echo "<script>console.log('response: " . $response . "' );</script>";
			echo "<script>console.log('response: " . json_encode($response) . "' );</script>";

			if(is_array($response)){//верные данные всегда массив, ошибки строка.

				foreach($shopSkus_success as $shopSku => $success_data){
					//Обновляем данные и статус предложения если нет ошибок
					$update_data = [
						'marketSku'=> $success_data['mapping']['marketSku'],
						'marketSkuName'=>$success_data['offer']['marketSkuName'],
						'marketCategoryName'=>$success_data['offer']['marketCategoryName'],
						'status'=>'IN_WORK',
						'shopSku' => $success_data['offer']['shopSku'],
					];
					
					$this->model_extension_module_yandex_beru->updateOffer($update_data);
				}
				
				echo "<script>console.log('shopSkus: " . $shopSkus . "' );</script>";
				echo "<script>console.log('shopSkus_success: " . $shopSkus_success . "' );</script>";
				echo "<script>console.log('shopSkus: " . json_encode($shopSkus) . "' );</script>";
				echo "<script>console.log('shopSkus_success: " . json_encode($shopSkus_success) . "' );</script>";
				echo "<script>console.log('update_data: " . json_encode($update_data) . "' );</script>";
				echo "<script>console.log('success_data: " . json_encode($success_data) . "' );</script>";
				echo "<script>console.log('array_keys(shopSkus_success): " . array_keys($shopSkus_success) . "' );</script>";
				echo "<script>console.log('array_keys(shopSkus_success): " . json_encode(array_keys($shopSkus_success)) . "' );</script>";
				//success_data: {"offer":{"shopSku":"15401","name":"Детский рюкзак KAKOO Машинки - Мими","category":"Рюкзаки AnimalWorld","yandex_sku":"","image":"catalog/Kakoo/AW0023-05.jpg","images":["catalog/Kakoo/AW0023-05_3.jpg","catalog/Kakoo/AW0023-05_2.jpg","catalog/Kakoo/AW0023-05_1.jpg"],"manufacturerCountries":["Китай"],"manufacturer":"KAKOO","vendor":"KAKOO","weightDimensions":{"length":"26.00000000","width":"3.00000000","height":"30.00000000","weight":"0.30000000"},"marketSkuName":"","marketCategoryName":"Рюкзаки и ранцы для школы","urls":["https://eridanus.ru/image/catalog/Kakoo/AW0023-05.jpg"],"pictures":["https://eridanus.ru/image/catalog/Kakoo/AW0023-05.jpg","https://eridanus.ru/image/catalog/Kakoo/AW0023-05_3.jpg","https://eridanus.ru/image/catalog/Kakoo/AW0023-05_2.jpg","https://eridanus.ru/image/catalog/Kakoo/AW0023-05_1.jpg"]},"mapping":{"marketSku":""}}
				if(array_keys($shopSkus_success) == $shopSkus){
//					Если список sku без ошибок совпал со списком исходных sku то редиректим на страницу статусов предложений
					echo "<script>console.log('array_keys(shopSkus_success) == shopSkus' );</script>";
					$this->response->redirect($this->url->link('extension/module/yandex_marketplace/offer_status', 'user_token=' . $this->session->data['user_token'] , true));
					echo "<script>console.log('shopSkus_success' );</script>";
				}else{
					echo "<script>console.log('error_partial_dispatch' );</script>";
					$this->error['warning'] = $this->language->get('error_partial_dispatch');
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
			echo "<script>console.log('todo Добавить обработку ответа. ' );</script>";
			echo "<script>console.log('Если все хорошо, то обновляем в базе статус ' );</script>";
//			todo Добавить обработку ответа. 
//			Если все хорошо, то обновляем в базе статус
	
		}
	
	}
	
	protected function getForm() {
		echo "<script>console.log('getFormshopSkus' );</script>";
		$this->document->addStyle('view/stylesheet/select2.css');
		$this->document->addScript('view/javascript/select2.min.js');

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->request->get['group_id'])) {
			$group_id = $this->request->get['group_id'];
		} else {
			$group_id = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		
		$this->load->model('catalog/category');
		$data['categories'] = $this->model_catalog_category->getCategories();

		$data['options'] = $this->model_extension_module_yandex_beru->getOptionList();

		$data['text_form'] = !isset($this->request->get['group_id']) ? $this->language->get('text_add_group') : $this->language->get('text_edit_group');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

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
			'text' => $this->language->get('heading_title_groups'),
			'href' => $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'], true)
		);

		if (!isset($this->request->get['group_id'])) {
			$data['action_save'] = $this->url->link('extension/module/yandex_marketplace/product_group/add', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action_save'] = $this->url->link('extension/module/yandex_marketplace/product_group/edit', 'user_token=' . $this->session->data['user_token'] . '&group_id=' . $this->request->get['group_id'], true);
		}
		
		$data['action_cancel'] = $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'], true);

		if (isset($this->request->get['group_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$group_info = $this->model_extension_module_yandex_beru->getProductGroup($this->request->get['group_id']);
		}
		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} elseif (isset($this->request->post['filter_name'])) {
			$filter_name = $this->request->post['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} elseif (isset($this->request->post['filter_model'])) {
			$filter_model = $this->request->post['filter_model'];
		} else {
			$filter_model = '';
		}

		if (isset($this->request->get['filter_category'])) {
			$filter_category = explode('-', $this->request->get['filter_category']);
		} elseif (isset($this->request->post['filter_category'])) {
			$filter_category = $this->request->post['filter_category'];
		} else {
			$filter_category = array();
		}
			
		if (isset($this->request->get['filter_product'])) {
			if(empty($this->request->get['filter_product'])){
				$filter_product = array();
			}else{
				$filter_product = explode('-', $this->request->get['filter_product']);
			}
			
		} elseif (isset($this->request->post['filter_product'])) {
			if(empty($this->request->post['filter_product'])){
				$filter_product = array();
			}else{
				$filter_product = $this->request->post['filter_product'];
			}
		} elseif (!empty($group_info['filter_product'])) {
			$filter_product = json_decode($group_info['filter_product']);
		} else {
			$filter_product = array();
		}

		if (isset($this->request->get['filter_option'])) {
			$filter_option = explode('-', $this->request->get['filter_option']);
		} elseif (isset($this->request->post['filter_option'])) {
			$filter_option = $this->request->post['filter_option'];
		} else {
			$filter_option = array();
		}

		if (isset($this->request->get['filter_price_from'])) {
			$filter_price_from = $this->request->get['filter_price_from'];
		} elseif (isset($this->request->post['filter_price_from'])) {
			$filter_price_from = $this->request->post['filter_price_from'];
		} else {
			$filter_price_from = '';
		}

		if (isset($this->request->get['filter_price_to'])) {
			$filter_price_to = $this->request->get['filter_price_to'];
		} elseif (isset($this->request->post['filter_price_to'])) {
			$filter_price_to = $this->request->post['filter_price_to'];
		} else {
			$filter_price_to = '';
		}

		if (isset($this->request->get['filter_quantity_from'])) {
			$filter_quantity_from = $this->request->get['filter_quantity_from'];
		} elseif (isset($this->request->post['filter_quantity_from'])) {
			$filter_quantity_from = $this->request->post['filter_quantity_from'];
		} else {
			$filter_quantity_from = '';
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$filter_quantity_to = $this->request->get['filter_quantity_to'];
		} elseif (isset($this->request->post['filter_quantity_to'])) {
			$filter_quantity_to = $this->request->post['filter_quantity_to'];
		} else {
			$filter_quantity_to = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} elseif (isset($this->request->post['filter_status'])) {
			$filter_status = $this->request->post['filter_status'];
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['group_name'])) {
			$data['name'] = $this->request->get['group_name'];
		} elseif (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($group_info)) {
			$data['name'] = $group_info['name'];
		} else {
			$data['name'] = '';
		}

		$data['mode'] = $mode = !isset($this->request->get['group_id']) ? 'add' : 'edit';
		$data['user_token'] = $this->session->data['user_token'];
		
		$filter_data = array(
			'filter_name'	  					=> $filter_name,
			'filter_model'	  				=> $filter_model,
			'filter_category'	  			=> $filter_category,
			'filter_product'	  			=> $filter_product,
			'filter_option'	  				=> $filter_option,
			'filter_price_from'	  		=> $filter_price_from,
			'filter_price_to'	  			=> $filter_price_to,
			'filter_quantity_from' 		=> $filter_quantity_from,
			'filter_quantity_to' 			=> $filter_quantity_to,
			'filter_status'   				=> $filter_status,
			'sort'            				=> $sort,
			'order'           				=> $order,
			'start'           				=> ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           				=> $this->config->get('config_limit_admin')
		);

		$products_in_group = $this->model_extension_module_yandex_beru->getProductsFromGroup($group_id);
		$product_total = $this->model_extension_module_yandex_beru->getTotalProductsByFilters($filter_data);
		$results = $this->model_extension_module_yandex_beru->getProductsByFilters($filter_data);

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}

			$special = false;

			$product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			foreach ($product_specials  as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || strtotime($product_special['date_start']) < time()) && ($product_special['date_end'] == '0000-00-00' || strtotime($product_special['date_end']) > time())) {
					$special = $this->currency->format($product_special['price'], $this->config->get('config_currency'));

					break;
				}
			}

			$data['products'][] = array(
				'product_id' => $result['product_id'],
				'image'      => $image,
				'name'       => $result['name'],
				'model'      => $result['model'],
				'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
				'special'    => $special,
				'quantity'   => $result['quantity'],
				'product_in_group' => in_array($result['product_id'], $products_in_group), 
				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')
			);
		}

		$url_params = array(
			'group_id' => '',
			'filter_name' => 'html_decode',
			'filter_model' => 'html_decode',
			'filter_category' => '',
			'filter_option' => '',
			'filter_price_from' => '',
			'filter_price_to' => '',
			'filter_quantity_from' => '',
			'filter_quantity_to' => '',
			'filter_status' => '',
			'page' => '',
		);
		$url = $this->createUrlTail($url_params);

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_name'] = $this->url->link('extension/module/yandex_marketplace/product_group/' . $mode, 'user_token=' . $this->session->data['user_token'] . $url . '&sort=pd.name', true);
		$data['sort_model'] = $this->url->link('extension/module/yandex_marketplace/product_group/' . $mode, 'user_token=' . $this->session->data['user_token'] . $url . '&sort=p.model', true);
		$data['sort_price'] = $this->url->link('extension/module/yandex_marketplace/product_group/' . $mode, 'user_token=' . $this->session->data['user_token'] . $url . '&sort=p.price', true);
		$data['sort_quantity'] = $this->url->link('extension/module/yandex_marketplace/product_group/' . $mode, 'user_token=' . $this->session->data['user_token'] . $url . '&sort=p.quantity', true);
		$data['sort_status'] = $this->url->link('extension/module/yandex_marketplace/product_group/' . $mode, 'user_token=' . $this->session->data['user_token'] . $url . '&sort=p.status', true);
		$data['sort_order'] = $this->url->link('extension/module/yandex_marketplace/product_group/' . $mode, 'user_token=' . $this->session->data['user_token'] . $url . '&sort=p.sort_order', true);

		$url_params = array(
			'group_id' => '',
			'filter_name' => 'html_decode',
			'filter_model' => 'html_decode',
			'filter_category' => '',
			'filter_option' => '',
			'filter_price_from' => '',
			'filter_price_to' => '',
			'filter_quantity_from' => '',
			'filter_quantity_to' => '',
			'filter_status' => '',
			'sort' => '',
			'order' => ''
		);
		$url = $this->createUrlTail($url_params);

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/yandex_marketplace/product_group/' . $mode, 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

		$data['group_id'] = $group_id;
		$data['filter_name'] = $filter_name;
		$data['filter_model'] = $filter_model;
		$data['filter_category'] = json_encode($filter_category);
		
		
		$data['filter_products'] = array();
		
		if(!empty($filter_product)){
			foreach($filter_product as $filter_product_id){
				$product_info = $this->model_catalog_product->getProduct($filter_product_id);

				if(!empty($product_info)){
					$data['filter_products'][] = [
						'product_id' => $product_info['product_id'],
						'name'       => $product_info['name'],
					];
				}
			}
		}
		
		$data['filter_option'] = json_encode($filter_option);
		$data['filter_price_from'] = $filter_price_from;
		$data['filter_price_to'] = $filter_price_to;
		$data['filter_quantity_from'] = $filter_quantity_from;
		$data['filter_quantity_to'] = $filter_quantity_to;
		$data['filter_status'] = $filter_status;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/group_form', $data));
	}

	// Чтобы уменьшить количество кода в других функциях
	protected function createUrlTail($params = array()) {
		echo "<script>console.log('createUrlTailshopSkus' );</script>";
		$url = '';

		foreach ($params as $param => $mode) {
			if (empty($param)) {
				continue;
			}
			if (empty($mode)) {
				$mode = 'default';
			}

			if (isset($this->request->get[$param])) {
				if ($mode == 'default') {
					$url .= '&' . $param . '=' . $this->request->get[$param];
				} elseif ($mode == 'html_decode') {
					$url .= '&' . $param . '=' . urlencode(html_entity_decode($this->request->get[$param], ENT_QUOTES, 'UTF-8'));
				}
			}
		}

		return $url;
	}

	protected function validateForm() {
		echo "<script>console.log('validateFormshopSkus' );</script>";
		if (!$this->user->hasPermission('modify', 'extension/module/yandex_marketplace')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_group_name');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_form');
		}

		return !$this->error;
	}

	protected function validateModify() {
		echo "<script>console.log('validateModifyshopSkus' );</script>";
		if (!$this->user->hasPermission('modify', 'extension/module/yandex_marketplace')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	private function getUpdatesOfferInfo($key){
		echo "<script>console.log('key' );</script>";
		echo "<script>console.log('key: " . $key . "' );</script>";
		$this->load->language('extension/module/yandex_marketplace');
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/option');
		
		$offer_key_data = explode('-',$shopSku);
		$product_id = array_shift($offer_key_data);
		$product_info = $this->model_catalog_product->getProduct($product_id);	
	
		$offer_info = $this->model_extension_module_yandex_beru->getOffer($shopSku);	
		
		$options_text = '';
		
		if(!empty($offer_key_data)){
			
			$options = array_chunk($offer_key_data, 2);
			
			foreach($options as $option){
				
				$option_value = $this->model_catalog_option->getOptionValue($option[1]);
				$options_text .= ' ' . $option_value['name'];
			}
			
		}
		
		$offer_data = [
			"shopSku" => $this->getValidatedSku($shopSku),
			"name"	=> $product_info['name'].$options_text,
			"category" => $this->model_extension_module_yandex_beru->getProductCategory($product_id)
			];
		
		if(!empty($product_info['manufacturer_id'])){
			$manufacturer_data = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);
			if($manufacturer_data){
				$offer_data['vendor'] = $manufacturer_data['name'];
				$offer_data['manufacturer'] = $manufacturer_data['name'];
			}
			
		}
//		Должен содержать хотя бы одну, но не больше 5 стран.
		$manufacturer_countries = ['Китай'];
		
		$offer_data['manufacturerCountries'] = $manufacturer_countries;
		
		$urls = array();
		
		if($product_info['image']){
			$urls[] = HTTPS_CATALOG.'images/'.$product_info['image'];
		}
		
		$offer_data['urls'] = $urls;
		
		$offerMappingEntrie = [
			'offer' => $offer_data,
			'mapping' => [
				'marketSku' => $offer_info['yandex_sku']
			]	 
		];

		return $offerMappingEntrie;
	}

	private function getValidatedSku($sku){
		return preg_replace('/[^a-zA-ZА-Яа-я0-9\,\.\/\(\)\[\]\-\=]/', '-',$sku);
	}
}