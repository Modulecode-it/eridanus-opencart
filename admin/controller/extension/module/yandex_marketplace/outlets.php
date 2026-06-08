<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOutlets extends Controller {

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
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_outlets'),
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];
		
//		Errors
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		if (isset($this->error['outlet_storagePeriod'])) {
			$data['outlet_storagePeriod'] = $this->error['outlet_storagePeriod'];
		}

		if (isset($this->error['paymentMethods'])) {
			$data['error_paymentMethods'] = $this->error['paymentMethods'];
		}

		if (isset($this->error['shipping_zone'])) {
			$data['error_shipping_zone'] = $this->error['shipping_zone'];
		}

		$data['user_token'] = $this->session->data['user_token'];
		
		$data['add_outlet'] = $this->url->link('extension/module/yandex_marketplace/outlets/addOutlet', 'user_token=' . $this->session->data['user_token'], true);
		$data['update_outlet'] = $this->url->link('extension/module/yandex_marketplace/outlets/updateOutlets', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete_outlet'] = $this->url->link('extension/module/yandex_marketplace/outlets/deleteOutlet', 'user_token=' . $this->session->data['user_token'], true);
        $data['edit_outlet'] = $this->url->link('extension/module/yandex_marketplace/outlets/editOutlet', 'user_token=' . $this->session->data['user_token'], true);
		
		$this->load->model('extension/module/yandex_beru');
        $outlets = $this->model_extension_module_yandex_beru->getOutlets();
		
		if(empty($outlets)){
            $this->api = new yandex_beru();
                    
            $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            $component = $this->api->loadComponent('outlets');
            $component = $this->api->sendData($component);
                    
            foreach($component['outlets'] as $outlet){
                $data['outlets'][] = array(
                    'name'	  			=> $outlet['name'],
                    'address'	  	    => $outlet['address'],
                    'storagePeriod'	    => $outlet['storagePeriod'],
                    'workingTime' 	    => $outlet['workingTime'],
                    'status' 	        => $outlet['status'],
                    'id' 	            => $outlet['id'],
                );
                 
                $this->model_extension_module_yandex_beru->addOutlets($outlet);
            } 
            
		}else{
            foreach($outlets as $outlet){
                $data['outlets'][] = array(
                    'name'	  			=> $outlet['name'],
                    'address'	  	    => json_decode($outlet['address']),
                    'storagePeriod'	    => $outlet['storagePeriod'],
                    'workingTime' 	    => $outlet['workingTime'],
                    'status' 	        => $outlet['status'],
                    'id' 	            => $outlet['id'],
                );
            } 
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/outlets', $data));

	}
    public function updateOutlets(){
        $this->load->model('extension/module/yandex_beru');
        
        $this->api = new yandex_beru();
                    
        $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
        $component = $this->api->loadComponent('outlets');
        $component = $this->api->sendData($component);
     
        if(isset($component['outlets'])){
            $this->model_extension_module_yandex_beru->deleteOutlets();
            foreach($component['outlets'] as $outlet){
                
                switch ($outlet['status']) {
                    case "AT_MODERATION":
                        $outlet['status'] = "Проверяется";
                        break;
                    case "FAILED":
                        $outlet['status'] = "Не прошла проверку и отклонена модератором";
                        break;
                    case "MODERATED":
                        $outlet['status'] = "Проверена и одобрена";
                        break;
                     case "NONMODERATED":
                        $outlet['status'] = "Новая точка, нуждается в проверке";
                        break;
                }
            
                $data['outlets'][] = array(
                    'name'	  			=> $outlet['name'],
                    'address'	  	    => $outlet['address'],
                    'storagePeriod'	    => $outlet['storagePeriod'],
                    'workingTime' 	    => $outlet['workingTime'],
                    'status' 	        => $outlet['status'],
                    'id' 	            => $outlet['id'],
                );
                $this->model_extension_module_yandex_beru->addOutlets($outlet);
            } 
            $this->response->redirect($this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true));
        }
	}
	
	public function deleteOutlet(){
        if($this->request->get["outlet_id"]){
            
            $this->load->model('extension/module/yandex_beru');
            $this->model_extension_module_yandex_beru->deleteOutlets($this->request->get["outlet_id"]);
        
            $this->api = new yandex_beru();
                    
            $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            $component = $this->api->loadComponent('outletDelete');
            $component->setOutletId($this->request->get["outlet_id"]);
            $out = $this->api->sendData($component);
            
            if(isset($out['status']) && $out['status'] === 'OK'){
                $this->session->data['success'] = 'Точка продаж удалена!';
                $this->updateOutlets();
            }
        }
	}
	
	public function addOutlet(){
	
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/yandex_beru.css');
		
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('setting/store');
		$this->load->model('extension/module/yandex_beru');
		
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
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_outlets'),
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_add_outlet'),
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets/addOutlet', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['user_token'] = $this->session->data['user_token'];
		$data['cancel'] = $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true);

		if (isset($this->request->post['outlet_name'])) {
			$data['outlet_name'] = $this->request->post['outlet_name'];
		} else {
			$data['outlet_name'] = '';
		}
		
		if (isset($this->request->post['type'])) {
			$data['type'] = $this->request->post['type'];
		}else {
			$data['type'] = 'DEPOT';
		}
		
		if (isset($this->request->post['outlet_id'])) {
			$data['outlet_id'] = $this->request->post['outlet_id'];
		} else {
			$data['outlet_id'] = '';
		}
		
		if (isset($this->request->post["isMain"])) {
			$data['isMain'] = $this->request->post["isMain"];
		} else {
			$data['isMain'] = '';
		}
		
		if (isset($this->request->post['outlet_region'])) {
			$data['outlet_region'] = $this->request->post['outlet_region'];
		} else {
			$data['outlet_region'] = '';
		}
		
		if (isset($this->request->post['region_id'])) {
			$data['region_id'] = $this->request->post['region_id'];
		} else {
			$data['region_id'] = '';
		}
		
		if (isset($this->request->post['outlet_address'])) {
			$data['outlet_address'] = $this->request->post['outlet_address'];
		} else {
			$data['outlet_address'] = '';
		}
		
		if (isset($this->request->post["address_city"])) {
			$data['address_city'] = $this->request->post['address_city'];
		} else {
			$data['address_city'] = '';
		}
		
		if (isset($this->request->post["address_street"])) {
			$data['address_street'] = $this->request->post['address_street'];
		} else {
			$data['address_street'] = '';
		}
		
		if (isset($this->request->post["address_number"])) {
			$data['address_number'] = $this->request->post['address_number'];
		} else {
			$data['address_number'] = '';
		}
		
		if (isset($this->request->post['cost'])) {
			$data['cost'] = $this->request->post['cost'];
		} else {
			$data['cost'] = '';
		}
		
		if (isset($this->request->post['minDeliveryDays'])) {
			$data['minDeliveryDays'] = $this->request->post['minDeliveryDays'];
		} else {
			$data['minDeliveryDays'] = '';
		}
		
		if (isset($this->request->post['maxDeliveryDays'])) {
			$data['maxDeliveryDays'] = $this->request->post['maxDeliveryDays'];
		} else {
			$data['maxDeliveryDays'] = '';
		}
		
		if (isset($this->request->post['orderBefore'])) {
			$data['orderBefore'] = $this->request->post['orderBefore'];
		} else {
			$data['orderBefore'] = '';
		}
		
		if (isset($this->request->post['outlet_phone'])) {
			$data['outlet_phone'] = $this->request->post['outlet_phone'];
		} else {
			$data['outlet_phone'] = '';
		}
		
		if (isset($this->request->post['outlet_phone_additional'])) {
			$data['outlet_phone_additional'] = $this->request->post['outlet_phone_additional'];
		} else {
			$data['outlet_phone_additional'] = '';
		}
		
		if (isset($this->request->post['outlet_storagePeriod'])) {
			$data['outlet_storagePeriod'] = $this->request->post['outlet_storagePeriod'];
		} else {
			$data['outlet_storagePeriod'] = '10';
		}
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
		
            $outlet = array(
                "name" => $this->request->post["outlet_name"],
                "type" => $this->request->post["type"],
                //"coords" => "20.4522144, 54.7104264",
                "isMain" => isset($this->request->post["isMain"]) ? true : false,
                "shopOutletCode" => $this->request->post["outlet_id"],
                "id" => $this->request->post["outlet_id"],
                "visibility" => $this->request->post["visibility"],
                "address" => array(
                    "regionId" => $this->request->post["region_id"],
                    "city" => $this->request->post["address_city"],
                    "street" => $this->request->post["address_street"],
                    "number" => $this->request->post["address_number"],
                ),
                "phones" => array(
                    $this->request->post["outlet_phone"] . ($this->request->post["outlet_phone_additional"] ? '#' . $this->request->post["outlet_phone_additional"] : ''),
                ),
                "workingSchedule" => array(
                    "workInHoliday" => false,
                    "scheduleItems" => $this->request->post["sale_worktime"],
                ),
                "deliveryRules" => array(
                    array(
                        "cost" => $this->request->post["cost"],
                        "minDeliveryDays" => $this->request->post["minDeliveryDays"],
                        "maxDeliveryDays" => $this->request->post["maxDeliveryDays"],
                        "orderBefore" => $this->request->post["orderBefore"]
                    )
                ),
                "emails" => array(
                    "example-shop@yandex.ru"
                ),
                "storagePeriod" => str_replace("_", "", $this->request->post["outlet_storagePeriod"]),
                "status" => "AT_MODERATION",
                //"workingTime" => $this->request->post["sale_worktime"]
            );
           
            $this->api = new yandex_beru();
                    
            $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            $component = $this->api->loadComponent('outlets');
            $component->type = 'POST';
            $component->setData(json_encode($outlet));
            $out = $this->api->sendData($component);

            if(isset($out['status']) && $out['status'] === 'OK'){
                $this->session->data['success'] = 'Точка продаж успешно создана!';
                $this->updateOutlets();                
            }
		}
		
		if (isset($this->error['sale_worktime'])) {
			$data['error_sale_worktime'] = $this->error['sale_worktime'];
		}
		
		if (isset($this->error['storagePeriod'])) {
			$data['error_storagePeriod'] = $this->error['storagePeriod'];
		}
		
		if (isset($this->error['region_id'])) {
			$data['error_region_id'] = $this->error['region_id'];
		}
		
		if (isset($this->error['min_max_delivery_days'])) {
			$data['error_min_max_delivery_days'] = $this->error['min_max_delivery_days'];
		}
		
		if (isset($this->error['other_region_delivery_1'])) {
			$data['error_other_region_delivery_1'] = $this->error['other_region_delivery_1'];
		}
		
		if (isset($this->error['other_region_delivery_2'])) {
			$data['error_other_region_delivery_2'] = $this->error['other_region_delivery_2'];
		}
		
		if (isset($this->error['own_region_delivery'])) {
			$data['error_own_region_delivery'] = $this->error['own_region_delivery'];
		}
		
		if (isset($this->error['outlet_id'])) {
			$data['error_outlet_id'] = $this->error['outlet_id'];
		}
		
		if (isset($this->error['outlet_name'])) {
			$data['error_outlet_name'] = $this->error['outlet_name'];
		}

        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
        $this->response->setOutput($this->load->view('extension/module/yandex_marketplace/outlets_add', $data));
		
	}
	
	public function editOutlet(){
	
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/yandex_beru.css');
		
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('setting/store');
		$this->load->model('extension/module/yandex_beru');
		
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
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_outlets'),
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_edit_outlet'),
			'href' => $this->url->link('extension/module/yandex_marketplace/outlets/addOutlet', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['cancel'] = $this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true);
		
		if($this->request->get["outlet_id"]){
        
            $this->api = new yandex_beru();
                    
            $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            $component = $this->api->loadComponent('outletInfo');
            $component->setOutletId($this->request->get["outlet_id"]);
            $out = $this->api->sendData($component);
         
            if(!isset($out['outlet']['deliveryRules'])){
                $out['outlet']['deliveryRules'] = array(array());
            }
            $data['outlet'] = $out['outlet'];
        }

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $outlet = array(
                "shopOutletCode" => $this->request->post["id"],
                "name" => $this->request->post["name"],
                "type" => $this->request->post["type"],
                "visibility" => $this->request->post["visibility"],
                "isMain" => isset($this->request->post["isMain"]) ? true : false,
//                "coords" => $this->request->post["coords"],
//                 "emails" => array(
//                     $this->request->post["emails"],
//                 ),
                "address" => array(
                    "regionId" => $this->request->post["region_id"],
                    "city" => $this->request->post["address_city"],
                    "street" => $this->request->post["address_street"],
                    "number" => $this->request->post["address_number"],
                ),
                "phones" => array(
                    $this->request->post["phones"],
                ),
                "workingSchedule" => array(
                    "scheduleItems" => 
                        $this->request->post["sale_worktime"],

                ),
                "deliveryRules" => array(
                    array(
                        "cost" => isset($this->request->post["cost"]) ? $this->request->post["cost"] : '',
                        "minDeliveryDays" => isset($this->request->post["minDeliveryDays"]) ? $this->request->post["minDeliveryDays"] : '',
                        "maxDeliveryDays" => isset($this->request->post["maxDeliveryDays"]) ? $this->request->post["maxDeliveryDays"] : '',
                        "orderBefore" => isset($this->request->post["orderBefore"]) ? $this->request->post["orderBefore"] : '',
                    )
                ),
                "storagePeriod" => str_replace("_", "", $this->request->post["storagePeriod"]),
                "id" => $this->request->post["id"],
                //"status" => "AT_MODERATION",
                //"workingTime" => $this->request->post["sale_worktime"]
            );
            $data['outlet'] = $outlet;
            
            if($this->validate()){
                $this->api = new yandex_beru();
                      
                $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
                $component = $this->api->loadComponent('outletEdit');
                $component->setOutletId($this->request->get["outlet_id"]);
                $component->setData(json_encode($outlet));
                $out = $this->api->sendData($component);

                if(isset($out['status']) && $out['status'] === 'OK'){
                    $this->session->data['success'] = 'Точка продаж успешно отредактирована!';
                    $this->response->redirect($this->url->link('extension/module/yandex_marketplace/outlets', 'user_token=' . $this->session->data['user_token'], true));                
                }
            }
		}
		
		if (isset($this->error['storagePeriod'])) {
			$data['error_storagePeriod'] = $this->error['storagePeriod'];
		}
		
		if (isset($this->error['sale_worktime'])) {
			$data['error_sale_worktime'] = $this->error['sale_worktime'];
		}
		
		if (isset($this->error['min_max_delivery_days'])) {
			$data['error_min_max_delivery_days'] = $this->error['min_max_delivery_days'];
		}
		
		if (isset($this->error['other_region_delivery_1'])) {
			$data['error_other_region_delivery_1'] = $this->error['other_region_delivery_1'];
		}
		
		if (isset($this->error['other_region_delivery_2'])) {
			$data['error_other_region_delivery_2'] = $this->error['other_region_delivery_2'];
		}
		
		if (isset($this->error['own_region_delivery'])) {
			$data['error_own_region_delivery'] = $this->error['own_region_delivery'];
		}
		
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
        $this->response->setOutput($this->load->view('extension/module/yandex_marketplace/outlets_edit', $data));
		
	}

	public function getAjaxRegion(){
		$json = array();
		
		if(isset($this->request->post["outlet_region"])){
            $city = array(
                "name" => $this->request->post["outlet_region"],
            );

            $this->regions = new yandex_beru();
                    
            $this->regions->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
            $region = $this->regions->loadComponent('regions');
            $region->setData($city);
            $out = $region->sendData($region);

//             foreach($out['regions'] as $region){
//                     $json['html'][$region['id']] = $region['name'] . ', '. $region['parent']['name'] . ', ' . $region['parent']['parent']['name'];
//             }
            
            foreach($out as $region_info){
                foreach($region_info as $index => $region){
                    $json[$index]['html'] = $region['name'] . ', '. $region['parent']['name'] . ', ' . $region['parent']['parent']['name'];
                    $json[$index]['region_id'] = $region['id'];
                }
            }
		}
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
			
	}
	
	protected function validate() {
		
		if(!empty($this->request->post)){
			
			if (isset($this->request->post["outlet_name"]) && ((utf8_strlen($this->request->post["outlet_name"]) < 1) || (utf8_strlen($this->request->post["outlet_name"]) > 255))) {
                $this->error['outlet_name'] = 'Название точки продаж должно содержать не менее 1 символа и не более 255';
			}
			
			if (isset($this->request->post["outlet_id"])) {
                $this->api = new yandex_beru();
                    
                $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
                $component = $this->api->loadComponent('outlets');
                $component = $this->api->sendData($component);
                
                foreach($component['outlets'] as $outlet){
                    if($outlet['id'] == $this->request->post["outlet_id"]){
                        $this->error['outlet_id'] = 'Точка продаж с данным идентификатором уже существует';
                    }
                }
			}
			
			if (isset($this->request->post["outlet_storagePeriod"]) && ($this->request->post["outlet_storagePeriod"] > 14 || $this->request->post["outlet_storagePeriod"] < 3)) {
				$this->error['storagePeriod'] = 'Срок хранения не может превышать 14 дней и не ожет быть меньше 3 дней';
			}
			
			if (isset($this->request->post["storagePeriod"]) && ($this->request->post["storagePeriod"] > 14 || $this->request->post["storagePeriod"] < 3)) {
				$this->error['storagePeriod'] = 'Срок хранения не может превышать 14 дней и не может быть меньше 3 дней';
			}
			
			
            if (isset($this->request->post["maxDeliveryDays"]) && isset($this->request->post["minDeliveryDays"]) && (isset($this->request->post["type"]) && $this->request->post["type"] != 'RETAIL')) {
                            
                if($this->request->post["minDeliveryDays"] < 0 || $this->request->post["maxDeliveryDays"] > 60 || empty($this->request->post["minDeliveryDays"])){
                    $this->error['min_max_delivery_days'] = 'Срок доставки не может быть меньше нуля и больше 60 дней.';
                }else{
                    $this->api = new yandex_beru();   
                    $this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'),$this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
                    $component = $this->api->loadComponent('regionCompany');
                    $out = $this->api->sendData($component);
                    
                    if (isset($this->request->post["region_id"]) && $this->request->post["region_id"] != $out['region']['id']) {
                        if($this->request->post["minDeliveryDays"] <= 18 && ($this->request->post["maxDeliveryDays"] - $this->request->post["minDeliveryDays"]) > 4){
                            $this->error['other_region_delivery_1'] = 'Если мин.срок до 18 дней, разница не должна превышать четырех дней.';
                        }elseif($this->request->post["minDeliveryDays"] > 18 && ($this->request->post["minDeliveryDays"] * 2 > $this->request->post["maxDeliveryDays"])){
                            $this->error['other_region_delivery_2'] = 'Если мин.срок больше 18 дней, разница должна быть не больше чем в два раза';
                        }
                    }else{
                        if(($this->request->post["maxDeliveryDays"] - $this->request->post["minDeliveryDays"]) > 2){
                            $this->error['own_region_delivery'] = 'Для доставки по своему региону разница не должна превышать двух дней.';
                        }
                    }
                }
                
            }
			
			if (isset($this->request->post["sale_worktime"])) {
				foreach($this->request->post["sale_worktime"] as $items){
                    foreach($items as $item){
                        if(empty($item)){
                            $this->error['sale_worktime'] = 'При добавлении режимов работы заполните все поля времени работы';
                        }
                    }
				}            
			}

			if (!$this->request->post["region_id"]) {
				$this->error['region_id'] = 'Укажите город для определения региона, после чего нажмите клавишу Enter';       
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
