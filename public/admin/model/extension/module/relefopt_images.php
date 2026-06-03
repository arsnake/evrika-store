<?php
/**
 * Evrika — РельефОпт Image Parser Model
 */
class ModelExtensionModuleRelefoptImages extends Model {

    const SEARCH_API     = 'https://relefopt.ru/v3/catalog/main/search/';
    const TOKEN_ENDPOINT = 'https://relefopt.ru/connect/token';

    // ──────────────────────────────────────────────
    // Токен
    // ──────────────────────────────────────────────

    public function getToken() {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('relefopt_images');

        $token = $settings['relefopt_images_token'] ?? '';

        if ($token && $this->isTokenValid($token)) {
            return $token;
        }

        // Попытка автообновления через client_credentials
        $client_id     = $settings['relefopt_images_client_id'] ?? '';
        $client_secret = $settings['relefopt_images_client_secret'] ?? '';

        if ($client_id && $client_secret) {
            $new_token = $this->fetchNewToken($client_id, $client_secret);
            if ($new_token) {
                $settings['relefopt_images_token'] = $new_token;
                $this->model_setting_setting->editSetting('relefopt_images', $settings);
                return $new_token;
            }
        }

        return null;
    }

    private function isTokenValid($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        $padding = strlen($parts[1]) % 4;
        if ($padding) $parts[1] .= str_repeat('=', 4 - $padding);

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return $payload && isset($payload['exp']) && $payload['exp'] > (time() + 30);
    }

    private function fetchNewToken($client_id, $client_secret) {
        $ch = curl_init(self::TOKEN_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'scope'         => 'api-relefopt',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        if (!$resp) return null;
        $data = json_decode($resp, true);
        return $data['access_token'] ?? null;
    }

    // ──────────────────────────────────────────────
    // Поиск товаров
    // ──────────────────────────────────────────────

    public function searchProducts($token, $code) {
        $url = self::SEARCH_API . '?' . http_build_query([
            'q'       => $code,
            'offset'  => 0,
            'limit'   => 10,
            'sort'    => 'popularity',
            'order'   => 'asc',
            'correct' => 'Y',
            'type'    => 'items',
        ]);

        $raw = $this->apiGet($url, $token);
        if ($raw === false) {
            return ['error' => 'Нет ответа от РельефОпт API'];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['error' => 'Некорректный ответ от РельефОпт API'];
        }

        // Ошибка авторизации
        if (isset($data['meta']['errorType'])) {
            return ['error' => 'Ошибка авторизации: ' . ($data['meta']['errorDetail'] ?? 'токен недействителен. Обновите токен в настройках.')];
        }

        $items = $data['response']['ITEMS'] ?? [];
        if (empty($items)) {
            return [
                'products' => [],
                'message'  => 'Товар с кодом «' . htmlspecialchars($code, ENT_QUOTES) . '» не найден на РельефОпт',
            ];
        }

        $products = [];
        foreach ($items as $item) {
            $photos = $item['PHOTOS'] ?? [];

            // Главное фото — всегда первым, без дублей
            $main = $item['DETAIL_PICTURE_PATH'] ?? '';
            if ($main && (!$photos || $photos[0] !== $main)) {
                array_unshift($photos, $main);
                $photos = array_values(array_unique($photos));
            }

            if (empty($photos)) continue;

            $products[] = [
                'id'      => $item['ID'] ?? '',
                'code'    => $item['CODE_1C'] ?? '',
                'name'    => $item['NAME'] ?? '',
                'article' => $item['ARTICLE'] ?? '',
                'photos'  => $photos,
            ];
        }

        return [
            'products' => $products,
            'total'    => count($products),
        ];
    }

    private function apiGet($url, $token) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 OcStore-Evrika/1.0',
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
        ]);
        $resp = curl_exec($ch);
        $err  = curl_errno($ch);
        curl_close($ch);
        return $err ? false : $resp;
    }

    // ──────────────────────────────────────────────
    // Загрузка изображения
    // ──────────────────────────────────────────────

    public function downloadImage($url, $sort_order = 0) {
        $dir = DIR_IMAGE . 'catalog/relefopt/';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return ['error' => 'Не удалось создать папку: ' . $dir];
            }
        }

        // Расширение из URL
        $path_info = pathinfo(parse_url($url, PHP_URL_PATH));
        $ext = strtolower($path_info['extension'] ?? 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $ext = 'jpg';
        }

        $filename = 'rp_' . md5($url) . '.' . $ext;
        $filepath = $dir . $filename;

        // Скачиваем если ещё нет
        if (!file_exists($filepath)) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0',
            ]);
            $image_data = curl_exec($ch);
            $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err   = curl_errno($ch);
            curl_close($ch);

            if ($curl_err || $http_code !== 200 || !$image_data) {
                return ['error' => 'Ошибка загрузки (HTTP ' . $http_code . '): ' . $url];
            }

            file_put_contents($filepath, $image_data);
        }

        // Генерируем превью для отображения в форме
        $this->load->model('tool/image');
        $oc_path = 'catalog/relefopt/' . $filename;
        $thumb   = $this->model_tool_image->resize($oc_path, 100, 100);

        return [
            'success'    => true,
            'path'       => $oc_path,
            'thumb'      => $thumb,
            'sort_order' => $sort_order,
        ];
    }
}
