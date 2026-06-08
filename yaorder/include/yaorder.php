<?php
class YaOrder {

	public $ORDER_STATUSES = array(
		'UNPAID' => 'Ожидает оплаты',
		'PROCESSING' => 'В обработке',
		'DELIVERY' => 'Заказ передан в службу доставки',
		'PICKUP' => 'Доставлен в пункт самовывоза',
		'DELIVERED' => 'Вручен покупателю',
		'CANCELLED' => 'Отменен',
	);

	public $ORDER_SUBSTATUSES = array(
		'STARTED' => 'новый заказ',
		'READY_TO_SHIP' => 'готов к отгрузке',
		'SHIPPED' => 'отгружен курьерской службе',
		'SHOP_FAILED' => 'магазин не может выполнить заказ',
		
		'DELIVERY_SERVICE_UNDELIVERED' => 'служба доставки не смогла доставить заказ',
		'DELIVERY_SERVICE_DELIVERED' => 'доставлено службой доставки',
		'PROCESSING_EXPIRED' => 'магазин за 7 дней не обработал заказ',
		'REPLACING_ORDER' => 'покупатель изменил заказ',
		'RESERVATION_EXPIRED' => 'покупатель за 10 минут не завершил оформление зарезервированного заказа',
		'RESERVATION_FAILED' => 'магазин не подтвердил, что готов принять заказ',
		'USER_CHANGED_MIND' => 'покупатель отменил заказ',
		'USER_NOT_PAID' => 'покупатель за 2 часа не оплатил заказ',
		'USER_REFUSED_DELIVERY' => 'покупателя не устроили условия доставки',
		'USER_REFUSED_PRODUCT' => 'покупателю не подошел товар',
		'USER_REFUSED_QUALITY' => 'покупателя не устраивает качество товара', //2
		'USER_UNREACHABLE' => 'не удалось связаться с покупателем',
	);
	
	public $ORDER_STAGES = array(
		'STARTED' => array(
			'name' => 'новый заказ',
			'next' => array('READY_TO_SHIP', 'SHOP_FAILED')
		),
		'READY_TO_SHIP' => array(
			'name' => 'готов к отгрузке',
			'next' => array('SHIPPED', 'SHOP_FAILED')
		),
		'SHOP_FAILED' => array(
			'name' => 'магазин не может выполнить заказ',
			'substatuses' => true
		)
	);

	//+++ DBS FLOW +++
	public $ORDER_DBSSTATUSES = array(
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

	public $ORDER_DBSSUBSTATUSES = array(
		'STARTED' => '',
		'DELIVERY_SERVICE_UNDELIVERED' => 'служба доставки не смогла доставить заказ',
		'DELIVERY_SERVICE_DELIVERED' => 'доставлено службой доставки',
		'DELIVERY_SERVICE_RECEIVED' => 'получено службой доставки',
		'PROCESSING_EXPIRED' => 'магазин за 7 дней не обработал заказ',
		'REPLACING_ORDER' => 'покупатель изменил заказ',
		'RESERVATION_EXPIRED' => 'покупатель за 10 минут не завершил оформление зарезервированного заказа',
		'RESERVATION_FAILED' => 'магазин не подтвердил, что готов принять заказ',
		'USER_CHANGED_MIND' => 'покупатель отменил заказ',
		'PICKUP_EXPIRED' => 'покупатель не забрал заказ',
		
		'USER_UNREACHABLE' => 'не удалось связаться с покупателем',
		'USER_CHANGED_MIND' => 'покупатель отменил заказ по собственным причинам',
		'USER_REFUSED_DELIVERY' => 'покупателя не устраивают условия доставки',
		'USER_REFUSED_PRODUCT' => 'покупателю не подошел товар',
		'USER_NOT_PAID' => 'покупатель не оплатил заказ',
		'REPLACING_ORDER' => 'покупатель изменяет состав заказа', //1
		'USER_REFUSED_QUALITY' => 'покупателя не устраивает качество товара', //2
		'SHOP_FAILED' => 'магазин не может выполнить заказ',
	);
	//--- DBS FLOW ---
	
	public $PAYMENT_TYPES = array(
		'PREPAID' => 'Предоплата',
		'POSTPAID' => 'При получении'
	);

	public $PAYMENT_METHODS = array(
		'YANDEX' => 'банковской картой',
		'APPLE_PAY' => 'Apple pay',
		'GOOGLE_PAY' => 'Google pay',
		'CREDIT' => 'в кредит',
		'EXTERNAL_CERTIFICATE' => 'подарочным сертификатом',
		'CASH_ON_DELIVERY' => 'наличными при получении',
		'CARD_ON_DELIVERY' => 'банковской картой при получении',
        'TINKOFF_CREDIT' => 'в кредит от Тинькофф',
        'TINKOFF_INSTALLMENTS' => 'в рассрочку от Тинькофф',
	);
	
	public $DELIVERY_TYPES = array(
		'DELIVERY' => 'Курьерская доставка',
		'PICKUP' => 'Самовывоз',
		'POST' => 'Почта'
	);
	

	public function getStatusName($key) {
		return (isset($this->ORDER_STATUSES[$key]) ? $this->ORDER_STATUSES[$key] : 'Неизвестный статус');
	}

	public function getStatusNext($key) {
		$ret = array();
		if (!isset($this->ORDER_STAGES[$key]) || !isset($this->ORDER_STAGES[$key]['next'])) {
			return false;
		}
		foreach ($this->ORDER_STAGES[$key]['next'] as $next) {
			$ret[$next] = $this->ORDER_SUBSTATUSES[$next];
		}
		return $ret;
	}

	public function getDBSStatusName($key) {
		return (isset($this->ORDER_DBSSTATUSES[$key]) ? $this->ORDER_DBSSTATUSES[$key]['name'] : 'Неизвестный статус');
	}

	public function getDBSStatusNext($key) {
		$ret = array();
		if (!isset($this->ORDER_DBSSTATUSES[$key]) || !isset($this->ORDER_DBSSTATUSES[$key]['next'])) {
			return false;
		}
		foreach ($this->ORDER_DBSSTATUSES[$key]['next'] as $next) {
			$ret[$next] = $this->getStatusName($next);
		}
		return $ret;
	}

	public function getDBSSubstatusNext($key) {
		$ret = $this->ORDER_DBSSUBSTATUSES;
		if ($key == 'PROCESSING')
			unset($ret['USER_REFUSED_QUALITY']);
		elseif (($key == 'DELIVERY') || ($key == 'PICKUP'))
			unset($ret['REPLACING_ORDER']);
		else
			$ret = false;
		return $ret;
	}

	public function isDeliveryEditable($statuskey) {
return false;
		if (in_array($statuskey, array('PROCESSING', 'DELIVERY', 'PICKUP'))) {
			return true;
		}
		return false;
	}
}
