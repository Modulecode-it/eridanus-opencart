<?php
ini_set("display_errors","1");
ini_set("display_startup_errors","1");
ini_set('error_reporting', E_ALL);

class ControllerYandexbuyApp extends Controller {
	private $oauth_id = 'a818c3215ab64bb6916d01066be3e552';
	private $oauth_pass = '3739343f10f74c5a8d00e25cbe356fea';
	
	function __construct() {
		session_start();
	}
	
	private function fault($message) {
		//header('HTTP/1.0 403 Forbidden');
		echo '<h3>'.$message.'</h3>';
		exit;
	}
	
	private function checkSession() {
		if (isset($_GET['company_id'])) {
			$_SESSION['yaorder_company_id'] = $_GET['company_id'];
		}
		if (isset($_GET['login'])) {
			$_SESSION['yaorder_login'] = $_GET['login'];
		}
		if (!isset($_SESSION['yaorder_company_id']) || !isset($_SESSION['yaorder_login'])) {
			return $this->fault('Empty company_id and/or Yandex login');
		}
		if (!isset($_GET['ya_order'])) {
			return $this->fault('Empty Yandex order ID');
		}
	}
	
	public function login() {
		$this->checkSession();
		$ya_order = $_GET['ya_order'];
		$url =  'https://oauth.yandex.ru/authorize?response_type=code&client_id='.$this->oauth_id.'&display=popup&state='.$ya_order;
		//header('Location: '.$url);
		echo "<script>
var yawin = window.open('$url', 'ya_oauth','width=600,height=350,resizable=yes,scrollbars=yes,status=yes,location=false');
//yawin.attachEvent('onclose' ,function(){ location.reload(); });
</script>";
		return true;
	}

	public function index() {
		$this->checkSession();
		$ya_order = $_GET['ya_order'];

		if (isset($_GET['token'])) {
			$_SESSION['yaorder_yandex_access_token'] = $_GET['token'];
		}
		if (!isset($_SESSION['yaorder_yandex_access_token'])) {
			return header('Location: http://sourcedistillery.com/yaorder/login.php?ya_order='.$ya_order);
		}
		$token = $_SESSION['yaorder_yandex_access_token'];

		$view = new YaBuyView($ya_order);
		
		if (isset($_GET['action']) && $_GET['action'] == 'status') {
			$order_data = $this->status($ya_order, $_GET['status'], ($_GET['status'] == 'CANCELLED' ? $_GET['substatus'] : ''));
		}
		elseif (isset($_GET['action']) && $_GET['action'] == 'delivery') {
			$delivery_data = array('id'=>$ya_order, 'type'=>$_GET['type']);
			if (isset($_GET['serviceName'])) {
				$delivery_data['serviceName'] = $_GET['serviceName'];
			}
			if (isset($_GET['price'])) {
				$delivery_data['price'] = floatval($_GET['price']);
			}
			if (isset($_GET['outletId'])) {
				$delivery_data['outletId'] = intval($_GET['outletId']);
			}
			$order_data = $this->delivery($ya_order, $delivery_data);
		}
		elseif (isset($_GET['action']) && $_GET['action'] == 'outlets') {
			header('Content-type: application/csv');
			header('Content-Disposition: attachment; filename="outlets.csv"');
			$this->outlets();
			return;
		}
		else {
			$order_data = $this->get($ya_order);
		}
		
		if (isset($order_data['error'])) {
			return $view->error($order_data);
		}
		else {
			return $view->render($order_data);
		}
	}
	
	protected function get($ya_order) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yaorder_company_id'].'/orders/'.$ya_order
			.'.json?oauth_token='.$_SESSION['yaorder_yandex_access_token'].'&oauth_client_id='.$this->oauth_id.'&oauth_login='.$_SESSION['yaorder_login'];
			
		$tuCurl = curl_init();
		curl_setopt($tuCurl, CURLOPT_URL, $url);
		curl_setopt($tuCurl, CURLOPT_PORT , 443);
		curl_setopt($tuCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
		curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);

		$tuData = curl_exec($tuCurl);
		if(curl_errno($tuCurl)){
			$info = curl_getinfo($tuCurl);
			$this->fault('Curl Error: '.curl_error($tuData).'. Took: ' . $info['total_time'] . 'sec. URL: ' . $info['url']);
			return false;
		} else {
			$data = json_decode($tuData, true);
			if (isset($data['error'])) {
				unset($_SESSION['yaorder_yandex_access_token']);
				//return $this->fault('Yandex returns error: "'.$data['error']['message'].'"');
			}
			return $data;
		}
	}
	
	protected function status($ya_order, $status, $substatus='') {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yaorder_company_id'].'/orders/'.$ya_order.'/status.json'
			.'?oauth_token='.$_SESSION['yaorder_yandex_access_token'].'&oauth_client_id='.$this->oauth_id.'&oauth_login='.$_SESSION['yaorder_login'];
		$data = array('order'=>array('status'=>$status));
		if ($substatus) {
			$data['order']['substatus'] = $substatus;
		}
		return $this->apirequest($url, json_encode($data));
	}	

	protected function outlets($page = 0) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yaorder_company_id'].'/outlets.json?page='.$page
			.'&oauth_token='.$_SESSION['yaorder_yandex_access_token'].'&oauth_client_id='.$this->oauth_id.'&oauth_login='.$_SESSION['yaorder_login'];
		$ols = $this->apirequest($url, '', false);
		foreach ($ols['outlets'] as $outlet) {
			if (($outlet['status'] != 'MODERATED') || ($outlet['visibility'] != 'VISIBLE')) {
				continue;
			}
			$address = ($outlet['address']['street'] ? 'ул. '.$outlet['address']['street'] : '')
				.($outlet['address']['number'] ? ', д.'.$outlet['address']['number'] : '')
				.($outlet['address']['building'] ? ' стр.'.$outlet['address']['building'] : '')
				.($outlet['address']['block'] ? ' корп.'.$outlet['address']['block'] : '')
				.($outlet['address']['estate'] ? ' влад.'.$outlet['address']['estate'] : '');
				
			$price = 0;
			if (isset($outlet['deliveryRules'])) {
				$price_parts = array();
				if (count($outlet['deliveryRules']) == 1) {
					$price = $outlet['deliveryRules'][0]['cost'];
				}
				else {
					foreach($outlet['deliveryRules'] as $rule) {
						$price_parts[] = (isset($rule['price-from']) ? $rule['price-from'] : 0).':'.$rule['cost'];
					}
					$price = implode('|', $price_parts);
				}
			}
			
			echo $outlet['id'].';;'.$outlet['address']['city'].';;'.$address.';'.$outlet['address']['additional'].';'.$price."\n";
		}
		if (($ols['pager']['to']-$ols['pager']['from']) >= 50) {
			$this->outlets($page+1);
		}
		
	}	
	
	protected function delivery($ya_order, $delivery_data) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yaorder_company_id'].'/orders/'.$ya_order.'/delivery.json'
			.'?oauth_token='.$_SESSION['yaorder_yandex_access_token'].'&oauth_client_id='.$this->oauth_id.'&oauth_login='.$_SESSION['yaorder_login'];
		$data = array('delivery'=>$delivery_data);
		return $this->apirequest($url, json_encode($data));
	}
	
	private function apirequest($url, $query = '', $put = true) {
		// Clean up string
		$putString = stripslashes($query);
		// Put string into a temporary file
		$putData = tmpfile();
		// Write the string to the temporary file
		fwrite($putData, $putString);
		// Move back to the beginning of the file
		fseek($putData, 0);
		
		$tuCurl = curl_init();
		curl_setopt($tuCurl, CURLOPT_URL, $url);
		curl_setopt($tuCurl, CURLOPT_PORT , 443);
		curl_setopt($tuCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
		curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($tuCurl, CURLOPT_BINARYTRANSFER, 1);
		if ($put) {
			curl_setopt($tuCurl, CURLOPT_PUT, 1);
			curl_setopt($tuCurl, CURLOPT_INFILE, $putData);
		}
		curl_setopt($tuCurl, CURLOPT_INFILESIZE, strlen($putString));

		$tuData = curl_exec($tuCurl);
		if(curl_errno($tuCurl)){
			$info = curl_getinfo($tuCurl);
			$this->fault('Curl Error: '.curl_error($tuCurl).'. Took: ' . $info['total_time'] . 'sec. URL: ' . $info['url']);
			return false;
		} else {
			$data = json_decode($tuData, true);
			return $data;
		}
	}
    
	public function token() {
		if (!isset($_GET['code'])) {
			return $this->fault('Яндекс вернул неверный или пуской код авторизации OAuth');
		}
		
		$tuCurl = curl_init();
		curl_setopt($tuCurl, CURLOPT_URL, 'https://oauth.yandex.ru/token');
		curl_setopt($tuCurl, CURLOPT_PORT , 443);
		curl_setopt($tuCurl, CURLOPT_HEADER, 0);
		curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($tuCurl, CURLOPT_POST, 1);
		curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, false);		
		curl_setopt($tuCurl, CURLOPT_POSTFIELDS, array(
			'grant_type'=>'authorization_code',
			'code'=>$_GET['code'],
			'client_id'=>$this->oauth_id,
			'client_secret'=>$this->oauth_pass
		));
		$tuData = curl_exec($tuCurl);
		if(curl_errno($tuCurl)){
			$info = curl_getinfo($tuCurl);
			return $this->fault('Ошибка доступа к серверу авторизации: '.curl_error($tuCurl).'. Потрачено: ' . $info['total_time'] . 'сек. URL: ' . $info['url']);
		} 
		
		//Yandex returns {"access_token": "ea135929105c4f29a0f5117d2960926f", "expires_in": 2592000}
		$authdata = json_decode($tuData);
		
		header('Content-Type: text/html; charset=utf-8');
		if (isset($authdata->access_token) && $authdata->access_token) {
			echo '<h3>Токен: <u>'.$authdata->access_token.'</u><br/>Время жизни: '.floor($authdata->expires_in/3600/24).' суток.</h3>';
			echo 'Сохраните токен в настройках модуля, обновляйте токен до того, как кончится его время жизни.';
			return;
		}
		else {
			return $this->fault('oAuth авторизация не удалась. Закройте это окно и попробуйте получить токен еще раз.');
		}
	}
    
    
}

class YaOrder {

	public $ORDER_STATUSES = array(
		/*
		'UNPAID' => array(
			'name' => 'Ожидает оплаты',
			'next' => array('PROCESSING', 'CANCELLED')
		),
		*/
		'PROCESSING' => array(
			'name' => 'В обработке',
			'next' => array('DELIVERY', /*'PICKUP',*/ 'CANCELLED')
		),
		'DELIVERY' => array(
			'name' => 'Готов к передаче в службу доставки',
			'next' => array('DELIVERED', 'PICKUP', 'CANCELLED')
		),
		'PICKUP' => array(
			'name' => 'Доставлен в пункт самовывоза',
			'next' => array('DELIVERED', 'CANCELLED')
		),
		'DELIVERED' => array(
			'name' => 'Вручен покупателю'
		),
		'CANCELLED' => array(
			'name' => 'Отменен',
			'substatuses' => true
		),
	);

	public $ORDER_SUBSTATUSES = array(
		'USER_UNREACHABLE' => 'не удалось связаться с покупателем',
		'USER_CHANGED_MIND' => 'покупатель отменил заказ по собственным причинам',
		'USER_REFUSED_DELIVERY' => 'покупателя не устраивают условия доставки',
		'USER_REFUSED_PRODUCT' => 'покупателю не подошел товар',
		'REPLACING_ORDER' => 'покупатель изменяет состав заказа', //1
		'USER_REFUSED_QUALITY' => 'покупателя не устраивает качество товара', //2
		'SHOP_FAILED' => 'магазин не может выполнить заказ',
	);

	public $PAYMENT_TYPES = array(
		'PREPAID' => 'Предоплата',
		'POSTPAID' => 'При получении'
	);

	public $PAYMENT_METHODS = array(
		'CASH_ON_DELIVERY' => 'наличный расчет',
		'CARD_ON_DELIVERY' => 'оплата банковской картой'
	);
	
	public $DELIVERY_TYPES = array(
		'DELIVERY' => 'Курьерская доставка',
		'PICKUP' => 'Самовывоз',
		'POST' => 'Почта'
	);
	

	public function getStatusName($key) {
		return (isset($this->ORDER_STATUSES[$key]) ? $this->ORDER_STATUSES[$key]['name'] : 'Неизвестный статус');
	}

	public function getStatusNext($key) {
		$ret = array();
		if (!isset($this->ORDER_STATUSES[$key]) || !isset($this->ORDER_STATUSES[$key]['next'])) {
			return false;
		}
		foreach ($this->ORDER_STATUSES[$key]['next'] as $next) {
			$ret[$next] = $this->getStatusName($next);
		}
		return $ret;
	}

	public function getSubstatusNext($key) {
		$ret = $this->ORDER_SUBSTATUSES;
		if ($key == 'PROCESSING')
			unset($ret['USER_REFUSED_QUALITY']);
		elseif (($key == 'DELIVERY') || ($key == 'PICKUP'))
			unset($ret['REPLACING_ORDER']);
		else
			$ret = false;
		return $ret;
	}
	
	public function isDeliveryEditable($statuskey) {
		if (in_array($statuskey, array('PROCESSING', 'DELIVERY', 'PICKUP'))) {
			return true;
		}
		return false;
	}
}
