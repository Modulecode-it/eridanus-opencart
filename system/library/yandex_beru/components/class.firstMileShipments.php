<?php  
class firstMileShipments extends yandex_beru implements exchange {
	protected $method = '/first-mile/shipments.json';
	private $data;	
	public $type = 'PUT';
	
	public function setData($data) {
		$this->json = $this->createJson($data);
	}
	
	private function createJson($data = array()) {
		return json_encode($data);
	}
	
	public function getData(){
		return $this->json;
	}
}
