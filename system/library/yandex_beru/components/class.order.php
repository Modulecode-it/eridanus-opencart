<?php  
class Order extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $data;	
	private $order_id;
	public $type = 'GET';
	
	public function getMethod(){
		return $this->method.$this->order_id.'.json';
	}
	
	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}
	
	public function getOrder() {
		return $this->order_id;
	}
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
//	public function prepareResponse($data, &$error) {
//		//Сформирвоать массив при необходимости
//		if (is_scalar($data)) {
//			$error[]['error_response'] = 'Ошибка сервера Yandex: неверный формат ответа!';
//		}
//		if(isset($data["errors"])){
//			foreach($data["errors"] as $error_response){
//				$error[]['error_response'] = $this->getErrorText($error_response);	
//			}	
//		}
//		return $data;
//	}
	
//	private function getErrorText($error){
//		$errors = [
//			'BAD_REQUEST' => '(BAD_REQUEST) Ошибка запроса. Проверьте настройки подключения.',];
//		if(isset($errors[$error['code']])){
//			return $errors[$error['code']];
//		}else{
//			return $error['message'];
//		}
//	}
}

?>