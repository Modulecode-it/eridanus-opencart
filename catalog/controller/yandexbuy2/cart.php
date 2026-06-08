<?php
/**
* Маркетплейс "Яндекс.Маркет" - прием заказов по API для OpenCart (ocStore) 2.x
*
* @author Alexander Toporkov <toporchillo@gmail.com>
* @copyright (C) 2013- Alexander Toporkov
* @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
require_once(dirname(__FILE__).'/base.php');

class ControllerYandexbuy2Cart extends ControllerYandexbuy2Base {

	public function index() {
		$postdata = file_get_contents("php://input");
		if (!$postdata) {
			header('HTTP/1.0 404 Not Found');
			echo '<h1>No data posted</h1>';
			exit;
		}
		$this->load->model('catalog/product');
		$data = json_decode($postdata, true);
		
		$currency = $data['cart']['currency']; //Assume RUR
		
		$ret = array('cart'=>array('items'=>array()));
		$total = 0;

		foreach ($data['cart']['items'] as $item) {
			$offer_id = $item['offerId'];
			$option_value_id = 0;
			$option2_value_id = 0;
			if (!$this->config->get('yabuy2_long_id')) {
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
			if ($product_info['status'] != 1
				|| !$product_info['quantity']
				/* || $product_info['stock_status'] == (int)$out_of_stock_id */) {
				continue;
			}

			$count = min($product_info['quantity'], $item['count']);
			if ($count < $product_info['minimum']) {
				$count = $product_info['minimum'];
			}
			$price = ($product_info['special'] ? $product_info['special'] : $product_info['price']);
			if ($option_value_id > 0) {
				$option = $this->getProductOptionData($offer_id, $option_value_id);
				if (!$option['quantity'] && $option['subtract']) {
					continue;
				}
				if ($option['price_prefix'] == '+') {
					$price+= $option['price'];
				}
				elseif ($option['price_prefix'] == '-') {
					$price-= $option['price'];
				}
			}
			if ($option2_value_id > 0) {
				$option2 = $this->getProductOptionData($offer_id, $option2_value_id);
				if (!$option2['quantity'] && $option2['subtract']) {
					continue;
				}
				if ($option2['price_prefix'] == '+') {
					$price+= $option2['price'];
				}
				elseif ($option2['price_prefix'] == '-') {
					$price-= $option2['price'];
				}
			}
			
			$option_data = array();
			//@todo сделать учет опций
			$this->cart->add($offer_id, intval($count), $option_data);
			
			$total+= floatval($price)*$count;
			$ret['cart']['items'][] = array(
				'feedId'=>$item['feedId'],
				'offerId'=>$item['offerId'],
				//'price'=>floatval($price),
				'count'=>intval($count),
				//'delivery'=>true
			);			
		}
		if ($this->DBS) {
            $regions = $this->extractRegions($data['cart']['delivery']['region']);
            $address_data = isset($data['cart']['delivery']['address']) ? $data['cart']['delivery']['address'] : array();
            //$ext_shipping = $this->getExternalShipping($this->regionToAddress($data['cart']['delivery']['region'], $address_data));
            $delivery_shipping = $this->getShipping($total, $regions);
            $outlets_shipping = $this->getOutletShipping($total, $regions);
            $postals_shipping = $this->getPostalShipping($total, $regions);
            $ret['cart']['deliveryOptions'] =  array_merge($delivery_shipping, /* $ext_shipping, */ $outlets_shipping, $postals_shipping);
            
            $ret['cart']['paymentMethods'] = array('CASH_ON_DELIVERY'); //$this->paymentMethods;
        }
		header('Content-Type: application/json;charset=utf-8');
		echo json_encode($ret);
	}
	
	protected function extractRegions($region) {
		$res_regions = array($region['id']);
		if (isset($region['parent'])) {
			$res_regions = array_merge($res_regions, $this->extractRegions($region['parent']));
		}
		return $res_regions;
	}

	/**
	* Метод возвращает возможные способы доставки
	* @param float $total стоимость товаров в корзине
	* @param array $region регион доставки в формате Яндекса
	*/
	protected function getShipping($total=0, $regions=array()) {
		$ret = array(
		);
		
		$deliveries = $this->getDeliveries();
		if ($deliveries && is_array($deliveries)) {
			$idxs = array();
			$region_occuration = count($regions);
			foreach ($deliveries as $key=>$delivery) {
				$delivery_regions = explode(',', $delivery['region']);
				foreach ($delivery_regions as $delivery_region) {
					$occ = array_search($delivery_region, $regions);
					if ($occ !== false && $occ <= $region_occuration) {
						$idxs[] = $key;
						$region_occuration = $occ;
					}
				}
			}
			$idxs = array_unique($idxs);
			foreach ($idxs as $idx) {
				$delivery = $deliveries[$idx];
				$price = isset($delivery['price']) ? $this->getDeliveryPrice($delivery['price'], $total) : 0;
				if ($price === false)
					continue;
				$ret[] = array(
					'id'=>$delivery['id'],
					'type'=>'DELIVERY',
					'serviceName'=>$delivery['name'],
					'price'=>intval($price),
					'dates'=>$this->getDeliveryDays($delivery['days'], $delivery['before']),
					'paymentMethods'=>$delivery['payments']
				);
			}
		}
		return $ret;
	}
	
	protected function getOutletShipping($total=0, $regions=array()) {
		$ret = array();
		$outlets = $this->getOutlets($regions);
		if ($outlets && is_array($outlets)) {
			$outlet_ids = array();
			$prev_price = 'unset';
			foreach ($outlets as $outlet) {
				$price = isset($outlet['price']) ? $this->getDeliveryPrice($outlet['price'], $total) : 0;
				if ($price!=$prev_price) {
					$prev_price = $price;
					if ($price === false)
						continue;
					if (!isset($outlet['before'])) {
						$outlet['before'] = 12;
					}
					$ret[] = array(
						'id'=>'pickup',
						'type'=>'PICKUP',
						'serviceName'=>'Самовывоз',
						'price'=>intval($price),
						'dates'=>$outlet['days'] != '' ? $this->getDeliveryDays($outlet['days'], $outlet['before']) : $this->getDeliveryDays('1-2', $outlet['before']),
						'outlets'=>array(array('code' => (string)$outlet['id'])),
						'paymentMethods'=>isset($outlet['payments']) ? $outlet['payments'] : 'CASH_ON_DELIVERY'
					);
				}
				else {
					$ret[count($ret)-1]['outlets'][] = array('id' => intval($outlet['id']));
				}
			}
		}
		return $ret;
	}
	
	protected function getPostalShipping($total=0, $regions=array()) {
		$ret = array();
		$postals = $this->getPostals();
		if ($postals && is_array($postals)) {
			$idxs = array();
			$region_occuration = count($regions);
			foreach ($postals as $key=>$delivery) {
				$delivery_regions = explode(',', $delivery['region']);
				foreach ($delivery_regions as $delivery_region) {
					$occ = array_search($delivery_region, $regions);
					if ($occ !== false && $occ <= $region_occuration) {
						$idxs[] = $key;
						$region_occuration = $occ;
					}
				}
			}
			$idxs = array_unique($idxs);
			foreach ($idxs as $idx) {
				$delivery = $postals[$idx];
				$price = isset($delivery['price']) ? $this->getDeliveryPrice($delivery['price'], $total) : 0;
				if ($price === false)
					continue;
				$ret[] = array(
					'id'=>$delivery['id'],
					'type'=>'POST',
					'serviceName'=>$delivery['name'],
					'price'=>intval($price),
					'dates'=>$this->getDeliveryDays($delivery['days'], $delivery['before']),
					'paymentMethods'=>$delivery['payments']
				);
			}
		}
		
		return $ret;
	}
	
	/*
	protected function getExternalShipping($address_data) {
		$cache_key = 'yaorder_ext_shipping.'.$address_data['country_id'].'.'.$address_data['zone_id'].$address_data['postcode'].md5($address_data['city']);
		$ret = $this->cache->get($cache_key);
		if (!$ret) {
			$modules = $this->config->get('yabuy_modules');
			$ret = array();
			if (!is_array($modules)) {
				$modules = array();
			}
			foreach($modules as $code=>$module) {
				if (!$module['type']) {
					continue;
				}
				$this->load->model('extension/shipping/'.$code);
				$model_name = 'model_extension_shipping_'.$code;
				$quote = $this->{$model_name}->getQuote($address_data); 
				if (empty($quote) || !isset($quote['quote'])) {
					continue;
				}
				foreach($quote['quote'] as $item) {
					$item['title'] = strip_tags($item['title']);
					$title_parts = explode(':', $item['title']);
					if (!$title_parts[0]) {
						continue;
					}
					$ret[] = array(
						'id'=>$item['code'],
						'type'=>$module['type'],
						'serviceName'=>mb_substr(trim(strip_tags($title_parts[0])), 0, 50, 'UTF-8'),
						'price'=>intval($item['cost']),
						'dates'=>$this->getDeliveryDays($module['days'], $module['before']),
						'paymentMethods'=>$module['payments']
					);
				}
			}
			$this->cache->set($cache_key, $ret);
		}
		return $ret;
	}
    */

	
	protected function regionToAddress($region, $address = array()) {
		$res_regions = $this->extractRegionsFull($region);
		
		$this->load->model('localisation/zone');
		$zone_id = $this->regionMapping($res_regions['SUBJECT_FEDERATION']['id']);
		$zone_info = $this->model_localisation_zone->getZone($zone_id);
		
		if ($zone_info) {
			$zone_name = $zone_info['name'];
			$zone_code = $zone_info['code'];
		} else {
			$zone_name = '';
			$zone_code = '';
		}	

        $city = array();
        if (isset($res_regions['CITY'])) {
            $city[] = $res_regions['CITY']['name'];
        }
        if (isset($res_regions['VILLAGE'])) {
            $city[] = $res_regions['VILLAGE']['name'];

            if (isset($res_regions['SUBJECT_FEDERATION_DISTRICT'])) {
                $city[] = $res_regions['SUBJECT_FEDERATION_DISTRICT']['name'];
            }
        }
        
		$address = array(
			'firstname'      => '',
			'lastname'       => '',
			'company'        => '',
			'address_1'      => '',
			'address_2'      => '',
			'postcode'       => isset($address['postcode']) ? $address['postcode'] : '',
			'city'           => implode(', ', $city),
			'zone_id'        => $zone_id,
			'zone'           => $zone_name,
			'zone_code'      => $zone_code,
			'country_id'     => 176,
			'country'        => 'Российская Федерация',	
			'iso_code_2'     => 'RU',
			'iso_code_3'     => 'RUS',
			'address_format' => ''
		);

		return $address;
	}
	
	public function regionMapping($ya_reg) {
		$mapping = array(
			11235 => 2726,    //Алтайский край
			11375 => 2729,    //Амурская область
			10842 => 2724,    //Архангельская область
			10946 => 2725,    //Астраханская область
			10645 => 2727,    //Белгородская область
			10650 => 2730,    //Брянская область
			10658 => 2799,    //Владимирская область
			10950 => 2801,    //Волгоградская область
			10853 => 2802,    //Вологодская область
			10672 => 2803,    //Воронежская область
			10243 => 2728,    //Еврейская АО
			21949 => 2734,    //Забайкальский край
			10687 => 2741,    //Ивановская область
			11266 => 2740,    //Иркутская область
			10857 => 2743,    //Калининградская область
			10693 => 2744,    //Калужская область
			11398 => 2775,    //Камчатский край
			11020 => 2733,    //Карачаево-Черкеcсия
			11282 => 2747,    //Кемеровская область
			11070 => 2804,    //Кировская область
			10699 => 2750,    //Костромская область
			10995 => 2751,    //Краснодарский край
			11309 => 2752,    //Красноярский край
			11158 => 2754,    //Курганская область
			10705 => 2755,    //Курская область
			10174 => 2735,    //Ленинградская область
			10712 => 2757,    //Липецкая область
			11403 => 2758,    //Магаданская область
			213 => 2761,    //Москва
			1 => 2722,    //Московская область
			10897 => 2762,    //Мурманская область
			10231 => 2764,    //Ненецкий АО
			11079 => 2766,    //Нижегородская область
			10904 => 2767,    //Новгородская область
			11316 => 2768,    //Новосибирская область
			11318 => 2769,    //Омская область
			11084 => 2771,    //Оренбургская область
			10772 => 2770,    //Орловская область
			11095 => 2773,    //Пензенская область
			11108 => 2774,    //Пермский край
			11409 => 2800,    //Приморский край
			10926 => 2777,    //Псковская область
			11004 => 2760,    //Республика Адыгея
			10231 => 2738,    //Республика Алтай
			11111 => 2794,    //Республика Башкортостан
			11330 => 2796,    //Республика Бурятия
			11010 => 2759,    //Республика Дагестан
			11012 => 2765,    //Республика Ингушетия
			11013 => 2763,    //Республика Кабардино-Балкария
			11015 => 2736,    //Республика Калмыкия
			10933 => 2776,    //Республика Карелия
			10939 => 2787,    //Республика Коми
			11077 => 2808,    //Республика Марий Эл
			11117 => 2782,    //Республика Мордовия
			11443 => 2805,    //Республика Саха
			11021 => 2798,    //Республика Северная Осетия
			11119 => 2746,    //Республика Татарстан
			10233 => 2756,    //Республика Тыва
			11340 => 2721,    //Республика Хакасия
			11029 => 2778,    //Ростовская область
			10776 => 2779,    //Рязанская область
			11131 => 2781,    //Самарская область
			2 => 2785,    //Санкт-Петербург
			11146 => 2783,    //Саратовская область
			11450 => 2737,    //Сахалинская область
			11162 => 2807,    //Свердловская область
			10795 => 2784,    //Смоленская область
			11069 => 2786,    //Ставропольский край
			10802 => 2788,    //Тамбовская область
			10819 => 2792,    //Тверская область
			11353 => 2789,    //Томская область
			10832 => 2790,    //Тульская область
			11176 => 2793,    //Тюменская область
			11148 => 2742,    //Удмуртская Республика
			11153 => 2795,    //Ульяновская область
			11457 => 2748,    //Хабаровский край
			11193 => 2749,    //Ханты-Мансийский АО - Югра
			11225 => 2732,    //Челябинская область
			//11024 => 2739,    //Чеченская Республика
			11156 => 2731,    //Чувашская Республика
			10251 => 2723,    //Чукотский АО
			11232 => 2780,    //Ямало-Ненецкий АО
			10841 => 2806,    //Ярославская область
			 977 => 4237    //Крым
		);
		$oc_reg = isset($mapping[$ya_reg]) ? $mapping[$ya_reg] : 0;
		return $oc_reg;
	}
}
