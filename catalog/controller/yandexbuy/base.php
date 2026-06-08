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

class ControllerYandexbuyBase extends Controller {
	protected $logfile;
	protected $paymentMethods;
	protected $OUTLET_MAPPING;
	protected $CONFIG;
	
	protected $PAYMENT_TYPES;
	protected $PAYMENT_METHODS;

	protected function setConfig() {
		$this->CONFIG = array(
			//Цифры - статусы заказа в OpenCart
			//Если они у вас особенные - проставьте свои ID статусов заказа
			'STATUS_MAPPING' => array(
				'UNPAID' => $this->config->get('yabuy_unpaid_status'),			//Ожидание оплаты
				'PROCESSING' => $this->config->get('yabuy_processing_status'),	//Ожидание (По умолчанию)
				'DELIVERY' => $this->config->get('yabuy_delivery_status'),		//В обработке
				'PICKUP' => $this->config->get('yabuy_pickup_status'),			//Доставлено до ПВЗ
				'DELIVERED' => $this->config->get('yabuy_delivered_status'),	//Вручено курьером
				'CANCELLED' => $this->config->get('yabuy_cancelled_status'),	//Отменено
			)
		);
	}

	public function __construct($registry) {
		parent::__construct($registry);
	
		if (!$this->config->get('module_yabuy_status')) {
			echo '<h1>Yandex CPA integration is off</h1>';
			exit;
		}
		
		$this->logfile = DIR_LOGS . 'yandexbuy.log';
		
		$this->token = $this->config->get('yabuy_token');
		
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
		
		$this->setConfig();
		
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
