<?php
class cdekApi {
    private $client_id = 'epT5FMOa7IwjjlwTc1gUjO1GZDH1M1rE';
    private $client_secret = 'cYxOu9iAMZYQ1suEqfEvsHld4YQzjY0X';
    private $token = false;

    private function oauth() {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.edu.cdek.ru/v2/oauth/token?grant_type=client_credentials&client_id='.$this->client_id.'&client_secret='.$this->client_secret,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ));

        $response = curl_exec($curl);

        if ($error = curl_errno($curl)) {
            \APILogger::log('Ошибка доступа к API СДЭК: "' . curl_error($curl) . '"', 1);
            curl_close($curl);
            $this->token = false;
            return false;
        }
        curl_close($curl);

        try {
            $answer = json_decode($response, true);
            if (isset($answer['error'])) {
                \APILogger::log('Не удалось выполнить запрос к API СДЭК. Ответ: ', 2, $answer);
                $this->token = false;
                return false;
            }
            if (isset($answer['access_token'])) {
                $this->token = $answer['access_token'];
            }
            return $answer;
        } catch (Exception $e) {
            //Невалидный JSON
            \APILogger::log('API СДЭК вернул невалидный JSON:', 2, $response);
            $this->token = false;
            return false;
        }
    }

    /**
     * Рассчеёт доставки
     * @param array $data
       {
          "tariff_code":136,
          "from_location": {
            "code": 943
          },
          "to_location": {
            "code": 152
          },
          "packages": [
            {
              "weight": 4000
            }
          ]
       }
     *
     */
    public function calculateTariff($data) {
        $url = 'https://api.cdek.ru/v2/calculator/tariff';
        return $this->apiCall($url, 'POST', $data);
    }

    /**
     * Список ПВЗ
     * @param $params - фильтр ПВЗ
     * @return false|mixed
     */
    public function getPvz($params) {
        $url = 'https://api.cdek.ru/v2/deliverypoints?' . http_build_query($params);
        return $this->apiCall($url, 'GET');
    }

    private function apiCall($url, $method, $data = '') {
        if (!$this->token) {
            $auth = $this->oauth();
            if (!$auth) {
                return false;
            }
        }
        $curl = curl_init();

        $curl_params = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->token
            )
        );
        if ($method == 'POST' && $data) {
            $curl_params[CURLOPT_POSTFIELDS] = json_encode($data);
            $curl_params[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        curl_setopt_array($curl, $curl_params);

        $response = curl_exec($curl);

        if ($error = curl_errno($curl)) {
            \APILogger::log('Ошибка доступа к API СДЭК: "' . curl_error($curl) . '"', 1);
            curl_close($curl);
            return false;
        }
        curl_close($curl);

        try {
            $answer = json_decode($response, true);
            if (isset($answer['requests']) && isset($answer['requests'][0]['errors'])) {
                \APILogger::log('Не удалось выполнить запрос к API СДЭК. Ответ: ', 2, $answer);
                return false;
            }
            if (isset($answer['errors'])) {
                \APILogger::log('Не удалось выполнить запрос к API СДЭК. Ответ: ', 2, $answer);
                return false;
            }
            return $answer;
        } catch (Exception $e) {
            //Невалидный JSON
            \APILogger::log('API СДЭК вернул невалидный JSON:', 2, $response);
            return false;
        }
    }
}