<?php
class ControllerExtensionShippingMeasoft extends Controller {

	public function getSettings() {

		$measoftshipping_client_id = $this->config->get('shipping_measoftcourier_extra') ? $this->config->get('shipping_measoftcourier_extra') : '';
		$measoftshipping_client_code = $this->config->get('shipping_measoftcourier_client_code') ? $this->config->get('shipping_measoftcourier_client_code') : '';
		$measoftshipping_weight = $this->config->get('shipping_measoftcourier_order_default_weight') ? $this->config->get('shipping_measoftcourier_order_default_weight') : 0.1;
		$measoftshipping_map_width = $this->config->get('shipping_measoftcourier_map_width') ? $this->config->get('shipping_measoftcourier_map_width') : 650;
		$measoftshipping_map_height = $this->config->get('shipping_measoftcourier_map_height') ? $this->config->get('shipping_measoftcourier_map_height') : 755;

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode(array(
			'id'	=> $measoftshipping_client_id,
			'code'	=> $measoftshipping_client_code,
			'default_weight'	=> $measoftshipping_weight,
			'width'	=> $measoftshipping_map_width,
			'height'	=> $measoftshipping_map_height,
			'weight'	=> $this->getWeight()
		)));
	}

	public function quote() 
	{

		$quoted = [];
		$this->load->language('extension/shipping/measoftshipping');
		$this->load->language('extension/shipping/measoftcouriershipping');
		$quoted['empty'] = $this->language->get('no_tariff');

		if (isset($this->request->post['pvzid']) && isset($this->request->post['city'])) {

			require_once(DIR_SYSTEM.'library/measoft/measoftcourier.class.php');

			$measoft = new Measoft(
				$this->config->get('shipping_measoftcourier_login'),
				$this->config->get('shipping_measoftcourier_password'),
				$this->config->get('shipping_measoftcourier_extra'),				
				$this->language->get('code')
			);
	
			$townfrom = $this->config->get('shipping_measoftcourier_city');
			if ($this->config->get('shipping_measoftcourier_city_code')) {
				$townfrom = $this->config->get('shipping_measoftcourier_city_code');	
			}
			
			$pvz = $this->request->post['pvzid'];

			$townto = $measoft->getCityCode($pvz);
			if (!$townto) {
				$townto = $this->request->post['city'];
			}

			$zipcode = isset($this->request->post['zipcode']) ? $this->request->post['zipcode'] : '';
			$zipcode = '';			

			$weight = $this->getWeight();
			$packages = $measoft->preparePackages($this->cart->getProducts());
			$result = $measoft->calculatorRequest([
				'townto' => $townto,
				'townfrom' => $townfrom,
				'mass' => $weight,
				'zipcode' => $zipcode,
				'pvzid'	=> $pvz,
				'packages' => $packages,
			]);
			$cost = 0;
			if ($result) {
				$cost = (double)$result->price
                * $this->config->get('shipping_measoftcourier_shipping_rate')
                + $this->config->get('shipping_measoftcourier_shipping_add_sum');

				$quoted['cost'] = $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_measoftcourier_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']);
			}			
			$this->setSession($weight, $cost);

		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($quoted));

	}

	private function setSession($weight,$cost) 
	{
		$this->load->language('shipping/measoftshipping');
		$html = "<input type='hidden' name='pvz_id' class='pvzcode'><input type='hidden' name='pvz_name' id='pvzname' placeholder='Нажмите кнопку справа для выбора ПВЗ'>";
		$html .= "<input type='hidden' readonly name='pvz_city' id='pvz_city'>";
		// $html .= "<button type='button' id='ks2008_clean_pvz' class='btn clearPvz' title='Очистить ПВЗ'><img style='width:10px' src='/admin/view/image/measoftcourier/cross.png'></button>";
		$ksProductWeight = $weight < $this->config->get('shipping_measoftcourier_order_default_weight') ? $this->config->get('shipping_measoftcourier_order_default_weight') : $weight;
		$formated_cost = $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_measoftcourier_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']) . $this->language->get('choose_another_point');
		
		$this->session->data['pvz_id'] = $this->request->post['pvzid'];
		$this->session->data['pvz_cost'] = $cost;
		$this->session->data['cart_weight'] = $weight;
		$this->session->data['pvz_name'] = $this->request->post['pvzname'];
		$this->session->data['pvz_acceptcash'] = $this->request->post['pvz_acceptcash'];
		$this->session->data['pvz_acceptcard'] = $this->request->post['pvz_acceptcard'];
		$this->session->data['shipping_method']['code'] = 'measoftcouriershipping.standard';
		$this->session->data['shipping_methods']['measoftcouriershipping']['quote']['standard'] = array(
			'code'         => 'measoftcouriershipping.standard',
			'title'        => $this->request->post['pvzname'],
			'title_html'   => '<span id="mea_description" onclick="showModalMea()">'. $formated_cost . "</span><input type='hidden' name='ksProductWeight' id='ksProductWeight' value='".$ksProductWeight."' />".$html,
			'cost'         => $cost,
			'tax_class_id' => $this->config->get('shipping_measoftcourier_tax_class_id'),
			'sort_order'   => $this->config->get('shipping_measoftcourier_sort_order'),
			'text'         => ''
		);
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