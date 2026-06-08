<?php  
class outletEdit extends yandex_beru implements exchange {
	protected $method = '/outlets/';
	private $data;	
	public $type = 'PUT';
	private $json;
	private $outletId;
	
	public function getMethod(){
		return $this->method . $this->outletId.'.json';
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function setOutletId($outletId){
        $this->outletId = $outletId;
	}
}
?>
