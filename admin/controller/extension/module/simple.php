<?php
$php_version = PHP_MAJOR_VERSION . PHP_MINOR_VERSION;
$ioncube_loaders = array(
    53 => 53,
    54 => 54,
    55 => 55,
    56 => 56,
    70 => 56,
    71 => 71,
    72 => 72,
    73 => 72,
    74 => 72,
);
$ioncube_loader = !empty($ioncube_loaders[$php_version]) ? $ioncube_loaders[$php_version] : 72;
require_once(DIR_SYSTEM . '/library/simple/php/simple_admin_' . $ioncube_loader . '.php');