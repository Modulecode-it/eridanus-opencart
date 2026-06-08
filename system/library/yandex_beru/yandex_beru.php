<?php 
class yandex_beru{
	private $oauth_token = "8F000001408200AE";
	private $auth_token = "y0_AgAAAAAXQ4U5AAYc2QAAAADb4K-9CbcVpV0mQE6nVdNFyYBvefnMN5c";
	private $campaignId = "21644830";
	
	private $appid = "40fd2df6a0004cea8079b25325d2f2e2";
	
	private $licenseServer = "https://oauth.yandex.ru/authorize";
	private $apiServer = "https://api.delivery.yandex.ru/";
	private $response_type = "token";
	
	private $apiPartnerServer = "https://api.partner.market.yandex.ru/v2/"; //campaigns/{campaignId}/offer-mapping-entries/updates.[format]
	
	public $version = "3.0.10"; // Версия модуля

	private static $ext_dir;
	public $error; // Ошибки при выполнении метода
	public $logger;
	
	public function __construct($oauth_token = '', $auth_token = '', $campaignId = '') {

		if (!empty($oauth_token) && !empty($auth_token) && !empty($campaignId)) {
			$this->setAuth($oauth_token, $auth_token, $campaignId);
		}

		$this->init();
	}
	
	public function setAuth($oauth_token, $auth_token, $campaignId) {
		$this->oauth_token = $oauth_token;
		$this->auth_token = $auth_token;
		$this->campaignId = $campaignId;
	}
	protected function setLogger($filename){
	    $this->logger = new beru_logger($filename);
    }
	
	private function init() {
		//Подгрузка дополнительных файлов
		spl_autoload_register(array($this, 'autoloader'));
		spl_autoload_extensions('.php');
		self::$ext_dir = dirname(__FILE__);
        $this->setLogger('yandex_beru.log');
	}
	
	public function loadComponent($component) {
		if (!class_exists($component)) return null;
		return new $component($this->oauth_token, $this->auth_token, $this->campaignId);
	}
	
	public function sendData(exchange $component) {

		$action = method_exists($component, 'action') ? $component->action($component) : $this->action($component);
		$parser = method_exists($component, 'getParser') ? $component->getParser() : new parser_json();
		
		//echo "<script>console.log('sendData action: " . $action . "' );</script>";
		//echo "<script>console.log('sendData action: " . json_encode($action) . "' );</script>";
		
		
		//echo "<script>console.log('sendData parser: " . $parser . "' );</script>";
		//echo "<script>console.log('sendData parser: " . json_encode($parser) . "' );</script>";
		
		$response = $this->getURL($action, $parser,$component->getData(), $component->type);
		//echo "<script>console.log('sendData: " . $response . "' );</script>";
		//echo "<script>console.log('sendData: " . json_encode($response) . "' );</script>";

		// Обнуление массива ошибок
		$this->error = array();

		return method_exists($component, 'prepareResponse') ? $component->prepareResponse($response, $this->error,$component) : $this->prepareResponse($response);
	}

	public function getMethod() {
		return $this->method;
	}

	public function action(exchange $component = NULL) {

		return $this->apiPartnerServer .'campaigns/' . $this->campaignId . $component->getMethod();
	}
	
	static public function autoloader($class_name) {
		if (class_exists($class_name)) return;

		$folders = array(DIR_SYSTEM.'library/yandex_beru/', DIR_SYSTEM.'library/yandex_beru/components/');

		foreach ($folders as $folder) {

			foreach (array('class', 'interface') as $type) {

				$file_name = $folder . $type . '.' . $class_name . '.php';

				if (file_exists($file_name)) {
					return require_once $file_name;
				}
			}

		}
	}

	protected function prepareResponse($data, &$error, exchange $component = NULL) {

		if(!empty($data['status']) && $data['status'] == 'ERROR'){

			return $error = method_exists($component, 'hasError') ? $component->hasError($data) : $this->hasError($data);

		} else {

			return $data;

		}

	}

	protected function hasError($data) {

		$message = "";

		foreach ($data['errors'] as $error) {

			$message .= $error['code'] . "<br>";
			if(!empty($error["message"])){
				$message .= $error["message"] . "<br>";
			}
		
		}

		return $message;

	}
	
	protected function getURL($url, response_parser $parser, $data = array(),$type = 'POST') {
//		$response = $this->getURL($action, $parser,$component->getData(), $component->type);
		$header = array();
		$header[] = 'Content-type: application/json';
		$header[] = 'Authorization: OAuth oauth_token="'.$this->oauth_token.'", oauth_client_id="'.$this->appid.'"';
		
		$ch = curl_init();

		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects, or false
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "Yandex-Modul-OpenCart", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
	        CURLOPT_TIMEOUT        => 120,      // timeout on response
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_HTTPHEADER	   => $header,
			CURLOPT_SSL_VERIFYPEER	=> false
	    );
		
		if ($type == 'POST') {
			$options[CURLOPT_POST] = 1;
			
			if (!empty($data)) {
				$options[CURLOPT_POSTFIELDS] = $data;
			}
		} elseif ($type == "PUT"){
			$header[] = 'Content-length: ' . strlen($data);
			
			$options[CURLOPT_CUSTOMREQUEST] = "PUT";
			$options[CURLOPT_POST] = 0;
			
			if (!empty($data)) {
				$options[CURLOPT_POSTFIELDS] = $data;
			}
		} elseif ($type == "DELETE"){
			$header[] = 'Content-length: ' . strlen($data);
			
			$options[CURLOPT_CUSTOMREQUEST] = "DELETE";
			$options[CURLOPT_POST] = 0;
			
			if (!empty($data)) {
				$options[CURLOPT_POSTFIELDS] = $data;
			}
		}else {
			$options[CURLOPT_POST] = 0;
			
			if (!empty($data)) {
				$url = $url.'?'.preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', http_build_query($data, null, '&'));
			}
		}

		$options[CURLOPT_HTTPHEADER] = $header;

		$ch = curl_init( $url );
		//echo "<script>console.log('url: " . json_encode($url) . "' );</script>";
	    curl_setopt_array( $ch, $options );
		//echo "<script>console.log('options: " . json_encode($options) . "' );</script>";
		$out = curl_exec( $ch );


	
		
//	    $err     = curl_errno( $ch );
//	    $errmsg  = curl_error( $ch );
//	    $header  = curl_getinfo( $ch );
	    
		curl_close( $ch );



		$parser->setData($out);
		//echo "<script>console.log('parser out: " . json_encode($out) . "' );</script>";


		return $parser->getData();
	}
}
