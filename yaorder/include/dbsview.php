<?php
class YaBuyDBSView {
	protected $yaorder;
	protected $yaorder_id;

	function __construct($yaorder_id) {
		$this->yaorder_id = $yaorder_id;
	}

	public function render($data) {
		$this->yaorder = new YaOrder();
		$vdata = $this->getViewData($data);
		return $this->showForm($vdata);
	}

	public function error($data) {
		$this->showError($data['error']);
	}

	public function payable($company_id) {
		$this->showPayable($company_id);
	}

	protected function getViewData($data) {
		$ret = array();

		$ret['id'] = $data['order']['id'];

		$ret['status'] = $data['order']['status'];

		$ret['status_text'] = $this->yaorder->getDBSStatusName($ret['status']);
		
		if (isset($data['order']['substatus'])) {
			$ret['substatus'] = $data['order']['substatus'];
			$ret['substatus_text'] = $this->yaorder->ORDER_DBSSUBSTATUSES[$ret['substatus']];
		}
		else {
			$ret['substatus'] = '';
			$ret['substatus_text'] = '';
		}

		$ret['fake'] = $data['order']['fake'];

		$ret['payment'] = $data['order']['paymentType'];
		$ret['payment_text'] = $this->yaorder->PAYMENT_TYPES[$data['order']['paymentType']];
		$ret['payment_method_text'] = $this->yaorder->PAYMENT_METHODS[$data['order']['paymentMethod']];

		$ret['next'] = $this->yaorder->getDBSStatusNext($ret['status']);

		$ret['substatus_next'] = $this->yaorder->getDBSSubstatusNext($ret['status']);
		
		$ret['delivery'] = $data['order']['delivery'];
		$ret['delivery']['type_text'] = $this->yaorder->DELIVERY_TYPES[$data['order']['delivery']['type']];
		
		$ret['delivery_editable'] = $this->yaorder->isDeliveryEditable($ret['status']);
		$ret['delivery_types'] = $this->yaorder->DELIVERY_TYPES;

		return $ret;
	}

	protected function showError($data) {
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<base href="<?=HTTPS_SERVER?>yaorder/" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>

<div class="warning">
<b>Яндекс вернул ошибку: "<?=$data['message']?>"</b>
</div>

<div style="float:right;">
<a href="<?=HTTPS_SERVER?>yaorder/index.php?ya_order=<?=$this->yaorder_id?>" style="float: right;">обновить</a>
</div>

<table class="form">
<tbody>
	<tr>
	<td>Заказ №</td>
	<td><?=abs($this->yaorder_id)?></td>
	</tr>
</tbody>
</table>
<!--
<br />
<a href="<?=HTTPS_SERVER?>yaorder/index.php?action=outlets&ya_order=-<?=$this->yaorder_id?>" style="float: right;">тчк.продаж</a>
<?php $this->getStatCounters(); ?>
-->
</body>
</html>
<?php
	}

	protected function showForm($data) {
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<base href="<?=HTTPS_SERVER?>yaorder/" />
	<link rel="stylesheet" type="text/css" href="style.css" />
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
</head>
<body>
<div style="float:right;">
<a href="<?=HTTPS_SERVER?>yaorder/index.php?ya_order=<?=$this->yaorder_id?>" style="float: right;">обновить</a>
</div>

<table class="form">
<tbody>
	<tr>
	<td>Заказ №</td>
	<td><?=$data['id']?><?=($data['fake'] ? ' <span style="color: red;">тестовый заказ</span>' : '')?></td>
	</tr>
	<tr class="hide_on_edit">
	<td>Статус</td>
	<td><?=$data['status_text']?><?=($data['substatus_text'] ? ' ('.$data['substatus_text'].')' : '')?>
	<?php if ($data['next']) { ?>
		<span style="float: right;"><a href="#" id="edit_status_link">Сменить статус</a></span>
	<?php } ?>
	</td>
	</tr>
	<tr class="hide_on_edit">
	<td>Способ оплаты</td>
	<td><?=$data['payment_text']?><?=($data['payment_method_text'] ? ' ('.$data['payment_method_text'].')' : '')?></td>
	</tr>
	
	<tr class="hide_on_edit">
	<td>Способ доставки</td>
	<td><?=$data['delivery']['type_text']?><?=($data['delivery']['serviceName'] ? ' ('.$data['delivery']['serviceName'].')' : '')?>
	<?php if ($data['delivery_editable']) { ?>
		<span style="float: right;"><a href="#" id="edit_delivery_link">Сменить доставку</a></span>
	<?php } ?>
	</td>
	</tr>
	<tr class="hide_on_edit">
	<td>Стоимость доставки</td>
	<td><?=$data['delivery']['price']?> руб.</td>
	</tr>
	<tr class="hide_on_edit">
	<td>Срок доставки</td>
	<td>с <?=$data['delivery']['dates']['fromDate']?> по <?=$data['delivery']['dates']['toDate']?>
	<?php if ($data['next']) { ?>
		<span style="float: right;"> <a id="move_deliverydate_link">Сдвинуть срок доставки</a> </span>
	<?php } ?>
	</td>
	</tr>
<?php if (isset($data['delivery']['shipments']) && isset($data['delivery']['shipments'][0])) { ?>
	<tr class="hide_on_edit">
	<td>Трек-номер</td>
	<td><?=(isset($data['delivery']['tracks']) && isset($data['delivery']['tracks'][0]['trackCode']) ? $data['delivery']['tracks'][0]['trackCode'] : '')?>
	<span id="delivery_service_name"></span>
	<?php if ($data['next']) { ?>
		<span style="float: right;"> <a id="trackсode_link">Трек-номер</a> </span>
	<?php } ?>
	</td>
	</tr>
<?php } ?>

</tbody>
</table>

<?php if ($data['next']) { ?>
<form action="<?=HTTPS_SERVER?>yaorder/index.php" method="GET" id="edit_status_form" style="display: none;">
	<input type="hidden" name="action" value="dbsstatus" />
	<input type="hidden" name="ya_order" value="<?=$this->yaorder_id?>" />
	<table class="form">
	<tr>
	<td width="35%"><label for="status">Сменить статус:</label></td>
	<td><select name="status" id="status_sel" style="width: 100%;">
		<?php foreach ($data['next'] as $key=>$text) { ?>
		  <option value="<?=$key?>"><?=$text?></option>
		<?php } ?>
		</select>
	</td>
	</tr>

	<?php if ($data['substatus_next']) { ?>
	<tr id="substatus_tr">
	<td><label for="substatus">Причина:</label></td>
	<td><select name="substatus" id="substatus_sel" style="width: 100%;">
		<?php foreach ($data['substatus_next'] as $key=>$text) { ?>
		  <option value="<?=$key?>"><?=$text?></option>
		<?php } ?>
		</select>
	</td>
	</tr>
	<?php } ?>
	<tr>
	<td colspan="2" align="right">
		<input type="reset" name="cancel" value="Отменить" id="cancel_status_form" class="button" />
		<input type="submit" name="save" value="Применить" class="button" />
	</td>
	</tr>
	</table>
</form>

<form action="<?=HTTPS_SERVER?>yaorder/index.php" method="GET" id="edit_delivery_form" style="display: none;">
	<input type="hidden" name="action" value="delivery" />
	<input type="hidden" name="ya_order" value="<?=$this->yaorder_id?>" />
	<table class="form">
	<tr>
	<td width="35%"><label for="deliverytype_sel">Тип доставки:</label></td>
	<td><select name="type" id="deliverytype_sel" style="width: 100%;">
		<?php foreach ($data['delivery_types'] as $key=>$text) { ?>
		  <option value="<?=$key?>"<?=($data['delivery']['type']==$key ? ' selected' : '')?>><?=$text?></option>
		<?php } ?>
		</select>
	</td>
	</tr>
	<tr id="servicename_tr">
	<td width="35%"><label for="serviceName">Способ доставки:</label></td>
	<td><input type="text" name="serviceName" value="<?=htmlspecialchars($data['delivery']['serviceName'])?>" id="service_name_inp" style="width: 98%;" maxlength="50" />
	</td>
	</tr>
	<tr  id="outlet_tr">
	<td width="35%"><label for="outlet_id_inp">Пункт самовывоза:</label></td>
	<td><input type="text" name="outletId" value="<?=(isset($data['delivery']['outletId']) ? $data['delivery']['outletId'] : '')?>" id="outlet_id_inp" size="6" />
	<a href="http://partner.market.yandex.ru/offshop-list.xml?id=<?=$this->yaorder_id?>" target="_blank">смотреть на Маркете</a>
	</td>
	</tr>

	<td width="35%"><label for="price">Стоимость доставки:</label></td>
	<td><input type="text" name="price" value="<?=htmlspecialchars($data['delivery']['price'])?>"<?=($data['payment'] == 'PREPAID' ? ' disabled' : '')?> size="6" />руб.
	</td>
	</tr>
	
	<tr>
	<td colspan="2" align="right">
		<input type="reset" name="cancel" value="Отменить" id="cancel_delivery_form" class="button" />
		<input type="submit" name="save" value="Применить" class="button" />
	</td>
	</tr>
	</table>
</form>

<form action="<?=HTTPS_SERVER?>yaorder/index.php" method="GET" id="move_deliverydate_form" style="display: none;">
	<input type="hidden" name="action" value="deliverydate" />
	<input type="hidden" name="ya_order" value="<?=$this->yaorder_id?>" />
	<input type="hidden" name="shipmentId" value="<?=$shipmentId?>" />	
	<table class="form">
	<tr>
	<td width="35%"><label for="todate_cal">Новая дата доставки:</label></td>
	<td>
		<input type="text" name="todate" value="<?=$data['delivery']['dates']['toDate']?>" size="12" />
		<span class="help">Дату можно перенести максимум на три дня. Обновлять дату для одного заказа можно не более трех раз.</span>
	</td>
	</tr>
	<td width="35%"><label for="todate_reason_sel">Причина смены даты:</label></td>
	<td><select name="reason" id="todate_reason_sel" style="width: 100%;">
		  <option value="PARTNER_MOVED_DELIVERY_DATES">магазин не может доставит заказ в срок</option>
		  <option value="USER_MOVED_DELIVERY_DATES">покупатель сам изменил дату</option>
		</select>
	</td>
	</tr>
	
	<tr>
	<td colspan="2" align="right">
		<input type="reset" name="cancel" value="Отменить" id="cancel_deliverydate_form" class="button" />
		<input type="submit" name="save" value="Применить" class="button" />
	</td>
	</tr>
	</table>
</form>

<form action="<?=HTTPS_SERVER?>yaorder/index.php" method="GET" id="trackcode_form" style="display: none;">
	<input type="hidden" name="action" value="delivery_track" />
	<input type="hidden" name="ya_order" value="<?=$this->yaorder_id?>" />
	<table class="form">
	<tr>
	<td width="35%"><label for="trackcode_inp">Трек-номер:</label></td>
	<td>
		<input type="text" id="trackcode_inp" name="trackcode" value="<?=(isset($data['delivery']['tracks']) && isset($data['delivery']['tracks'][0]['trackCode']) ? $data['delivery']['tracks'][0]['trackCode'] : '')?>" size="24" />
	</td>
	</tr>
	<td width="35%"><label for="deliveryservice_sel">Служба доставки:</label></td>
	<td><select name="deliveryservice" id="deliveryservice_sel" style="width: 100%;">
		</select>
	</td>
	</tr>
	
	<tr>
	<td colspan="2" align="right">
		<input type="reset" name="cancel" value="Отменить" id="cancel_trackcode_form" class="button" />
		<input type="submit" name="save" value="Применить" class="button" />
	</td>
	</tr>
	</table>
</form>

<script>
$('#edit_status_link').click(function() {
	$('.hide_on_edit').hide();
	$('#edit_status_form').show();
	return false;
})

$('#cancel_status_form').click(function() {
	$('#edit_status_form').hide();
	$('.hide_on_edit').show();
})

$('#status_sel').change(function() {
	if ($(this).val() == 'CANCELLED')
		$('#substatus_tr').show();
	else
		$('#substatus_tr').hide();
})
$('#status_sel').trigger('change');

$('#edit_delivery_link').click(function() {
	$('.hide_on_edit').hide();
	$('#edit_delivery_form').show();
	return false;
})

$('#cancel_delivery_form').click(function() {
	$('#edit_delivery_form').hide();
	$('.hide_on_edit').show();
})

$('#deliverytype_sel').change(function() {
	/*
	if ($(this).val() == 'DELIVERY') {
		$('#service_name_inp').removeAttr('disabled');
		$('#servicename_tr').show();
	} else {
		$('#service_name_inp').attr('disabled', 'true');
		$('#servicename_tr').hide();
	}
	*/
	if ($(this).val() == 'PICKUP') {
		$('#outlet_id_inp').removeAttr('disabled');
		$('#outlet_tr').show();
	} else {
		$('#outlet_id_inp').attr('disabled', 'true');
		$('#outlet_tr').hide();
	}
})

$('#move_deliverydate_link').click(function() {
	$('.hide_on_edit').hide();
	$('#move_deliverydate_form').show();
	return false;
})

$('#cancel_deliverydate_form').click(function() {
	$('#move_deliverydate_form').hide();
	$('.hide_on_edit').show();
})

$('#trackсode_link').click(function() {
	$('.hide_on_edit').hide();
	$('#trackcode_form').show();
	return false;
})

$('#cancel_trackcode_form').click(function() {
	$('#trackcode_form').hide();
	$('.hide_on_edit').show();
})

$('#deliverytype_sel').trigger('change');

$.get("<?=HTTPS_SERVER?>yaorder/index.php?action=delivery_services&ya_order=<?=$this->yaorder_id?>", function(resp) {
	var data = JSON.parse(resp);
	if (data.result && data.result.deliveryService) {
		var items = data.result.deliveryService.sort(function(a, b) {
		  var nameA = a.name.toUpperCase(); // ignore upper and lowercase
		  var nameB = b.name.toUpperCase(); // ignore upper and lowercase
		  if (nameA < nameB) {
			return -1;
		  }
		  if (nameA > nameB) {
			return 1;
		  }
		  // names must be equal
		  return 0;
		});
		var sel = '<?=(isset($data['delivery']['tracks']) && isset($data['delivery']['tracks'][0]['deliveryServiceId']) ? $data['delivery']['tracks'][0]['deliveryServiceId'] : '')?>';
		$.each(items, function(key, item) {
			if (item.id == sel) {
				$('#delivery_service_name').text(item.name);
			}
			$('#deliveryservice_sel').append('<option value="'+item.id+'"'+(item.id==sel ? ' selected="selected"' : '')+'>'+item.name+'</option>');
		})
	}
})
</script>

<?php } ?>

</body>
</html>
<?php
	}
	
	protected function getStatCounters() {
	}
}
