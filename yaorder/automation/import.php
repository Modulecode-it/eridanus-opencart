<?php
include '../config.php';
include '../lib/APILogger.php';
include '../lib/cdekApi.php';
include '../lib/yandexApi.php';
echo "===========================================================\n";
$token_file = dirname(__FILE__).'/../'.'t_'.APP_ID.'.token';
if (is_file($token_file)) {
    $access_token = file_get_contents($token_file);
}
if (!$access_token) {
    echo 'oAuth токен авторизации Яндекс не найден!';
    exit;
}

$REGIONS_CACHE = array();

$yaApi = new yandexApi(APP_ID, $access_token);

$cdekApi = new cdekApi();
$pvzs = $cdekApi->getPvz(array('country_code'=>'RU'));
if (!$pvzs) {
    echo 'Не удалось получить список ПВЗ';
    exit;
}

$fppvz = fopen('pvz_ru.csv', 'w+');
$fpunf = fopen('pvz_unfound.csv', 'w+');
$csvheader = array('code'=>'Код ПВЗ', 'type'=>'Тип', 'delivery'=>'Доставка в днях', 'city'=>'Город', 'region'=>'Регион', 'pvz'=>'Данные', 'cdek_id'=>'СДЭК-код города', 'reg_id'=>'Яндекс-код города', 'obl_id'=>'Яндекс-код области');
fputcsv($fppvz, $csvheader, '|');


foreach ($pvzs as $pvz) {
    $to_replace = array('Чувашия'=>'Чувашская', 'Удмуртия'=>'Удмуртская', 'Санкт-Петербург'=>'Ленинградская', 'Москва'=>'Московская', 'Севастополь'=>'Крым');
    $city = $pvz['location']['city'];
    $city = str_replace('Химки Новые', 'Химки', $city);
    $region = $pvz['location']['region'];
    foreach ($to_replace as $from=>$to) {
        $region = str_replace($from, $to, $region);
    }
    $region = trim($region);
    if (isset($REGIONS_CACHE[$city]) && isset($REGIONS_CACHE[$city][$region])) {
        $reg_id = $REGIONS_CACHE[$city][$region]['reg_id'];
        $oblast_id = $REGIONS_CACHE[$city][$region]['oblast_id'];
    }
    else {
        $ya_region = $yaApi->findRegion($city, $region);
        $reg_id = false;
        $oblast_id = false;
        if ($ya_region && is_array($ya_region)) {
            foreach ($ya_region as $type => $reg) {
                if (in_array($type, array('VILLAGE', 'TOWN', 'CITY'))) {
                    $reg_id = $reg['id'];
                }
            }
            $oblast_id = $ya_region['REPUBLIC']['id'];
            $REGIONS_CACHE[$city][$region] = array('reg_id'=>$reg_id, 'oblast_id'=>$oblast_id);
        }
    }

    $address = explode(',', $pvz['location']['address']);
    $pvz_phone = $pvz['phones'][0]['number'];
    $phone = substr($pvz_phone, 0, 2).'('.substr($pvz_phone, 2, 3).')'.substr($pvz_phone, 5);
    $days_map = array(
        1=>'MONDAY',
        2=>'TUESDAY',
        3=>'WEDNESDAY',
        4=>'THURSDAY',
        5=>'FRIDAY',
        6=>'SATURDAY',
        7=>'SUNDAY'
    );
    $scheduleItems = array();
    foreach ($pvz['work_time_list'] as $item) {
        $times = explode('/', $item['time']);
        if (intval($times[1]) < intval($times[0])) {
            $times[0] = '23:59'; //Некоторые ПВЗ работают с 08:00 до 01:00. Яндекс считает это ошибкой.
        }
        $scheduleItems[] = array(
            'startDay'=> $days_map[$item['day']],
            'endDay'=> $days_map[$item['day']],
            'startTime'=> $times[0],
            'endTime'=> $times[1]
        );
    }
    $data = array(
         "name"=> "СДЭК: ".$pvz['name'],
          "type"=> "DEPOT",
          "coords"=> $pvz['location']['longitude'].','.$pvz['location']['latitude'],
          "isMain"=> false,
          "shopOutletCode"=> '51'.$pvz['code'],
          "visibility"=> "VISIBLE",
          "address"=> array(
            "regionId"=> $reg_id,
            "street"=> $address[0],
            "number"=> $address[1]
          ),
          "phones"=> array(
              $phone
          ),
          "workingSchedule"=> array(
              "workInHoliday"=> false,
              "scheduleItems"=> $scheduleItems
          ),
          "deliveryRules"=> array(
              array(
                  "cost"=> 400,
                  "minDeliveryDays"=> 2,
                  "maxDeliveryDays"=> 5,
                  "deliveryServiceId"=> 51,
                  "orderBefore"=> 12,
                  "priceFreePickup"=> 5000
              )
          ),
    );
    if (isset($pvz['email'])) {
        $data['emails'] = array($pvz['email']);
    }
    if ($reg_id) {
        fputcsv($fppvz, array('code'=>'51'.$pvz['code'], 'type'=>$pvz['type'], 'delivery'=>'2-5', 'city'=>$city, 'region'=>$region, 'pvz'=>json_encode($data), 'cdek_id'=>$pvz['location']['city_code'], 'reg_id'=>$reg_id, 'obl_id'=>$oblast_id), '|');
    }
    else {
        fputcsv($fpunf, array('code'=>'51'.$pvz['code'], 'type'=>$pvz['type'], 'delivery'=>'2-5', 'city'=>$city, 'region'=>$region, 'pvz'=>json_encode($data), 'cdek_id'=>$pvz['location']['city_code'], 'reg_id'=>'', 'obl_id'=>''), '|');
        print_r($pvz['location']);
    }
}
fclose($fppvz);
fclose($fpunf);
