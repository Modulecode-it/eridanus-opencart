<?php
class ControllerExtensionShippingMeasoftcourier extends Controller
{
    private $version = '4.3.1';
    private $error = array();

    public function index()
    {
        $this->load->language('extension/shipping/measoftcourier');

        $this->document->setTitle($this->language->get('heading_title'));
		$this->document->addScript('view/javascript/measoftcourier/admin_script.js');

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_measoftcourier', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['account_login'] = $this->language->get('account_login');
        $data['account_password'] = $this->language->get('account_password');
        $data['account_extra'] = $this->language->get('account_extra');
		$data['client_code'] = $this->language->get('client_code');
		$data['auto_client_code'] = $this->language->get('auto_client_code');
        $data['account_city'] = $this->language->get('account_city');
        $data['help_fixed_length'] = $this->language->get('help_fixed_length');
        $data['entry_pvz_title'] = $this->language->get('entry_pvz_title');
        $data['entry_pvz_description'] = $this->language->get('entry_pvz_description');
        $data['entry_courier_title'] = $this->language->get('entry_courier_title');
        $data['entry_courier_description'] = $this->language->get('entry_courier_description');
		$data['pvz_off'] = $this->language->get('pvz_off');
        $data['entry_tax_class'] = $this->language->get('entry_tax_class');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');

        $data['entry_default_weight'] = $this->language->get('entry_default_weight');
        $data['entry_default_height'] = $this->language->get('entry_default_height');
        $data['entry_default_widht'] = $this->language->get('entry_default_widht');

        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_none'] = $this->language->get('text_none');
        $data['text_edit'] = $this->language->get('text_edit');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['error_login'])) {
            $data['error_login'] = $this->error['error_login'];
        } else {
            $data['error_login'] = '';
        }

        if (isset($this->error['error_password'])) {
            $data['error_password'] = $this->error['error_password'];
        } else {
            $data['error_password'] = '';
        }

        if (isset($this->error['error_extra'])) {
            $data['error_extra'] = $this->error['error_extra'];
        } else {
            $data['error_extra'] = '';
        }
		
		if (isset($this->error['error_client_code'])) {
            $data['error_client_code'] = $this->error['error_client_code'];
        } else {
            $data['error_client_code'] = '';
        }

        if (isset($this->error['error_city'])) {
            $data['error_city'] = $this->error['error_city'];
        } else {
            $data['error_city'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
            );
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/shipping/measoftcourier', 'user_token=' . $this->session->data['user_token'], true)
            );

        $data['action'] = $this->url->link('extension/shipping/measoftcourier', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

        if (isset($this->request->post['shipping_measoftcourier_login'])) {
            $data['shipping_measoftcourier_login'] = $this->request->post['shipping_measoftcourier_login'];
        } else {
            $data['shipping_measoftcourier_login'] = $this->config->get('shipping_measoftcourier_login');
        }
        if (isset($this->request->post['shipping_measoftcourier_password'])) {
            $data['shipping_measoftcourier_password'] = $this->request->post['shipping_measoftcourier_password'];
        } else {
            $data['shipping_measoftcourier_password'] = $this->config->get('shipping_measoftcourier_password');
        }
        if (isset($this->request->post['shipping_measoftcourier_extra'])) {
            $data['shipping_measoftcourier_extra'] = $this->request->post['shipping_measoftcourier_extra'];
        } else {
            $data['shipping_measoftcourier_extra'] = $this->config->get('shipping_measoftcourier_extra');
        }
		
		if (isset($this->request->post['shipping_measoftcourier_client_code'])) {
            $data['shipping_measoftcourier_client_code'] = $this->request->post['shipping_measoftcourier_client_code'];
        } else {
            $data['shipping_measoftcourier_client_code'] = $this->config->get('shipping_measoftcourier_client_code');
        }

        if (isset($this->request->post['shipping_measoftcourier_city'])) {
            $data['shipping_measoftcourier_city'] = $this->request->post['shipping_measoftcourier_city'];
        } else {
            $data['shipping_measoftcourier_city'] = $this->config->get('shipping_measoftcourier_city');
        }
        if (isset($this->request->post['shipping_measoftcourier_status'])) {
            $data['shipping_measoftcourier_status'] = $this->request->post['shipping_measoftcourier_status'];
        } else {
            $data['shipping_measoftcourier_status'] = $this->config->get('shipping_measoftcourier_status');
        }

        if (isset($this->request->post['shipping_measoftcourier_order_default_weight'])) {
            $data['shipping_measoftcourier_order_default_weight'] = $this->request->post['shipping_measoftcourier_order_default_weight'];
        } else {
            $data['shipping_measoftcourier_order_default_weight'] = $this->config->get('shipping_measoftcourier_order_default_weight');
        }

        if (isset($this->request->post['shipping_measoftcourier_sort_order'])) {
            $data['shipping_measoftcourier_sort_order'] = $this->request->post['shipping_measoftcourier_sort_order'];
        } else {
            $data['shipping_measoftcourier_sort_order'] = $this->config->get('shipping_measoftcourier_sort_order');
        }
        if (isset($this->request->post['shipping_measoftcourier_pvz_title'])) {
            $data['shipping_measoftcourier_pvz_title'] = $this->request->post['shipping_measoftcourier_pvz_title'];
        } else {
            $data['shipping_measoftcourier_pvz_title'] = $this->config->get('shipping_measoftcourier_pvz_title');
        }
        if (isset($this->request->post['shipping_measoftcourier_pvz_description'])) {
            $data['shipping_measoftcourier_pvz_description'] = $this->request->post['shipping_measoftcourier_pvz_description'];
        } else {
            $data['shipping_measoftcourier_pvz_description'] = $this->config->get('shipping_measoftcourier_pvz_description');
        }
        if (isset($this->request->post['shipping_measoftcourier_courier_title'])) {
            $data['shipping_measoftcourier_courier_title'] = $this->request->post['shipping_measoftcourier_courier_title'];
        } else {
            $data['shipping_measoftcourier_courier_title'] = $this->config->get('shipping_measoftcourier_courier_title');
        }
        if (isset($this->request->post['shipping_measoftcourier_courier_description'])) {
            $data['shipping_measoftcourier_courier_description'] = $this->request->post['shipping_measoftcourier_courier_description'];
        } else {
            $data['shipping_measoftcourier_courier_description'] = $this->config->get('shipping_measoftcourier_courier_description');
        }

        if (isset($this->request->post['shipping_measoftcourier_order_fixed_length'])) {
            $data['shipping_measoftcourier_order_fixed_length'] = $this->request->post['shipping_measoftcourier_order_fixed_length'];
        } else {
            $data['shipping_measoftcourier_order_fixed_length'] = $this->config->get('shipping_measoftcourier_order_fixed_length');
        }
        if (isset($this->request->post['shipping_measoftcourier_order_prefix'])) {
            $data['shipping_measoftcourier_order_prefix'] = $this->request->post['shipping_measoftcourier_order_prefix'];
        } else {
            $data['shipping_measoftcourier_order_prefix'] = $this->config->get('shipping_measoftcourier_order_prefix');
        }
        if (isset($this->request->post['shipping_measoftcourier_use_articles'])) {
            $data['shipping_measoftcourier_use_articles'] = $this->request->post['shipping_measoftcourier_use_articles'];
        } else {
            $data['shipping_measoftcourier_use_articles'] = $this->config->get('shipping_measoftcourier_use_articles');
        }
		if (isset($this->request->post['shipping_measoftcourier_pvz_off'])) {
            $data['shipping_measoftcourier_pvz_off'] = $this->request->post['shipping_measoftcourier_pvz_off'];
        } else {
            $data['shipping_measoftcourier_pvz_off'] = $this->config->get('shipping_measoftcourier_pvz_off');
        }
		
        /*
                if (isset($this->request->post['shipping_measoftcourier_use_for_calculation'])) {
                    $data['shipping_measoftcourier_use_for_calculation'] = $this->request->post['shipping_measoftcourier_use_for_calculation'];
                } else {
                    $data['shipping_measoftcourier_use_for_calculation'] = $this->config->get('shipping_measoftcourier_use_for_calculation');
                }
        */
        if (isset($this->request->post['shipping_measoftcourier_map_width'])) {
            $data['shipping_measoftcourier_map_width'] = $this->request->post['shipping_measoftcourier_map_width'];
        } else {
            $data['shipping_measoftcourier_map_width'] = $this->config->get('shipping_measoftcourier_map_width');
        }

        if (isset($this->request->post['shipping_measoftcourier_map_height'])) {
            $data['shipping_measoftcourier_map_height'] = $this->request->post['shipping_measoftcourier_map_height'];
        } else {
            $data['shipping_measoftcourier_map_height'] = $this->config->get('shipping_measoftcourier_map_height');
        }        

        if (isset($this->request->post['shipping_measoftcourier_shipping_rate'])) {
            $data['shipping_measoftcourier_shipping_rate'] = $this->request->post['shipping_measoftcourier_shipping_rate'];
        } else {
            $data['shipping_measoftcourier_shipping_rate'] = $this->config->get('shipping_measoftcourier_shipping_rate');
        }

        if (isset($this->request->post['shipping_measoftcourier_shipping_add_sum'])) {
            $data['shipping_measoftcourier_shipping_add_sum'] = $this->request->post['shipping_measoftcourier_shipping_add_sum'];
        } else {
            $data['shipping_measoftcourier_shipping_add_sum'] = $this->config->get('shipping_measoftcourier_shipping_add_sum');
        }

        $data['payment_methods'] = $this->getPaymentMethods();

        if (isset($this->request->post['shipping_measoftcourier_payment_cash'])) {
            $data['shipping_measoftcourier_payment_cash'] = $this->request->post['shipping_measoftcourier_payment_cash'];
        } else {
            $data['shipping_measoftcourier_payment_cash'] = $this->config->get('shipping_measoftcourier_payment_cash');
        }

        if (isset($this->request->post['shipping_measoftcourier_payment_card'])) {
            $data['shipping_measoftcourier_payment_card'] = $this->request->post['shipping_measoftcourier_payment_card'];
        } else {
            $data['shipping_measoftcourier_payment_card'] = $this->config->get('shipping_measoftcourier_payment_card');
        }

        if (isset($this->request->post['shipping_measoftcourier_payment_none'])) {
            $data['shipping_measoftcourier_payment_none'] = $this->request->post['shipping_measoftcourier_payment_none'];
        } else {
            $data['shipping_measoftcourier_payment_none'] = $this->config->get('shipping_measoftcourier_payment_none');
        }

        if (isset($this->request->post['shipping_measoftcourier_tax_class_id'])) {
            $data['shipping_measoftcourier_tax_class_id'] = $this->request->post['shipping_measoftcourier_tax_class_id'];
        } else {
            $data['shipping_measoftcourier_tax_class_id'] = $this->config->get('shipping_measoftcourier_tax_class_id');
        }
        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        if (isset($this->request->post['shipping_measoftcourier_geo_zone_id'])) {
            $data['shipping_measoftcourier_geo_zone_id'] = $this->request->post['shipping_measoftcourier_geo_zone_id'];
        } else {
            $data['shipping_measoftcourier_geo_zone_id'] = $this->config->get('shipping_measoftcourier_geo_zone_id');
        }
		
		$this->load->model('setting/store');

		$data['stores'] = array();
		
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);
		
		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}
		
		if (isset($this->request->post['shipping_measoftcourier_product_store'])) {
            $data['shipping_measoftcourier_product_store'] = $this->request->post['shipping_measoftcourier_product_store'];
        } else {
            $data['shipping_measoftcourier_product_store'] = $this->config->get('shipping_measoftcourier_product_store');
        }		
		$data['shipping_measoftcourier_product_store'] = $data['shipping_measoftcourier_product_store'] ?? [];
		
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

		
        $this->response->setOutput($this->load->view('extension/shipping/measoftcourier', $data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/shipping/measoftcourier')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['shipping_measoftcourier_login']) {
            $this->error['error_login'] = $this->language->get('error_login');
        }

        if (!$this->request->post['shipping_measoftcourier_password']) {
            $this->error['error_password'] = $this->language->get('error_password');
        }

        if (!$this->request->post['shipping_measoftcourier_extra']) {
            $this->error['error_extra'] = $this->language->get('error_extra');
        }
		
		if (!$this->request->post['shipping_measoftcourier_client_code']) {
            $this->error['error_client_code'] = $this->language->get('error_client_code');
        }


        if (!$this->request->post['shipping_measoftcourier_city']) {
            $this->error['error_city'] = $this->language->get('error_city');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Метод для Ajax запросов.
     */
    public function ajax()
    {
        $action = 'action' . ucfirst($this->request->get['action']);

        $this->$action();
    }

    /**
     * Возвращает название CMS и Opencart версию.
     */
    public function getSender()
    {
        return array(
            'module' => Measoft::OPENCART,
            'cms_version' => VERSION,
            'module_version' => $this->version,
        );
    }
	
	    /**
     * Получение кода клиента
     */
    private function actionAutoclientcode()
    {
	
        $this->load->model('extension/shipping/measoftcourier');

		
        $shipping_measoftcourier_login = $this->request->post['shipping_measoftcourier_login'];
    	$shipping_measoftcourier_password = $this->request->post['shipping_measoftcourier_password'];
		$shipping_measoftcourier_extra = $this->request->post['shipping_measoftcourier_extra'];
       
        
       

        try {
            $client_code = $this->model_extension_shipping_measoftcourier->getClientCode($shipping_measoftcourier_login, $shipping_measoftcourier_password, $shipping_measoftcourier_extra);
        if (!$client_code) {
            self::sendRespond('Укажите корректные Логин, Пароль, Код курьерской службы');
        }

            self::sendRespond($client_code, 'set_client_code');
        } catch (Exception $e) {
            self::sendRespond($e->getMessage());
        }
    }

    /**
     * Вывод формы для отправки заказа.
     */
    private function actionCourierTemplate()
    {
        $this->load->model('extension/shipping/measoftcourier');

        $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
        $order = $this->model_extension_shipping_measoftcourier->getOrder($order_id);

        $this->response->setOutput($this->load->view('extension/shipping/measoftcourier_widget', array(
            'order_id' => $order_id,
            'order' => $order,
            'user_token'    => $this->session->data['user_token'],
            'delivery_date' => date('Y-m-d', time() + 3600 * 24),
        )));
    }

    /**
     * Отправка заказа в API.
     */
    private function actionSend()
    {
        $this->load->model('extension/shipping/measoftcourier');

        $order_id    = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
        $shipping    = isset($_REQUEST['shipping']) ? floatval($_REQUEST['shipping']) : 0;
        $date        = isset($_REQUEST['date']) ? (string) $_REQUEST['date'] : '';
        $time_min    = isset($_REQUEST['time_min']) ? (string) $_REQUEST['time_min'] : '';
        $time_max    = isset($_REQUEST['time_max']) ? (string) $_REQUEST['time_max'] : '';
        $price       = isset($_REQUEST['price']) ? (float) $_REQUEST['price'] : null;
        $instruction = isset($_REQUEST['instruction']) ? (string) $_REQUEST['instruction'] : '';
        $return      = isset($_REQUEST['return']) ? (string) $_REQUEST['return'] : '';
        $pvz_id      = isset($_REQUEST['pvz_id']) ? intval($_REQUEST['pvz_id']) : false;
        $pvz_name    = isset($_REQUEST['pvz_name']) ? intval($_REQUEST['pvz_name']) : false;

        // получаем дату доставки
        if (!$date) {
            self::sendRespond('Укажите дату доставки');
        }

        try {
            $status = $this->model_extension_shipping_measoftcourier->newOrder(
                $order_id,
                $shipping,
                $date,
                $time_min,
                $time_max,
                $price,
                $this->getSender(),
                $instruction,
                $return,
                $pvz_id,
                $pvz_name
            );

            self::sendRespond($status, 'reload_status');
        } catch (Exception $e) {
            self::sendRespond($e->getMessage());
        }
    }

    /**
     * Получение статуса заказа
     */
    private function actionStatus()
    {
        $this->load->language('extension/shipping/measoftcourier');

        $this->load->model('extension/shipping/measoftcourier');

        try {
            $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
            $status = $this->model_extension_shipping_measoftcourier->getStatus($order_id);

            self::sendRespond($status);
        } catch (Exception $e) {
            self::sendRespond(print_r($e->getMessage(), true), 'show_form_send');
        }
    }

    private function getPaymentMethods()
    {
        $this->load->model('setting/extension');
        $extensions = $this->model_setting_extension->getInstalled('payment');

        $methods = array();
        foreach ($extensions as $extension) {
            if ($this->config->get("payment_{$extension}_status")) {
                $this->load->language('extension/payment/' . $extension, 'extension');

                $methods[] = array(
                    'id' => $extension,
                    'title' => $this->language->get('extension')->get('heading_title')
                );
            }
        }

        return $methods;
    }

    private static function sendRespond($message, $action = null)
    {
        echo json_encode(array(
            'data' => array(
                'message' => $message,
                'action' => $action,
            ),
        ));
        exit();
    }

    public function install()
    {
        $pvz_query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'pvz_id'");
        if (!$pvz_query->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `pvz_id` varchar (20)");
        }
        $pvz_query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'pvz_name'");
        if (!$pvz_query->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `pvz_name` TEXT");
        }
    }

}
