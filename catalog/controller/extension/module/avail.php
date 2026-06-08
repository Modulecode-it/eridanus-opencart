<?php

class ControllerExtensionModuleAvail extends Controller
{
    private $error = array();

    public function index()
    {
        $this->document->addStyle('catalog/view/theme/'.$this->config->get("theme_default_directory").'/stylesheet/availability.css');
        $this->document->addScript('catalog/view/javascript/avail.js');
    }

    public function getConfig()
    {
        $json = array();
        $cinfig = $this->config->get('avail_text');
        $config_language_id = $this->config->get('config_language_id');
        $buttom_name = $cinfig[$config_language_id]['button_avail'];
        $button_avail_help = $cinfig[$config_language_id]['button_avail_help'];
        $avail_button_cart_productpage = $this->config->get('avail_button_cart_productpage');
        $avail_options_status = $this->config->get('avail_options_status');
        $avail_block_option_productpage = $this->config->get('avail_block_option_productpage');


        $json = Array(
            'all_button_id' => $this->config->get('avail_button_cart_other'),
            'block_product' => $this->config->get('avail_block_product'),    // блок продукта Миниатюра
            'button' => $this->config->get('module_avail_status'), // включен модуль
            'avail_default' => $this->config->get('avail_default'),
            'text' => $buttom_name,
            'button_avail_help' => $button_avail_help,
            'avail_block_option_productpage' => $avail_block_option_productpage,
            'avail_button_cart_productpage' => $avail_button_cart_productpage,
            'avail_options_status' => $avail_options_status,
            'avail_button_other_productpage' => $this->config->get('avail_button_other_productpage'),
            'avail_background_button_send_notify' => $this->config->get('avail_background_button_send_notify'),
            'avail_border_button_send_notify' => $this->config->get('avail_border_button_send_notify'),
            'avail_icon_send_notify' => $this->config->get('avail_icon_send_notify'),
            'avail_background_button_open_notify' => $this->config->get('avail_background_button_open_notify'),
            'avail_border_button_open_notify' => $this->config->get('avail_border_button_open_notify'),
            'avail_icon_open_notify' => $this->config->get('avail_icon_open_notify'),
            'avail_text_button_send_notify' => $this->config->get('avail_text_button_send_notify'),
            'avail_text_button_open_notify' => $this->config->get('avail_text_button_open_notify'),
            'avail_button_type' => $this->config->get('avail_button_type'),
            'avail_button_product_class' => $this->config->get('avail_button_product_class'),
            'avail_button_athepage_class' => $this->config->get('avail_button_athepage_class'),
            'avail_customer_class' => $this->config->get('avail_customer_class'),

        );

        $this->response->setOutput(json_encode($json));
    }

    public function ValidOption()
    {

        $json = array();
        $this->language->load('extension/module/avail');
        $this->load->model('catalog/product');

        if (isset($_POST['option'])) {
            $option = array_filter($_POST['option']);
        } else {
            $option = array();
        }
        // получаем информацию о опциях
        $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);
        // если опция обязательна и не выбрана
        foreach ($product_options as $product_option) {
            if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                $json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
            }
        }

        if (isset($this->request->post['recurring_id'])) {
            $recurring_id = $this->request->post['recurring_id'];
        } else {
            $recurring_id = 0;
        }

        $recurrings = $this->model_catalog_product->getProfiles($this->request->post['product_id']);

        if ($recurrings) {
            $recurring_ids = array();

            foreach ($recurrings as $recurring) {
                $recurring_ids[] = $recurring['recurring_id'];
            }

            if (!in_array($recurring_id, $recurring_ids)) {
                $json['error']['recurring'] = $this->language->get('error_recurring_required');
            }
        }

        if (!$json) { //опции нету или не обязательны
            $json['success'] = '1';
            $this->response->setOutput(json_encode($json));
        } else { // опции есть и оязательны для заполненния

            $this->response->setOutput(json_encode($json));
        }
    }

    public function openForm()
    {

        $this->language->load('extension/module/avail');
        $this->load->model('extension/module/avail');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $data['lang'] = $this->session->data['language'];

        // проверяем есть ли опции у товара

        $data['heading_title'] = $this->language->get('heading_title');
        $data['entry_enquiry'] = $this->language->get('entry_enquiry');
        $data['entry_product'] = $this->language->get('entry_product');
        $data['entry_price'] = $this->language->get('entry_price');
        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_mail'] = $this->language->get('entry_mail');
        $data['entry_admin_mail'] = $this->language->get('entry_admin_mail');
        $data['entry_captcha'] = $this->language->get('entry_captcha');
        $data['entry_phone'] = $this->language->get('entry_phone');
        $data['button_send'] = $this->language->get('button_send');
        $data['text_product'] = $this->language->get('text_product');
        $data['text_detail'] = $this->language->get('text_detail');
        $data['text_price'] = $this->language->get('text_price');
        $data['text_avail_quantity'] = $this->language->get('text_avail_quantity');
        $data['avail_config_google_captcha_status'] = $this->config->get('avail_config_google_captcha_status');
        $data['avail_config_google_captcha_public'] = $this->config->get('avail_config_google_captcha_public');
        $data['avail_config_google_captcha_secret'] = $this->config->get('avail_config_google_captcha_secret');
        $data['admin_email'] = $this->config->get('email');
        $data['captcha_status'] = $this->config->get('config_captcha_status');
        $data['avail_show_img'] = $this->config->get('avail_show_img');
        $data['avail_quantity'] = $this->config->get('avail_quantity');
        $data['avail_background_button_send_notify'] = $this->config->get('avail_background_button_send_notify');
        $data['avail_border_button_send_notify'] = $this->config->get('avail_border_button_send_notify');
        $data['avail_text_button_send_notify'] = $this->config->get('avail_text_button_send_notify');
        $data['avail_icon_send_notify'] = $this->config->get('avail_icon_send_notify');
        $data['avail_show_comment'] = $this->config->get('avail_show_comment');



        $data['product_id'] = $this->request->post['product_id'];
        $data['logged'] = $this->customer->isLogged();
        $data['first_name'] = $this->customer->isLogged() ? $this->customer->getFirstName() : '';
        $data['mail'] = $this->customer->getEmail() ? $this->customer->getEmail() : '';
        $data['logged_id'] = $this->customer->getId() ? $this->customer->getId() : '';
        $product_info = $this->model_catalog_product->getProduct($this->request->post['product_id']);
        $data['language_id'] = $this->config->get('config_language_id');
        $data['product_name'] = htmlspecialchars_decode($product_info['name']);
        $data['image_src'] = $this->model_tool_image->resize($product_info['image'], 200, 200);
        $data['optionsId'] = array();
        $data['optionsNames'] = array();

        //Встановлюємо мінімальне значення бажаного товару
        $data['avail_product_quantity'] = $product_info['quantity'];
        $data['avail_min_product_quantity'] = $product_info['minimum']; // + 1;

        /*ТЕСТУЄМО ВИВІД ПОЛЯ*/
        $data['text_arbitrary_required'] = $this->language->get('text_arbitrary_required');
        $data['text_arbitrary_phone'] = $this->language->get('text_arbitrary_phone');
        $data['text_arbitrary_min_two'] = $this->language->get('text_arbitrary_min_two');
        $data['text_arbitrary_email'] = $this->language->get('text_arbitrary_email');
        $data['text_arbitrary_number'] = $this->language->get('text_arbitrary_number');
        $data['placeholder_phone'] = $this->language->get('placeholder_phone');

        $data['error_avail_quantity'] = $this->language->get('error_avail_quantity');
        $data['entry_terms_conditions'] = $this->language->get('entry_terms_conditions');


        $data['avail_show_terms_conditions'] = $this->config->get('avail_show_terms_conditions');
        if ($this->config->get('avail_show_terms_conditions') == 1) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('avail_config_account_id'));

            if ($information_info) {
                $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('avail_config_account_id'), true), $information_info['title'], $information_info['title']);
            } else {
                $data['text_agree'] = '';
            }
        } else {
            $data['text_agree'] = '';
        }



        if(isset($this->request->post['quantity'])){
            $data['quantity_av'] = $this->request->post['quantity'];
        }else{
            $data['quantity_av'] = $product_info['minimum'];
        }

        $data['avail_arbitrary'] = $this->config->get('avail_arbitrary');
        $av_field_row = 0;


        // фрмуємо масив данних для валідації форми для поля ім\'я та мейл
        // назва поля і його довжина
        $data['avail_valid_rules'] = "'name': {
                                                minlength: 2,
                                        }, ";
        // назва поля і його довжина
        $data['avail_valid_rules'] .= "'email': {
                                                minlength: 2,
                                                email: true,
                                        }, ";
        // назва поля і помилка
        $data['avail_valid_messages'] = "'name': {
                                                minlength: '$data[text_arbitrary_min_two]',
                                        }, ";
        // назва поля і помилка
        $data['avail_valid_messages'] .= "'email': {
                                                minlength: '$data[text_arbitrary_min_two]',
                                                email: '$data[text_arbitrary_email]',
                                        }, ";
      /*  $data['avail_valid_messages'] = "'desired_quantity': {
                                                min: '$data[error_avail_quantity]',
                                        }, ";*/
        // формуємо масив данних для валідації форми для кастомних полів
        if($data['avail_arbitrary']) {
            foreach ($data['avail_arbitrary'] as $av_arbitrary) {

                if ($av_arbitrary['field_valid'] == "1") {
                    if ($av_arbitrary['field_typeval'] == "phone") {
                        $data['avail_valid_rules'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                minlength: 14,
                                                maxlength: 14,
                                        }, ";
                        $data['avail_valid_messages'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                minlength: '$data[text_arbitrary_phone]',
                                                maxlength: '$data[text_arbitrary_phone]',
                                        }, ";
                    }
                    if ($av_arbitrary['field_typeval'] == "text") {
                        $data['avail_valid_rules'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                minlength: 2,
                                        }, ";
                        $data['avail_valid_messages'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                minlength: '$data[text_arbitrary_min_two]',
                                        }, ";
                    }
                    if ($av_arbitrary['field_typeval'] == "textarea") {
                        $data['avail_valid_rules'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                minlength: 2,
                                        }, ";
                        $data['avail_valid_messages'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                minlength: '$data[text_arbitrary_min_two]',
                                        }, ";
                    }
                    if ($av_arbitrary['field_typeval'] == "email") {
                        $data['avail_valid_rules'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                minlength: 2,
                                                email: true,
                                        }, ";
                        $data['avail_valid_messages'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                email: '$data[text_arbitrary_email]',
                                        }, ";
                    }
                    if ($av_arbitrary['field_typeval'] == "number") {
                        $data['avail_valid_rules'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                digits: true,
                                        }, ";
                        $data['avail_valid_messages'] .= "\"avail_arbitrary[" . $av_field_row . "]\": {
                                                digits: '$data[text_arbitrary_number]',
                                        }, ";
                    }
                }
                $av_field_row++;
            }


            /*ДЛЯ СОРТУВАННЯ*/
            if ($data['avail_arbitrary']) {
                $keys = array_keys($data['avail_arbitrary']);
                array_multisort(
                    array_column($data['avail_arbitrary'], 'field_sort'), SORT_ASC, SORT_NUMERIC, $data['avail_arbitrary'], $keys
                );
                $data['avail_arbitrary'] = array_combine($keys, $data['avail_arbitrary']);
            }
            /*ДЛЯ СОРТУВАННЯ*/
            /*ТЕСТУЄМО ВИВІД ПОЛЯ*/

        }
        // если есть опции
        if(!empty($this->request->post['option'])) {
            //  $this->model_extension_module_avail->getOptionType($this->request->post['option']);
            $data['product_options'] =  $this->model_extension_module_avail->getOptionType($this->request->post['option']);
            //если установлен модуль liveprice то стоимость берем с его логики

            if ((float)$product_info['special']) {
                $price = $product_info['special'];
            } else {
                $price =  $product_info['price'];
            }
            $option_price = $this->model_extension_module_avail->getOptionPrice($this->request->post['product_id'], $this->request->post['option']);
            $price_summ = $option_price + $price;
            $data['price'] = $this->currency->format( $this->tax->calculate($price_summ, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            $data['special'] = false;

            // добавляем в масив с опциями Тип рпции
            foreach ($this->request->post['option'] as $product_option_id => $product_option_value_id) {
                if (!empty($product_option_value_id)) {
                    if (!is_array($product_option_value_id)) {
                        array_push($data['optionsId'], $this->model_extension_module_avail->getOptionsId($product_option_id, $product_option_value_id));
                    } else {
                        foreach ($product_option_value_id as $index => $value) {
                            array_push($data['optionsId'], $this->model_extension_module_avail->getOptionsId($product_option_id, $value));
                        }
                    }
                }
            }
            // добавляем в масив с опциями название опции
            if($data['optionsId']) {
                foreach ($data['optionsId'] as $value) {
                    array_push($data['optionsNames'], $this->model_extension_module_avail->getOptionsNames($value['option_id'], $value['option_value_id'], $value['product_option_value_id']));
                }
            }
        } else {
            // если опций нету
            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $data['price'] = false;
            }
            if ((float)$product_info['special']) {
                $data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $data['special'] = false;
            }
            $data['product_options'] = Array();
        }
        $data['captcha'] = '';


            return $this->response->setOutput($this->load->view('extension/module/avail', $data));


    }

    public function getoptionsquantity_old()
    {
        $this->load->model('extension/module/avail');
        $response = array();
      /*  $optionsInfo = array();
        foreach ($this->request->post['option'] as $product_option_id => $product_option_value_id) {
            if (!empty($product_option_value_id)) {
                if (!is_array($product_option_value_id)) {
                    array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $product_option_value_id));
                } else {
                    foreach ($product_option_value_id as $index => $value) {
                        array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $value));
                    }
                }
            }
        }

        foreach ($optionsInfo as $info) {
            if (isset($info['quantity']) && $info['quantity'] <= 0) {
                $response = false;
                break;
            } else {
                $response = true;
            }
        }*/

        // Якщо ввімкнено налаштування для врахування бажаного товару
        if($this->config->get('avail_quantity') && !empty($this->request->post['option'])){

            $optionsInfo = array();
            foreach ($this->request->post['option'] as $product_option_id => $product_option_value_id) {
                if (!empty($product_option_value_id)) {
                    if (!is_array($product_option_value_id)) {
                        array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $product_option_value_id));
                    } else {
                        foreach ($product_option_value_id as $index => $value) {
                            array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $value));
                        }
                    }
                }
            }

            $check = 0;

            foreach($optionsInfo as $optionInfo){

                if($this->request->post['quantity'] > $optionInfo['quantity']){
                    $check = 1;
                    break;
                }

            }

            if($check == 0){
                $response = true;
            } else {
                $response = false;
            }
        } else {
            $response = true;
        }
        $this->response->setOutput(json_encode($response));
    }

    public function getoptionsquantity()
    {
        $this->load->model('extension/module/avail');
        //  $response = array();
        $optionsInfo = array();
        if(empty($this->config->get('avail_quantity')) or  $this->config->get('avail_quantity') == 0) {

            foreach ($this->request->post['option'] as $product_option_id => $product_option_value_id) {
                if (!empty($product_option_value_id)) {
                    if (!is_array($product_option_value_id)) {
                        array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $product_option_value_id));
                    } else {
                        foreach ($product_option_value_id as $index => $value) {
                            array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $value));
                        }
                    }
                }
            }


            foreach ($optionsInfo as $info) {

                if (isset($info['quantity']) && $info['quantity'] <= 0) {

                    $response = false;
                    break;
                } else {

                    $response = true;
                }
            }
        }

        // Якщо ввімкнено налаштування для врахування бажаного товару
        if($this->config->get('avail_quantity') && !empty($this->request->post['option'])){

            $optionsInfo = array();
            foreach ($this->request->post['option'] as $product_option_id => $product_option_value_id) {
                if (!empty($product_option_value_id)) {
                    if (!is_array($product_option_value_id)) {
                        array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $product_option_value_id));
                    } else {
                        foreach ($product_option_value_id as $index => $value) {
                            array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $value));
                        }
                    }
                }
            }

            $check = 0;

            foreach($optionsInfo as $optionInfo){

                if($this->request->post['quantity'] > $optionInfo['quantity']){
                    $check = 1;
                    break;
                }

            }

            if($check == 0){
                $response = true;
            } else {
                $response = false;
            }
        }

        $this->response->setOutput(json_encode($response));
    }

    public function save()
    {
        $this->load->model('catalog/product');
        $this->load->model('extension/module/avail');
        $this->language->load('extension/module/avail');
        $this->load->model('tool/image');
        $json = array();
        $message = '';

        $option_val = array();
        if (!empty($this->request->post['option_type']) && !empty($this->request->post['option_name'])) {
            $option_val = array_combine($this->request->post['option_type'], $this->request->post['option_name']);

            $option_val = http_build_query($option_val, '', ', ');
            $option_val = urldecode($option_val);

        }
        if ($this->config->get('avail_email')) {
            $avail_email = $this->config->get('avail_email');
        } else {
            $avail_email = "";
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $link = $this->url->link('product/product', '&product_id=' . $this->request->post['product_id']);
            $result = $this->model_catalog_product->getProduct($this->request->post['product_id']);

            if(isset($this->request->post['option'])) {


                //если установлен модуль liveprice то стоимость берем с его логики

                // выводим стоимость с учетом опций
                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                    if ((float)$result['special']) {
                        $price = $result['special'];
                    } else {
                        $price = $result['price'];
                    }
                    $option_price = $this->model_extension_module_avail->getOptionPrice($this->request->post['product_id'], $this->request->post['option']);
                    $price_summ = $option_price + $price;
                    $price = $this->currency->format($this->tax->calculate($price_summ, $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    //$data['special'] = false;
                    $special = false;
                } else {
                    $special = false;
                    $price = false;
                }
            } else {
                // если опций нету

                if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                    if ((float)$result['special']) {
                        $price = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    }
                } else {
                    $price = false;
                }

            }


            if ($result['image']) {
                $popup = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
            } else {
                $popup = '';
            }

            if ($result['image']) {
                $thumb = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));

            } else {
                $thumb = '';
            }


            // масив для писем
            $info = array(
                'product' => $result['name'],
                'product_id' => $this->request->post['product_id'],
                'admin_email' => $avail_email,
                'href' => $link,
                'price' => $price,
                'special' => '',
                'sku' => $result['sku'],
                'model' => $result['model'],
                'thumb' => $thumb,
                'popup' => $popup,
                'name' => $this->request->post['name'],
                'email' => $this->request->post['email'],
                'desired_quantity' => $this->request->post['desired_quantity'],
                'logged_id' => $this->request->post['logged_id'] ? $this->request->post['logged_id'] : '',
                'comment' => (!empty($this->request->post['enquiry']))?$this->request->post['enquiry']:'',
                'language_id' => $this->request->post['language_id'],
                'option_name' => $option_val ? $option_val : '',
                'status' => 0
            );

            // масив для добавления в базу
            $infobd = array(
                'product' => quotemeta ($result['name']),
                'product_id' => $this->request->post['product_id'],
                'admin_email' => $avail_email,
                'href' => $link,
                'price' => $price,
                'special' => '',
                'name' => quotemeta ($this->request->post['name']),
                'email' => $this->request->post['email'],
                'desired_quantity' => $this->request->post['desired_quantity'],
                'logged_id' => $this->request->post['logged_id'] ? $this->request->post['logged_id'] : '',
                'comment' => (!empty($this->request->post['enquiry']))?$this->request->post['enquiry']:'',
                'language_id' => $this->request->post['language_id'],
                'option_name' => $option_val ? $option_val : '',
                'arbitrary_fields' => !empty($this->request->post['avail_arbitrary'])?serialize($this->request->post['avail_arbitrary']):'',
                'status' => 0
            );

            $search_data = array('%name%', '%product_name%', '%price%', '%link%', '%option_type%', '%option_name%','%model%','%sku%','%thumb%','%popup%','%email%');
            $replace_data = array($info['name'], $info['product'], $price, $link, '', $info['option_name'],$info['model'],$info['sku'],$info['thumb'],$info['popup'],$info['email']);



            $messages = $this->config->get('avail');

            /* заглавие */
            $subject = strip_tags(html_entity_decode($messages[$info['language_id']]['client_title'], ENT_QUOTES, 'UTF-8'));

            if (strlen($subject) > 1) {
                $mail_subject = htmlspecialchars_decode($subject);
                $mail_subject = str_replace($search_data, $replace_data, $mail_subject);
            } else {
                $mail_subject = $this->language->get('mail_subject');
            }

            $message = strip_tags(html_entity_decode($messages[$this->request->post['language_id']]['client_message'], ENT_QUOTES, 'UTF-8'));

            if (strlen($message) > 1) {

                $message = htmlspecialchars_decode($messages[$this->request->post['language_id']]['client_message']);
                $message = str_replace($search_data, $replace_data, $message);

                // Для довільних полів

                // Перевіряємо чи у масиві POST існують дані довільних полів і користувач поставив у поле message шорткоди
                if (isset($this->request->post['avail_arbitrary'])){
                    // Беремо інформацію яку ввів користувач у довільні поля
                    $arbitrary_data_text_post_input = $this->request->post['avail_arbitrary'];
                    // Створюємо масив в який запишемо інформацію яку ввів користувач у довільні поля
                    $arbitrary_data_text_post = array();
                    foreach ($arbitrary_data_text_post_input as $t) {
                        $arbitrary_data_text_post[] = $t;
                    }
                    // Беремо всі дані довільних полів із settings
                    $data['avail_arbitrary'] = $this->config->get('avail_arbitrary');
                    // Дізнаємось назву ключів у довільних полях
                    $arbitrary_data_post_keys = array_keys($arbitrary_data_text_post_input);
                    // Створюємо масив в який, через масив, запишемо всі шорткоди, які відповідають кожному довільному полю
                    $arbitrary_data_post_search = array();
                    foreach ($arbitrary_data_post_keys as $k) {
                        $arbitrary_data_post_search[] = $data['avail_arbitrary'][$k]['field_shortcode'];
                    }
                    // Замінити всі шорткоди на те що введе покупець/користувач у довільні поля
                    $message = str_replace($arbitrary_data_post_search, $arbitrary_data_text_post, $message);
                }

            } else {

                $message = "
                                                <!DOCTYPE html>
                                                <html>
                                                        <head>
                                                                <title>" . $this->language->get('heading_title') . "</title>
                                                        </head>
                                                        <body>
                                                                <p>" . $this->language->get('mail_notify') . "</p>
                                                                <p>" . html_entity_decode($info['name'] . ", " . $this->language->get('text_mail_send'), ENT_QUOTES, 'UTF-8') . "</p>
                                                                <p>" . $this->language->get('text_product') . " " . $info['product'] . "</p>
                                                                <p>" . $this->language->get('info_product') . " " . " <a href=" . $info['href'] . ">" . $info['product'] . "</a></p>
                                                                <p>" . $this->language->get('text_price') . " " . $price . "</p>";
            }
            if ($this->config->get('config_mail')) {
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
            $mail->setTo($info['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($messages[$info['language_id']]['sender']);
            $mail->setSubject(html_entity_decode($mail_subject), ENT_QUOTES, 'UTF-8');

            $this->model_extension_module_avail->addMail($infobd);
            $lastId = $this->model_extension_module_avail->getLastId();

            if (isset($this->request->post['option_id'])) {

                $option_id = $this->request->post['option_id'];
                $option_quantity = $this->request->post['option_quantity'];
                $option_name = $this->request->post['option_name'];
                $option_type = $this->request->post['option_type'];

                $product_option_value_id = $this->request->post['product_option_value_id'];

                for ($i = 0; $i < count($option_id); $i++) {
                    $option_info = array(
                        'main_id' => $lastId,
                        'product_id' => $this->request->post['product_id'],
                        'option_value_id' => $option_id[$i],
                        'option_quantity' => $option_quantity[$i],
                        'option_name' => $option_name[$i],
                        'option_type' => $option_type[$i],
                        'product_option_value_id' => $product_option_value_id[$i]
                    );
                    $this->model_extension_module_avail->addOption($option_info);

                    $messagesopt = $this->config->get('avail');
                    $messageopt = strip_tags(html_entity_decode($messagesopt[$this->request->post['language_id']]['client_message'], ENT_QUOTES, 'UTF-8'));

                    if (strlen($messageopt) > 1) {
                        $messageopt = htmlspecialchars_decode($messagesopt[$info['language_id']]['client_message']);
                        $messageopt = str_replace($search_data, $replace_data, $messageopt);
                    } else {
                        $message .= "<p>" . $option_type[$i] . " - " . $option_name[$i] . "</p>";
                    }
                }


            }

            $message .= "</body></html>";

            $mail->setHtml($message);
            $mail->send();

            if ($info['admin_email']) {

                $messages = $this->config->get('avail');

                /* заглавие */
                $subject = strip_tags(html_entity_decode($messages[$info['language_id']]['admin_title'], ENT_QUOTES, 'UTF-8'));

                if (strlen($subject) > 1) {
                    $mail_subject = htmlspecialchars_decode($subject);
                    $mail_subject = str_replace($search_data, $replace_data, $mail_subject);
                } else {
                    $mail_subject = $this->language->get('mail_subject');
                }

                $message = strip_tags(html_entity_decode($messages[$this->request->post['language_id']]['admin_message'], ENT_QUOTES, 'UTF-8'));

                if (strlen($message) > 1) {
                    $admin_message = htmlspecialchars_decode($messages[$this->request->post['language_id']]['admin_message']);
                    $admin_message = str_replace($search_data, $replace_data, $admin_message);

                    // Для довільних полів

                    // Перевіряємо чи у масиві POST існують дані довільних полів і користувач поставив у поле admin_message шорткоди
                    if (isset($this->request->post['avail_arbitrary'])){
                        // Беремо інформацію яку ввів користувач у довільні поля
                        $arbitrary_data_text_post_input = $this->request->post['avail_arbitrary'];
                        // Створюємо масив в який запишемо інформацію яку ввів користувач у довільні поля
                        $arbitrary_data_text_post = array();
                        foreach ($arbitrary_data_text_post_input as $t) {
                            $arbitrary_data_text_post[] = $t;
                        }
                        // Беремо всі дані довільних полів із settings
                        $data['avail_arbitrary'] = $this->config->get('avail_arbitrary');
                        // Дізнаємось назву ключів у довільних полях
                        $arbitrary_data_post_keys = array_keys($arbitrary_data_text_post_input);
                        // Створюємо масив в який, через масив, запишемо всі шорткоди, які відповідають кожному довільному полю
                        $arbitrary_data_post_search = array();
                        foreach ($arbitrary_data_post_keys as $k) {
                            $arbitrary_data_post_search[] = $data['avail_arbitrary'][$k]['field_shortcode'];
                        }
                        // Замінити всі шорткоди на те що введе покупець/користувач у довільні поля
                        $admin_message = str_replace($arbitrary_data_post_search, $arbitrary_data_text_post, $admin_message);
                    }
                } else {
                    $admin_message = "
                                                <!DOCTYPE html>
                                                        <html>
                                                                <body>
                                                                        <p>" . $this->config->get('config_owner') . ", " . $this->language->get('admin_mail_notify') . "</p>
                                                                </body>
                                                        </html>
                                                ";
                }

                if ($this->config->get('config_mail')) {
                    $admin_email = new Mail($this->config->get('config_mail'));
                } else {
                    $admin_email = new Mail();
                    $admin_email->protocol = $this->config->get('config_mail_protocol');
                    $admin_email->parameter = $this->config->get('config_mail_parameter');
                    $admin_email->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                    $admin_email->smtp_username = $this->config->get('config_mail_smtp_username');
                    $admin_email->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                    $admin_email->smtp_port = $this->config->get('config_mail_smtp_port');
                    $admin_email->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
                }
                $admin_email->setTo($info['admin_email']);
                $admin_email->setFrom($this->config->get('config_email'));
                $admin_email->setSender($messages[$info['language_id']]['sender']);
                $admin_email->setSubject(html_entity_decode($mail_subject), ENT_QUOTES, 'UTF-8');
                $admin_email->setHtml($admin_message);
                $admin_email->send();
            }
            $json['success'] = $this->language->get('success');
            $this->response->setOutput(json_encode($json));
        }
        if (isset($this->error['name'])) {
            $json['error_name'] = $this->error['name'];
        } else {
            $json['error_name'] = '';
        }
        if (isset($this->error['email'])) {
            $json['error_email'] = $this->error['email'];
        } else {
            $json['error_email'] = '';
        }
        if (isset($this->error['price'])) {
            $json['error_price'] = $this->error['price'];
        } else {
            $json['error_price'] = '';
        }
        if (isset($this->error['product'])) {
            $json['error_product'] = $this->error['product'];
        } else {
            $json['error_product'] = '';
        }
        if (isset($this->error['captcha'])) {
            $json['error_captcha'] = $this->error['captcha'];
        } else {
            $json['error_captcha'] = '';
        }
        $this->response->setOutput(json_encode($json));
    }

    public function validate()
    {
        $this->language->load('extension/module/avail');

        if ((utf8_strlen($this->request->post['name']) < 2) || (utf8_strlen($this->request->post['name']) > 32)) {
            $this->error['name'] = $this->language->get('error_name');
        }
        if (!preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $this->request->post['email'])) {
            $this->error['email'] = $this->language->get('error_email');
        }


        if (isset($this->request->post['captcha_status']) && $this->request->post['captcha_status'] == '1') {
            $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->request->post['avail_config_google_captcha_secret']) . '&response=' . $this->request->post['g-recaptcha-response'] . '&remoteip=' . $this->request->server['REMOTE_ADDR']);
            $recaptcha = json_decode($recaptcha, true);
            if (!$recaptcha['success']) {
                $this->error['captcha'] = $this->language->get('error_captcha');
            }
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }


    public function getProductById()
    {
        $this->load->model('extension/module/avail');
        $json = array();
        $json = $this->model_extension_module_avail->getProductId($this->request->post['product_id']);

        if ($json) {
            $this->response->setOutput(json_encode($json));
        } else {
            return false;
        }

    }
    public function notify()
    {
        // $store = HTTP_CATALOG;
        if ($this->request->get['cronkey'] == $this->config->get('avail_cron_key')) {
            $this->load->model('extension/module/avail');
            $this->load->model('catalog/product');
            $this->language->load('extension/module/avail');

            $success = '';
            $success_options = '';

            if($this->config->get('avail_options_status') == 2) { // по статусу товара
                $result = $this->model_extension_module_avail->notifyProductByStokStatus();
                //$result_options = Array();
                $result_options = $this->model_extension_module_avail->ProductWithOption();
            } else {
                $result = $this->model_extension_module_avail->notifyOption();
                /*получаем список заявок по продуктам с опциями*/
                $result_options = $this->model_extension_module_avail->ProductWithOption();
            }

            $messages = $this->config->get('avail');
            $product = Array();



            /*mail send*/
            $cron_work = 1;
            $notifi_for_admin = '0';
            require_once(DIR_APPLICATION . 'controller/extension/module/availmail.php');

            /*end mail send*/

            if (!empty($success) || !empty($success_options)) {
                echo  $this->language->get('success_send_motify');
                // $this->response->setOutput(json_encode($json));
            } else {
                echo $this->language->get('error_send_motify');

            }
        }
    }


    public function GetProductStatus($data = array()){
        // пролверяем на совпадение статус товара и стату с настроек при которых заменять кнопку
        // если $avail_button_cange_status = 0  то кнопку заменяем
        // если модуль работает по количеству товара на складе

        //  $this->load->model('extension/module/avail');
        // $product_info = $this->model_extension_module_avail->ProductStockStatusId($product_id);
        // echo $this->config->get('avail_options_status').'<br>';
        if($this->config->get('avail_options_status') == 0){
            $avail_button_change_status = ($data['quantity'] > 0)?1:0;
        }   elseif ($this->config->get('avail_options_status') == 2) {      // если заменять кнопку по статусу
            //$this->load->model('extension/module/avail');
            // $stock_status_id = $this->model_extension_module_avail->ProductStockStatusId($product_id);
            if (in_array($data['stock_status_id'], $this->config->get('avail_button_cange_status'))) {
                $avail_button_change_status = 0;
                // echo 'test1';
            } else {
                // echo 'test2';
                $avail_button_change_status = 1;
            }

        } else {    // работаем по опцииях
            $this->load->model('catalog/product');
            // если у товара есть опции то кнопку не меняем ее сменит скрипт при выборе опции
            if($this->model_catalog_product->getProductOptions($data['product_id'])) {
                $avail_button_change_status = 1;
            } else {
                // если на товаре нету опций то модуль работает по количеству товара
                $avail_button_change_status = ($data['quantity'] > 0)?1:0;
            }
        }
        return  $avail_button_change_status;


    }
    private function changeMailStatus(){
        $this->load->model('extension/module/avail');
        $data['token'] = $this->session->data['token'];
        $id = $this->request->post['id'];
        $status = $this->request->post['status'];
        if($this->model_extension_module_avail->changeMailStatus($id, $status)){
            echo 'ok';
        } else {
            echo 'error';
        }
    }
    public function accountavail()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', '', 'SSL');

            $this->response->redirect($this->url->link('account/login', '', 'SSL'));
        }

        $this->language->load('account/account');
        $this->language->load('extension/module/avail');

        $this->document->setTitle($this->language->get('heading_title'));


        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/avail', '', 'SSL')
        );

        $data['heading_title_account'] = $this->language->get('heading_title_account');

        $data['button_continue'] = $this->language->get('button_continue');
        $data['button_back'] = $this->language->get('button_back');
        $data['text_data_add'] = $this->language->get('text_data_add');
        $data['text_product'] = $this->language->get('text_product');
        $data['text_quantity'] = $this->language->get('text_quantity');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_delete'] = $this->language->get('text_delete');
        $data['text_notify_no'] = $this->language->get('text_notify_no');


        $data['action'] = $this->url->link('extension/module/avail/accountavail', '', 'SSL');
        $this->load->model('extension/module/avail');

        $avails = $this->model_extension_module_avail->getAvailabilities($this->customer->getId());

        //     echo ''.$this->config->get('avail_options_status');
        foreach ($avails as $key => $avail) {
            if ($this->config->get('avail_options_status') == '1') {


                $avails[$key]['avail_status'] = $data['text_no'];

                foreach ($avail['option'] as $option) {
                    if ($option['option_quantity'] <= 0) {
                        $avail_status = '0';
                        $avails[$key]['avail_status'] = $data['text_no'];

                        break;
                    } else {
                        $avail_status = '1';
                        $avails[$key]['avail_status'] = $data['text_yes'];
                    }
                }
            }   else if($this->config->get('avail_options_status') == '0'){

                if ($avail['quantity'] <= 0) {
                    $avail_status = '0';
                    $avails[$key]['avail_status'] = $data['text_no'];
                } else {
                    $avail_status = '1';
                    $avails[$key]['avail_status'] = $data['text_yes'];
                }

            } else {
                $product_info = $this->model_extension_module_avail->ProductStockStatusId($avail['product_id']);
                if (in_array($product_info['stock_status_id'], $this->config->get('avail_button_cange_status'))) {
                    $avails[$key]['avail_status'] = $data['text_no'];
                } else {
                    $avails[$key]['avail_status'] = $data['text_yes'];
                }
            }
        }

        $data['avails'] = $avails;
        //$data['text_status']   = ($avail_status == '1')?$data['text_yes']:$data['text_no'];
        $data['back'] = $this->url->link('account/account', '', 'SSL');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');


        return $this->response->setOutput($this->load->view('extension/module/accountavail', $data));

    }

    public function checkQuantity(){

        if(isset($this->request->post['quantity']) and isset($this->request->post['product_id'])){
            $config_avail_quantity = $this->config->get('avail_quantity');
            $avail_button_cart_productpage = $this->config->get('avail_button_cart_productpage');

            // Якщо ввімкнено налаштування для врахування бажаного товару
            if($config_avail_quantity){
                $return = array();
                $this->load->model('catalog/product');
                $this->load->model('extension/module/avail');
                $product_info = $this->model_catalog_product->getProduct($this->request->post['product_id']);

                $not_option = 0;

                // Якщо ввімкнено налаштування по кількості опцій на товарі
                if($this->config->get('avail_options_status') == 1){

                    if(isset($this->request->post['option'])){
                        $optionsInfo = array();
                        foreach ($this->request->post['option'] as $product_option_id => $product_option_value_id) {
                            if (!empty($product_option_value_id)) {
                                if (!is_array($product_option_value_id)) {
                                    array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $product_option_value_id));
                                } else {
                                    foreach ($product_option_value_id as $index => $value) {
                                        array_push($optionsInfo, $this->model_extension_module_avail->getOptionsId($product_option_id, $value));
                                    }
                                }
                            }
                        }

                        $check = 0;

                        foreach($optionsInfo as $optionInfo){

                            if($this->request->post['quantity'] > $optionInfo['quantity']){
                                $check = 1;
                                break;
                            }

                        }

                        if($check == 0){
                            $return = array(
                                'command' => 'not_replace',
                                'btn_cart' => $avail_button_cart_productpage
                            );
                        }else{
                            $return = array(
                                'command' => 'replace',
                                'btn_cart' => $avail_button_cart_productpage
                            );
                        }
                    }else{
                        $not_option = 1;
                    }

                }else{
                    $not_option = 1;
                }

                // Перевірка без опцій
                if($not_option == 1){
                    // Якщо відразу на товарі кількість більше 0 (в іншому випадку вже повинна бути замінена кнопка купівлі)
                    if($product_info['quantity'] > 0){
                        // Якщо бажана кількість більша за наявну кількість товару в магазині - даємо команду на заміну
                        if($this->request->post['quantity'] > $product_info['quantity']){

                            $return = array(
                                'command' => 'replace',
                                'btn_cart' => $avail_button_cart_productpage
                            );

                        }else{
                            $return = array(
                                'command' => 'not_replace',
                                'btn_cart' => $avail_button_cart_productpage
                            );
                        }
                    }
                }

                $this->response->setOutput(json_encode($return));
            }
        }
    }

    public function delete(){
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('extension/module/avail/accountavail', '', 'SSL');

            $this->response->redirect($this->url->link('account/login', '', 'SSL'));
        }

        $this->language->load('extension/module/avail');

        $this->document->setTitle($this->language->get('heading_title'));


        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('extension/module/avail/accountavail', '', 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_newsletter'),
            'href' => $this->url->link('extension/module/avail/accountavail', '', 'SSL')
        );

        $data['heading_title'] = $this->language->get('heading_title');

        $data['button_continue'] = $this->language->get('button_continue');
        $data['button_back'] = $this->language->get('button_back');
        $data['text_data_add'] = $this->language->get('text_data_add');
        $data['text_product'] = $this->language->get('text_product');
        $data['text_quantity'] = $this->language->get('text_quantity');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_delete'] = $this->language->get('text_delete');

        $data['action'] = $this->url->link('extension/module/avail/accountavail', '', 'SSL');
        $this->load->model('extension/module/avail');


        $data['back'] = $this->url->link('account/account', '', 'SSL');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');


        if($this->model_extension_module_avail->changeAvailStatus($this->request->get['avail_id'],'1')){
            $data['success'] =     $this->language->get('text_success');
        } else {
            $data['success'] =     $this->language->get('text_success_not');
        }

        $this->response->redirect($this->url->link('extension/module/avail/accountavail', '', 'SSL'));


    }

}

?>