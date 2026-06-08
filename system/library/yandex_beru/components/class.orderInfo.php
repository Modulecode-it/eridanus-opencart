<?php  
class orderInfo extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $data;	
	public $type = 'GET';
	private $json;
	private $orderId;
	
	public function getMethod(){
		return $this->method . $this->orderId.'.json';
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function setOrderId($orderId){
        $this->orderId = $orderId;
	}
}

?>
