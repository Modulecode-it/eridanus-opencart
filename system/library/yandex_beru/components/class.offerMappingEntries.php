<?php  
class offerMappingEntries extends yandex_beru implements exchange {
	protected $method = '/offer-mapping-entries.json';
	private $data;	
	public $type = 'GET';
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
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