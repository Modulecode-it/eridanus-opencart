<?php

require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceOfferBinding extends Controller {
	private $error = '';

	public function index() {

        $this->load->model('extension/module/yandex_beru');
        $this->load->language('extension/module/yandex_marketplace');

        $this->document->addStyle('view/stylesheet/yandex_beru.css');

        $data['user_token']     = $this->session->data['user_token'];
        $data['product_row']    = 0;


        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/yandex_marketplace/offer_binding', $data));

    }

    public function options() {

        $this->load->model('extension/module/yandex_beru');
        $this->load->model('catalog/option');
        
        $json = array();


        if (!empty($this->request->post['product_id'])){

            $results = $this->model_extension_module_yandex_beru->getPrimaryProductoptions($this->request->post['product_id']);

            foreach ($results as $result) {

                $option_values = array();

                foreach ($result['option_values'] as $value) {

                    $option_value_info = $this->model_catalog_option->getOptionValue($value);

                    $option_values[] = array(
                        'option_value_id'   => $value,
                        'name'              => $option_value_info['name'],
                        
                    );

                }

                $option = $this->model_catalog_option->getOption($result['option_id']);

                $json['options'][] = array(
                    'option_id'     => $option['option_id'],
                    'name'          => $option['name'],
                    'option_values' => $option_values
                );
               
            }

        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

    }

    public function checkOffers(){

        $this->api = new yandex_beru();
        $this->api->setAuth($this->config->get('yandex_beru_oauth'),$this->config->get('yandex_beru_auth_token'),$this->config->get('yandex_beru_company_id'));
        
		$component = $this->api->loadComponent('offerMappingEntries');

        function cmp($a, $b){
            return strcmp($a["option_id"], $b["option_id"]);
        }

        $this->load->language('extension/module/yandex_marketplace');
        $this->load->model('extension/module/yandex_beru');

        $error = array();

        foreach ($this->request->post['product'] as $key => $product) {
            
            if(empty($product['product_id'])){
                $error[$key]['product_id'] = $this->language->get('error_binding_product');
            } 

            if(empty($product['shopSku'])){
                $error[$key]['shopSku'] = $this->language->get('error_binding_shopSku');
            } else {

                $result_shop = $this->model_extension_module_yandex_beru->getOffer($product['shopSku']);

                if(!empty($result_shop)){

                    $error[$key]['dublicate_shop'] = $this->language->get('error_binding_dublicate_shop');
    
                }

            }

            if(empty($product['yandex_sku'])){
                $error[$key]['yandex_sku'] = $this->language->get('error_binding_yandex_sku');
            } 

            $shopSKU[$key] = $product['product_id'];

            if(!empty($product['options'])){

                usort($product['options'], "cmp"); 

                foreach ($product['options'] as $option) {
  
                    if($option['option_value'] == 0){
                        $error[$key]['option'][$option['option_id']] = $this->language->get('error_binding_option');
                    } 
                    $shopSKU[$key] .= '-' . $option['option_id'] . '-' . $option['option_value'];

                }

            }
         
            $result_key = $this->model_extension_module_yandex_beru->getOfferByKey($shopSKU[$key]);

            if(!empty($result_key)){

                $error[$key]['dublicate_key'] = $this->language->get('error_binding_dublicate_key');

            }

            if(empty($error[$key])){

                $push_data = array(
                    'shop_sku'     => $product['shopSku'],
                );

                $component->setData($push_data);
			
                $response = $this->api->sendData($component); 

                if(is_array($response)){//верные данные всегда массив, ошибки строка.

                    if(!empty($response['result']['offerMappingEntries']['0']['offer'])){

                        $offer = $response['result']['offerMappingEntries']['0'];

                        if(!empty($offer['mapping']['marketSku'])){

                            if($offer['mapping']['marketSku'] != $product['yandex_sku']){
    
                                $error[$key]['checkMarket'] = $this->language->get('error_binding_checkMarket_marketSKU');    
        
                            } else {
        
                                $data = array(
                                    'key'           => $shopSKU[$key],
                                    'marketSku'     => $product['yandex_sku'],
                                    'shopSku'       => $product['shopSku'],
                                    'marketSkuName' => $offer['offer']['name'],
                                );
        
                                $this->model_extension_module_yandex_beru->addOffer($data);
        
                                $this->model_extension_module_yandex_beru->updateOfferStatus($product['shopSku'], $offer['offer']['processingState']['status']);
        
                                $error[$key]['success'] = 1;
        
                            }

                        } else {

                            if(isset($offer['awaitingModerationMapping']['marketSku']) && $offer['awaitingModerationMapping']['marketSku'] != $product['yandex_sku']){
    
                                $error[$key]['checkMarket'] = $this->language->get('error_binding_checkMarket_marketSKU');    
        
                            } else {
        
                                $data = array(
                                    'key'           => $shopSKU[$key],
                                    'marketSku'     => $product['yandex_sku'],
                                    'shopSku'       => $product['shopSku'],
                                    'marketSkuName' => $offer['offer']['name'],
                                );
        
                                $this->model_extension_module_yandex_beru->addOffer($data);
        
                                $this->model_extension_module_yandex_beru->updateOfferStatus($product['shopSku'], $offer['offer']['processingState']['status']);
        
                                $error[$key]['success'] = 1;
        
                            }

                        }
        
                    } else {
    
                        $error[$key]['checkMarket'] = $this->language->get("error_binding_checkMarket_shopSKU");
    
                    }

                } else {
    
                    $code_error = array("PARTIAL_CONTENT", "BAD_REQUEST", "UNAUTHORIZED", "FORBIDDEN", "NOT_FOUND", "METHOD_NOT_ALLOWED", "UNSUPPORTED_MEDIA_TYPE", "ENHANCE_YOUR_CALM", "INTERNAL_SERVER_ERROR","SERVICE_UNAVAILABLE", "UNKNOWN_ERROR");
                    $text_code_error = array(
                        $this->language->get('error_PARTIAL_CONTENT'),
                        $this->language->get('error_BAD_REQUEST'),
                        $this->language->get('error_UNAUTHORIZED'),
                        $this->language->get('error_FORBIDDEN'),
                        $this->language->get('error_NOT_FOUND'),
                        $this->language->get('error_METHOD_NOT_ALLOWED'),
                        $this->language->get('error_UNSUPPORTED_MEDIA_TYPE'),
                        $this->language->get('error_ENHANCE_YOUR_CALM'),
                        $this->language->get('error_INTERNAL_SERVER_ERROR'),
                        $this->language->get('error_SERVICE_UNAVAILABLE'),
                        $this->language->get('error_UNKNOWN_ERROR'),
                    );
    
                    $error['global'] = str_replace($code_error, $text_code_error, $response);
    
                }

            }

        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($error));

    }

}
