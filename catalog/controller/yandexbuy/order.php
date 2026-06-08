<?php
/**
* Маркетплейс "Яндекс.Маркет" - прием заказов по API для OpenCart (ocStore) 2.x
*
* @author Alexander Toporkov <toporchillo@gmail.com>
* @copyright (C) 2013- Alexander Toporkov
* @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
require_once(dirname(__FILE__).'/base.php');

class ControllerYandexbuyOrder extends ControllerYandexbuyBase {

	public function accept() {
		$postdata = file_get_contents("php://input");
		if (!$postdata) {
			header('HTTP/1.0 404 Not Found');
			echo '<h1>No data posted</h1>';
			exit;
		}
		
		$this->load->model('checkout/order');
		$this->load->model('catalog/product');
		$this->log('accept: '.$postdata);
		$data = json_decode($postdata, true);

		$subtotal = 0;
		$total = 0;
		$subsidy = 0;
		$totals = array();
		$products = array();
		foreach ($data['order']['items'] as $item) {
			$subtotal+= $item['count']*$item['price'];
			if (isset($item['subsidy'])) {
				$subsidy+= $item['subsidy'];
			}
			$offer_id = $item['offerId'];
			$option_value_id = 0;
			$option2_value_id = 0;
			if (!$this->config->get('yabuy_long_id')) {
				if (strlen($offer_id) > 12) {
					$offer_id = intval(substr($item['offerId'], 0, strlen($offer_id) - 12));
					$option_value_id = intval(ltrim(substr($item['offerId'], -12, 6), '0'));
					$option2_value_id = intval(ltrim(substr($item['offerId'], -6), '0'));
				}
				elseif (strlen($offer_id) > 6) {
					$offer_id = intval(substr($offer_id, 0, strlen($offer_id) - 6));
					$option_value_id = intval(ltrim(substr($item['offerId'], -6), '0'));
				}
			}
			$product_info = $this->model_catalog_product->getProduct($offer_id);
			$option = $this->getProductOptionData($offer_id, $option_value_id);
			$product_data = array(
				'product_id'=>$offer_id,
				'name'=>$item['offerName'],
				'model'=>$product_info['model'],
				'quantity'=>$item['count'],
				'price'=>$item['price'],
				'total'=>$item['count']*$item['price'],
				'tax'=>0,
				'reward'=>0,
				'option'=>(count($option) > 0 ? array($option) : array()),
				'download'=>array()
			);
			if ($option2_value_id) {
				$option2 = $this->getProductOptionData($offer_id, $option2_value_id);
				$product_data['option'][] = $option2;
			}
			$products[] = $product_data;
		}
		$total = $subtotal;
		$currency = $data['order']['currency']; //Assume RUR
		if (!$this->currency->has($currency)) {
		    $currency = 'RUB';
		}
        
        if ($subsidy>0) {
			$totals[] = array('code'=>'sub_total', 'title'=>'Скидка от Яндекс.Маркета', 'text'=>$this->currency->format($subsidy, $currency), 'value'=>$subsidy, 'sort_order'=>1);
		}
		$totals[] = array('code'=>'sub_total', 'title'=>'Сумма', 'text'=>$this->currency->format($subtotal, $currency), 'value'=>$subtotal, 'sort_order'=>1);
		
		$country_id = 176;
		$country = 'Российская федерация';
		$zone_id = 0;
		$zone = '';
		$city = '';
		$postcode = '';
		$address_1 = '';
		$address_2 = '';

		$firstname = 'Заказ FBS';
		$lastname = '№'.intval($data['order']['id']);
		$email = 'trash@yandex.ru';
		$phone = '';
		
		if (isset($data['order']['user'])) {
			$arr = explode(' ', $data['order']['user']['name']);
			$lastname = array_pop($arr);
			$firstname = trim(implode(' ', $arr));
			$email = $data['order']['user']['email'];
			$phone = $data['order']['user']['phone'];
		}
		
		if (isset($data['order']['delivery']) && isset($data['order']['delivery']['price'])) {
			$totals[] = array('code'=>'shipping', 'title'=>'Доставка', 'text'=>$this->currency->format($data['order']['delivery']['price'], $currency), 'value'=>$data['order']['delivery']['price'], 'sort_order'=>2);		
			$total+= $data['order']['delivery']['price'];
		}

		$totals[] = array('code'=>'total', 'title'=>'Итого', 'text'=>$this->currency->format($total, $currency), 'value'=>$total, 'sort_order'=>3);

		if (isset($data['order']['paymentType']) && isset($data['order']['paymentMethod'])) {
			$payment_method_text = isset($this->PAYMENT_TYPES[$data['order']['paymentType']]) ? $this->PAYMENT_TYPES[$data['order']['paymentType']] : $data['order']['paymentType'];
			$payment_method_text.= $data['order']['paymentMethod'] && isset($this->PAYMENT_METHODS[$data['order']['paymentMethod']]) ? ' ('.$this->PAYMENT_METHODS[$data['order']['paymentMethod']].')' : '';
		}
		else {
			$payment_method_text = 'Способ оплаты не выбран';
		}

		$order = array(
			'invoice_prefix'=>$this->config->get('config_invoice_prefix'),
			'store_id'=>$this->config->get('config_store_id'),
			'store_name'=>$this->config->get('config_name'),
			'store_url'=>($this->config->get('config_store_id') ? $this->config->get('config_url') : HTTP_SERVER),
			'customer_id'=>0,
			'customer_group_id'=>1, //!!!
			'firstname'=>$firstname,
			'lastname'=>$lastname,
			'email'=>$email,
			'telephone'=>$phone,
			'fax'=>'',
			'payment_firstname'=>$firstname,
			'payment_lastname'=>$lastname,
			'payment_company'=>'Yandex',
			'payment_company_id'=>'',
			'payment_tax_id'=>'',
			'payment_address_1'=>$address_1,
			'payment_address_2'=>$address_2,
			'payment_city'=>$city,
			'payment_postcode'=>$postcode,
			'payment_country'=>$country,
			'payment_country_id'=>$country_id,
			'payment_zone'=>$zone,
			'payment_zone_id'=>$zone_id,
			'payment_address_format'=>'',
			'payment_method'=>$payment_method_text,
			'payment_code'=>isset($data['order']['paymentType']) && isset($this->PAYMENT_TYPES_MODULES[$data['order']['paymentType']]) ? $this->PAYMENT_TYPES_MODULES[$data['order']['paymentType']] : '',

			'shipping_firstname'=>'Получатель',
			'shipping_lastname'=>'Яндекс.Маркет',
			'shipping_company'=>'Yandex',
			'shipping_address_1'=>$address_1,
			'shipping_address_2'=>$address_2,
			'shipping_city'=>$city,
			'shipping_postcode'=>$postcode,
			'shipping_country'=>$country,
			'shipping_country_id'=>$country_id,
			'shipping_zone'=>$zone,
			'shipping_zone_id'=>$zone_id,
			'shipping_address_format'=>'',
			'shipping_method'=>$data['order']['delivery']['serviceName'],
			'shipping_code'=>$data['order']['delivery']['type'],
			'comment'=> (isset($data['order']['delivery']['shipments']) && isset($data['order']['delivery']['shipments'][0]) ? 'Отгрузка в Яндекс.Маркет '
                    .$data['order']['delivery']['shipments'][0]['shipmentDate'].'. '
                : '')
                .(isset($data['order']['delivery']['dates']) ? 'Доставка ' 
					.(isset($data['order']['delivery']['dates']['fromDate']) ? ' c '.$data['order']['delivery']['dates']['fromDate'] : '')
					.(isset($data['order']['delivery']['dates']['toDate']) ? ' до '.$data['order']['delivery']['dates']['toDate'] : '')
				: '')
				.(isset($data['order']['notes']) && $data['order']['notes'] ? "\nКомментарий покупателя: '".$data['order']['notes']."'" : ''),
			'total'=>$total,
			'order_status_id'=>0,
			'order_status'=>'Ошибочные заказы',
			'affiliate_id'=>0,
			'commission'=>0,
			'language_id'=>$this->config->get('config_language_id'), //!!!
			'currency_id'=>$this->currency->getId($currency), //!!!
			'currency_code'=>$currency,
			'currency_value'=>$this->currency->getValue($currency), //!!!
			'ip'=>$this->request->server['REMOTE_ADDR'],
			'forwarded_ip'=>(isset($this->request->server['HTTP_X_FORWARDED_FOR']) ? $this->request->server['HTTP_X_FORWARDED_FOR'] : $this->request->server['REMOTE_ADDR']),
			'user_agent'=>'Yandex Robot',
			'accept_language'=>(isset($this->request->server['HTTP_ACCEPT_LANGUAGE']) ? $this->request->server['HTTP_ACCEPT_LANGUAGE'] : ''),
			
			'products'=>$products,
			'totals'=>$totals,
			
			'vouchers'=>array(),
            //Избыточные поля для модуля Simple
            'marketing_id'=>0,
            'tracking'=>''
		);
        if (isset($data['order']['paymentMethod']) && isset($this->PAYMENT_TYPES_MODULES[$data['order']['paymentMethod']]) && $this->PAYMENT_METHODS[$data['order']['paymentMethod']]) {
            $order['payment_custom_field'] = array(
                'name' => 'Способ оплаты',
                'value' => $this->PAYMENT_METHODS[$data['order']['paymentMethod']],
                'sort_order'=>1
            );
        }
		$order_id = $this->model_checkout_order->addOrder($order);
		
		if ($order_id) {
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET yaorder_id='".intval($data['order']['id'])."' WHERE order_id='".$order_id."'");
			$ret =  array('order'=>array('id'=>$order_id.'', 'accepted'=>true));
            
            //++++ Добавим дату отгрузки в поле shipping_datetime_start ++++
            /*
            if (isset($data['order']['delivery']['shipments']) && isset($data['order']['delivery']['shipments'][0])) {
                $shdate_arr = explode('-', $data['order']['delivery']['shipments'][0]['shipmentDate']);
                $shdate = implode('-', array_reverse($shdate_arr)).' 08:00:00';
                $this->db->query("UPDATE `" . DB_PREFIX . "order` SET shipping_datetime_start='".$shdate."' WHERE order_id='".$order_id."'");
            }
            */
            //---- Добавим дату отгрузки в поле shipping_datetime_start ----
		}
		else {
			$ret =  array('order'=>array('accepted'=>false));
		}
		
		header('Content-Type: application/json;charset=utf-8');
		echo json_encode($ret);
	}
	
	public function status() {
		$postdata = file_get_contents("php://input");
		if (!$postdata) {
			header('HTTP/1.0 404 Not Found');
			echo '<h1>No data posted</h1>';
			exit;
		}
		
		$this->load->model('checkout/order');
		$this->log('status: '.$postdata);
		$data = json_decode($postdata, true);
		
		$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE yaorder_id='".intval($data['order']['id'])."'");
		$order_id = (isset($query->row['order_id']) ? $query->row['order_id'] : 0);
		
		if ($order_id) {
			if ($data['order']['status'] == 'PROCESSING') {
				//$this->db->query("UPDATE `" . DB_PREFIX . "order` SET source='beru' WHERE order_id='".intval($data['order']['id'])."'");
				$this->model_checkout_order->addOrderHistory($order_id, $this->CONFIG['STATUS_MAPPING'][$data['order']['status']], 'Заказ создан через Яндекс.Маркет', false);
			}
			else {
				$this->model_checkout_order->addOrderHistory($order_id, $this->CONFIG['STATUS_MAPPING'][$data['order']['status']], isset($data['order']['substatus']) ? $data['order']['substatus'] : 'Яндекс.Маркет поменял статус заказа', false);
			}
		}
		
		header('Content-Type: application/json;charset=utf-8');
		echo json_encode(array('ok'=>1));
	}
	
	public function forward_status(&$route, &$data) {
		/*
		$order_id = $data[0];
		$order_status_id = $data[1];
		if ($order_status_id == 5 || $order_status_id == 103) { //Сделка завершена
			$query = $this->db->query("SELECT yaorder_id FROM `" . DB_PREFIX . "order` WHERE order_id='".intval($order_id)."'");
			$yaorder_id = (isset($query->row['yaorder_id']) ? $query->row['yaorder_id'] : 0);
			if ($yaorder_id) {
				$url = HTTPS_SERVER.'yaorder/index.php?action=status&ya_order='.$yaorder_id.'&substatus=SHIPPED'
					.'&company_id='.$this->config->get('yabuy_yacompany').'&login='.$this->config->get('yabuy_login').'&token='.$_SESSION['yo_yandex_access_token'];
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
