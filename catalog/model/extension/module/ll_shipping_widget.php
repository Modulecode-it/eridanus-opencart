<?php
/**
 * @author    p0v1n0m <p0v1n0m@gmail.com>
 * @license   Commercial
 * @link      https://github.com/p0v1n0m
 */
class ModelExtensionModuleLLShippingWidget extends Model {
	public function getProductCategoryIDs($product_id) {
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		if (isset($query->rows)) {
			return array_column($query->rows, 'category_id');
		}
	}
}
