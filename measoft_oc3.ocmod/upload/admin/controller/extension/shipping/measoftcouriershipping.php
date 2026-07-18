<?php
class ControllerExtensionShippingMeasoftcouriershipping extends Controller
{
    private $version = '4.3.1';
    private $error = array();

    public function index()
    {
        $this->load->language('extension/shipping/measoftcouriershipping');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_measoftcouriershipping', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['entry_tax_class'] = $this->language->get('entry_tax_class');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_none'] = $this->language->get('text_none');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['entry_services'] = $this->language->get('entry_services');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
            );
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('extension/shipping/measoftcouriershipping', 'user_token=' . $this->session->data['user_token'], true)
            );

        $data['action'] = $this->url->link('extension/shipping/measoftcouriershipping', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

        if (isset($this->request->post['shipping_measoftcouriershipping_tax_class_id'])) {
            $data['shipping_measoftcouriershipping_tax_class_id'] = $this->request->post['shipping_measoftcouriershipping_tax_class_id'];
        } else {
            $data['shipping_measoftcouriershipping_tax_class_id'] = $this->config->get('shipping_measoftcouriershipping_tax_class_id');
        }
        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        if (isset($this->request->post['shipping_measoftcouriershipping_geo_zone_id'])) {
            $data['shipping_measoftcouriershipping_geo_zone_id'] = $this->request->post['shipping_measoftcouriershipping_geo_zone_id'];
        } else {
            $data['shipping_measoftcouriershipping_geo_zone_id'] = $this->config->get('shipping_measoftcouriershipping_geo_zone_id');
        }
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        require_once(DIR_SYSTEM.'library/measoft/measoftcourier.class.php');

        $measoft = new Measoft(
            $this->config->get('shipping_measoftcourier_login'),
            $this->config->get('shipping_measoftcourier_password'),
            $this->config->get('shipping_measoftcourier_extra'),			
			$this->language->get('code')
        );
        $response = $measoft->getServiceList();
        $response = json_decode(json_encode($response), true);

        if (isset($this->request->post['shipping_measoftcouriershipping_status'])) {
            $data['shipping_measoftcouriershipping_status'] = $this->request->post['shipping_measoftcouriershipping_status'];
        } else {
            $data['shipping_measoftcouriershipping_status'] = $this->config->get('shipping_measoftcouriershipping_status');
        }

        if (isset($this->request->post['shipping_measoftcouriershipping_sort_order'])) {
            $data['shipping_measoftcouriershipping_sort_order'] = $this->request->post['shipping_measoftcouriershipping_sort_order'];
        } else {
            $data['shipping_measoftcouriershipping_sort_order'] = $this->config->get('shipping_measoftcouriershipping_sort_order');
        }

        if (isset($this->request->post['shipping_measoftcouriershipping_services_delivery'])) {
            $data['shipping_measoftcouriershipping_services_delivery'] = $this->request->post['shipping_measoftcouriershipping_services_delivery'];
        } else {
            $data['shipping_measoftcouriershipping_services_delivery'] = $this->config->get('shipping_measoftcouriershipping_services_delivery');
        }

        $data['services'] = $response['service'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/measoftcouriershipping', $data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/shipping/measoftcouriershipping')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
