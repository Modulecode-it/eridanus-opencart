<?php  

class offerPricesSuggestions extends yandex_beru implements exchange {
	protected $method = '/offer-prices/suggestions.json';
	private $json;	
	public $type = 'POST';
	
	public function setData($data) {
		$this->json = $this->createJson($data);
		//var_dump($this->json);
	}
	
	private function createJson($data = array()) {
		return json_encode($data);
	}
	
	public function getData(){
		return $this->json;
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

?>