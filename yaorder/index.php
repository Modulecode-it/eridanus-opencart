<?php
include('../config.php');
include('config.php');
include('include/controller.php');
include('include/view.php');
include('include/dbsview.php');
include('include/yaorder.php');

include('lib/yandexApi.php');
include('lib/APILogger.php');

$ctrl = new YaBuyController();
$data = $ctrl->index();
