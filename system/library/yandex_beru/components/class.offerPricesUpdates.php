<?php  

class offerPricesUpdates extends yandex_beru implements exchange {
	protected $method = '/offer-prices/updates.json';
	private $json;	
    public $type = 'POST';
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