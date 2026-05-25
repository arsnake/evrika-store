<?php
/**
 * Evrika Auto-Register controller
 * Handles checkout-side quick registration (no password input from user).
 */
class ControllerCheckoutAutoRegister extends Controller {

    /**
     * POST save() — сохраняет данные регистранта в сессию.
     * Вызывается из шага 1 по клику "Продолжить".
     */
    public function save() {
        $json = array();

        if ($this->customer->isLogged()) {
            $json['success'] = true;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $firstname = trim(strip_tags($this->request->post['firstname'] ?? ''));
        $lastname  = trim(strip_tags($this->request->post['lastname']  ?? ''));
        $email     = trim($this->request->post['email']     ?? '');
        $telephone = trim(strip_tags($this->request->post['telephone'] ?? ''));

        if (empty($firstname)) {
            $json['error']['firstname'] = 'Введите имя';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $json['error']['email'] = 'Введите корректный e-mail';
        }

        if (!$json) {
            $this->session->data['evrika_registrant'] = array(
                'firstname' => $firstname,
                'lastname'  => $lastname,
                'email'     => $email,
                'telephone' => $telephone,
            );

            // Также заполняем session['guest'] чтобы confirm.php мог создать заказ
            $this->session->data['guest'] = array(
                'customer_group_id' => $this->config->get('config_customer_group_id'),
                'firstname'         => $firstname,
                'lastname'          => $lastname,
                'email'             => $email,
                'telephone'         => $telephone,
                'custom_field'      => array(),
            );

            // payment_address fallback (нужен confirm.php)
            if (empty($this->session->data['payment_address'])) {
                $this->session->data['payment_address'] = array(
                    'firstname'      => $firstname,
                    'lastname'       => $lastname,
                    'company'        => '',
                    'address_1'      => '',
                    'address_2'      => '',
                    'city'           => '',
                    'postcode'       => '',
                    'zone'           => '',
                    'zone_id'        => (int)$this->config->get('config_zone_id'),
                    'country'        => '',
                    'country_id'     => (int)$this->config->get('config_country_id'),
                    'address_format' => '',
                );
            }

            $json['success'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * POST register() — создаёт аккаунт, логинит, отправляет пароль на почту.
     * Вызывается между шагом 4 и загрузкой confirm (шаг 5).
     */
    public function register() {
        $json = array();

        if ($this->customer->isLogged()) {
            $json['success'] = true;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if (empty($this->session->data['evrika_registrant'])) {
            $json['error'] = 'Нет данных для регистрации';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $reg = $this->session->data['evrika_registrant'];

        $this->load->model('account/customer');

        // Email уже зарегистрирован — просто пропускаем, гостевой заказ
        if ($this->model_account_customer->getTotalCustomersByEmail($reg['email'])) {
            $json['success'] = true;
            $json['exists']  = true;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Генерируем пароль: 8 символов
        $password = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 8);

        $customer_data = array(
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'firstname'         => $reg['firstname'],
            'lastname'          => $reg['lastname'],
            'email'             => $reg['email'],
            'telephone'         => $reg['telephone'],
            'password'          => $password,
            'newsletter'        => 0,
        );

        $this->model_account_customer->addCustomer($customer_data);

        // Логиним пользователя
        $this->customer->login($reg['email'], $password);

        unset($this->session->data['guest']);

        $json['success'] = true;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}
