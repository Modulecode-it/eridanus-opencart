<?php
class ModelExtensionModuleProductStickers extends Model {
	public function getProductSticker($product) {
		$this->load->language('extension/module/product_stickers');
		
		$language_id = $this->config->get('config_language_id');
		$product_stickers_data = array();

		// New Sticker	
		$product_stickers_new = $this->config->get('module_product_stickers_new');
		$date_added = (time() - strtotime($product_stickers_new['date_new'] ? $product['date_added'] : $product['date_available'])) / 86400;

		if ($product_stickers_new['status'] && (int)$date_added <= (int)$product_stickers_new['day']) {
			$product_stickers_data[] = array(
				'class' 	 => 'product_stickers-new',
				'name'  	 => $this->language->get('text_product_stickers_new'),
				'image' 	 => '',
				'sort_order' => $product_stickers_new['sort_order']
			);
		}				
		
		// Sticker Special
		$product_stickers_special = $this->config->get('module_product_stickers_special');
		$class = '';
		
		if ((float)$product['special'] && $product_stickers_special['status']) {
			if ($product_stickers_special['label'] == 2) {
				$name = $this->language->get('text_product_stickers_special') . ' <span class="product_stickers-text-percent">-' . round(($product['price'] - $product['special']) / ($product['price'] / 100)) . '%</span>';
			} elseif ($product_stickers_special['label'] == 1) {
				$class = ' product_stickers-percent';
				$name = '-' . round(($product['price'] - $product['special']) / ($product['price'] / 100)) . '%';
			} else {
				$name = $this->language->get('text_product_stickers_special');
			}
			
			$product_stickers_data[] = array(
				'class' 		=> 'product_stickers-special' . $class,
				'name'  		=> $name,
				'image' 		=> '',
				'sort_order'	=> $product_stickers_special['sort_order']
			);
		}
		
		// Sticker Bestseller
		$product_stickers_bestseller = $this->config->get('module_product_stickers_bestseller');
		
		if ($product_stickers_bestseller['status'] && (int)$product_stickers_bestseller['sale'] && $product['product_stickers_bestseller'] && $product['product_stickers_bestseller'] >= $product_stickers_bestseller['sale']) {
			$product_stickers_data[] = array(
				'class' 	 => 'product_stickers-bestseller',
				'name'  	 => $this->language->get('text_product_stickers_bestseller'),
				'image' 	 => '',
				'sort_order' => $product_stickers_bestseller['sort_order']
			);
		}
		
		// Sticker Stock Status
		$product_stickers_stock = $this->config->get('module_product_stickers_stock');
		
		if ($product_stickers_stock['status'] && ($product['quantity'] <= 0)) {
			$product_stickers_data[] = array(
				'class' 	 => 'product_stickers-stock',
				'name'  	 => $product['stock_status'],
				'image' 	 => '',
				'sort_order' => $product_stickers_stock['sort_order']
			);
		}
		
		// Sticker Price
		if ($this->config->get('module_product_stickers_price')) {
			if ((float)$product['special']) {
				$price = $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
			} else {
				$price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
			}
			
			foreach ($this->config->get('module_product_stickers_price') as $key => $value) {
				if ($value['status'] && (!$value['date_start'] || $value['date_start'] <= date('Y-m-d')) && (!$value['date_end'] || $value['date_end'] > date('Y-m-d')) && ($price >= (float)$value['min']) && ($price <= (float)$value['max'])) {
					$product_stickers_data[] = array(
						'class' 		=> 'product_stickers-price' . $key,
						'name'  		=> $value[$language_id]['name'],
						'image' 		=> $value['image'] ? 'style="background: url(\'/image/' . $value['image'] . '\');"' : '',
						'sort_order'	=> $value['sort_order']
					);
				}
			}
		}
		
		// Sticker Custom
		if ($product['product_stickers_custom'] && $this->config->get('module_product_stickers_custom')) {
			$product_stickers_custom = unserialize($product['product_stickers_custom']);
			
			foreach ($this->config->get('module_product_stickers_custom') as $key => $value) {
				if ($value['status'] && isset($product_stickers_custom[$key]['status']) && $product_stickers_custom[$key]['status'] &&  (!$value['date_start'] || $value['date_start'] <= date('Y-m-d')) && (!$value['date_end'] || $value['date_end'] > date('Y-m-d'))) {
					$product_stickers_data[] = array(
						'class' 	 => 'product_stickers-custom' . $key,
						'name'  	 => $value[$language_id]['name'],
						'image' 	 => $value['image'] ? 'style="background: url(\'/image/' . $value['image'] . '\');"' : '',
						'sort_order' => $value['sort_order']
					);
				}
			}
		}
		
		if ($product_stickers_data) {
			$sort_order = array();
			
			foreach ($product_stickers_data as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $product_stickers_data);
			
			return $product_stickers_data;
		} else {
			return false;
		}
	}
}