<?php  

class buyer extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $order_id;
	private $data;
	public $type = 'GET';

	public function setData($data) {
		$this->data = $data;
	}

	public function getData() {
		return $this->data;
	}

	public function getMethod() {
		return $this->method . $this->order_id . '/buyer.json';
	}

	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}

	public function getOrder() {
		return $this->order_id;
	}
}