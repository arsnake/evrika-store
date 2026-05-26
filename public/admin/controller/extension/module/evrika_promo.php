<?php
class ControllerExtensionModuleEvrikaPromo extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/evrika_promo');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('evrika_promo', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/evrika_promo', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/evrika_promo', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		require_once(DIR_TEMPLATE . '../../../catalog/view/theme/evrika/php/icons.php');
		$data['evrika_icons'] = evrika_get_icons();

		foreach (array(1, 2) as $n) {
			foreach (array('title', 'desc', 'icon') as $field) {
				$key = 'evrika_promo_banner' . $n . '_' . $field;
				if (isset($this->request->post[$key])) {
					$data[$key] = $this->request->post[$key];
				} else {
					$data[$key] = $this->config->get($key);
				}
			}
		}

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/evrika_promo', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/evrika_promo')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}
