<?php  
class ShipmentsReceptionTransferAct extends yandex_beru implements exchange {
	protected $method = '/shipments/reception-transfer-act.json';
	private $data;	
	public $type = 'GET';
	
	public function setData($data) {
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function prepareResponse($data, &$error, exchange $component = NULL) {		
		if(isset($data["errors"])){
			foreach($data["errors"] as $error_response){
				$data = $this->getErrorText($error_response);	
			}
		}
		return $data;
		
	}
	private function getErrorText($error){
		$errors = [
			'NOT_FOUND' => 'На сегодня актов приема-передачи нет.',];
		if(isset($errors[$error['code']])){
			return $errors[$error['code']];
		}else{
			return $error['message'];
		}
    }
	
	public function getParser(){
		return new parser_pdf('Акт приема-передачи сегодняшних заказов '.date("d.m.y") .'.pdf');
	}
}

?>