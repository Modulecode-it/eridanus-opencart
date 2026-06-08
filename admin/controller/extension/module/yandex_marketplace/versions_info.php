<?php
require_once DIR_SYSTEM . 'library/yandex_beru/yandex_beru.php';

class ControllerExtensionModuleYandexMarketplaceVersionsInfo extends Controller {
	private $error = [];
	private $api;

	public function index() {
		$this->load->language('extension/module/yandex_marketplace');
		$this->document->setTitle($this->language->get('heading_title_versions'));
		$this->load->model('tool/image');
		$this->document->addStyle('view/stylesheet/yandex_beru.css');
		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_yandex_beru'),
			'href' => $this->url->link('extension/module/yandex_marketplace', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_versions'),
			'href' => $this->url->link('extension/module/yandex_marketplace/verions_info', 'user_token=' . $this->session->data['user_token'], true)
		);

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
        
        $data['user_token'] = $this->session->data['user_token'];

		$data['product_groups'] = array();

		$filter_data = array(
			'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           => $this->config->get('config_limit_admin')
		);

		//$group_total = $this->model_extension_module_yandex_beru->getTotalProductGroups();
		
// 		$pagination = new Pagination();
// 		$pagination->total = $group_total;
// 		$pagination->page = $page;
// 		$pagination->limit = $this->config->get('config_limit_admin');
// 		$pagination->url = $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);
// 
// 		$data['pagination'] = $pagination->render();
// 
// 		$data['results'] = sprintf($this->language->get('text_pagination'), ($group_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($group_total - $this->config->get('config_limit_admin'))) ? $group_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $group_total, ceil($group_total / $this->config->get('config_limit_admin')));
		
// 		$path = DIR_SYSTEM . "library/yandex_beru/changelog.txt";
// 		$changelog = file_get_contents($path);
// 		
// 		preg_match_all('/.+/ui', $changelog, $matches);
//     var_dump($matches);die;
//         preg_match_all('/[0-9]\D[0-9]\D[0-9]\D[0-9]/', $changelog, $versions);
        
        $path = DIR_SYSTEM . "library/yandex_beru/changelog.txt";
		$string = file_get_contents($path);
        
        $logs = explode("\n\n",$string);
        
        $data['versions_info'] = array();
        
        foreach($logs as $log){
            $lines = explode("\n",$log);
            $ver = $lines[0];
            unset($lines[0]);
            $lines = array_diff($lines, array(''));
            $data['versions_info'][] = array(
                'version'=> $ver,
                'line'=> '<ul><li>'.(implode("</li><li>",$lines)).'</li></ul>',
            );
        }
        
         //var_dump($data['versions_info']);
//         var_dump($data['lines']);
//         $string = "1.5.1.0
//     Добавлено логирование при отгрузке с выводом IP адрес вашего сервера, с которого отправляется запрос к API СДЭК, URL-адрес, на который был отправлен запрос, данные массива которые отправляются
//     Ответ сервера с заголовоком ответа сервера(200/404/500 и тд), тело ответа в читаемом виде, ошибки ответа
//     Во вкладка «получатель» добавлен поиск в select выбора пункта выдачи
//     Исправлена ошибка с картой при выборе постоматов
// 
// 1.5.0.2	
//     Переработано обновление городов в связи с ограничением получаемых городов за 1 запрос до 10000";
// $logs = explode("\n\n",$string);
// //var_dump($logs);
// foreach($logs as $log){
// 	$lines = explode("\n",$log);
// 	$ver = $lines[0];
// 	unset($lines[0]);
// 	var_dump($ver);
// 	var_dump(implode("\n",$lines));
// }
        
		if(!empty($data['check_products'])){

			$products_id_total = count($data['check_products']);
			$data['check_products'] = array_slice($data['check_products'], (($page_re - 1) * $limit_re), $limit_re);

			$pagination_re = new Pagination();
			$pagination_re->total = $products_id_total;
			$pagination_re->page = $page_re;
			$pagination_re->limit = $limit_re;
			
			$pagination_re->url = $this->url->link('extension/module/yandex_marketplace/product_group', 'user_token=' . $this->session->data['user_token'] . $url_groups . '&page_re={page}', true);

			$data['pagination_re'] = $pagination_re->render();

			$data['results_re'] = sprintf($this->language->get('text_pagination'), ($products_id_total) ? (($page_re - 1) * $limit_re) + 1 : 0, ((($page_re - 1) * $limit_re) > ($products_id_total - $limit_re)) ? $products_id_total : ((($page_re - 1) * $limit_re) + $limit_re), $products_id_total, ceil($products_id_total / $limit_re));
		}
    
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/yandex_marketplace/versions_info', $data));
	}
	
	public function modalVersionInfo() {
        
        if (isset($this->request->get['id'])) {
                $version_id = str_replace("version-", "", $this->request->get['id']);
            
                $path = DIR_SYSTEM . "library/yandex_beru/changelog.txt";
                $string = file_get_contents($path);
                $logs = explode("\n\n",$string);

                $data['versions_info'] = array();
                
                foreach($logs as $log){
                    $lines = explode("\n",$log);
                    $ver = $lines[0];
                    unset($lines[0]);
                    $lines = array_diff($lines, array(''));
                    $data['versions_info'][] = array(
                        'version'=> $ver,
                        'line'=> '<ul><li>'.(implode("</li><li>",$lines)).'</li></ul>',
                    );
                }
                
                $data['info'] = $data['versions_info'][(int)$version_id];
            }else{
                $path = DIR_SYSTEM . "library/yandex_beru/changelog.txt";
                $string = file_get_contents($path);
                $logs = explode("\n\n",$string);

                $data['versions_info'] = array();
                
                foreach($logs as $log){
                    $lines = explode("\n",$log);
                    $ver = $lines[0];
                    unset($lines[0]);
                    $lines = array_diff($lines, array(''));
                    $data['versions_info'][] = array(
                        'version'=> $ver,
                        'line'=> '<ul><li>'.(implode("</li><li>",$lines)).'</li></ul>',
                    );
                }
                $data['info'] = $data['versions_info'][0];
            }
        
        $this->load->language('extension/module/yandex_marketplace');
        $data['heading_title'] = $this->language->get('text_yandex_beru');
        $data['user_token'] = $this->session->data['user_token'];
        
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('yandex_beru_version');
    
        if(isset($this->request->get['setting_page'])){
            if(empty($settings) || $settings['yandex_beru_version_last_version'] != $data['versions_info'][0]['version']){
                $setting = array(
                    'yandex_beru_version_modal' => 1,
                    'yandex_beru_version_last_version' => $data['versions_info'][0]['version'],
                );
                $this->model_setting_setting->editSetting('yandex_beru_version', $setting); 
                $this->response->setOutput($this->load->view('extension/module/yandex_marketplace/version_modal', $data));
            }
        }else{
            $this->response->setOutput($this->load->view('extension/module/yandex_marketplace/version_modal', $data));
        }
	
    }
}
