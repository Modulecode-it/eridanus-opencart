<?php
class ControllerExtensionModuleFeaturednew extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/featurednew');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');
		
		$this->load->model('extension/module/featurednew');

		$data['products'] = array();

		if (isset($this->request->get['path'])) {
			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

			}
		} else {
			$category_id = 0;
		}

		if (!$setting['limit']) {
			$setting['limit'] = 4;
		}

			$filter_data = array(
				'filter_category_id' => $category_id,
				'filter_sub_category' => true,
				'random'             => $setting['shuffle'],
				'start'              => 0,
				'limit'              => $setting['limit']
			);

			//$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			//$results = $this->model_catalog_product->getProducts($filter_data);

		$results = $this->model_extension_module_featurednew->getFeaturedProducts($filter_data);
		
			foreach ($results as $result) {
				$product_info = $this->model_catalog_product->getProduct($result['product_id']);
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

			$catid = $this->model_extension_module_featurednew->getFeaturedCategories($result['product_id']);
            if(isset($catid[0]) && !empty($catid[0])){
                $path = $catid[0]['category_id'];
            }else{
                $path = '';
            }					

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'path=' . $path . '&product_id=' . $result['product_id'])
				);
			}

		if ($data['products']) {
			return $this->load->view('extension/module/featurednew', $data);
		}
	}
}