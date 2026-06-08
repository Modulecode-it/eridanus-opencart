<?php 
require_once 'postcalc_light_lib.php';
extract($arrPostcalcConfig, EXTR_PREFIX_ALL, 'postcalc_config');

$postcalc_from = (isset($_GET['postcalc_from'])) ? $_GET['postcalc_from'] : $postcalc_config_default_from;
$postcalc_to = (isset($_GET['postcalc_to'])) ? $_GET['postcalc_to'] : '190000';
$postcalc_weight = (isset($_GET['postcalc_weight'])) ? $_GET['postcalc_weight'] : 1000;
$postcalc_valuation = (isset($_GET['postcalc_valuation'])) ? $_GET['postcalc_valuation'] : 0;
$postcalc_country = (isset($_GET['postcalc_country'])) ? $_GET['postcalc_country'] : 'RU';

if ( isset($_GET['postcalc_from']) ) {
	$arrResponse = postcalc_request($postcalc_from, $postcalc_to, $postcalc_weight, $postcalc_valuation, $postcalc_country);
	
	if (is_array($arrResponse)) {
		echo serialize($arrResponse);
	}
}
?>