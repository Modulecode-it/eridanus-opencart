<?php
/**
* Маркетплейс "Яндекс.Маркет" - прием заказов по API для OpenCart (ocStore) 2.x
*
* @author Alexander Toporkov <toporchillo@gmail.com>
* @copyright (C) 2013- Alexander Toporkov
* @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
require_once(dirname(__FILE__).'/base.php');

class ControllerYandexbuyCart extends ControllerYandexbuyBase {

	public function index() {
		$postdata = file_get_contents("php://input");
		if (!$postdata) {
			header('HTTP/1.0 404 Not Found');
			echo '<h1>No data posted</h1>';
			exit;
		}
		$this->load->model('catalog/product');
		$data = json_decode($postdata, true);
		
		$currency = $data['cart']['currency']; //Assume RUR
		
		$ret = array('cart'=>array('items'=>array()));
		$total = 0;
		
		foreach ($data['cart']['items'] as $item) {
			$offer_id = $item['offerId'];
			$option_value_id = 0;
			$option2_value_id = 0;
			if (!$this->config->get('yabuy_long_id')) {
				if (strlen($offer_id) > 12) {
					$offer_id = intval(substr($item['offerId'], 0, strlen($offer_id) - 12));
					$option_value_id = intval(ltrim(substr($item['offerId'], -12, 6), '0'));
					$option2_value_id = intval(ltrim(substr($item['offerId'], -6), '0'));
				}
				elseif (strlen($offer_id) > 6) {
					$offer_id = intval(substr($offer_id, 0, strlen($offer_id) - 6));
					$option_value_id = intval(ltrim(substr($item['offerId'], -6), '0'));
				}
			}
			$product_info = $this->model_catalog_product->getProduct($offer_id);
			if ($product_info['status'] != 1
				|| !$product_info['quantity']
				/* || $product_info['stock_status'] == (int)$out_of_stock_id */) {
				continue;
			}

			$count = min($product_info['quantity'], $item['count']);
			if ($count < $product_info['minimum']) {
				$count = $product_info['minimum'];
			}
			$price = ($product_info['special'] ? $product_info['special'] : $product_info['price']);
			if ($option_value_id > 0) {
				$option = $this->getProductOptionData($offer_id, $option_value_id);
				if (!$option['quantity'] && $option['subtract']) {
					continue;
				}
				if ($option['price_prefix'] == '+') {
					$price+= $option['price'];
				}
				elseif ($option['price_prefix'] == '-') {
					$price-= $option['price'];
				}
			}
			if ($option2_value_id > 0) {
				$option2 = $this->getProductOptionData($offer_id, $option2_value_id);
				if (!$option2['quantity'] && $option2['subtract']) {
					continue;
				}
				if ($option2['price_prefix'] == '+') {
					$price+= $option2['price'];
				}
				elseif ($option2['price_prefix'] == '-') {
					$price-= $option2['price'];
				}
			}
			
			$option_data = array();
			//@todo сделать учет опций
			$this->cart->add($offer_id, intval($count), $option_data);
			
			$total+= floatval($price)*$count;
			$ret['cart']['items'][] = array(
				'feedId'=>$item['feedId'],
				'offerId'=>$item['offerId'],
				//'price'=>floatval($price),
				'count'=>intval($count),
				//'delivery'=>true
			);			
		}
		header('Content-Type: application/json;charset=utf-8');
		echo json_encode($ret);
	}
}
