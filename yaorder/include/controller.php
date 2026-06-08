<?php
class YaBuyController {

	private $oauth_id = APP_ID;
	private $oauth_pass = APP_PASSWORD;
	private $access_token = false;
	
	private $yandexApi;
	
	function __construct() {
		$token_file = dirname(__FILE__).'/../'.'t_'.APP_ID.'.token';
		if (is_file($token_file)) {
			$this->access_token = file_get_contents($token_file);
		}
		$this->yandexApi = new yandexApi($this->oauth_id, $this->access_token);
		session_start();
	}
	
	private function fault($message) {
		//header('HTTP/1.0 403 Forbidden');
		echo '<h3>'.$message.'</h3>';
		exit;
	}
	
	private function checkSession() {
		if (isset($_GET['company_id'])) {
			$_SESSION['yo_company_id'] = $_GET['company_id'];
		}
		if (isset($_GET['login'])) {
			$_SESSION['yo_login'] = $_GET['login'];
		}
		if (!isset($_SESSION['yo_company_id']) || !isset($_SESSION['yo_login'])) {
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
        if (intval($_GET['ya_order']) < 0) {
            $view = new YaBuyDBSView($ya_order, $this->oauth_id);
        }
        else {
            $view = new YaBuyView($ya_order, $this->oauth_id);
        }

		if (!$this->access_token) {
			return header('Location: '.HTTPS_SERVER.'yaorder/login.php?ya_order='.$ya_order);
		}
		$token = $this->access_token;

		if (isset($_GET['action']) && $_GET['action'] == 'status') {
			if ($_GET['substatus'] == 'SHOP_FAILED') {
				$status = 'CANCELLED';
			}
			else {
				$status = 'PROCESSING';
			}
			$order_data = $this->status($ya_order, $status, $_GET['substatus']);
		}
		elseif (isset($_GET['action']) && $_GET['action'] == 'dbsstatus') {
			$order_data = $this->status($ya_order, $_GET['status'], ($_GET['status'] == 'CANCELLED' ? $_GET['substatus'] : ''));
		}
		elseif (isset($_GET['action']) && $_GET['action'] == 'box') {
			$op_res = $this->box($ya_order, $_GET['shipmentId'], floatval($_GET['width']), floatval($_GET['height']), floatval($_GET['depth']), floatval($_GET['weight']));
			$order_data = $this->get($ya_order);
		}
		elseif (isset($_GET['action']) && $_GET['action'] == 'transfer_act') {
            return $this->transfer_act();
        }
		elseif (isset($_GET['action']) && $_GET['action'] == 'labels') {
            return $this->labels();
        }
		elseif (isset($_GET['action']) && $_GET['action'] == 'deliverydate') {
			$todate = trim(str_replace('.', '-', $_GET['todate']));
            $op_res = $this->deliverydate($ya_order, $todate, $_GET['reason']);
			$order_data = $this->get($ya_order);
        }
		elseif (isset($_GET['action']) && $_GET['action'] == 'delivery_services') {
            return $this->delivery_services();
		}
		elseif (isset($_GET['action']) && $_GET['action'] == 'delivery_track') {
            $op_res = $this->delivery_track($ya_order, $_GET['trackcode'], $_GET['deliveryservice']);
			$order_data = $this->get($ya_order);
		}
		elseif (isset($_GET['action']) && $_GET['action'] == 'buyer') {
            return $this->buyer($ya_order);
		}
		else {
			$order_data = $this->get($ya_order);
		}
		
		if (isset($order_data['error'])) {
			return $view->error($order_data);
		}
		elseif (isset($op_res) && isset($op_res['errors'])) {
			return $view->error(array('error'=>$op_res['errors'][0]));
		}
		else {
			return $view->render($order_data);
		}
	}

    public function transfer_act() {
		$this->checkSession();
		$ya_order = abs($_GET['ya_order']);
        
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/shipments/reception-transfer-act';
		$pdf_data = $this->yandexApi->apirequest($url, '', 'GET', false);
        if ($pdf_data && strpos($pdf_data, 'Resource not found') !== 0) {
            header('Content-Type: application/pdf');
        }
        echo $pdf_data;
    }

    public function labels() {
		$this->checkSession();
		$ya_order = abs($_GET['ya_order']);
        
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.$ya_order.'/delivery/labels';
		$pdf_data = $this->yandexApi->apirequest($url, '', 'GET', false);
        if ($pdf_data && strpos($pdf_data, 'Resource not found') !== 0) {
            header('Content-Type: application/pdf');
        }
        echo $pdf_data;
    }
	
	protected function get($ya_order) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.abs($ya_order).'.json';
		$data = $this->yandexApi->apirequest($url, '', 'GET', true);
		if ($data === false) {
			$this->fault($this->yandexApi->last_error);
		}
		elseif (isset($data['error']) && isset($data['error']['message'])) {
			$this->fault('Яндекс вернул ошибку: "'.$data['error']['message'].'"');
		}
		return $data;
	}
	
	protected function status($ya_order, $status, $substatus) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.abs($ya_order).'/status.json';
		$data = array('order'=>array('status'=>$status));
		if ($substatus) {
			$data['order']['substatus'] = $substatus;
		}
		return $this->yandexApi->apirequest($url, json_encode($data));
	}	

	protected function box($ya_order, $shipmentId, $width, $height, $depth, $weight) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.abs($ya_order).'/delivery/shipments/'.$shipmentId.'/boxes.json';
		$data = array('boxes'=>array(array(
			'fulfilmentId'=>$ya_order.'-1',
			'weight'=>$weight,
			'width'=>$width,
			'height'=>$height,
			'depth'=>$depth
		)));
		return $this->yandexApi->apirequest($url, json_encode($data));
	}	
	
	protected function delivery($ya_order, $delivery_data) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.abs($ya_order).'/delivery.json';
		$data = array('delivery'=>$delivery_data);
		return $this->yandexApi->apirequest($url, json_encode($data));
	}

	protected function deliverydate($ya_order, $date, $reason) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.abs($ya_order).'/delivery/date.json';
		$data = array('dates'=>array('toDate'=>$date), 'reason'=>$reason);
		return $this->yandexApi->apirequest($url, json_encode($data));
	}

	protected function delivery_services() {
		$url = 'https://api.partner.market.yandex.ru/v2/delivery/services.json';
		echo $this->yandexApi->apirequest($url, '', 'GET', false);
	}
	
	protected function delivery_track($ya_order, $trackCode, $deliveryServiceId) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.abs($ya_order).'/delivery/track.json';
		$data = array('trackCode'=>$trackCode, 'deliveryServiceId'=>intval($deliveryServiceId));
		return $this->yandexApi->apirequest($url, json_encode($data), 'POST');
	}
	
	protected function buyer($ya_order) {
		$url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$_SESSION['yo_company_id'].'/orders/'.abs($ya_order).'/buyer.json';
		echo $this->yandexApi->apirequest($url, '', 'GET', false);
	}
	
	public function token() {
		if (!isset($_GET['code'])) {
			return $this->fault('Яндекс вернул неверный или пустой код авторизации OAuth');
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
			$filename = realpath(dirname(__FILE__).'/../').'/t_'.APP_ID.'.token';
			file_put_contents($filename, $authdata->access_token);
			if (!is_file($filename)) {
				echo '<span style="color:#ff0000">Не удалось сохранить файл с токеном ' .$filename.'. Проверьте права доступа.</span>';
			}
			else {
				echo 'Токен сохранён, обновляйте токен до того, как кончится его время жизни.';
			}
			return;
		}
		else {
			return $this->fault('oAuth авторизация не удалась. Закройте это окно и попробуйте получить токен еще раз.');
		}
	}	
}
