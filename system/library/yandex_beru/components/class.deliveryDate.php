<?php  

class deliveryDate extends yandex_beru implements exchange {
    private $apiPartnerServer = "https://api.partner.market.yandex.ru/v2/";
	protected $method = '/orders/';
	private $data;	
    public $type = 'PUT';
    public function setData($data) {
		$this->json = $this->createJson($data);
	}
	
	public function getData(){
		return $this->json;
    }

    public function setOrder($order_id) {
		$this->order_id = $order_id;
    }
    
    private function createJson($data = array()) {
		return json_encode($data);
	}

	public function getMethod(){
		return $this->method.$this->order_id.'/delivery/date.json';
	}

	public function getOrder() {
		return $this->order_id;
	}

	public function prepareResponse($data, &$error, exchange $component = NULL) {
		//prepareResponse($response, $this->error,$component)
		//Сформирвоать массив при необходимости
		if (is_scalar($data)) {
			$error[]['error_response'] = 'Ошибка сервера Yandex: неверный формат ответа!';
		}
		if(isset($data["errors"])){
			foreach($data["errors"] as $error_response){
				$error[]['error_response'] = $this->getErrorText($error_response);	
			}	
		}
		return $data;
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