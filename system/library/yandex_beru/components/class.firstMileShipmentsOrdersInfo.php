<?php  
//https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/get-campaigns-shipment-id-labels.html
class firstMileShipmentsOrdersInfo extends yandex_beru implements exchange {
	protected $method = '/first-mile/shipments/';
	private $shipmentId;
	private $data;
	public $type = 'GET';
	
	public function getMethod(){
		return $this->method . $this->shipmentId . '/orders/info.json';
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
