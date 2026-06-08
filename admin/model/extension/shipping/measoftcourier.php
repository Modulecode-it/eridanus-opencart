<?php
require_once(DIR_SYSTEM.'library/measoft/measoftcourier.class.php');

class ModelExtensionShippingMeasoftcourier extends Model
{
    /**
     * Запрос по АПИ на создание нового заказа
     */
    public function newOrder($order_id, $shipping, $date, $time_min, $time_max, $price = 0, $sender = null, $instruction = null, $return = null, $pvz_id = null, $pvz_name = null)
    {
        $courier_order = $this->getOrder($order_id, $this->config->get('shipping_measoftcourier_order_prefix'), $shipping, $date, $time_min, $time_max, $sender, $pvz_id);

        if ($pvz_name) {
            $courier_order['info']['address'] = $pvz_name;
        }

        if ($price) {
            $courier_order['info']['price'] = $price;
        }

        if ($instruction) {
            $courier_order['info']['instruction'] = $instruction;
        }

        if ($return) {
            $courier_order['info']['return'] = $return;
        }

        // получаем объект для запросов и проверяем данные заказа на валидность
        $courier_object = $this->getCourierObject();

        $courier_object->orderValidate($courier_order['info'], $courier_order['items']);

        // отправляем заказ
        if ($courier_object->errors || !$courier_object->orderRequest($courier_order['info'], $courier_order['items'])) {
            throw new Exception('При отправке заказа произошли ошибки: ' . print_r($courier_object->errors, true));
        }

        return 'Заказ №' . $order_id . ' успешно отправлен.';
    }
	
	
	
		/**
     * Получение по АПИ кода клиента
     */
    public function getClientCode($login, $pass, $extra)
    {
		
        // получаем объект для запросов
        $courier_object = $this->getCourierObject($login, $pass, $extra);


        // возвращаем статус
        $client_code = $courier_object->getClientCode();

       
        return $client_code;
    }
	
    /**
     * Получение по АПИ статуса заказа
     */
    public function getStatus($order_id)
    {
		
        // получаем объект для запросов
        $courier_object = $this->getCourierObject();


        // возвращаем статус
        $status = $courier_object->statusRequest(Measoft::orderNoTransform(
            $order_id,
            $this->config->get('shipping_measoftcourier_order_prefix'),
            $this->config->get('shipping_measoftcourier_order_fixed_length')
        ));

        if (!$status) {
            throw new Exception($courier_object->errors);
        }

        return $status;
    }

    /**
     * Получение данных о заказе для отсылки через объект Measoft.
     */
    public function getOrder($order_id, $prefix = '', $shipping = 0, $date = null, $time_min = null, $time_max = null, $sender = null, $pvz_id = null)
    {
        $this->load->model('sale/order');
        $order = $this->model_sale_order->getOrder($order_id);
        $products = $this->model_sale_order->getOrderProducts($order_id);
        $totals = $this->getOrderTotals($order_id);

        $this->load->model('catalog/product');

        $company = null;
        if ($order['shipping_company']) {
            $company = $order['shipping_company'];
        } elseif ($order['payment_company']) {
            $company = $order['payment_company'];
        }

        $name = null;
        if ($order['shipping_firstname']) {
            $name = self::implode(' ', $order['shipping_firstname'], $order['shipping_lastname']);
        } elseif ($order['payment_firstname']) {
            $name = self::implode(' ', $order['payment_firstname'], $order['payment_lastname']);
        }

        $city = null;
        if ($order['shipping_city']) {
            $city = trim(str_replace('г.', '', $order['shipping_city']));
        } elseif ($order['payment_city']) {
            $city = trim(str_replace('г.', '', $order['payment_city']));
        }

        $addr = null;
        if ($order['shipping_address_1']) {
            $addr = self::implode(', ', $order['shipping_address_1'], $order['shipping_address_2']);
        } elseif ($order['payment_address_1']) {
            $addr = self::implode(', ', $order['payment_address_1'], $order['payment_address_2']);
        }

        $zipcode = '';
        if ($order['shipping_postcode']) {
            $zipcode = $order['shipping_postcode'];
        } elseif ($order['payment_postcode']) {
            $zipcode = $order['payment_postcode'];
        }

        // модуль отправитель заказа
        $order_info['sender'] = $sender;
        // номер заказа
        $order_info['orderno'] = Measoft::orderNoTransform($order_id, $prefix, $this->config->get('shipping_measoftcourier_order_fixed_length'));

        if ($company) {
            // компания-получатель. Должно быть заполнено company ИЛИ person
            $order_info['company'] = $company;
        }
        // контактное лицо, должно быть заполнено company ИЛИ person
        if ($name) {
            $order_info['person'] = $name;
        }

        // пвз выбранное на карте
        $order_info['pvz_id'] = false;
        if (isset($pvz_id)) {
            $order_info['pvz_id'] = $pvz_id;
        } elseif (isset($order['pvz_id'])) {
            $order_info['pvz_id'] = $order['pvz_id'];
        }
        if (isset($order['pvz_name'])) {
            $order_info['pvz_name'] = $order['pvz_name'];
        }

        // телефон, можно указывать несколько телефонов
        $order_info['phone'] = $order['telephone'];
        $order_info['town'] = $city;
        $order_info['zipcode'] = $zipcode;
        if (isset($addr) || !empty($addr)) {
            $order_info['address'] = $addr;
        } else {
            $order_info['address'] = 'address empty';
        }
        // сумма заказа
        $order_info['price'] = number_format(doubleval($order['total']), 2, '.', '');
        // объявленная стоимость
        $order_info['inshprice'] = number_format(doubleval($totals['sub_total']), 2, '.', '');
        // доставка
        $order_info['deliveryprice'] = number_format(doubleval($totals['shipping']), 2, '.', '');
        // оплата
        if (in_array($order['payment_code'], (!empty($this
            ->config
            ->get('shipping_measoftcourier_payment_cash')) ? $this->config->get('shipping_measoftcourier_payment_cash') : []))) {
            $order_info['paytype'] = 'CASH';
        } elseif (in_array($order['payment_code'], (!empty($this
            ->config
            ->get('shipping_measoftcourier_payment_card')) ? $this->config->get('shipping_measoftcourier_payment_card') : []))) {
            $order_info['paytype'] = 'CARD';
        } elseif (in_array($order['payment_code'], (!empty($this
            ->config
            ->get('shipping_measoftcourier_payment_none')) ? $this->config->get('shipping_measoftcourier_payment_none') : []))) {
            $order_info['paytype'] = 'NO';
        }
        // поручение
        $order_info['instruction'] = $order['comment'];
        // дата доставки в формате "YYYY-MM-DD"
        $order_info['date'] = $date;
        // желаемое время доставки в формате "HH:MM"
        $order_info['time_min'] = $time_min;
        // желаемое время доставки в формате "HH:MM"
        $order_info['time_max'] = $time_max;
        $order_info['barcode'] = null;
        $order_info['quantity'] = null;
        $order_info['weight'] = null;
        $order_info['return'] = 'NO';

        $order_items = array();
        $enclosure = '';
        $mass = 0;
        if (is_array($products)) {
            foreach ($products as $p) {
                $after_name = '';
                $options = $this->model_sale_order->getOrderOptions($order_id, $p['order_product_id']);
                $product = $this->model_catalog_product->getProduct($p['product_id']);
				
				$weight_delimeter = 1;
				if ($product['weight_class_id'] == 2) {
					$weight_delimeter = 1000;
				}

                if ($options) {
                    foreach ($options as $option) {
                        $after_name .= ' ' . $option['value'];
                    }
                }
				
				$weight = (float)(((isset($product['weight']) && $product['weight'] ) ? doubleval($product['weight']) : (0.1 * $weight_delimeter)) / $weight_delimeter);

                $order_items[] = array(
                    'name' => self::implode(' ', $p['name'], $after_name) ,
                    'article' => $this->config->get('shipping_measoftcourier_use_articles') && isset($product['sku']) ? $product['sku'] : '',
                    'barcode' => isset($p['barcode']) ? $p['barcode'] : '',
                    'quantity' => $p['quantity'],
                    'retprice' => doubleval($this->tax->calculate($p['price'], $this->config->get('shipping_measoftcourier_tax_class_id'), $this->config->get('config_tax'))) ,
                    'mass' => $weight,
					'length' => (float)$product['length'],
					'width' => (float)$product['width'],
					'height' => (float)$product['height'],
                );

                $enclosure .= $p['name'] . ', кол-во: ' . $p['quantity'] . '.';
                $mass += $weight * doubleval($p['quantity']);
            }

            /*if ($totals['shipping']) {
                $order_items[] = array(
                    'name' => 'Доставка',
                    'article' => '',
                    'barcode' => '',
                    'quantity' => 1,
                    'retprice' => $totals['shipping'],
                    'mass' => 0,
                );
            }*/
        }

        // наименование
        $order_info['enclosure'] = $enclosure;

        // масса посылки
        $order_info['weight'] = $mass;

        return array(
            'info' => $order_info,
            'items' => $order_items,
        );
    }

    /**
     * Извлекает итоговые суммы
     */
    private function getOrderTotals($order_id)
    {
        // free shipping
        /*if (_MEASOFT_ORDER_STATUS_FREE_SHIPPING_) {
            $history = $this->db->query("SELECT oh.order_status_id FROM " . DB_PREFIX . "order_history oh WHERE oh.order_id = " . (int)$order_id)->rows;
            foreach ($history as $key => $value) {
                if ($value['order_status_id'] == _MEASOFT_ORDER_STATUS_FREE_SHIPPING_) {
                    return 0;
                }
            }
        }*/

        $this->load->model('sale/order');
        $totals = $this->model_sale_order->getOrderTotals($order_id);

        $result = array();
        if ($totals) {
            foreach ($totals as $total) {
                $result[$total['code']] = $total['value'];
            }
        }

        return $result;
    }

    private function getCourierObject($login=null, $pass=null, $client_id=null)
    {
        $this->load->model('setting/setting');

		if(!$login)
		$login = $this->config->get('shipping_measoftcourier_login');
		if(!$pass)
        $pass = $this->config->get('shipping_measoftcourier_password');
		if(!$client_id)
        $client_id = $this->config->get('shipping_measoftcourier_extra');

        if (!$login || !$pass || !$client_id) {
            throw new Exception('Проверьте аутентификационные данные');
        }
		
        return new Measoft($login, $pass, $client_id, $this->language->get('code'));
    }

    private static function implode()
    {
        $args = func_get_args();
        $glue = '';
        $pieces = array();

        foreach ($args as $i => $value) {
            if ($i == 0) {
                $glue = $value;
            } elseif ($value) {
                $pieces[] = $value;
            }
        }

        if ($pieces) {
            return implode($glue, $pieces);
        }

        return '';
    }
}
