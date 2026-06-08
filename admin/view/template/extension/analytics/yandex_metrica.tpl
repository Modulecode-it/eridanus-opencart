<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-analytics" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <a href="https://metrika.yandex.ru/" target="_blank">
      <img src="<?php echo $logo; ?>" alt="<?php echo $heading_title; ?>" title="<?php echo $heading_title; ?>" style="width:200px; margin-bottom:15px" />
    </a>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-analytics" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a id="nav-tab-general" href="#tab-general" data-toggle="tab"><?php echo $tab_metrics; ?></a></li>
            <li><a id="nav-tab-search" href="#tab-search" data-toggle="tab"><?php echo $tab_metric_search; ?></a></li>
            <li><a id="nav-tab-log" href="#tab-log" data-toggle="tab"><?php echo $tab_logging; ?></a></li>
            <li><a id="nav-tab-info" href="#tab-info" data-toggle="tab"><?php echo $tab_info; ?></a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab-general">
              <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_signup; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
              </div>
              <?php if (isset($notify_module_version)) { ?>
              <div class="alert alert-warning"><i class="fa fa-info-circle"></i> <?php echo $notify_module_version; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
              </div>
              <?php } ?>
              <fieldset id="option-value">
                <div class="form-group required">
                  <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                  <div class="col-sm-3">
                    <select name="yandex_metrica_status" id="input-status" class="form-control">
                      <?php if ($yandex_metrica_status) { ?>
                      <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                      <option value="0"><?php echo $text_disabled; ?></option>
                      <?php } else { ?>
                      <option value="1"><?php echo $text_enabled; ?></option>
                      <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </fieldset>
              <fieldset id="option-value">
                <legend><?php echo $entry_metrics_list; ?></legend>
                <table id="metrics" class="table table-striped table-bordered table-hover">
                  <thead>
                    <tr>
                      <td class="text-left"><?php echo $column_metric_code; ?></td>
                      <td class="text-left"><?php echo $column_settings; ?></td>
                      <td></td>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $metrica_row = 0; ?>
                    <?php if ($yandex_metrica_codes) { ?>
                    <?php foreach ($yandex_metrica_codes as $metrica) { ?>
                    <tr id="metrica-row<?php echo $metrica_row; ?>">
                        <td class="text-left">
                          <input type="text" name="yandex_metrica_codes[<?php echo $metrica_row; ?>][code]" value="<?php echo $metrica['code']; ?>" placeholder="<?php echo $column_metric_code; ?>" class="form-control" />
                          <?php if (isset($error_metrica[$metrica_row]['code'])) { ?>
                          <div class="text-danger"><?php echo $error_metrica[$metrica_row]['code']; ?></div>
                          <?php } ?>
                        </td>
                        <td class="text-left" style="width: 40%;">
                          <div class="checkbox">
                            <label for="ya_metrica_webvizor_<?php echo $metrica_row; ?>">
                              <?php if (!empty($metrica['ya_metrica_webvizor'])) { ?>
                              <input type="checkbox" checked="checked" name="yandex_metrica_codes[<?php echo $metrica_row; ?>][ya_metrica_webvizor]" id="ya_metrica_webvizor_<?php echo $metrica_row; ?>" class="" value="1"/> <?php echo $entry_webvisor; ?>
                              <?php } else { ?>
                              <input type="checkbox" name="yandex_metrica_codes[<?php echo $metrica_row; ?>][ya_metrica_webvizor]" id="ya_metrica_webvizor_<?php echo $metrica_row; ?>" class="" value="1"/> <?php echo $entry_webvisor; ?>
                              <?php } ?>
                            </label>
                          </div>
                        </td>
                        <td class="text-left">
                          <button type="button" onclick="$('#metrica-row<?php echo $metrica_row; ?>, .tooltip').remove();" data-toggle="tooltip" title="<?php echo $button_remove; ?>" class="btn btn-danger">
                            <i class="fa fa-minus-circle"></i>
                          </button>
                        </td>
                    </tr>
                    <?php $metrica_row++; ?>
                    <?php } ?>
                    <?php } ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"></td>
                      <td class="text-left"><button type="button" onclick="addmetrica();" data-toggle="tooltip" title="" class="btn btn-primary"><i class="fa fa-plus-circle"></i></button></td>
                    </tr>
                  </tfoot>
                </table>
              </fieldset>
            </div>
            <div class="tab-pane" id="tab-search">
                <div class="text-left">
                  <button type="button" id="button-search-metrica" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary"><i class="fa fa-arrow-search"></i> <?php echo $text_find_metric; ?></button>
                </div>
                <br />
                <div class="panel panel-default">
                  <div class="panel-body">
                    <textarea id="search-metrica-status" wrap="off" rows="15" readonly class="form-control"></textarea>
                  </div>
                </div>
            </div>
            <div class="tab-pane" id="tab-log">
              <?php if (!empty($error_warning)) { ?>
              <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
              </div>
              <?php } ?>
              <?php if (isset($success)) { ?>
              <div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
              </div>
              <?php } ?>
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $text_write_logs; ?></label>
                <div class="col-sm-10">
                  <label class="radio-inline"> <?php if (isset($yandex_metrica_log)) { ?>
                    <input type="radio" name="yandex_metrica_log" value="1" checked="checked" />
                    <?php echo $text_yes; ?>
                    <? } else { ?>
                    <input type="radio" name="yandex_metrica_log" value="1" />
                    <?php echo $text_yes; ?>
                    <? } ?></label>
                  <label class="radio-inline"> <?php if (!isset($yandex_metrica_log)) { ?>
                    <input type="radio" name="yandex_metrica_log" value="0" checked="checked" />
                    <?php echo $text_no; ?>
                    <?php } else { ?>
                    <input type="radio" name="yandex_metrica_log" value="0" />
                    <?php echo $text_no; ?>
                    <?php } ?></label>
                </div>
              </div>
              <div class="panel panel-default">
                <div class="panel-heading">
                  <h3 class="panel-title"><i class="fa fa-exclamation-triangle"></i><?php echo $text_logs; ?></h3>
                </div>
                <div class="panel-body">
                  <textarea wrap="off" rows="15" readonly class="form-control"><?php echo $log; ?></textarea>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-info">
                <div class="text-left">
                    <?php echo $text_instructions; ?>
                </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript"><!--
// Добавление новых метрик
var metrica_row = <?php echo $metrica_row; ?>;

function addmetrica() {
  html  = '<tr id="metrica-row' + metrica_row + '">';
  html += '  <td class="text-left"><input type="text" name="yandex_metrica_codes[' + metrica_row + '][code]" value="" placeholder="<?php echo $column_metric_code; ?>" class="form-control" /></td>';
  html += '  <td class="text-left" style="width: 40%;">';
  html += '    <div class="checkbox">';
  html += '        <label for="ya_metrica_webvizor_' + metrica_row + '">';
  html += '                <input type="checkbox" checked="checked" name="yandex_metrica_codes[' + metrica_row + '][ya_metrica_webvizor]" id="ya_metrica_webvizor_' + metrica_row + '" class="" value="1"/> <?php echo $entry_webvisor; ?>';
  html += '        </label>';
  html += '    </div>';
  html += '  </td>';
  html += '  <td class="text-left"><button type="button" onclick="$(\'#metrica-row' + metrica_row  + ', .tooltip\').remove();" data-toggle="tooltip" title="<?php echo $button_remove; ?>" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>';
  html += '</tr>';

  $('#metrics tbody').append(html);

  metrica_row++;
}

$('#button-search-metrica').on('click', function() {
  $.ajax({
    url: 'index.php?route=extension/analytics/yandex_metrica/find_ya_metricax&token=<?php echo $token; ?>&store_id=<?php echo $store_id; ?>',
    dataType: 'json',
    beforeSend: function() {
      $('#search-metrica-status').val('');
      $('#search-metrica-status').val('<?php echo $text_start_of_search_metric; ?>');
      result = $('#search-metrica-status').val();
    },
    complete: function() {
      result += "\n<?php echo $text_end_of_search_metric; ?>";
      $('#search-metrica-status').val(result);
    },
    success: function(json) {
      if (json.count_of_metrik == 0) {
        result += "\n<?php echo $text_metrics_not_found; ?>:";
      } else {
        result += "\n<?php echo $text_metrics_found; ?>:";
        result += printMetrikCodes(json.codes_metrik);
      }
      $('#search-metrica-status').val(result);
    },
    error: function(xhr, ajaxOptions, thrownError) {
      console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });
});

function printMetrikCodes(codes) {
  str = '';
  for(var i=0; i<codes.length; i++) {
    str += "\n<?php echo $text_counter_number; ?>: " + codes[i] + ";";
  }
  return str;
}
//--></script> 

<?php if (isset($has_settings)) { ?>
  <script type="text/javascript"><!--
    $('#nav-tab-general').tab('show');
  //--></script>
<?php } else { ?>
  <script type="text/javascript"><!--
    $('#nav-tab-info').tab('show');
  //--></script>
<?php } ?>
<?php echo $footer; ?> 
