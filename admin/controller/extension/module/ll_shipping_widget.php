<?php
/**
 * @author    p0v1n0m <p0v1n0m@gmail.com>
 * @license   Commercial
 * @link      https://github.com/p0v1n0m
 */
class ControllerExtensionModuleLLShippingWidget extends Controller {
	private $m = 'll_shipping_widget';
	private $t = 'module_ll_shipping_widget';
	private $version = '2.2.4';
	private $email = 'p0v1n0m@gmail.com';
	private $site = 'https://p0v1n0m.ru';
	private $module_docs = 'https://opencartforum.com/files/file/7114-shipping-widget-raschet-dostavki-na-lyuboy-stranice/';
	private $autocompletes = ['ll_cdek', 'll_sl', 'll_pec', 'simple'];
	private $error = [];

	public function index() {
		$this->load->language('extension/module/' . $this->m);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (isset($this->request->post[$this->t . '_license']) &&
				isset($this->request->server['HTTP_HOST']) &&
				(base64_encode(hash_hmac('sha256',$this->request->server['HTTP_HOST'].$this->t,M_PI,true)) == $this->request->post[$this->t . '_license'] || base64_encode(hash_hmac('sha256',$this->request->server['HTTP_HOST'].$this->m,M_PI,true)) == $this->request->post[$this->t . '_license'])
			) {
				$this->model_setting_setting->editSetting($this->t, $this->request->post);

				$this->session->data['success'] = $this->language->get('text_success');

				if (isset($this->request->get['back'])) {
					$this->response->redirect($this->url->link('extension/module/' . $this->m, 'user_token=' . $this->session->data['user_token'], true));
				} else {
					$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
				}
			} else {
				$this->load->model('extension/extension');

				$this->model_extension_extension->uninstall('module', $this->t);

				$this->session->data['warning'] = $this->language->get('error_license');

				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			}
		}

		if (isset($this->session->data['warning'])) {
			$data['error_warning'] = $this->session->data['warning'];

			unset($this->session->data['warning']);
		} elseif (isset($this->error['warning'])) {
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

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/' . $this->m, 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/module/' . $this->m, 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post[$this->t . '_title'])) {
			$data[$this->t . '_title'] = $this->request->post[$this->t . '_title'];
		} elseif ($this->config->has($this->t . '_title')) {
			$data[$this->t . '_title'] = $this->config->get($this->t . '_title');
		} else {
			$data[$this->t . '_title'] = [];
		}

		if (isset($this->request->post[$this->t . '_method_select'])) {
			$data[$this->t . '_method_select'] = $this->request->post[$this->t . '_method_select'];
		} else {
			$data[$this->t . '_method_select'] = $this->config->get($this->t . '_method_select');
		}

		if (isset($this->request->post[$this->t . '_method_title'])) {
			$data[$this->t . '_method_title'] = $this->request->post[$this->t . '_method_title'];
		} else {
			$data[$this->t . '_method_title'] = $this->config->get($this->t . '_method_title');
		}

		if (isset($this->request->post[$this->t . '_country_status'])) {
			$data[$this->t . '_country_status'] = $this->request->post[$this->t . '_country_status'];
		} else {
			$data[$this->t . '_country_status'] = $this->config->get($this->t . '_country_status');
		}

		if (isset($this->request->post[$this->t . '_zone_status'])) {
			$data[$this->t . '_zone_status'] = $this->request->post[$this->t . '_zone_status'];
		} else {
			$data[$this->t . '_zone_status'] = $this->config->get($this->t . '_zone_status');
		}

		if (isset($this->request->post[$this->t . '_postcode_status'])) {
			$data[$this->t . '_postcode_status'] = $this->request->post[$this->t . '_postcode_status'];
		} else {
			$data[$this->t . '_postcode_status'] = $this->config->get($this->t . '_postcode_status');
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		if (isset($this->request->post[$this->t . '_country_id'])) {
			$data[$this->t . '_country_id'] = $this->request->post[$this->t . '_country_id'];
		} else {
			$data[$this->t . '_country_id'] = $this->config->get($this->t . '_country_id');
		}

		if (isset($this->request->post[$this->t . '_zone_id'])) {
			$data[$this->t . '_zone_id'] = $this->request->post[$this->t . '_zone_id'];
		} else {
			$data[$this->t . '_zone_id'] = $this->config->get($this->t . '_zone_id');
		}

		if (isset($this->request->post[$this->t . '_city'])) {
			$data[$this->t . '_city'] = $this->request->post[$this->t . '_city'];
		} else {
			$data[$this->t . '_city'] = $this->config->get($this->t . '_city');
		}

		if (isset($this->request->post[$this->t . '_postcode'])) {
			$data[$this->t . '_postcode'] = $this->request->post[$this->t . '_postcode'];
		} else {
			$data[$this->t . '_postcode'] = $this->config->get($this->t . '_postcode');
		}

		if (isset($this->request->post[$this->t . '_autodetect'])) {
			$data[$this->t . '_autodetect'] = $this->request->post[$this->t . '_autodetect'];
		} else {
			$data[$this->t . '_autodetect'] = $this->config->get($this->t . '_autodetect');
		}

		$data['autocompletes'] = $this->autocompletes;

		if (isset($this->request->post[$this->t . '_autocomplete'])) {
			$data[$this->t . '_autocomplete'] = $this->request->post[$this->t . '_autocomplete'];
		} else {
			$data[$this->t . '_autocomplete'] = $this->config->get($this->t . '_autocomplete');
		}

		if (isset($this->request->post[$this->t . '_insert'])) {
			$data[$this->t . '_insert'] = $this->request->post[$this->t . '_insert'];
		} else {
			$data[$this->t . '_insert'] = $this->config->get($this->t . '_insert');
		}

		if (isset($this->request->post[$this->t . '_selector'])) {
			$data[$this->t . '_selector'] = $this->request->post[$this->t . '_selector'];
		} else {
			$data[$this->t . '_selector'] = $this->config->get($this->t . '_selector');
		}

		if (isset($this->request->post[$this->t . '_options'])) {
			$data[$this->t . '_options'] = $this->request->post[$this->t . '_options'];
		} else {
			$data[$this->t . '_options'] = $this->config->get($this->t . '_options');
		}

		$this->load->model('catalog/category');

		$filter_data = [
			'sort'  => 'name',
			'order' => 'ASC',
		];

		$data['categories'] = $this->model_catalog_category->getCategories($filter_data);

		if (isset($this->request->post[$this->t . '_categories'])) {
			$data[$this->t . '_categories'] = $this->request->post[$this->t . '_categories'];
		} elseif ($this->config->has($this->t . '_categories')) {
			$data[$this->t . '_categories'] = $this->config->get($this->t . '_categories');
		} else {
			$data[$this->t . '_categories'] = [];
		}

		$this->load->model('catalog/product');

		if (isset($this->request->post[$this->t . '_products'])) {
			$products = $this->request->post[$this->t . '_products'];
		} elseif ($this->config->has($this->t . '_products')) {
			$products = $this->config->get($this->t . '_products');
		} else {
			$products = [];
		}

		$data['products'] = [];

		foreach ($products as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				$data['products'][] = array(
					'product_id' => $product_info['product_id'],
					'name'       => $product_info['name'],
				);
			}
		}

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		if (isset($this->request->post[$this->t . '_customer_group'])) {
			$data[$this->t . '_customer_group'] = $this->request->post[$this->t . '_customer_group'];
		} elseif ($this->config->has($this->t . '_customer_group')) {
			$data[$this->t . '_customer_group'] = $this->config->get($this->t . '_customer_group');
		} else {
			$data[$this->t . '_customer_group'] = [];
		}

		if (isset($this->request->post[$this->t . '_description'])) {
			$data[$this->t . '_description'] = $this->request->post[$this->t . '_description'];
		} else {
			$data[$this->t . '_description'] = $this->config->get($this->t . '_description');
		}

		if (isset($this->request->post[$this->t . '_status'])) {
			$data[$this->t . '_status'] = $this->request->post[$this->t . '_status'];
		} else {
			$data[$this->t . '_status'] = $this->config->get($this->t . '_status');
		}

		if (isset($this->request->post[$this->t . '_product_id'])) {
			$data[$this->t . '_product_id'] = $this->request->post[$this->t . '_product_id'];
		} else {
			$data[$this->t . '_product_id'] = $this->config->get($this->t . '_product_id');
		}

		if (isset($this->request->post[$this->t . '_product'])) {
			$data[$this->t . '_product'] = $this->request->post[$this->t . '_product'];
		} else {
			$data[$this->t . '_product'] = $this->config->get($this->t . '_product');
		}

		if ($data[$this->t . '_product'] != '' && $data[$this->t . '_product_id'] > 0) {
			$product = $this->model_catalog_product->getProduct((int)$data[$this->t . '_product_id']);

			$data[$this->t . '_product'] = $product['name'];
		} else {
			$data[$this->t . '_product_id'] = '';
			$data[$this->t . '_product'] = '';
		}

		$this->load->model('catalog/information');

		$data['informations'] = $this->model_catalog_information->getInformations();

		if (isset($this->request->post[$this->t . '_information_id'])) {
			$data[$this->t . '_information_id'] = $this->request->post[$this->t . '_information_id'];
		} else {
			$data[$this->t . '_information_id'] = $this->config->get($this->t . '_information_id');
		}

		if (isset($this->request->post[$this->t . '_height'])) {
			$data[$this->t . '_height'] = $this->request->post[$this->t . '_height'];
		} else {
			$data[$this->t . '_height'] = $this->config->get($this->t . '_height');
		}

		$data['shippings'] = [];

		$this->load->model('setting/extension');

		$shippings = $this->model_setting_extension->getInstalled('shipping');

		$files = glob(DIR_APPLICATION . 'controller/{extension/shipping,shipping}/*.php', GLOB_BRACE);

		if ($files) {
			foreach ($files as $file) {
				$shipping = basename($file, '.php');

				$this->load->language('extension/shipping/' . $shipping);

				if (in_array($shipping, $shippings)) {
					$data['shippings'][] = [
						'code' => $shipping,
						'name' => $this->language->get('heading_title'),
					];
				}
			}

			$this->load->language('extension/module/' . $this->m);
		}

		$filterit = $this->config->get('filterit_shipping');

		if (isset($filterit['created'])) {
			foreach ($filterit['created'] as $method_code => $variant) {
				$data['shippings'][] = [
					'code' => $method_code,
					'name' => !empty($variant['title'][$this->config->get('config_admin_language')]) ? '[' . $method_code .'] ' . $variant['title'][$this->config->get('config_admin_language')] : '[' . $method_code .']',
				];
			}
		}

		if (isset($this->request->post[$this->t . '_allowed'])) {
			$data[$this->t . '_allowed'] = $this->request->post[$this->t . '_allowed'];
		} elseif ($this->config->has($this->t . '_allowed')) {
			$data[$this->t . '_allowed'] = $this->config->get($this->t . '_allowed');
		} else {
			$data[$this->t . '_allowed'] = [];
		}

		if (isset($this->request->post[$this->t . '_descriptions'])) {
			$data[$this->t . '_descriptions'] = $this->request->post[$this->t . '_descriptions'];
		} elseif ($this->config->has($this->t . '_descriptions')) {
			$data[$this->t . '_descriptions'] = $this->config->get($this->t . '_descriptions');
		} else {
			$data[$this->t . '_descriptions'] = [];
		}

		if (isset($this->request->post[$this->t . '_license'])) {
			$data[$this->t . '_license'] = $this->request->post[$this->t . '_license'];
		} else {
			$data[$this->t . '_license'] = $this->config->get($this->t . '_license');
		}

		$data['m'] = $this->m;
		$data['version'] = $this->version;
		$data['email'] = $this->email;
		$data['site'] = $this->site;
		$data['module_docs'] = $this->module_docs;
		$data['user_token'] = $this->session->data['user_token'];
		$data['host'] = isset($this->request->server['HTTP_HOST']) ? $this->request->server['HTTP_HOST'] : '';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		if (version_compare(VERSION, '3.1', '>=')) {
			$this->response->setOutput($this->load->view('extension/module/' . $this->m, $data));
		} else {
			$this->response->setOutput($this->load->view('extension/module/' . $this->m . '_3_0', $data));
		}
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/' . $this->m)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
