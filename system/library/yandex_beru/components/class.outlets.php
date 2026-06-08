<?php  
class outlets extends yandex_beru implements exchange {
	protected $method = '/outlets.json';
	private $data;	
	public $type = 'GET';
	private $json;
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
}

?>
