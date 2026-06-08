<?php  
class Orderlabel extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $data;	
	private $order_id;
	private $shipmentId;
	private $boxId;
	public $type = 'GET';
	
	public function getMethod(){
		return $this->method.$this->order_id.'/delivery/shipments/'.$this->shipmentId.'/boxes/'.$this->boxId.'/label.json';
	}
	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}
	public function getOrder() {
		return $this->order_id;
	}
	
	public function setShipmentId($shipmentId) {
		$this->shipmentId = $shipmentId;
	}
	public function getShipmentId() {
		return $this->shipmentId;
	}
	
	public function setBoxId($boxId) {
		$this->boxId = $boxId;
	}
	public function getBoxId() {
		return $this->ordboxIder_id;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function getParser(){
		return new parser_pdf('Ярлык '.$this->order_id.' '.$this->boxId.'.pdf');
	}
}

?>