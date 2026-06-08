<?php  
class ShipmentsBoxes extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $data;	
	private $json;	
	private $order_id;
	private $shipment_id;
	public $type = 'PUT';
	
	public function getMethod(){
		return $this->method.$this->order_id.'/delivery/shipments/'.$this->shipment_id.'/boxes.json';
	}
	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}
	public function getOrder() {
		return $this->order_id;
	}
	public function setShipment($shipment_id) {
		$this->shipment_id = $shipment_id;
	}
	public function getShipment() {
		return $this->shipment_id;
	}
	
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

?>