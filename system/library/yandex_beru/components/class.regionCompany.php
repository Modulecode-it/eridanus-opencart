<?php  
class regionCompany extends yandex_beru implements exchange {
	protected $method = '/region.json';
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
