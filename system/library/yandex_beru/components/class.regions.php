<?php  
class regions extends yandex_beru implements exchange {
    private $apiPartnerServer = "https://api.partner.market.yandex.ru/v2/";
	protected $method = '/regions.json';
	private $data;	
	public $type = 'GET';
	private $json;
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function action(exchange $component = NULL) {

		return $this->apiPartnerServer . $component->getMethod();
	}
}

?>
