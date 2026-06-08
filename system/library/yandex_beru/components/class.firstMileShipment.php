<?php  
class firstMileShipment extends yandex_beru implements exchange {
	protected $method = '/first-mile/shipments/';
	private $shipmentId;
	private $data;
	public $type = 'GET';
	
	public function getMethod(){
		return $this->method . $this->shipmentId . '.json';
	}
	
	public function setShipmentId($shipmentId) {
		$this->shipmentId = $shipmentId;
	}
	
	public function getShipmentId() {
		return $this->shipmentId;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
}
