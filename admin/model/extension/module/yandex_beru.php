<?php
class ModelExtensionModuleYandexBeru extends Model {
	public function validateKeys($data = array()) {
		if (empty($data['yandex_beru_oauth']) || empty($data['yandex_beru_company_id'])) {
			return false;
		}

		return $this->checkAccessTokens();
	}
	
	public function checkAccessTokens() {
     
//		todo добавить валидацию токенов если это нужно
		return true;
		
    }
	
//	Получение полных данных о товаре
	
	private function getProduct($product_id) {
		
		$query = $this->db->query("
			SELECT DISTINCT 
				p.*, 
				pd.*
			FROM 
				" . DB_PREFIX . "product p 
			LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
			
			WHERE 
				p.product_id = '" . (int)$product_id . "' 
			AND 
				pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		
		$product_data = $query->row;
		
		if(!empty($product_data['manufacturer_id'])){
			$manufacturer_query = $this->db->query("
				SELECT DISTINCT 
					m.name 
				FROM 
					" . DB_PREFIX . "manufacturer m
				WHERE 
					m.manufacturer_id = '" . (int)$product_data['manufacturer_id'] . "'");
			
			if($manufacturer_query->num_rows){
				$product_data['manufacturer'] = $manufacturer_query->row['name'];
			}else{
				$product_data['manufacturer'] = '';
			}
		}
		
		$category_query  =  $this->db->query("
				SELECT DISTINCT 
					cd.name 
				FROM 
					" . DB_PREFIX . "product_to_category p2c
				LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = p2c.category_id) 
				
				WHERE 
					p2c.product_id = '" . (int)$product_id . "'
				AND 
					cd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		
		if($category_query->num_rows){
			$product_data['category'] = $category_query->row['name'];
		}else{
			$product_data['category'] = '';
		}
		
		$images_query = $this->db->query("
				SELECT  
					image
				FROM 
					" . DB_PREFIX . "product_image
				WHERE 
					product_id = '" . (int)$product_id . "'
				LIMIT 10");
		
		if($images_query->num_rows){
			foreach ($images_query->rows as $image_result) {
				$product_data['images'][] = $image_result['image'];
			}
		}else{
			$product_data['images'] = [];
		}
		
		if(!empty($product_data['weight'])){
			$product_data['weight'] = $this->weight->convert($product_data['weight'], $product_data['weight_class_id'], $this->config->get('yandex_beru_weight_kg'));
		}
		if(!empty($product_data['length'])){
			$product_data['length'] = $this->length->convert($product_data['length'], $product_data['length_class_id'], $this->config->get('yandex_beru_length_cm'));
		}
		if(!empty($product_data['width'])){
			$product_data['width'] = $this->length->convert($product_data['width'], $product_data['length_class_id'], $this->config->get('yandex_beru_length_cm'));
		}
		if(!empty($product_data['height'])){
			$product_data['height'] = $this->length->convert($product_data['height'], $product_data['length_class_id'], $this->config->get('yandex_beru_length_cm'));
		}
		return $product_data;
	}
	
//	Получение значений аттрибутов товара
	public function getProductAttributes($product_id){
		$attributes = array();
		
		$query = $this->db->query("
			SELECT 
				* 
			FROM 
				" . DB_PREFIX . "product_attribute pa
			WHERE 
				pa.product_id = '" . (int)$product_id . "' 
			AND 
				pa.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		
		foreach($query->rows as $row){
			$attributes[$row['attribute_id']] = $row['text'];
		}
		
		return $attributes;
	}
	
	public function getProductCategory($product_id) {

		$product_category_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' LIMIT 1");

		if($query->num_rows){
			return $query->row['category_id'];
		}else{
			return false;
		}
	}
	
	public function getSourceFields($data = array()) {
		
		if (!empty($data['source'])) {
			switch ($data['source']) {
				case 'general':
					return $this->getSourceGeneralFields();
					break;
				case 'data':
					return $this->getSourceDataFields();
					break;
				case 'links':
					return $this->getSourceLinksFields();
					break;
				case 'attribute':
					return $this->getSourceAttributeFields();
					break;
				case 'option':
					return $this->getSourceOptionFields();
					break;
				default :
					return array();
					break;
			}
		} else {
			return array();
		}
	}

	private function getSourceGeneralFields() {
		$fields = [
			[
				'key'	=>	'name',
				'name'	=>	'Название товара'
			],
			[
				'key'	=>	'description',
				'name'	=>	'Описание'
			],
			[
				'key'	=>	'meta_title',
				'name'	=>	'Мета-тег Title'
			],
			[
				'key'	=>	'meta_description',
				'name'	=>	'Мета-тег Description'
			],
			[
				'key'	=>	'meta_keyword',
				'name'	=>	'Мета-тег Keyword'
			],
			[
				'key'	=>	'tag',
				'name'	=>	'Теги товара'
			],
		];
		return $fields;
	}

	private function getSourceDataFields() {
		$fields = [
			[
				'key'	=>	'model',
				'name'	=>	'Модель'
			],
			[
				'key'	=>	'sku',
				'name'	=>	'Артикул'
			],
			[
				'key'	=>	'upc',
				'name'	=>	'UPC'
			],
			[
				'key'	=>	'ean',
				'name'	=>	'EAN'
			],
			[
				'key'	=>	'jan',
				'name'	=>	'JAN'
			],
			[
				'key'	=>	'isbn',
				'name'	=>	'ISBN'
			],
			[
				'key'	=>	'mpn',
				'name'	=>	'MPN'
			],
			[
				'key'	=>	'location',
				'name'	=>	'Расположение'
			],
			[
				'key'	=>	'price',
				'name'	=>	'Цена'
			],
			[
				'key'	=>	'quantity',
				'name'	=>	'Количество'
			],
			[
				'key'	=>	'minimum',
				'name'	=>	'Минимальное количество'
			],
			[
				'key'	=>	'shipping',
				'name'	=>	'Необходима доставка'
			],
			[
				'key'	=>	'date_available',
				'name'	=>	'Дата поступления'
			],
			[
				'key'	=>	'length',
				'name'	=>	'Размеры (Длинна)'
			],
			[
				'key'	=>	'width',
				'name'	=>	'Размеры (Ширина)'
			],
			[
				'key'	=>	'height',
				'name'	=>	'Размеры (Высота)'
			],
			[
				'key'	=>	'weight',
				'name'	=>	'Вес'
			],
		];
		return $fields;
	}

	private function getSourceLinksFields() {
		$fields = [
			[
				'key'	=>	'manufacturer',
				'name'	=>	'Производитель'
			],
			[
				'key'	=>	'category',
				'name'	=>	'Категория'
			],
		];
		return $fields;
	}

	public function getSourceAttributeFields() {
		$this->load->model('catalog/attribute');
		
		$fields = array();
		
		$attributes  = $this->model_catalog_attribute->getAttributes();
		
		foreach ($attributes as $attribute) {
			$fields[] = [
				'key'	=>	$attribute['attribute_id'],
				'name'	=>	$attribute['name'],
			];
		}
		return $fields;
	}
	
	public function getSourceOptionFields() {
		$this->load->model('catalog/option');
		
		$fields = array();
		
		$options  = $this->model_catalog_option->getOptions();
		
		foreach ($options as $option) {
			$fields[] = [
				'key'	=>	$option['option_id'],
				'name'	=>	$option['name'],
			];
		}
		return $fields;
	}
	
	//	Product groups
	public function addProductGroup($data) {
		$sql = "INSERT INTO " . DB_PREFIX . "yb_product_group SET name = '" . $this->db->escape($data['name']) . "', filter_name = '" . $this->db->escape($data['filter_name']) . "', filter_model = '" . $this->db->escape($data['filter_model']) . "'";

		if (isset($data['filter_category']) && $data['filter_category'] !== '') {
			$sql .= ", filter_category = '" . json_encode($data['filter_category']) . "'";
		} else {
			$sql .= ", filter_category = 'null'";
		}
		
		if (isset($data['filter_product']) && $data['filter_product'] !== '') {
			$sql .= ", filter_product = '" . json_encode($data['filter_product']) . "'";
		} else {
			$sql .= ", filter_product = 'null'";
		}

		if (isset($data['filter_option']) && $data['filter_option'] !== '') {
			$sql .= ", filter_option = '" . (float)$data['filter_option'] . "'";
		} else {
			$sql .= ", filter_option = 'null'";
		}

		if (isset($data['filter_price_from']) && $data['filter_price_from'] !== '') {
			$sql .= ", filter_price_from = '" . (float)$data['filter_price_from'] . "'";
		}

		if (isset($data['filter_price_to']) && $data['filter_price_to'] !== '') {
			$sql .= ", filter_price_to = '" . (float)$data['filter_price_to'] . "'";
		}

		if (isset($data['filter_quantity_from']) && $data['filter_quantity_from'] !== '') {
			$sql .= ", filter_quantity_from = '" . (int)$data['filter_quantity_from'] . "'";
		}

		if (isset($data['filter_quantity_to']) && $data['filter_quantity_to'] !== '') {
			$sql .= ", filter_quantity_to = '" . (int)$data['filter_quantity_to'] . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= ", filter_status = '" . (int)$data['filter_status'] . "'";
		}

		$this->db->query($sql);

		$group_id = $this->db->getLastId();

		$filtered_products = $this->getProductIdsByFilters($data);

		foreach ($filtered_products as $product) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "yb_product_to_product_group SET product_id = '" . (int)$product['product_id'] . "', group_id = '" . (int)$group_id . "'");
		}
	}

	public function editProductGroup($group_id, $data) {
		$sql = "UPDATE " . DB_PREFIX . "yb_product_group SET name = '" . $this->db->escape($data['name']) . "', filter_name = '" . $this->db->escape($data['filter_name']) . "', filter_model = '" . $this->db->escape($data['filter_model']) . "'";

		if (isset($data['filter_category']) && $data['filter_category'] !== '') {
			$sql .= ", filter_category = '" . json_encode($data['filter_category']) . "'";
		} else {
			$sql .= ", filter_category = 'null'";
		}
		
		if (isset($data['filter_product']) && $data['filter_product'] !== '') {
			$sql .= ", filter_product = '" . json_encode($data['filter_product']) . "'";
		} else {
			$sql .= ", filter_product = 'null'";
		}

		if (isset($data['filter_option']) && $data['filter_option'] !== '') {
			$sql .= ", filter_option = '" . (float)$data['filter_option'] . "'";
		} else {
			$sql .= ", filter_option = 'null'";
		}

		if (isset($data['filter_price_from']) && $data['filter_price_from'] !== '') {
			$sql .= ", filter_price_from = '" . (float)$data['filter_price_from'] . "'";
		} else {
			$sql .= ", filter_price_from = NULL";
		}

		if (isset($data['filter_price_to']) && $data['filter_price_to'] !== '') {
			$sql .= ", filter_price_to = '" . (float)$data['filter_price_to'] . "'";
		} else {
			$sql .= ", filter_price_to = NULL";
		}

		if (isset($data['filter_quantity_from']) && $data['filter_quantity_from'] !== '') {
			$sql .= ", filter_quantity_from = '" . (int)$data['filter_quantity_from'] . "'";
		} else {
			$sql .= ", filter_quantity_from = NULL";
		}

		if (isset($data['filter_quantity_to']) && $data['filter_quantity_to'] !== '') {
			$sql .= ", filter_quantity_to = '" . (int)$data['filter_quantity_to'] . "'";
		} else {
			$sql .= ", filter_quantity_to = NULL";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= ", filter_status = '" . (int)$data['filter_status'] . "'";
		} else {
			$sql .= ", filter_status = NULL";
		}

		$sql .= " WHERE group_id = '" . (int)$group_id . "'";

		$this->db->query($sql);

		$this->db->query("DELETE FROM " . DB_PREFIX . "yb_product_to_product_group WHERE group_id = '" . (int)$group_id . "'");

		$filtered_products = $this->getProductIdsByFilters($data);

		foreach ($filtered_products as $product) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "yb_product_to_product_group SET product_id = '" . (int)$product['product_id'] . "', group_id = '" . (int)$group_id . "'");
		}
	}

	public function deleteProductGroup($group_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "yb_product_group WHERE group_id = '" . (int)$group_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "yb_product_to_product_group WHERE group_id = '" . (int)$group_id . "'");
	}

	public function getProductGroup($group_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "yb_product_group WHERE group_id = '" . (int)$group_id . "'");

		return $query->row;
	}

	public function getProductGroups($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "yb_product_group ORDER BY group_id DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalProductGroups() {
		$query = $this->db->query("SELECT COUNT(DISTINCT group_id) AS total FROM " . DB_PREFIX . "yb_product_group");

		return $query->row['total'];
	}

	// Products in groups
	public function getProductsFromGroup($group_id) {
		$group_product_data = array();

		$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "yb_product_to_product_group WHERE group_id = '" . (int)$group_id . "'");

		foreach ($query->rows as $result) {
			$group_product_data[] = $result['product_id'];
		}

		return $group_product_data;
	}
	
	// Products in groups
	public function getProductsFromGroups($groups = array(), $data = array()) {
		$groups = array_map('intval', $groups);
		$groups_str = implode(",", $groups);
		
		$group_product_data = array();
		
		$sql = "SELECT product_id FROM " . DB_PREFIX . "yb_product_to_product_group WHERE group_id IN (" . $this->db->escape($groups_str) . ") GROUP BY product_id";
	
		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$group_product_data[] = $result['product_id'];
		}

		return $group_product_data;
	}
	
	public function getProductsByFilters($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) LEFT JOIN " . DB_PREFIX . "product_option po ON (p.product_id = po.product_id)";

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
//		if (!empty($data['filter_product'])) {
//			$sql .= "AND ( 1 ";
//		}
		
		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
		}

		if (!empty($data['filter_category'])) {
			$sql .= " AND p2c.category_id IN ('". implode("','", array_map('intval', $data['filter_category'])) . "')";
		}

		if (!empty($data['filter_option'])) {
			$sql .= " AND po.option_id IN ('". implode("','", array_map('intval', $data['filter_option'])) . "')";
		}

		if (isset($data['filter_price_from']) && $data['filter_price_from'] !== '') {
			$sql .= " AND p.price >= '" . (float)($data['filter_price_from']) . "'";
		}

		if (isset($data['filter_price_to']) && $data['filter_price_to'] !== '') {
			$sql .= " AND p.price <= '" . (float)($data['filter_price_to']) . "'";
		}

		if (isset($data['filter_quantity_from']) && $data['filter_quantity_from'] !== '') {
			$sql .= " AND p.quantity >= '" . (int)$data['filter_quantity_from'] . "'";
		}

		if (isset($data['filter_quantity_to']) && $data['filter_quantity_to'] !== '') {
			$sql .= " AND p.quantity <= '" . (int)$data['filter_quantity_to'] . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

//		if (!empty($data['filter_product'])) {
//			$sql .= ") OR p.product_id IN ('". implode("','", array_map('intval', $data['filter_product'])) . "')";
//		}
		
		if (!empty($data['filter_product'])) {
			$sql .= " AND p.product_id IN ('". implode("','", array_map('intval', $data['filter_product'])) . "')";
		}
		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.price',
			'p.quantity',
			'p.status',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalProductsByFilters($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) LEFT JOIN " . DB_PREFIX . "product_option po ON (p.product_id = po.product_id)";

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
//		if (!empty($data['filter_product'])) {
//			$sql .= "AND ( 1 ";
//		}
		
		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
		}

		if (!empty($data['filter_category'])) {
			$sql .= " AND p2c.category_id IN ('". implode("','", array_map('intval', $data['filter_category'])) . "')";
		}
		
		if (!empty($data['filter_option'])) {
			$sql .= " AND po.option_id IN ('". implode("','", array_map('intval', $data['filter_option'])) . "')";
		}

		if (isset($data['filter_price_from']) && $data['filter_price_from'] !== '') {
			$sql .= " AND p.price >= '" . (float)($data['filter_price_from']) . "'";
		}

		if (isset($data['filter_price_to']) && $data['filter_price_to'] !== '') {
			$sql .= " AND p.price <= '" . (float)($data['filter_price_to']) . "'";
		}

		if (isset($data['filter_quantity_from']) && $data['filter_quantity_from'] !== '') {
			$sql .= " AND p.quantity >= '" . (int)$data['filter_quantity_from'] . "'";
		}

		if (isset($data['filter_quantity_to']) && $data['filter_quantity_to'] !== '') {
			$sql .= " AND p.quantity <= '" . (int)$data['filter_quantity_to'] . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
//		if (!empty($data['filter_product'])) {
//			$sql .= ") OR p.product_id IN ('". implode("','", array_map('intval', $data['filter_product'])) . "')";
//		}
		if (!empty($data['filter_product'])) {
			$sql .= " AND p.product_id IN ('". implode("','", array_map('intval', $data['filter_product'])) . "')";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getProductIdsByFilters($data = array()) {
		$sql = "SELECT DISTINCT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) LEFT JOIN " . DB_PREFIX . "product_option po ON (p.product_id = po.product_id)";

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

//		if (!empty($data['filter_product'])) {
//			$sql .= "AND ( 1 ";
//		}
		
		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_model'])) {
			$sql .= " AND p.model LIKE '%" . $this->db->escape($data['filter_model']) . "%'";
		}

		if (!empty($data['filter_category'])) {
			$sql .= " AND p2c.category_id IN ('". implode("','", array_map('intval', $data['filter_category'])) . "')";
		}
		
		if (!empty($data['filter_option'])) {
			$sql .= " AND po.option_id IN ('". implode("','", array_map('intval', $data['filter_option'])) . "')";
		}


		if (isset($data['filter_price_from']) && $data['filter_price_from'] !== '') {
			$sql .= " AND p.price >= '" . (float)($data['filter_price_from']) . "'";
		}

		if (isset($data['filter_price_to']) && $data['filter_price_to'] !== '') {
			$sql .= " AND p.price <= '" . (float)($data['filter_price_to']) . "'";
		}

		if (isset($data['filter_quantity_from']) && $data['filter_quantity_from'] !== '') {
			$sql .= " AND p.quantity >= '" . (int)$data['filter_quantity_from'] . "'";
		}

		if (isset($data['filter_quantity_to']) && $data['filter_quantity_to'] !== '') {
			$sql .= " AND p.quantity <= '" . (int)$data['filter_quantity_to'] . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		
//		if (!empty($data['filter_product'])) {
//			$sql .= ") OR p.product_id IN ('". implode("','", array_map('intval', $data['filter_product'])) . "')";
//		}
		
		if (!empty($data['filter_product'])) {
			$sql .= " AND p.product_id IN ('". implode("','", array_map('intval', $data['filter_product'])) . "')";
		}
		
		$query = $this->db->query($sql);

		return $query->rows;
	}
	// /Products in groups

	public function getOptionList() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option o LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->rows;

	}
	
	public function getPrimaryOptionsCombinations($product_id){


		$product_options = $this->getPrimaryProductoptions($product_id);
		
		$primary_options_combinations = array();
		
		$this->getPrimaryOptionCombinations($product_options, $primary_options_combinations);

		
		if($primary_options_combinations){
			return $primary_options_combinations;
		}else{
			return array("");
		}
		
		
	}
	
	public function getPrimaryOptionCombinations($product_options, &$primary_options_combinations, $prefix = ""){
		
		if($product_options){
			$product_option = array_shift($product_options);
			
			foreach($product_option['option_values'] as $option_value){
				$prefix_new = $prefix.'-'.$product_option['option_id'].'-'.$option_value;
				
				$this->getPrimaryOptionCombinations($product_options, $primary_options_combinations,$prefix_new);
			}
		}else{
			$primary_options_combinations[] = $prefix;
		}
	}
	
	public function getPrimaryProductoptions($product_id){
		$product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po WHERE po.product_id = '" . (int)$product_id . "' AND po.required = 1 ORDER BY option_id ASC");
		
		$product_options = array();
		foreach ($product_option_query->rows as $product_option) {
			$option_values = array();
			
			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov WHERE pov.product_option_id = '" . (int)$product_option['product_option_id'] . "'");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$option_values[] = $product_option_value['option_value_id'];
			}
			if($option_values){
				$product_options[] = [
					'option_id' => $product_option['option_id'],
					'option_values' => $option_values
				];
			}
		}

		


		return $product_options;
	}
	
	public function getOffer($shopSku){
		
		$query = $this->db->query("SELECT DISTINCT *, status AS beru_status FROM " . DB_PREFIX . "yb_offers WHERE shopSku = '" . $this->db->escape($shopSku) . "'");	
		//$query = $this->db->query("SELECT DISTINCT *, status AS beru_status FROM " . DB_PREFIX . "yb_offers WHERE shopSku = '" . 15405 . "'");	
		
		return $query->row;
	}
	
	public function getOfferByKey($key){
		
		$query = $this->db->query("SELECT DISTINCT *, status AS beru_status FROM " . DB_PREFIX . "yb_offers WHERE `key` = '" . $this->db->escape($key) . "'");
		
		return $query->row;
	}
//	Добавление сохраненных предложений
	public function addOffer($offer_data){
		$sql = "
			INSERT INTO " . DB_PREFIX . "yb_offers 
			SET 
				`key` = '" . $this->db->escape($offer_data['key']) . "',
				`yandex_sku` = '" . $this->db->escape(isset($offer_data['marketSku'])?$offer_data['marketSku']:'') . "',
				`yandex_category` = '" . $this->db->escape(isset($offer_data['marketCategoryId'])?$offer_data['marketCategoryId']:''). "',
				`marketSkuName` = '" . $this->db->escape(isset($offer_data['marketSkuName'])?$offer_data['marketSkuName']:'') . "',
				`marketCategoryName` = '" . $this->db->escape(isset($offer_data['marketCategoryName'])?$offer_data['marketCategoryName']:'') . "', 
				`status` = '',
				`shopSku` = '" . $this->db->escape(isset($offer_data['shopSku'])?$offer_data['shopSku']:$offer_data['key']) . "'";
					
		$this->db->query($sql);
	}
	
//	Обновление сохраненных предложений
	public function updateOffer($offer_data){
		echo "<script>console.log('updateOffer_offer_data: " . json_encode($offer_data) . "' );</script>";
		$sql = "
			UPDATE " . DB_PREFIX . "yb_offers 
			SET 
				`yandex_sku` = '" . $this->db->escape(isset($offer_data['yandex_sku'])?$offer_data['yandex_sku']:'') . "',
				`yandex_category` = '" . $this->db->escape(isset($offer_data['marketCategoryId'])?$offer_data['marketCategoryId']:''). "',
				`marketSkuName` = '" . $this->db->escape(isset($offer_data['marketSkuName'])?$offer_data['marketSkuName']:'') . "',
				`marketCategoryName` = '" . $this->db->escape(isset($offer_data['marketCategoryName'])?$offer_data['marketCategoryName']:'') . "'";
				
		echo "<script>console.log('updateOffer_marketCategoryName: " . $offer_data['marketCategoryName'] . "' );</script>";
		echo "<script>console.log('updateOffer_marketSkuName: " . $offer_data['marketSkuName'] . "' );</script>";
		echo "<script>console.log('updateOffer_marketSku: " . $offer_data['yandex_sku'] . "' );</script>";
		if(!empty($offer_data['status'])){
			$sql .= " ,`status` = '".$this->db->escape($offer_data['status'])."'";
		}
		
		$sql .= "WHERE `shopSku` = '" . $this->db->escape($offer_data['shopSku']) . "'";
					
		$this->db->query($sql);
	}
	
	public function updateOfferShopSku($key, $shopSku){
		echo "<script>console.log('updateOfferShopSku: " . $key . "' );</script>";
		echo "<script>console.log('updateOfferShopSku: " . $shopSku . "' );</script>";
		$this->db->query("UPDATE " . DB_PREFIX . "yb_offers SET `shopSku` = '" . $this->db->escape($shopSku) . "' WHERE `key` = '" . $this->db->escape($key) . "'");
	}
	
	public function getOffers($data = array()){
		
		$sql = "SELECT DISTINCT o.shopSku FROM " . DB_PREFIX . "yb_offers o WHERE 1 ";
		
		if (!empty($data['filter_shopSku'])) {
			$sql .= " AND o.shopSku LIKE '%" . $this->db->escape($data['filter_shopSku']) . "%'";
		}
		
		if (!empty($data['filter_marketSkuName'])) {
			$sql .= " AND o.marketSkuName LIKE '%" . $this->db->escape($data['filter_marketSkuName']) . "%'";
		}
		
		if (!empty($data['filter_status'])) {
			$sql .= " AND o.status = '" . $this->db->escape($data['filter_status']) . "'";
		}
		
		if(!empty($data['filter_loaded'])){
			$sql .= " AND o.status != ''";
//			READY — товар прошел модерацию.
//			IN_WORK — товар проходит модерацию.
//			NEED_CONTENT — для товара без SKU на Яндексе market-sku / marketSku нужно найти карточку самостоятельно или создать ее.
//			NEED_INFO — товар не прошел модерацию из-за ошибок или недостающих сведений в описании товара.
//			REJECTED — товар не прошел модерацию, так как Беру не планирует размещать подобные товары.
//			SUSPENDED — товар не прошел модерацию, так как Беру пока не размещает подобные товары.
//			OTHER — товар не прошел модерацию по другой причине.
		}

		
		
		if (isset($data['filter_price_from'])) {

			$sql .= " AND o.offer_price >= " . (float)$data['filter_price_from'] . " ";
		}
		
		if (isset($data['filter_price_to'])) {
			$sql .= " AND o.offer_price <= " . (float)$data['filter_price_to'] . " ";
		}
		
		if (isset($data['start']) || isset($data['limit'])) {
			if (empty($data['start']) || $data['start'] < 0) {
				$data['start'] = 0;
			}

			if (empty($data['limit']) || $data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getOffersUpdatePrice($data = array()){
		if (!empty($data['filter_price_different'])) {
			//Нужно сбросить к дефолтной валюте если 0 чтобы работал лефт джоин с валютами
			$this->db->query("UPDATE " . DB_PREFIX . "product SET currency_id = '1' WHERE currency_id = '0'");
		}
		
		$sql = "SELECT DISTINCT o.shopSku FROM " . DB_PREFIX . "yb_offers o ";
// 		$sql = "SELECT * FROM " . DB_PREFIX . "yb_offers o WHERE o.shopSku != '' ";
		
		if (!empty($data['filter_price_different'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON o.key LIKE concat(p.product_id,'%') ";
			$sql .= " LEFT JOIN " . DB_PREFIX . "currency c ON (p.currency_id = c.currency_id) ";
		}
		
		$sql .= "WHERE o.shopSku != '' ";
		
		if (!empty($data['filter_price_different'])) {
			$sql .= " AND CEILING(p.yandex_price/c.value) != o.offer_price ";
			$sql .= " AND (p.product_id = o.key OR o.key LIKE concat(p.product_id,'-','%')) ";
		}
		
		if (!empty($data['filter_shopSku'])) {
			$sql .= " AND o.shopSku LIKE '%" . $this->db->escape($data['filter_shopSku']) . "%'";
		}
		
		if (!empty($data['filter_marketSkuName'])) {
			$sql .= " AND o.marketSkuName LIKE '%" . $this->db->escape($data['filter_marketSkuName']) . "%'";
		}
		
		if (!empty($data['filter_status'])) {
			$sql .= " AND o.status = '" . $this->db->escape($data['filter_status']) . "'";
		}
		
		if(!empty($data['filter_loaded'])){
			$sql .= " AND o.status != ''";
//			READY — товар прошел модерацию.
//			IN_WORK — товар проходит модерацию.
//			NEED_CONTENT — для товара без SKU на Яндексе market-sku / marketSku нужно найти карточку самостоятельно или создать ее.
//			NEED_INFO — товар не прошел модерацию из-за ошибок или недостающих сведений в описании товара.
//			REJECTED — товар не прошел модерацию, так как Беру не планирует размещать подобные товары.
//			SUSPENDED — товар не прошел модерацию, так как Беру пока не размещает подобные товары.
//			OTHER — товар не прошел модерацию по другой причине.
		}

		
		
		if (isset($data['filter_price_from'])) {

			$sql .= " AND o.offer_price >= " . (float)$data['filter_price_from'] . " ";
		}
		
		if (isset($data['filter_price_to'])) {
			$sql .= " AND o.offer_price <= " . (float)$data['filter_price_to'] . " ";
		}
		
		if (isset($data['start']) || isset($data['limit'])) {
			if (empty($data['start']) || $data['start'] < 0) {
				$data['start'] = 0;
			}

			if (empty($data['limit']) || $data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		
		$query = $this->db->query($sql);

		return $query->rows;
	}
	
	public function getOfferInfo($data = array()){
    
        $sql = "SELECT * FROM " . DB_PREFIX . "yb_offers o ";
		$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON o.key = p.product_id";
		$sql .= " WHERE o.shopSku != '' ";
		
		$query = $this->db->query($sql);

		return $query->rows;
	}
	
	public function getProductCards($data = array()){
		
		$sql = "SELECT DISTINCT o.yandex_sku FROM " . DB_PREFIX . "yb_offers o WHERE 1 ";
		
		if (!empty($data['filter_shopSku'])) {
			$sql .= " AND o.shopSku LIKE '%" . $this->db->escape($data['filter_shopSku']) . "%'";
		}
		
		if (!empty($data['filter_marketSkuName'])) {
			$sql .= " AND o.marketSkuName LIKE '%" . $this->db->escape($data['filter_marketSkuName']) . "%'";
		}
		
		if (!empty($data['filter_status'])) {
			$sql .= " AND o.status = '" . $this->db->escape($data['filter_status']) . "'";
		}
		
		if(!empty($data['filter_loaded'])){
			$sql .= " AND o.status != ''";
		}
		
		if (isset($data['filter_price_from'])) {

			$sql .= " AND o.offer_price >= " . (float)$data['filter_price_from'] . " ";
		}
		
		if (isset($data['filter_price_to'])) {
			$sql .= " AND o.offer_price <= " . (float)$data['filter_price_to'] . " ";
		}
		
		if (isset($data['start']) || isset($data['limit'])) {
			if (empty($data['start']) || $data['start'] < 0) {
				$data['start'] = 0;
			}

			if (empty($data['limit']) || $data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getProductCard($yandex_sku){
		
		$query = $this->db->query("SELECT DISTINCT *, status AS beru_status FROM " . DB_PREFIX . "yb_offers WHERE yandex_sku = '" . $this->db->escape($yandex_sku) . "'");	
		
		return $query->row;
	}

	public function getTotalOffers($data = array()){
		$sql = "SELECT COUNT(DISTINCT o.shopSku) AS total FROM " . DB_PREFIX . "yb_offers o WHERE 1 ";
		
		if (!empty($data['filter_shopSku'])) {
			$sql .= " AND o.shopSku LIKE '%" . $this->db->escape($data['filter_shopSku']) . "%'";
		}
		
		if (!empty($data['filter_marketSkuName'])) {
			$sql .= " AND o.marketSkuName LIKE '%" . $this->db->escape($data['filter_marketSkuName']) . "%'";
		}
		
		if (!empty($data['filter_status'])) {
			$sql .= " AND o.status = '" . $this->db->escape($data['filter_status']) . "'";
		}
		
		if(!empty($data['filter_loaded'])){
			$sql .= " AND o.status != ''";
		}
		
		if (isset($data['filter_price_from'])) {
			$sql .= " AND o.offer_price >= " . (float)$data['filter_price_from'] . " ";
		}
		
		if (isset($data['filter_price_to'])) {
			$sql .= " AND o.offer_price <= " . (float)$data['filter_price_to'] . " ";
		}
		
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	
	public function getTotalOffersUpdatePrice($data = array(), $type = 'shopSku'){
		if (!empty($data['filter_price_different'])) {
			//Нужно сбросить к дефолтной валюте если 0 чтобы работал лефт джоин с валютами
			$this->db->query("UPDATE " . DB_PREFIX . "product SET currency_id = '1' WHERE currency_id = '0'");
		}
		
		if ($type == 'yandex_sku') {
			$sql = "SELECT COUNT(DISTINCT o.yandex_sku) AS total FROM " . DB_PREFIX . "yb_offers o ";
		} else {
			$sql = "SELECT COUNT(DISTINCT o.shopSku) AS total FROM " . DB_PREFIX . "yb_offers o ";
		}
		
		
		if (!empty($data['filter_price_different'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON o.key LIKE concat(p.product_id,'%') ";
			$sql .= " LEFT JOIN " . DB_PREFIX . "currency c ON (p.currency_id = c.currency_id) ";
		}
		
		if ($type == 'yandex_sku') {
			$sql.= "WHERE o.yandex_sku != '' ";
		} else {
			$sql.= "WHERE o.shopSku != '' ";
		}
		
		if (!empty($data['filter_price_different'])) {
			$sql .= " AND CEILING(p.yandex_price/c.value) != o.offer_price ";
			$sql .= " AND (p.product_id = o.key OR o.key LIKE concat(p.product_id,'-','%')) ";
		}

// 		$sql = "SELECT COUNT(DISTINCT o.shopSku) AS total FROM " . DB_PREFIX . "yb_offers o WHERE 1 ";
		
		if (!empty($data['filter_shopSku'])) {
			$sql .= " AND o.shopSku LIKE '%" . $this->db->escape($data['filter_shopSku']) . "%'";
		}
		
		if (!empty($data['filter_marketSkuName'])) {
			$sql .= " AND o.marketSkuName LIKE '%" . $this->db->escape($data['filter_marketSkuName']) . "%'";
		}
		
		if (!empty($data['filter_status'])) {
			$sql .= " AND o.status = '" . $this->db->escape($data['filter_status']) . "'";
		}
		
		if(!empty($data['filter_loaded'])){
			$sql .= " AND o.status != ''";
		}
		
		if (isset($data['filter_price_from'])) {
			$sql .= " AND o.offer_price >= " . (float)$data['filter_price_from'] . " ";
		}
		
		if (isset($data['filter_price_to'])) {
			$sql .= " AND o.offer_price <= " . (float)$data['filter_price_to'] . " ";
		}
		
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
//	обновление статуса предложения
	public function updateOfferStatus($shopSku, $status){
		$this->db->query("UPDATE " . DB_PREFIX . "yb_offers SET status = '". $this->db->escape($status)."' WHERE shopSku = '" . $this->db->escape($shopSku) . "'");
	}

	public function getProductOfferCart($id, $filter_data = array()){
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/option');

		$product_card_info = $this->getOfferByMSKU($id);

		return $product_card_info;

	}

	public function getOfferByMSKU($yandex_sku){

		$query = $this->db->query("SELECT DISTINCT *, status AS beru_status FROM " . DB_PREFIX . "yb_offers WHERE yandex_sku = '" . $this->db->escape($yandex_sku) . "'");	
		
		return $query->row;
	}



	
	public function getFullOfferInfo($id, $filter_data = array(), $type = 'shopSku'){
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/option');
		
		if($type == 'shopSku'){
			//$filter_data = ['shopSku','name','category','yandex_sku','image','images'];
			$offer_info = $this->getOffer($id);
			echo "<script>console.log('offer_infooffer_info1: " . json_encode($offer_info) . "' );</script>";
			echo "<script>console.log('offer_infooffer_info1: " . $id . "' );</script>";
		}else{
			$offer_info = $this->getOfferByKey($id);
			echo "<script>console.log('offer_infooffer_info2: " . json_encode($offer_info) . "' );</script>";
			echo "<script>console.log('offer_infooffer_info2: " . $id . "' );</script>";
		}
		
		if($offer_info){
			$offer_key_data = explode('-',$offer_info['key']);
		}else{
			$offer_key_data = explode('-',$id);
		}
		
		$product_id = array_shift($offer_key_data);

		$options_text = '';
		
		$product_options = array();
		
		if(!empty($offer_key_data)){
			
			$options = array_chunk($offer_key_data, 2);
			
			foreach($options as $option){
				
				$option_value = $this->model_catalog_option->getOptionValue($option[1]);
				$options_text .= ' ' . $option_value['name'];
				
				$product_options[$option[1]] = $option_value;
			}
			
		}
		
		$offer_data = array();
		
		$product_info = $this->getProduct($product_id);
		
		echo "<script>console.log('product_info_: " . json_encode($product_info) . "' );</script>";
		$product_info['name'] = $product_info['name'].$options_text;
		
		$product_attributes = $this->getProductAttributes($product_id);
		
		foreach($filter_data as $filter_data_row){
			if(array_key_exists($filter_data_row, $product_info)){
				$offer_data[$filter_data_row] = $product_info[$filter_data_row];
			}elseif(array_key_exists($filter_data_row, $offer_info)){
				$offer_data[$filter_data_row] = $offer_info[$filter_data_row];
			}else{
//				Необходимо получить данные о товаре через 
				$fieldsets = $this->config->get('yandex_beru_fieldsets');
				
//				Проверяем задано ли в сопоставлении полей 
				if(array_key_exists($filter_data_row, $fieldsets)){
//					Если source не задан значит это массив
					if(isset($fieldsets[$filter_data_row]['source'])){
						
						switch ($fieldsets[$filter_data_row]['source']) {
							case 'general':
							case 'data':
							case 'links':
								$offer_data[$filter_data_row] = isset($product_info[$fieldsets[$filter_data_row]['field']])?$product_info[$fieldsets[$filter_data_row]['field']]:"";
								break;
							case 'attribute':
								$offer_data[$filter_data_row] = isset($product_attributes[$fieldsets[$filter_data_row]['field']])?$product_attributes[$fieldsets[$filter_data_row]['field']]:"";
								break;
							case 'option':
								$offer_data[$filter_data_row] = isset($product_options[$fieldsets[$filter_data_row]['field']])?$product_options[$fieldsets[$filter_data_row]['field']]:"";
								break;
							default :
								break;
						}
					}else{
						foreach($fieldsets[$filter_data_row] as $key => $filter_data_row_item){
							switch ($filter_data_row_item['source']) {
								case 'general':
								case 'data':
								case 'links':
									$offer_data[$filter_data_row][$key] = isset($product_info[$filter_data_row_item['field']])?$product_info[$filter_data_row_item['field']]:"";
									break;
								case 'attribute':
									$offer_data[$filter_data_row][$key] = isset($product_attributes[$filter_data_row_item['field']])?$product_attributes[$filter_data_row_item['field']]:"";
									break;
								case 'option':
									$offer_data[$filter_data_row][$key] = isset($product_options[$filter_data_row_item['field']])?$product_options[$filter_data_row_item['field']]:"";
									break;
								default :
									break;
							}	
						}
					}
				}else{
					$offer_data[$filter_data_row] = '';
				}
			
			}	
		}
		

		echo "<script>console.log('offer_data_getFullOfferInfo: " . json_encode($offer_data) . "' );</script>";
		return $offer_data;
	}

	public function updatePriceByMSKU($price, $yandex_sku){

		$sql =  $this->db->query("UPDATE " . DB_PREFIX . "yb_offers SET offer_price = '" . $price . "' WHERE yandex_sku =  '" . $yandex_sku . "'");
		
	}

	public function updatePriceByShopSku($price, $shopSku){

		$sql =  $this->db->query("UPDATE " . DB_PREFIX . "yb_offers SET offer_price = '" . $price . "' WHERE shopSku =  '" . $shopSku . "'");
		
	}

	public function updateRecomendPrice($marketSku, $priceSuggestion){

		$sql = '';

		foreach ($priceSuggestion as $price) {
			
			switch ($price['type']) {
				case "MIN_PRICE_MARKET":
					$sql .= "minPriceOnBeru = '" . $price['price'] . "', ";
					break;
				case "BUYBOX":
					$sql .= "byboxPriceOnBeru = '" . $price['price'] . "', ";
					break;
				case "DEFAULT_OFFER":
					$sql .= "defaultPriceOnBeru = '" . $price['price'] . "', ";
					break;
				case "MAX_DISCOUNT_BASE":
					$sql .= "maxPriceOnBeru = '" . $price['price'] . "', ";
					break;		
				case "MARKET_OUTLIER_PRICE":
					$sql .= "outlierPrice = '" . $price['price'] . "', ";
					break;						
			}

		}

		if($sql != ""){
			$sql = substr($sql,0,-2);

			$this->db->query("UPDATE " . DB_PREFIX . "yb_offers SET " . $sql . " WHERE yandex_sku = '" . $marketSku . "'");
		}
		
	}

	public function logPrice($data){

		foreach ($data as $offer) {

			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "yb_offers WHERE yandex_sku = '" . $offer['marketSku'] . "'");

			$this->db->query("INSERT INTO " . DB_PREFIX . "yb_history_price SET user = '" . (int)$this->user->getId() . "', price = '" . $this->db->escape($offer['price']['value']) . "', offer_id = '" . $this->db->escape($query->row['shopSku']) . "', offer_name = '" . $this->db->escape($query->row['marketSkuName']) . "',  date_update =  NOW()");

		}

	}

	public function getHistoryPrice($data){

		$sql ="SELECT * FROM " . DB_PREFIX . "yb_history_price yhb LEFT JOIN " . DB_PREFIX . "user u ON(yhb.user = u.user_id) LEFT JOIN " . DB_PREFIX . "yb_offers yo ON(yhb.offer_id = yo.shopSku) WHERE 1";


		if (isset($data['filter_date_form'])) {

			$sql .= " AND yhb.date_update >= '" . $data['filter_date_form'] . "' ";
		}
		
		if (isset($data['filter_date_to'])) {
			$sql .= " AND yhb.date_update <= '" . $data['filter_date_to'] . "' ";
		}
		
		if (isset($data['start']) || isset($data['limit'])) {
			if (empty($data['start']) || $data['start'] < 0) {
				$data['start'] = 0;
			}

			if (empty($data['limit']) || $data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		
		$query = $this->db->query($sql);

		return $query->rows;

	}

	public function getTotalHistoryPrice($data = array()){

		$sql = "SELECT COUNT(yhp.offer_id) AS total FROM " . DB_PREFIX . "yb_history_price yhp WHERE 1 ";
		
		if (isset($data['filter_date_form'])) {
			$sql .= " AND yhp.date_update >= '" . $data['filter_date_form'] . "' ";
		}
		
		if (isset($data['filter_date_to'])) {
			$sql .= " AND yhp.date_update <= '" . $data['filter_date_to'] . "' ";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function findOfferErrors($shopSku, $type = 'shopSku'){
		$errors = array();
		
		$filter_data = ['shopSku','name','category','manufacturer','manufacturerCountries','vendor','image', 'length', 'width', 'height'];
		$offer_data = $this->getFullOfferInfo($shopSku, $filter_data, $type);
		//offer_infooffer_info1: {"key":"15401","shopSku":"15401","yandex_sku":"","yandex_category":"","status":"IN_WORK","marketSkuName":"","marketCategoryName":"Рюкзаки и ранцы для школы","offer_price":"0","minPriceOnBeru":"0","maxPriceOnBeru":"0","defaultPriceOnBeru":"0","byboxPriceOnBeru":"0","outlierPrice":"0","beru_status":"IN_WORK"}
		echo "<script>console.log('findOfferErrors_ offer_data: " . json_encode($offer_data) . "' );</script>";
		foreach($filter_data as $field){
			echo "<script>console.log('findOfferErrors_offer_data[field]: " . $offer_data[$field] . "' );</script>";
			echo "<script>console.log('findOfferErrors_offer_data[field]: " . $field . "' );</script>";
			if(empty($offer_data[$field])){
				$errors[] = 'error_'.$field;
			}
			if(($field == 'length' || $field == 'width' || $field == 'height') && $offer_data[$field]==0.0){
				$errors[] = 'error_'.$field;
			}
		}
		return $errors;
	}
	
	public function getUpdatesOfferInfo($shopSku){
		echo "<script>console.log('getUpdatesOfferInfo: " . $shopSku . "' );</script>";
		$fieldsets = $this->config->get('yandex_beru_fieldsets');

		$filter_data = ['shopSku','name','category','description','yandex_sku','image','images'];
		//$shopSku=15401;
		foreach($fieldsets as $key => $fieldset){
			$filter_data[] = $key;
		}
		echo "<script>console.log('getUpdatesOfferInfo: " . $shopSku . "' );</script>";
		//Текущие данные о товаре хранящиеся в базе
		//offer_infooffer_info1: {"key":"15401","shopSku":"15401","yandex_sku":"","yandex_category":"","status":"IN_WORK","marketSkuName":"","marketCategoryName":"Рюкзаки и ранцы для школы","offer_price":"0","minPriceOnBeru":"0","maxPriceOnBeru":"0","defaultPriceOnBeru":"0","byboxPriceOnBeru":"0","outlierPrice":"0","beru_status":"IN_WORK"}
		$offer_data = $this->getFullOfferInfo($shopSku, $filter_data, 'shopSku');
				echo "<script>console.log('offer_data_2: " . $offer_data . "' );</script>";
		echo "<script>console.log('offer_data_2: " . json_encode($offer_data) . "' );</script>";
		//Дополняем данные информацией пришедшей с рекомендаций. 
		$this->api = new yandex_beru();			
		$this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
		
		$component = $this->api->loadComponent('offerMappingEntriesSuggestions');
		
		$post_data['offers'][0] = $this->getFullOfferInfo($shopSku,["shopSku","name","category","description","ean","vendor"], 'shopSku');
		//['shopSku','name','category','manufacturer','manufacturerCountries','vendor','image'];
		
		//product_info: {"product_id":"15401","model":"AW0023-05","sku":"","upc":"","ean":"4627195953377","jan":"","isbn":"","mpn":"","location":"","quantity":"22","stock_status_id":"7","image":"catalog/Kakoo/AW0023-05.jpg","manufacturer_id":"190","shipping":"1","shippingtime":"1","price":"3370.0000","points":"3370","tax_class_id":"9","date_available":"2021-10-26","weight":"0.30000000","weight_class_id":"1","length":"26.00000000","width":"3.00000000","height":"30.00000000","length_class_id":"1","subtract":"1","minimum":"1","sort_order":"0","status":"1","featured":"1","viewed":"659","date_added":"2021-10-26 00:00:00","date_modified":"2021-10-26 00:00:00","needyml":"1","goods":"1","product_stickers_custom":"","language_id":"1","name":"Детский рюкзак KAKOO Машинки - Мими","header":"","short_description":"","description":"Рюкзак плюш&lt;br /&gt;&lt;br /&gt; Размеры: 30*26*12 см Материал: плюш&lt;br /&gt; Рюкзачок стилизирована под изображение животных. Безусловно понравится юным героям!&lt;br /&gt;&lt;br /&gt; ","tag":"Детский рюкзак KAKOO Машинки - Мими,рюкзаки плюшевые, дошкольный рюкзак, мягкий рюкзак, рюкзак-игрушка","meta_title":"AW0023-05 Детский рюкзак KAKOO Машинки - Мими","meta_description":"AW0023-05 Детский рюкзак KAKOO Машинки - Мими","meta_keyword":"Детский рюкзак KAKOO Машинки - Мими,рюкзаки плюшевые, дошкольный рюкзак, мягкий рюкзак, рюкзак-игрушка","manufacturer":"KAKOO","category":"Рюкзаки AnimalWorld","images":["catalog/Kakoo/AW0023-05_3.jpg","catalog/Kakoo/AW0023-05_2.jpg","catalog/Kakoo/AW0023-05_1.jpg"]}
/* 		product_info: {"product_id":"15401","model":"AW0023-05","sku":"","upc":"","ean":"4627195953377","jan":"","isbn":"","mpn":"","location":"","quantity":"22","stock_status_id":"7","image":"catalog/Kakoo/AW0023-05.jpg","manufacturer_id":"190","shipping":"1","shippingtime":"1","price":"3370.0000","points":"3370","tax_class_id":"9","date_available":"2021-10-26","weight":"0.30000000","weight_class_id":"1","length":"26.00000000","width":"3.00000000","height":"30.00000000","length_class_id":"1","subtract":"1","minimum":"1","sort_order":"0","status":"1","featured":"1","viewed":"659","date_added":"2021-10-26 00:00:00","date_modified":"2021-10-26 00:00:00","needyml":"1","goods":"1","product_stickers_custom":"","language_id":"1","name":"Детский рюкзак KAKOO Машинки - Мими","header":"","short_description":"","description":"Рюкзак плюш&lt;br /&gt;&lt;br /&gt; Размеры: 30*26*12 см
Материал: плюш&lt;br /&gt;
Рюкзачок стилизирована под изображение животных. Безусловно понравится юным героям!&lt;br /&gt;
&lt;br /&gt; ","tag":"Детский рюкзак KAKOO Машинки - Мими,рюкзаки плюшевые, дошкольный рюкзак, мягкий рюкзак, рюкзак-игрушка","meta_title":"AW0023-05 Детский рюкзак KAKOO Машинки - Мими","meta_description":"AW0023-05 Детский рюкзак KAKOO Машинки - Мими","meta_keyword":"Детский рюкзак KAKOO Машинки - Мими,рюкзаки плюшевые, дошкольный рюкзак, мягкий рюкзак, рюкзак-игрушка","manufacturer":"KAKOO","category":"Рюкзаки AnimalWorld","images":["catalog/Kakoo/AW0023-05_3.jpg","catalog/Kakoo/AW0023-05_2.jpg","catalog/Kakoo/AW0023-05_1.jpg"]} */
		
		$component->setData($post_data);
		echo "<script>console.log('post_data_getUpdatesOfferInfo: " . json_encode($post_data) . "' );</script>";
		echo "<script>console.log('component_getUpdatesOfferInfo: " . json_encode($component) . "' );</script>";
		$response = $this->api->sendData($component);
		echo "<script>console.log('response_getUpdatesOfferInfo: " . json_encode($response) . "' );</script>";
		$test = $post_data['offers']['0'];
		echo "<script>console.log('response_post_data: " . json_encode($test) . "' );</script>";
		echo "<script>console.log('response_post_data: " . json_encode($test['description']) . "' );</script>";
		if(is_array($response)){
//			верные данные всегда массив, ошибки строка.
//			По вернувшимся предложениям обновляем таблицу


					echo "<script>console.log('response_getUpdatesOfferInfo: " . json_encode($response['result']['offers']['0']) . "' );</script>";
			if(isset($response['result']['offers']['0'])){
				$response_offer = $response['result']['offers']['0'];
				echo "<script>console.log('response_offer_getUpdatesOfferInfo: " . json_encode($response_offer) . "' );</script>";						
				$offer_data['shopSku'] = isset($response_offer['shopSku'])?$response_offer['shopSku']:'';
				$offer_data['description'] = isset($test['description'])?html_entity_decode($test['description']):'';
				$offer_data['barcodes'][0] = isset($test['ean'])?$test['ean']:'';
				$offer_data['yandex_sku'] = isset($response_offer['marketSku'])?$response_offer['marketSku']:'';
				$offer_data['marketSkuName'] = isset($response_offer['marketSkuName'])?$response_offer['marketSkuName']:'';
				$offer_data['marketCategoryName'] = isset($response_offer['marketCategoryName'])?$response_offer['marketCategoryName']:'';			
			}
		}
		
		$offer_data['manufacturerCountries'] = [$offer_data['manufacturerCountries']];
		$marketSku = $offer_data['yandex_sku'];
				
		if($offer_data['image']){
			$offer_data['urls'][] = HTTPS_CATALOG.'image/'.$offer_data['image'];
			$offer_data['pictures'][] = HTTPS_CATALOG.'image/'.$offer_data['image'];
		}
		
		if($offer_data['images']){
			foreach($offer_data['images'] as $image){
				$offer_data['pictures'][] = HTTPS_CATALOG.'image/'.$image;
			}
		}
	
		$offerMappingEntrie = [
			'offer' => $offer_data,
			'mapping' => [
				'marketSku' => $marketSku
			]	 
		];
		
		return $offerMappingEntrie;
	}
//	Упаковки для заказа
	public function getOrderBoxes($order_id){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "yb_order_boxes WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}
	
	public function setOrderBoxes($order_id, $boxes){
		$this->db->query("DELETE FROM " . DB_PREFIX . "yb_order_boxes WHERE order_id = '" . (int)$order_id . "'");
		foreach($boxes as $box){
			$this->db->query("INSERT INTO " . DB_PREFIX . "yb_order_boxes SET order_id = '" . (int)$order_id . "', depth = '" . (int)(isset($box['depth'])?$box['depth']:0) . "', width = '" . (int)(isset($box['width'])?$box['width']:0) . "', height = '" . (int)(isset($box['height'])?$box['height']:0) . "', weight = '" . (int)(isset($box['weight'])?$box['weight']:0) . "', market_box_id = '".(int)$box['id']."', fulfilmentId = '".$this->db->escape($box['fulfilmentId'])."'");
		}
		return true;
	}
	
	public function gerOrderShipmentId($order_id){
		$query = $this->db->query("SELECT `shipment_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");
		
		if($query->num_rows){
			return $query->row['shipment_id'];
		}else{
			return false;
		} 
	}
	
	public function gerMarketOrderId($order_id){
		$query = $this->db->query("SELECT `market_order_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");
		
		if($query->num_rows){
			return $query->row['market_order_id'];
		}else{
			return false;
		} 
	}
	
	public function getOrderIdByMarketId($market_order_id){
		$query = $this->db->query("SELECT `order_id` FROM `" . DB_PREFIX . "order` WHERE `market_order_id` = '" . (int)$market_order_id . "'");
		
		if($query->num_rows){
			return $query->row['order_id'];
		}else{
			return false;
		} 
	}
	
	public function getMarketOrderType($order_id){
		$query = $this->db->query("SELECT `shipment_scheme` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");
		
		if($query->num_rows){
			return $query->row['shipment_scheme'];
		}else{
			return false;
		} 
	}
	
	public function getOrderShipmentDate($order_id){
		$query = $this->db->query("SELECT `shipment_date` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");
		
		if($query->num_rows){
			return $query->row['shipment_date'];
		}else{
			return false;
		} 
	}

	public function getOrderShipmentTime($order_id){
		$query = $this->db->query("SELECT `shipment_time` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");
		
		if($query->num_rows){
			return $query->row['shipment_time'];
		}else{
			return false;
		} 
	}

	public function delete($module_id){

		$this->db->query("DELETE FROM " . DB_PREFIX . "module WHERE module_id = '" . (int)$module_id . "'");

	}

	public function getLastYandex(){

		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "module` WHERE `code` = 'yandex_market' ORDER BY module_id DESC LIMIT 1");

		if ($query->row) {
			return json_decode($query->row['setting'], true);
		} else {
			return array();
		}

		return $query->row;

	}

	public function addShipping($data){

		$json = json_encode($data);

		$this->db->query("INSERT INTO " . DB_PREFIX . "yb_shipping_dbs SET setting = '" . $this->db->escape($json) . "'");

	}

	public function editShipping($data, $shipping_id){

		$json = json_encode($data);

		$this->db->query("UPDATE " . DB_PREFIX . "yb_shipping_dbs SET `setting` = '" . $this->db->escape($json) . "' WHERE `shipping_id` = '" . $shipping_id . "'");

	}

	public function getShippings($shipping_id){
		

		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "yb_shipping_dbs WHERE `shipping_id` = '" . $shipping_id . "'");

		return $query->row;

	}

	public function getProducts($filter, $shipping_products){

		$sql = "SELECT DISTINCT p.product_id FROM  " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";
		$sql .= " LEFT JOIN  " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)"; 
		$sql .= " WHERE (p.status='1'"; 

		//Категории
		if(empty($filter['category'])){
			$sql.= 'AND p2c.category_id IS NOT NULL';
		} else {
			$sql .= " AND ("; 
			foreach ($filter['category'] as $key => $category) {
				if($key == "0"){
					$sql .= "p2c.category_id = '" . $category . "'"; 
				} else {
					$sql .= " or p2c.category_id = '" . $category . "'"; 
				}
			}
			$sql .= ") "; 
		}

		//цена от
		if(!empty($filter['price_from'])){
			$sql .= " AND p.price >= '" . $filter['price_from'] . "'"; 
		}
		//цена от

		//цена до
		if(!empty($filter['price_to'])){
			$sql .= " AND p.price <= '" . $filter['price_to'] . "'"; 
		}
		//цена до

		//кол-во от
		if(!empty($filter['quantity_from'])){
			$sql .= " AND p.quantity >= '" . $filter['quantity_from'] . "'"; 
		}
		//кол-во от

		//кол-во до
		if(!empty($filter['quantity_to'])){
			$sql .= " AND p.quantity <= '" . $filter['quantity_to'] . "'"; 
		}
		//кол-во до

		//model
		if(!empty($filter['model'])){
			$sql .= " AND p.model = '" . $filter['model'] . "'"; 
		}
		//model

		$sql .= ")";

		if(!empty($shipping_products)){
			$sql .= " OR (";
			$product_string = "";
			foreach ($shipping_products as $key => $shipping_product) {
				if($key == '0'){
					$product_string .= "p.product_id='" . $shipping_product['product_id'] . "'";
				} else {
					$product_string .= " or p.product_id='" . $shipping_product['product_id'] . "'";
				}
			}
			$sql .= $product_string . ")";
		}

		if (isset($filter['start'])) {
			if ($filter['start'] < 0) {
				$filter['start'] = 0;
			}

		}

		$sql .= "LIMIT " . (int)$filter['start'] . "," . (int)$this->config->get('config_limit_admin');

		$query = $this->db->query($sql);
		$productsArray = array();
		foreach ($query->rows as $key => $products) {
			$productsArray[$key] =  $products['product_id'];
		}

		$productsArray = array_unique($productsArray);

		return $productsArray;

	}

	public function getTotalProducts($filter, $shipping_products){


		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM  " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";
		$sql .= " LEFT JOIN  " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)"; 
		$sql .= " WHERE (p.status='1'"; 

		//Категории
		if(empty($filter['category'])){
			$sql.= 'AND p2c.category_id IS NOT NULL';
		} else {
			$sql .= " AND ("; 
			foreach ($filter['category'] as $key => $category) {
				if($key == "0"){
					$sql .= "p2c.category_id = '" . $category . "'"; 
				} else {
					$sql .= " or p2c.category_id = '" . $category . "'"; 
				}
			}
			$sql .= ") "; 
		}

		//цена от
		if(!empty($filter['price_from'])){
			$sql .= " AND p.price >= '" . $filter['price_from'] . "'"; 
		}
		//цена от

		//цена до
		if(!empty($filter['price_to'])){
			$sql .= " AND p.price <= '" . $filter['price_to'] . "'"; 
		}
		//цена до

		//кол-во от
		if(!empty($filter['quantity_from'])){
			$sql .= " AND p.quantity >= '" . $filter['quantity_from'] . "'"; 
		}
		//кол-во от

		//кол-во до
		if(!empty($filter['quantity_to'])){
			$sql .= " AND p.quantity <= '" . $filter['quantity_to'] . "'"; 
		}
		//кол-во до

		//model
		if(!empty($filter['model'])){
			$sql .= " AND p.model = '" . $filter['model'] . "'"; 
		}
		//model


		$sql .= ")";

		if(!empty($shipping_products)){
			$sql .= " OR (";
			$product_string = "";
			foreach ($shipping_products as $key => $shipping_product) {
				if($key == '0'){
					$product_string .= "p.product_id='" . $shipping_product['product_id'] . "'";
				} else {
					$product_string .= " or p.product_id='" . $shipping_product['product_id'] . "'";
				}
			}
			$sql .= $product_string . ")";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];

	}

	public function addRegion($region_info){

		if(!empty($region_info['parent'])){

			$chek_region =$this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "yb_regions WHERE `region_id` = '" . (int)$region_info['id'] . "'");

			if(empty($chek_region->rows)){

				$sql = "INSERT INTO " . DB_PREFIX . "yb_regions SET region_id = '" . (int)$region_info['id'] . "', name = '" . $this->db->escape($region_info['name']) . "', type = '" . $this->db->escape($region_info['type']) . "', parent = '" . (int)$region_info['parent']['id'] . "'";

				$query = $this->db->query($sql);

			}

			$this->addRegion($region_info['parent']);

		} else {

			$chek_region =$this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "yb_regions WHERE `region_id` = '" . (int)$region_info['id'] . "'");
			
			if(empty($chek_region->rows)){
				$sql = "INSERT INTO " . DB_PREFIX . "yb_regions SET region_id = '" . (int)$region_info['id'] . "', name = '" . $this->db->escape($region_info['name']) . "', type = '" . $this->db->escape($region_info['type']) . "'";

				$query = $this->db->query($sql);

			}

		}


	}

	public function getShippingZone(){

		$sql = "SELECT DISTINCT * FROM " . DB_PREFIX . "yb_regions WHERE `type` = 'REPUBLIC' or `name` = 'Москва'";

		$republics = $this->db->query($sql)->rows;
		
		$republic_info = [];
		
		foreach ($republics as $key => $republic) {

			$republic_info[$key]['name'] = $republic['name'];
			$republic_info[$key]['id'] = $republic['region_id'];
		
			$name_parent = $this->getParentRegion($republic['parent']);

			$republic_info[$key]['name'] .= $name_parent;
		
		}

		return $republic_info;

	}

	public function getParentRegion($parent_id, $name =''){

		$parent_region =  $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "yb_regions WHERE `region_id` = '" . $parent_id ."'")->rows;

		if(!empty($parent_region)){

			$name .= ", " . $parent_region['0']['name'];

			$test = $this->getParentRegion($parent_region['0']['parent'], $name);

			return $test;

		} else {

			return $name;

		}

	}

	public function addDeliveryService($deliveryService){

		$this->db->query("TRUNCATE " . DB_PREFIX . "yb_deliveryService");

		foreach ($deliveryService as $service) {

			$sql = "INSERT INTO " . DB_PREFIX . "yb_deliveryService SET service_id = '" . (int)$service['id'] . "', name = '" . $this->db->escape($service['name']) . "'";

			$query = $this->db->query($sql);

		}

	}

	public function getTrackNumber($order_id){

		$track_number =  $this->db->query("SELECT DISTINCT track_number FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . $order_id ."'")->row;

		return $track_number['track_number'];

	}

	public function getDeliveryService($order_id){

		$deliveryService_id =  $this->db->query("SELECT DISTINCT service_id FROM `" . DB_PREFIX . "order` WHERE order_id = '" . $order_id . "'")->row;

		$deliveryService_name = $this->getDeliveryServiceInfo($deliveryService_id['service_id']);

		$deliveryService = array(
			'service_id'	=> $deliveryService_id['service_id'],
			'name'			=> $deliveryService_name

		);

		return $deliveryService;

	}

	public function getDeliveryServices($name){

		$deliveryServices =  $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "yb_deliveryService WHERE name LIKE  '%" . $name ."%'")->rows;

		return $deliveryServices;

	}

	public function getDeliveryServiceInfo($service_id){

		$deliveryService =  $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "yb_deliveryService WHERE service_id ='" . $service_id ."'")->row;

		return (!empty($deliveryService['name']) ? $deliveryService['name'] : '');
		
	}

	public function addTrakAndService($data, $order_id){

		$sql = "UPDATE `" . DB_PREFIX . "order` SET track_number = '" . $this->db->escape($data['trackCode']) . "', service_id = '" . (int)$data['deliveryServiceId'] . "' WHERE order_id = '" . (int)$order_id . "'";
		
		return $this->db->query($sql);

	}
	
	public function getCancellationOrder($order_id){
		$sql = "SELECT coa.*, NOW() as now_date, o.order_id, o.market_order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "yb_cancel_orders_accept` coa LEFT JOIN `" . DB_PREFIX . "order` o ON (coa.order_id = o.order_id) WHERE o.order_id = '" . (int)$order_id . "'";

		$query = $this->db->query($sql);

		return $query->row;
	}

	public function getCancellationOrders(){
		$cancellation_orders = [];
//		Формируем список заказов на отмену через api для сопоставления с хранящимися в базе
		
		if(!empty($this->config->get('yandex_beru_status_DBS'))){
			$this->api = new yandex_beru();			
			$this->api->setAuth($this->config->get('yandex_beru_oauth_DBS'), $this->config->get('yandex_beru_auth_token_DBS'),$this->config->get('yandex_beru_company_id_DBS'));
			
			$component = $this->api->loadComponent('orders');
			
			$get_data = [
				'onlyWaitingForCancellationApprove' => true,
			];
			$component->setData($get_data);
			$response = $this->api->sendData($component);

			if(!empty($response['orders'])){
				foreach($response['orders'] as $order){
					$cancellation_orders[] = $order['id'];
				}
			}
		}
		
		if(!empty($cancellation_orders)){
			$this->db->query("DELETE FROM `" . DB_PREFIX . "yb_cancel_orders_accept` WHERE market_order_id NOT IN (" . implode(",", $cancellation_orders) . ");");
		}else{
			$this->db->query("DELETE FROM `" . DB_PREFIX . "yb_cancel_orders_accept` WHERE 1;");
		}
		
		$sql = "SELECT coa.*, NOW() as now_date, o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "yb_cancel_orders_accept` coa LEFT JOIN `" . DB_PREFIX . "order` o ON (coa.order_id = o.order_id) ";

		$sql .= " ORDER BY o.order_id";
		
		$sql .= " DESC";
		
		$query = $this->db->query($sql);

		return $query->rows;
	}
	
	public function getCancellationOrdersTotal(){
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "yb_cancel_orders_accept` WHERE 1");
		
		return $query->row['total'];
	}

	public function addOrderHistory($order_id, $info) {

		$this->load->model('sale/order');

		$order_info = $this->model_sale_order->getOrder($order_id);
		
		$comment = "Дата доставки изменена на " . $info['new_date'] . "\r\n";
		$comment .= "Причина: " . $info['reason'] . "\r\n";

		if ($order_info) {
 			$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_info['order_status_id'] . "', notify = '0', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
		}

	}
	
	public function getVersionModule(){

		$version = $this->db->query("SELECT DISTINCT version FROM " . DB_PREFIX . "modification WHERE code = 'Yandex_beru'");
        
        if(isset($version->row['version'])){
            return $version->row['version'];
        }
	}
	
    public function setVersionModule($version){

		$this->db->query("UPDATE `" . DB_PREFIX . "modification` SET `version` = '" . $version . "'WHERE code = 'Yandex_beru'");	
		
	}
	
	public function getOrderByMarketId($market_order_id){
		$order_query = $this->db->query("SELECT *, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.market_order_id = '" . (int)$market_order_id . "'");
		
		return $order_query->row;
	}
	//	Получение статуcа заказа Маркета согласно таблице сопоставления в настройках модуля
	public function getOrderMarketStatusByOrderId($order_id){
		$shop_status_query = $this->db->query("SELECT market_status, market_substatus FROM `" . DB_PREFIX . "order`  WHERE order_id='" . (int)$order_id . "'");
		
		if ($shop_status_query->num_rows) {
			$market_status = [
				'status'    => $shop_status_query->row['market_status'],
				'substatus' => $shop_status_query->row['market_substatus'],
			];
		} else {
			$market_status =  false;
		}
		
		return $market_status;
	
	}
	
	//	Получение electronicAcceptanceCertificate
	public function getElectronicAcceptanceCertificate($order_id) {
		$query = $this->db->query("SELECT electronic_acceptance_certificate_code FROM `" . DB_PREFIX . "order` WHERE order_id='" . (int)$order_id . "' LIMIT 1");
		
		if ($query->num_rows) {
            return $query->row['electronic_acceptance_certificate_code'];
		} else {
			return false;
		}
	}

	//  Получение номера машины
	public function getVehicleNumber($order_id) {
		$query = $this->db->query("SELECT `vehicle_number` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($query->num_rows) {
			return $query->row['vehicle_number'];
		} else {
			return false;
		}
	}
	
	public function getDeliveryCourier($order_id) {
		$query = $this->db->query("SELECT `delivery_courier` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($query->num_rows) {
			return $query->row['delivery_courier'];
		} else {
			return false;
		}
	}
	
	public function getKeyByShopSku($shopSku){
		$query = $this->db->query("SELECT `key` FROM " . DB_PREFIX . "yb_offers WHERE `shopSku` = '" . $this->db->escape($shopSku) . "'");
		
		if($query->num_rows){
			return $query->row['key'];
		}else{
			return false;
		}
	}
	
	public function getProductData($product_id){
		$query = $this->db->query("
			SELECT 
				p.*, 
				pd.*
			FROM " . DB_PREFIX . "product p
			LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
			WHERE p.product_id = '" . (int)$product_id . "'
			AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}
	
	public function getProductOptionValue($product_id, $option_id, $option_value_id) {
		$query = $this->db->query("
			SELECT 
				pov.*, 
				ovd.*,
				od.name as option_name,
				o.type
			FROM " . DB_PREFIX . "product_option_value pov 
			LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) 
			LEFT JOIN " . DB_PREFIX . "option o ON (pov.option_id = o.option_id)
			LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id)
			WHERE pov.option_value_id = '" . (int)$option_value_id . "'
			AND pov.option_id = '" . (int)$option_id . "' 
			AND pov.product_id = '" . (int)$product_id . "' 
			AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'
			AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}
	
	public function refreshOrderProducts($order_id, $products){
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		foreach ($products as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

				$order_product_id = $this->db->getLastId();

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}
		}
		
	}

	public function refreshOrderShipmentDate($order_id, $shipment_date){
		$new_date = DateTime::createFromFormat('d-m-Y', $shipment_date)->format('Y-m-d');

		$this->db->query("UPDATE " . DB_PREFIX . "order SET `shipment_date` = '" . $new_date . "' WHERE `order_id` = '" . (int)$order_id . "'");	
	}

	public function refreshOrderShipmentTime($order_id, $shipment_time){
		$this->db->query("UPDATE " . DB_PREFIX . "order SET `shipment_time` = '" . $this->db->escape($shipment_time) . "' WHERE `order_id` = '" . (int)$order_id . "'");	
	}
	
	public function refreshOrderTotals($order_id, $totals){
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

		if (isset($totals)) {
			foreach ($totals as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
				
				if($total['code'] == 'total' && isset($total['total_buyer_price']) && isset($total['total_subsidy'])){
					$this->db->query("UPDATE `" . DB_PREFIX . "order` SET `total` = '" . (float)$total['value'] . "', `buyer-price` = '" . (float)$total['total_buyer_price'] . "', subsidy = '" . (float)$total['total_subsidy'] . "' WHERE order_id = '" . (int)$order_id . "'");
				}
			}
		}
	}
	
	public function addHolidays($holidays_str, $ignore_officials = false){
		$holidays = explode(',',$holidays_str);
	
		$this->db->query("DELETE FROM " . DB_PREFIX . "yb_holidays WHERE `official` = '0'");
		
		foreach($holidays as $holiday_str){
			$holiday = explode('-', $holiday_str);
			
//			o - day 1 - month
			if($holiday && isset($holiday[0]) && isset($holiday[1])){
				$is_offical = $this->db->query("SELECT * FROM " . DB_PREFIX . "yb_holidays WHERE `month` = '". (int)($holiday[1]) . "' AND `day` = '" . (int)($holiday[0]) . "' AND `official` = '1'")->num_rows;
			
				if(!$ignore_officials || $is_offical == false){
					$this->addHoliday(['month'=>$holiday[1],'day'=>$holiday[0],'official'=>0]);
				}
			}
		}
	}
	
	public function addHoliday($data){
		return $this->db->query("INSERT INTO " . DB_PREFIX . "yb_holidays SET `month` = '". (int)$data['month'] . "', `day` = '" . (int)$data['day'] . "', official = '". (int)$data['official'] ."'");
	}
	
	public function deleteOfficalHolidays(){
		return $this->db->query("DELETE FROM " . DB_PREFIX . "yb_holidays WHERE official = 1");
	}
	
	public function getHolidaysForInput($all = false){
		$holidays = $this->getHolidays($all);
		
		$result = [];
		
		foreach($holidays as $holiday){
			$res_str = '\'' . $holiday['day'] . '-' . $holiday['month'] . '\'';
			
			if(!in_array($res_str, $result)){
				$result[] = $res_str;
			}
		}
		
		return implode(',', $result);
	}
	
	public function getHolidays($all = false){
		if($all){
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "yb_holidays WHERE 1");
		}else{
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "yb_holidays WHERE `official` = '0'");
		}
		
		return $query->rows;
	}
	
	public function getOffersById($product_id){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "yb_offers WHERE `key` LIKE '" . (int)$product_id . "%'");

		return $query->rows;

	}

	public function addDateUpdate($key){
		$this->db->query("INSERT INTO " . DB_PREFIX . "yb_stock_offers SET 	`key` = '" . $key . "', last_update = NOW()");
	}


	public function updateDateUpdate($key){
		$this->db->query("UPDATE " . DB_PREFIX . "yb_stock_offers SET `key` = '" . $key . "', last_update = NOW() WHERE `key` = '" . $this->db->escape($key) . "'");
	}
	
	public function addOutlets($outlets_data){
		$sql = "
			INSERT INTO " . DB_PREFIX . "yb_outlets
			SET 
                `id` = '" . $this->db->escape($outlets_data['id']) . "',
				`shopOutletCode` = '" . $this->db->escape($outlets_data['shopOutletCode']) . "',
				`name` = '" . $this->db->escape($outlets_data['name']) . "',
				`type` = '" . $this->db->escape($outlets_data['type']) . "',
				`visibility` = '" . $this->db->escape($outlets_data['visibility']) . "',
				`isMain` = '" . $this->db->escape($outlets_data['isMain']) . "', 
				`coords` = '" . $this->db->escape($outlets_data['coords']) . "',
				`address` = '" . $this->db->escape(json_encode($outlets_data['address'])) . "',
				`phones` = '" . $this->db->escape(json_encode($outlets_data['phones'])) . "',
				`workingSchedule` = '" . $this->db->escape(json_encode($outlets_data['workingSchedule'])) . "',
				`deliveryRules` = '" . $this->db->escape(isset($outlets_data['deliveryRules'])?json_encode($outlets_data['deliveryRules']):'') . "',
				`storagePeriod` = '" . $this->db->escape($outlets_data['storagePeriod']) . "',
				`status` = '" . $this->db->escape($outlets_data['status']) . "',
				`region` = '" . $this->db->escape(json_encode($outlets_data['region'])) . "',
				`workingTime` = '" . $this->db->escape($outlets_data['workingTime']) . "'";

		$this->db->query($sql);
	}
	
	public function getOutlets(){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "yb_outlets");

		return $query->rows;
	}
	
	public function deleteOutlets($outlet_id = false){
        if($outlet_id){
            return $this->db->query("DELETE FROM " . DB_PREFIX . "yb_outlets WHERE id =" . $outlet_id);
        }else{
            return $this->db->query("TRUNCATE TABLE " . DB_PREFIX . "yb_outlets");
		}
	}
	
	public function getOutletIdByOrderId($order_id){
		$query = $this->db->query("SELECT ym_outlet_id FROM " . DB_PREFIX . "order WHERE order_id=".$order_id);

		return $query->row;
	}
	
	public function getShippingsDBS(){

		$query = $this->db->query("SELECT * FROM `oc_setting` WHERE `code` LIKE 'yandex_beru_DBS'");

		return $query->row;

	}
	
	public function getShopOrderId($market_order_id){
		$query = $this->db->query("SELECT `order_id` FROM `" . DB_PREFIX . "order` WHERE `market_order_id` = '" . (int)$market_order_id . "'");
		
		if($query->num_rows){
			return $query->row['order_id'];
		}else{
			return false;
		}
	}
	
	public function setOrderOutletId($order_id, $ym_outlet_id){

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET `ym_outlet_id` = '" . $ym_outlet_id . "'WHERE order_id =" . $order_id);	
		
	}
	
	public function editOrder($order_id, $data) {
		// Void the order first
		//$this->addOrderHistory($order_id, 0);

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(json_encode($data['payment_custom_field'])) . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(json_encode($data['shipping_custom_field'])) . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");

		// Products
		if (isset($data['products'])) {
			foreach ($data['products'] as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

				$order_product_id = $this->db->getLastId();

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}
			}
		}

		// Totals
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

		if (isset($data['totals'])) {
			foreach ($data['totals'] as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}
	}
}
