<?php  
//https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-campaigns-confirm-shipments.html
class firstMileShipmentsConfirm extends yandex_beru implements exchange {
	protected $method = '/first-mile/shipments/';
	private $shipmentId;
	private $data;
	public $type = 'POST';
	
	public function getMethod(){
		return $this->method . $this->shipmentId . '/confirm.json';
	}
	
	public function setShipmentId($shipmentId) {
		$this->shipmentId = $shipmentId;
	}
	
	public function getShipmentId() {
		return $this->shipmentId;
	}
	
	public function setData($data) {
		$this->data = $this->createJson($data);
	}
	
	private function createJson($data = array()) {
		return json_encode($data);
	}
	public function getData(){
		return $this->data;
	}
}
