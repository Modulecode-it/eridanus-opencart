<?php  

class trackNumber extends yandex_beru implements exchange {
    private $apiPartnerServer = "https://api.partner.market.yandex.ru/v2/";
	protected $method = '/orders/';
	private $data;	
    public $type = 'POST';
    public function setData($data) {
		$this->json = $this->createJson($data);
	}

	public function getMethod(){
		return $this->method.$this->order_id.'/delivery/track.json';
	}

	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}

	public function getOrder() {
		return $this->order_id;
	}
   
	public function getData(){
		return $this->json;
    }

	private function createJson($data = array()) {
		return json_encode($data);
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
