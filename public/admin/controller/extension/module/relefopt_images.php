<?php
/**
 * Evrika — РельефОпт Image Parser
 * Парсер изображений товара с сайта relefopt.ru
 */
class ControllerExtensionModuleRelefoptImages extends Controller {

    public function index() {
        $this->load->model('setting/setting');
        $this->document->setTitle('РельефОпт — Парсер изображений');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_setting_setting->editSetting('relefopt_images', $this->request->post);
            $this->session->data['success'] = 'Настройки сохранены!';
            $this->response->redirect($this->url->link('extension/module/relefopt_images',
                'user_token=' . $this->session->data['user_token'], true));
        }

        $settings = $this->model_setting_setting->getSetting('relefopt_images');

        $data['relefopt_token']         = $settings['relefopt_images_token'] ?? '';
        $data['relefopt_client_id']     = $settings['relefopt_images_client_id'] ?? 'relefopt';
        $data['relefopt_client_secret'] = $settings['relefopt_images_client_secret'] ?? '';

        $data['success'] = $this->session->data['success'] ?? '';
        unset($this->session->data['success']);

        $data['user_token']  = $this->session->data['user_token'];
        $data['action']      = $this->url->link('extension/module/relefopt_images',
            'user_token=' . $this->session->data['user_token'], true);
        $data['cancel']      = $this->url->link('marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/relefopt_images', $data));
    }

    /** AJAX: поиск товаров по коду на РельефОпт */
    public function search() {
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->isLogged()) {
            $this->response->setOutput(json_encode(['error' => 'Не авторизован']));
            return;
        }

        $code = trim($this->request->post['code'] ?? '');
        if (!$code) {
            $this->response->setOutput(json_encode(['error' => 'Не указан код товара']));
            return;
        }

        $this->load->model('extension/module/relefopt_images');
        $token = $this->model_extension_module_relefopt_images->getToken();

        if (!$token) {
            $url = $this->url->link('extension/module/relefopt_images',
                'user_token=' . $this->session->data['user_token'], true);
            $this->response->setOutput(json_encode([
                'error' => 'Токен не настроен или просрочен. <a href="' . $url . '" target="_blank">Откройте настройки модуля</a>.'
            ]));
            return;
        }

        $result = $this->model_extension_module_relefopt_images->searchProducts($token, $code);
        $this->response->setOutput(json_encode($result));
    }

    /** AJAX: скачать одно изображение, сохранить в OcStore, вернуть путь */
    public function importImage() {
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->isLogged()) {
            $this->response->setOutput(json_encode(['error' => 'Не авторизован']));
            return;
        }

        $url        = trim($this->request->post['url'] ?? '');
        $sort_order = (int)($this->request->post['sort_order'] ?? 0);

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->response->setOutput(json_encode(['error' => 'Некорректный URL изображения']));
            return;
        }

        $this->load->model('extension/module/relefopt_images');
        $result = $this->model_extension_module_relefopt_images->downloadImage($url, $sort_order);
        $this->response->setOutput(json_encode($result));
    }

    public function install() {}
    public function uninstall() {}
}
