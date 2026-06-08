<?php
include('../config.php');
include('config.php');
include('include/controller.php');

include('lib/yandexApi.php');
include('lib/APILogger.php');

$ctrl = new YaBuyController();

$ctrl->login();
