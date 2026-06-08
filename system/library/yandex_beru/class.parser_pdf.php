<?php
class parser_pdf extends response_parser {
	private $filename; 
	
	public function __construct($filename = 'output.pdf'){
		$this->filename = $filename;
	}
	
	public function getData() {
		
		$reply = json_decode($this->data, true);
		
		if(empty($reply)){
			if(strpos($this->data, 'PDF')!==false){
				header('Cache-Control: public'); 
				header('Content-type: application/pdf');
				header('Content-Disposition: inline; filename="'.$this->filename.'"');
				header('Content-Length: '.strlen($this->data));
				echo $this->data;
				exit;
			}else{
				$reply = [
					'status'=>'ERROR',
					'errors'=>[
						0 => [
							'code' => 'UNKNOWN_ERROR',
							'message' => ''
						]
					]
				];
			}
		}
		
		return $reply;
	}	
}
?>