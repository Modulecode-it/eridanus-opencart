<?php  
class Orderlabels extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $data;	
	private $order_id;
	public $type = 'GET';
	
	public function getMethod(){
		return $this->method.$this->order_id.'/delivery/labels.json';
	}
	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}
	public function getOrder() {
		return $this->order_id;
	}
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function getParser(){
		return new parser_pdf('Ярлыки '.$this->order_id.'.pdf');
	}

	
}

?>