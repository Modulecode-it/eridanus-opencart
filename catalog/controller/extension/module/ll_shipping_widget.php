<?php
/**
 * @author    p0v1n0m <p0v1n0m@gmail.com>
 * @license   Commercial
 * @link      https://github.com/p0v1n0m
 */
class ControllerExtensionModuleLLShippingWidget extends Controller {
	private $m = 'll_shipping_widget';
	private $t = 'module_ll_shipping_widget';
	private $map = 'https://api-maps.yandex.ru/2.1?lang=ru_RU';
	private $geo = null;

	public function index() {
		if ($this->config->get($this->t . '_status') && !empty($this->config->get($this->t . '_allowed'))) {
			$product_id = 0;

			if ($this->customer->isLogged() && !empty($this->config->get($this->t . '_customer_group')) && in_array($this->customer->getGroupId(), $this->config->get($this->t . '_customer_group'))) {
				return;
			}

			if (isset($this->request->get['product_id']) && $this->request->get['product_id'] > 0) {
				$product_id = (int)$this->request->get['product_id'];
				$data['map'] = 0;
			} elseif ($this->config->get($this->t . '_product_id') > 0) {
				if ($this->config->get($this->t . '_information_id') && isset($this->request->get['information_id']) && $this->request->get['information_id'] != $this->config->get($this->t . '_information_id')) {
					return;
				}

				$product_id = (int)$this->config->get($this->t . '_product_id');
				$data['map'] = 1;
			}

			if ($product_id > 0 && (empty($this->config->get($this->t . '_products')) || !in_array($product_id, $this->config->get($this->t . '_products')))) {
				$this->load->language('extension/module/' . $this->m);
				$this->load->model('extension/module/' . $this->m);

				$categories = $this->{'model_extension_module_' . $this->m}->getProductCategoryIDs($product_id);

				if (!isset($categories) || (isset($categories) && (!is_array($this->config->get($this->t . '_categories')) || !array_intersect($categories, $this->config->get($this->t . '_categories'))))) {
					$data['allowed'] = false;

					foreach ($this->config->get($this->t . '_allowed') as $code) {
						$cut = explode('_', $code);

						if ($this->config->get('shipping_' . $code . '_status')) {
							if ($cut[0] == 'll' && ($this->config->get('shipping_' . $code . '_api_key') || $this->config->get('shipping_' . $code . '_api_token'))) {
								$this->document->addScript($this->map . '&apikey=' . $this->config->get('shipping_' . $code . '_map_key'));
								$this->document->addScript('catalog/view/javascript/' . $code . '/' . $code . '.js');

								$data['allowed'] = $code;
							} elseif ($code == 'bb') {
								$this->document->addScript($this->map);
								$this->document->addScript('catalog/view/javascript/bb.js');

								$data['allowed'] = $code;
							}
						}
					}

					$data['product_id'] = $product_id;
					$data['m'] = $this->m;
					$data['height'] = $this->config->get($this->t . '_height');
					$data['insert'] = $this->config->get($this->t . '_insert');
					$data['selector'] = $this->config->get($this->t . '_selector');
					$data['autocomplete'] = $this->config->get($this->t . '_autocomplete');
					$data['description'] = html_entity_decode($this->config->get($this->t . '_description'), ENT_QUOTES, 'UTF-8');
					$data['text_calculate'] = $this->language->get('text_calculate');
					$data['text_country'] = $this->language->get('text_country');
					$data['text_zone'] = $this->language->get('text_zone');
					$data['text_city'] = $this->language->get('text_city');
					$data['text_postcode'] = $this->language->get('text_postcode');
					$data['text_none'] = $this->language->get('text_none');
					$data['title'] = isset($this->config->get($this->t . '_title')[$this->config->get('config_language_id')]) ? $this->config->get($this->t . '_title')[$this->config->get('config_language_id')] : '';
					$data['country_id'] = $this->getField('country_id');
					$data['country'] = $this->getField('country');
					$data['iso_code_2'] = $this->getField('iso_code_2');
					$data['iso_code_3'] = $this->getField('iso_code_3');
					$data['zone_id'] = $this->getField('zone_id');
					$data['zone'] = $this->getField('zone');
					$data['zone_code'] = $this->getField('zone_code');
					$data['city'] = $this->getField('city');
					$data['postcode'] = $this->getField('postcode');
					$data['country_status'] = $this->config->get($this->t . '_country_status');
					$data['zone_status'] = $this->config->get($this->t . '_zone_status');
					$data['postcode_status'] = $this->config->get($this->t . '_postcode_status');
					$data['options'] = $this->config->get($this->t . '_options');

					if ($this->config->get($this->t . '_country_status')) {
						$this->load->model('localisation/country');

						$data['countries'] = $this->model_localisation_country->getCountries();
					}

					if (isset($this->session->data['shipping_method']['code'])) {
						$this->session->data[$this->m . '_active'] = $this->session->data['shipping_method']['code'];
					}

					return $this->load->view('extension/module/' . $this->m, $data);
				}
			}
		}
	}

	public function getShippingMethods() {
		if (isset($this->request->get['product']) && (int)$this->request->get['product'] > 0) {
			$this->load->language('extension/module/' . $this->m);

			$product_id = (int)$this->request->get['product'];
			$address = [
				'firstname'      => '',
				'lastname'       => '',
				'company'        => '',
				'address_1'      => '',
				'address_2'      => '',
				'city'           => '',
				'postcode'       => '',
				'country_id'     => '',
				'country'        => '',
				'iso_code_2'     => '',
				'iso_code_3'     => '',
				'zone_id'        => '',
				'zone'           => '',
				'zone_code'      => '',
				'address_id'     => '',
				'address_format' => '',
				'default'        => false,
			];

			if (isset($this->request->get['country_id'])) {
				$address['country_id'] = (int)$this->request->get['country_id'];
			}

			if (isset($this->request->get['country'])) {
				$address['country'] = $this->request->get['country'];
			}

			if (isset($this->request->get['iso_code_2'])) {
				$address['iso_code_2'] = $this->request->get['iso_code_2'];
			}

			if (isset($this->request->get['iso_code_3'])) {
				$address['iso_code_3'] = $this->request->get['iso_code_3'];
			}

			if (isset($this->request->get['zone_id'])) {
				$address['zone_id'] = (int)$this->request->get['zone_id'];
			}

			if (isset($this->request->get['zone'])) {
				$address['zone'] = $this->request->get['zone'];
			}

			if (isset($this->request->get['zone_code'])) {
				$address['zone_code'] = $this->request->get['zone_code'];
			}

			if (isset($this->request->get['city'])) {
				$address['city'] = $this->request->get['city'];
			}

			if (isset($this->request->get['postcode'])) {
				$address['postcode'] = $this->request->get['postcode'];
			}

			$this->session->data['shipping_address']['country_id'] = $this->session->data['simple']['shipping_address']['country_id'] = $address['country_id'];
			$this->session->data['shipping_address']['country'] = $this->session->data['simple']['shipping_address']['country'] = $address['country'];
			$this->session->data['shipping_address']['iso_code_2'] = $this->session->data['simple']['shipping_address']['iso_code_2'] = $address['iso_code_2'];
			$this->session->data['shipping_address']['iso_code_3'] = $this->session->data['simple']['shipping_address']['iso_code_3'] = $address['iso_code_3'];
			$this->session->data['shipping_address']['zone_id'] = $this->session->data['simple']['shipping_address']['zone_id'] = $address['zone_id'];
			$this->session->data['shipping_address']['zone'] = $this->session->data['simple']['shipping_address']['zone'] = $address['zone'];
			$this->session->data['shipping_address']['zone_code'] = $this->session->data['simple']['shipping_address']['zone_code'] = $address['zone_code'];
			$this->session->data['shipping_address']['city'] = $this->session->data['simple']['shipping_address']['city'] = $address['city'];
			$this->session->data['shipping_address']['postcode'] = $this->session->data['simple']['shipping_address']['postcode'] = $address['postcode'];

			$data['text_calculate_error'] = $this->language->get('text_calculate_error');
			$data['text_select'] = $this->language->get('text_select');
			$data['method_select'] = $this->config->get($this->t . '_method_select');
			$data['method_title'] = $this->config->get($this->t . '_method_title');
			$data['descriptions'] = $this->config->get($this->t . '_descriptions');
			$data['code'] = isset($this->session->data['shipping_method']['code']) ? $this->session->data['shipping_method']['code'] : '';

			// для совместимости с 3, твиг не знает про html_entity_decode
			if (!empty($this->config->get($this->t . '_descriptions'))) {
				$data['descriptions'] = [];

				foreach ($this->config->get($this->t . '_descriptions') as $description) {
					$data['descriptions'][] = [
						'code'        => $description['code'],
						'position'    => $description['position'],
						'description' => html_entity_decode($description['description'], ENT_QUOTES, 'UTF-8'),
					];
				}
			}

			$method_data = [];

			$this->load->model('setting/extension');

			$results = $this->model_setting_extension->getExtensions('shipping');

			// virtual cart
			$registry = new Registry();

			$registry->set('config', $this->registry->get('config'));
			$registry->set('customer', new Cart\Customer($registry));
			$registry->set('session', new LL_Shipping_Widget_Session());
			$registry->set('db', $this->registry->get('db'));
			$registry->set('tax', $this->registry->get('tax'));
			$registry->set('weight', $this->registry->get('weight'));

			$quantity = isset($this->request->get['quantity']) && (int)$this->request->get['quantity'] > 0 ? (int)$this->request->get['quantity'] : 1;
			$option = isset($this->request->get['option']) ? $this->request->get['option'] : [];

			$this->cart = new Cart\Cart($registry);
			$this->cart->add($product_id, $quantity, $option);
			// virtual cart

			foreach ($results as $result) {
				if ($this->config->get('shipping_' . $result['code'] . '_status') && in_array($result['code'], $this->config->get($this->t . '_allowed'))) {
					$this->load->model('extension/shipping/' . $result['code']);

					$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($address);

					if ($quote) {
						$method_data[$result['code']] = [
							'title'      => $quote['title'],
							'quote'      => $quote['quote'],
							'sort_order' => $quote['sort_order'],
						];
					}
				}
			}

			$sort_order = [];

			foreach ($method_data as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $method_data);

			if ($this->config->get('filterit_shipping')) {
				if (!$this->filterit && (method_exists($this->load, 'library') || get_class($this->load) == 'agooLoader') ) {
					$this->load->library('simple/filterit');
				}

				if (!$this->filterit) {
					$this->filterit = new Simple\Filterit($this->registry);
				}

				$method_data = $this->filterit->filterShipping($method_data, $this->session->data['shipping_address']);
			}

			// virtual cart
			if (isset($this->cart->getProducts()[0])) {
				$this->cart->remove($this->cart->getProducts()[0]['cart_id']);
			}
			// virtual cart

			$data['shipping_methods'] = $method_data;

			$this->response->setOutput($this->load->view('extension/module/' . $this->m . '_load', $data));
		}
	}

	public function setShippingMethod() {
		if (isset($this->request->post['code'])) {

			$this->session->data['shipping_method']['code'] = $this->session->data[$this->m . '_active'] = $this->request->post['code'];
			$this->session->data['shipping_method']['title'] = isset($this->request->post['title']) ? htmlspecialchars_decode($this->request->post['title']) : '';
			$this->session->data['shipping_method']['cost'] = isset($this->request->post['cost']) ? $this->request->post['cost'] : 0;
			$this->session->data['shipping_method']['tax_class_id'] = isset($this->request->post['tax_class_id']) ? $this->request->post['tax_class_id'] : 0;
			$this->session->data['shipping_method']['text'] = isset($this->request->post['text']) ? htmlspecialchars_decode($this->request->post['text']) : '';
		}
	}

	public function autocomplete() {
		$json = [];

		if ($this->config->get($this->t . '_autocomplete') && isset($this->request->get['city'])) {
			if ($this->config->get($this->t . '_autocomplete') == 'simple') {
				$this->load->model('tool/simplegeo');

				$results = $this->model_tool_simplegeo->getGeoList($this->request->get['city']);
			} else {
				$this->load->model('extension/shipping/' . $this->config->get($this->t . '_autocomplete'));

				$results = $this->{'model_extension_shipping_' . $this->config->get($this->t . '_autocomplete')}->getCities($this->request->get['city']);
			}

			if (!empty($results)) {
				foreach ($results as $result) {
					$json[] = [
						'name'  => strip_tags(html_entity_decode($result['full'], ENT_QUOTES, 'UTF-8')),
						'value' => strip_tags(html_entity_decode($result['city'], ENT_QUOTES, 'UTF-8')),
					];
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function getField($field) {
		if (isset($this->session->data['shipping_address'][$field])) {
			return $this->session->data['shipping_address'][$field];
		} elseif (isset($this->session->data['simple']['shipping_address'][$field])) {
			return $this->session->data['simple']['shipping_address'][$field];
		} elseif ($this->config->get($this->t . '_autodetect') && !empty($this->getGeo($field))) {
			return $this->getGeo($field);
		} elseif ($this->config->get($this->t . '_' . $field)) {
			return $this->config->get($this->t . '_' . $field);
		} else {
			return '';
		}
	}

	protected function setGeo() {
		if ($this->config->get($this->t . '_autodetect') == 'geoip') {
			$this->geo = $this->progroman_city_manager->getFullInfo();
		} elseif ($this->config->get($this->t . '_autodetect') == 'simple') {
			$this->load->model('tool/simplegeo');

			$this->geo = $this->model_tool_simplegeo->getGeoDataByIp(1);
		}
	}

	protected function getGeo($field) {
		if (!isset($this->geo)) {
			$this->setGeo();
		}

		return isset($this->geo[$field]) ? $this->geo[$field] : '';
	}
}
