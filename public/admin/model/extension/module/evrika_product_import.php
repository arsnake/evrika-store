<?php
/**
 * Evrika Product Import — модель
 * PHP 7.4 compatible
 */
class ModelExtensionModuleEvrikaProductImport extends Model {

    // =========================================================================
    // УСТАНОВКА / УДАЛЕНИЕ
    // =========================================================================

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "evrika_import_preset` (
                `preset_id`  INT(11) NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(128) NOT NULL,
                `mapping`    TEXT NOT NULL,
                `settings`   TEXT NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`preset_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "evrika_import_log` (
                `log_id`      INT(11) NOT NULL AUTO_INCREMENT,
                `filename`    VARCHAR(255) NOT NULL,
                `created_at`  DATETIME NOT NULL,
                `total`       INT(11) NOT NULL DEFAULT 0,
                `created_cnt` INT(11) NOT NULL DEFAULT 0,
                `updated_cnt` INT(11) NOT NULL DEFAULT 0,
                `skipped_cnt` INT(11) NOT NULL DEFAULT 0,
                `errors`      TEXT DEFAULT NULL,
                PRIMARY KEY (`log_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "evrika_import_preset`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "evrika_import_log`");
    }

    // =========================================================================
    // ПРЕСЕТЫ
    // =========================================================================

    public function savePreset($name, array $mapping, array $settings) {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "evrika_import_preset`
                (`name`, `mapping`, `settings`, `created_at`)
            VALUES (
                '" . $this->db->escape($name) . "',
                '" . $this->db->escape(json_encode($mapping, JSON_UNESCAPED_UNICODE)) . "',
                '" . $this->db->escape(json_encode($settings, JSON_UNESCAPED_UNICODE)) . "',
                NOW()
            )
        ");
        return $this->db->getLastId();
    }

    public function getPresets() {
        $query = $this->db->query("
            SELECT `preset_id`, `name`, `created_at`
            FROM `" . DB_PREFIX . "evrika_import_preset`
            ORDER BY `created_at` DESC
        ");
        return $query->rows;
    }

    public function getPreset($preset_id) {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "evrika_import_preset`
            WHERE `preset_id` = " . (int)$preset_id . "
            LIMIT 1
        ");
        return isset($query->row['preset_id']) ? $query->row : null;
    }

    public function deletePreset($preset_id) {
        $this->db->query("
            DELETE FROM `" . DB_PREFIX . "evrika_import_preset`
            WHERE `preset_id` = " . (int)$preset_id
        );
    }

    // =========================================================================
    // ЛОГ ИМПОРТОВ
    // =========================================================================

    public function saveLog(array $data) {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "evrika_import_log`
                (`filename`, `created_at`, `total`, `created_cnt`, `updated_cnt`, `skipped_cnt`, `errors`)
            VALUES (
                '" . $this->db->escape($data['filename']) . "',
                NOW(),
                " . (int)$data['total'] . ",
                " . (int)$data['created_cnt'] . ",
                " . (int)$data['updated_cnt'] . ",
                " . (int)$data['skipped_cnt'] . ",
                '" . $this->db->escape($data['errors']) . "'
            )
        ");
        return $this->db->getLastId();
    }

    public function getLogs($limit = 20) {
        $query = $this->db->query("
            SELECT *
            FROM `" . DB_PREFIX . "evrika_import_log`
            ORDER BY `created_at` DESC
            LIMIT " . (int)$limit
        );
        return $query->rows;
    }

    // =========================================================================
    // ГРУППЫ ТОВАРОВ
    // =========================================================================

    /**
     * Извлекает группы товаров по первому слову названия и находит рекомендованную категорию.
     */
    public function getProductGroups(array $productNames) {
        $groupMap    = array(); // norm_key => display first word
        $groupCounts = array(); // norm_key => count

        foreach ($productNames as $name) {
            $name = trim((string)$name);
            if ($name === '') {
                continue;
            }
            $parts     = preg_split('/\s+/', $name, 2);
            $firstWord = trim($parts[0]);
            if ($firstWord === '') {
                continue;
            }
            $normKey = mb_strtolower($firstWord, 'UTF-8');
            if (!isset($groupMap[$normKey])) {
                $groupMap[$normKey]    = $firstWord;
                $groupCounts[$normKey] = 0;
            }
            $groupCounts[$normKey]++;
        }

        if (empty($groupMap)) {
            return array();
        }

        // Загружаем все категории для поиска рекомендации
        $languageId = (int)$this->config->get('config_language_id');
        $catQuery   = $this->db->query("
            SELECT c.`category_id`, cd.`name`
            FROM `" . DB_PREFIX . "category` c
            LEFT JOIN `" . DB_PREFIX . "category_description` cd
                ON c.`category_id` = cd.`category_id`
                AND cd.`language_id` = " . $languageId . "
            WHERE c.`status` = 1
            ORDER BY cd.`name` ASC
        ");
        $categories = $catQuery->rows;

        $result = array();
        foreach ($groupMap as $normKey => $displayKey) {
            $bestCatId   = 0;
            $bestCatName = '';
            $bestScore   = 0;

            foreach ($categories as $cat) {
                if (empty($cat['name'])) {
                    continue;
                }
                $catLower = mb_strtolower($cat['name'], 'UTF-8');
                $score    = 0;
                if (strpos($catLower, $normKey) !== false) {
                    $score = 2; // название категории содержит ключ группы
                } elseif (mb_strlen($normKey, 'UTF-8') >= 4 && strpos($normKey, $catLower) !== false && mb_strlen($catLower, 'UTF-8') >= 3) {
                    $score = 1;
                }
                if ($score > $bestScore) {
                    $bestScore   = $score;
                    $bestCatId   = (int)$cat['category_id'];
                    $bestCatName = $cat['name'];
                }
            }

            $result[] = array(
                'key'            => $displayKey,
                'key_normalized' => $normKey,
                'count'          => $groupCounts[$normKey],
                'category_id'    => $bestCatId,
                'category_name'  => $bestCatName,
            );
        }

        // Сортируем по количеству товаров DESC
        usort($result, function($a, $b) { return $b['count'] - $a['count']; });

        return $result;
    }

    // =========================================================================
    // КАТЕГОРИИ С УМНОЙ СОРТИРОВКОЙ
    // =========================================================================

    /**
     * Возвращает все категории, отсортированные по релевантности к именам товаров.
     * Сначала — категории с score > 0 (по убыванию), затем — остальные алфавитно.
     *
     * @param  array $productNames Массив названий товаров из файла
     * @return array
     */
    public function getCategoriesSorted(array $productNames) {
        $languageId = (int)$this->config->get('config_language_id');

        $query = $this->db->query("
            SELECT c.`category_id`, cd.`name`
            FROM `" . DB_PREFIX . "category` c
            LEFT JOIN `" . DB_PREFIX . "category_description` cd
                ON c.`category_id` = cd.`category_id`
                AND cd.`language_id` = " . $languageId . "
            WHERE c.`status` = 1
            ORDER BY cd.`name` ASC
        ");

        $categories = $query->rows;

        if (empty($productNames) || empty($categories)) {
            foreach ($categories as &$cat) {
                $cat['score'] = 0;
            }
            return $categories;
        }

        // Нормализуем имена товаров один раз
        $normalizedProducts = array();
        foreach ($productNames as $pname) {
            $normalizedProducts[] = mb_strtolower(trim($pname), 'UTF-8');
        }

        // Считаем score для каждой категории
        foreach ($categories as &$cat) {
            $catName = mb_strtolower(trim($cat['name']), 'UTF-8');
            $score   = 0;

            if ($catName === '') {
                $cat['score'] = 0;
                continue;
            }

            foreach ($normalizedProducts as $pname) {
                if ($pname === '') {
                    continue;
                }
                // Категория содержится в названии товара
                if (strpos($pname, $catName) !== false) {
                    $score++;
                    continue;
                }
                // Начало названия товара содержится в названии категории
                $prefix = mb_substr($pname, 0, min(10, mb_strlen($pname, 'UTF-8')), 'UTF-8');
                if ($prefix !== '' && mb_strlen($catName, 'UTF-8') >= 4 && strpos($catName, $prefix) !== false) {
                    $score++;
                }
            }

            $cat['score'] = $score;
        }
        unset($cat);

        // Сортируем: сначала score DESC, затем name ASC
        usort($categories, function($a, $b) {
            if ($b['score'] !== $a['score']) {
                return $b['score'] - $a['score'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return $categories;
    }

    // =========================================================================
    // ПОИСК ТОВАРА
    // =========================================================================

    /**
     * Найти ID существующего товара по заданному полю.
     *
     * @param  string $field Поле: model | sku | upc | name
     * @param  string $value Значение
     * @return int|null
     */
    public function findProduct($field, $value) {
        if ($value === '') {
            return null;
        }

        $allowedFields = array('model', 'sku', 'upc', 'ean');

        if (in_array($field, $allowedFields)) {
            $query = $this->db->query("
                SELECT `product_id` FROM `" . DB_PREFIX . "product`
                WHERE `" . $field . "` = '" . $this->db->escape($value) . "'
                LIMIT 1
            ");
        } elseif ($field === 'name') {
            $languageId = (int)$this->config->get('config_language_id');
            $query = $this->db->query("
                SELECT `product_id` FROM `" . DB_PREFIX . "product_description`
                WHERE `name` = '" . $this->db->escape($value) . "'
                AND `language_id` = " . $languageId . "
                LIMIT 1
            ");
        } else {
            return null;
        }

        if (isset($query->row['product_id'])) {
            return (int)$query->row['product_id'];
        }

        return null;
    }

    // =========================================================================
    // ПРОИЗВОДИТЕЛЬ
    // =========================================================================

    /**
     * Найти производителя по имени или создать нового.
     *
     * @param  string $name
     * @return int manufacturer_id
     */
    public function findOrCreateManufacturer($name) {
        if ($name === '') {
            return 0;
        }

        $query = $this->db->query("
            SELECT `manufacturer_id` FROM `" . DB_PREFIX . "manufacturer`
            WHERE `name` = '" . $this->db->escape($name) . "'
            LIMIT 1
        ");

        if (isset($query->row['manufacturer_id'])) {
            return (int)$query->row['manufacturer_id'];
        }

        // Создаём нового
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "manufacturer` (`name`, `sort_order`)
            VALUES ('" . $this->db->escape($name) . "', 0)
        ");
        $manufacturerId = $this->db->getLastId();

        // Описание (OcStore требует запись в manufacturer_description для поиска по языку)
        $languageId = (int)$this->config->get('config_language_id');
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "manufacturer_description`
                (`manufacturer_id`, `language_id`, `description`, `meta_title`, `meta_h1`, `meta_description`, `meta_keyword`)
            VALUES (
                " . (int)$manufacturerId . ",
                " . $languageId . ",
                '',
                '" . $this->db->escape($name) . "',
                '" . $this->db->escape($name) . "',
                '',
                ''
            )
        ");

        // Привязываем ко всем магазинам
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` (`manufacturer_id`, `store_id`)
            VALUES (" . (int)$manufacturerId . ", 0)
        ");

        // SEO URL для нового производителя
        $this->createManufacturerSeoUrl($manufacturerId, $name);

        return (int)$manufacturerId;
    }

    // =========================================================================
    // ИМПОРТ ОДНОГО ТОВАРА
    // =========================================================================

    /**
     * Создать или обновить товар.
     *
     * @param  array  $productData Данные товара из файла (field => value)
     * @param  array  $settings    Настройки импорта
     * @return array  array with keys: status, product_id, name, model, categories, message
     */
    public function importProduct(array $productData, array $settings) {
        $mode        = isset($settings['mode'])             ? $settings['mode']             : 'both';
        $keyField    = isset($settings['key_field'])        ? $settings['key_field']        : 'model';
        $defStatus   = isset($settings['default_status'])   ? (int)$settings['default_status']   : 1;
        $languageId  = isset($settings['language_id'])      ? (int)$settings['language_id']      : 1;
        $storeId     = isset($settings['store_id'])         ? (int)$settings['store_id']         : 0;

        // default_category — массив ID категорий (мультиселект)
        $defCategories = array();
        if (!empty($settings['default_category'])) {
            $raw = $settings['default_category'];
            if (is_array($raw)) {
                foreach ($raw as $cid) {
                    $cid = (int)$cid;
                    if ($cid > 0) {
                        $defCategories[] = $cid;
                    }
                }
            } elseif ((int)$raw > 0) {
                $defCategories[] = (int)$raw;
            }
        }

        // Определяем ключевое значение для поиска
        $keyValue   = isset($productData[$keyField]) ? trim($productData[$keyField]) : '';
        $productId  = null;

        if ($keyValue !== '') {
            $productId = $this->findProduct($keyField, $keyValue);
        }

        // Производитель
        $manufacturerId = 0;
        if (!empty($productData['manufacturer'])) {
            $manufacturerId = $this->findOrCreateManufacturer(trim($productData['manufacturer']));
        }

        // Базовые поля oc_product
        $price     = isset($productData['price'])     ? $this->parsePrice($productData['price'])     : '0.0000';
        $quantity  = isset($productData['quantity'])  ? (int)$productData['quantity']               : 0;
        $model     = isset($productData['model'])     ? $this->db->escape(trim($productData['model'])) : '';
        $sku       = isset($productData['sku'])       ? $this->db->escape(trim($productData['sku']))   : '';
        $upc       = isset($productData['upc'])       ? $this->db->escape(trim($productData['upc']))   : '';
        $ean       = isset($productData['ean'])       ? $this->db->escape(trim($productData['ean']))   : '';
        $weight    = isset($productData['weight'])    ? (float)str_replace(',', '.', $productData['weight']) : '0.00';
        $minimum   = isset($productData['minimum'])   ? max(1, (int)$productData['minimum'])          : 1;
        $sortOrder = isset($productData['sort_order'])? (int)$productData['sort_order']               : 0;
        $status    = isset($productData['status'])    ? (int)$productData['status']                   : $defStatus;

        // Поля oc_product_description
        $name            = isset($productData['name'])             ? $this->db->escape(trim($productData['name']))             : '';
        $description     = isset($productData['description'])      ? $this->db->escape(trim($productData['description']))      : '';
        $metaTitle       = isset($productData['meta_title'])       ? $this->db->escape(trim($productData['meta_title']))       : ($name !== '' ? $name : '');
        $metaDescription = isset($productData['meta_description']) ? $this->db->escape(trim($productData['meta_description'])) : '';
        $tag             = isset($productData['tag'])              ? $this->db->escape(trim($productData['tag']))              : '';

        if ($productId !== null) {
            // ---- ОБНОВЛЕНИЕ ----
            if ($mode === 'create') {
                return array('status' => 'skipped', 'product_id' => $productId, 'name' => isset($productData['name']) ? trim($productData['name']) : '', 'model' => isset($productData['model']) ? trim($productData['model']) : '', 'categories' => array(), 'message' => 'Пропущено (режим: только создавать)');
            }

            $this->db->query("
                UPDATE `" . DB_PREFIX . "product` SET
                    `model`           = '" . $model . "',
                    `sku`             = '" . $sku . "',
                    `upc`             = '" . $upc . "',
                    `ean`             = '" . $ean . "',
                    `price`           = '" . (float)$price . "',
                    `quantity`        = " . (int)$quantity . ",
                    `manufacturer_id` = " . (int)$manufacturerId . ",
                    `weight`          = '" . (float)$weight . "',
                    `minimum`         = " . (int)$minimum . ",
                    `sort_order`      = " . (int)$sortOrder . ",
                    `status`          = " . (int)$status . ",
                    `date_modified`   = NOW()
                WHERE `product_id` = " . (int)$productId
            );

            // Обновляем описание
            $this->db->query("
                UPDATE `" . DB_PREFIX . "product_description` SET
                    `name`             = '" . $name . "',
                    `description`      = '" . $description . "',
                    `meta_title`       = '" . $metaTitle . "',
                    `meta_description` = '" . $metaDescription . "',
                    `tag`              = '" . $tag . "'
                WHERE `product_id` = " . (int)$productId . "
                AND `language_id`  = " . (int)$languageId
            );

            $this->applyCategories($productId, $productData, $settings, $defCategories);
            $this->createProductSeoUrl($productId, isset($productData['name']) ? trim($productData['name']) : '');

            return array('status' => 'updated', 'product_id' => $productId, 'name' => isset($productData['name']) ? trim($productData['name']) : '', 'model' => isset($productData['model']) ? trim($productData['model']) : '', 'categories' => $this->getProductCategoryNames($productId), 'message' => '');

        } else {
            // ---- СОЗДАНИЕ ----
            if ($mode === 'update') {
                return array('status' => 'skipped', 'product_id' => null, 'name' => isset($productData['name']) ? trim($productData['name']) : '', 'model' => isset($productData['model']) ? trim($productData['model']) : '', 'categories' => array(), 'message' => 'Пропущено (режим: только обновлять)');
            }

            if ($name === '' && $model === '') {
                return array('status' => 'error', 'product_id' => null, 'name' => '', 'model' => '', 'categories' => array(), 'message' => 'Нет названия и кода товара — невозможно создать.');
            }

            $this->db->query("
                INSERT INTO `" . DB_PREFIX . "product`
                    (`model`, `sku`, `upc`, `ean`, `price`, `quantity`,
                     `manufacturer_id`, `weight`, `minimum`, `sort_order`, `status`,
                     `stock_status_id`, `shipping`, `image`, `date_added`, `date_modified`)
                VALUES (
                    '" . $model . "',
                    '" . $sku . "',
                    '" . $upc . "',
                    '" . $ean . "',
                    '" . (float)$price . "',
                    " . (int)$quantity . ",
                    " . (int)$manufacturerId . ",
                    '" . (float)$weight . "',
                    " . (int)$minimum . ",
                    " . (int)$sortOrder . ",
                    " . (int)$status . ",
                    7,
                    1,
                    '',
                    NOW(),
                    NOW()
                )
            ");

            $productId = $this->db->getLastId();

            if (!$productId) {
                return array('status' => 'error', 'product_id' => null, 'name' => '', 'model' => '', 'categories' => array(), 'message' => 'Ошибка базы данных при создании товара.');
            }

            // Описание
            $this->db->query("
                INSERT INTO `" . DB_PREFIX . "product_description`
                    (`product_id`, `language_id`, `name`, `description`, `tag`, `meta_title`, `meta_description`, `meta_keyword`)
                VALUES (
                    " . (int)$productId . ",
                    " . (int)$languageId . ",
                    '" . $name . "',
                    '" . $description . "',
                    '" . $tag . "',
                    '" . $metaTitle . "',
                    '" . $metaDescription . "',
                    ''
                )
            ");

            // Привязка к магазину
            $this->db->query("
                INSERT INTO `" . DB_PREFIX . "product_to_store` (`product_id`, `store_id`)
                VALUES (" . (int)$productId . ", " . (int)$storeId . ")
            ");

            $this->applyCategories($productId, $productData, $settings, $defCategories);
            $this->createProductSeoUrl($productId, isset($productData['name']) ? trim($productData['name']) : '');

            return array('status' => 'created', 'product_id' => $productId, 'name' => isset($productData['name']) ? trim($productData['name']) : '', 'model' => isset($productData['model']) ? trim($productData['model']) : '', 'categories' => $this->getProductCategoryNames($productId), 'message' => '');
        }
    }

    // =========================================================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =========================================================================

    private function getProductCategoryNames($productId) {
        $languageId = (int)$this->config->get('config_language_id');
        $query = $this->db->query("
            SELECT cd.`name`
            FROM `" . DB_PREFIX . "product_to_category` pc
            JOIN `" . DB_PREFIX . "category_description` cd
                ON cd.`category_id` = pc.`category_id`
                AND cd.`language_id` = " . $languageId . "
            WHERE pc.`product_id` = " . (int)$productId . "
            ORDER BY cd.`name` ASC
        ");
        $names = array();
        foreach ($query->rows as $row) {
            $names[] = $row['name'];
        }
        return $names;
    }

    /**
     * Назначает категории товару: из файла + из группы + дефолтные (все одновременно, аддитивно).
     */
    private function applyCategories($productId, array $productData, array $settings, array $defCategories) {
        $attached = array();

        // 1. Категория из столбца файла
        if (!empty($productData['category'])) {
            $catId = $this->findCategoryByName($productData['category']);
            if ($catId && !in_array($catId, $attached)) {
                $this->attachCategory($productId, $catId);
                $attached[] = $catId;
            }
        }

        // 2. Дефолтные категории — применяются всегда
        foreach ($defCategories as $cid) {
            $cid = (int)$cid;
            if ($cid > 0 && !in_array($cid, $attached)) {
                $this->attachCategory($productId, $cid);
                $attached[] = $cid;
            }
        }

        // 3. Категории по группе товара — добавляются поверх дефолтных
        $groupCategories = isset($settings['group_categories']) && is_array($settings['group_categories']) ? $settings['group_categories'] : array();
        if (!empty($groupCategories) && !empty($productData['name'])) {
            $parts     = preg_split('/\s+/', trim($productData['name']), 2);
            $firstNorm = mb_strtolower(trim($parts[0]), 'UTF-8');
            if ($firstNorm !== '' && isset($groupCategories[$firstNorm])) {
                // Значение может быть числом (старый формат) или массивом (мультиселект)
                $groupCatIds = is_array($groupCategories[$firstNorm])
                    ? $groupCategories[$firstNorm]
                    : array($groupCategories[$firstNorm]);
                foreach ($groupCatIds as $groupCatId) {
                    $groupCatId = (int)$groupCatId;
                    if ($groupCatId > 0 && !in_array($groupCatId, $attached)) {
                        $this->attachCategory($productId, $groupCatId);
                        $attached[] = $groupCatId;
                    }
                }
            }
        }
    }

    /**
     * Транслитерация строки для SEO slug.
     */
    private function generateSlug($name) {
        $map = array(
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z',
            'и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
            'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch',
            'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'Yo','Ж'=>'Zh','З'=>'Z',
            'И'=>'I','Й'=>'J','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R',
            'С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'H','Ц'=>'Ts','Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Sch',
            'Ъ'=>'','Ы'=>'Y','Ь'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya',
        );
        $slug = strtr($name, $map);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = preg_replace('/-{2,}/', '-', $slug);
        return $slug;
    }

    /**
     * Создаёт SEO URL запись для нового производителя.
     */
    private function createManufacturerSeoUrl($manufacturerId, $name) {
        $slug = $this->generateSlug($name);
        if ($slug === '') {
            return;
        }

        // Проверяем уникальность и добавляем суффикс при необходимости
        $baseSlug = $slug;
        $suffix   = 1;
        while (true) {
            $check = $this->db->query("
                SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "seo_url`
                WHERE `keyword` = '" . $this->db->escape($slug) . "'
            ");
            if ((int)$check->row['cnt'] === 0) {
                break;
            }
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
            if ($suffix > 200) {
                break; // защита от бесконечного цикла
            }
        }

        $languageId = (int)$this->config->get('config_language_id');
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "seo_url`
                (`store_id`, `language_id`, `query`, `keyword`)
            VALUES (
                0,
                " . $languageId . ",
                'manufacturer_id=" . (int)$manufacturerId . "',
                '" . $this->db->escape($slug) . "'
            )
        ");
    }

    /**
     * Создаёт SEO URL для товара (если ещё не существует).
     */
    private function createProductSeoUrl($productId, $name) {
        $slug = $this->generateSlug($name);
        if ($slug === '') {
            return;
        }

        // Не перезаписываем, если уже есть
        $existing = $this->db->query("
            SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "seo_url`
            WHERE `query` = 'product_id=" . (int)$productId . "'
        ");
        if ((int)$existing->row['cnt'] > 0) {
            return;
        }

        // Уникальность slug
        $baseSlug = $slug;
        $suffix   = 1;
        while (true) {
            $check = $this->db->query("
                SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "seo_url`
                WHERE `keyword` = '" . $this->db->escape($slug) . "'
            ");
            if ((int)$check->row['cnt'] === 0) {
                break;
            }
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
            if ($suffix > 200) {
                break;
            }
        }

        $languageId = (int)$this->config->get('config_language_id');
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "seo_url`
                (`store_id`, `language_id`, `query`, `keyword`)
            VALUES (
                0,
                " . $languageId . ",
                'product_id=" . (int)$productId . "',
                '" . $this->db->escape($slug) . "'
            )
        ");
    }

    /**
     * Найти категорию по имени (точное совпадение, case-insensitive).
     *
     * @param  string $name
     * @return int|null category_id
     */
    private function findCategoryByName($name) {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $languageId = (int)$this->config->get('config_language_id');
        $query = $this->db->query("
            SELECT `category_id` FROM `" . DB_PREFIX . "category_description`
            WHERE `name` = '" . $this->db->escape($name) . "'
            AND `language_id` = " . $languageId . "
            LIMIT 1
        ");

        return isset($query->row['category_id']) ? (int)$query->row['category_id'] : null;
    }

    /**
     * Привязать товар к категории (если ещё не привязан).
     *
     * @param  int $productId
     * @param  int $categoryId
     */
    private function attachCategory($productId, $categoryId) {
        $check = $this->db->query("
            SELECT COUNT(*) AS cnt FROM `" . DB_PREFIX . "product_to_category`
            WHERE `product_id` = " . (int)$productId . "
            AND `category_id` = " . (int)$categoryId
        );

        if (isset($check->row['cnt']) && (int)$check->row['cnt'] === 0) {
            $this->db->query("
                INSERT INTO `" . DB_PREFIX . "product_to_category` (`product_id`, `category_id`)
                VALUES (" . (int)$productId . ", " . (int)$categoryId . ")
            ");
        }
    }

    /**
     * Парсим цену: убираем валютные символы, пробелы, заменяем запятую на точку.
     *
     * @param  string $raw
     * @return string
     */
    private function parsePrice($raw) {
        // Убираем всё кроме цифр, точки и запятой
        $clean = preg_replace('/[^0-9.,]/', '', (string)$raw);
        // Если есть и точка и запятая — запятая это разделитель тысяч
        if (strpos($clean, '.') !== false && strpos($clean, ',') !== false) {
            $clean = str_replace(',', '', $clean);
        } else {
            $clean = str_replace(',', '.', $clean);
        }
        $val = (float)$clean;
        return number_format($val, 4, '.', '');
    }
}
