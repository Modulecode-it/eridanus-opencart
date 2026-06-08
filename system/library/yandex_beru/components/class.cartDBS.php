<?php  

class cartDBS extends yandex_beru implements exchange {
    private $apiPartnerServer = "https://api.partner.market.yandex.ru/v2/";
	protected $method = '/regions.json';
	private $data;	
    public $type = 'GET';
    public function setData($data) {
		$this->data = $data;
    }
   
	
	public function getData(){
		return $this->data;
    }

    public function action(exchange $component = NULL){
		return $this->apiPartnerServer . "/delivery/services.json";
	}
	
	private function getErrorText($error){
		$errors = [
			'BAD_REQUEST' => '(BAD_REQUEST) Ошибка запроса. Проверьте настройки подключения.',];
		if(isset($errors[$error['code']])){
			return $errors[$error['code']];
		}else{
			return $error['message'];
		}
    }
}