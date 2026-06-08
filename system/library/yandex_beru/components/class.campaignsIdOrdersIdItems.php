<?php  
//Изменение состава заказа
//https://yandex.ru/dev/market/partner-dsbs/doc/dg/reference/put-campaigns-id-orders-id-items.html
class campaignsIdOrdersIdItems extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $json;	
	private $order_id;
	public $type = 'PUT';
	
	public function getMethod(){
		return $this->method.$this->order_id.'/items.json';
	}
	
	public function setOrder($order_id) {
		$this->order_id = $order_id;
	}
	
	public function getOrder() {
		return $this->order_id;
	}
	
	public function setData($data) {
		$this->json = $this->createJson($data);
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