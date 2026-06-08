<?php  
class info extends yandex_beru {
	
	private static $field_to_function = [''];
	
	public function getLicenseServer() {
		return $this->licenseServer;
	}

	public function getApiServer() {
		return $this->apiServer;
	}
	
	public function getApiPartnerServer() {
		return $this->apiPartnerServer;
	}
	
	public function getAppid() {
		return $this->appid;
	}
	public function getPublicationStatuses(){
		return ['READY','IN_WORK','NEED_CONTENT','NEED_INFO','REJECTED','SUSPENDED','OTHER'];
	}
	
	public function getRequiredOfferFields(){
//		category - обязательный параметр, но выбор не нужен т.к. он системный
//		url - системный параметр

		return [
			'manufacturerCountries' => ['childs' => false],
			'manufacturer' 			=> ['childs' => false],
			'vendor' 				=> ['childs' => false],
		];
	}

	public function getMainFieldsSimplified(){//основные поля упрощенной схемы
		return [
			'name'					=> ['childs' => false, 'required' => true],
			'vendor' 				=> ['childs' => false],
			'description' 			=> ['childs' => false],
			'sales_notes' 			=> ['childs' => false],
			'country_of_origin' 	=> ['childs' => false],
		];
	}

	public function getFieldsSimplified(){//все остальные поля упрощенной схемы
		return [
			'vendorCode' 			=> ['childs' => false],
			'purchase_price' 		=> ['childs' => false],
			'supplier' 				=> ['childs' => false],
			'delivery' 				=> ['childs' => false],
			'pickup' 				=> ['childs' => false],
			'store' 				=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'adult'					=> ['childs' => false],
			'condition'				=> ['childs' => true],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'downloadable'			=> ['childs' => false],
			'available'				=> ['childs' => false],
			'age'					=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'cpa'					=> ['childs' => false],
		];
	}

	public function getMainFieldsArbitrary(){//основные поля произвольной схемы
		return [
			'model'					=> ['childs' => false, 'required' => true],
			'typePrefix'			=> ['childs' => false, 'required' => true],
			'vendor' 				=> ['childs' => false, 'required' => true],
			'description' 			=> ['childs' => false],
			'sales_notes' 			=> ['childs' => false],
			'country_of_origin' 	=> ['childs' => false],
		];

	}

	public function getFieldsArbitrary(){//все остальные поля произвольной схемы
		return [
			'vendorCode' 			=> ['childs' => false],
			'purchase_price' 		=> ['childs' => false],
			'supplier' 				=> ['childs' => false],
			'delivery' 				=> ['childs' => false],
			'pickup' 				=> ['childs' => false],
			'store' 				=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'adult'					=> ['childs' => false],
			'condition'				=> ['childs' => true],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'downloadable'			=> ['childs' => false],
			'available'				=> ['childs' => false],
			'age'					=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'cpa'					=> ['childs' => false],
		];
	}

	public function getMainFieldsAlcohol(){//основные поля спец. схемы алкоголь
		return [
			'name'					=> ['childs' => false, 'required' => true],
			'vendor' 				=> ['childs' => false, 'required' => true],
			'barcode' 				=> ['childs' => false, 'required' => true, 'unique' => false],
			'param' 				=> ['childs' => false, 'required' => true, 'unique' => false],
			'description' 			=> ['childs' => false],
			'sales_notes' 			=> ['childs' => false],
			'country_of_origin' 	=> ['childs' => false],
		];
	}

	public function getFieldsAlcohol(){//все остальные поля спец. схемы алкоголь
		return [
			'pickup' 				=> ['childs' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'condition'				=> ['childs' => true],
			'bid'					=> ['childs' => false],
			'purchase_price' 		=> ['childs' => false],
			'vendorCode' 			=> ['childs' => false],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'age'					=> ['childs' => false],
		];
	}

	public function getMainFieldsAudiobooks(){//основные поля спец. схемы аудиокниги
		return [
			'name'					=> ['childs' => false, 'required' => true],
			'publisher' 			=> ['childs' => false, 'required' => true],
			'age'					=> ['childs' => false, 'required' => true],
			'author'				=> ['childs' => false],
			'year'					=> ['childs' => false],
			'language'				=> ['childs' => false],
			'country_of_origin' 	=> ['childs' => false],
		];
	}

	public function getFieldsAudiobooks(){//все остальные поля спец. схемы аудиокниги
		return [
			'ISBN'					=> ['childs' => false],
			'series'				=> ['childs' => false],
			'volume'				=> ['childs' => false],
			'part'					=> ['childs' => false],
			'table_of_contents'		=> ['childs' => false],
			'performed_by'			=> ['childs' => false],
			'performance_type'		=> ['childs' => false],
			'storage'				=> ['childs' => false],
			'format'				=> ['childs' => false],
			'recording_length'		=> ['childs' => false],
			'age'					=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'supplier' 				=> ['childs' => false],
			'store' 				=> ['childs' => false],
			'description' 			=> ['childs' => false],
			'sales_notes' 			=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'adult'					=> ['childs' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'condition'				=> ['childs' => true],
			'credit-template'		=> ['childs' => false],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'downloadable'			=> ['childs' => false],
			'available'				=> ['childs' => false],
			'cpa'					=> ['childs' => false],
		];
	}
	
	public function getMainFieldsEventTickets(){//основные поля спец. схемы Билеты на мероприятие
		return [
			'name'					=> ['childs' => false, 'required' => true],
			'place'					=> ['childs' => false, 'required' => true],
			'date'					=> ['childs' => false, 'required' => true],
			'hall'					=> ['childs' => false],
		];
	}
	
	public function getFieldsEventTickets(){//все остальные поля спец. схемы Билеты на мероприятие
		return [
			'hall_part'				=> ['childs' => false],
			'is_premiere'			=> ['childs' => false],
			'is_kids'				=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'purchase_price' 		=> ['childs' => false],
			'supplier' 				=> ['childs' => false],
			'delivery' 				=> ['childs' => false],
			'pickup' 				=> ['childs' => false],
			'store' 				=> ['childs' => false],
			'description' 			=> ['childs' => false],
			'sales_notes' 			=> ['childs' => false],
			'min-quantity' 			=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'country_of_origin'		=> ['childs' => false],
			'adult'					=> ['childs' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'condition'				=> ['childs' => true],
			'credit-template'		=> ['childs' => false],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'downloadable'			=> ['childs' => false],
			'available'				=> ['childs' => false],
			'age'					=> ['childs' => false],
			'cpa'					=> ['childs' => false],

		];
	}

	public function getMainFieldsBooks(){//основные поля спец. схемы книги
		return [
			'name'					=> ['childs' => false, 'required' => true],
			'publisher'				=> ['childs' => false, 'required' => true],
			'age'					=> ['childs' => false, 'required' => true],
			'author'				=> ['childs' => false],
			'year'					=> ['childs' => false],
			'language'				=> ['childs' => false],

		];
	}

	public function getFieldsBooks(){//все остальные поля спец. схемы книги
		return [
			'ISBN'					=> ['childs' => false],
			'series'				=> ['childs' => false],
			'volume'				=> ['childs' => false],
			'part'					=> ['childs' => false],
			'table_of_contents'		=> ['childs' => false],
			'binding'				=> ['childs' => false],
			'page_extent'			=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'purchase_price' 		=> ['childs' => false],
			'supplier' 				=> ['childs' => false],
			'delivery' 				=> ['childs' => false],
			'pickup' 				=> ['childs' => false],
			'store' 				=> ['childs' => false],
			'description' 			=> ['childs' => false],
			'sales_notes' 			=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'country_of_origin'		=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'adult'					=> ['childs' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'condition'				=> ['childs' => true],
			'credit-template'		=> ['childs' => false],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'downloadable'			=> ['childs' => false],
			'available'				=> ['childs' => false],
			'cpa'					=> ['childs' => false],
		];
	}

	public function getMainFieldsMedicine(){//основные поля спец. схемы книги
		return [
			'name'					=> ['childs' => false, 'required' => true],
			'pickup'				=> ['childs' => false, 'required' => true],
			'delivery'				=> ['childs' => false, 'required' => true],
			'description'			=> ['childs' => false],
			'sales_notes'			=> ['childs' => false],
			'country_of_origin'		=> ['childs' => false],

		];
	}

	public function getFieldsMedicine(){//все остальные поля спец. схемы книги
		return [
			'param'					=> ['childs' => false, 'unique' => false],
			'vendor'				=> ['childs' => false],
			'vendorCode'			=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'purchase_price'		=> ['childs' => false],
			'country_of_origin'		=> ['childs' => false],
			'store' 				=> ['childs' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
		];
	}

	public function getMainFieldsmusicVideo(){//основные поля спец. схемы Музыкальная и видеопродукция
		return [
			'title'					=> ['childs' => false, 'required' => true],
			'artist'				=> ['childs' => false],
			'year'					=> ['childs' => false],
			'director'				=> ['childs' => false],
			'country'				=> ['childs' => false],
		];
	}
	
	public function getFieldsmusicVideo(){//все остальные поля спец. схемы Музыкальная и видеопродукция
		return [
			'media'					=> ['childs' => false],
			'starring'				=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'supplier'				=> ['childs' => false],
			'delivery'				=> ['childs' => false],
			'pickup'				=> ['childs' => false],
			'store'					=> ['childs' => false],
			'description'			=> ['childs' => false],
			'sales_notes'			=> ['childs' => false],
			'min-quantity'			=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'country_of_origin'		=> ['childs' => false],
			'adult'					=> ['childs' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'condition'				=> ['childs' => true],
			'credit-template'		=> ['childs' => false],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'downloadable'			=> ['childs' => false],
			'available'				=> ['childs' => false],
			'age'					=> ['childs' => false],
			'cpa'					=> ['childs' => false],
		];
	}

	public function getMainFieldsmusicTours(){//основные поля спец. схемы Музыкальная и видеопродукция
		return [
			'name'					=> ['childs' => false, 'required' => true],
			'days'					=> ['childs' => false, 'required' => true],
			'included'				=> ['childs' => false, 'required' => true],
			'transport'				=> ['childs' => false, 'required' => true],
			'worldRegion'			=> ['childs' => false],
			'country'				=> ['childs' => false],
			'region'				=> ['childs' => false],
		];
	}

	public function getFieldsmusicTours(){//все остальные поля спец. схемы Музыкальная и видеопродукция
		return [
			'dataTour'				=> ['childs' => false, 'unique' => false],
			'hotel_stars'			=> ['childs' => false],
			'room'					=> ['childs' => false],
			'meal'					=> ['childs' => false],
			'price_min'				=> ['childs' => false],
			'price_max'				=> ['childs' => false],
			'options'				=> ['childs' => false],
			'bid'					=> ['childs' => false],
			'purchase_price'		=> ['childs' => false],
			'supplier'				=> ['childs' => false],
			'delivery'				=> ['childs' => false],
			'pickup'				=> ['childs' => false],
			'store'					=> ['childs' => false],
			'description'			=> ['childs' => false],
			'sales_notes'			=> ['childs' => false],
			'min-quantity'			=> ['childs' => false],
			'manufacturer_warranty'	=> ['childs' => false],
			'country_of_origin'		=> ['childs' => false],
			'adult'					=> ['childs' => false],
			'barcode'				=> ['childs' => false, 'unique' => false],
			'param'					=> ['childs' => false, 'unique' => false],
			'condition'				=> ['childs' => true],
			'credit-template'		=> ['childs' => false],
			'expiry'				=> ['childs' => false],
			'weight'				=> ['childs' => false],
			'dimensions'			=> ['childs' => false],
			'downloadable'			=> ['childs' => false],
			'available'				=> ['childs' => false],
			'age'					=> ['childs' => false],
			'cpa'					=> ['childs' => false],
		];
	}
	
	public function getAdditionalOfferFields(){
		return [
			'weightDimensions'		=> ['childs' => true],
			'vendorCode'  			=> ['childs' => false],
			'barcodes'	 			=> ['childs' => false],
			'description' 			=> ['childs' => false],
			'shelfLife' 			=> ['childs' => true],
			'lifeTime'				=> ['childs' => true],
			'guaranteePeriod'		=> ['childs' => true],
			'customsCommodityCodes'	=> ['childs' => false],
			'certificate'			=> ['childs' => false],
			'transportUnitSize'		=> ['childs' => false],
			'minShipment'			=> ['childs' => false],
			'quantumOfSupply'		=> ['childs' => false],
			'supplyScheduleDays'	=> ['childs' => false],
			'deliveryDurationDays'	=> ['childs' => false],
			'boxCount'				=> ['childs' => false],

		];
	
	}
	
	public function get_weightDimensionsFields(){
		return [
			'length',
			'width',
			'height',
			'weight',
		];
	}
	
	public function getSources(){
		return [
			'general',
			'data',
			'links',
			'attribute',
			'option'
		];
	}
	
	public function getOfferStatuses(){
		return ['CANCELLED','CANCELLED-SHOP_FAILED','DELIVERED','DELIVERY','PICKUP','PICKUP','UNPAID','PROCESSING','PROCESSING-READY_TO_SHIP','PROCESSING-SHIPPED'];
	}
	
	public function getOfferStatusesDbs(){
		//return ['CANCELLED-SHOP_FAILED','CANCELLED-RESERVATION_EXPIRED','CANCELLED-USER_NOT_PAID','CANCELLED-PROCESSING_EXPIRED','CANCELLED-USER_CHANGED_MIND', 'CANCELLED-REPLACING_ORDER', 'PROCESSING', 'RESERVED', 'UNPAID', 'DELIVERY', 'PICKUP', 'DELIVERED', 'CANCELLED'];
		return ['PROCESSING', 'RESERVED', 'UNPAID', 'DELIVERY', 'PICKUP', 'DELIVERED', 'CANCELLED'];
	}

	public function get_shelfLifeFields(){
		return [
			'timePeriod',
			'timeUnit',
			'comment',
		];
	}
	
	public function get_lifeTimeFields(){
		return [
			'timePeriod',
			'timeUnit',
			'comment'
		];
	}
	
	public function get_guaranteePeriodFields(){
		return [
			'timePeriod',
			'timeUnit',
			'comment'
		];
	}

	public function get_conditionFields(){
		return [
			'type',
		];
	}

	public function getOfferStatusesDbsCANCELLED(){
		return ['SHOP_FAILED', 'USER_CHANGED_MIND', 'USER_UNREACHABLE'];
	}

	public function getOfferStatusesDbsCANCELLEDWithPICKUP(){
		return ['PICKUP_EXPIRED','SHOP_FAILED', 'USER_CHANGED_MIND', 'USER_UNREACHABLE'];
	}


	public function getFieldRow($field_name){
		return ($this->getAdditionalOfferFields()[$field_name]);
	}

	public function getFieldRowArr_market($field_name){
		$func_name = 'get_'.$field_name.'Fields';
		if(method_exists($this, $func_name)){
			return $this->$func_name();	
		} else {
			return array();	
		}
	}
	
	public function getFieldRowArr($field_name){
		if(in_array($field_name, $this->getPermitted_fields())){
			$func_name = 'get_'.$field_name.'Fields';
			return $this->$func_name();	
		}else{
			return array();
		}
		
	}
	
	private function getPermitted_fields(){
		return array_keys(array_filter($this->getAdditionalOfferFields(), function($el) { return !empty($el['childs']); }));
	}
	
	public function getUserCencellationSubstatuses(){
		return ['USER_CHANGED_MIND','USER_NOT_PAID','USER_REFUSED_DELIVERY','USER_REFUSED_PRODUCT','USER_REFUSED_QUALITY','USER_UNREACHABLE'];
	}
	
	public function getLiftTypes(){
		return ['NOT_NEEDED','MANUAL','ELEVATOR','CARGO_ELEVATOR'];
	}
	
	public function getShipmentStatuses(){
		return ['WAITING_DEPARTURE','OUTBOUND_PLANNED','OUTBOUND_CREATED','OUTBOUND_CONFIRMED','MOVEMENT_COURIER_FOUND','MOVEMENT_HANDED_OVER','MOVEMENT_DELIVERING','MOVEMENT_DELIVERED','INBOUND_ARRIVED','INBOUND_ACCEPTANCE','INBOUND_ACCEPTED','INBOUND_SHIPPED'];
	}
}
?>
