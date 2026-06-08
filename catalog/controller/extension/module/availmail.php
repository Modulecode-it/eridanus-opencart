<?php
$this->load->model('tool/image');
/*Test*/
if(!empty($result)) {
    foreach ($result as $info) {

        // Якщо ввімкнено налаштування враховувати бажану кількість товару
        if($this->config->get('avail_quantity')){
            // Якщо кількість товару менша ніж бажана кількість - пропускаємо даний товар
            if($info['quantity'] < $info['desired_quantity']){
                continue;
            }
        }

        // если работаем по статусу товара и статус с настроек совпадает со статусом товара то отправлять
        if ($this->config->get('avail_options_status') == 2 && in_array($info['stock_status_id'], $this->config->get('avail_notify_status'))) {
            $flag = 0;
            // echo 'test1';
        } elseif ($this->config->get('avail_options_status') <> 2) {    //если работаем не по статусу то всегда отправлять
            // echo 'test2';
            $flag = 0;
        } else {
            $flag = 1;    // не отправлять (то есть только если статус не совпадает со статусом с настроек)
        }
        if($flag == 0){
            $product = $this->model_catalog_product->getProduct($info['product_id']);

            $product_price = $this->model_extension_module_avail->getPrice($info['product_id']);

            if (!$product_price['special']) {
                $price = $this->currency->format($product['price'], $this->config->get('config_currency'));
            } else {
                $price = $this->currency->format($product_price['special'], $this->config->get('config_currency'));
            }

            /* заглавие */
            $subject = strip_tags(html_entity_decode($messages[$info['language_id']]['notification_title'], ENT_QUOTES, 'UTF-8'));

            //   $message = strip_tags(html_entity_decode($messages[$info['language_id']]));

            if(!empty($cron_work) && ($cron_work == 1)){
                $link = 'index.php?route=product/product&product_id=' . $info['product_id'];
            } else {
                $link = HTTP_CATALOG . 'index.php?route=product/product&product_id=' . $info['product_id'];
            }

            if ($product['image']) {
                $popup = $this->model_tool_image->resize($product['image'],  $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
            } else {
                $popup = '';
            }

            if ($product['image']) {
                $thumb = $this->model_tool_image->resize($product['image'],$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));

            } else {
                $thumb = '';
            }

            $search_data = array('%name%', '%product_name%', '%price%', '%link%', '%option_type%', '%option_name%','%model%','%sku%','%thumb%','%popup%');
            $replace_data = array($info['name'], $info['product'], $price, $link, '', '', $product['model'], $product['sku'], $thumb, $popup);

            if (strlen($subject) > 1) {
                $mail_subject = htmlspecialchars_decode($subject);
                $mail_subject = str_replace($search_data, $replace_data, $mail_subject);
            } else {
                $mail_subject = $this->lenguage->get('mail_subject');
            }
//echo strlen($message);
            if (!empty($messages[$info['language_id']]['notification_message']) && strlen($messages[$info['language_id']]['notification_message']) > 1) {

                $mail_text = htmlspecialchars_decode($messages[$info['language_id']]['notification_message']);
                $mail_text = str_replace($search_data, $replace_data, $mail_text);

                // Для довільних полів

                // Перевіряємо чи у масиві POST існують дані довільних полів і користувач поставив у поле message шорткоди
                if (!empty($info['arbitrary_fields']) && strlen($info['arbitrary_fields']) > 1) {
                    // Беремо інформацію яку ввів користувач у довільні поля
                    $arbitrary_data_text_post_input = unserialize($info['arbitrary_fields']);
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
                    $mail_text = str_replace($arbitrary_data_post_search, $arbitrary_data_text_post, $mail_text);
                }




            } else {
                $mail_text = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Document</title></head><body>";
                $mail_text .= "<p>" . html_entity_decode($info['name'] . ', ' . $this->language->get('text_mail_send') . "</p>", ENT_QUOTES, 'UTF-8');
                $mail_text .= "<p>" . $this->language->get('text_product') . ': ' . $info['product'] . "</p>";
                $mail_text .= "<p>" . $this->language->get('text_link_page') . ": " . " <a href=" . $link . ">" . $info['product'] . "</a></p>";
                $mail_text .= "<p>" . $this->language->get('text_price') . ': ' . $price . "</p></body></html>";
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
            $mail->setSubject(html_entity_decode($mail_subject, ENT_QUOTES, 'UTF-8'));

            $mail->setHtml($mail_text);
            $mail->send();
            $this->model_extension_module_avail->changeMailStatus($info['id'], 1);
            $success = $this->language->get('success');
        }

    }

}

if (!empty($result_options)){

    $option_types = '';
    $option_names = '';
    foreach ($result_options as $info ) {
        // Якщо ввімкнено налаштування враховувати бажану кількість товару
        if($this->config->get('avail_quantity')){
            // Якщо кількість товару менша ніж бажана кількість - пропускаємо даний товар
            if($info['quantity'] < $info['desired_quantity']){
                continue;
            }
        }
        $option_price = 0;
        $option_points = 0;
        $option_weight = 0;
        // данные по товару
        $product = $this->model_catalog_product->getProduct($info['product_id']);
        // данные по скидке
        $product_price = $this->model_extension_module_avail->getPrice($info['product_id']);
        // данные по опциям с заявок
        $options_notify = $this->model_extension_module_avail->OptionBuyProduct($info['product_id'], $info['id']);

        // получаем данные по опциям товара
        $data['options'] = array();
        // получаем данные по всем опциям и перебираем их
        foreach ($this->model_catalog_product->getProductOptions($info['product_id']) as $option) {
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
        if (!$product_price['special']) {
            $price = $product['price'];
        } else {
            $price = $product_price['special'];
        }
// если стоимость опций больше 0 то добавляее до общей стоимости товара
        if($option_price > 0){
            $price = $price +  $option_price;
        } else {
            $price = $price;
        }

        $price = $this->currency->format($this->tax->calculate($price, $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

        $flag = 0;
        if($notifi_for_admin == 1) {
            if($this->config->get('config_secure')==0){
                $catalog = HTTP_CATALOG;
            } else {
                $catalog = HTTPS_CATALOG;
            }
        } else {
            if($this->config->get('config_secure')==0){
                $catalog = HTTP_SERVER;
            } else {
                $catalog = HTTPS_SERVER;
            }
        }
        $link = $catalog . 'index.php?route=product/product&product_id='. $info['product_id'];
        /*масив данными по товаре и его опциях по заявке*/


        $info['options'] = array();
        array_push($info['options'], $options_notify);
        // если модуль работает по количеству опций
        if ($this->config->get('avail_options_status') == 1){
            foreach ($info['options'] as $index => $arr ) {
                foreach($arr as $option) {
                    if($option['option_quantity'] <= 0) {
                        $flag = 1;
                    }
                }
            }
        } elseif ($this->config->get('avail_options_status') == 2){    // если модуль работает по статусу товара
            if (in_array($info['stock_status_id'], $this->config->get('avail_notify_status'))) {
                $flag = 0;   //   если статус с настроек совпадает с реальным статусом то отправляем уведомление
                // echo 'test1';
            } else {
                // echo 'test2';
                $flag = 1;
            }
        } else {
            if ($info['quantity'] <= 0){
                $flag = 1;
            }

        }

        if($flag == 0) {
            foreach($info['options'] as $index => $arr ) {
                $option_names = '';
                $i =0;
                if($i == 0){ $mark = " ";}else{ $mark = " , ";}
                foreach($arr as $option) {
//$option_types .= "<p>" . $option['option_type'] . "</p>";
                    $option_names .=  $mark . "" . $option['option_type'] . " - ". $option['option_name']. "<br>" ;
                    $i++;}
            }

            if ($product['image']) {
                $popup = $this->model_tool_image->resize($product['image'],  $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
            } else {
                $popup = '';
            }

            if ($product['image']) {
                $thumb = $this->model_tool_image->resize($product['image'],$this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));

            } else {
                $thumb = '';
            }


            $search_data = array('%name%', '%product_name%', '%price%', '%link%', '%option_type%', '%option_name%','%model%','%sku%','%thumb%','%popup%');
            $replace_data = array($info['name'], $info['product'], $price, $link, '', $option_names, $product['model'], $product['sku'], $thumb, $popup);


            /* заглавие */
            $subject = strip_tags(html_entity_decode($messages[$info['language_id']]['notification_title'], ENT_QUOTES, 'UTF-8'));
            // $message = strip_tags(html_entity_decode($messages[$info['language_id']]['notification_message']));

            if (strlen($subject) > 1) {
                $mail_subject = htmlspecialchars_decode($subject);
                $mail_subject = str_replace($search_data, $replace_data, $mail_subject);


            } else {
                $mail_subject = $this->lenguage->get('mail_subject');
            }

            if (!empty($messages[$info['language_id']]['notification_message']) && strlen($messages[$info['language_id']]['notification_message']) > 1) {

                $mail_text = htmlspecialchars_decode($messages[$info['language_id']]['notification_message']);
                $mail_text = str_replace($search_data, $replace_data, $mail_text);

                // Для довільних полів

                // Перевіряємо чи у масиві POST існують дані довільних полів і користувач поставив у поле message шорткоди
                if (!empty($info['arbitrary_fields']) && strlen($info['arbitrary_fields']) > 1) {
                    // Беремо інформацію яку ввів користувач у довільні поля
                    $arbitrary_data_text_post_input = unserialize($info['arbitrary_fields']);
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
                    $mail_text = str_replace($arbitrary_data_post_search, $arbitrary_data_text_post, $mail_text);
                }

            } else {
                $mail_text = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Document</title></head><body>";
                $mail_text .="<p>" . html_entity_decode($info['name'].', '.$this->language->get('text_mail_send'). "</p>", ENT_QUOTES, 'UTF-8');
                $mail_text .= "<p>" . $this->language->get('text_product') .': ' . $info['product'] . "</p>";
                $mail_text .= "<p>" . $this->language->get('text_link_page') . ": " . " <a href=" . $link . ">" . $info['product'] . "</a></p>";
                $mail_text .= "<p>" . $this->language->get('text_price') . ': ' . $price . "</p>";
                foreach($info['options'] as $index => $arr ) {
                    foreach($arr as $option) {
                        $mail_text .= "<p>" . $option['option_type'] . ' - ' . $option['option_name'] . "</p>";
                    }
                }
                $mail_text .= "</body></html>";
            }
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

            $mail->setTo($info['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($messages[$info['language_id']]['sender']);
            $mail->setSubject(html_entity_decode($mail_subject, ENT_QUOTES, 'UTF-8'));

            $mail->setHtml($mail_text);

            $mail->send();
            $this->model_extension_module_avail->changeMailStatus($info['id'], 1);
            $success_options = $this->language->get('success');
        }

    }

}

?>