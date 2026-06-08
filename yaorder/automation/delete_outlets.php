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
    echo "Удаление старых точек самовывоза на Яндекс.Маркете.
Как запускать программу: 
php delete_outlets.php 22905734 51
  22905734 - номер кампании в Яндекс.Маркет
  51 - будут удалены только те ПВЗ, код которых начинается на эти цифры 
  ";
    exit;
}
define('CAMPAIGN_ID', $argv[1]); //Например 22905734
$prefix = (isset($argv[2]) ? $argv[2] : false);
$yaApi = new yandexApi(APP_ID, $access_token);

$i = 0;
$j = 0;
$ret = $yaApi->findOutlet(CAMPAIGN_ID);
$outlets = $ret['outlets'];
$continue = true;
while ($outlets && !empty($outlets) && $continue) {
    $continue = false;
    foreach ($outlets as $outlet) {
        $i++;
        if ($prefix && strpos($outlet['shopOutletCode'], $prefix) !== 0) {
            continue;
        }
        $del = $yaApi->deleteOutlet(CAMPAIGN_ID, $outlet['id']);
        echo "Удаляем $i точку самовывоза ".$outlet['shopOutletCode']."\n";
        $continue = true;
        $j++;
    }
    $ret = $yaApi->findOutlet(CAMPAIGN_ID);
    $outlets = $ret['outlets'];
}
echo "Обработано $i точек самовывоза, удалено $j\n";
