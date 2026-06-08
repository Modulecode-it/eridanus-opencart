<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplace extends Controller {
	private $error = array();
	private $api;
	private $version_info_link = 'https://cache-mskm902.cdn.yandex.net/download.cdn.yandex.net/market/opencart/version.txt';
	private $holidays_json_link = 'https://cache-mskm902.cdn.yandex.net/download.cdn.yandex.net/market/opencart/calendar2021.txt';
	
	public function index() {
		$this->api = new yandex_beru();
		
		$this->load->language('extension/module/yandex_marketplace');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/yandex_beru.css');
		
		$this->document->addStyle('view/javascript/jquery/datepick/css/jquery.datepick.css');
		$this->document->addScript('view/javascript/jquery/datepick/js/jquery.plugin.js');
		$this->document->addScript('view/javascript/jquery/datepick/js/jquery.datepick.js');
		$this->document->addScript('view/javascript/jquery/datepick/js/jquery.datepick-ru.js');
        
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('localisation/tax_class');
		$this->load->model('setting/store');
		$this->load->model('extension/module/yandex_beru');
        $this->document->addScript('view/javascript/yandex_market/version_modal.js');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if(isset($this->request->post['holidays_DBS'])){
				//Если отключили официальные выходные или они включены не записываем в базу официальные праздники как выборанные 
				if((empty($this->request->post['yandex_beru_official_holidays']) && !empty($this->config->get('yandex_beru_official_holidays'))) || !empty($this->request->post['yandex_beru_official_holidays'])){
					$ignore_officials = true;
				}else{
					$ignore_officials = false;
				}
				
				$this->model_extension_module_yandex_beru->addHolidays($this->request->post['holidays_DBS'], $ignore_officials);
			}
			
			$this->model_setting_setting->editSetting('yandex_beru', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

		}
		
		$settings = $this->model_setting_setting->getSetting('yandex_beru');
			
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
			'href' => $this->url->link('extension/module/yandex_marketplace', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/yandex_marketplace', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		
		$data['token'] = $this->session->data['user_token'];
		
		//Tab generals
		
		$data['api_yandex_fbs_link'] = HTTPS_CATALOG.'index.php?route=extension/module/yandex_market';
		$data['api_yandex_dbs_link'] = HTTPS_CATALOG.'index.php?route=extension/module/yandex_market_dbs';
		
		if (isset($this->request->post['yandex_beru_title'])) {
			$data['yandex_beru_title'] = $this->request->post['yandex_beru_title'];
		} elseif(isset($settings['yandex_beru_title'])) {
			$data['yandex_beru_title'] = $settings['yandex_beru_title'];
		} else {
			$data['yandex_beru_title'] = '';
		}
		
		if (isset($this->request->post['yandex_beru_tax_class_id'])) {
			$data['yandex_beru_tax_class_id'] = $this->request->post['yandex_beru_tax_class_id'];
		} elseif(isset($settings['yandex_beru_tax_class_id'])) {
			$data['yandex_beru_tax_class_id'] = $settings['yandex_beru_tax_class_id'];
		} else {
			$data['yandex_beru_tax_class_id'] = 0;
		}
		
		if (isset($this->request->post['yandex_beru_store'])) {
			$data['yandex_beru_store'] = $this->request->post['yandex_beru_store'];
		} elseif(isset($settings['yandex_beru_store'])) {
			$data['yandex_beru_store'] = $settings['yandex_beru_store'];
		} else {
			$data['yandex_beru_store'] = array();
		}
		
		if (isset($this->request->post['yandex_beru_sort_order'])) {
			$data['yandex_beru_sort_order'] = $this->request->post['yandex_beru_sort_order'];
		} elseif(isset($settings['yandex_beru_sort_order'])) {
			$data['yandex_beru_sort_order'] = $settings['yandex_beru_sort_order'];
		} else {
			$data['yandex_beru_sort_order'] = 0;
		}
		
		if (isset($this->request->post['yandex_beru_status'])) {
			$data['yandex_beru_status'] = $this->request->post['yandex_beru_status'];
		} elseif(isset($settings['yandex_beru_status'])) {
			$data['yandex_beru_status'] = $settings['yandex_beru_status'];
		} else {
			$data['yandex_beru_status'] = 0;
		}
		
		if (isset($this->request->post['yandex_beru_status_DBS'])) {
			$data['yandex_beru_status_DBS'] = $this->request->post['yandex_beru_status_DBS'];
		} elseif(isset($settings['yandex_beru_status_DBS'])) {
			$data['yandex_beru_status_DBS'] = $settings['yandex_beru_status_DBS'];
		} else {
			$data['yandex_beru_status_DBS'] = 0;
		}
		
		if (isset($this->request->post['yandex_beru_auth_token'])) {
			$data['yandex_beru_auth_token'] = $this->request->post['yandex_beru_auth_token'];
		} elseif(isset($settings['yandex_beru_auth_token'])) {
			$data['yandex_beru_auth_token'] = $settings['yandex_beru_auth_token'];
		} else {
			$data['yandex_beru_auth_token'] = '';
		}
		
		if (isset($this->request->post['yandex_beru_auth_token_DBS'])) {
			$data['yandex_beru_auth_token_DBS'] = $this->request->post['yandex_beru_auth_token_DBS'];
		} elseif(isset($settings['yandex_beru_auth_token_DBS'])) {
			$data['yandex_beru_auth_token_DBS'] = $settings['yandex_beru_auth_token_DBS'];
		} else {
			$data['yandex_beru_auth_token_DBS'] = '';
		}
		
		if (isset($this->request->post['yandex_beru_company_id'])) {
			$data['yandex_beru_company_id'] = $this->request->post['yandex_beru_company_id'];
		} elseif(isset($settings['yandex_beru_company_id'])) {
			$data['yandex_beru_company_id'] = $settings['yandex_beru_company_id'];
		} else {
			$data['yandex_beru_company_id'] = '';
		}
		
		if (isset($this->request->post['yandex_beru_company_id_DBS'])) {
			$data['yandex_beru_company_id_DBS'] = $this->request->post['yandex_beru_company_id_DBS'];
		} elseif(isset($settings['yandex_beru_company_id_DBS'])) {
			$data['yandex_beru_company_id_DBS'] = $settings['yandex_beru_company_id_DBS'];
		} else {
			$data['yandex_beru_company_id_DBS'] = '';
		}
		
		if (isset($this->request->post['yandex_beru_oauth_DBS'])) {
			$data['yandex_beru_oauth_DBS'] = $this->request->post['yandex_beru_oauth_DBS'];
		} elseif(isset($settings['yandex_beru_oauth_DBS'])) {
			$data['yandex_beru_oauth_DBS'] = $settings['yandex_beru_oauth_DBS'];
		} else {
			$data['yandex_beru_oauth_DBS'] = '';
		}
		
		if (isset($this->request->post['yandex_beru_oauth'])) {
			$data['yandex_beru_oauth'] = $this->request->post['yandex_beru_oauth'];
		} elseif(isset($settings['yandex_beru_oauth'])) {
			$data['yandex_beru_oauth'] = $settings['yandex_beru_oauth'];
		} else {
			$data['yandex_beru_oauth'] = '';
		}
		
		if (isset($this->request->post['yandex_beru_weight_kg'])) {
			$data['yandex_beru_weight_kg'] = $this->request->post['yandex_beru_weight_kg'];
		} elseif(isset($settings['yandex_beru_weight_kg'])) {
			$data['yandex_beru_weight_kg'] = $settings['yandex_beru_weight_kg'];
		} else {
			$data['yandex_beru_weight_kg'] = 0;
		}
		
		if (isset($this->request->post['yandex_beru_length_cm'])) {
			$data['yandex_beru_length_cm'] = $this->request->post['yandex_beru_length_cm'];
		} elseif(isset($settings['yandex_beru_length_cm'])) {
			$data['yandex_beru_length_cm'] = $settings['yandex_beru_length_cm'];
		} else {
			$data['yandex_beru_length_cm'] = 0;
		}
		
		if (!empty($this->request->post['yandex_beru_active_tab'])) {
			$data['active_tab'] = $this->request->post['yandex_beru_active_tab'];
		} else {
			$data['active_tab'] = 'general';
		}
		
		if (!empty($this->request->post['yandex_beru_active_tab_DBS'])) {
			$data['active_tab_DBS'] = $this->request->post['yandex_beru_active_tab_DBS'];
		} else {
			$data['active_tab_DBS'] = 'general-DBS';
		}

		
		if (!empty($this->request->post['yandex_beru_active_tab_main'])) {
			$data['active_tab_main'] = $this->request->post['yandex_beru_active_tab_main'];
		} else {
			$data['active_tab_main'] = 'main';
		}


		if (!empty($this->request->post['yandex_beru_service_name'])) {
			$data['yandex_beru_service_name'] = $this->request->post['yandex_beru_service_name'];
		} elseif(isset($settings['yandex_beru_service_name'])) {
			$data['yandex_beru_service_name'] = $settings['yandex_beru_service_name'];
		} else {
			$data['yandex_beru_service_name'] = '';
		}
		
		if (!empty($this->request->post['yandex_beru_services'])) {
			$yandex_beru_services = $this->request->post['yandex_beru_services'];
		} elseif(isset($settings['yandex_beru_services'])) {
			$yandex_beru_services = $settings['yandex_beru_services'];
		} else {
			$yandex_beru_services = array();
		}

		if (!empty($this->request->post['yandex_beru_service_id'])) {
			$data['yandex_beru_service_id'] = $this->request->post['yandex_beru_service_id'];
		} elseif(isset($settings['yandex_beru_service_id'])) {
			$data['yandex_beru_service_id'] = $settings['yandex_beru_service_id'];
		} else {
			$data['yandex_beru_service_id'] = 0;
		}
		
		if($data['yandex_beru_service_id'] && !in_array($data['yandex_beru_service_id'], $yandex_beru_services)){
			$yandex_beru_services[] = $data['yandex_beru_service_id'];	
		}
		
		if(!empty($yandex_beru_services)){
			foreach($yandex_beru_services as $yandex_beru_service){
				$service_info = $this->model_extension_module_yandex_beru->getDeliveryServiceInfo($yandex_beru_service);
				
				if($service_info){
					$data['yandex_beru_services'][] = [
							'service_id' => $yandex_beru_service,
							'name'       => $service_info,
					];
				}
			}
		}
			
		if (!empty($this->request->post['yandex_beru_subsidy_fbs'])) {
			$data['yandex_beru_subsidy_fbs'] = $this->request->post['yandex_beru_subsidy_fbs'];
		} elseif(isset($settings['yandex_beru_subsidy_fbs'])) {
			$data['yandex_beru_subsidy_fbs'] = $settings['yandex_beru_subsidy_fbs'];
		} else {
			$data['yandex_beru_subsidy_fbs'] = 0;
		}

		if (!empty($this->request->post['yandex_beru_subsidy_dbs'])) {
			$data['yandex_beru_subsidy_dbs'] = $this->request->post['yandex_beru_subsidy_dbs'];
		} elseif(isset($settings['yandex_beru_subsidy_dbs'])) {
			$data['yandex_beru_subsidy_dbs'] = $settings['yandex_beru_subsidy_dbs'];
		} else {
			$data['yandex_beru_subsidy_dbs'] = 0;
		}

		if (!empty($this->request->post['yandex_beru_check_5_dbs'])) {
			$data['yandex_beru_check_5_dbs'] = $this->request->post['yandex_beru_check_5_dbs'];
		} elseif(isset($settings['yandex_beru_check_5_dbs'])) {
			$data['yandex_beru_check_5_dbs'] = $settings['yandex_beru_check_5_dbs'];
		} else {
			$data['yandex_beru_check_5_dbs'] = 0;
		}

		if (!empty($this->request->post['yandex_beru_check_5_fbs'])) {
			$data['yandex_beru_check_5_fbs'] = $this->request->post['yandex_beru_check_5_fbs'];
		} elseif(isset($settings['yandex_beru_check_5_fbs'])) {
			$data['yandex_beru_check_5_fbs'] = $settings['yandex_beru_check_5_fbs'];
		} else {
			$data['yandex_beru_check_5_fbs'] = 0;
		}

		if (!empty($this->request->post['yandex_beru_offer_prices_type'])) {
			$data['offer_prices_type'] = $this->request->post['yandex_beru_offer_prices_type'];
		} elseif(isset($settings['yandex_beru_offer_prices_type'])) {
			$data['offer_prices_type'] = $settings['yandex_beru_offer_prices_type'];
		} else {
			$data['offer_prices_type'] = 'shop_sku';
		}
		
		if (!empty($this->request->post['yandex_beru_weekend_days_of_week'])) {
			$data['weekend_days_of_week'] = $this->request->post['yandex_beru_weekend_days_of_week'];
		} elseif(isset($settings['yandex_beru_weekend_days_of_week'])) {
			$data['weekend_days_of_week'] = $settings['yandex_beru_weekend_days_of_week'];
		} else {
			$data['weekend_days_of_week'] = [];
		}
		
		if (!empty($this->request->post['yandex_beru_official_holidays'])) {
			$data['official_holidays'] = $this->request->post['yandex_beru_official_holidays'];
		} elseif(isset($settings['yandex_beru_official_holidays'])) {
			$data['official_holidays'] = $settings['yandex_beru_official_holidays'];
		} else {
			$data['official_holidays'] = false;
		}
		
		
		$data['days_of_week'] = [1=>'Пн',2=>'Вт',3=>'Ср',4=>'Чт',5=>'Пт',6=>'Сб',7=>'Вс'];
		
		$data['holidays_DBS'] = $this->model_extension_module_yandex_beru->getHolidaysForInput($data['official_holidays']);
		
		$order_statuses = $this->getInfo()->getOfferStatuses();
		
		$data['statuses'] = array();
		
		foreach($order_statuses as $beru_status){
			
			$data['statuses'][$beru_status]['name'] = $this->language->get('order_status_'. $beru_status);
			
			if(!empty($settings['yandex_beru_statuses']) && isset($settings['yandex_beru_statuses'][$beru_status])){
				$data['statuses'][$beru_status]['val'] = $settings['yandex_beru_statuses'][$beru_status];
			}else{
				$data['statuses'][$beru_status]['val'] = false;
			}	
		}

		$order_statuses_dbs = $this->getInfo()->getOfferStatusesDbs();

		foreach($order_statuses_dbs as $beru_status_dbs){
			
			$data['statuses_dbs'][$beru_status_dbs]['name'] = $this->language->get('order_status_'. $beru_status_dbs);
			
			if(!empty($settings['yandex_beru_statuses_dbs']) && isset($settings['yandex_beru_statuses_dbs'][$beru_status_dbs])){
				$data['statuses_dbs'][$beru_status_dbs]['val'] = $settings['yandex_beru_statuses_dbs'][$beru_status_dbs];
			}else{
				$data['statuses_dbs'][$beru_status_dbs]['val'] = false;
			}	
		}

		$this->load->model('localisation/order_status');
		$data['opencart_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		
		$data['stores'] = array();
		$data['stores'][] = array(
			'store_id' => 0,
			'name'	   => $this->language->get('text_store_default')
		);
		
		$data['stores'] = array_merge($data['stores'], $this->model_setting_store->getStores());
		
		$actual_version = @file_get_contents($this->version_info_link);
		
		$version = $this->model_extension_module_yandex_beru->getVersionModule();
		
		if($actual_version !== false && $version != $actual_version){
			$data['text_update_notification'] = sprintf($this->language->get('text_update_notification'), $actual_version);
		}else{
			$data['text_update_notification'] = '';
		}
		
//		$holidays_json = @file_get_contents($this->holidays_json_link);
//		
//		$data['holiday_api_year'] = false;
//		
//		if($holidays_json){
//			$holidays = (array)json_decode($holidays_json);
//			
//			if(isset($holidays['year'])){
//				$data['holiday_api_year'] = $holidays['year'];
//			}
//		}
//		
//		if (!empty($this->request->post['yandex_beru_holidays_year'])) {
//			$data['holiday_year'] = $this->request->post['yandex_beru_holidays_year'];
//		} elseif(isset($settings['yandex_beru_holidays_year'])) {
//			$data['holiday_year'] = $settings['yandex_beru_holidays_year'];
//		} else {
//			$data['holiday_year'] = false;
//		}
//		
//		if($data['holiday_year'] == false || $data['holiday_api_year'] != $data['holiday_year']){
//			$data['holidays_update_notification'] = true;
//			
//			if($data['official_holidays']){
//				$data['error_holidays'] = $this->language->get('error_warning_holidays');
//			}
//		}
		
		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();
		
		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
		
//	Сопоставление полей для генерации прайслистов / загрузки товаров на беру
//	https://yandex.ru/support/partnermarket/offers.html
		
		$required_fields = $this->getInfo()->getRequiredOfferFields();

		if (isset($this->request->post['yandex_beru_fieldsets'])) {
			$fieldsets = $this->request->post['yandex_beru_fieldsets'];
		} elseif(isset($settings['yandex_beru_fieldsets'])) {
			$fieldsets = $settings['yandex_beru_fieldsets'];
		} else {
			$fieldsets = array();
		}
		
		$data['sources'] = array();
		
		foreach($this->getInfo()->getSources() as $source_key){
			$data['sources'][] = [
				'key'	=> $source_key,
				'name'	=> $this->language->get('text_source_'. $source_key)
			];
		}
		
		$data['required_fields'] = [];
		
		foreach($required_fields as $required_field_key => $required_field){
			
			if(!empty($fieldsets) && !empty($fieldsets[$required_field_key])){
				$source = $fieldsets[$required_field_key]['source'];
				$field = $fieldsets[$required_field_key]['field'];
				unset($fieldsets[$required_field_key]);
			}else{
				$source = 'general';
				$field = '';
			}
			
			$fields_arr = $this->model_extension_module_yandex_beru->getSourceFields(['source' => $source]);
			
			$fields = array();
			
			foreach($fields_arr as $fields_item){
				$fields[] = [
					'key'		=> $fields_item['key'],
					'name'		=> $fields_item['name'],
					'selected'	=> ($field == $fields_item['key'])?true:false
				];
			}
			
			$data['required_fields'][] = [
				'key'	=> $required_field_key,
				'name'	=> $this->language->get('text_field_name_'. $required_field_key),
				'info'	=> $this->language->get('text_field_info_'. $required_field_key),
				'field'	=> $fields,
				'source'=> $source
			];
			
		}
		
		$additional_fields = $this->getInfo()->getAdditionalOfferFields();
		
		$data['additional_fields'] = array();
		
		foreach($additional_fields as $additional_field_key => $additional_field){
			$data['additional_fields'][$additional_field_key] = [
				'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
				'info'	=> $this->language->get('text_field_info_'. $additional_field_key),
				'childs'=> $additional_field['childs']
			];
		}
		
		$data['additional_field_rows'] = array();
		
		if(!empty($fieldsets)){
			foreach($fieldsets as $field_key => $added_field){
				if(array_key_exists($field_key, $data['additional_fields'])){
					$childs_fields_data = array();

					if(!empty($data['additional_fields'][$field_key]['childs'])){
						$child_field_row_arr = $this->getInfo()->getFieldRowArr($field_key);

						foreach($child_field_row_arr as $child_field_item){
							$child_fields_arr = $this->model_extension_module_yandex_beru->getSourceFields(['source' => $added_field[$child_field_item]['source']]);

							$child_fields = array();

							foreach($child_fields_arr as $fields_item){
								$child_fields[] = [
									'key'		=> $fields_item['key'],
									'name'		=> $fields_item['name'],
									'selected'	=> ($added_field[$child_field_item]['field'] == $fields_item['key'])?true:false
								];
							}
							
							$childs_fields_data[] = [
								'key'	=> $child_field_item,
								'name'	=> $this->language->get('text_field_name_'. $field_key.'_'.$child_field_item),
								'info'	=> $this->language->get('text_field_info_'. $field_key.'_'.$child_field_item),
								'field'	=> $child_fields,
								'source'=> $added_field[$child_field_item]['source']
							];		
						}

					}else{
						$child_fields_arr = $this->model_extension_module_yandex_beru->getSourceFields(['source' => $added_field['source']]);

						$child_fields = array();

						foreach($child_fields_arr as $fields_item){
							$child_fields[] = [
								'key'		=> $fields_item['key'],
								'name'		=> $this->language->get('text_field_name_'. $fields_item['key']),
								'selected'	=> ($added_field['field'] == $fields_item['key'])?true:false
							];
						}
					}
					
					$data['additional_field_rows'][] = [
						'key' 		=> $field_key,
						'name'		=> $this->language->get('text_field_name_'. $field_key),
						'info' 		=> $this->language->get('text_field_info_'. $field_key),
						'field'		=> !empty($child_fields)?$child_fields:"",
						'source' 	=> !empty($added_field['source'])?$added_field['source']:"",
						'childs' 	=> $childs_fields_data
					];	
				}	
			}
		}


		$data['paymentMethods'] = array(
			'YANDEX'			=>	'банковской картой при оформлении',
			'APPLE_PAY'			=>	'Apple Pay при оформлении',
			'GOOGLE_PAY'		=>	'Google Pay при оформлении',
			'CARD_ON_DELIVERY'	=>	'банковской картой при получении',
			'CASH_ON_DELIVERY'	=>	'наличными при получении',
		);

		$data['user_token'] = $this->session->data['user_token'];
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$version_module = new yandex_beru;
		$data['version'] = $version_module->version;
		
		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace', $data));
	}
	
	public function getSourceFieldRow(){
		$this->load->language('extension/module/yandex_marketplace');
		
		$this->api = new yandex_beru();
		
		$this->load->model('extension/module/yandex_beru');
		
		if (($this->request->server['REQUEST_METHOD'] == 'GET')) {
			
			$row_key = !empty($this->request->get['row'])?$this->request->get['row']:"";
			
			$rows = $this->getInfo()->getFieldRowArr($row_key);
			$data['rows'] = array();
		
			foreach($rows as $row){
				$data['rows'][$row] = [
					'name'	=> $this->language->get('text_field_name_'.$row_key.'_'.$row),
					'info'	=> $this->language->get('text_field_info_'.$row_key.'_'.$row)
				];
			}	
			
			
			$data['row_data'] = $this->getInfo()->getFieldRow($row_key);
			$data['row_data']['name'] = $this->language->get('text_field_name_'.$row_key);
			$data['row_data']['info'] = $this->language->get('text_field_name_'.$row_key);
			
			$data['row_key'] = $row_key;
			
			$data['sources'] = array();
		
			foreach($this->getInfo()->getSources() as $source_key){
				$data['sources'][] = [
					'key'	=> $source_key,
					'name'	=> $this->language->get('text_source_'. $source_key)
				];
			}
			
			$data['fields'] = $this->model_extension_module_yandex_beru->getSourceFields(['source' => 'general']);
			$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/field_row_data', $data));
		}
	
	}
	
	public function getInfo() {

		static $instance;

		if (!$instance) {
			$instance = $this->api->loadComponent('info');
		}

		return $instance;
	}
	
	public function getSourceFields(){
		
		$this->load->model('extension/module/yandex_beru');
		
		$json = array();
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$json['success']['fields'] = $this->model_extension_module_yandex_beru->getSourceFields($this->request->post);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function refresh_holidays(){
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('setting/setting');
		
		$json = [];
		
		$holidays_json = @file_get_contents($this->holidays_json_link);
		
		if($holidays_json){
			$holidays = (array)json_decode($holidays_json);
			
			$shop_holidays_year = $this->config->get('yandex_beru_holidays_year');
			
			if(!empty($shop_holidays_year) && $shop_holidays_year == $holidays['year']){
				$json = [
					'success' => true,
					'text'    => 'Данные уже обновлены <i class="fa fa-check" aria-hidden="true"></i>',
				];
			}else{
				$this->model_extension_module_yandex_beru->deleteOfficalHolidays();
				
				foreach($holidays['month'] as $month => $days){
					foreach($days as $day){
						$this->model_extension_module_yandex_beru->addHoliday(['month'=>$month,'day'=>$day,'official'=>'1']);
					}
				}
				
				$this->model_setting_setting->editSettingValue('yandex_beru', 'yandex_beru_holidays_year', $holidays['year']);
				
				$json = [
					'success' => true,
					'text'    => 'Данные упешно обновлены <i class="fa fa-check" aria-hidden="true"></i>',
				];
			}
		}else{
			$json = [
					'success' => false,
					'text'    => 'Ошибка обновления',
				];
			
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function validateKey() {
		if (!$this->user->hasPermission('modify', 'extension/module/yandex_marketplace')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		$this->load->model('extension/module/yandex_beru');
		
		if(!$this->model_extension_module_yandex_beru->validateKeys($this->request->post)){
			$this->error['warning'] = $this->language->get('error_key');	
		}
		
		return !$this->error;
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/yandex_marketplace')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
//		if(!empty($this->request->post['yandex_beru_statuses'])){
//			foreach($this->request->post['yandex_beru_statuses'] as $yandex_beru_status){
//				if($yandex_beru_status == 0){
//					$this->error['warning'] = $this->language->get('error_empty_order_status');
//					break;
//				}
//			}
//		}

		
		if($this->request->post['yandex_beru_status'] == "1"){

			if(empty($this->request->post['yandex_beru_length_cm'])){
				$this->error['warning'] = $this->language->get('error_empty_length_cm');
			}
	
			if(empty($this->request->post['yandex_beru_weight_kg'])){
				$this->error['warning'] = $this->language->get('error_empty_weight_kg');
			}
			
			if(!empty($this->request->post['yandex_beru_fieldsets'])){
				foreach($this->request->post['yandex_beru_fieldsets'] as $yandex_beru_fieldset){
					if(isset($yandex_beru_fieldset['field'])){
						if(empty($yandex_beru_fieldset['field'])){
							$this->error['warning'] = $this->language->get('error_empty_fields');
							break;
						}
					}else{
						foreach($yandex_beru_fieldset as $yandex_beru_field_group_item){
							if(isset($yandex_beru_field_group_item['field'])){
								if(empty($yandex_beru_field_group_item['field'])){
									$this->error['warning'] = $this->language->get('error_empty_fields');
									break 2;
								}
							}	
						}
					}
				}
			}

		}

		return !$this->error;
	}
	
	public function install() {
		
		$this->load->model('extension/module/yandex_beru_install');
		
		$check_delivery_services = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_deliveryService'");
		
		if(empty($check_delivery_services->rows)){
			$this->db->query("CREATE TABLE " . DB_PREFIX . "yb_deliveryService( ".
			"service_id int(11) NOT NULL AUTO_INCREMENT, ".
			"name varchar(255) NOT NULL, ".
			"PRIMARY KEY (`service_id`)) "
			);

			$this->model_extension_module_yandex_beru_install->updateFillDeliveryService();
		}
		
		$check_table_1 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_history_price'");

		if(empty($check_table_1->rows)){
			$this->db->query("CREATE TABLE " . DB_PREFIX . "yb_history_price( ".
			"offer_id varchar(255) NOT NULL, ".
			"offer_name varchar(255) NOT NULL, ".
			"user int(11) NOT NULL, ".
			"price float NOT NULL, ".
			"date_update datetime NOT NULL)"
			);
		}
			
		$check_table_2 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_offers'");

		if(empty($check_table_2->rows)){
			$this->db->query("CREATE TABLE " . DB_PREFIX . "yb_offers( ".
			"`key` varchar(100) NOT NULL, ".
			"shopSku varchar(255) NOT NULL, ".
			"yandex_sku varchar(255) NOT NULL DEFAULT '', ".
			"yandex_category varchar(255) NOT NULL DEFAULT '', ".
			"status varchar(255) NOT NULL DEFAULT '', ".
			"marketSkuName varchar(255) NOT NULL DEFAULT '', ".
			"marketCategoryName varchar(255) NOT NULL DEFAULT '', ".
			"offer_price float NOT NULL DEFAULT '0', ".
			"minPriceOnBeru float NOT NULL DEFAULT '0', ".
			"maxPriceOnBeru float NOT NULL DEFAULT '0', ".
			"defaultPriceOnBeru float NOT NULL DEFAULT '0', ".
			"byboxPriceOnBeru float NOT NULL DEFAULT '0', ".
			"outlierPrice float NOT NULL DEFAULT '0', " . 
			"PRIMARY KEY (`key`)) "
			);
		}

		$check_table_3 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_product_group'");

		if(empty($check_table_3->rows)){
			$this->db->query("CREATE TABLE " . DB_PREFIX . "yb_product_group( ".
				"group_id int(11) NOT NULL AUTO_INCREMENT, ".
				"name varchar(255) NOT NULL, ".
				"filter_name text NULL DEFAULT NULL, ".
				"filter_model text NULL DEFAULT NULL, ".
				"filter_category text NULL DEFAULT NULL, ".
				"filter_product text NULL DEFAULT NULL, ".
				"filter_option text NULL DEFAULT NULL, ".
				"filter_price_from float NULL DEFAULT NULL, ".
				"filter_price_to float NULL DEFAULT NULL, ".
				"filter_quantity_from int(11) NULL DEFAULT NULL, ".
				"filter_quantity_to int(11) NULL DEFAULT NULL, ".
				"filter_status tinyint(1) NULL DEFAULT NULL, " . 
				"PRIMARY KEY (`group_id`)) "
			);
		}

		$check_table_4 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_product_to_product_group'");

		if(empty($check_table_4->rows)){
			$this->db->query("CREATE TABLE " . DB_PREFIX . "yb_product_to_product_group( ".
				"product_id int(11) NOT NULL , ".
				"group_id int(11) NOT NULL, ".
				"PRIMARY KEY (`product_id`, `group_id`))"
				
			);
		}

		$check_table_5 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_order_boxes'");

		if(empty($check_table_5->rows)){
			$this->db->query("CREATE TABLE " . DB_PREFIX . "yb_order_boxes( ".
				"box_id int(11) NOT NULL , ".
				"order_id int(11) NOT NULL , ".
				"weight int(64) NOT NULL , ".
				"width int(64) NOT NULL , ".
				"height int(64) NOT NULL , ".
				"depth int(64) NOT NULL , ".
				"market_box_id int(11) NOT NULL , ".
				"fulfilmentId varchar(128) NOT NULL , ".
				"group_id int(11) NOT NULL, ".
				"PRIMARY KEY (`box_id`))"
			);
		}
		
		$check_table_6 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_regions'");

		if(empty($check_table_6->rows)){
			$this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "yb_regions(
				  `region_id` int(11) NOT NULL,
				  `name` varchar(255) NOT NULL,
				  `type` varchar(255) NOT NULL,
				  `parent` int(11) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
			);
			
			$this->db->query("INSERT INTO `" . DB_PREFIX . "yb_regions` (`region_id`, `name`, `type`, `parent`) VALUES
				(11004, 'Республика Адыгея', 'REPUBLIC', 26),
				(26, 'Южный федеральный округ', 'COUNTRY_DISTRICT', 225),
				(225, 'Россия', 'COUNTRY', 0),
				(11111, 'Республика Башкортостан', 'REPUBLIC', 40),
				(40, 'Приволжский федеральный округ', 'COUNTRY_DISTRICT', 225),
				(11330, 'Республика Бурятия', 'REPUBLIC', 73),
				(73, 'Дальневосточный федеральный округ', 'COUNTRY_DISTRICT', 225),
				(10231, 'Республика Алтай', 'REPUBLIC', 59),
				(59, 'Сибирский федеральный округ', 'COUNTRY_DISTRICT', 225),
				(11010, 'Республика Дагестан', 'REPUBLIC', 102444),
				(102444, 'Северо-Кавказский федеральный округ', 'COUNTRY_DISTRICT', 225),
				(11012, 'Республика Ингушетия', 'REPUBLIC', 102444),
				(11013, 'Кабардино-Балкарская Республика', 'REPUBLIC', 102444),
				(11015, 'Республика Калмыкия', 'REPUBLIC', 26),
				(11020, 'Карачаево-Черкесская Республика', 'REPUBLIC', 102444),
				(10933, 'Республика Карелия', 'REPUBLIC', 17),
				(17, 'Северо-Западный федеральный округ', 'COUNTRY_DISTRICT', 225),
				(10939, 'Республика Коми', 'REPUBLIC', 17),
				(11077, 'Республика Марий Эл', 'REPUBLIC', 40),
				(11117, 'Республика Мордовия', 'REPUBLIC', 40),
				(11443, 'Республика Саха (Якутия)', 'REPUBLIC', 73),
				(11021, 'Республика Северная Осетия — Алания', 'REPUBLIC', 102444),
				(10233, 'Республика Тыва', 'REPUBLIC', 59),
				(11148, 'Удмуртская Республика', 'REPUBLIC', 40),
				(11340, 'Республика Хакасия', 'REPUBLIC', 59),
				(11024, 'Чеченская Республика', 'REPUBLIC', 102444),
				(11235, 'Алтайский край', 'REPUBLIC', 59),
				(10995, 'Краснодарский край', 'REPUBLIC', 26),
				(11309, 'Красноярский край', 'REPUBLIC', 59),
				(11409, 'Приморский край', 'REPUBLIC', 73),
				(11069, 'Ставропольский край', 'REPUBLIC', 102444),
				(11457, 'Хабаровский край', 'REPUBLIC', 73),
				(11375, 'Амурская область', 'REPUBLIC', 73),
				(10842, 'Архангельская область', 'REPUBLIC', 17),
				(10946, 'Астраханская область', 'REPUBLIC', 26),
				(10645, 'Белгородская область', 'REPUBLIC', 3),
				(3, 'Центральный федеральный округ', 'COUNTRY_DISTRICT', 225),
				(10650, 'Брянская область', 'REPUBLIC', 3),
				(10658, 'Владимирская область', 'REPUBLIC', 3),
				(10950, 'Волгоградская область', 'REPUBLIC', 26),
				(10853, 'Вологодская область', 'REPUBLIC', 17),
				(10672, 'Воронежская область', 'REPUBLIC', 3),
				(10687, 'Ивановская область', 'REPUBLIC', 3),
				(11266, 'Иркутская область', 'REPUBLIC', 59),
				(10857, 'Калининградская область', 'REPUBLIC', 17),
				(10693, 'Калужская область', 'REPUBLIC', 3),
				(11398, 'Камчатский край', 'REPUBLIC', 73),
				(11070, 'Кировская область', 'REPUBLIC', 40),
				(10699, 'Костромская область', 'REPUBLIC', 3),
				(11158, 'Курганская область', 'REPUBLIC', 52),
				(52, 'Уральский федеральный округ', 'COUNTRY_DISTRICT', 225),
				(10705, 'Курская область', 'REPUBLIC', 3),
				(10712, 'Липецкая область', 'REPUBLIC', 3),
				(11403, 'Магаданская область', 'REPUBLIC', 73),
				(10897, 'Мурманская область', 'REPUBLIC', 17),
				(11079, 'Нижегородская область', 'REPUBLIC', 40),
				(10904, 'Новгородская область', 'REPUBLIC', 17),
				(11316, 'Новосибирская область', 'REPUBLIC', 59),
				(11318, 'Омская область', 'REPUBLIC', 59),
				(11084, 'Оренбургская область', 'REPUBLIC', 40),
				(10772, 'Орловская область', 'REPUBLIC', 3),
				(11095, 'Пензенская область', 'REPUBLIC', 40),
				(11108, 'Пермский край', 'REPUBLIC', 40),
				(10926, 'Псковская область', 'REPUBLIC', 17),
				(11029, 'Ростовская область', 'REPUBLIC', 26),
				(10776, 'Рязанская область', 'REPUBLIC', 3),
				(11131, 'Самарская область', 'REPUBLIC', 40),
				(11146, 'Саратовская область', 'REPUBLIC', 40),
				(11450, 'Сахалинская область', 'REPUBLIC', 73),
				(11162, 'Свердловская область', 'REPUBLIC', 52),
				(10795, 'Смоленская область', 'REPUBLIC', 3),
				(10802, 'Тамбовская область', 'REPUBLIC', 3),
				(10819, 'Тверская область', 'REPUBLIC', 3),
				(11353, 'Томская область', 'REPUBLIC', 59),
				(10832, 'Тульская область', 'REPUBLIC', 3),
				(11176, 'Тюменская область', 'REPUBLIC', 52),
				(11153, 'Ульяновская область', 'REPUBLIC', 40),
				(11225, 'Челябинская область', 'REPUBLIC', 52),
				(21949, 'Забайкальский край', 'REPUBLIC', 73),
				(10841, 'Ярославская область', 'REPUBLIC', 3),
				(213, 'Москва', 'CITY', 1),
				(1, 'Москва и Московская область', 'REPUBLIC', 3),
				(2, 'Санкт-Петербург', 'CITY', 10174),
				(10174, 'Санкт-Петербург и Ленинградская область', 'REPUBLIC', 17),
				(10243, 'Еврейская автономная область', 'REPUBLIC', 73),
				(10176, 'Ненецкий автономный округ', 'REPUBLIC', 17),
				(11193, 'Ханты-Мансийский автономный округ - Югра', 'REPUBLIC', 52),
				(10251, 'Чукотский автономный округ', 'REPUBLIC', 73),
				(11232, 'Ямало-Ненецкий автономный округ', 'REPUBLIC', 52),
				(977, 'Республика Крым', 'REPUBLIC', 26),
				(959, 'Севастополь', 'CITY', 977);
			");
		}

		$check_table_7 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_cancel_orders_accept'");

		if(empty($check_table_7->rows)){
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "yb_cancel_orders_accept` ( ".
				  "`cancel_orders_accept_id` int(11) NOT NULL AUTO_INCREMENT, ".
				  "`order_id` int(11) NOT NULL, ".
				  "`market_order_id` int(11) NOT NULL, ".
				  "`cancel_status` varchar(75) NOT NULL, ".
				  "`notify_date` datetime NOT NULL, ".
				  "`order_type` varchar(11) NOT NULL, ".
				  "PRIMARY KEY (`cancel_orders_accept_id`))"
			);
		}
		
		$check_table_8 = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_outlets'");
		
		if(empty($check_table_8->rows)){
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "yb_outlets` ( ".
                "`id` int(11) NOT NULL,
                `shopOutletCode` int(11) NOT NULL,
                `name` varchar(255) NOT NULL,
                `type` varchar(65) NOT NULL,
                `visibility` varchar(65) NOT NULL,
                `isMain` tinyint(4) NOT NULL,
                `coords` varchar(255) NOT NULL,
                `address` text NOT NULL,
                `phones` varchar(255) NOT NULL,
                `workingSchedule` text NOT NULL,
                `deliveryRules` text NOT NULL,
                `storagePeriod` int(11) NOT NULL,
                `status` varchar(65) NOT NULL,
                `region` text NOT NULL,
                `workingTime` text NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
			);
		}
		
		$check_order_outlet_id = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='ym_outlet_id'");

		if(empty($check_order_outlet_id->rows)){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN ym_outlet_id int(11) NULL DEFAULT NULL");
		}
		
		$check_electronic_acceptance_certificate_code = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='electronic_acceptance_certificate_code'");

		if(empty($check_electronic_acceptance_certificate_code->rows)){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN electronic_acceptance_certificate_code varchar(255) NULL DEFAULT NULL");
		}
		
		$deliveryCourier = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='delivery_courier'");

		if(empty($deliveryCourier->rows)){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN delivery_courier text NULL DEFAULT NULL");
		}
		
		$vehicleNumber = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='vehicle_number'");

		if(empty($vehicleNumber->rows)){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN vehicle_number text NULL DEFAULT NULL");
		}
		
		$check_order_shipment_id = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='shipment_id'");

		if(empty($check_order_shipment_id->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN shipment_id int(11) NOT NULL DEFAULT '0'");
		}
		
		$check_order_shipment_scheme = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='shipment_scheme'");

		if(empty($check_order_shipment_scheme->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN shipment_scheme varchar(10) NOT NULL DEFAULT '' ");
		}

		$check_order_market_order_id = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='market_order_id'");

		if(empty($check_order_market_order_id->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN market_order_id int(11) NOT NULL DEFAULT '0'");
		}

		$check_order_shipment_date = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='shipment_date'");
		
		if(empty($check_order_shipment_date->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN shipment_date date NULL DEFAULT NULL");
		}
		
		$check_order_track_number = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='track_number'");
		
		if(empty($check_order_track_number->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN `track_number` varchar(255) NULL DEFAULT NULL");
		}
		
		$check_order_service_id = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='service_id'");
		
		if(empty($check_order_service_id->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN `service_id` int(11) NULL DEFAULT NULL");
		}
		
		$check_order_buyer_price = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='buyer-price'");
		
		if(empty($check_order_buyer_price->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN `buyer-price` float NULL DEFAULT NULL");
		}
		
		$check_order_subsidy = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='subsidy'");
		
		if(empty($check_order_subsidy->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN `subsidy` float NULL DEFAULT NULL");
		}
		
		$check_order_market_status = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='market_status'");
		
		if(empty($check_order_market_status->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN `market_status` varchar(100) NULL DEFAULT ''");
		}
		
		$check_order_market_substatus = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='market_substatus'");
		
		if(empty($check_order_market_substatus->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN `market_substatus` varchar(100) NULL DEFAULT ''");
		}

		$check_order_real_delivery_date = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='real_delivery_date'");
		
		if(empty($check_order_real_delivery_date->rows)){
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD COLUMN `real_delivery_date` DATE NULL DEFAULT NULL");
		}
		
		$check_order_is_fake_phone = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME='is_fake_phone'");
		
		if(empty($check_order_is_fake_phone->rows)){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN `is_fake_phone` BOOLEAN NULL DEFAULT FALSE");
		}

		$check_table_holidays = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_holidays'");

		if(empty($check_table_holidays->rows)){
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "yb_holidays` ( ".
				"`holiday_id` int(11) NOT NULL AUTO_INCREMENT, ".
				"`month` int(2) NOT NULL, ".
				"`day` int(2) NOT NULL, ".
				"`official` int(1) NOT NULL DEFAULT '0', ".
				"PRIMARY KEY (`holiday_id`))"
			);
		}
		
        $check_stock_offers = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "yb_stock_offers'");

		if(empty($check_stock_offers->rows)){
			$this->db->query("CREATE TABLE IF NOT EXISTS `oc_yb_stock_offers` (
                `key` varchar(255) NOT NULL,
                `last_update` datetime NOT NULL,
                `push_date` datetime NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
			);
		}
    }
}
