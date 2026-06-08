<?php
class YaBuyView {
	protected $yaorder;
	protected $yaorder_id;
	protected $oauth_id;

	function __construct($yaorder_id, $oauth_id = '') {
		$this->yaorder_id = $yaorder_id;
		$this->oauth_id = $oauth_id;
	}

	public function render($data) {
		$this->yaorder = new YaOrder();
		$vdata = $this->getViewData($data);
		return $this->showForm($vdata);
	}

	public function error($data) {
		$this->showError($data['errors']);
	}

	public function payable($company_id) {
		$this->showPayable($company_id);
	}

	protected function getViewData($data) {
		$ret = array();

		$ret['id'] = $data['order']['id'];

		$ret['status'] = $data['order']['status'];

		$ret['status_text'] = $this->yaorder->getStatusName($ret['status']);

		$ret['substatus'] = $data['order']['substatus'];

		$ret['substatus_text'] = $this->yaorder->ORDER_SUBSTATUSES[$ret['substatus']];

		$ret['fake'] = $data['order']['fake'];

		$ret['payment'] = $data['order']['paymentType'];
		$ret['payment_text'] = $this->yaorder->PAYMENT_TYPES[$data['order']['paymentType']];
		$ret['payment_method_text'] = $this->yaorder->PAYMENT_METHODS[$data['order']['paymentMethod']];

		$ret['next'] = $this->yaorder->getStatusNext($ret['substatus']);

		$ret['delivery'] = $data['order']['delivery'];
		$ret['delivery']['type_text'] = $this->yaorder->DELIVERY_TYPES[$data['order']['delivery']['type']];
		
		$ret['delivery_types'] = $this->yaorder->DELIVERY_TYPES;

		return $ret;
	}

	protected function showError($data) {
		if (isset($data[0])) {
			$data = $data[0];
		}
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
	<td><?=$data['status_text']?><?=($data['substatus'] ? ' ('.$data['substatus_text'].')' : '')?>
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
	</td>
	</tr>
	<tr class="hide_on_edit">
	<td>Стоимость доставки</td>
	<td><?=$data['delivery']['price']?> руб.</td>
	</tr>
	<tr class="hide_on_edit">
	<td>Срок доставки</td>
	<td>с <?=$data['delivery']['dates']['fromDate']?> по <?=$data['delivery']['dates']['toDate']?></td>
	</tr>
	<?php
		$shipmentId = '';
		$box = array('width'=>0, 'height'=>0, 'depth'=>0, 'weight'=>0);
		$box_created = false;
		if (isset($data['delivery']['shipments']) && isset($data['delivery']['shipments'][0])) {
			$shipmentId = $data['delivery']['shipments'][0]['id'];
			$box = $data['delivery']['shipments'][0];
			if (isset($data['delivery']['shipments'][0]['boxes']) && isset($data['delivery']['shipments'][0]['boxes'][0])) {
				$box = $data['delivery']['shipments'][0]['boxes'][0];
				$box_created = true;
			}
	?>
	<tr class="hide_on_edit">
	<td>ID посылки</td>
	<td><?=$shipmentId?>
	<?php if ($box_created) { ?>
		&nbsp;<a href="<?=HTTPS_SERVER?>yaorder/index.php?action=transfer_act&ya_order=<?=$this->yaorder_id?>" target="_blank">Акт приема-передачи</a>
	<?php } ?>
	</td>
	</tr>
	<tr class="hide_on_edit">
	<td>Размеры коробки</td>
	<td><?php 
		if ($box_created) {
			echo $box['width'].'&times;'.$box['height'].'&times;'.$box['depth'].'см, '.$box['weight'].'гр.'; 
		}
		else {
			echo '<span style="color: red;">Задайте размеры коробки</span>';
		}
		?>
		<?php if ($box_created) { ?>
            &nbsp;<a href="<?=HTTPS_SERVER?>yaorder/index.php?action=labels&ya_order=<?=$this->yaorder_id?>" target="_blank">Скачать этикетки</a>
		<?php } ?>
		<span style="float: right;"> <a id="edit_box_link">Задать размеры</a> </span>
	</td>
	</tr>
	<?php } ?>

</tbody>
</table>

<?php if ($data['next']) { ?>
<form action="<?=HTTPS_SERVER?>yaorder/index.php" method="GET" id="edit_status_form" style="display: none;">
	<input type="hidden" name="action" value="status" />
	<input type="hidden" name="ya_order" value="<?=$this->yaorder_id?>" />
	<table class="form">
	<tr>
	<td width="35%"><label for="status">Сменить статус:</label></td>
	<td><select name="substatus" id="status_sel" style="width: 100%;">
		<?php foreach ($data['next'] as $key=>$text) { ?>
		  <option value="<?=$key?>"><?=$text?></option>
		<?php } ?>
		</select>
	</td>
	</tr>

	<tr>
	<td colspan="2" align="right">
		<input type="reset" name="cancel" value="Отменить" id="cancel_status_form" class="button" />
		<input type="submit" name="save" value="Применить" class="button" />
	</td>
	</tr>
	</table>
</form>
<?php } ?>

<?php if ($shipmentId) { ?>
<form action="<?=HTTPS_SERVER?>yaorder/index.php" method="GET" id="edit_box_form" style="display: none;">
	<input type="hidden" name="action" value="box" />
	<input type="hidden" name="ya_order" value="<?=$this->yaorder_id?>" />
	<input type="hidden" name="shipmentId" value="<?=$shipmentId?>" />	
	<table class="form">
	<tr>
	<td width="35%"><label for="deliverytype_sel">Размеры в см:</label></td>
	<td>
		<input type="text" name="width" value="<?=$box['width']?>" size="2" /> &times;
		<input type="text" name="height" value="<?=$box['height']?>" size="2" /> &times;
		<input type="text" name="depth" value="<?=$box['depth']?>" size="2" />
	</td>
	</tr>
	<td width="35%"><label for="price">Вес в граммах:</label></td>
	<td><input type="text" name="weight" value="<?=$box['weight']?>" size="5" />
	</td>
	</tr>
	
	<tr>
	<td colspan="2" align="right">
		<input type="reset" name="cancel" value="Отменить" id="cancel_box_form" class="button" />
		<input type="submit" name="save" value="Применить" class="button" />
	</td>
	</tr>
	</table>
</form>
<?php } ?>

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

$('#edit_box_link').click(function() {
	$('.hide_on_edit').hide();
	$('#edit_box_form').show();
	return false;
})

$('#cancel_box_form').click(function() {
	$('#edit_box_form').hide();
	$('.hide_on_edit').show();
})

$('#status_sel').trigger('change');
</script>

</body>
</html>
<?php
	}
	
}
