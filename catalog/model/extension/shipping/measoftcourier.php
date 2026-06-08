<?php
class ModelExtensionShippingMeasoftcourier extends Model
{
    public function getQuote($address)
    {
        $method_data = [];
		$disbaled_list = $this->config->get('shipping_measoftcourier_product_store');
		$disbaled_list = $disbaled_list ?? [];
		if (in_array($this->config->get('config_store_id'), $disbaled_list)) return $method_data;

        $this->load->language('extension/shipping/measoftcourier');

        if (!isset($address['zone_id'])) {
            $address['zone_id'] = '';
        }
        if (!isset($address['country_id'])) {
            $address['country_id'] = '';
        }

        require_once(DIR_SYSTEM.'library/measoft/measoftcourier.class.php');

        $measoft = new Measoft(
            $this->config->get('shipping_measoftcourier_login'),
            $this->config->get('shipping_measoftcourier_password'),
            $this->config->get('shipping_measoftcourier_extra'),
			$this->language->get('code')
        );

        if (isset($address['city']) && $address['city']) {
            $town = $address['city'];
        } else {
            $town_list = $measoft->getTownList([
                'zipcode' => $address['postcode'],
            ]);
            $town = !empty($town_list) ? $town_list[0]['name'] : $address['zone'];
        }
		
		$payment_method_code = (isset($this->session->data['payment_method']['code']))?$this->session->data['payment_method']['code']:'';		
		$paytype = $measoft->fetchPaytype($payment_method_code, $this->config->get('shipping_measoftcourier_payment_cash'),  $this->config->get('shipping_measoftcourier_payment_card'), $this->config->get('shipping_measoftcourier_payment_none'));	
		

        $weight = $this->getWeight();;		
		$packages = $measoft->preparePackages($this->cart->getProducts());
        $result = $measoft->calculatorRequest([
            'townto' => $town,
            'townfrom' => $this->config->get('shipping_measoftcourier_city'),
            'mass' => $weight,
            'zipcode' => isset($address['postcode']) ? $address['postcode'] : '',
			'address' => $address['address_1'],
			'packages' => $packages,
			'paytype' => $paytype,
        ]);

        if ($result) {
            $cost = (double)$result->price
                * $this->config->get('shipping_measoftcourier_shipping_rate')
                + $this->config->get('shipping_measoftcourier_shipping_add_sum');
            $interval = '';
            if ($result->mindeliverydays || $result->maxdeliverydays) {
                $days = array();
                if ($result->mindeliverydays) {
                    $days[] = (int)$result->mindeliverydays;
                }
                if ($result->maxdeliverydays && (int)$result->maxdeliverydays != (int)$result->mindeliverydays) {
                    $days[] = (int)$result->maxdeliverydays;
                }

                $days_list = explode('|', $this->language->get('text_shipping_days'));

                $max_days = $days[count($days)-1];
                $interval = '';
                if ($max_days > 4) {
                    $days_text = $days_list[2];
                    $interval = ' ('.implode('-', $days).' '.$days_text.')';
                } elseif ($max_days > 1) {
                    $days_text = $days_list[1];
                    $interval = ' ('.implode('-', $days).' '.$days_text.')';
                } else {
                    $days_text = $days_list[0];
                }
            }

            if ($weight) {
                $ksProductWeight = $weight;
            } elseif ($this->config->get('measoftshipping_weight')) {
                $ksProductWeight = $this->config->get('measoftshipping_weight');
            } else {
                $ksProductWeight = 0.1;
            }


            $title = $this->config->get('shipping_measoftcourier_courier_description')."<input type='hidden' name='ksProductWeight' id='ksProductWeight' value='".$ksProductWeight."' />";
            //При запросе из админки
            if (isset($this->session->data['api_id']) && isset($this->request->get['order_id'])) {
                $title = $this->config->get('shipping_measoftcourier_courier_description');
            }

            $method_data = array(
                'code'       => 'measoftcourier',
                'title'      => $this->config->get('shipping_measoftcourier_courier_title').$interval,
                'quote'      => array('standard' => array(
                    'code'         => 'measoftcourier.standard',
                    'title'        => $title,
                    'cost'         => $cost,
                    'tax_class_id' => $this->config->get('shipping_measoftcourier_tax_class_id'),
                    'sort_order'   => $this->config->get('shipping_measoftcourier_sort_order'),
                    'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_measoftcourier_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']),
                )),
                'sort_order' => $this->config->get('shipping_measoftcourier_sort_order'),
                'error'      => false,
            );
        }

        return $method_data;
    }
	
	private function getWeight() 
	{
        $weight = $this->cart->getWeight();
		if (!$weight) {
			$weight = 0.1;
		}
		
		if($this->config->get('config_weight_class_id')==2){
			$weight=$weight/1000;
		}
		
		return round($weight, 2);
	}
	
}
