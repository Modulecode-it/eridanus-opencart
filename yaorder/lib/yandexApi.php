<?php
class yandexApi {
    private $oauth_id;
    private $access_token;
	
	public $last_error;

    private $to_remove = array('Москва и ', 'Санкт-Петербург и ', 'Югра', 'Кузбасс', 'Саха', 'Алания', 'Республика', 'республика', 'респ.', 'автономный округ', 'авт. округ', 'край', 'автономная область', 'авт. область', 'область', 'обл.', '()');


    function __construct($oauth_id, $access_token) {
        $this->oauth_id = $oauth_id;
        $this->access_token = $access_token;
    }

    public function findRegion($city, $region_name) {
        if ($region_name) {
            foreach ($this->to_remove as $word) {
                $region_name = str_replace($word, '', $region_name);
            }
            $region_name = trim($region_name, ' -');
        }

        $url = 'https://api.partner.market.yandex.ru/v2/regions.json?name='.rawurlencode($city);
        $regions = $this->apirequest($url, '', 'GET');
        if ($regions) {
            foreach ($regions['regions'] as $region) {
                $region = $this->parseRegion($region);
                if (count($regions['regions']) == 1 || !$region_name || $region['REPUBLIC']['name'] == $region_name) {
                    return $region;
                    /*
                    foreach ($region as $type => $reg) {
                        if (in_array($type, array('VILLAGE', 'TOWN', 'CITY'))) {
                            return $reg['id'];
                        }
                    }
                    */
                }
            }
        }
        \APILogger::log('Не найден регион Яндекс для "'.$city.', '.$region_name.'", ответ Яндекс:', 3, $regions);
        return false;
    }

    public function createOutlet($campaign, $data) {
        $url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$campaign.'/outlets.json';
        $ret = $this->apirequest($url, $data, 'POST');
        if ($ret['status'] == 'ERROR') {
            $message = 'Ошибка создания ПВЗ в кампании '.$campaign.': "'.$ret['errors'][0]['message'].'"';
            \APILogger::log($message, 3, $data);
			$this->last_error = $message;
            return false;
        }
        return $ret;
    }

    public function findOutlet($campaign, $code = false) {
        $url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$campaign.'/outlets.json'.($code ? '?shop_outlet_code='.$code : '');
        $ret = $this->apirequest($url, '', 'GET');
        /*
        if ($ret['status'] == 'ERROR') {
            \APILogger::log('Ошибка поиска ПВЗ по коду '.$code.' в кампании '.$campaign.': "'.$ret['errors'][0]['message'], 3);
            return false;
        }
        */
        return $ret;
    }

    public function deleteOutlet($campaign, $outletId) {
        $url = 'https://api.partner.market.yandex.ru/v2/campaigns/'.$campaign.'/outlets/'.$outletId.'.json';
        $ret = $this->apirequest($url, '', 'DELETE');
        if (isset($ret['errors'])) {
			$message = 'Ошибка удаления ПВЗ '.$outletId.' в кампании '.$campaign.': "'.$ret['errors'][0]['message'].'"';
            \APILogger::log($message, 3);
			$this->last_error = $message;
            return false;
        }
        return $ret;
    }

    private function parseRegion($region) {
        $ret = array();

        while ($region) {
            if ($region['type'] == 'REPUBLIC') {
                foreach ($this->to_remove as $word) {
                    $region['name'] = str_replace($word, '', $region['name']);
                }
                $region['name'] = trim($region['name'], ' -');
            }
            $ret[$region['type']] = array('id'=>$region['id'], 'name'=>$region['name']);

            if (isset($region['parent'])) {
                $region = $region['parent'];
            }
            else {
                $region = false;
            }
        }
        return $ret;
    }

    public function apirequest($url, $query = '', $method = 'PUT', $json = true) {
        if ($method == 'PUT') {
            // Clean up string
            $putString = stripslashes($query);
            // Put string into a temporary file
            $putData = tmpfile();
            // Write the string to the temporary file
            fwrite($putData, $putString);
            // Move back to the beginning of the file
            fseek($putData, 0);
        }

        $tuCurl = curl_init();
        curl_setopt($tuCurl, CURLOPT_URL, $url);
        curl_setopt($tuCurl, CURLOPT_PORT , 443);
        curl_setopt($tuCurl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json;charset=utf-8',
            'Authorization: OAuth oauth_token="'.$this->access_token.'", oauth_client_id="'.$this->oauth_id.'"'
        ));
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($tuCurl, CURLOPT_SSL_VERIFYHOST, false);
        if ($method == 'PUT') {
            curl_setopt($tuCurl, CURLOPT_PUT, 1);
            curl_setopt($tuCurl, CURLOPT_INFILE, $putData);
            curl_setopt($tuCurl, CURLOPT_INFILESIZE, strlen($putString));
        }
        elseif ($method == 'POST') {
            curl_setopt($tuCurl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($tuCurl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($tuCurl, CURLOPT_POST, 1);
            curl_setopt($tuCurl, CURLOPT_POSTFIELDS, $query);
        }
        else {
            curl_setopt($tuCurl, CURLOPT_CUSTOMREQUEST, $method);
        }

        $tuData = curl_exec($tuCurl);

        if(curl_errno($tuCurl)){
            $info = curl_getinfo($tuCurl);
            $message = 'Curl Error: '.curl_error($tuCurl).'. Took: ' . $info['total_time'] . 'sec. URL: ' . $info['url'];
			\APILogger::log($message, 2);
			$this->last_error = $message;
			
            curl_close($tuCurl);
            return false;
        } else {
            curl_close($tuCurl);
            if ($json) {
                $data = json_decode($tuData, true);
                return $data;
            }
            else {
                return $tuData;
            }
        }
    }
}