<?php  
class Cancellation extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $json;	
	private $order_id;
	public $type = 'PUT';
	
	public function getMethod(){
		return $this->method.$this->order_id.'/cancellation/accept';
	}
	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}
	public function getOrder() {
		return $this->order_id;
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
