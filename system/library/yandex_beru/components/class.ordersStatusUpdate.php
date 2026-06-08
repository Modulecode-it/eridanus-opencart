<?php  
//https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-campaigns-id-orders-status-update.html/
//Изменяет статусы нескольких заказов. Максимальное количество заказов, у которых можно изменить статус в одном запросе, — 30.
class ordersStatusUpdate extends yandex_beru implements exchange {
	protected $method = '/orders/status-update.json';
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
	
	public function prepareResponse($data, &$error, exchange $component = NULL) {
		
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

?>