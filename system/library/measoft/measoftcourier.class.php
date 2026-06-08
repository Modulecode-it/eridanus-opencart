<?php
/**
 * @copyright Copyright &copy; Компания MEAsoft, 2014
 */

class Measoft
{
    /**
     * Константы с модулями, которые используют данный класс
     */
    const BITRIX = 'bitrix';
    const INSALES = 'insales';
    const OPENCART = 'opencart';
    const PRESTASHOP = 'prestashop';
    const SHOPSCRIPT = 'shopscript';

    /**
     * Логин
     */
    public $login;

    /**
     * Пароль
     */
    public $password;

    /**
     * Код клиента
     */
    public $extra;

    /**
     * Проверка заказа
     */
    public $errors = array();

    /**
     * Последний ответ на запрос
     */
    public $lastResponse;
	
	/**
     * код языка
     */
    public $langCode;

    /**
     * Конструктор
     */
    public function __construct($login = null, $password = null, $extra = null, $lang = null)
    {
        if ($login && $password && $extra)
        {
            $this->login = $login;
            $this->password = $password;
            $this->extra = $extra;
			$this->langCode =  !empty($lang) ? substr($lang, 0, 2) : 'en';
			
						
        }
        else
        {
            die("Проверьте аутентификационные данные. Логин: '$login', пароль: '$password', код клиента: '$password'.");
        }
    }
	
		
	/**
     * Получение кода клиента
     */
    public  function getClientCode()
    {
        $level = 0;

        $xml = $this->startxml();
        $xml .= $this->makexmlnode('client', '', $level, '', 1);
        $xml .= $this->makexmlNode('auth', '', $level + 1, 'extra="'.$this->extra.'" login="'.$this->login.'" pass="'.$this->password.'"');
        $xml .= $this->makexmlnode('client', '', $level, '', 2);

        $response = simplexml_load_string($this->sendRequest($xml));

        if ($this->getRequestErrors($response)) {
            $code = trim((string) $response->code);   
			return $code;
        } else {
            return false;
        }
    }

    /**
     * Проверка дынных заказа
     */
    public function orderValidate($order = null, $items = null)
    {
        if (!isset($order['phone']))
        {
            $this->errors[] = 'не заполнен телефон получателя';
        }

        if (!isset($order['town']) || !$order['town'])
        {
            $this->errors[] = 'не заполнен город получателя';
        }

        if (!isset($order['address']) || !$order['address'])
        {
            $this->errors[] = 'не заполнен адрес получателя';
        }

        $date_parts = explode('-', $order['date']);
        if (!isset($order['date']) || !$order['date'])
        {
            $this->errors[] = 'не заполнена дата доставки товара';
        }
        else
        {
            if (count($date_parts) != 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0]))
            {
                $this->errors[] = 'неверный формат даты доставки';
            }
        }

        if (!isset($order['time_min']) || !$order['time_min'])
        {
            
        }
        else if (!preg_match('/^([0,1][0-9]|2[0-3]):([0-5][0-9])$/', $order['time_min']))
        {
            $this->errors[] = 'неверный формат минимального времени доставки: "' . $order['time_min'] . '"';
        }

        if (!isset($order['time_max']) || !$order['time_max'])
        {
            
        }
        else if (!preg_match('/^([0,1][0-9]|2[0-3]):([0-5][0-9])$/', $order['time_max']))
        {
            $this->errors[] = 'неверный формат максимального времени доставки: "' . $order['time_max'] . '"';
        }

        if ($order['time_min'] > $order['time_max'])
        {
            $this->errors[] = 'конечное время доставки должно быть больше начального';
        }

        if ($this->errors)
        {
            $this->errors = "\n - " . implode(";\n - ", $this->errors);
            return false;
        }

        return true;
    }

    /**
     * Получение списка срочности
     */
    public function getServiceList()
    {
        $level = 0;

        $xml = $this->startxml();
        $xml .= $this->makexmlnode('services', '', $level, '', 1);

        $level++;
        $xml .= $this->makexmlNode('auth', '', $level, 'extra="' . $this->extra . '"');
        $level--;

        $xml .= $this->makexmlnode('services', '', $level, '', 2);

        $response = simplexml_load_string($this->sendRequest($xml));

        if ($this->getRequestErrors($response))
        {
            return $response;
        }
        else
        {
            return false;
        }
    }
	
	public function preparePackages($packages){
		$result = [];
		
		if(is_array($packages) && $packages){
			foreach($packages as $package){
				
				$weight_delimeter = 1;
				if ($package['weight_class_id'] == 2) {
					$weight_delimeter = 1000;
				}
				
				$result[] = [				
					'mass' => (float)(($package['weight'] / $package['quantity']) / $weight_delimeter),
					'length' => (float)$package['length'],
					'width' => (float)$package['width'],
					'height' => (float)$package['height'],
					'quantity' => (int)$package['quantity']
				];
			}
		}
		
		return $result;		
	}
	
	public function fetchPaytype($code, $cash, $card, $none){
		if(empty($code)){
			return '';
		}
	
		if(!is_array($cash)){
			$cash = [];
		}
		
		if(!is_array($card)){
			$card = [];
		}
				
		if(!is_array($none)){
			$none = [];
		}
		
		if(in_array($code, $cash)){
			$paytype = 'CASH';
		}
		elseif(in_array($code, $card)){
			$paytype = 'CARD';
		}
		elseif(in_array($code, $none)){
			$paytype = 'NO';
		}
		
		return $paytype;
		
	}

    /**
     * Отправка запроса стоимости
     */
    public function calculatorRequest($order = null, $service_mode = '1')
    {
        $errorsText = array(
            0 => 'OK',
            1 => 'Неверный xml',
            2 => 'Широта не указана',
            3 => 'Долгота не указана',
            4 => 'Дата и время запроса не указаны',
            5 => 'Точность не указана',
            6 => 'Идентификатор телефона не указан',
            7 => 'Идентификатор телефона не найден',
            8 => 'Неверная широта',
            9 => 'Неверная долгота',
            10 => 'Неверная точность',
            11 => 'Заказы не найдены',
            12 => 'Неверные дата и время запроса',
            13 => 'Ошибка mysql',
            14 => 'Неизвестная функция',

            15 => 'Тариф не найден',
            18 => 'Город отправления не указан',
            19 => 'Город назначения не указан',
            20 => 'Неверная масса',
            21 => 'Город отправления не найден',
            22 => 'Город назначения не найден',
            23 => 'Масса не указана',
            24 => 'Логин не указан',
            25 => 'Ошибка авторизации',
            26 => 'Логин уже существует',
            27 => 'Клиент уже существует',
            28 => 'Адрес не указан',
            29 => 'Более не поддерживается',
            30 => 'Настройка sip не выполнена',
            31 => 'Телефон не указан',
            32 => 'Телефон курьера не указан',
            33 => 'Ошибка соединения',
            34 => 'Неверный номер',
            35 => 'Неверный номер',
            36 => 'Ошибка определения тарифа',
            37 => 'Ошибка определения тарифа',
            38 => 'Тариф не найден',
            39 => 'Тариф не найден',
        );

        if (!$order)
        {
            $this->errors = 'Не указаны параметры заказа';
            return false;
        }

        $order['mass'] = isset($order['mass']) && $order['mass'] ? $order['mass'] : '0.1';

        $level = 0;
        $xml = $this->startXML();

        $xml .= $this->makeXMLNode('calculator', '', $level, '', 1);

        $level++;
        $xml .= $this->makeXMLNode('auth', '', $level, 'extra="' . $this->extra . '" login="' . $this->login . '" pass="' . $this->password . '"');
		
		$level++;
        $xml .= $this->makeXMLNode('order', '', $level, '', 1);
		
		$level++;
        $xml .= $this->makeXMLNode('sender', '', $level, '', 1);
		
		$xml .= $this->makeXMLNode('town', (isset($order['townfrom']) ? $order['townfrom'] : ''), $level, '');		
		
		$level--;
		$xml .= $this->makeXMLNode('sender', '', $level, '', 2);
		
		$level++;
        $xml .= $this->makeXMLNode('receiver', '', $level, '', 1);
		
		$xml .= $this->makeXMLNode('town', (isset($order['townto']) ? $order['townto'] : ''), $level, '');		
		$xml .= $this->makeXMLNode('zipcode', (isset($order['zipcode']) ? $order['zipcode'] : ''), $level, '');		
		$xml .= $this->makeXMLNode('address', (isset($order['address']) ? $order['address'] : ''), $level, '');		
		$xml .= $this->makeXMLNode('pvz', (isset($order['pvzid']) ? $order['pvzid'] : ''), $level, '');		
		
		$level--;
		$xml .= $this->makeXMLNode('receiver', '', $level, '', 2);
		
		
		if(!empty($order['packages']) && $order['packages']){
			
			$level++;
			$xml .= $this->makeXMLNode('packages', '', $level, '', 1);
			
			foreach($order['packages'] as $package){
				$xml .= $this->makeXMLNode('package', '', $level, 'quantity="' . $package['quantity']. '" mass="' . $package['mass']. '" width="' . $package['width'] . '" length="' . $package['length'] . '" height="' . $package['height'] . '"');					
			}			
		
			$level--;
			$xml .= $this->makeXMLNode('packages', '', $level, '', 2);
			
		}
		
		
		$xml .= $this->makeXMLNode('service', $service_mode, $level, '');
		$xml .= $this->makeXMLNode('weight', (isset($order['mass']) ? $order['mass'] : ''), $level, '');
		$xml .= $this->makeXMLNode('paytype', (isset($order['paytype']) ? $order['paytype'] : ''), $level, '');
		
		
        $level--;
		$xml .= $this->makeXMLNode('order', '', $level, '', 2);
		
		$level--;
        $xml .= $this->makeXMLNode('calculator', '', $level, '', 2);

        $this->lastResponse = $result = simplexml_load_string($this->sendRequest($xml));

        if (!$result || !isset($result))
        {
            $this->errors[] = 'Ошибка сервиса';
            return false;
        }

        if ($attributes = $result->attributes())
        {
            if (isset($attributes['error']) && $attributes['error'] > 0)
            {
                $this->errors[] = isset($errorsText[(int)$attributes['error']]) ? $errorsText[(int)$attributes['error']] : (string)$response;
            }
        }

        if (!$this->errors)
        {
            if (isset($result->calc))
            {
                if ($attributes = $result
                    ->calc
                    ->attributes())
                {
                    if (isset($attributes['price']))
                    {
                        return $result->calc;
                    }
                }
            }
            else
            {
                $this->errors[] = 'Ошибка передачи данных';
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Отправка заказа.
     */
    public function orderRequest($order = null, $items = null)
    {
        if (!$order || !$items)
        {
            $this->errors = 'Пустой массив заказа';
            return false;
        }

        $response = simplexml_load_string($this->sendRequest($this->createXML($order, $items)));
        if ($this->getRequestErrors($response))
        {
            if (isset($response->createorder[0]['orderno']))
            {
                return (string)$response->createorder[0]['orderno'];
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Статус заказа
     */
    public function statusRequest($orderNumber = null)
    {
        $statuses = array(
            'NEW' => 'Новый',
            'PICKUP' => 'Забран у отправителя',
            'ACCEPTED' => 'Получен складом',
            'INVENTORY' => 'Инвентаризация',
            'DEPARTURING' => 'Планируется отправка',
            'DEPARTURE' => 'Отправлено со склада',
            'DELIVERY' => 'Выдан курьеру на доставку',
            'COURIERDELIVERED' => 'Доставлен (предварительно)',
            'COMPLETE' => 'Доставлен',
            'PARTIALLY' => 'Доставлен частично',
            'COURIERRETURN' => 'Курьер вернул на склад',
            'CANCELED' => 'Не доставлен (Возврат/Отмена)',
            'RETURNING' => 'Планируется возврат',
            'RETURNED' => 'Возвращен',
            'CONFIRM' => 'Согласована доставка',
            'DATECHANGE' => 'Перенос',
            'NEWPICKUP' => 'Создан забор',
            'UNCONFIRM' => 'Не удалось согласовать доставку',
            'PICKUPREADY' => 'Готов к выдаче',
            'AWAITING_SYNC' => 'Ожидание синхронизации',
        );

        if (!$orderNumber)
        {
            $this->errors = 'Не указан номер заказа';
            return false;
        }

        $level = 0;
        $xml = $this->startxml();
        $xml .= $this->makexmlnode('statusreq', '', $level, '', 1);

        $level++;
        $xml .= $this->makeXMLNode('auth', '', $level, 'extra="' . $this->extra . '" login="' . $this->login . '" pass="' . $this->password . '"');
        $xml .= $this->makexmlnode('orderno', $orderNumber, $level, '');
        $level--;

        $xml .= $this->makexmlnode('statusreq', '', $level, '', 2);

        $response = simplexml_load_string($this->sendRequest($xml) , null, LIBXML_NOCDATA);
        if ($this->getRequestErrors($response))
        {
            $status = trim((string)$response->order[0]
                ->status);
            if (isset($statuses[$status]))
            {
                return $statuses[$status];
            }
            else
            {
                return 'Неопределенный статус';
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Получение всех заказов, которые изменились со времени последнего запроса.
     */
    public function changedOrdersRequest()
    {
        $level = 0;

        $xml = $this->startxml();
        $xml .= $this->makexmlnode('statusreq', '', $level, '', 1);

        $level++;
        $xml .= $this->makexmlNode('auth', '', $level, 'extra="' . $this->extra . '" login="' . $this->login . '" pass="' . $this->password . '"');
        $xml .= $this->makexmlnode('changes', 'ONLY_LAST', $level, '');
        $xml .= $this->makexmlnode('quickstatus', 'NO', $level, '');
        $level--;

        $xml .= $this->makexmlnode('statusreq', '', $level, '', 2);

        $response = simplexml_load_string($this->sendRequest($xml));

        //$this->commitLastStatusRequest()
        if ($this->getRequestErrors($response))
        {
            $status = trim((string)$response->order[0]
                ->status);
            if (isset($statuses[$status]))
            {
                return $statuses[$status];
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Подтверждение получения измененных заказов.
     */
    protected function commitLastStatusRequest()
    {
        $level = 0;

        $xml = $this->startxml();
        $xml .= $this->makexmlnode('commitlaststatus', '', $level, '', 1);
        $xml .= $this->makexmlNode('auth', '', $level + 1, 'extra="' . $this->extra . '" login="' . $this->login . '" pass="' . $this->password . '"');
        $xml .= $this->makexmlnode('commitlaststatus', '', $level, '', 2);

        $response = simplexml_load_string($this->sendRequest($xml));

        if ($this->getRequestErrors($response))
        {
            $status = trim((string)$response->order[0]
                ->status);
            if (isset($statuses[$status]))
            {
                return $statuses[$status];
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Выполнение POST запроса
     */
    public function sendRequest($content)
    {
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: text/xml',
                'charset' => 'utf-8',
                'content' => $content
            )
        );

        $context = stream_context_create($opts);
        if (!$contents = @file_get_contents('https://home.courierexe.ru/api/', false, $context))
        {
            if (!$curl = curl_init())
            {
                $this->errors = 'Возможно не поддерживается передача по HTTPS. Проверьте наличие open_ssl';
                return false;
            }
            curl_setopt($curl, CURLOPT_URL, 'https://home.courierexe.ru/api/');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-type: text/xml; charset=utf-8'
            ));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $contents = curl_exec($curl);
            curl_close($curl);
        }

        if (!$contents)
        {
            $this->errors = 'Ошибка сервиса';
            return false;
        }

        $this->lastResponse = simplexml_load_string($contents);

        return $contents;
    }

    private function _getConcatError($attributes)
    {
        $attribute = 'errormsg';
        $lang = $this->langCode;
        $lang != 'en' ?: $lang = '';

        return (isset($attributes[$attribute . $lang]) && !empty($attributes[$attribute . $lang])) ? $attributes[$attribute . $lang] : '';

    }

    public function getLangError($attributes)
    {

        // Если нет текста ошибки с языком, то ищем локаль
        if (!$this->_getConcatError($attributes)) {

            // Берем локаль
            $this->langCode = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

            // Если нет текста ошибки с языком, то пытаемся дать русский или английский
            if (!$this->_getConcatError($attributes)) {

                //  Если знают русский
                if (in_array($lang, ['ru', 'be', 'uk', 'uz', 'tg', 'kk', 'ab', 'ky', 'lv'])) $this->langCode = 'ru';
                else $this->langCode = 'en';

            }
        }

        return $this->_getConcatError($attributes);
    }
	
	

    /**
     * Проверяем ошибки возвращаемые АПИ
     */
    public function getRequestErrors($response)
    {
        $errorsText = array(
            'Ошибок нет',
            'Ошибка авторизации',
            'Отправлен пустой запрос',
            'Некорректно указана сумма заказа',
            'Некорректный общий вес заказа',
            'Не найден город получатель',
            'Не найден город отправитель',
            'Не заполнен адрес получателя',
            'Не заполнен телефон получателя',
            'Не заполнено контактное имя получателя',
            'Не заполнено название компании получателя',
            'Некорректная сумма объявленной ценности',
            'Артикул не найден',
            'Не заполнено название компании отправителя',
            'Не заполнено контактное имя отправителя',
            'Не заполнен телефон отправителя',
            'Не заполнен адрес отправителя',
            'Заказ с таким номером уже существует'
        );
        $this->errors = array();

        if (!$response || !isset($response))
        {
            return false;
        }

        if ($attributes = $response->attributes())
        {
            if (isset($attributes['count']) && $attributes['count'] == 0)
            {
                $this->errors[] = 'Заказ с таким номером не найден';
            }
            if (isset($attributes['error']) && $attributes['error'] > 0)
            {
                $this->errors[] = isset($errorsText[(int)$attributes['error']]) ? $errorsText[(int)$attributes['error']] : (string)$response;
            }
        }

        if (isset($response->createorder))
        {
            foreach ($response->createorder as $order)
            {
                if ($attributes = $order->attributes())
                {
                    if (isset($attributes['error']) && $attributes['error'] > 0)
                    {
                        $this->errors[] = isset($errorsText[(int)$attributes['error']]) ? $errorsText[(int)$attributes['error']] : $this->getLangError($attributes);
                    }
                }
            }
        }

        if (isset($response->error))
        {
            foreach ($response->error as $error)
            {
                if ($attributes = $error->attributes())
                {
                    if (isset($attributes['error']) && $attributes['error'] > 0)
                    {
                        $this->errors[] = isset($errorsText[(int)$attributes['error']]) ? $errorsText[(int)$attributes['error']] : $this->getLangError($attributes);
                    }
                }
                else
                {
                    $this->errors[] = 'Ошибка синтаксиса XML: ' . (string)$error;
                }
            }
        }

        if ($this->errors)
        {
            $this->errors = implode(';<br>', $this->errors);
            return false;
        }

        return true;
    }

    /**
     * Подготавливаем данные для запроса
     */
    public function createXML($order, $items)
    {
        //        $order = $this->stripTagsHTMLRecursive($order);
        //        $items = $this->stripTagsHTMLRecursive($items);
        //Обработка вложений
        $resultItems = '';
        $itemInshprice = 0;
        $itemWeight = 0;
        if (isset($items) && $items)
        {
            $resultItems .= $this->makeXMLNode('items', '', 3, '', 1);
            foreach ($items as $item)
            {

                if ($item['name'] == 'Доставка')
                {
                    continue;
                }
                //Расчёт стоимости
                if ($item['name'] !== 'Доставка' && $item['name'] !== 'Скидка' && $item['name'] !== 'Наценка')
                {
                    $itemInshprice += $item['retprice'] * $item['quantity'];
                }

                //Расчёт массы
                if ($item['name'] !== 'Доставка' && $item['name'] !== 'Скидка' && $item['name'] !== 'Наценка')
                {
                    $itemWeight += $item['mass'] * $item['quantity'];
                }

                $resultItems .= $this->makeXMLNode('item', $item['name'], 4, 'quantity="' . $item['quantity'] . '"' . ' mass="' . $item['mass'] . '"'. ' width="' . $item['width'] . '"'. ' length="' . $item['length'] . '"'. ' height="' . $item['height'] . '"' . ' retprice="' . $item['retprice'] . '"' . ' barcode="' . $this->stripTagsHTML($item['barcode']) . '"' . ($item['article'] ? ' article="' . $this->stripTagsHTML($item['article']) . '"' : ''));
            }
            $resultItems .= $this->makeXMLNode('items', '', 3, '', 2);
        }

        $level = 0;
        $result = $this->startXML();

        $result .= $this->makeXMLNode('neworder', '', $level, '', 1);

        $level++;
        if (isset($order['sender']) and is_array($order['sender']))
        {
            $result .= $this->makeXMLNode('sender', '', $level, 'type="4" module="' . $order['sender']['module'] . '" module_version="' . $order['sender']['module_version'] . '" cms_version="' . $order['sender']['cms_version'] . '"');
        }
        $result .= $this->makeXMLNode('auth', '', $level, 'extra="' . $this->extra . '" login="' . $this->login . '" pass="' . $this->password . '"');
        $result .= $this->makeXMLNode('order', '', $level, 'orderno="' . $order['orderno'] . '"', 1);

        $level++;
        $result .= $this->makeXMLNode('barcode', $order['barcode'], $level);

        $result .= $this->makeXMLNode('receiver', '', $level, '', 1);

        $level++;
        if (isset($order['company'])) $result .= $this->makeXMLNode('company', $order['company'], $level);
        if (isset($order['person'])) $result .= $this->makeXMLNode('person', $order['person'], $level);
        $result .= $this->makeXMLNode('phone', $order['phone'], $level);
        $result .= $this->makeXMLNode('zipcode', (isset($order['zipcode']) ? $order['zipcode'] : '') , $level);
        $result .= $this->makeXMLNode('town', $order['town'], $level);
        $result .= $this->makeXMLNode('date', $order['date'], $level);
        $result .= $this->makeXMLNode('time_min', $order['time_min'], $level);
        $result .= $this->makeXMLNode('time_max', $order['time_max'], $level);

        if (isset($order['pvz_id']))
        {
            $result .= $this->makeXMLNode('pvz', $order['pvz_id'], $level);
        } elseif (isset($order['pvz_name']))
        {
            $result .= $this->makeXMLNode('pvz', $order['pvz_name'], $level);
        }
        $result .= $this->makeXMLNode('address', $order['address'], $level);

        $level--;

        $result .= $this->makeXMLNode('receiver', '', $level, '', 2);

        $result .= $this->makeXMLNode('weight', ($order['weight'] ? $order['weight'] : $itemWeight) , $level);
        $result .= $this->makeXMLNode('quantity', ($order['quantity'] ? $order['quantity'] : 1) , $level);
        $result .= $this->makeXMLNode('paytype', (isset($order['paytype'])) ? $order['paytype'] : 'CASH', $level);
        $result .= $this->makeXMLNode('service', 1, $level);
        $result .= $this->makeXMLNode('price', $order['price'], $level);
        $result .= $this->makeXMLNode('discount', (isset($order['discount'])) ? $order['discount'] : '0', $level);
        $result .= $this->makeXMLNode('inshprice', ($order['inshprice'] ? $order['inshprice'] : $itemInshprice) , $level);
        $result .= $this->makeXMLNode('deliveryprice', $order['deliveryprice'], $level);
        $result .= $this->makeXMLNode('enclosure', $order['enclosure'], $level);
        $result .= $this->makeXMLNode('instruction', $order['instruction'], $level);

        $result .= $resultItems;

        $level--;
        $result .= $this->makeXMLNode('order', '', $level, '', 2);

        $level--;
        $result .= $this->makeXMLNode('neworder', '', $level, '', 2);

        return $result;
    }

    public function startXML()
    {
        return ('<?xml version="1.0" encoding="UTF-8"?>');
    }

    public function stripTagsHTML($s)
    {
        $s = str_replace('&', '&amp;', $s);
        $s = str_replace("'", '&apos;', $s);
        $s = str_replace('<', '&lt;', $s);
        $s = str_replace('>', '&gt;', $s);
        $s = str_replace('"', '&quot;', $s);

        return $s;
    }

    public function stripTagsHTMLRecursive($element)
    {
        if (is_array($element))
        {
            $return = array();
            foreach ($element as $key => $value)
            {
                $return[$key] = $this->stripTagsHTMLRecursive($value);
            }
            return $return;
        }
        else
        {
            return $this->stripTagsHTML($element);
        }
    }

    public function makeXMLNode($nodename, $nodetext, $level = 0, $attr = '', $justopen = 0)
    {
        $result = "\r\n";
        for ($i = 0;$i < $level;$i++) $result .= '    ';

        $emptytag = ($nodetext === '') && ($justopen == 0);
        $nodetext = $this->stripTagsHTML($nodetext);

        if ($justopen < 2) $result .= '<' . $nodename . ($attr ? $attr = ' ' . $attr : '') . ($emptytag ? ' /' : '') . '>' . $nodetext;
        if ((($justopen == 0) && !$emptytag) || ($justopen == 2)) $result .= '</' . $nodename . '>';

        return ($result);
    }

    /**
     *
     */
    public function clearErrors()
    {
        $this->errors = array();
    }

    /**
     * Возвращает отформатированный номер заказа с префиксом и учетом фиксированной длины.
     */
    public static function orderNoTransform($orderNo, $prefix = '', $fixLength = 0)
    {
        return $prefix . (!$fixLength ? $orderNo : str_pad($orderNo, $fixLength, 0, STR_PAD_LEFT));
    }

    /**
     * Получить код населенного пункта по коду ПВЗ
     */

    public function getCityCode($pvzid)
	{
		$code = 0;
		$level = 0;
		$xml = $this->startXML();

		$xml .= $this->makeXMLNode('pvzlist', '', $level, '', 1);

		$level++;
		$xml .= $this->makeXMLNode('auth', '', $level, 'extra="' . $this->extra . '" login="' . $this->login . '" pass="' . $this->password . '"');
		$xml .= $this->makeXMLNode('code', (int)$pvzid, $level);
		$level--;

		$xml .= $this->makeXMLNode('pvzlist', '', $level, '', 2);

		$result = simplexml_load_string($this->sendRequest($xml));

		if (isset($result->pvz) && isset($result->pvz->town)) {
			$attrs = $result->pvz->town->attributes();
			$code = $attrs->code->__toString();
		}

		return $code;
    }    
    
    public function getTownList($fields=array()) {
        $level = 0;
        $xml = $this->startXML();

        $xml .= $this->makeXMLNode('townlist', '', $level, '', 1);
        $level++;
        $xml .= $this->makeXMLNode('codesearch', '', $level, '', 1);

        $level++;
        foreach ($fields as $field=>$value) {
            $xml .= $this->makeXMLNode($field, $value, $level);
        }
        $level--;

        $xml .= $this->makeXMLNode('codesearch', '', $level, '', 2);
        $level--;
        $xml .= $this->makeXMLNode('townlist', '', $level, '', 2);

        $this->lastResponse = $result = simplexml_load_string($this->sendRequest($xml));

        if (!$result || !isset($result)) {
            $this->errors[] = 'Ошибка сервиса';
            return false;
        }

        $towns = array();
        if (!empty($result->town)) foreach ($result->town as $town) {
            $towns[] = array(
                'code' => (int)$town->code,
                'name' => (string)$town->name,
            );
        }

        return $towns;
    }
    
    public function getPVZ($pvzid)
	{
		$code = 0;
		$level = 0;
		$xml = $this->startXML();

		$xml .= $this->makeXMLNode('pvzlist', '', $level, '', 1);

		$level++;
		$xml .= $this->makeXMLNode('auth', '', $level, 'extra="' . $this->extra . '" login="' . $this->login . '" pass="' . $this->password . '"');
		$xml .= $this->makeXMLNode('code', (int)$pvzid, $level);
		$level--;

		$xml .= $this->makeXMLNode('pvzlist', '', $level, '', 2);

		$result = simplexml_load_string($this->sendRequest($xml));

    	return $result;
	}       
}
