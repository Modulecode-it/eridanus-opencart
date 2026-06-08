<?php  
class Orders extends yandex_beru implements exchange {
	protected $method = '/orders.json';
	private $data;	
	public $type = 'GET';
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
}
