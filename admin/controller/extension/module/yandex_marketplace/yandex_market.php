<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceYandexMarket extends Controller {
	private $error = array();
	private $api;

	public function index() {
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('localisation/tax_class');
		$this->load->model('setting/store');
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('catalog/manufacturer');
		$this->load->model('localisation/currency');
		$this->load->model('setting/module');

		$this->document->addStyle('view/stylesheet/yandex_beru.css');

		$this->load->language('extension/module/yandex_marketplace');


		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('yandex_market', $this->request->post);
				$this->response->redirect($this->url->link('extension/module/yandex_marketplace/yandex_market/success', 'user_token=' . $this->session->data['user_token'], true));
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
				$this->response->redirect($this->url->link('extension/module/yandex_marketplace/yandex_market/success', 'user_token=' . $this->session->data['user_token'] . '&module_id=' .  $this->request->get['module_id'], true));
				
			}
			$this->session->data['success'] = $this->language->get('text_success');
		}
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}
		$this->api = new yandex_beru();
		$data = array();
		$data['user_token'] = $this->session->data['user_token'];
		if (isset($this->request->post['filtres'])) {
			$data['filtres'] = $this->request->post['filtres'];
		} elseif (!empty($module_info)) {
			$data['filtres'] = $module_info['filtres'];
		} else {
			$data['filtres'] = array();
		}
		if(!empty($data['filtres'])){
			
			foreach ($data['filtres'] as $key => $filter) {

				if(empty($filter['yandex_market_category_all']) && !empty($filter['yandex_market_category_list'])){
					$category = $filter['yandex_market_category_list'];
				} else {
					$category  = array();
				}

				$data['filtres'][$key]['market_cat_tree'] = $this->treeCat($key,$category); //категории

			}
		} else {

			$data['market_cat_tree'] = $this->treeCat('1', array()); //категории

		}
 
		$results = $this->model_catalog_manufacturer->getManufacturers(); //производители

		foreach ($results as $result) {
			$data['manufacturers'][] = array(
				'manufacturer_id' => $result['manufacturer_id'],
				'name'            => $result['name'],
				'sort_order'      => $result['sort_order'],
			);
		}

		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		if (isset($this->request->post['stock_status_id'])) {
			$data['stock_status_id'] = $this->request->post['stock_status_id'];
		} elseif (!empty($product_info)) {
			$data['stock_status_id'] = $product_info['stock_status_id'];
		} else {
			$data['stock_status_id'] = 0;
		}

		if (isset($this->request->post['short_name_shop'])) {
			$data['short_name_shop'] = $this->request->post['short_name_shop'];
		} elseif (!empty($module_info)) {
			$data['short_name_shop'] = $module_info['short_name_shop'];
		} else {
			$data['short_name_shop'] = '';
		}

		if (isset($this->request->post['full_name_company'])) {
			$data['full_name_company'] = $this->request->post['full_name_company'];
		} elseif (!empty($module_info)) {
			$data['full_name_company'] = $module_info['full_name_company'];
		} else {
			$data['full_name_company'] = '';
		}

		if (isset($this->request->post['oldprice'])) {
			$data['oldprice'] = $this->request->post['oldprice'];
		} elseif (!empty($module_info['oldprice'])) {
			$data['oldprice'] = $module_info['oldprice'];
		} else {
			$data['oldprice'] = '';
		}

		if (isset($this->request->post['enable_auto_discounts'])) {
			$data['enable_auto_discounts'] = $this->request->post['enable_auto_discounts'];
		} elseif (!empty($module_info['enable_auto_discounts'])) {
			$data['enable_auto_discounts'] = $module_info['enable_auto_discounts'];
		} else {
			$data['enable_auto_discounts'] = '';
		}

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
	
		if (isset($this->request->post['currency'])) {
			$data['currency'] = $this->request->post['currency'];
		} elseif (!empty($module_info)) {
			$data['currency'] = $module_info['currency'];
		} else {
			$data['currency'] = $this->config->get('config_currency');
		}
		
		$data['offer_variability'] = array(
            'id'=> 'ID',
            'scu'=> 'SKU',
		);
		
		if (isset($this->request->post['offer_id'])) {
			$data['offer_id'] = $this->request->post['offer_id'];
		} elseif (!empty($module_info) && isset($module_info['offer_id'])) {
			$data['offer_id'] = $module_info['offer_id'];
		} else {
			$data['offer_id'] = '';
		}
		
		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['name_file'])) {
			$data['name_file'] = $this->request->post['name_file'];
			$data['link'] = $this->request->post['link'];
			$data['link_cron'] = $this->request->post['link_cron'];
		} elseif (!empty($module_info)) {
			$data['name_file'] = $module_info['name_file'];
			if($module_info['cache_status'] == '1'){
				if($this->config->get('config_secure') == '1'){
					$data['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' .$module_info['name_file'] .'.xml';
					$data['link_cron'] = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
				} else {
					$data['link'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' .$module_info['name_file'] .'.xml';
					$data['link_cron'] = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
				}
			} else {
				if($this->config->get('config_secure') == '1'){
					$data['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
				} else {
					$data['link'] = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
				}

				$data['link_cron'] ='';
			}
			
		} else {
			$data['name_file'] = '';
			$data['link'] = '';
			$data['link_cron'] ='';
		}
		
		if (isset($this->request->post['cache_status']) == '1') {
			$data['cache_status'] = $this->request->post['cache_status'];
		} elseif (!empty($module_info) && $module_info['cache_status'] == '1') {
			$data['cache_status'] = $module_info['cache_status'];
		} else {
			$data['cache_status'] = '';
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($module_info)) {
			$data['image'] = $module_info['image'];
		} else {
			$data['image'] = 'main';
		}

		if (isset($this->request->post['delivery_options_main'])) {
			$data['delivery_options_main'] = $this->request->post['delivery_options_main'];
		} elseif (!empty($module_info)) {
			$data['delivery_options_main'] = $module_info['delivery_options_main'];
		} else {
			$data['delivery_options_main'] = '';
		}

		if (isset($this->request->post['pickup_options'])) {
			$data['pickup_options'] = $this->request->post['pickup_options'];
		} elseif (!empty($module_info)) {
			$data['pickup_options'] = $module_info['pickup_options'];
		} else {
			$data['pickup_options'] = '';
		}


		if (isset($this->request->post['type']) && $this->request->post['type'] != '0') {
			$data['type'] = $this->request->post['type'];
		} elseif (!empty($module_info)) {
			$data['type'] = $module_info['type'];
		} else {
			$data['type'] = '';
		}



		if($data['type'] != ''){

			if (isset($this->request->post['main_fields'])) {
				$main_fields = $this->request->post['main_fields'];
			} elseif (!empty($module_info)) {
				$main_fields = $module_info['main_fields'];
			} else {
				$main_fields = '';
			}

		

			foreach ($main_fields as $key => $main_field) {

				if (isset($main_field)) {
					$source[$key] = $main_field;
				} elseif (!empty($module_info['main_field'])) {
					$source[$key] = $module_info['main_fields'][$key];
				} else {
					$source[$key] = '';
				}
	
			}
			if (isset($this->request->post['all_fields'])) {
				$all_fields = $this->request->post['all_fields'];
			} elseif (!empty($module_info['all_fields'])) {
				$all_fields = $module_info['all_fields'];
			} else {
				$all_fields = '';
				$source_all = array();
			}

			if(!empty($all_fields)){
				foreach ($all_fields as $key => $all_field) {
					if(isset($all_field)){
						$source_all[$key] = $all_field;
					} elseif (!empty($module_info['all_fields'])) {
						$source_all[$key] = $module_info['main_fields'][$key];
					} else {
						$source_all[$key] = '';
					}
				}
			}
		}

		if (isset($this->request->post['log'])) {
			$data['log'] = $this->request->post['log'];
		} elseif (!empty($module_info['log'])) {
			$data['log'] = $module_info['log'];
		} else {
			$data['log'] = '1';
		}


		if (isset($this->request->post['type']) && $this->request->post['type'] != '0') {
			$data['format_form'] = $this->selectType($data['type'], $source, $source_all);
		} elseif (!empty($module_info)) {
			$data['format_form'] = $this->selectType($module_info['type'], $source, $source_all);
		} else {
			$data['format_form'] = '';
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['error_warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['short_name_shop'])) {
			$data['error_short_name_shop'] = $this->error['short_name_shop'];
		} else {
			$data['error_short_name_shop'] = '';
		}

		if (isset($this->error['full_name_company'])) {
			$data['error_full_name_company'] = $this->error['full_name_company'];
		} else {
			$data['error_full_name_company'] = '';
		}

		if (isset($this->error['name_fid'])) {
			$data['error_name_fid'] = $this->error['name_fid'];
		} else {
			$data['error_name_fid'] = '';
		}

		if (isset($this->error['name_file'])) {
			$data['error_name_file'] = $this->error['name_file'];
		} else {
			$data['error_name_file'] = '';
		}

		if (isset($this->error['name_file_lat'])) {
			$data['error_name_file_lat'] = $this->error['name_file_lat'];
		} else {
			$data['error_name_file_lat'] = '';
		}

		if (isset($this->error['type'])) {
			$data['error_type'] = $this->error['type'];
		} else {
			$data['error_type'] = '';
		}

		if (isset($this->error['main_delivery'])) {
			$data['error_main_delivery'] = $this->error['main_delivery'];
		} else {
			$data['error_main_delivery'] = '';
		}

		if(!empty($this->request->get['module_id'])){

			$data['delete'] = $this->url->link('extension/module/yandex_marketplace/yandex_market/delete', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);

		}

        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/yandex_marketplace/yanadex_market', $data));

	}

	public function getCategories(){

		$this->load->model('catalog/category');

		if (empty($this->categories)) {
            $categories = array();
            foreach ($this->model_catalog_category->getCategories(array('sort' => 'name')) as $category) {
                $names                                = explode('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', $category['name']);
                $category['name']                     = end($names);
                $categories[$category['parent_id']][] = $category;
            }
            $this->categories = $categories;
        }

        return $this->categories;

	}
	
	public function treeCat($filter_id = '1', array $checkedList, $inputName = ' '){

		if($inputName == " "){

			$inputName = 'name="filtres[' . $filter_id . '][yandex_market_category_list][]"';

		}
		
        $html = $this->treeFolder($this->getCategories(), 0, $checkedList, $inputName);

        return $html;
    }

    private function treeFolder($categories, $id, $checkedList, $inputAttr){


        if (!isset($categories[$id])) {
            return '';
        }
        $html = '';
        foreach ($categories[$id] as $category) {

			$checked = in_array($category['category_id'], $checkedList) ? ' checked' : '';
			
            $html    .= '<li>
            <span>
                <label>
                    <input type="checkbox" '.$inputAttr.' value="'.$category['category_id'].'" '.$checked.'>
                    '.$category['name'].'
                </label>
            </span>';
            if (isset($categories[$category['category_id']])) {
                $html .= '<ul class="yandex_market_category_tree_branch">'
                    .$this->treeFolder($categories, $category['category_id'], $checkedList, $inputAttr)
                    .'</ul>';
            }
            $html .= '</li>';
		}
		
        return $html;
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

	public function selectType($type = 0, $source = array(), $source_all = array()){

		if(!empty($this->request->post) or $type != '0'){

			$this->api = new yandex_beru();

			$this->load->model('setting/setting');
			$this->load->model('localisation/language');
			$this->load->model('localisation/tax_class');
			$this->load->model('setting/store');
			$this->load->model('extension/module/yandex_beru');

			$this->load->language('extension/module/yandex_marketplace');

			$data = array();
			

			if(!empty($this->request->post['source'])){

				$source_type = $this->request->post['source'];

			} else {

				$source_type = $type;

			}
			
			if($source_type == 'simplified'){//упрощенный формат

				$simplified_fields = $this->getInfo()->getMainFieldsSimplified();

				$data = $this->fields($simplified_fields,$source,$source_all);

				$additional_fields = $this->getInfo()->getFieldsSimplified();

				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> $this->language->get('text_field_info_'. $additional_field_key) == 'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}

				$data['user_token'] = $this->session->data['user_token'];

			} elseif($source_type == 'arbitrary'){//произвольный формат

				$arbitrary_fields = $this->getInfo()->getMainFieldsArbitrary();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$data['user_token'] = $this->session->data['user_token'];

				$additional_fields = $this->getInfo()->getFieldsArbitrary();
		
				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> (string)$this->language->get('text_field_info_'. $additional_field_key) == (string)'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}
				
			} elseif($source_type == 'alcohol'){//алкоголь

				$arbitrary_fields = $this->getInfo()->getMainFieldsAlcohol();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$data['user_token'] = $this->session->data['user_token'];

				$additional_fields = $this->getInfo()->getFieldsAlcohol();
		
				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> (string)$this->language->get('text_field_info_'. $additional_field_key) == (string)'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}

			} elseif($source_type == 'audiobooks'){//аудиокниги

				$arbitrary_fields = $this->getInfo()->getMainFieldsAudiobooks();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$data['user_token'] = $this->session->data['user_token'];

				$additional_fields = $this->getInfo()->getFieldsAudiobooks();
		
				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> (string)$this->language->get('text_field_info_'. $additional_field_key) == (string)'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}

			} elseif($source_type == 'eventTickets'){//Билеты на мероприятия

				$arbitrary_fields = $this->getInfo()->getMainFieldsEventTickets();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$data['user_token'] = $this->session->data['user_token'];

				$additional_fields = $this->getInfo()->getFieldsEventTickets();
		
				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> (string)$this->language->get('text_field_info_'. $additional_field_key) == (string)'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}

			} elseif($source_type == 'books'){//книги

				$arbitrary_fields = $this->getInfo()->getMainFieldsBooks();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$data['user_token'] = $this->session->data['user_token'];

				$additional_fields = $this->getInfo()->getFieldsBooks();
		
				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){

					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> $this->language->get('text_field_info_'. $additional_field_key) == 'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}
			
			} elseif($source_type == 'medicine'){//Лекарства

				$arbitrary_fields = $this->getInfo()->getMainFieldsMedicine();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$data['user_token'] = $this->session->data['user_token'];

				$additional_fields = $this->getInfo()->getFieldsMedicine();
		
				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> (string)$this->language->get('text_field_info_'. $additional_field_key) == (string)'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}

			} elseif($source_type == 'musicVideo'){//Музыкальная и видеопродукция

				$arbitrary_fields = $this->getInfo()->getMainFieldsmusicVideo();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$data['user_token'] = $this->session->data['user_token'];

				$additional_fields = $this->getInfo()->getFieldsmusicVideo();
		
				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> (string)$this->language->get('text_field_info_'. $additional_field_key) == (string)'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}
			
			} elseif($source_type == 'tours'){//туры

				$arbitrary_fields = $this->getInfo()->getMainFieldsmusicTours();

				$data = $this->fields($arbitrary_fields, $source,$source_all);

				$additional_fields = $this->getInfo()->getFieldsmusicTours();

				$data['user_token'] = $this->session->data['user_token'];

				$data['additional_fields'] = array();
		
				foreach($additional_fields as $additional_field_key => $additional_field){
					$data['additional_fields'][$additional_field_key] = [
						'name'	=> $this->language->get('text_field_name_'. $additional_field_key),
						'info'	=> (string)$this->language->get('text_field_info_'. $additional_field_key) == (string)'text_field_info_'. $additional_field_key ? '' : $this->language->get('text_field_info_'. $additional_field_key),
						'childs'=> $additional_field['childs']
					];
				}

			
			}

			if (isset($this->error['param_name'])) {
				$data['error_param_name'] = $this->error['param_name'];
			} else {
				$data['error_param_name'] = '';
			}

			if(!empty( $this->request->post['source'])){

				$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/main_fields', $data));

			} else {

				return 	$this->load->view('extension/module/yandex_marketplace/main_fields', $data);

			}
		}
	}


	
	
	public function fields($form_fields, $fieldsets = array(),$fieldsets_all = array()){
		$this->load->language('extension/module/yandex_marketplace');

		$data['sources'] = array();
		
		foreach($this->getInfo()->getSources() as $source_key){
			$data['sources'][] = [
				'key'	=> $source_key,
				'name'	=> $this->language->get('text_source_'. $source_key)
			];
		}

		$data['form_fields'] = [];

		foreach($form_fields as $form_field_key => $form_field){

			if(!empty($fieldsets) && !empty($fieldsets[$form_field_key])){
				$source = $fieldsets[$form_field_key]['source'];
				$field = $fieldsets[$form_field_key]['field'];
				unset($fieldsets[$form_field_key]);
			}else{
				$source = 'general';
				$field = '';
			}

			if (isset($this->error['model'])) {
				$data['error_model'] = $this->error['model'];
			} else {
				$data['error_model'] = '';
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

				$data['form_fields'][] = [
					'key'		=> $form_field_key,
					'name'		=> $this->language->get('text_field_name_'. $form_field_key),
					'info'		=>(string)$this->language->get('text_field_info_'. $form_field_key) == (string)'text_field_info_'. $form_field_key ? '' : $this->language->get('text_field_info_'. $form_field_key),
					'field'		=> $fields,
					'source'	=> $source,
					'required' 	=> !empty($form_field['required']) ? $form_field['required'] : false,
					'unique'	=> !empty($form_field['unique']) ? $form_field['unique'] : '',
					'error'		=> !empty($this->error[$form_field_key]) ? $this->error[$form_field_key] : '',
				];

		}


		foreach($fieldsets_all as $fieldset_all_key => $fieldset_all){

			if(!empty($fieldsets_all) && !empty($fieldsets_all[$fieldset_all_key])){
				$source = $fieldsets_all[$fieldset_all_key]['source'];
				$field = $fieldsets_all[$fieldset_all_key]['field'];
				$unit = !empty($fieldsets_all[$fieldset_all_key]['unit']) ? $fieldsets_all[$fieldset_all_key]['unit'] : '';
				$name_param = !empty($fieldsets_all[$fieldset_all_key]['name_param']) ? $fieldsets_all[$fieldset_all_key]['name_param'] : '';
				unset($fieldsets_all[$fieldset_all_key]);
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


			if(!empty($fieldset_all['childs'])){

				foreach ($fieldset_all['childs'] as $key_child => $child) {

					$fields = array();

					foreach($fields_arr as $fields_item){
						$fields[] = [
							'key'		=> $fields_item['key'],
							'name'		=> $fields_item['name'],
							'selected'	=> ($child['field'] == $fields_item['key'])?true:false
						];
					}
					$childs[] = [
						'key'		=> $key_child,
						'name'		=> $this->language->get('text_field_name_'. $key_child),
						'info'		=>(string)$this->language->get('text_field_info_'. $key_child) == (string)'text_field_info_'. $key_child ? '' : $this->language->get('text_field_info_'. $key_child),
						'field'		=> $fields,
						'source'	=> $child['source'],
					];
				}
			
			}
			
			
			$data['fieldsets_all'][] = [
				'key'		=> $fieldset_all_key,
				'name'		=> $this->language->get('text_field_name_'. $fieldset_all_key),
				'info'		=>(string)$this->language->get('text_field_info_'. $fieldset_all_key) == (string)'text_field_info_'. $fieldset_all_key ? '' : $this->language->get('text_field_info_'. $fieldset_all_key),
				'field'		=> $fields,
				'source'	=> $source,
				'unit'		=> $unit,
				'name_param'=> $name_param,
				'required' 	=> !empty($fieldsets_all['required']) ? $fieldsets_all['required'] : false,
				'unique'	=> !empty($fieldsets_all['unique']) ? $fieldsets_all['unique'] : '',
				'childs'	=> !empty($childs) ? $childs : '',
			];

		}

		return $data;
	}

	public function getSourceFieldRow(){


		$this->load->language('extension/module/yandex_marketplace');
		
		$this->api = new yandex_beru();
		
		$this->load->model('extension/module/yandex_beru');
		
		if (($this->request->server['REQUEST_METHOD'] == 'GET')) {
			
			$row_key = !empty($this->request->get['row'])?$this->request->get['row']:"";

			$data['row_data']['name'] = $this->language->get('text_field_name_'.$row_key);
			$data['row_data']['info'] = (string)$this->language->get('text_field_info_'. $row_key) == (string)'text_field_info_'. $row_key ? '' : $this->language->get('text_field_info_'. $row_key);

			$child_field_row_arr = $this->getInfo()->getFieldRowArr_market($row_key);

			foreach ($child_field_row_arr as $key => $value) {
				$data['row_data']['childs'][$key]['name'] = $this->language->get('text_field_name_'.$value);
				$data['row_data']['childs'][$key]['info'] = (string)$this->language->get('text_field_info_'. $value) == (string)'text_field_info_'. $value ? '' : $this->language->get('text_field_info_'. $value);
				$data['row_data']['childs'][$key]['row_key'] = $value;
			}

			$data['row_key'] = $row_key;
			
			$data['sources'] = array();
		
			foreach($this->getInfo()->getSources() as $source_key){
				$data['sources'][] = [
					'key'	=> $source_key,
					'name'	=> $this->language->get('text_source_'. $source_key)
				];
			}

			$data['fields'] = $this->model_extension_module_yandex_beru->getSourceFields(['source' => 'general']);

			$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/field_row_data_market', $data));
		}
	
	}

	public function addFilter(){

		$this->load->model('catalog/manufacturer');
		$this->load->model('localisation/currency');
		$this->load->model('localisation/stock_status');

		$this->load->language('extension/module/yandex_marketplace');

		$data['user_token'] = $this->session->data['user_token'];

		$filter_number = $this->request->post['id'] + 1;

		$data['market_cat_tree'] = $this->treeCat($filter_number,array()); //категории


		$results = $this->model_catalog_manufacturer->getManufacturers(); //производители

		foreach ($results as $result) {
			$data['manufacturers'][] = array(
				'manufacturer_id' => $result['manufacturer_id'],
				'name'            => $result['name'],
				'sort_order'      => $result['sort_order'],
			);
		}

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		if (isset($this->request->post['stock_status_id'])) {
			$data['stock_status_id'] = $this->request->post['stock_status_id'];
		} elseif (!empty($product_info)) {
			$data['stock_status_id'] = $product_info['stock_status_id'];
		} else {
			$data['stock_status_id'] = 0;
		}

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$data['filter_number'] = $filter_number;

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/filter_tab', $data));

	}

	public function delete(){

		$this->load->model('extension/module/yandex_beru');

		 $this->model_extension_module_yandex_beru->delete($this->request->get['module_id']);

		 $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));

	}

	public function success(){
		
		$this->load->model('extension/module/yandex_beru');
		$this->load->model('setting/module');

		$this->document->addStyle('view/stylesheet/yandex_beru.css');

		if(!empty($this->request->get['module_id'])){

			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);

		} else {

			$module_info = $this->model_extension_module_yandex_beru->getLastYandex();

		}

		if($module_info['cache_status'] == '1'){
			if($this->config->get('config_secure') == '1'){
				$data['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' .$module_info['name_file'];
				$data['link_cron'] = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
			} else {
				$data['link'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' .$module_info['name_file'];
				$data['link_cron'] = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
			}
		} else {
			if($this->config->get('config_secure') == '1'){
				$data['link'] = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
			} else {
				$data['link'] = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?route=extension/module/yandex_market/xml&fid=' . $module_info['name_file'];
			}

			$data['link_cron'] ='';
		}

		$file = DIR_LOGS . 'yandex_module_xml_' .  $module_info['name_file'] . '.log';

		if(file_exists($file)){
			$data['log'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
		} else{
			$data['log'] = '';
		}

		$data['cache_status'] = $module_info['cache_status'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/success', $data));

	}


	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/banner')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['short_name_shop'])) {
			$this->error['short_name_shop'] = $this->language->get('error_short_name_shop');
		}

		if (empty($this->request->post['full_name_company'])) {
			$this->error['full_name_company'] = $this->language->get('error_full_name_company');
		}

		if (empty($this->request->post['name'])) {
			$this->error['name_fid'] = $this->language->get('error_name_fid');
		}

		if (empty($this->request->post['name_file'])) {
			$this->error['name_file'] = $this->language->get('error_name_file');
		} else {
			if (preg_match("/[a-zA-Z]+/",$this->request->post['name_file']) == 0 ) {
				$this->error['name_file_lat'] = $this->language->get('error_name_file_lat');
			}
		}

		foreach ($this->request->post['filtres'] as $filter) {

			foreach ($filter['delivery_options'] as $delivery_option) {
				if(!empty($delivery_option) and $delivery_option['delivery_options_cost'] != '' and $delivery_option['delivery_options_days']){
					$delivery = true;
				}
			}
		}
	
		foreach ($this->request->post['delivery_options_main'] as $delivery_option_main) {
			if(!empty($delivery_option_main) and $delivery_option_main['delivery_options_cost'] != '' and $delivery_option_main['delivery_options_days']){
				$main_delivery = true;
			}
		}

		if((!isset($main_delivery) && isset($main_delivery)) or (isset($delivery) && !isset($main_delivery))){
			$this->error['main_delivery'] =  $this->language->get('error_main_delivery');
		}
		

		if (empty($this->request->post['type']) or $this->request->post['type'] == "0") {
			$this->error['type'] = $this->language->get('error_type');

		} elseif($this->request->post['type'] == "arbitrary"){

			if($this->request->post['main_fields']['model']['source'] == "0" or $this->request->post['main_fields']['model']['field'] == "0"){
				$this->error['model'] = $this->language->get('error_model');
			}

			if($this->request->post['main_fields']['typePrefix']['source'] == "0" or $this->request->post['main_fields']['typePrefix']['field'] == "0"){
				$this->error['typePrefix'] = $this->language->get('error_typePrefix');

			}
			
			if($this->request->post['main_fields']['vendor']['source'] == "0" or $this->request->post['main_fields']['vendor']['field'] == "0"){
				$this->error['vendor'] = $this->language->get('error_vendor_market');

			}

		} elseif($this->request->post['type'] == "simplified"){

			if($this->request->post['main_fields']['name']['source'] == "0" or $this->request->post['main_fields']['name']['field'] == "0"){
				$this->error['name'] =  $this->language->get('error_name'); 

			}

		} elseif($this->request->post['type'] == "alcohol"){

			if($this->request->post['main_fields']['name']['source'] == "0" or $this->request->post['main_fields']['name']['field'] == "0"){
				$this->error['name'] =  $this->language->get('error_name'); 

			}

			if($this->request->post['main_fields']['vendor']['source'] == "0" or $this->request->post['main_fields']['vendor']['field'] == "0"){
				$this->error['vendor'] = $this->language->get('error_vendor_market');

			}

			if($this->request->post['main_fields']['barcode']['source'] == "0" or $this->request->post['main_fields']['barcode']['field'] == "0"){
				$this->error['barcode'] = $this->language->get('error_barcode');

			}

			if($this->request->post['main_fields']['param']['source'] == "0" or $this->request->post['main_fields']['param']['field'] == "0"){
				$this->error['param'] = $this->language->get('error_param');

			}

		}  elseif($this->request->post['type'] == "audiobooks"){

			if($this->request->post['main_fields']['name']['source'] == "0" or $this->request->post['main_fields']['name']['field'] == "0"){
				$this->error['name'] = $this->language->get('error_name');

			}

			if($this->request->post['main_fields']['publisher']['source'] == "0" or $this->request->post['main_fields']['publisher']['field'] == "0"){
				$this->error['publisher'] = $this->language->get('error_publisher');

			}

			if($this->request->post['main_fields']['age']['source'] == "0" or $this->request->post['main_fields']['age']['field'] == "0"){
				$this->error['age'] = $this->language->get('error_age');

			}

		} elseif($this->request->post['type'] == "eventTickets"){

			if($this->request->post['main_fields']['name']['source'] == "0" or $this->request->post['main_fields']['name']['field'] == "0"){
				$this->error['name'] = $this->language->get('error_name');

			}

			if($this->request->post['main_fields']['place']['source'] == "0" or $this->request->post['main_fields']['place']['field'] == "0"){
				$this->error['place'] = $this->language->get('error_place');

			}

			if($this->request->post['main_fields']['date']['source'] == "0" or $this->request->post['main_fields']['date']['field'] == "0"){
				$this->error['date'] = $this->language->get('error_date');

			}

		} elseif($this->request->post['type'] == "books"){

			if($this->request->post['main_fields']['name']['source'] == "0" or $this->request->post['main_fields']['name']['field'] == "0"){
				$this->error['name'] = $this->language->get('error_name');

			}

			if($this->request->post['main_fields']['publisher']['source'] == "0" or $this->request->post['main_fields']['publisher']['field'] == "0"){
				$this->error['publisher'] = $this->language->get('error_publisher');

			}

			if($this->request->post['main_fields']['age']['source'] == "0" or $this->request->post['main_fields']['age']['field'] == "0"){
				$this->error['age'] = $this->language->get('error_age');

			}

		} elseif($this->request->post['type'] == "medicine"){

			if($this->request->post['main_fields']['name']['source'] == "0" or $this->request->post['main_fields']['name']['field'] == "0"){
				$this->error['name'] = $this->language->get('error_name');

			}

			if($this->request->post['main_fields']['pickup']['source'] == "0" or $this->request->post['main_fields']['pickup']['field'] == "0"){
				$this->error['pickup'] = $this->language->get('error_pickup');

			}

			if($this->request->post['main_fields']['delivery']['source'] == "0" or $this->request->post['main_fields']['delivery']['field'] == "0"){
				$this->error['delivery'] = $this->language->get('error_delivery');

			}

		} elseif($this->request->post['type'] == "musicVideo"){

			if($this->request->post['main_fields']['title']['source'] == "0" or $this->request->post['main_fields']['title']['field'] == "0"){
				$this->error['title'] = $this->language->get('error_title');

			}

		} elseif($this->request->post['type'] == "musicTours"){

			if($this->request->post['main_fields']['name']['source'] == "0" or $this->request->post['main_fields']['name']['field'] == "0"){
				$this->error['name'] = $this->language->get('error_name');

			}

			if($this->request->post['main_fields']['days']['source'] == "0" or $this->request->post['main_fields']['days']['field'] == "0"){
				$this->error['days'] = $this->language->get('error_days');

			}

			if($this->request->post['main_fields']['included']['source'] == "0" or $this->request->post['main_fields']['included']['field'] == "0"){
				$this->error['included'] = $this->language->get('error_included');

			}

			if($this->request->post['main_fields']['transport']['source'] == "0" or $this->request->post['main_fields']['transport']['field'] == "0"){
				$this->error['transport'] = $this->language->get('error_transport');

			}

		} 

		if(!empty($this->request->post['all_fields']['param']) and (empty($this->request->post['all_fields']['param']['name_param']) or $this->request->post['all_fields']['param']['name_param'] == '')){

			$this->error['param_name'] = $this->language->get('error_param_name');

		}


		return !$this->error;
	}

}
