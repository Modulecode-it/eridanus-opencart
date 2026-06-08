<?php
/**
* Маркетплейс "Яндекс.Маркет" - прием заказов по API для OpenCart (ocStore) 2.x
*
* @author Alexander Toporkov <toporchillo@gmail.com>
* @copyright (C) 2013- Alexander Toporkov
* @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
require_once(dirname(__FILE__).'/base.php');

class ControllerYandexbuyStocks extends ControllerYandexbuyBase {
	public function index() {
		$postdata = file_get_contents("php://input");
		if (!$postdata) {
			header('HTTP/1.0 404 Not Found');
			echo '<h1>No data posted</h1>';
			exit;
		}
		
		$data = json_decode($postdata, true);
		$ret = array('skus'=>array());
		$updatedAt = date(DATE_ATOM);
		$warehouseId = $data["warehouseId"];
		foreach ($data["skus"] as $sku) {
			//$product = $this->getProductBySku($sku);
			$ret['skus'][] = array(
				'sku' => $sku,
				'warehouseId' => $warehouseId,
				'items' => array(
					array(
						'type' => 'FIT',
						//'count' => isset($product['quantity']) ? $product['quantity'] : 0,
						'count' => intval($this->getProductQuantity($sku, 1)),
						'updatedAt' => $updatedAt
					)
				)
			);
		}
		
		header('Content-Type: application/json;charset=utf-8');
		echo json_encode($ret);		
	}
	
	private function getProductBySku($sku) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE product_id=".intval($sku));
		$product = isset($query->row['product_id']) ? $query->row : array();
		return $product;
	}
	
	private function getProductQuantity($offer_id, $warehouse_id) {
		$option_value_id = 0;
		$option2_value_id = 0;
		if (!$this->config->get('yabuy_long_id')) {
			if (strlen($offer_id) > 12) {
				$offer_id = intval(substr($offer_id, 0, strlen($offer_id) - 12));
				$option_value_id = intval(ltrim(substr($offer_id, -12, 6), '0'));
				$option2_value_id = intval(ltrim(substr($offer_id, -6), '0'));
			}
			elseif (strlen($offer_id) > 6) {
				$offer_id = intval(substr($offer_id, 0, strlen($offer_id) - 6));
				$option_value_id = intval(ltrim(substr($offer_id, -6), '0'));
			}
		}
		$query = $this->db->query("SELECT status, quantity FROM `" . DB_PREFIX . "product` WHERE product_id=".intval($offer_id));
		if (!$query->num_rows || $query->row['status'] != 1 || !$query->row['quantity']) {
			return 0;
		}
		
		if ($option_value_id > 0) {
			$option = $this->getProductOptionData($offer_id, $option_value_id);
			if (isset($option['subtract']) && $option['subtract']) {
				return $option['quantity'];
			}
		}
		if ($option2_value_id > 0) {
			$option2 = $this->getProductOptionData($offer_id, $option2_value_id);
			if (isset($option2['subtract']) && $option2['subtract']) {
				return $option2['quantity'];
			}
		}
		return $query->row['quantity'];
	}
}
