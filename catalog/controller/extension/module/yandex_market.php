<?php
/*
Для правильной обработки урл авторизации в htaccess необходимо добавить следующую строку при установке модуля
RewriteRule ^yandex_beru/([^?]*) index.php?route=extension/module/yandex_beru/$1 [L,QSA]


https://beru.git.t-leader.ru/index.php?route=extension/module/yandex_beru

Передача заказа и запрос на принятие заказа
https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-order-accept-docpage/

Уведомление о смене статуса заказа
https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-order-status-docpage/

*/
require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarket extends Controller {

	// https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-stocks-docpage/
	public function stocks(){
		$log = new Log('yandex_beru_stocks.log');
		
		if ($this->validate()) {
			$this->load->model('extension/module/yandex_beru');
			$log->write("get");
			$log->write(print_r($this->request->get,1));
			$log->write(print_r(file_get_contents('php://input'),1));

			$request = json_decode(file_get_contents('php://input'));

			$warehouse_id = $request->warehouseId;
			$skus = array();

			if(!empty($this->config->get('yandex_beru_check_5_fbs'))){ //Самопроверка. Отправляем нулевое кол-во товаров

				foreach ($request->skus as $sku) {
					$skus[] = array(
						'sku' => $sku,
						'warehouseId' => $warehouse_id,
						'items' => array(
							array(
								'type' => 'FIT',
								'count' => 0, //TODO Остаток товара
								'updatedAt' => date(DATE_ATOM)
							)
						)
					);
				}
			} else {
				foreach ($request->skus as $sku) {
					$skus[] = array(
						'sku' => $sku,
						'warehouseId' => $warehouse_id,
						'items' => array(
							array(
								'type' => 'FIT',
								'count' => $this->model_extension_module_yandex_beru->getQuantityBySKU($sku), //TODO Остаток товара
								'updatedAt' => date(DATE_ATOM)
							)
						)
					);
				}
			}
			$response = array(
				'skus' => $skus
			);
			
// 			$log->write("response");
// 			$log->write(print_r(json_encode($response),1));

			$this->response->addHeader('Content-Type: application/json');
			$this->response->addHeader('User-Agent: Yandex-Modul-OpenCart');
			$this->response->setOutput(json_encode($response));
		} else {
			header('HTTP/1.1 403 Forbidden');
		}

		/*
		Ошибка 400 Bad Request
		Если магазин считает запрос, поступающий от маркетплейса Беру, некорректным, магазин должен вернуть статус ответа 400 с описанием причины ошибки в теле ответа. Такие ответы будут анализироваться на предмет нарушений и недоработок API со стороны маркетплейса Беру.

		Ошибка 500 Internal Server Error
		В случае технической ошибки на стороне магазина он должен вернуть статус ответа 500. Магазины с большим количеством таких ответов могут быть отключены от маркетплейса Беру.
		*/

	}

	//  Запрос информации о товарах
	//	https://yandex.ru/dev/market/partner-marketplace-cd/doc/dg/reference/post-cart-docpage/
	public function cart() {
		$json = array();

		$log = new Log('yandex_beru_cart_fbs.log');
        $log->write(print_r($this->request->get,1));
        $log->write(print_r(file_get_contents('php://input'),1));
		
		if ($this->validate()) {
			$this->load->model('extension/module/yandex_beru');
			
			$request = json_decode(file_get_contents('php://input'));

			$items = array();
			$count = array();

			foreach ($request->cart->items as $item) {
				$count[$item->offerId] = $this->model_extension_module_yandex_beru->getQuantityBySKU($item->offerId);
			/*
			Товара нет в наличии
			Укажите параметр count=0, вложенный в параметр item. Если все товары из корзины отсутствуют в продаже, передайте параметр items пустым.
			*/

				if(!empty($this->config->get('yandex_beru_check_5_fbs'))){ //Самопроверка. Отправляем нулевое кол-во товаров
					$items = array();
				} else {
					if (array_sum($count) > 0) {
						foreach ($request->cart->items as $item) {
							$items[] = array(
								'feedId' => $item->feedId,
								'offerId' => $item->offerId,
								'count' => (int)$count[$item->offerId]
							);
						}

					}
				}
			}
			$response = array(
				'cart' => array(
					'items' => $items
				)
			);

			
			
			$this->response->addHeader('Content-Type: application/json');
			$this->response->addHeader('User-Agent: Yandex-Modul-OpenCart');
			$this->response->setOutput(json_encode($response));
		} else {
			header('HTTP/1.1 403 Forbidden');
		}

		/*
		Ошибка 400 Bad Request
		Если магазин считает запрос, поступающий от маркетплейса Беру, некорректным, магазин должен вернуть статус ответа 400 с описанием причины ошибки в теле ответа. Такие ответы будут анализироваться на предмет нарушений и недоработок API со стороны маркетплейса Беру.

		Ошибка 500 Internal Server Error
		В случае технической ошибки на стороне магазина он должен вернуть статус ответа 500. Магазины с большим количеством таких ответов могут быть отключены от маркетплейса Беру.
		*/
	}
	
	private function validate() {
		$auth_token = '';
			
		if (!isset($headers['AUTHORIZATION'])) {
			if (function_exists('apache_request_headers')) {
				$requestHeaders = apache_request_headers();
				$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
				if (isset($requestHeaders['Authorization'])) {
					$auth_token = trim($requestHeaders['Authorization']);
				}
			}
		}
		
		if(!empty($this->request->get['auth-token'])){
			$auth_token = $this->request->get['auth-token'];
		}
		
		return ($auth_token == $this->config->get('yandex_beru_auth_token') && $this->config->get('yandex_beru_status') == '1');	
	}

	public function xml(){
		ini_set('memory_limit', '-1');

		$this->load->model('extension/module/yandex_beru');
		$this->load->model('localisation/currency');

		if(!empty($this->request->get['fid'])){

			$version_module = $this->model_extension_module_yandex_beru->getVersionModule();

			$settings = $this->model_extension_module_yandex_beru->checkNameFile($this->request->get['fid']);

			$setting = json_decode($settings['setting'], true);

			$this->clearLog();

			if(!empty($settings)){
				//Список категорий
				$categories = array();
				foreach ($setting['filtres'] as $filtr) {
					if($filtr['yandex_market_category_all'] == 'on' or empty($filtr['yandex_market_category_list'])){
						$categories = $this->model_extension_module_yandex_beru->getCategory();
						$category_all = true;
						break;
					} else {
						array_push($categories, $filtr['yandex_market_category_list']);
					}
				}

				if(empty($category_all)){
					$categories = $this->model_extension_module_yandex_beru->getCategory($categories);
				}
				//Список категорий

				//выборка товаров по фильтру
				foreach ($setting['filtres'] as $key => $filtr) {
					$productsAll[$key] =  $this->model_extension_module_yandex_beru->getProducts($filtr);	
				}
				
				$delivery = '';

				foreach ($setting['delivery_options_main'] as $delivery_shop) {

					if(!empty($delivery_shop['delivery_options_cost']) && !empty($delivery_shop['delivery_options_days'])){

						$delivery .= '<delivery-options>';
							$delivery .= '<option cost="' . $delivery_shop['delivery_options_cost'] . '" days="' . $delivery_shop['delivery_options_days'] . '"';

							if(!empty($delivery_shop['delivery_options_order_before'])){

								$delivery .= ' order-before="' . $delivery_shop['delivery_options_order_before'] .'"';

							}
							
							$delivery .= '/>';
						$delivery .= '</delivery-options>';
					} 		
				} 

				$pickup = '';

				if(!empty($pickup_options['pickup_options_cost']) && !empty($pickup_options['pickup_options_days'])){

					$pickup = '<pickup-options>';
						$pickup .= '<option cost="' . $setting['pickup_options']['pickup_options_cost'] . '" days="' . $setting['pickup_options']['pickup_options_days'] . '"';

						if(!empty($setting['pickup_options']['pickup_options_order_before'])){

							$pickup .= ' order-before="' . $setting['pickup_options']['pickup_options_order_before'] . '"';

						}
					
						$pickup .= '/>';
					$pickup .= '</pickup-options>';
				} 

				foreach ($productsAll as $filtr_number => $products) {
					foreach ($productsAll as $filtr_number_2 => $products_2) {
						if($filtr_number_2 == $filtr_number){
							break;
						} else {
							$result_array =	array_intersect($products, $products_2);
							foreach ($result_array as $key => $value) {
								unset($productsAll[$filtr_number][$key]);
							}
						}
					}
				}
				//выборка товаров по фильтру

				$products_info = array();

				foreach ($productsAll as $filtr_number => $products) {
					foreach ($products as $product_number => $product) {

						$temp_product = $this->model_extension_module_yandex_beru->getProductInfo($product, $setting, $filtr_number);

						$products_info[] = $temp_product;

						$product_id = array_keys($temp_product);

						$productFilter[] = array(
							$filtr_number 	=> $product_id['0']
						);
						
					}
				}


				switch ($setting['type']) {
					case 'arbitrary':
						$type = ' type="vendor.model"';
						break;
						case 'simplified':
							$type = ' ';
							break;
						case 'medicine':
							$type = ' type="medicine"';
							break;
						case 'books':
							$type = ' type="book"';
							break;
						case 'musicVideo':
							$type = ' type="artist.title"';
							break;
						case 'eventTickets':
							$type = ' type="event-ticket"';
							break;
						case 'tours':
							$type = ' type="tour"';
							break;
						case 'alcohol':
							$type = ' type="alco"';
							break;
						case 'audiobooks':
							$type = ' type="audiobook"';
							break;
						default:   //такого варианта быть не должно, значит сохранена неверня информация
							$type = ' ';
							break;	
				}				
				
				$offer = '';
				foreach ($products_info as $product_info) {

					set_time_limit(0);

					$keys = array_keys($product_info);

					$product = $this->model_extension_module_yandex_beru->getProduct($keys['0']);

					$check =  $this->validateOffer($product, $setting,  $product_info);

							if($check === false){
						continue;
					}

                    if($setting['offer_id'] == 'ID'){
                        if(!empty($product_info[$keys['0']]['bid'])){
                            $offer .= '<offer id="' . $keys['0'] . '"' . $type . 'bid="' . $product_info[$keys['0']]['bid'] .'">';
                        } else {
                            $offer .= '<offer id="' . $keys['0'] . '"' . $type . '>';
                        }
                    }else{
                        switch ($setting['offer_id']) {
                            case 'SKU':
                                $offer .= '<offer id="' . $product['sku'] . '"' . $type . '>';
                                break;
                        }
                    }
                    
					$categories_id = explode(',',$product['category_id']);

					foreach ($productFilter as $filter) {
						$filtr_id = array_search($keys['0'], $filter);
						if(!empty($filtr_id)){
							break;
						}
					}

					if($setting['filtres'][$filtr_id]['yandex_market_category_all'] != 'on' && !empty($filtr['yandex_market_category_list'])){
						$res_cat = array_intersect($setting['filtres'][$filtr_id]['yandex_market_category_list'],$categories_id);
						$offer .= '<categoryId>' . array_shift($res_cat) . '</categoryId>';
					} else {
						$offer .= '<categoryId>' . array_shift($categories_id) . '</categoryId>';
					}

					$offer .= '<url>' . $this->url->link('product/product', 'product_id=' . $keys['0']) . '</url>';


					if(!empty($setting['oldprice'])){

						$special = $this->model_extension_module_yandex_beru->getSpecial($keys['0']);

						if(!empty($special)){
							
							$special_price = $this->currency->format($this->tax->calculate($special['price'], $product['tax_class_id'], $this->config->get('config_tax')), $setting['currency'], $value = '', false);

							$offer .= '<price>' . round ($special_price , 2 , PHP_ROUND_HALF_UP ) . '</price>';

							$oldprice = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $setting['currency'], $value = '', false);

							$offer .= '<oldprice>' . round ($oldprice , 2 , PHP_ROUND_HALF_UP ) . '</oldprice>';

						} else {

							$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $setting['currency'], $value = '', false);

							$offer .= '<price>' . round ($price , 2 , PHP_ROUND_HALF_UP ) . '</price>';

						}
						
					} else {

						$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $setting['currency'], $value = '', false);

						$offer .= '<price>' . round ($price , 2 , PHP_ROUND_HALF_UP ) . '</price>';

					}

					$offer .= '<currencyId>' . $setting['currency'] . '</currencyId>';
                    
					if($setting['image'] == 'all'){

						$images = $this->model_extension_module_yandex_beru->getImages($keys['0']);

						$offer .= '<picture>' . HTTPS_SERVER . 'image/' .  $product['image'] . '</picture>';

						if(!empty($images)){
							foreach ($images as $image) {
								$offer .= '<picture>' . HTTPS_SERVER . 'image/' . $image['image'] . '</picture>';
							}
						}

					} elseif ($setting['image'] == 'main') {

						$images = $this->model_extension_module_yandex_beru->getImages($keys['0']);

						$offer .= '<picture>' . HTTPS_SERVER . 'image/' .  $product['image'] . '</picture>';
					}
					
                    $offer .= '<count>' . $product['quantity'] . '</count>';
                    
					$replace_yes_array = array("Да", "Yes", "1");
					$replace_not_array = array("Нет", "false", "0");

					foreach ($product_info[$keys['0']] as $tag => $tag_val) {
                    $tag_val['main'] = $string = str_replace('&', '&amp;', $tag_val['main']); 
						if($tag != 'bid'){
							if($tag == 'supplier'){
								$offer .= '<' . $tag . ' ogrn=' . $tag_val['main'] . '/>';
							} elseif($tag == 'param' or $tag == 'age') {
								if(!empty($tag_val['unit'])){
									$unit = ' unit="' . $tag_val['unit'] . '"';
								} else {
									$unit = '';
								}

								if(!empty($tag_val['name_param'])){
									$name_param = ' name="' . $tag_val['name_param'] . '" ';
								} else {
									$name_param = '';
								}
								$offer .= '<' . $tag . $name_param . $unit . '>' . $tag_val['main'] . '</' . $tag . '>';
							} elseif($tag == 'delivery' or $tag == 'pickup' or $tag == 'store' or $tag == 'manufacturer_warranty' or $tag == 'adult' or $tag == 'downloadable' or $tag == 'is_kids' or $tag == 'is_premiere'){
								$tag_val['main'] = str_replace($replace_yes_array, 'true', $tag_val['main']);
								$tag_val['main'] = str_replace($replace_not_array, 'false', $tag_val['main']);
								$offer .= '<' . $tag . '>' . $tag_val['main'] . '</' . $tag . '>';

							} elseif($tag == 'condition' and !empty($tag_val['child'])) {
								$offer .= '<' . $tag . ' type="' . $tag_val['child'][$keys['0']]['type']['main'] . '">';
								$offer .= '<reason>' . $tag_val['main'] . '</reason>';
								$offer .= '</' . $tag . '>';
							} elseif($tag == 'weight' and empty($tag_val['main'])) {
								$offer .= '<' . $tag . '>' . 1 . '</' . $tag . '>';
							} else {
								$offer .= '<' . $tag . '>' . $tag_val['main'] . '</' . $tag . '>';
							}
							
						}
					}
					foreach ($setting['filtres'][$filtr_id]['delivery_options'] as $delivery_options) {
						if(!empty($delivery_options['delivery_options_cost']) && !empty($delivery_options['delivery_options_days'])){
							$offer .= '<delivery-options>';
								$offer .= '<option cost="' . $delivery_options['delivery_options_cost'] . '" days="' . $delivery_options['delivery_options_days'] . '"';
								if(!empty($delivery_options['delivery_options_order_before'])){
									$offer .= ' order-before="' . $delivery_options['delivery_options_order_before'] .'"';
								}
								$offer .= '/>';
							$offer .= '</delivery-options>';
						}
					}

					if(!empty($setting['filtres'][$filtr_id]['pickup_options']['pickup_options_cost']) && !empty($setting['filtres'][$filtr_id]['pickup_options']['pickup_options_days'])){

						$offer .= '<pickup-options>';
							$offer .= '<option cost="' . $setting['filtres'][$filtr_id]['pickup_options']['pickup_options_cost'] . '" days="' . $setting['filtres'][$filtr_id]['pickup_options']['pickup_options_days'] . '"';

							if(!empty($setting['filtres'][$filtr_id]['pickup_options']['pickup_options_order_before'])){

								$offer .= ' order-before="' . $setting['filtres'][$filtr_id]['pickup_options']['pickup_options_order_before'] . '"';

							}
						
							$offer .= '/>';
						$offer .= '</pickup-options>';
					}

					$offer .= '</offer>';

				}

				$xml = '<?xml version="1.0" encoding="UTF-8"?>';
				$xml .= '<yml_catalog date="' .  date("Y-m-d H:i") . '">';
				$xml .= '<shop>';
				$xml .= '<name>' . $setting['short_name_shop'] . '</name>';
				$xml .= '<company>' . $setting['full_name_company'] . '</company>';
				$xml .= '<url>' . HTTPS_SERVER . '</url>';
				$xml .= '<platform>Opencart/Yandex.Marketplace</platform>';
				$xml .= '<version>' . VERSION . '/' . $version_module . '</version>';
				$xml .= '<currencies>';
				$xml .= '<currency id="' . $setting['currency'] . '" rate="1"/>';
				$xml .= '</currencies>';
				$xml .= '<categories>';
				
				foreach ($categories as $category) {
					if($category['parent_id'] != '0'){
						$xml .='<category id="' . $category['category_id'] . '" parentId="' . $category['parent_id'] . '">' . $category['name'] .'</category>';
					} else {
						$xml .='<category id="' . $category['category_id'] . '">' . $category['name'] .'</category>';
					}
				}
				
				$xml .= '</categories>';
				
				if(!empty($setting['enable_auto_discounts']) && $setting['enable_auto_discounts'] == 1){
					$xml .= '<enable_auto_discounts>true</enable_auto_discounts>';
				}
				
				$xml .= $delivery;
				$xml .= $pickup;
				$xml .= ' <offers>';
				$xml .= $offer;
				$xml .= ' </offers>';
				$xml .= '</shop>';
				$xml .= '</yml_catalog>';

				if($setting['cache_status'] == "1"){
					$file = fopen($_SERVER['DOCUMENT_ROOT'] . '/' . $setting['name_file'] . '.xml', 'w') or die("Не удалось открыть файл. Проверьте права доступа для записи файла");
					fwrite($file, print_r($xml, true));
					fclose($file);
				} else {
					$this->response->addHeader('Content-Type: application/xml; charset=utf-8');
					$this->response->setOutput($xml);
				}

			} else {

				echo 'Выгрузка с таким названием файла не существет! Пожалуйста проверьте настройки модуля';

			}

		} 

	}

	private function validateOffer($product, $setting, $product_info){

	$this->api = new yandex_beru();	

 		switch ($setting['type']) {
			case 'arbitrary':
				$fields = $this->getInfo()->getMainFieldsArbitrary();
				break;
			case 'simplified':
				$fields = $this->getInfo()->getMainFieldsSimplified();
				break;
			case 'medicine':
				$fields = $this->getInfo()->getMainFieldsMedicine();
				break;
			case 'books':
				$fields = $this->getInfo()->getMainFieldsBooks();
				break;
			case 'musicVideo':
				$fields = $this->getInfo()->getMainFieldsmusicVideo();
				break;
			case 'eventTickets':
				$fields = $this->getInfo()->getMainFieldsEventTickets();
				break;
			case 'tours':
				$fields = $this->getInfo()->getMainFieldsmusicTours();
				break;
			case 'alcohol':
				$fields = $this->getInfo()->getMainFieldsAlcohol();
				break;
			case 'audiobooks':
				$fields = $this->getInfo()->getMainFieldsAudiobooks();
				break;
			default:   //такого варианта быть не должно, значит сохранена неверня информация
				$type = ' ';
				break;	
		}	

		foreach ($fields as $key => $field) {
			if(!empty($field['required']) && $field['required'] == '1'){
				$product_id = array_keys($product_info);
				if(!in_array($key,  array_keys(current($product_info)))){
					$tag_none[$product_id['0']][] = $key; 
					if(!empty($setting['log']) and $setting['log'] == 1){
						$this->log($product_id['0'], $key);
					}
				} 
			}
		}

		if($product['price'] == '0'){
			$tag_none[$product_id['0']][] = 'price';
			if(!empty($setting['log']) and $setting['log'] == 1){
				$this->log($product_id['0'], 'price');
			}
		}

		if(!empty($tag_none)){
			return false;
		} else {
			return true;
		}

	}

	private function getInfo() {

		static $instance;

		if (!$instance) {
			$instance = $this->api->loadComponent('info');
		}

		return $instance;
	}
	
	private function log($product_id, $tag){

		$this->load->model('extension/module/yandex_beru');

		$product_name = $this->model_extension_module_yandex_beru->getProductName($product_id);

		$text = date('Y-m-d G:i:s') . "\r\n";


		if($tag == 'price'){
			$text .= "Пропущен товар: \r\n";
			$text .= "product_id=" . $product_id . " " . $product_name . "\r\n";
			$text .= "Причина: Нулевая цена товара \r\n";
		} else {
			$text .= "Пропущен товар: \r\n";
			$text .= "product_id=" . $product_id . " " . $product_name . "\r\n";
			$text .= "Причина: отсутствуют данные для обязательного тега " . $tag . "\r\n";
		}

		$file = fopen(DIR_LOGS . 'yandex_module_xml_' . $this->request->get['fid'] . '.log', 'a');
		fwrite($file, print_r($text, true));
		fclose($file);

	}

	private function clearLog(){
		$file = DIR_LOGS . 'yandex_module_xml_' . $this->request->get['fid'] . '.log';
		$handle = fopen($file, 'w+');
		fclose($handle);
	}
	
}
?>
