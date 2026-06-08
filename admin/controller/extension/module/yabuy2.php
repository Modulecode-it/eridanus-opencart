<?php
include(DIR_APPLICATION.'/../yaorder/config.php');

class ControllerExtensionModuleYabuy2 extends Controller {
	private $tdata = array();
	private $error = array();
	private $oauth_id = APP_ID;
	
	public function index() { 
		$this->load->language("extension/module/yabuy");
		$this->load->language("extension/module/yabuy2");

		$this->document->setTitle($this->language->get("heading_title")); 
		
		$this->load->model("setting/setting");
				
		if (($this->request->server["REQUEST_METHOD"] == "POST") && $this->validate()) {
			if (strpos($this->request->post['yabuy2_yacompany'], '-') > 0) {
				$yacompany_arr = explode('-', $this->request->post['yabuy2_yacompany']);
				$yacompany = trim($yacompany_arr[1]);
			}
			else {
				$yacompany = trim($this->request->post['yabuy2_yacompany']);
			}
			$this->request->post['yabuy2_yacompany'] = $yacompany;
			$this->request->post['yabuy2_login'] = trim($this->request->post['yabuy2_login']);
			$this->request->post['yabuy2_token'] = trim($this->request->post['yabuy2_token']);
			$this->model_setting_setting->editSetting("yabuy2", $this->request->post);		
			$this->model_setting_setting->editSetting("module_yabuy2", array('module_yabuy2_status'=>$this->request->post['module_yabuy2_status']));		
					
			$this->session->data["success"] = $this->language->get("text_success");
						
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
		
		$this->tdata["yandex_oauth_id"] = $this->oauth_id;
		$this->tdata["is_token_exists"] = false;
		$filename = realpath(DIR_APPLICATION.'../yaorder').'/t_'.$this->oauth_id.'.token';
		if (is_file($filename)) {
			$token = file_get_contents($filename);
			$this->tdata["is_token_exists"] = $token;
		}
		
		$this->setLanguage();

		//errors
		if (isset($this->error["warning"])) {
			$this->tdata["error_warning"] = $this->error["warning"];
		} else {
			$this->tdata["error_warning"] = "";
		}
		
		//breadcrumbs
		$this->tdata["breadcrumbs"] = array();

   		$this->tdata["breadcrumbs"][] = array(
       		"text"      => $this->language->get("text_home"),
			"href"      => $this->url->link("common/home", "user_token=" . $this->session->data["user_token"], "SSL"),
      		"separator" => false
   		);

   		$this->tdata["breadcrumbs"][] = array(
       		"text"      => $this->language->get("text_module"),
			"href"      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', "SSL"),
      		"separator" => " :: "
   		);
		
   		$this->tdata["breadcrumbs"][] = array(
       		"text"      => $this->language->get("heading_title"),
			"href"      => $this->url->link("extension/module/yabuy2", "user_token=" . $this->session->data["user_token"], "SSL"),
      		"separator" => " :: "
   		);
		
		$this->tdata["action"] = $this->url->link("extension/module/yabuy2", "user_token=" . $this->session->data["user_token"], "SSL");
		
		$this->tdata["cancel"] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', "SSL");
		
		if (isset($this->request->post['module_yabuy2_status'])) {
			$this->tdata['module_yabuy2_status'] = $this->request->post['module_yabuy2_status'];
		} else {
			$this->tdata['module_yabuy2_status'] = $this->config->get('module_yabuy2_status');
		}

		if (isset($this->request->post['yabuy2_dbs'])) {
			$this->tdata['yabuy2_dbs'] = $this->request->post['yabuy2_dbs'];
		} else {
			$this->tdata['yabuy2_dbs'] = $this->config->get('yabuy2_dbs');
		}
		
		if (isset($this->request->post['yabuy2_yacompany'])) {
			$this->tdata['yabuy2_yacompany'] = $this->request->post['yabuy2_yacompany'];
		} else {
			$this->tdata['yabuy2_yacompany'] = $this->config->get('yabuy2_yacompany');
		}

		if (isset($this->request->post['yabuy2_token'])) {
			$this->tdata['yabuy2_token'] = $this->request->post['yabuy2_token'];
		} else {
			$this->tdata['yabuy2_token'] = $this->config->get('yabuy2_token');
		}
		
		if (isset($this->request->post['yabuy2_login'])) {
			$this->tdata['yabuy2_login'] = $this->request->post['yabuy2_login'];
		} else {
			$this->tdata['yabuy2_login'] = $this->config->get('yabuy2_login');
		}
		
		if (isset($this->request->post['yabuy2_app_url'])) {
			$this->tdata['yabuy2_app_url'] = $this->request->post['yabuy2_app_url'];
		} else {
			$this->tdata['yabuy2_app_url'] = $this->config->get('yabuy2_app_url');
		}

		if (isset($this->request->post['yabuy2_long_id'])) {
			$this->tdata['yabuy2_long_id'] = $this->request->post['yabuy2_long_id'];
		} else {
			$this->tdata['yabuy2_long_id'] = $this->config->get('yabuy2_long_id');
		}

		//+++ Соответствие статусов +++
		$this->load->model('localisation/order_status');
    	$this->tdata['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['yabuy2_unpaid_status'])) {
			$this->tdata['yabuy2_unpaid_status'] = $this->request->post['yabuy2_unpaid_status'];
		} else {
			$this->tdata['yabuy2_unpaid_status'] = $this->config->get('yabuy2_unpaid_status');
		}
		
		if (isset($this->request->post['yabuy2_procesing_status'])) {
			$this->tdata['yabuy2_processing_status'] = $this->request->post['yabuy2_processing_status'];
		} else {
			$this->tdata['yabuy2_processing_status'] = $this->config->get('yabuy2_processing_status');
		}
		
		if (isset($this->request->post['yabuy2_delivery_status'])) {
			$this->tdata['yabuy2_delivery_status'] = $this->request->post['yabuy2_delivery_status'];
		} else {
			$this->tdata['yabuy2_delivery_status'] = $this->config->get('yabuy2_delivery_status');
		}
		
		if (isset($this->request->post['yabuy2_pickup_status'])) {
			$this->tdata['yabuy2_pickup_status'] = $this->request->post['yabuy2_pickup_status'];
		} else {
			$this->tdata['yabuy2_pickup_status'] = $this->config->get('yabuy2_pickup_status');
		}
		
		if (isset($this->request->post['yabuy2_delivered_status'])) {
			$this->tdata['yabuy2_delivered_status'] = $this->request->post['yabuy2_delivered_status'];
		} else {
			$this->tdata['yabuy2_delivered_status'] = $this->config->get('yabuy2_delivered_status');
		}
		
		if (isset($this->request->post['yabuy2_cancelled_status'])) {
			$this->tdata['yabuy2_cancelled_status'] = $this->request->post['yabuy2_cancelled_status'];
		} else {
			$this->tdata['yabuy2_cancelled_status'] = $this->config->get('yabuy2_cancelled_status');
		}
		//--- Соответствие статусов ---
		
		//+++ DBS +++
		if (isset($this->request->post['yabuy2_weekend_sat'])) {
			$this->tdata['yabuy2_weekend_sat'] = $this->request->post['yabuy2_weekend_sat'];
		} else {
			$this->tdata['yabuy2_weekend_sat'] = $this->config->get('yabuy2_weekend_sat');
		}
		if (isset($this->request->post['yabuy2_weekend_sun'])) {
			$this->tdata['yabuy2_weekend_sun'] = $this->request->post['yabuy2_weekend_sun'];
		} else {
			$this->tdata['yabuy2_weekend_sun'] = $this->config->get('yabuy2_weekend_sun');
		}
		
        /*
		$this->tdata['modules'] = $this->getShippingModules();
		
		$this->tdata["yabuy2_modules"] = array();
		if (isset($this->request->post["yabuy2_modules"])) {
			$this->tdata["yabuy2_modules"] = $this->request->post["yabuy2_modules"];
		} elseif ($this->config->get("yabuy2_modules")) { 
			$this->tdata["yabuy2_modules"] = $this->config->get("yabuy2_modules");
		}
        */
		
		$this->tdata["yabuy2_deliveries"] = array();
		if (isset($this->request->post["yabuy2_deliveries"])) {
			$this->tdata["yabuy2_deliveries"] = $this->request->post["yabuy2_deliveries"];
		} elseif ($this->config->get("yabuy2_deliveries")) { 
			$this->tdata["yabuy2_deliveries"] = $this->config->get("yabuy2_deliveries");
		}

		$this->tdata["yabuy2_postals"] = array();
		if (isset($this->request->post["yabuy2_postals"])) {
			$this->tdata["yabuy2_postals"] = $this->request->post["yabuy2_postals"];
		} elseif ($this->config->get("yabuy2_postals")) { 
			$this->tdata["yabuy2_postals"] = $this->config->get("yabuy2_postals");
		}
		
		$this->tdata["yabuy2_outlets"] = array();
		if (isset($this->request->post["yabuy2_outlets"])) {
			$this->tdata["yabuy2_outlets"] = $this->request->post["yabuy2_outlets"];
		} elseif ($this->config->get("yabuy2_outlets")) { 
			$this->tdata["yabuy2_outlets"] = $this->config->get("yabuy2_outlets");
		}

		$this->tdata["url_csvoutlets"] = $this->url->link('extension/module/yabuy/csvoutlets', '', "SSL");
		$this->tdata['user_token'] = $this->session->data["user_token"];
		if (is_file(DIR_CATALOG . 'controller/yandexbuy2/outlets.csv')) {
			$this->tdata['csvoutlets'] = true;
			$this->tdata["text_csvoutlets"] = sprintf($this->language->get("text_csvoutlets"), '<a href="/catalog/controller/yandexbuy2/outlets.csv">catalog/controller/yandexbuy2/outlets.csv</a>');
			$this->tdata["text_show"] = $this->language->get("text_show");
		}
		else {
			$this->tdata['csvoutlets'] = false;
		}
		//--- DBS ---

		$this->tdata['header'] = $this->load->controller('common/header');
		$this->tdata['column_left'] = $this->load->controller('common/column_left');
		$this->tdata['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/module/yabuy2', $this->tdata));
	}
	
	protected function setLanguage() {
		$this->tdata["heading_title"] = $this->language->get("heading_title");
		
		$this->tdata['text_edit'] = $this->language->get('text_edit');
		$this->tdata["text_enabled"] = $this->language->get("text_enabled");
		$this->tdata["text_disabled"] = $this->language->get("text_disabled");
		$this->tdata["text_get"] = $this->language->get("text_get");

		$this->tdata["text_module_name"] = $this->language->get("text_module_name");
		$this->tdata["text_module_days"] = $this->language->get("text_module_days");
		$this->tdata["text_module_before"] = $this->language->get("text_module_before");
		$this->tdata["text_module_type"] = $this->language->get("text_module_type");
		$this->tdata["text_module_type_off"] = $this->language->get("text_module_type_off");
		$this->tdata["text_module_type_delivery"] = $this->language->get("text_module_type_delivery");
		$this->tdata["text_module_type_postal"] = $this->language->get("text_module_type_postal");
		
		$this->tdata["text_delivery_id"] = $this->language->get("text_delivery_id");
		$this->tdata["text_delivery_name"] = $this->language->get("text_delivery_name");
		$this->tdata["text_delivery_price"] = $this->language->get("text_delivery_price");
		$this->tdata["text_delivery_days"] = $this->language->get("text_delivery_days");
		$this->tdata["text_delivery_before"] = $this->language->get("text_delivery_before");
		$this->tdata["text_delivery_region"] = $this->language->get("text_delivery_region");
		
		$this->tdata["text_outlet_id"] = $this->language->get("text_outlet_id");
		$this->tdata["text_outlet_price"] = $this->language->get("text_outlet_price");
		$this->tdata["text_outlet_zone"] = $this->language->get("text_outlet_zone");
		$this->tdata["text_outlet_city"] = $this->language->get("text_outlet_city");
		$this->tdata["text_outlet_postcode"] = $this->language->get("text_outlet_postcode");
		$this->tdata["text_outlet_address_1"] = $this->language->get("text_outlet_address_1");
		$this->tdata["text_outlet_address_2"] = $this->language->get("text_outlet_address_2");
		$this->tdata["text_outlet_price"] = $this->language->get("text_outlet_price");

		$this->tdata["entry_status"] = $this->language->get("entry_status");
		$this->tdata["entry_yacompany"] = $this->language->get("entry_yacompany");
		$this->tdata["entry_token"] = $this->language->get("entry_token");
		$this->tdata["entry_yalogin"] = $this->language->get("entry_yalogin");
		$this->tdata["entry_oauth_token"] = $this->language->get("entry_oauth_token");
		$this->tdata["entry_payments"] = $this->language->get("entry_payments");
		$this->tdata["entry_modules"] = $this->language->get("entry_modules");
		$this->tdata["entry_deliveries"] = $this->language->get("entry_deliveries");
		$this->tdata["entry_postals"] = $this->language->get("entry_postals");
		$this->tdata["entry_outlets"] = $this->language->get("entry_outlets");
		
		//buttons
		$this->tdata["button_save"] = $this->language->get("button_save");
		$this->tdata["button_cancel"] = $this->language->get("button_cancel");
		$this->tdata["button_add_outlet"] = $this->language->get("button_add_outlet");
		$this->tdata["button_remove"] = $this->language->get("button_remove");		
	}
	
	/**
	 * Возвращает установленные модули доставки
	 */
	protected function getShippingModules() {
		$blacklist = array('track_no', 'rupost_updater', 'boxberry_updater', 'axiomus_sender', 'dhl_sender');
		$this->load->model('extension/extension');
		$extensions = $this->model_extension_extension->getInstalled('shipping');
		$modules = array();
		
		foreach ($extensions as $key => $extension) {
			if (!file_exists(DIR_APPLICATION . 'controller/extension/shipping/' . $extension . '.php')) {
				continue;
			}
			if (in_array($extension, $blacklist)) {
				continue;
			}
			$this->language->load('extension/shipping/' . $extension);
										
			$modules[] = array(
				'code' => $extension,
				'name' => $this->language->get('heading_title'),
				'edit_url' => $this->url->link('extension/shipping/' . $extension . '', 'user_token=' . $this->session->data['user_token'], 'SSL'),
				'status' => $this->config->get($extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
			);
		}
		return $modules;
	}
	
	public function install() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` LIMIT 1");
		if (!isset($query->row['yaorder_id'])) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD  `yaorder_id` INT NOT NULL AFTER `order_id`");
		}
		$this->load->model('extension/event');
		$this->model_extension_event->addEvent('order_status_2_yandexbuy2', 'catalog/model/checkout/order/addOrderHistory/after', 'yandexbuy2/status/forward');			
	}
	
	public function uninstall() {
		$this->load->model('extension/event');
		$this->model_extension_event->deleteEvent('order_status_2_yandexbuy2');
	}
	
	public function csvoutlets() {
		$outlets = array();
		if (is_file(DIR_CATALOG . 'controller/yandexbuy2/outlets.csv')) {
			$fp = fopen(DIR_CATALOG . 'controller/yandexbuy/outlets.csv', 'r');
			if($fp){
				while ($data = fgets($fp)) {
					$data = explode(';', $data);
					$num = count($data);
					if((int)$num >= 6){
						$outlets[] = array(
							'id' => $data[0],
							'zone' => $data[1],
							'city' => $data[2],
							'postcode' => $data[3],
							'address_1' => $data[4],
							'address_2' => $data[5],
							'price' => (isset($data[6]) ? $data[6] : 0)
						);
					}
				}
			}
			fclose($fp);
		}
		echo json_encode($outlets);
	}
	
	private function validate() {
		if (!$this->user->hasPermission("modify", "extension/module/yabuy2")) {
			$this->error["warning"] = $this->language->get("error_permission");
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}
