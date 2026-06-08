<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceShippingdbs extends Controller {

	private $error = array();

    public function index(){

        $this->api = new yandex_beru();
		
		$this->load->language('extension/module/yandex_marketplace');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/yandex_beru.css');
		
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('localisation/tax_class');
		$this->load->model('setting/store');
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->model('catalog/category');
		
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			
			$json['yandex_beru_DBS'] = json_encode($this->request->post);
				
			$this->model_setting_setting->editSetting('yandex_beru_DBS', $json);

			$this->session->data['success'] = $this->language->get('text_success');
		}
	
//		breadcrumbs
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
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/yandex_marketplace/shipping_dbs', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];
		
//		Errors
		$data['tab_errors'] = array(); 
		
		foreach($this->error as $errors){
			foreach($errors as $error_key => $error){
				$data['tab_errors'][$error_key] = true;
			}
		}
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		}

		if (isset($this->error['fromDate'])) {
			$data['error_fromDate'] = $this->error['fromDate'];
		}

		if (isset($this->error['toDate'])) {
			$data['error_toDate'] = $this->error['toDate'];
		}
		
		if (isset($this->error['shipmentDate'])) {
			$data['error_shipmentDate'] = $this->error['shipmentDate'];
		}

		if (isset($this->error['paymentMethods'])) {
			$data['error_paymentMethods'] = $this->error['paymentMethods'];
		}

		if (isset($this->error['shipping_zone'])) {
			$data['error_shipping_zone'] = $this->error['shipping_zone'];
		}
		
		if (isset($this->error['intervals'])) {
			$data['error_intervals'] = $this->error['intervals'];
		}
		if (!empty($this->request->post['shippings'])){
			$data['setting']['shippings'] = $this->request->post['shippings'];
		}elseif(!empty($this->config->get('yandex_beru_DBS'))){
			$data['setting'] =  json_decode($this->config->get('yandex_beru_DBS'), true);
		}

		if (isset($this->request->post['page'])) {
			$page = $this->request->post['page'];
		} else {
			$page = 1;
		}
		
		if (isset($this->request->post['active_tab'])) {
			$data['active_tab'] = $this->request->post['active_tab'];
		} else {
			$data['active_tab'] = 1;
		}
		
		$data['categories'] = $this->model_catalog_category->getCategories();

		$data['shipping_zone'] =  $this->model_extension_module_yandex_beru->getShippingZone();
  	
		$data['user_token'] = $this->session->data['user_token'];
		
		$products = array();
		
		if(!empty($data['setting']['shippings'])){
			foreach ($data['setting']['shippings'] as $key_shipping => $shipping) {

				$shipping['filter']['start'] = ($page - 1) * $this->config->get('config_limit_admin');

				$filter_data = array(
					'model'	  			=> $shipping['filter']['model'],
					'price_from'	  	=> $shipping['filter']['price_from'],
					'price_to'			=> $shipping['filter']['price_to'],
					'quantity_from' 	=> $shipping['filter']['quantity_from'],
					'quantity_to' 		=> $shipping['filter']['quantity_to'],
					'category'	  		=> !empty($shipping['filter']['category']) ? $shipping['filter']['category'] : array(),
					'start'           	=> ($page - 1) * $this->config->get('config_limit_admin'),
					'limit'           	=> $this->config->get('config_limit_admin'),
					'page'				=> $page,
				);
				if(!empty($data['setting']['shippings'][$key_shipping]['products'])){
					foreach($data['setting']['shippings'][$key_shipping]['products'] as $key_product => $product_id){
						$shipping['products'][$key_product]	= $this->getProductInfo($product_id);
					}
				}else{
					$shipping['products'] = array();
				}
				
				$shipping['product_list'] = $this->getFileredProductsForm($filter_data, $shipping['products']);

				$data['setting']['shippings'][$key_shipping] = $shipping;
					
			}
		}else{
			$data['setting']['shippings'][1] = ['name'=>'Новая доставка'];
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/shipping_dbs', $data));

	}
	
	public function getAjaxProductList(){
		$json = array();
		
		if(!empty($this->request->post)){
			if (isset($this->request->post['page'])) {
				$page = $this->request->post['page'];
			} else {
				$page = 1;
			}
			
			$filter_data = array(
				'model'	  			=> $this->request->post['model'],
				'price_from'	  	=> $this->request->post['price_from'],
				'price_to'			=> $this->request->post['price_to'],
				'quantity_from' 	=> $this->request->post['quantity_from'],
				'quantity_to' 		=> $this->request->post['quantity_to'],
				'category'	  		=> !empty($this->request->post['category']) ? $this->request->post['category'] : array(),
				'start'           	=> ($page - 1) * $this->config->get('config_limit_admin'),
				'limit'           	=> $this->config->get('config_limit_admin'),
				'page'				=> $page,
			);
				
			$shipping_products = [];
			
			if(!empty($this->request->post['products'])){
				foreach($this->request->post['products'] as $product_id){
					$shipping_products[] = $this->getProductInfo($product_id);
				}
			}
			
			$json['html'] = $this->getFileredProductsForm($filter_data, $shipping_products);
			
		}
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
			
	}
	
	private function getFileredProductsForm($filter_data, $shipping_products){
		$this->load->language('extension/module/yandex_marketplace');
		$this->load->model('extension/module/yandex_beru');
		
		$page = $filter_data['page'];
		
		$total_products = $this->model_extension_module_yandex_beru->getTotalProducts($filter_data, $shipping_products);
		$products = $this->model_extension_module_yandex_beru->getProducts($filter_data, $shipping_products);
		
		$pagination = new Pagination();
		$pagination->total = $total_products;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/yandex_marketplace/shipping_dbs', '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total_products) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($total_products - $this->config->get('config_limit_admin'))) ? $total_products : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total_products, ceil($total_products / $this->config->get('config_limit_admin')));
		
		foreach ($products as $product_id) {
			$data['products'][] = $this->getProductInfo($product_id);	
		}
		return $this->load->view('extension/module/yandex_marketplace/shipping_dbs_product_list', $data);
		
	}
	
	private function getProductInfo($product_id){
		
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		
		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (is_file(DIR_IMAGE . $product_info['image'])) {
			$image = $this->model_tool_image->resize($product_info['image'], 40, 40);
		} else {
			$image = $this->model_tool_image->resize('no_image.png', 40, 40);
		}

		return array(
			'product_id' => $product_id,
			'image'      => $image,
			'name'       => $product_info['name'],
			'model'      => $product_info['model'],
			'price'      => $this->currency->format($product_info['price'], $this->config->get('config_currency')),
			'quantity'   => $product_info['quantity'],
		);
		
	}

	public function addShipping(){
		
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->load->model('catalog/category');
		$this->load->model('extension/module/yandex_beru');
		
		$data['categories'] = $this->model_catalog_category->getCategories();
		
		$data['shipping_zone'] = $this->model_extension_module_yandex_beru->getShippingZone();

		$data['key'] = $this->request->post['id'];
		
		$data['shipping']['name'] = "Доставка " . $data['key'];
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/shipping_filter_tab', $data));
	}
	
	public function updateRegion(){

		$regions = array("Республика Адыгея", "Республика Башкортостан", "Республика Бурятия", "Республика Алтай", "Республика Дагестан", "Республика Ингушетия", "Кабардино-Балкарская Республика", "Республика Калмыкия","Карачаево-Черкесская Республика", "Республика Карелия", "Республика Коми", "Республика Марий Эл", "Республика Мордовия", "Республика Саха (Якутия)", "Республика Северная Осетия - Алания", "Республика Татарстан (Татарстан)","Республика Тыва", "Удмуртская Республика", "Республика Хакасия", "Чеченская Республика", "Чувашская Республика - Чувашия", "Алтайский край", "Краснодарский край", "Красноярский край", "Приморский край", "Ставропольский край", "Хабаровский край", "Амурская область", "Архангельская область", "Астраханская область", "Белгородская область", "Брянская область", "Владимирская область", "Волгоградская область", "Вологодская область", "Воронежская область", "Ивановская область", "Иркутская область", "Калининградская область", "Калужская область","Камчатский край", "Кемеровская область - Кузбасс", "Кировская область", "Костромская область", "Курганская область", "Курская область", "Ленинградская область", "Липецкая область", "Магаданская область", "Московская область", "Мурманская область", "Нижегородская область", "Новгородская область", "Новосибирская область", "Омская область", "Оренбургская область", "Орловская область", "Пензенская область", "Пермский край", "Псковская область", "Ростовская область", "Рязанская область", "Самарская область", "Саратовская область", "Сахалинская область", "Свердловская область", "Смоленская область", "Тамбовская область", "Тверская область", "Томская область", "Тульская область", "Тюменская область", "Ульяновская область", "Челябинская область", "Забайкальский край", "Ярославская область", "Москва", "Санкт-Петербург", "Еврейская автономная область", "Ненецкий автономный округ", "Ханты-Мансийский автономный округ - Югра", "Чукотский автономный округ", "Ямало-Ненецкий автономный округ", "Республика Крым", "Севастополь", "иные территории, включая город и космодром Байконур");
 
		$this->api = new yandex_beru();

		$this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id'));

		$component = $this->api->loadComponent('updateRegions');

		$this->load->model('extension/module/yandex_beru');

		$this->db->query("TRUNCATE " . DB_PREFIX . "yb_regions");

		foreach ($regions as $region) {

			$region_1 = array('name' => $region);

			print_r('<pre>');

				print_r($region);

			print_r('<pre>');

			$component->setData($region_1);

			$response = $this->api->sendData($component); 

			print_r('<pre>');

				print_r($response);

			print_r('<pre>');

			if(!empty($response['regions']['0'])){

				$this->model_extension_module_yandex_beru->addRegion($response['regions']['0']);

			}

		}

	}

	public function getAPIdelivery(){

		$this->load->model('extension/module/yandex_beru');

		$this->api = new yandex_beru();

		$this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id'));

		$component = $this->api->loadComponent('deliveryServices');
		
		$component->setData('');

		$response = $this->api->sendData($component); 

		if(!empty($response['result']['deliveryService'])){

			$this->model_extension_module_yandex_beru->addDeliveryService($response['result']['deliveryService']);

		}

	}

	protected function validate() {

		if (!$this->user->hasPermission('modify', 'extension/module/banner')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if(!empty($this->request->post['shippings'])){
		foreach ($this->request->post['shippings'] as $key => $value) {
			
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 60)) {
				$this->error['name'][$key] = 'Название должно быть больше 1 символа и меньше 50';
			}
			
			$fromDate = 0;
			$toDate = 0; 
			$shipmentDate = 0;
			
			if (!preg_match('#^[0-9]+$#', $value['fromDate'])) {
				$this->error['fromDate'][$key] = 'Должны присутствовать только цифры';
			}else{
				$fromDate = $value['fromDate'];
			}

			if (!preg_match('#^[0-9]+$#', $value['toDate'])) {
				$this->error['toDate'][$key] = 'Должны присутствовать только цифры';
			}else{
				$toDate = $value['toDate'];
			}
			
			if (!preg_match('#^[0-9]+$#', $value['shipmentDate'])) {
				$this->error['shipmentDate'][$key] = 'Должны присутствовать только цифры';
			}else{
				$shipmentDate = $value['shipmentDate'];
			}
			
			if($fromDate > $toDate){
				$this->error['fromDate'][$key] = 'Ближайшая дата доставки не может быть больше поздней даты доставки';
			}elseif(($toDate - $fromDate) > 6){
				$this->error['toDate'][$key] = 'Интервал между датами не может превышать 7 дней';
			}elseif($toDate > 31 || $fromDate > 31){
				$this->error['toDate'][$key] = 'Максимальный срок доставки 31 день';
			}
			
			if ($shipmentDate > $fromDate){
				$this->error['shipmentDate'][$key] = 'Дата отгрузки не может быть больше самой ранней даты доставки (но может совпадать с датой доставки)';
			}
			
			if ($shipmentDate < 0){
				$this->error['shipmentDate'][$key] = 'Дата отгрузки не может быть меньше нуля';
			}
			
			if (empty($value['paymentMethods'])) {
				$this->error['paymentMethods'][$key] = 'Выберете методы оплаты';
			}

			if (empty($value['shipping_zone'])) {
				$this->error['shipping_zone'][$key] = 'Выберете регионы доставки';
			}

			if(!empty($value['intervals'])){
				if(count($value['intervals']) > 5 ){
					$this->error['intervals'][$key] = 'Максимальное количество инетвалов: 5';
				}else{
					foreach($value['intervals'] as $interval_val){
						if(!$this->isValidDate($interval_val['from'], 'H:s') || !$this->isValidDate($interval_val['to'], 'H:s')){
							$this->error['intervals'][$key] = 'Интервал должен быть в формате ЧЧ:ММ (например 12:00)';
						}else{
							$diference = strtotime($interval_val['to']) - strtotime($interval_val['from']);

							if($diference < (60*60*2) || $diference > (60*60*8)){
								$this->error['intervals'][$key] = 'Интервал должен быть от 2 до 8 часов';
							}
						}
					}
				}
			}

		}
		}
		return !$this->error;

	}
	
	private function isValidDate(string $date, string $format = 'Y-m-d'): bool
	{
		$dateObj = DateTime::createFromFormat($format, $date);
		return $dateObj && $dateObj->format($format) == $date;
	}
}