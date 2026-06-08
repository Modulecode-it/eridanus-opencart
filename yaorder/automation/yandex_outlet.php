<?php
include '../config.php';
include '../lib/APILogger.php';
include '../lib/cdekApi.php';
include '../lib/yandexApi.php';

$addDaysToDelivery = 0; //Добавлять столько дней к сроку доставки СДЭК
$cdekTariffCode = 368; //Код траифа СДЭК на основании которого будут рассчитываться сроки (см. https://confluence.cdek.ru/pages/viewpage.action?pageId=63345511 Приложение 2)

$token_file = dirname(__FILE__).'/../'.'t_'.APP_ID.'.token';
if (is_file($token_file)) {
    $access_token = file_get_contents($token_file);
}
if (!isset($access_token) || !$access_token) {
    echo 'oAuth токен авторизации Яндекс не найден!';
    exit;
}
if (!isset($argv[1]) || !isset($argv[2])) {
    echo "Импорт точек самовывоза из CSV-файла в Яндекс.Маркет.
Как запускать программу: 
php yandex_outlet.php 22905734 pvz_ru.csv pvz_fault_ru.csv 943
  22905734 - номер кампании в Яндекс.Маркет
  pvz_ru.csv - файл, откуда брать ПВЗ для импорта
  pvz_fault_ru.csv - файл, куда будут записываться ПВЗ, которые не удалось импортировать
  943 - СДЭК-код города отправителя (см. https://сдэк-калькулятор.рф/spisok-gorodov-dostavki/ код в URL города)
  ";
    exit;
}
if (!isset($argv[3])) {
    $argv[3] = 'pvz_fault_'.date('Y-m-d_H-i').'.csv';
}
$city_from = isset($argv[4]) ? $argv[4] : false;

$yaApi = new yandexApi(APP_ID, $access_token);
$cdekApi = new cdekApi();

define('CAMPAIGN_ID', $argv[1]); //Например 22905734

$fppvz = fopen($argv[2], 'r');
$header = fgets($fppvz);

$fpunf = fopen($argv[3], 'w+');
$fname = 'outlets.csv';
$k = 0;
while (is_file($fname)) {
    $k++;
    $fname = "outlets-".$k.".csv";
}
$fpoutlets = fopen($fname, 'w+');
$csvheader = array('code'=>'Код ПВЗ', 'type'=>'Тип', 'delivery'=>'Доставка в днях', 'city'=>'Город', 'region'=>'Регион', 'pvz'=>'Данные', 'cdek_id'=>'СДЭК-код города', 'reg_id'=>'Яндекс-код города', 'obl_id'=>'Яндекс-код области');
fputcsv($fpunf, $csvheader, '|');

echo "=== Начинаем импорт ========================================================\n";

$TARIFF_CACHE = array();
$i = 0;
$j = 0;
while (($row = fgetcsv($fppvz, 10000, "|")) !== FALSE) {
    $i++;
    $data = json_decode($row[5], true);
    $data['address']['regionId'] = intval($row[7]);
    $data['shopOutletCode'] = $row[0];
    $days = explode('-', $row[2]);
    $minDeliveryDays = $days[0];
    $maxDeliveryDays = (isset($days[1]) ? $days[1] : $days[0]);
    if (isset($row[6]) && $city_from) {
        if (!isset($TARIFF_CACHE[$row[6]])) {
            $tariff = $cdekApi->calculateTariff(array(
                "tariff_code" => $cdekTariffCode,
                "from_location" => array(
                    "code" => intval($city_from)
                ),
                "to_location" => array(
                    "code" => intval($row[6])
                ),
                "packages" => array(
                    "weight" => 1000
                )
            ));
            if ($tariff) {
                $TARIFF_CACHE[$row[6]] = $tariff;
                $minDeliveryDays = $tariff['period_min'] + $addDaysToDelivery;
                $maxDeliveryDays = $tariff['period_max'] + $addDaysToDelivery;

            }
        }
        else {
            $tariff = $TARIFF_CACHE[$row[6]];
            $minDeliveryDays = $tariff['period_min'] + $addDaysToDelivery;
            $maxDeliveryDays = $tariff['period_max'] + $addDaysToDelivery;
        }
    }

    $data['deliveryRules'][0]['minDeliveryDays'] = intval($minDeliveryDays);
    $data['deliveryRules'][0]['maxDeliveryDays'] = intval($maxDeliveryDays);

    $outlets = $yaApi->findOutlet(CAMPAIGN_ID, $data['shopOutletCode']);
    if ($outlets && !empty($outlets['outlets'])) {
        $outlet_id = $outlets['outlets'][0]['id'];
        $del = $yaApi->deleteOutlet(CAMPAIGN_ID, $outlet_id);
    }
    $ret = $yaApi->createOutlet(CAMPAIGN_ID, json_encode($data));

    if (!$ret) {
        fputcsv($fpunf, $row, '|');
    }
    else {
        $outlet = array(
            'id' => $data['shopOutletCode'],
            'zone' => $row[8],
            'city' => $row[3],
            'address_1' => $data['address']['street'].' '.$data['address']['number'],
            'address_2' => ($row[1] == 'POSTAMAT' ? 'Постамат' : 'ПВЗ').' СДЭК #'.substr($data['shopOutletCode'], 2),
            'price' => 400, //Яндекс назначил свою цену
            'days' => $minDeliveryDays.'-'.$maxDeliveryDays
        );
        fputcsv($fpoutlets, $outlet, ';');
        \APILogger::log(' Создан ПВЗ ID='.$ret['result']['id'].', код '.$data['shopOutletCode'], 3);
        $j++;
    }
    if ($i%10 == 0) {
        echo "Обработано $i, успешно $j\n";
    }
}
fclose($fppvz);
fclose($fpunf);
fclose($fpoutlets);