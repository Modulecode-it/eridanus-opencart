<?php
/**
* Маркетплейс "Яндекс.Маркет" - прием заказов по API для OpenCart (ocStore) 2.x
*
* @author Alexander Toporkov <toporchillo@gmail.com>
* @copyright (C) 2013- Alexander Toporkov
* @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

ini_set("display_errors","1");
ini_set("display_startup_errors","1");
ini_set('error_reporting', E_ALL);

class ControllerYandexbuy2Base extends Controller {
	protected $logfile;
	protected $paymentMethods;
	protected $OUTLET_MAPPING;
	protected $CONFIG;
	protected $WEEKENDS;
	
	protected $PAYMENT_TYPES;
	protected $PAYMENT_METHODS;
    
    protected $DBS;

	protected function setConfig() {
		//+++ Соответсвие точек продаж Яндекс-Маркета адресам, в созданном заказе +++
		//Устанавливается в системе администрирования, но может быть установлено и здесь
		$this->OUTLET_MAPPING = array();
		foreach($this->getOutlets() as $outlet) {
			$this->OUTLET_MAPPING[$outlet['id']] = array(
				'zone'=>$outlet['zone'],
				'city'=>$outlet['city'],
				'postcode'=>$outlet['postcode'],
				'address_1'=>$outlet['address_1'],
				'address_2'=>$outlet['address_2']
			);
		}
		//--- Соответсвие точек продаж Яндекс-Маркета адресам, в созданном заказе ---

		$this->CONFIG = array(
			//Справа статусы заказа в OpenCart
			'STATUS_MAPPING' => array(
				'UNPAID' => $this->config->get('yabuy2_unpaid_status'),			//Ожидание оплаты
				'PROCESSING' => $this->config->get('yabuy2_processing_status'),	//Ожидание (По умолчанию)
				'DELIVERY' => $this->config->get('yabuy2_delivery_status'),		//В обработке
				'PICKUP' => $this->config->get('yabuy2_pickup_status'),			//Доставлено до ПВЗ
				'DELIVERED' => $this->config->get('yabuy2_delivered_status'),	//Вручено курьером
				'CANCELLED' => $this->config->get('yabuy2_cancelled_status'),	//Отменено
			)
		);
	}

	protected function getDeliveries() {
		$deliveries = $this->config->get('yabuy2_deliveries');
		if (!is_array($deliveries)) {
			$deliveries = array();
		}
		return $deliveries;
	}

	protected function getPostals() {
		$postals = $this->config->get('yabuy2_postals');
		if (!is_array($postals)) {
			$postals = array();
		}
		return $postals;
	}
	
	protected function getOutlets($regions = false) {
		$outlets = $this->config->get('yabuy2_outlets');
		if (!is_array($outlets)) {
			$outlets = array();
		}
		if (is_file(DIR_APPLICATION . 'controller/yandexbuy2/outlets.csv')) {
			$fp = fopen(DIR_APPLICATION . 'controller/yandexbuy2/outlets.csv', 'r');
			if($fp){
				$head = fgets($fp);
				while ($data = fgets($fp)) {
					$data = explode(';', $data);
					$num = count($data);
					if ((int)$num >= 7) {
						if ($regions && !in_array(intval($data[1]), $regions)) {
							continue;
						}
						$outlets[] = array(
							'id' => $data[0],
							'zone' => $data[1],
							'city' => $data[2],
							'postcode' => '',
							'address_1' => $data[3],
							'address_2' => $data[4],
							'price' => (isset($data[5]) ? $data[5] : 0),
							'days' => (isset($data[6]) ? $data[6] : 0)
						);
					}
				}
			}
			fclose($fp);
		}
		return $outlets;
	}

	protected function getDeliveryPrice($price, $total=0) {
		if (strpos($price, ':') === false)
			return $price;
		$vars = explode('|', $price);
		$ret = false;
		foreach ($vars as $var) {
			$tp = explode(':', $var);
			if ($total < $tp[0]) break;
			$ret = $tp[1];
		}
		return $ret;
	}

	/**
	* Возвращает время доставки для подстановки в ответ API
	* @param string $days кол-во дней - число или диапазон через дефис
	* @param string $before час "перескока" после которого заказ будет отправляться на следующий день
	*/
	protected function getDeliveryDays($days, $before) {
		$days_arr = explode('-', $days);
		$from = intval($days_arr[0]);
		$to = isset($days_arr[1]) ? intval($days_arr[1]) : $from;
		if ($before && intval(date('H')) >= intval($before)) {
			$from++;
			$to++;
		}
        if ($from < 0) {
            $from = 0;
        }
        if ($to < 0) {
            $to = 0;
        }
        if ($to < $from) {
            $to = $from;
        }
		//++++ Учитываем выходные дни, увеличиваем на них сроки доставки ++++
		$sat = $this->WEEKENDS['sat'];
		$sun = $this->WEEKENDS['sun'];
		if ($sat || $sun) {
			for ($i=0; $i<=$from; $i++) {
				if ($sat && date('w', time() + $i*24*3600) == 6) {
					$from++;
				}
				if ($sun && date('w', time() + $i*24*3600) == 0) {
					$from++;
				}
			}
			for ($i=0; $i<=$to; $i++) {
				if ($sat && date('w', time() + $i*24*3600) == 6) {
					$to++;
				}
				if ($sun && date('w', time() + $i*24*3600) == 0) {
					$to++;
				}
			}
		}
		//---- Учитываем выходные дни, увеличиваем на них сроки доставки ----
		return array('fromDate'=> date('d-m-Y', time() + $from*24*3600) /*, 'toDate'=> date('d-m-Y', time() + $to*24*3600) */);
	}

	protected function extractRegionsFull($region) {
		$res_regions = array();
		$res_regions[$region['type']] = $region;
		if (isset($region['parent'])) {
			$res_regions = array_merge($res_regions, $this->extractRegionsFull($region['parent']));
		}
		return $res_regions;
	}
		
	public function __construct($registry) {
		parent::__construct($registry);
	
		if (!$this->config->get('module_yabuy2_status')) {
			echo '<h1>Yandex CPA integration is off</h1>';
			exit;
		}
		
		$this->logfile = DIR_LOGS . 'yandexbuy.log';
		
		$this->token = $this->config->get('yabuy2_token');

        $this->DBS = $this->config->get('yabuy2_dbs');
		
		$this->PAYMENT_TYPES = array('PREPAID'=>'предоплата', 'POSTPAID'=>'постоплата');
        //В OpenCart 2 код способа оплаты в заказе должен соответствовать имени существющего модуля оплаты
	    $this->PAYMENT_TYPES_MODULES = array('PREPAID'=>'cheque', 'POSTPAID'=>'cod');

		//Доступные способы оплаты
		$this->PAYMENT_METHODS = array(
			'YANDEX' => 'банковской картой',
			'APPLE_PAY' => 'Apple pay',
			'GOOGLE_PAY' => 'Google pay',
			'CREDIT' => 'в кредит', //Больше не используется?
			'EXTERNAL_CERTIFICATE' => 'подарочным сертификатом', //Больше не используется?
			'CASH_ON_DELIVERY' => 'наличными при получении',
			'CARD_ON_DELIVERY' => 'банковской картой при получении',
			'TINKOFF_CREDIT' => 'в кредит от Тинькофф',
			'TINKOFF_INSTALLMENTS' => 'в рассрочку от Тинькофф',
		);
		
		$this->OUTLET_MAPPING = array();
		$outlets = $this->config->get('yabuy2_outlets');
		if (is_array($outlets))
		foreach ($outlets as $outlet) {
			if (!isset($outlet['id']) || !$outlet['id']) {
				continue;
			}
			$this->OUTLET_MAPPING[$outlet['id']] = $outlet;
		}

		$this->setConfig();
		$this->WEEKENDS = array(
			'sat'=>$this->config->get('yabuy2_weekend_sat'),
			'sun'=>$this->config->get('yabuy2_weekend_sun')
		);
		
		if (!isset($this->request->get['auth-token']) || $this->request->get['auth-token'] != $this->token) {
			header('HTTP/1.0 403 Forbidden');
			echo '<h1>Wrong or empty Yandex Authorization token</h1>';
			exit;
		}
	}
	
	protected function getProductOptionData($product_id, $option_value_id) {
		$ret = array();

		if ($option_value_id > 0) {
			$query = $this->db->query("SELECT pov.*, o.type, od.name, ovd.name AS value FROM `" . DB_PREFIX . "product_option_value` pov
				LEFT JOIN `" . DB_PREFIX . "option` o ON (pov.option_id = o.option_id)
				LEFT JOIN `" . DB_PREFIX . "option_description` od ON (pov.option_id = od.option_id AND od.language_id = '" . (int)$this->config->get('config_language_id') . "')
				LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (ovd.option_value_id = pov.option_value_id AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "')
				WHERE pov.option_value_id = '". (int)$option_value_id ."' AND pov.product_id = '" . (int)$product_id . "'");
			$ret = $query->row;
		}

		return $ret;
	}

	protected function log($text) {
		$flog = fopen($this->logfile, 'a');
		fwrite($flog, date('d.m.Y H:i:s').' '.$text."\n");
		fclose($flog);
	}
}
