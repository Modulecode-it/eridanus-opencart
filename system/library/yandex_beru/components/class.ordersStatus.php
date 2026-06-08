<?php  
//Изменение статуса по DBS схеме
//https://yandex.ru/dev/market/partner-dsbs/doc/dg/reference/put-campaigns-id-orders-id-status.html
//Изменяет статус заказа. Возможные изменения статусов:
//Когда магазин передал заказ службе доставки, нужно перевести заказ из статуса PROCESSING в статус DELIVERY.
//
//Когда служба доставки привезла заказ в пункт самовывоза, нужно перевести заказ из статуса DELIVERY в статус PICKUP.
//
//Когда покупатель получил заказ от курьера службы доставки или в пункте самовывоза, нужно перевести заказ из статуса DELIVERY или PICKUP в статус DELIVERED.
//
//Чтобы отменить заказ, нужно перевести его из статуса PROCESSING, DELIVERY или PICKUP в статус CANCELLED и указать причину отмены в параметре substatus.
class ordersStatus extends yandex_beru implements exchange {
	protected $method = '/orders/';
	private $order_id;	
	private $json;	
	public $type = 'PUT';
	
	public function getMethod(){
		return $this->method.$this->order_id.'/status.json';
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
	
	public function prepareResponse($data, &$error, exchange $component = NULL) {
		// prepareResponse($response, $this->error,$component)
		// Сформироать массив при необходимости

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
			'BAD_REQUEST' => [
				'Real delivery date should be from order creation date to current date inclusively' =>
				'Реальная дата доставки может быть в интервале от даты создания заказа, до декущей даты',
				'Order status CANCELLED must be accompanied with a substatus' =>
				'Статус заказа "Отменено" должен сопровождаться субстатусом',
			],
		];

		if(isset($errors[$error['code']][$error['message']])){
			return $errors[$error['code']][$error['message']];
		}else{
			return $error['message'];
		}
	}
}

?>