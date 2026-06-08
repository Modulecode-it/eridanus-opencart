<?php
class ModelExtensionShippingRussianPost extends Model {
	function getQuote($address) {	
		$this->load->language('extension/shipping/russian_post');
		
		$method_data = array();
		$quote_data = array();
		$shipping_date = '';
		$shipping_data = array();
		$images = array();
		
		if ($this->config->get('shipping_russian_post_show_images')) {
		
			$this->load->model('tool/image');
			
			foreach ($this->config->get('shipping_russian_post_images') as $image) {
				$images[] = '<img src="' . $this->model_tool_image->resize($image ? $image : 'no_image.png', $this->config->get('shipping_russian_post_image_width'), $this->config->get('shipping_russian_post_image_width')) . '" alt="" class="img-thumbnail" /> ';
			}
		}
		
		// Скрывать доставку если находимся в регионе отправки
		$hide_zone_id = false;
			
		if ($this->config->get('shipping_russian_post_hide') && (int)$address['zone_id'] > 0 && $address['zone_id'] == $this->config->get('config_zone_id')) {
			$hide_zone_id = true;
		}

		if ($this->config->get('shipping_russian_post') && !$hide_zone_id) {
			
			$arrayResponse 	= array();
			
			$russian_post = $this->config->get('shipping_russian_post');
			// Включать объявленную ценность
			if ($this->config->get('shipping_russian_post_declare')) {
				$v = $this->cart->getTotal();
			} else {
				$v = '0';
			}
			// Вес (по умолчанию)
			if ($this->cart->getWeight() > 0) {
				$weight = intval($this->weight->convert($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->config->get('shipping_russian_post_weight_class_id'))) + $this->config->get('shipping_russian_post_weight_pack');
			} else {
				$weight = (int)$this->config->get('shipping_russian_post_weight') + (int)$this->config->get('shipping_russian_post_weight_pack');
			}
			// Определение страны доставки
			$country = 'RU';
			if (isset($address["iso_code_2"]) && $address["iso_code_2"]) { 
				$country = $address["iso_code_2"];
			} elseif (isset($address["country_id"]) && $address["country_id"]) {
				$this->load->model('localisation/country');

				$country_info = $this->model_localisation_country->getCountry($address["country_id"]);
				
				if ($country_info) {
					$country = $country_info['iso_code_2'];
				}
			}
			
			if ($country == 'RU') {
				if (isset($address["postcode"]) && (int)$address["postcode"]) {
					$f = $this->config->get("shipping_russian_post_postcode");
					$arrayResponse = $this->apiRequest($country, $f, $address["postcode"], $weight, $v);
				}
				
				if (!$arrayResponse || $arrayResponse['Status'] == 'BAD_TO_CITY') {
					if (isset($address["city"]) && $address["city"]) {
						
						// Приведение формата городов в правильный вид
						if ((int)mb_stripos($address["city"], '-на-')) {
							$address["city"] = mb_convert_case(mb_substr($address["city"], 0, mb_stripos($address["city"], '-на-')), MB_CASE_TITLE, "UTF-8") . '-на-' . mb_convert_case(mb_substr($address["city"], mb_stripos($address["city"], '-на-') + 4), MB_CASE_TITLE, "UTF-8");
						} else {
							$address["city"] = mb_convert_case($address["city"], MB_CASE_TITLE, "UTF-8");
						}
						
						$f = $this->config->get("shipping_russian_post_city");
						$t = $address["city"];
						$arrayResponse = $this->apiRequest($country, $f, $t, $weight, $v);
					
						if ($arrayResponse['Status'] == 'BAD_TO_CITY') {
							if (isset($address["zone"]) || $address["zone"]) {
								$t = $address["city"] . ', ' . $address["zone"];
								$arrayResponse = $this->apiRequest($country, $f, $t, $weight, $v);
							}
						}
					}
				}
				
				if (!$arrayResponse || $arrayResponse['Status'] == 'BAD_TO_CITY' && (int)$this->config->get("shipping_russian_post_postcode_default")) {
					$f = $this->config->get("shipping_russian_post_postcode");
					$t = $this->config->get("shipping_russian_post_postcode_default");
					$arrayResponse = $this->apiRequest($country, $f, $t, $weight, $v);
				}
			} else {
				$arrayResponse = $this->apiRequest($country, $this->config->get("shipping_russian_post_postcode"), '', $weight, $v);
			}
			
			$quote_data = array();
			$sort_order = array();
			
			if ($arrayResponse && $arrayResponse['Status'] != 'BAD_TO_CITY') {
				foreach ($russian_post as $i => $post) {
					if (!$post['status']) {
						continue;
					}
					
					if ($post['geo_zone_id']) {
						$query = $this->db->query(
							"SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone 
							 WHERE geo_zone_id = '" . (int)$post['geo_zone_id'] . "' AND " .
								"country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
						if (!$query->num_rows) {
							continue;
						}
					}

					switch ($post['type']) {
						case '0':
							$shipping_data = $this->getShippingData($arrayResponse, 'ПростаяБандероль');
							break;
						case '1':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЗаказнаяБандероль');
							break;
						case '2':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЗаказнаяБандероль1Класс');
							break;
						case '3':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЦеннаяБандероль');
							break;
						case '4':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЦеннаяПосылка');
							break;
						case '5':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЦеннаяАвиаБандероль');
							break;
						case '6':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЦеннаяАвиаПосылка');
							break;
						case '7':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЦеннаяБандероль1Класс');
							break;
						case '8':
							$shipping_data = $this->getShippingData($arrayResponse, 'EMS');
							break;
						case '9':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМешокМ');
							break;
						case '10':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМешокМАвиа');
							break;
						case '11':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМешокМЗаказной');
							break;
						case '12':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМешокМАвиаЗаказной');
							break;
						case '13':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждБандероль');
							break;
						case '14':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждБандерольАвиа');
							break;
						case '15':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждБандерольЗаказная');
							break;
						case '16':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждБандерольАвиаЗаказная');
							break;
						case '17':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМелкийПакет');
							break;
						case '18':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМелкийПакетАвиа');
							break;
						case '19':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМелкийПакетЗаказной');
							break;
						case '20':
							$shipping_data = $this->getShippingData($arrayResponse, 'МждМелкийПакетАвиаЗаказной');
							break;
						case '21':
							$shipping_data = $this->getShippingData($arrayResponse, 'EMS_МждДокументы');
							break;
						case '22':
							$shipping_data = $this->getShippingData($arrayResponse, 'EMS_МждТовары');
							break;
						case '23':
							$shipping_data = $this->getShippingData($arrayResponse, 'КурьерОнлайн');
							break;
						case '24':
							$shipping_data = $this->getShippingData($arrayResponse, 'ПосылкаОнлайн');
							break;
						case '25':
							$shipping_data = $this->getShippingData($arrayResponse, 'ПростоеПисьмо');
							break;
						case '26':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЗаказноеПисьмо');
							break;
						case '27':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЦенноеПисьмо');
							break;
						case '28':
							$shipping_data = $this->getShippingData($arrayResponse, 'ПростойМультиконверт');
							break;
						case '29':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЗаказнойМультиконверт');
							break;
						case '30':
							$shipping_data = $this->getShippingData($arrayResponse, 'ПростоеПисьмо1Класс');
							break;
						case '31':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЗаказноеПисьмо1Класс');
							break;
						case '32':
							$shipping_data = $this->getShippingData($arrayResponse, 'ЦенноеПисьмо1Класс');
							break;
					}
					
					// Сроки доставки
					if ($this->config->get('shipping_russian_post_date')) {
						if ($shipping_data['date']) {
							if ((int)$post['date']) {
				
								$date_start = (int)$shipping_data['date'];
								$date_end = (int)$post['date'];
								
								if ($date_end <= $date_start) {
									$date_end += $date_start;
								}
								
								$shipping_data['date'] = $date_start . '-' . $date_end;
							}
							
							$shipping_date = sprintf($this->language->get('text_shipping_date'), $shipping_data['date']);
						} else { 
							// Сроки для международной доставки, так как в postcalc они не отображаются
							if ($post['date']) {
								$shipping_date = sprintf($this->language->get('text_shipping_date'), $post['date']);
							} else {
								$shipping_date = '';
							}
						}
					}
					// Бесплатная доставка
					if ((int)$post['free'] && $post['free'] < $this->cart->getTotal()) {
						$convert_cost = 0;
						$post['cost'] = 0;
					} else {
						// Конвертируем цену доставки из RUB в валюту по умолчанию
						$convert_cost = $this->currency->convert($shipping_data['cost'], 'RUB', $this->config->get('config_currency'));
						
						// Стоимость доставки с учетом наценки
						if ((float)$post['cost']) {
							if ($post['cost_prefix'] == '%') {
								$convert_cost = $convert_cost + ($convert_cost / 100) * $post['cost'];
							} elseif ($post['cost_prefix'] == '+') {
								$convert_cost = $convert_cost + $post['cost'];
							}
						}
					}
					
					$cost = $this->currency->format($this->tax->calculate($convert_cost, $post['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					
					if ($shipping_data['cost'] && $weight <= $shipping_data['max_weight'] && $this->cart->getTotal() >= (int)$post['limit']) {
						$quote_data['russian_post' . $i] = array(
							'code' 			=> 'russian_post.russian_post' . $i,
							'title' 		=> ($images ? $images[$i] : '') . $this->language->get('text_' . $post['type']) . $shipping_date,
							'cost' 			=> $convert_cost,
							'tax_class_id'  => $post['tax_class_id'],
							'text' 			=>  $cost 
						);                                 
						$sort_order[$i] = $post['sort_order'];
					}
				}
				
				array_multisort($sort_order, SORT_ASC, $quote_data);
				
				if ($quote_data) {
					$method_data = array(
						'code' => 'russian_post',
						'title' => $this->language->get('text_title'),
						'quote' => $quote_data,
						'sort_order' => $this->config->get('shipping_russian_post_sort_order'),
						'error'      => false
					);
				}
			}
		}
		
		return $method_data;
	}
	
	function getShippingData($response, $shipping) {
		return array(
			'cost'      => isset($response['Отправления'][$shipping]['Доставка']) ? $response['Отправления'][$shipping]['Доставка'] : 0,
			'date'       => isset($response['Отправления'][$shipping]['СрокДоставки']) ? $response['Отправления'][$shipping]['СрокДоставки'] : 0,
			'max_weight' => isset($response['Отправления'][$shipping]['ПредельныйВес']) ? $response['Отправления'][$shipping]['ПредельныйВес'] : 0
		);
	}
	
	function apiRequest($country, $f, $t, $weight, $v) {
		$Request = http_build_query(array (
			"st" => $_SERVER['HTTP_HOST'],
			"ml" => $this->config->get('config_email'),
			"c"  => $country,
			"f"  => $f,
			"t"  => $t,
			"w"  => $weight,
			"v"  => $v,
			"o"  => 'php',
			"cs" => 'utf-8',
			"key" => 'eridanus-3523813f'
		));

		if ($this->config->get("shipping_russian_post_type_query")) {
			$Request = "http://api.postcalc.ru/?" . $Request;
			$Response = file_get_contents($Request);
			
			if (substr($Response, 0, 3) == "\x1f\x8b\x08" ) {
				$Response = gzinflate(substr($Response, 10, -8));	
			}
		} else {
			if ($this->request->server['HTTPS']) {
				$server = $this->config->get('config_ssl');
			} else {
				$server = $this->config->get('config_url');
			}
			
			$url = $server . 'catalog/model/extension/shipping/postcalc/postcalc_light.php?';
			
			if ($country == 'RU') {
				$Request = http_build_query(array (
					"postcalc_from"      => $f,
					"postcalc_to"        => $t,
					"postcalc_weight"    => $weight,
					"postcalc_valuation" => $v,
					"postcalc_country"   => $country
				));
			} else {
				$Request = http_build_query(array (
					"postcalc_from"      => $f,
					"postcalc_weight"    => $weight,
					"postcalc_valuation" => $v,
					"postcalc_country"   => $country
				));
			}
			
			$Response = file_get_contents($url . $Request); 
		}
		
		// Режим отладки
		if ($this->config->get('shipping_russian_post_debug')) {
			echo 'Request: ' . $Request . '<br>';
			echo 'Response: <pre>';
			print_r(unserialize($Response));
			echo '</pre>';
		}
		
		if ($Response) {
			return unserialize($Response);
		} else {
			return false;
		}
	}
}
?>