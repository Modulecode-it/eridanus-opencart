<?php
class ControllerExtensionModuleAvail extends Controller {
    private $error = array();
    private $module_version = 97;

    public function install() {
        $this->load->model('extension/module/avail');
        $this->model_extension_module_avail->install();

    }

    public function addLicense() {
        $this->load->model('extension/module/avail');
        $this->load->model('setting/setting');
        if(isset($this->request->post['availl_license'])) {
            $checked = $this->model_extension_module_avail->checklicense($this->request->post['availl_license']);
            // echo 'rrr= $checked';
            if($checked == 'true')
            {
                //$this->model_module_avail->addLicense($this->request->post['license']);
                $this->model_setting_setting->editSetting('availl', $this->request->post);

                $json = 'true';
            } else {
                $json = 'false';
            }
        } else {
            $json = 'false';
        }
        $this->response->setOutput(json_encode($json));
    }

    public function index() {
        $this->load->language('extension/module/avail');
        $this->load->model('extension/module/avail');
        $this->load->model('localisation/language');
        $this->document->setTitle($this->language->get('heading_title1'));
        if($this->language->get('code')){
            $data['lang'] = $this->language->get('code');
        } else {
            $data['lang'] = $this->language->get('lang');
        }
        if (!empty($this->config->get('config_editor_default')) && $this->config->get('config_editor_default')) {
            $this->document->addScript('view/javascript/ckeditor/ckeditor.js');
            $this->document->addScript('view/javascript/ckeditor/ckeditor_init.js');
        } else {
            $this->document->addScript('view/javascript/summernote/summernote.js');
            //  $this->document->addScript('view/javascript/summernote/lang/summernote-' . $this->language->get('lang') . '.js');
            $this->document->addScript('view/javascript/summernote/opencart.js');
            $this->document->addStyle('view/javascript/summernote/summernote.css');
        }

        $this->load->model('catalog/information');

        $data['informations'] = $this->model_catalog_information->getInformations();

        //  $data['lang'] = $this->language->get('lang');
        $this->load->model('setting/setting');

        $checked = $this->model_extension_module_avail->checklicense($this->config->get("avail_license"));

        if ($checked <> '1'){
            $data['entry_license'] = $this->language->get('entry_license');
            $data['text_license'] = $this->language->get('text_license');
            $data['button_submit_key'] = $this->language->get('button_submit_key');
            $data['text_edit'] = $this->language->get('text_edit');
            $data['text_license_abow'] = $this->language->get('text_license_abow');
            $data['button_submit'] = $this->language->get('button_submit');
            $data['user_token'] = $this->session->data['user_token'];
            $data['heading_title'] = $this->language->get('heading_title');
            $data['text_varsion'] = $this->language->get('text_varsion');
            $data['button_send'] = $this->language->get('button_send');
            $data['button_save'] = $this->language->get('button_save');
            $data['button_cancel'] = $this->language->get('button_cancel');

            $data['text_sometext'] = $this->language->get('text_sometext');
            $data['text_purchased'] = $this->language->get('text_purchased');
            $data['text_codecan'] = $this->language->get('text_codecan');
            $data['text_opencart'] = $this->language->get('text_opencart');
            $data['text_forumopencart'] = $this->language->get('text_forumopencart');
            $data['text_myopencart'] = $this->language->get('text_myopencart');
            $data['text_purchased'] = $this->language->get('text_purchased');
            $data['text_opencart_user'] = $this->language->get('text_opencart_user');
            $data['text_forumopencart_user'] = $this->language->get('text_forumopencart_user');
            $data['text_payment_numer'] = $this->language->get('text_payment_numer');
            $data['text_client_mail'] = $this->language->get('text_client_mail');
            $data['text_domain'] = $this->language->get('text_domain');
            $data['text_comment'] = $this->language->get('text_comment');
            $data['text_success_license'] = $this->language->get('text_success_license');
            $data['text_not_successe'] = $this->language->get('text_not_successe');

            $data['text_success_send_mail'] = $this->language->get('text_success_send_mail');



            $data['client_mail'] = $this->config->get('config_email');

            $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');
            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_home'),
                'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
                'separator' => false
            );

            $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_module'),
                'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'),
                'separator' => ' :: '
            );

            $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('heading_title'),
                'href'      => $this->url->link('extension/module/avail', 'user_token=' . $this->session->data['user_token'], 'SSL'),
                'separator' => ' :: '
            );


            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('extension/module/availabilitylicense', $data));

        } else {
            //  if (!empty($this->request->post['avail_email'])){
            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

                $this->model_setting_setting->editSetting('avail', $this->request->post);
                $this->model_setting_setting->editSetting('module_avail', $this->request->post);

                $this->session->data['success'] = $this->language->get('text_success_avail');

                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'));
            }
            //  }
            $data['config_admin_language'] = $this->config->get('config_admin_language');
            //ТЕСТУЄМО ВИВІД В ІНПУТ
            //ТЕСТУЄМО ВИВІД В ІНПУТ
            //ТЕСТУЄМО ВИВІД В ІНПУТ

            if (isset($this->request->post['avail_arbitrary'])) {
                $data['avail_arbitrary'] = $this->request->post['avail_arbitrary'];
            } elseif ($this->config->get('avail_arbitrary')) {
                $data['avail_arbitrary'] = $this->config->get('avail_arbitrary');

            } else {
                $data['avail_arbitrary'] = '';
            }
            if (isset($this->request->post['avail_config_account_id'])) {
                $data['avail_config_account_id'] = $this->request->post['avail_config_account_id'];
            } elseif ($this->config->get('avail_config_account_id')) {
                $data['avail_config_account_id'] = $this->config->get('avail_config_account_id');

            } else {
                $data['avail_config_account_id'] = '';
            }


            //ТЕСТУЄМО ВИВІД В ІНПУТ
            //ТЕСТУЄМО ВИВІД В ІНПУТ
            //ТЕСТУЄМО ВИВІД В ІНПУТ


            $leyout = $this->model_extension_module_avail->getLeyoutByModule('avail');

            $leyour_link = $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'], 'SSL');
            $leyour_link = sprintf( $this->language->get('error_leyout'), $leyour_link);
            $data['leyout'] = ($leyout <= 0)?$leyour_link:'';
            $data['error_status'] = $this->language->get('error_status');

            $data['heading_title'] = $this->language->get('heading_title');
            $data['text_varsion'] = $this->language->get('text_varsion');
            $data['button_copy'] = $this->language->get('button_copy');
            $data['button_insert'] = $this->language->get('button_insert');
            $data['button_delete'] = $this->language->get('button_delete');
            $data['button_filter'] = $this->language->get('button_filter');
            $data['text_get_availabilitylist'] = $this->language->get('text_get_availabilitylist');
            $data['text_prefix'] = $this->language->get('text_prefix');
            $data['text_mail_on'] = $this->language->get('text_mail_on');
            $data['text_mail'] = $this->language->get('text_mail');
            $data['text_view_notices'] = $this->language->get('text_view_notices');
            $data['entry_name'] = $this->language->get('entry_name');
            $data['text_enabled'] = $this->language->get('text_enabled');
            $data['text_disabled'] = $this->language->get('text_disabled');
            $data['entry_status'] = $this->language->get('entry_status');
            $data['text_capcha'] = $this->language->get('text_capcha');
            $data['text_general'] = $this->language->get('text_general');
            $data['text_settings_mail'] = $this->language->get('text_settings_mail');
            $data['text_settings_appearance'] = $this->language->get('text_settings_appearance');
            $data['text_show_img'] = $this->language->get('text_show_img');
            $data['text_settings_css'] = $this->language->get('text_settings_css');
            $data['text_product_page'] = $this->language->get('text_product_page');
            $data['text_ather_page'] = $this->language->get('text_ather_page');
            $data['text_google_captcha'] = $this->language->get('text_google_captcha');
            $data['text_global_settings'] = $this->language->get('text_global_settings');
            $data['text_mail_defoult'] = "";//$this->config->get('config_email'); @todo: убрать из шаблона данную переменную
            $data['text_config_booton'] = $this->language->get('text_config_booton');
            $data['text_config_mail'] = $this->language->get('text_config_mail');
            $data['text_column_settings'] = $this->language->get('text_column_settings');
            $data['text_show_model'] = $this->language->get('text_show_model');
            $data['text_show_sku'] = $this->language->get('text_show_sku');

            $data['text_not_successe'] = $this->language->get('text_not_successe');
            $data['text_success_license'] = $this->language->get('text_success_license');

            $data['text_work_product_quantity']     = $this->language->get('text_work_product_quantity');
            $data['text_work_option_quantity']      = $this->language->get('text_work_option_quantity');
            $data['text_work_status']               = $this->language->get('text_work_status');

            $data['text_button_type']               = $this->language->get('text_button_type');
            $data['text_button_type_button']        = $this->language->get('text_button_type_button');
            $data['text_button_type_a']             = $this->language->get('text_button_type_a');
            $data['text_button_type_input']         = $this->language->get('text_button_type_input');
            $data['text_data_content_cron_help'] = $this->language->get('text_data_content_cron_help');
            $data['entry_avail_text_cron_help'] = $this->language->get('entry_avail_text_cron_help');

            $data['entry_button_athepage_class']    = $this->language->get('entry_button_athepage_class');
            $data['entry_button_product_class']    = $this->language->get('entry_button_product_class');
            $data['entry_avail_quantity']    = $this->language->get('entry_avail_quantity');



            $data['entry_google_captcha_public'] = $this->language->get('entry_google_captcha_public');
            $data['config_google_captcha_public'] = $this->language->get('config_google_captcha_public');
            $data['entry_google_captcha_secret'] = $this->language->get('entry_google_captcha_secret');
            $data['config_google_captcha_secret'] = $this->language->get('config_google_captcha_secret');
            $data['entry_capcha_status'] = $this->language->get('entry_capcha_status');
            $data['entry_capcha_status'] = $this->language->get('entry_capcha_status');
            $data['entry_config_product_edit'] = $this->language->get('entry_config_product_edit');
            $data['entry_avail_text_button_avail'] = $this->language->get('entry_avail_text_button_avail');
            $data['entry_avail_sender'] = $this->language->get('entry_avail_sender');
            $data['entry_notification_sender'] = $this->language->get('entry_notification_sender');

            $data['entry_sms_status'] = $this->language->get('entry_sms_status');
            $data['entry_sms_admin'] = $this->language->get('entry_sms_admin');
            $data['entry_sms_send1'] = $this->language->get('entry_sms_send1');
            $data['entry_sms_send2'] = $this->language->get('entry_sms_send2');

            $data['entry_background_button_send_notify'] = $this->language->get('entry_background_button_send_notify');
            $data['entry_border_button_send_notify'] = $this->language->get('entry_border_button_send_notify');
            $data['text_button_send_notify'] = $this->language->get('text_button_send_notify');
            $data['entry_text_button_send_notify'] = $this->language->get('entry_text_button_send_notify');


            $data['entry_background_button_open_notify'] = $this->language->get('entry_background_button_open_notify');
            $data['entry_border_button_open_notify'] = $this->language->get('entry_border_button_open_notify');
            $data['entry_text_button_open_notify'] = $this->language->get('entry_text_button_open_notify');
            $data['text_button_open_notify'] = $this->language->get('text_button_open_notify');
            $data['entry_icon_open_notify'] = $this->language->get('entry_icon_open_notify');
            $data['entry_icon_send_notify'] = $this->language->get('entry_icon_send_notify');
            $data['entry_avail_work_status'] = $this->language->get('entry_avail_work_status');
            $data['entry_avail_notify_status'] = $this->language->get('entry_avail_notify_status');


            $data['text_cron'] = $this->language->get('text_cron');
            $data['text_shortcode_option_type'] = $this->language->get('text_shortcode_option_type');
            $data['text_shortcode_option_name'] = $this->language->get('text_shortcode_option_name');
            $data['hint'] = $this->language->get('hint');
            $data['text_options_status'] = $this->language->get('text_options_status');
            $data['text_button_cart_productpage'] = $this->language->get('text_button_cart_productpage');
            $data['text_button_other_productpage'] = $this->language->get('text_button_other_productpage');
            $data['text_block_option_productpage'] = $this->language->get('text_block_option_productpage');
            $data['text_shortcode_option_name'] = $this->language->get('text_shortcode_option_name');

            $data['shortcodes'] = $this->language->get('shortcodes');
            $data['text_shortcode_name'] = $this->language->get('text_shortcode_name');
            $data['text_shortcode_product_name'] = $this->language->get('text_shortcode_product_name');
            $data['text_shortcode_price'] = $this->language->get('text_shortcode_price');
            $data['text_shortcode_model'] = $this->language->get('text_shortcode_model');
            $data['text_shortcode_sku'] = $this->language->get('text_shortcode_sku');
            $data['text_shortcode_image'] = $this->language->get('text_shortcode_image');
            $data['text_shortcode_big_image'] = $this->language->get('text_shortcode_big_image');
            $data['text_shortcode_link'] = $this->language->get('text_shortcode_link');
            $data['text_shortcode_option_type'] = $this->language->get('text_shortcode_option_type');
            $data['text_button_cart_other'] = $this->language->get('text_button_cart_other');
            $data['text_button_them'] = $this->language->get('text_button_them');
            $data['text_yes'] = $this->language->get('text_yes');
            $data['text_no'] = $this->language->get('text_no');
            $data['text_block_product'] = $this->language->get('text_block_product');
            $data['text_show_comment'] = $this->language->get('text_show_comment');
            $data['text_show_terms_conditions'] = $this->language->get('text_show_terms_conditions');
            $data['text_href_terms_conditions'] = $this->language->get('text_href_terms_conditions');
            $data['text_popup_page'] = $this->language->get('text_popup_page');

            $data['text_avail_button_avail'] = $this->language->get('text_avail_button_avail');



            $data['mail_notification_title'] = $this->language->get('mail_notification_title');
            $data['mail_client_title'] = $this->language->get('mail_client_title');
            $data['mail_admin_title'] = $this->language->get('mail_admin_title');


            $data['entry_notification_message'] = $this->language->get('entry_notification_message');
            $data['entry_client_message'] = $this->language->get('entry_client_message');
            $data['entry_admin_message'] = $this->language->get('entry_admin_message');
            $data['entry_notification_title'] = $this->language->get('entry_notification_title');
            $data['entry_client_title'] = $this->language->get('entry_client_title');
            $data['entry_admin_title'] = $this->language->get('entry_admin_title');

            $data['button_save'] = $this->language->get('button_save');
            $data['button_cancel'] = $this->language->get('button_cancel');

            $data['languages'] = $this->model_localisation_language->getLanguages();

            //help

            $data['help_google_captcha'] = $this->language->get('help_google_captcha');
            $data['text_button_other_help'] = $this->language->get('text_button_other_help');
            $data['text_block_option_productpage_help'] = $this->language->get('text_block_option_productpage_help');
            $data['text_button_product_help'] = $this->language->get('text_button_product_help');
            $data['text_button_other_product_help'] = $this->language->get('text_button_other_product_help');
            $data['text_mail_help'] = $this->language->get('text_mail_help');
            $data['text_cron_key'] = $this->language->get('text_cron_key');
            $data['text_cron_key_help'] = $this->language->get('text_cron_key_help');
            $data['help_avail_work_status'] = $this->language->get('help_avail_work_status');
            $data['help_avail_notify_status'] = $this->language->get('help_avail_notify_status');

            // Arbitrary field
            $data['text_arbitrary_fields'] = $this->language->get('text_arbitrary_fields');
            $data['text_arbitrary_fieldname'] = $this->language->get('text_arbitrary_fieldname');
            $data['text_arbitrary_namefieldlabel'] = $this->language->get('text_arbitrary_namefieldlabel');
            $data['text_arbitrary_shortcode'] = $this->language->get('text_arbitrary_shortcode');
            $data['text_arbitrary_on_off'] = $this->language->get('text_arbitrary_on_off');
            $data['text_arbitrary_show'] = $this->language->get('text_arbitrary_show');
            $data['text_arbitrary_namefield'] = $this->language->get('text_arbitrary_namefield');
            $data['text_arbitrary_shortcode_field'] = $this->language->get('text_arbitrary_shortcode_field');
            $data['text_arbitrary_type_field'] = $this->language->get('text_arbitrary_type_field');
            $data['text_arbitrary_littletext'] = $this->language->get('text_arbitrary_littletext');
            $data['text_arbitrary_bigtext'] = $this->language->get('text_arbitrary_bigtext');
            $data['text_arbitrary_phone'] = $this->language->get('text_arbitrary_phone');
            $data['text_arbitrary_number'] = $this->language->get('text_arbitrary_number');
            $data['text_arbitrary_idlabel'] = $this->language->get('text_arbitrary_idlabel');
            $data['text_arbitrary_idfield'] = $this->language->get('text_arbitrary_idfield');
            $data['text_arbitrary_classlabel'] = $this->language->get('text_arbitrary_classlabel');
            $data['text_arbitrary_classfield'] = $this->language->get('text_arbitrary_classfield');
            $data['text_arbitrary_required'] = $this->language->get('text_arbitrary_required');
            $data['text_arbitrary_validon'] = $this->language->get('text_arbitrary_validon');
            $data['text_arbitrary_type_valid'] = $this->language->get('text_arbitrary_type_valid');
            $data['text_arbitrary_sort'] = $this->language->get('text_arbitrary_sort');
            $data['text_arbitrary_textarea'] = $this->language->get('text_arbitrary_textarea');
            $data['text_arbitrary_addfield'] = $this->language->get('text_arbitrary_addfield');
            $data['text_arbitrary_delfield'] = $this->language->get('text_arbitrary_delfield');

            $data['text_arb_shortcode_help'] = $this->language->get('text_arb_shortcode_help');
            $data['text_arb_type_help'] = $this->language->get('text_arb_type_help');
            $data['text_arb_id_help'] = $this->language->get('text_arb_id_help');
            $data['text_arb_class_help'] = $this->language->get('text_arb_class_help');
            $data['text_arb_type_valid_help'] = $this->language->get('text_arb_type_valid_help');
            $data['text_arb_sort_help'] = $this->language->get('text_arb_sort_help');

// проверяем на наличии поля с данными по доп полях
            if(count($this->model_extension_module_avail->CheckColumn()) > 0) {
                $data['count_create_column'] = 1;
                $data['text_create_column'] = $this->language->get('text_create_column');
            } else {
                $data['count_create_column'] = 0;
            }
            //    проверяем на наличие поля для хранения количества желаемого товара
            /*if( $this->model_extension_module_avail->CheckColumnDesiredQuantity() == 0) {
                $data['text_create_column_97'] = $this->language->get('text_create_column');
            }*/

            $data['entry_avail_text_button_avail_help'] = $this->language->get('entry_avail_text_button_avail_help');
            $data['entry_capcha_status_help'] = $this->language->get('entry_capcha_status_help');
            $data['entry_config_product_edit_help'] = $this->language->get('entry_config_product_edit_help');
            $data['text_block_product_help'] = $this->language->get('text_block_product_help');
            $data['mail_notification_sender'] = $this->config->get('config_name');
            if (isset($this->error['warning'])) {
                $data['error_warning'] = $this->error['warning'];
            } else {
                $data['error_warning'] = '';
            }

            if (isset($this->error['email'])) {
                $data['error_email'] = $this->error['email'];
            } else {
                $data['error_email'] = '';
            }
            if (isset($this->error['error_button_cart_productpage'])) {
                $data['error_button_cart_productpage'] = $this->error['error_button_cart_productpage'];
            } else {
                $data['error_button_cart_productpage'] = '';
            }
            if (isset($this->error['error_button_other_productpage'])) {
                $data['error_button_other_productpage'] = $this->error['error_button_othert_productpage'];
            } else {
                $data['error_button_other_productpage'] = '';
            }
            if (isset($this->error['error_block_option_productpage'])) {
                $data['error_block_option_productpage'] = $this->error['error_block_option_productpage'];
            } else {
                $data['error_block_option_productpage'] = '';
            }
            if (isset($this->error['error_button_cart_other'])) {
                $data['error_button_cart_other'] = $this->error['error_button_cart_other'];
            } else {
                $data['error_button_cart_other'] = '';
            }
            if (isset($this->error['error_block_product'])) {
                $data['error_block_product'] = $this->error['error_block_product'];
            } else {
                $data['error_block_product'] = '';
            }
            if (isset($this->error['error_mail_send'])) {
                $data['error_mail_send'] = $this->error['error_mail_send'];
            } else {
                $data['error_mail_send'] = '';
            }
            if (isset($this->error['error_avail_google_captcha_public'])) {
                $data['error_avail_google_captcha_public'] = $this->error['error_avail_google_captcha_public'];
            } else {
                $data['error_avail_google_captcha_public'] = '';
            }
            if (isset($this->error['error_avail_google_captcha_secret'])) {
                $data['error_avail_google_captcha_secret'] = $this->error['error_avail_google_captcha_secret'];
            } else {
                $data['error_avail_google_captcha_secret'] = '';
            }

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_module'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );

            if (!isset($this->request->get['module_id'])) {
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link('extension/module/avail', 'user_token=' . $this->session->data['user_token'], 'SSL')
                );
            } else {
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link('extension/module/avail', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL')
                );
            }
            if (!isset($this->request->get['module_id'])) {
                $data['action'] = $this->url->link('extension/module/avail', 'user_token=' . $this->session->data['user_token'], 'SSL');
            } else {
                $data['action'] = $this->url->link('extension/module/avail', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL');
            }

            if (!isset($this->request->get['module_id'])) {
                $data['getAvailabilityList'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'], 'SSL');
            } else {
                $data['getAvailabilityList'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL');
            }

            $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');

            if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
                $module_info = $this->model_extension_module->getModule($this->request->get['module_id']);
            }

            $data['user_token'] = $this->session->data['user_token'];

            if (isset($this->request->post['module_avail_status'])) {
                $data['module_avail_status'] = $this->request->post['module_avail_status'];
            } elseif ($this->config->get('module_avail_status')){
                $data['module_avail_status'] = $this->config->get('module_avail_status');
            } else {
                $data['module_avail_status'] = '';
            }

            if (isset($this->request->post['avail_quantity'])) {
                $data['avail_quantity'] = $this->request->post['avail_quantity'];
            } elseif ($this->config->get('avail_quantity')){
                $data['avail_quantity'] = $this->config->get('avail_quantity');
            } else {
                $data['avail_quantity'] = '';
            }

            if (isset($this->request->post['avail_show_img'])) {
                $data['avail_show_img'] = $this->request->post['avail_show_img'];
            } elseif ($this->config->get('avail_show_img') == '0'){
                $data['avail_show_img'] = $this->config->get('avail_show_img');
            } else{
                $data['avail_show_img'] = '1';
            }

            if (isset($this->request->post['avail_cron_key'])) {
                $data['cron_link'] =  'wget -O - '.HTTP_CATALOG.'index.php?route=extension/module/avail/notify&cronkey='.$this->request->post['avail_cron_key'].' /dev/null 2>&1';
            } elseif ($this->config->get('avail_cron_key')){
                $data['cron_link'] =   'wget -O - '.HTTP_CATALOG.'index.php?route=extension/module/avail/notify&cronkey='.$this->config->get('avail_cron_key').' /dev/null 2>&1';
            } else{
                $data['cron_link'] = $this->language->get('text_cron_key_help');
            }

            if (isset($this->request->post['avail_cron_key'])) {
                $data['cron_key'] =  $this->request->post['avail_cron_key'];
            } elseif ($this->config->get('avail_cron_key')){
                $data['cron_key'] =  $this->config->get('avail_cron_key');
            } else{
                $data['cron_key'] ='';
            }



            if (isset($this->request->post['avail_show_comment'])) {
                $data['avail_show_comment'] = $this->request->post['avail_show_comment'];
            } elseif ($this->config->get('avail_show_comment')){
                $data['avail_show_comment'] = $this->config->get('avail_show_comment');
            } else{
                $data['avail_show_comment'] = '0';
            }
            if (isset($this->request->post['avail_href_terms_conditions'])) {
                $data['avail_href_terms_conditions'] = $this->request->post['avail_href_terms_conditions'];
            } elseif ($this->config->get('avail_href_terms_conditions')){
                $data['avail_href_terms_conditions'] = $this->config->get('avail_href_terms_conditions');
            } else{
                $data['avail_href_terms_conditions'] = '';
            }

            if (isset($this->request->post['avail_show_terms_conditions'])) {
                $data['avail_show_terms_conditions'] = $this->request->post['avail_show_terms_conditions'];
            } elseif ($this->config->get('avail_show_terms_conditions')){
                $data['avail_show_terms_conditions'] = $this->config->get('avail_show_terms_conditions');
            } else{
                $data['avail_show_terms_conditions'] = '0';
            }

            if (isset($this->request->post['avail_show_comment_column'])) {
                $data['avail_show_comment_column'] = $this->request->post['avail_show_comment_column'];
            } elseif ($this->config->get('avail_show_comment_column')){
                $data['avail_show_comment_column'] = $this->config->get('avail_show_comment_column');
            } else{
                $data['avail_show_comment_column'] = '0';
            }
            if (isset($this->request->post['avail_options_status'])) {
                $data['avail_options_status'] = $this->request->post['avail_options_status'];
            } elseif ($this->config->get('avail_options_status')) {
                $data['avail_options_status'] = $this->config->get('avail_options_status');
            } else {
                $data['avail_options_status'] = '';
            }

            if (isset($this->request->post['avail_default'])) {
                $data['avail_default'] = $this->request->post['avail_default'];
            } elseif($this->config->get('avail_default') !== null) {
                $data['avail_default'] = $this->config->get('avail_default');
            } else {
                $data['avail_default'] = '';
            }
            if (isset($this->request->post['avail_block_option_productpage'])) {
                $data['block_option_productpage'] = $this->request->post['avail_block_option_productpage'];
            } elseif ($this->config->get('avail_block_option_productpage')) {
                $data['block_option_productpage'] = $this->config->get('avail_block_option_productpage');
            } else {
                $data['block_option_productpage'] = '';
            }
            if (isset($this->request->post['avail_button_cart_productpage'])) {
                $data['button_cart_productpage'] = $this->request->post['avail_button_cart_productpage'];
            } elseif ($this->config->get('avail_button_cart_productpage')) {
                $data['button_cart_productpage'] = $this->config->get('avail_button_cart_productpage');

            } else {
                $data['button_cart_productpage'] = '';
            }
            if (isset($this->request->post['avail_button_other_productpage'])) {
                $data['button_other_productpage'] = $this->request->post['avail_button_other_productpage'];
            } elseif ($this->config->get('avail_button_other_productpage')) {
                $data['button_other_productpage'] = $this->config->get('avail_button_other_productpage');
            } else {
                $data['button_other_productpage'] = '';
            }
            if (isset($this->request->post['avail_button_cart_other'])) {
                $data['button_cart_other'] = $this->request->post['avail_button_cart_other'];
            } elseif ($this->config->get('avail_button_cart_other')) {
                $data['button_cart_other'] = $this->config->get('avail_button_cart_other');
            } else {
                $data['button_cart_other'] = '';
            }
            if (isset($this->request->post['avail_block_product'])) {
                $data['avail_block_product'] = $this->request->post['avail_block_product'];
            } elseif ($this->config->get('avail_block_product')) {
                $data['avail_block_product'] = $this->config->get('avail_block_product');
            } else {
                $data['avail_block_product'] = '';
            }
            if (isset($this->request->post['avail_email'])) {
                $data['avail_email'] = $this->request->post['avail_email'];
            } elseif ($this->config->get('avail_email')) {
                $data['avail_email'] = $this->config->get('avail_email');
            } else {
                $data['avail_email'] = '';
            }

            if (isset($this->request->post['avail_config_google_captcha_status'])) {
                $data['avail_config_google_captcha_status'] = $this->request->post['avail_config_google_captcha_status'];
            } elseif ($this->config->get('avail_config_google_captcha_status')) {
                $data['avail_config_google_captcha_status'] = $this->config->get('avail_config_google_captcha_status');
            } else {
                $data['avail_config_google_captcha_status'] = '';
            }
            if (isset($this->request->post['avail_config_product_edit'])) {
                $data['avail_config_product_edit'] = $this->request->post['avail_config_product_edit'];
            } elseif ($this->config->get('avail_config_product_edit')) {
                $data['avail_config_product_edit'] = $this->config->get('avail_config_product_edit');
            } else {
                $data['avail_config_product_edit'] = '';
            }

            if (isset($this->request->post['avail_config_google_captcha_public'])) {
                $data['avail_config_google_captcha_public'] = $this->request->post['avail_config_google_captcha_public'];
            } elseif ($this->config->get('avail_config_google_captcha_public')) {
                $data['avail_config_google_captcha_public'] = $this->config->get('avail_config_google_captcha_public');
            } else {
                if ($this->config->get('google_captcha_key')) {
                    $data['avail_config_google_captcha_public'] = $this->config->get('google_captcha_key');
                } else {
                    $data['avail_config_google_captcha_public'] = '';
                }
            }

            if (isset($this->request->post['avail_config_google_captcha_secret'])) {
                $data['avail_config_google_captcha_secret'] = $this->request->post['avail_config_google_captcha_secret'];
            } elseif ($this->config->get('avail_config_google_captcha_secret')) {
                $data['avail_config_google_captcha_secret'] = $this->config->get('avail_config_google_captcha_secret');
            } else {
                if ($this->config->get('google_captcha_secret')) {
                    $data['avail_config_google_captcha_secret'] = $this->config->get('google_captcha_secret');
                } else {
                    $data['avail_config_google_captcha_secret'] = '';
                }
            }

            if (isset($this->request->post['avail'])) {
                $data['message'] = $this->request->post['avail'];
            } elseif ($this->config->get('avail')) {
                $data['message'] = $this->config->get('avail');
            } else {
                $data['message'] = array();
            }
            if (isset($this->request->post['avail_text'])) {
                $data['avail_text'] = $this->request->post['avail_text'];
            } elseif ($this->config->get('avail_text')) {
                $data['avail_text'] = $this->config->get('avail_text');
            } else {
                $data['avail_text'] = array();
            }
            if (isset($this->request->post['avail_text_button_avail'])) {
                $data['avail_text_button_avail'] = $this->request->post['avail_text_button_avail'];
            } elseif ($this->config->get('avail_text_button_avail')) {
                $data['avail_text_button_avail'] = $this->config->get('avail_text_button_avail');
            } else {
                $data['avail_text_button_avail'] = array();
            }
            if (isset($this->request->post['avail_text_button_send_notify'])) {
                $data['avail_text_button_send_notify'] = $this->request->post['avail_text_button_send_notify'];
            } elseif ($this->config->get('avail_text_button_send_notify')) {
                $data['avail_text_button_send_notify'] = $this->config->get('avail_text_button_send_notify');
            } else {
                $data['avail_text_button_send_notify'] = '';
            }
            /* color */
            if (isset($this->request->post['avail_background_button_send_notify'])) {
                $data['avail_background_button_send_notify'] = $this->request->post['avail_background_button_send_notify'];
            } elseif ($this->config->get('avail_background_button_send_notify')){
                $data['avail_background_button_send_notify'] = $this->config->get('avail_background_button_send_notify');
            } else{
                $data['avail_background_button_send_notify'] = '';
            }
            if (isset($this->request->post['avail_border_button_send_notify'])) {
                $data['avail_border_button_send_notify'] = $this->request->post['avail_border_button_send_notify'];
            } elseif ($this->config->get('avail_border_button_send_notify')){
                $data['avail_border_button_send_notify'] = $this->config->get('avail_border_button_send_notify');
            } else{
                $data['avail_border_button_send_notify'] = '';
            }

            if ($this->config->get('availsms_status')) {
                $data['availsms_status'] = true;
            } else {
                $data['availsms_status'] = false;
            }

            if ($data['availsms_status']) {

                if (isset($this->request->post['availsmssend_status'])) {
                    $data['availsmssend_status'] = $this->request->post['availsmssend_status'];
                } elseif ($this->config->get('availsmssend_status')){
                    $data['availsmssend_status'] = $this->config->get('availsmssend_status');
                } else {
                    $data['availsmssend_status'] = '';
                }

                if (isset($this->request->post['availsmssend_admin'])) {
                    $data['availsmssend_admin'] = $this->request->post['availsmssend_admin'];
                } elseif ($this->config->get('availsmssend_admin')){
                    $data['availsmssend_admin'] = $this->config->get('availsmssend_admin');
                } else {
                    $data['availsmssend_admin'] = '';
                }

                if (isset($this->request->post['availsmssend_send1'])) {
                    $data['availsmssend_send1'] = $this->request->post['availsmssend_send1'];
                } elseif ($this->config->get('availsmssend_send1')){
                    $data['availsmssend_send1'] = $this->config->get('availsmssend_send1');
                } else {
                    $data['availsmssend_send1'] = '';
                }

                if (isset($this->request->post['availsmssend_send2'])) {
                    $data['availsmssend_send2'] = $this->request->post['availsmssend_send2'];
                } elseif ($this->config->get('availsmssend_send2')){
                    $data['availsmssend_send2'] = $this->config->get('availsmssend_send2');
                } else {
                    $data['availsmssend_send2'] = '';
                }

            }


            if (isset($this->request->post['avail_background_button_open_notify'])) {
                $data['avail_background_button_open_notify'] = $this->request->post['avail_background_button_open_notify'];
            } elseif ($this->config->get('avail_background_button_open_notify')){
                $data['avail_background_button_open_notify'] = $this->config->get('avail_background_button_open_notify');
            } else{
                $data['avail_background_button_open_notify'] = '';
            }
            if (isset($this->request->post['avail_border_button_open_notify'])) {
                $data['avail_border_button_open_notify'] = $this->request->post['avail_border_button_open_notify'];
            } elseif ($this->config->get('avail_border_button_open_notify')){
                $data['avail_border_button_open_notify'] = $this->config->get('avail_border_button_open_notify');
            } else{
                $data['avail_border_button_open_notify'] = '';
            }
            if (isset($this->request->post['avail_text_button_open_notify'])) {
                $data['avail_text_button_open_notify'] = $this->request->post['avail_text_button_open_notify'];
            } elseif ($this->config->get('avail_border_button_open_notify')){
                $data['avail_text_button_open_notify'] = $this->config->get('avail_text_button_open_notify');
            } else{
                $data['avail_text_button_open_notify'] = '';
            }

            /* end color */
            /* icon */
            if (isset($this->request->post['avail_icon_send_notify'])) {
                $data['avail_icon_send_notify'] = $this->request->post['avail_icon_send_notify'];
            } elseif ($this->config->get('avail_icon_send_notify')){
                $data['avail_icon_send_notify'] = $this->config->get('avail_icon_send_notify');
            } else{
                $data['avail_icon_send_notify'] = '';
            }
            if (isset($this->request->post['avail_icon_open_notify'])) {
                $data['avail_icon_open_notify'] = $this->request->post['avail_icon_open_notify'];
            } elseif ($this->config->get('avail_icon_open_notify')){
                $data['avail_icon_open_notify'] = $this->config->get('avail_icon_open_notify');
            } else{
                $data['avail_icon_open_notify'] = '';
            }
            /* end icon */
            /* column setting*/
            if (isset($this->request->post['avail_show_model'])) {
                $data['avail_show_model'] = $this->request->post['avail_show_model'];
            } elseif ($this->config->get('avail_show_model')){
                $data['avail_show_model'] = $this->config->get('avail_show_model');
            } else{
                $data['avail_show_model'] = '0';
            }
            if (isset($this->request->post['avail_show_sku'])) {
                $data['avail_show_sku'] = $this->request->post['avail_show_sku'];
            } elseif ($this->config->get('avail_show_sku')){
                $data['avail_show_sku'] = $this->config->get('avail_show_sku');
            } else{
                $data['avail_show_sku'] = '0';
            }
            /* end column setting*/
            // тип кнопки
            if (isset($this->request->post['avail_button_type'])) {
                $data['avail_button_type'] = $this->request->post['avail_button_type'];
            } elseif ($this->config->get('avail_button_type')) {
                $data['avail_button_type'] = $this->config->get('avail_button_type');
            } else {
                $data['avail_button_type'] = '0';
            }
            // стиль  кнопки категории и тд
            if (isset($this->request->post['avail_button_athepage_class'])) {
                $data['avail_button_athepage_class'] = $this->request->post['avail_button_athepage_class'];
            } elseif ($this->config->get('avail_button_athepage_class')) {
                $data['avail_button_athepage_class'] = $this->config->get('avail_button_athepage_class');
            } else {
                $data['avail_button_athepage_class'] = '';
            }
            // стиль  кнопки на товаре
            if (isset($this->request->post['avail_button_product_class'])) {
                $data['avail_button_product_class'] = $this->request->post['avail_button_product_class'];
            } elseif ($this->config->get('avail_button_product_class')) {
                $data['avail_button_product_class'] = $this->config->get('avail_button_product_class');
            } else {
                $data['avail_button_product_class'] = '';
            }
            /* srock statuse*/
            $this->load->model('localisation/stock_status');

            $data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
            if (isset($this->request->post['avail_button_cange_status'])) {
                $data['avail_button_cange_status'] = $this->request->post['avail_button_cange_status'];
            } elseif ($this->config->get('avail_button_cange_status')) {
                $data['avail_button_cange_status'] = $this->config->get('avail_button_cange_status');
            } else {
                $data['avail_button_cange_status'] = array();
            }
            if (isset($this->request->post['avail_notify_status'])) {
                $data['avail_notify_status'] = $this->request->post['avail_notify_status'];
            } elseif ($this->config->get('avail_notify_status')) {
                $data['avail_notify_status'] = $this->config->get('avail_notify_status');
            } else {
                $data['avail_notify_status'] = array();
            }

            if (isset($this->request->post['avail_customer_class'])) {
                $data['avail_customer_class'] = $this->request->post['avail_customer_class'];
            } elseif ($this->config->get('avail_customer_class')) {
                $data['avail_customer_class'] = $this->config->get('avail_customer_class');
            } else {
                $data['avail_customer_class'] ='';
            }
            if (!isset($this->request->post['avail_notify_status']) && ($this->config->get('avail_notify_status') == 2)) {
                $data['notify_status'] = $this->language->get('error_notify_status');
            }    else {
                $data['notify_status'] = '';
            }
            if (!isset($this->request->post['avail_button_cange_status']) && ($this->config->get('avail_notify_status') == 2)) {
                $data['error_complete_status'] = $this->language->get('error_complete_status');
            }    else {
                $data['error_complete_status'] = '';
            }

            /* END srock statuse*/
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('extension/module/avail', $data));
        }
    }
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/avail')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $this->request->post['avail_email'])) {
            if (!empty($this->request->post['avail_email'])) {
                $this->error['email'] = $this->language->get('error_email');
            }
        }

        if ( $this->request->post['avail_config_google_captcha_secret'] == '1') {
            if (empty($this->request->post['avail_config_google_captcha_secret'] )) {
                $this->error['error_avail_google_captcha_public'] = $this->language->get('error_google_captcha_public');
            }
            if (empty($this->request->post['config_google_captcha_secret'] )) {
                $this->error['error_avail_google_captcha_secret'] = $this->language->get('error_google_captcha_secret');
            }
        }
        if($this->request->post['avail']){
            foreach ($this->request->post['avail'] as $avail){
                if(isset($avail['notification_message'])) {

                    $notification_message =  strip_tags(html_entity_decode($avail['notification_message'], ENT_QUOTES, 'UTF-8'));

                    if(strlen($notification_message) <= 20 && strlen($notification_message) >= 1){
                        $this->error['error_mail_send'] = $this->language->get('error_mail_send');
                    }
                }
                if(isset($avail['client_message'])) {
                    $client_message =  strip_tags(html_entity_decode($avail['client_message'], ENT_QUOTES, 'UTF-8'));
                    if(strlen($client_message) <= 20 && strlen($client_message) >= 1){
                        $this->error['error_mail_send'] = $this->language->get('error_mail_send');
                    }
                }

            }
        }
        return !$this->error;
    }
    public function getAvailabilityList(){
        $this->load->language('extension/module/avail');
        $this->load->model('extension/module/avail');
        $this->load->model('catalog/product');


        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        if (!isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/avail', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/avail', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL')
            );
        }
        if (!isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_list'),
                'href' => $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_list'),
                'href' => $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL')
            );
        }
        $this->document->addStyle('view/stylesheet/avail.css');
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['user_token'] = $this->session->data['user_token'];

        $data['statuses'] = array(
            '0' => $this->language->get('text_status_notprocessed'),
            '1' => $this->language->get('text_status_processed')
        );


        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_varsion'] = $this->language->get('text_varsion');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_time'] = $this->language->get('text_time');
        $data['text_product'] = $this->language->get('text_product');
        $data['text_price'] = $this->language->get('text_price');
        $data['text_mail'] = $this->language->get('text_mail');
        $data['text_name'] = $this->language->get('text_name');
        $data['text_comment'] = $this->language->get('text_comment');
        $data['text_statuse'] = $this->language->get('text_statuse');
        $data['text_model'] = $this->language->get('text_model');
        $data['text_sku'] = $this->language->get('text_sku');
        $data['text_article'] = $this->language->get('text_article');
        $data['text_quantity'] = $this->language->get('text_quantity');
        $data['text_avail_quantity'] = $this->language->get('text_avail_quantity');
        $data['text_avail_quantity_open'] = $this->language->get('text_avail_quantity_open');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_filter_disabled'] = $this->language->get('text_filter_disabled');
        $data['text_filter_enabled'] = $this->language->get('text_filter_enabled');
        $data['text_filter_count_all'] = $this->language->get('text_filter_count_all');
        $data['text_filter_count_10'] = $this->language->get('text_filter_count_10');
        $data['text_filter_count_25'] = $this->language->get('text_filter_count_25');
        $data['text_filter_count_50'] = $this->language->get('text_filter_count_50');
        $data['text_filter_count_100'] = $this->language->get('text_filter_count_100');
        $data['entry_model'] = $this->language->get('entry_model');
        $data['text_product_enough'] = $this->language->get('text_product_enough');
        $data['text_product_not_enough'] = $this->language->get('text_product_not_enough');
        $data['text_all_avail_close'] = $this->language->get('text_all_avail_close');
        $data['text_avail_desired_quantity'] = $this->language->get('text_avail_desired_quantity');



        $data['tab_active'] = $this->language->get('tab_active');
        $data['tab_closed'] = $this->language->get('tab_closed');
        $data['tab_products'] = $this->language->get('tab_products');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_send'] = $this->language->get('button_send');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['id'] = "№";

        $data['reload'] = $this->language->get('reload');

        /*filter*/
        $data['entry_name'] = $this->language->get('text_entry_name');
        $data['entry_email'] = $this->language->get('text_entry_email');
        $data['entry_date_start'] = $this->language->get('text_date_start');
        $data['entry_date_end'] = $this->language->get('text_date_end');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['text_entry_count'] = $this->language->get('text_entry_count');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['button_filter'] = $this->language->get('button_filter');
        $data['text_entry_status'] = $this->language->get('text_entry_status');
        $data['text_entry_status'] = $this->language->get('text_entry_status');
        $data['text_entry_status'] = $this->language->get('text_entry_status');


        $data['text_arbitrary_show_hide'] = $this->language->get('text_arbitrary_show_hide');


        /* count in page*/
        if($this->config->get('avlist_filter_count') == '10') {
            $data['avlist_filter_count'] = 10;
        } elseif($this->config->get('avlist_filter_count') == '25') {
            $data['avlist_filter_count'] = 25;
        } elseif($this->config->get('avlist_filter_count') == '50') {
            $data['avlist_filter_count'] = 50;
        } elseif($this->config->get('avlist_filter_count') == '100') {
            $data['avlist_filter_count'] = 100;
        } else { $data['avlist_filter_count'] = 'all';}
        /* end count in page*/

        /* column setting*/
        if ($this->config->get('avail_show_model')){
            $data['avail_show_model'] = $this->config->get('avail_show_model');
        } else{
            $data['avail_show_model'] = '0';
        }
        if ($this->config->get('avail_show_sku')){
            $data['avail_show_sku'] = $this->config->get('avail_show_sku');
        } else{
            $data['avail_show_sku'] = '0';
        }


        if ($this->config->get('avail_show_comment_column')){
            $data['avail_show_comment_column'] = $this->config->get('avail_show_comment_column');
        } else{
            $data['avail_show_comment_column'] = '0';
        }
        /* end column setting*/
        if (isset($this->request->post['avail_options_status'])) {
            $data['avail_options_status'] = $this->request->post['avail_options_status'];
        } elseif ($this->config->get('avail_options_status')) {
            $data['avail_options_status'] = $this->config->get('avail_options_status');
        } else {
            $data['avail_options_status'] = '';
        }


        if (isset($this->request->get['filter_name_products'])) {
            $filter_name_products = $this->request->get['filter_name_products'];
        } else {
            $filter_name_products = null;
        }
        if (isset($this->request->get['filter_name_close'])) {
            $filter_name_close = $this->request->get['filter_name_close'];
        } else {
            $filter_name_close = null;
        }

        if (isset($this->request->get['filter_email_close'])) {
            $filter_email_close = $this->request->get['filter_email_close'];
        } else {
            $filter_email_close = null;
        }
        if (isset($this->request->get['filter_date_start_close'])) {
            $filter_date_start_close = $this->request->get['filter_date_start_close'];
        } else {
            $filter_date_start_close = null;
        }
        if (isset($this->request->get['filter_date_end_close'])) {
            $filter_date_end_close = $this->request->get['filter_date_end_close'];
        } else {
            $filter_date_end_close = null;
        }
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = null;
        }
        if (isset($this->request->get['filter_email'])) {
            $filter_email = $this->request->get['filter_email'];
        } else {
            $filter_email = null;
        }
        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = null;
        }
        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = null;
        }
        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = null;
        }
        if (isset($this->request->get['filter_model'])) {
            $filter_model = $this->request->get['filter_model'];
        } else {
            $filter_model = null;
        }
        if (isset($this->request->get['filter_sku'])) {
            $filter_sku = $this->request->get['filter_sku'];
        } else {
            $filter_sku = null;
        }
        if (isset($this->request->get['filter_model_products'])) {
            $filter_model_products = $this->request->get['filter_model_products'];
        } else {
            $filter_model_products = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'a.time';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }
        // limit in page
        //echo $data['avlist_filter_count'];
        if($data['avlist_filter_count'] =='all'){
            $avlist_filter_count  = 9999;
        }  else {
            $avlist_filter_count = $data['avlist_filter_count'];
        }
        $filter_data = array(
            'filter_name'	 	  => $filter_name,
            'filter_email'	  	  => $filter_email,
            'filter_date_start'	  => $filter_date_start,
            'filter_date_end'	  => $filter_date_end,
            'filter_status'	 	  => $filter_status,
            'filter_model'	 	  => $filter_model,
            'filter_sku'	 	  => $filter_sku,
            'sort'            => $sort,
            'order'           => $order,
            'start'          	  => ($page - 1) * $avlist_filter_count,
            'limit'         	  => $avlist_filter_count
        );

        $filter_data_close = array(
            'filter_name_close'	  => $filter_name_close,
            'filter_email_close'	  => $filter_email_close,
            'filter_date_start_close'	  => $filter_date_start_close,
            'filter_date_end_close'	  => $filter_date_end_close,
            'filter_model'	  => $filter_model,
            'filter_sku'	  => $filter_sku,
            'sort'            => $sort,
            'order'           => $order,
            'start'           => ($page - 1) * $avlist_filter_count,
            'limit'           => $avlist_filter_count
        );
        $avlist_filter_count_new = 9999;
        $filter_data_products = array(
            'filter_name_products'	  => $filter_name_products,
            'filter_model_products'	  => $filter_model_products,
            'sort'            => $sort,
            'order'           => $order,
            'start'           => ($page - 1) * $avlist_filter_count_new,
            'limit'           => $avlist_filter_count_new
        );

        $data['filter_name'] = $filter_name;
        $data['filter_email'] = $filter_email;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_status'] = $filter_status;
        $data['filter_name_close'] = $filter_name_close;
        $data['filter_name_products'] = $filter_name_products;
        $data['filter_email_close'] = $filter_email_close;
        $data['filter_date_start_close'] = $filter_date_start_close;
        $data['filter_date_end_close'] = $filter_date_end_close;
        $data['filter_model'] = $filter_model;
        $data['filter_sku'] = $filter_sku;
        $data['filter_model_products'] = $filter_model_products;


        /*end filter*/
        $url = '';

        $availability = array();
        $availability_total = $this->model_extension_module_avail->getAvailabilitiesTotal();
        $availability  = $this->model_extension_module_avail->getAvailabilities($filter_data);
        if (isset($availability)) {

            foreach ($availability as $availabilities) {
                $option_price = 0;
                $option_points = 0;
                $option_weight = 0;
                $price_db = $this->model_extension_module_avail->getPrice($availabilities['product_id']);
                // данные по опциям с заявок
                $options_notify = $this->model_extension_module_avail->OptionBuyProduct($availabilities['product_id'], $availabilities['id']);

                // получаем данные по опциям товара
                $data['options'] = array();
                // получаем данные по всем опциям и перебираем их
                foreach ($this->model_catalog_product->getProductOptions($availabilities['product_id']) as $option) {
                    $product_option_value_data = array();
                    // перебираем занчения опций
                    foreach ($option['product_option_value'] as $option_value) {
                        // в разрезе опций которые есть на заявках проходим по нима
                        foreach($options_notify as $options_notifys) {
                            // если опция с товара есть на заявке то сумипуем ее стоимость
                            if(in_array($option_value['product_option_value_id'],$options_notifys )) {

                                if ($option_value['price_prefix'] == '+') {
                                    $option_price += $option_value['price'];
                                } elseif ($option_value['price_prefix'] == '-') {
                                    $option_price -= $option_value['price'];
                                }

                                if ($option_value['points_prefix'] == '+') {
                                    $option_points += $option_value['points'];
                                } elseif ($option_value['points_prefix'] == '-') {
                                    $option_points -= $option_value['points'];
                                }

                                if ($option_value['weight_prefix'] == '+') {
                                    $option_weight += $option_value['weight'];
                                } elseif ($option_value['weight_prefix'] == '-') {
                                    $option_weight -= $option_value['weight'];
                                }

                            }
                        }
                    }
                }
// получаем стоимость товара в зависимости есть ли скидка или нету
                if (!$price_db['special']) {
                    $price = $price_db['price'];
                } else {
                    $price = $price_db['special'];
                }
// если стоимость опций больше 0 то добавляее до общей стоимости товара
                if($option_price > 0){
                    $price = $price +  $option_price;
                } else {
                    $price = $price;
                }

                $price = $this->currency->format($price, $this->config->get('config_currency'));


                $quantity =   ($this->config->get('avail_options_status') && $this->config->get('avail_options_status') == 2 )?$availabilities['product_status']:$availabilities['quantity'];

                $data['availabilities'][] = array (
                    'id' => $availabilities['id'],
                    'time'=>  $availabilities['time'],
                    'product' => $availabilities['product'],
                    'model' => $availabilities['model'],
                    'sku' => $availabilities['sku'],
                    'product_id' => $availabilities['product_id'],
                    'edit'       => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $availabilities['product_id'] . $url, true),
                    'price' => $price,
                    'price_added' => $availabilities['price'],
                    // 'special' => $availabilities['special'],
                    'email' => $availabilities['email'],
                    'name' => $availabilities['name'],
                    'status' =>$availabilities['status'],
                    'quantity' =>$quantity,
                    'desired_quantity' =>$availabilities['desired_quantity'],
                    'comment' =>  mb_strlen($availabilities['comment'])> 50 ? substr($availabilities['comment'],0,50) : $availabilities['comment'],
                    'full_comment' => mb_strlen($availabilities['comment'])> 50 ? $availabilities['comment'] : '',
                    'options' => $availabilities['option'],
                    'arbitrary_fields' => ($availabilities['arbitrary']!= '')?unserialize($availabilities['arbitrary']):array(),
                    'arbitrary_lang' => $availabilities['language_id']
                );
                $data['avail_arbitrary_fields'] = $this->config->get('avail_arbitrary');
            }
        }

        $processed = array();
        $processed  = $this->model_extension_module_avail->getProcessed($filter_data_close);
        if(isset($processed)) {

            foreach ($processed as $proces) {
                $option_price = 0;
                $option_points = 0;
                $option_weight = 0;
                $price_db = $this->model_extension_module_avail->getPrice($proces['product_id']);
                // данные по опциям с заявок
                $options_notify = $this->model_extension_module_avail->OptionBuyProduct($proces['product_id'], $proces['id']);

                // получаем данные по опциям товара
                $data['options'] = array();
                // получаем данные по всем опциям и перебираем их
                foreach ($this->model_catalog_product->getProductOptions($proces['product_id']) as $option) {
                    $product_option_value_data = array();
                    // перебираем занчения опций
                    foreach ($option['product_option_value'] as $option_value) {
                        // в разрезе опций которые есть на заявках проходим по нима
                        foreach($options_notify as $options_notifys) {
                            // если опция с товара есть на заявке то сумипуем ее стоимость
                            if(in_array($option_value['product_option_value_id'],$options_notifys )) {

                                if ($option_value['price_prefix'] == '+') {
                                    $option_price += $option_value['price'];
                                } elseif ($option_value['price_prefix'] == '-') {
                                    $option_price -= $option_value['price'];
                                }



                            }
                        }
                    }
                }

// получаем стоимость товара в зависимости есть ли скидка или нету
                if (!$price_db['special']) {
                    $price = $price_db['price'];
                } else {
                    $price = $price_db['special'];
                }
// если стоимость опций больше 0 то добавляее до общей стоимости товара
                if($option_price > 0){
                    $price = $price +  $option_price;
                } else {
                    $price = $price;
                }

                $price = $this->currency->format($price, $this->config->get('config_currency'));

                $quantity =   ($this->config->get('avail_options_status') && $this->config->get('avail_options_status') == 2 )?$proces['product_status']:$proces['quantity'];

                $data['processed'][] = array (
                    'id' => $proces['id'],
                    'time'=>  $proces['time'],
                    'product_id'=>$proces['product_id'],
                    'product' => $proces['product'],
                    'edit'       => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $proces['product_id'] . $url, true),
                    'model' => $proces['model'],
                    'sku' => $proces['sku'],
                    'price' => $price,
                    'price_added' => $proces['price'],
                    //  'special' => $availabilities['special'],
                    'email' => $proces['email'],
                    'name' => $proces['name'],
                    'status' =>$proces['status'],
                    'quantity' =>$quantity,
                    'comment' =>  mb_strlen($proces['comment'])> 50 ? substr($proces['comment'],0,50) : $proces['comment'],
                    'full_comment' => mb_strlen($proces['comment'])> 50 ? $proces['comment'] : '',
                    'options' => $proces['option']
                );
            }
        }

        $products = array();
        $products  = $this->model_extension_module_avail->getProducts($filter_data_products);

        if(isset($products)) {
            $option_price = 0;
            $option_points = 0;
            $option_weight = 0;
            foreach ($products as $product) {
                $price_db = $this->model_extension_module_avail->getPrice($product['product_id']);

                if ($price_db['special']) {
                    $price = $this->currency->format($price_db['special'],$this->config->get('config_currency'));
                } else {
                    $price = $this->currency->format($price_db['price'],$this->config->get('config_currency'));

                }
                if ($product['option']) {
                    $quantity_all = $product['avail_quant'];
                    $quantity_open = $product['avail_quant_open'];
                } else {
                    $quantity_all = $product['avail_quant'];
                    $quantity_open = $product['avail_quant_open'];
                }
                //$quantity =   ($this->config->get('avail_options_status') && $this->config->get('avail_options_status') == 2 )?$product['product_status']:$product['quantity'];

                $data['products'][] = array (
                    'product_id' => $product['product_id'],
                    'name' => $product['name'],
                    'edit'       => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'] . $url, true),
                    'model' => $product['model'],
                    'sku' => $product['sku'],
                    'price' => $price,
                    'price_added' => $product['price'],
                    //  'special' => $availabilities['special'],
                    'quantity' =>$product['quantity'],

                    'check' => $product['check'],
                    'options' => $product['option'],
                    'avail_quant' => $quantity_all,
                    'avail_quant_open' => $quantity_open
                );
            }
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . $this->request->get['filter_name'];
        }
        if (isset($this->request->get['filter_email'])) {
            $url .= '&filter_email=' . $this->request->get['filter_email'];
        }
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . $this->request->get['filter_model'];
        }
        if (isset($this->request->get['filter_sku'])) {
            $url .= '&filter_sku=' . $this->request->get['filter_sku'];
        }
        if (isset($this->request->get['filter_model_products'])) {
            $url .= '&filter_model_products=' . $this->request->get['filter_model_products'];
        }
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        $data['sort_time'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=a.time' . $url, 'SSL');
        $data['sort_product'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=a.product' . $url, 'SSL');
        $data['sort_price'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, 'SSL');
        $data['sort_mail'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=a.email' . $url, 'SSL');
        $data['sort_name'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=a.name' . $url, 'SSL');
        $data['sort_statuse'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=a.statuse' . $url, 'SSL');
        $data['sort_model'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, 'SSL');
        $data['sort_sku'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sku' . $url, 'SSL');
        $data['sort_quantity'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url, 'SSL');
        $data['sort_desired_quantity'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . '&sort=a.desired_quantity' . $url, 'SSL');
//@todo:вывести отдельно сортировку для каждой из вкладок, т.к. если выбрать сортировку по открытым заявкам во вкладке по товарам то уходит в ошибку на других вкладках
        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }
        if (isset($this->request->get['filter_email'])) {
            $url .= '&filter_email=' . $this->request->get['filter_email'];
        }
        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }
        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }
        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }
        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . $this->request->get['filter_model'];
        }
        if (isset($this->request->get['filter_sku'])) {
            $url .= '&filter_sku=' . $this->request->get['filter_sku'];
        }
        if (isset($this->request->get['filter_model_products'])) {
            $url .= '&filter_model_products=' . $this->request->get['filter_model_products'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        /* pagination */
        $avlist_filter_count = $avlist_filter_count;
        $pagination = new Pagination();
        $pagination->total = $availability_total;
        $pagination->page = $page;
        $pagination->limit = $avlist_filter_count;
        $pagination->url = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', 'SSL');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($availability_total) ? (($page - 1) * $avlist_filter_count) + 1 : 0, ((($page - 1) * $avlist_filter_count) > ($availability_total - $avlist_filter_count)) ? $availability_total : ((($page - 1) * $avlist_filter_count) +$avlist_filter_count), $availability_total, ceil($availability_total / $avlist_filter_count));
        /* end pagination*/




        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/availist', $data));
    }

    public function changeMailStatus(){
        $this->load->model('extension/module/avail');
        $data['user_token'] = $this->session->data['user_token'];
        $id = $this->request->post['id'];
        $status = $this->request->post['status'];
        if($this->model_extension_module_avail->changeMailStatus($id, $status)){
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function notify() {
        $store = HTTP_CATALOG;
        $this->load->model('extension/module/avail');
        $this->load->model('catalog/product');
        $this->language->load('extension/module/avail');

        $success = '';
        $success_options = '';

        if($this->config->get('avail_options_status') == 2) { // по статусу товара
            $result = $this->model_extension_module_avail->notifyProductByStokStatus();
            $result_options = Array();
        } else {
            $result = $this->model_extension_module_avail->notifyOption();
            /*получаем список заявок по продуктам с опциями*/
            $result_options = $this->model_extension_module_avail->ProductWithOption();
        }

        $messages = $this->config->get('avail');
        $product = Array();

        $data['user_token'] = $this->session->data['user_token'];
        /*mail send*/
        $notifi_for_admin = 1;
        require_once(DIR_CATALOG . 'controller/extension/module/availmail.php');
        /*end mail send*/

        if(!empty($success) || !empty($success_options)) {
            $json['success'] = $this->language->get('success');
            $this->response->setOutput(json_encode($json));
        } else {
            $json['error'] = $this->language->get('error');
            $this->response->setOutput(json_encode($json));
        }
    }

    public function deleteNotifications() {
        $this->load->model('extension/module/avail');
        $data['user_token'] = $this->session->data['user_token'];
        $selected = $this->request->post['idArray'];

        foreach ($selected as $value ) {
            $this->model_extension_module_avail->deleteNotifications($value);
        }
    }

    public function requestKey(){

        if($this->request->server['REQUEST_METHOD'] == 'POST' ) {

            $mail_text = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Document</title></head><body>";
            $mail_text .= "<p> Новый заказ на лицензию!</p>";
            $mail_text .= "<p> покупка на: " . $this->request->post['wherebuy_radio'] . "</p>";
            $mail_text .= "<p> заказ / код: " . $this->request->post['user_data'] . "</p>";
            $mail_text .= "<p> домен: " . $this->request->post['client_domain'] . "</p>";
            $mail_text .= "<p> e-mail клиента  :" .  $this->request->post['client_mail'] . "</p></body></html>";

            if ($this->config->get('config_mail')){
                $mail = new Mail($this->config->get('config_mail'));
            } else {
                $mail = new Mail();
                $mail->protocol = $this->config->get('config_mail_protocol');
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
            }

            $mail->setTo('support@myopencart.club');
            $mail->setFrom('support@myopencart.club');
            $mail->setSender('Avail - Pro');
            $mail->setSubject('Avail - Pro Новой заказ на лицензионній ключ');

            $mail->setHtml($mail_text);

            $mail->send();
            $json = 'true';
            $this->response->setOutput(json_encode($json));
        }  else {

            $json = 'false';
            $this->response->setOutput(json_encode($json));
        }

    }

    public function addSetiongCount(){
        if($this->request->server['REQUEST_METHOD'] == 'POST'){
            $this->load->model('setting/setting');
            // echo $this->request->post;
            $this->model_setting_setting->editSetting('avlist', $this->request->post);
            return true;
        } else {
            return false;
        }

    }




    public function UpdateTo96(){
        $this->load->model('extension/module/avail');

        $results =  $this->model_extension_module_avail->CheckColumn();

        $result =  $this->model_extension_module_avail->UpdateTo96($results);
        $this->response->setOutput(json_encode($result));
    }



}
