<?php
class ControllerExtensionDashboardAvail extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/dashboard/avail');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('dashboard_avail', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/dashboard/avail', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/dashboard/avail', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true);

        if (isset($this->request->post['dashboard_avail_width'])) {
            $data['dashboard_avail_width'] = $this->request->post['dashboard_avail_width'];
        } else {
            $data['dashboard_avail_width'] = $this->config->get('dashboard_avail_width');
        }

        $data['columns'] = array();

        for ($i = 3; $i <= 12; $i++) {
            $data['columns'][] = $i;
        }

        if (isset($this->request->post['dashboard_avail_status'])) {
            $data['dashboard_avail_status'] = $this->request->post['dashboard_avail_status'];
        } else {
            $data['dashboard_avail_status'] = $this->config->get('dashboard_avail_status');
        }

        if (isset($this->request->post['dashboard_avail_sort_avail'])) {
            $data['dashboard_avail_sort_avail'] = $this->request->post['dashboard_avail_sort_avail'];
        } else {
            $data['dashboard_avail_sort_avail'] = $this->config->get('dashboard_avail_sort_avail');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/dashboard/avail_form', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/dashboard/avail')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function dashboard() {
        $this->load->language('extension/dashboard/avail');

        $data['user_token'] = $this->session->data['user_token'];
        $data['text_view'] = $this->language->get('text_view');
        $data['text_total_open'] = $this->language->get('text_total_open');
        $data['text_total_all'] = $this->language->get('text_total_all');
        // Total avails
        $this->load->model('extension/module/avail');

        // Total Orders
        $this->load->model('extension/module/avail');

        $order_total_open = $this->model_extension_module_avail->countAllAvailByStatus();


        if ($order_total_open > 0) {
            $data['total_open'] = $order_total_open;
        } else {
            $data['total_open'] = 0;
        }

        $order_total_all = $this->model_extension_module_avail->getTotalAvail();


        if ($order_total_all > 0) {
            $data['total_all'] = $order_total_all;
        } else {
            $data['total_all'] = 0;
        }

           


        $data['online'] = $this->url->link('extension/module/avail/getAvailabilityList', 'user_token=' . $this->session->data['user_token'], true);

        return $this->load->view('extension/dashboard/avail_info', $data);
    }
}
