<?php
class ControllerYandexbuyStatus extends Controller {
	public function forward(&$route, &$data) {
		/*
		$order_id = $data[0];
		$order_status_id = $data[1];
		if ($order_status_id == 5 || $order_status_id == 103) { //Сделка завершена
			$query = $this->db->query("SELECT yaorder_id FROM `" . DB_PREFIX . "order` WHERE order_id='".intval($order_id)."'");
			$yaorder_id = (isset($query->row['yaorder_id']) ? $query->row['yaorder_id'] : 0);
			if ($yaorder_id) {
				if ($yaorder_id > 0) {
					if (!$this->config->get('yabuy_status')) {
						return;
					}
					$url = HTTPS_SERVER.'yaorder/index.php?action=status&ya_order='.$yaorder_id.'&substatus=SHIPPED'
						.'&company_id='.$this->config->get('yabuy_yacompany').'&login='.$this->config->get('yabuy_login').'&token='.$_SESSION['yo_yandex_access_token'];
				}
				else {
					if (!$this->config->get('yabuy2_status')) {
						return;
					}
					$url = HTTPS_SERVER.'yaorder/index.php?action=status&ya_order='.$yaorder_id.'&substatus=SHIPPED'
						.'&company_id='.$this->config->get('yabuy2_yacompany').'&login='.$this->config->get('yabuy2_login').'&token='.$_SESSION['yo_yandex_access_token'];
				}
				$tuCurl = curl_init();
				curl_setopt($tuCurl, CURLOPT_URL, $url);
				curl_setopt($tuCurl, CURLOPT_PORT , 443);
				curl_setopt($tuCurl, CURLOPT_HEADER, 0);
				curl_setopt($tuCurl, CURLOPT_FOLLOWLOCATION, true);				
				curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, false);		
				$result = curl_exec($tuCurl);
				
				if(curl_errno($tuCurl)) {
					$info = curl_getinfo($tuCurl);
					echo 'Curl Error: '.curl_error($tuCurl).'. Took: ' . $info['total_time'] . 'sec. URL: ' . $info['url'];
				}
				//echo  $url."\n".$result;
				curl_close($tuCurl);
			}
		}
		*/
	}
}
