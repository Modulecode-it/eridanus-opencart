<?php
class ModelExtensionModuleProductStickers extends Model {
	public function createColumns () {
		// Custom
		$query = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . "product");
		$product_stickers_custom = false;

		if ($query->rows) {
			
			foreach ($query->rows as $row) {
				if ($row['Field'] == 'product_stickers_custom') {
					$product_stickers_custom = true;
				}
			}

			if (!$product_stickers_custom) {
				$this->db->query("ALTER TABLE `" . DB_PREFIX . "product`  ADD `product_stickers_custom` TEXT NOT NULL;");
			}
		}
	}
}