<?php  

class offersStocks extends yandex_beru implements exchange {
    private $apiPartnerServer = "https://api.partner.market.yandex.ru/v2/";
	protected $method = '/offers/stocks/';
    private $data;	
    private $json;	
    public $type = 'PUT';
    public function setData($data) {
		$this->json = $this->createJson($data);
	}

	public function getData(){

        // $log = fopen(DIR_LOGS . 'stocks', 'a');
		// fwrite($log, date('Y-m-d G:i:s') . ' - ' . print_r($this->json, true) . "\n");
		// fclose($log);

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