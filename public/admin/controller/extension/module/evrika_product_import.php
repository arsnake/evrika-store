<?php
/**
 * Evrika Product Import — контроллер
 * PHP 7.4 compatible
 */
class ControllerExtensionModuleEvrikaProductImport extends Controller {

    private $error = array();

    // -------------------------------------------------------------------------
    // ШАГ 1: Главная страница — форма загрузки файла
    // -------------------------------------------------------------------------
    public function index() {
        $this->load->language('extension/module/evrika_product_import');
        $this->load->model('extension/module/evrika_product_import');

        $this->document->setTitle($this->language->get('heading_title'));

        $data = $this->buildCommonData();

        // История импортов
        $data['logs'] = $this->model_extension_module_evrika_product_import->getLogs(10);

        // Пресеты
        $data['presets'] = $this->model_extension_module_evrika_product_import->getPresets();

        // URL действий
        $data['action_upload']  = $this->url->link('extension/module/evrika_product_import/upload', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_preset_delete'] = $this->url->link('extension/module/evrika_product_import/deletePreset', 'user_token=' . $this->session->data['user_token'], true);

        // Ошибки из сессии
        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        $this->response->setOutput($this->load->view('extension/module/evrika_product_import/upload', $data));
    }

    // -------------------------------------------------------------------------
    // POST: Принять файл, распарсить, сохранить в сессию → редирект на mapping
    // -------------------------------------------------------------------------
    public function upload() {
        $this->load->language('extension/module/evrika_product_import');

        if (!$this->validate()) {
            $this->session->data['error'] = $this->error['warning'];
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        // Проверка наличия файла
        if (empty($_FILES['import_file']['name'])) {
            $this->session->data['error'] = $this->language->get('error_no_file');
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $file = $_FILES['import_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Проверка типа
        if (!in_array($ext, array('xlsx', 'xls'))) {
            $this->session->data['error'] = $this->language->get('error_file_type');
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        // Проверка размера (10 МБ)
        if ($file['size'] > 10 * 1024 * 1024) {
            $this->session->data['error'] = $this->language->get('error_file_size');
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        // Сохраняем файл во временную папку
        $uploadDir = DIR_UPLOAD . 'evrika_import/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $tmpName  = 'import_' . time() . '_' . uniqid() . '.' . $ext;
        $tmpPath  = $uploadDir . $tmpName;

        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            $this->session->data['error'] = $this->language->get('error_file_upload');
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        // Парсим файл для определения заголовка (строки НЕ храним в сессии — слишком большой объём)
        $rows = $this->parseFile($tmpPath, $ext);

        if ($rows === null) {
            @unlink($tmpPath);
            $this->session->data['error'] = $this->language->get('error_parse');
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        if (empty($rows)) {
            @unlink($tmpPath);
            $this->session->data['error'] = $this->language->get('error_no_data');
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        // Определяем строку-заголовок и маппинг
        require_once DIR_SYSTEM . 'library/evrika/xlsimport/ColumnDetector.php';
        $detector  = new EvrikaColumnDetector();
        $headerIdx = $detector->detectHeaderRow($rows);
        $headerRow = isset($rows[$headerIdx]) ? $rows[$headerIdx] : array();
        $mapping   = $detector->suggestMapping($headerRow);

        // В сессию — только метаданные (не сами строки!)
        $this->session->data['evrika_import_file']      = $tmpPath;
        $this->session->data['evrika_import_ext']       = $ext;
        $this->session->data['evrika_import_filename']  = $file['name'];
        $this->session->data['evrika_import_header']    = $headerIdx;
        $this->session->data['evrika_import_mapping']   = $mapping;
        $this->session->data['evrika_import_preset_id'] = isset($this->request->post['preset_id']) ? (int)$this->request->post['preset_id'] : 0;

        $this->response->redirect($this->url->link('extension/module/evrika_product_import/mapping', 'user_token=' . $this->session->data['user_token'], true));
    }

    // -------------------------------------------------------------------------
    // ШАГ 2: Страница маппинга столбцов
    // -------------------------------------------------------------------------
    public function mapping() {
        $this->load->language('extension/module/evrika_product_import');
        $this->load->model('extension/module/evrika_product_import');

        // Проверяем что метаданные в сессии есть
        if (empty($this->session->data['evrika_import_file'])) {
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->document->setTitle($this->language->get('heading_title'));

        // Парсим файл заново (не храним строки в сессии во избежание переполнения)
        $filePath  = $this->session->data['evrika_import_file'];
        $fileExt   = isset($this->session->data['evrika_import_ext'])
            ? $this->session->data['evrika_import_ext']
            : strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows      = $this->parseFile($filePath, $fileExt);

        if ($rows === null || empty($rows)) {
            $this->session->data['error'] = $this->language->get('error_parse');
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $headerIdx = (int)$this->session->data['evrika_import_header'];
        $mapping   = $this->session->data['evrika_import_mapping'];
        $headerRow = isset($rows[$headerIdx]) ? $rows[$headerIdx] : array();

        // Если выбран пресет — переписываем маппинг из него
        $presetId = isset($this->session->data['evrika_import_preset_id']) ? (int)$this->session->data['evrika_import_preset_id'] : 0;
        if ($presetId > 0) {
            $preset = $this->model_extension_module_evrika_product_import->getPreset($presetId);
            if ($preset) {
                $mapping = json_decode($preset['mapping'], true);
            }
        }

        // Превью: строки данных (пропускаем заголовок, берём до 5 строк)
        $previewRows = array();
        for ($i = $headerIdx + 1; $i < min(count($rows), $headerIdx + 6); $i++) {
            if (!empty($rows[$i])) {
                $previewRows[] = $rows[$i];
            }
        }

        // Примеры значений для каждого столбца (для подсказки в интерфейсе)
        $columnSamples = array();
        foreach ($headerRow as $colIdx => $colName) {
            $samples = array();
            foreach ($previewRows as $row) {
                $val = isset($row[$colIdx]) ? trim((string)$row[$colIdx]) : '';
                if ($val !== '') {
                    $samples[] = $val;
                }
                if (count($samples) >= 3) {
                    break;
                }
            }
            $columnSamples[$colIdx] = $samples;
        }

        // Умный список категорий
        $productNames = array();
        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            if (empty($rows[$i])) {
                continue;
            }
            // Ищем столбец с именем товара
            foreach ($mapping as $colIdx => $field) {
                if ($field === 'name' && isset($rows[$i][$colIdx])) {
                    $productNames[] = (string)$rows[$i][$colIdx];
                    break;
                }
            }
        }

        require_once DIR_SYSTEM . 'library/evrika/xlsimport/ColumnDetector.php';
        $detector = new EvrikaColumnDetector();

        $data = $this->buildCommonData();
        $data['header_row']      = $headerRow;
        $data['preview_rows']    = $previewRows;
        $data['column_samples']  = $columnSamples;
        $data['mapping']         = $mapping;
        $data['available_fields'] = $detector->getAvailableFields();
        $data['presets']         = $this->model_extension_module_evrika_product_import->getPresets();
        $data['filename']        = isset($this->session->data['evrika_import_filename']) ? $this->session->data['evrika_import_filename'] : '';

        // Категории с умной сортировкой
        $data['categories'] = $this->model_extension_module_evrika_product_import->getCategoriesSorted($productNames);

        // Группы товаров для посекционной привязки категорий
        $data['product_groups'] = $this->model_extension_module_evrika_product_import->getProductGroups($productNames);

        // URL действий
        $data['action_import']       = $this->url->link('extension/module/evrika_product_import/import', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_save_preset']  = $this->url->link('extension/module/evrika_product_import/savePreset', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_load_preset']  = $this->url->link('extension/module/evrika_product_import/loadPreset', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_back']         = $this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true);

        $this->response->setOutput($this->load->view('extension/module/evrika_product_import/mapping', $data));
    }

    // -------------------------------------------------------------------------
    // POST AJAX: Выполнить импорт
    // -------------------------------------------------------------------------
    public function import() {
        $this->load->language('extension/module/evrika_product_import');
        $this->load->model('extension/module/evrika_product_import');

        $json = array('success' => false, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array(), 'done' => false);

        ob_start(); // поглощаем случайный вывод (warnings и т.п.) чтобы не сломать JSON

        if (!$this->validate()) {
            ob_end_clean();
            $json['error'] = $this->error['warning'];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if (empty($this->session->data['evrika_import_file'])) {
            ob_end_clean();
            $json['error'] = 'Данные импорта не найдены в сессии. Загрузите файл заново.';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Парсим файл заново (не храним строки в сессии)
        $filePath  = $this->session->data['evrika_import_file'];
        $fileExt   = isset($this->session->data['evrika_import_ext'])
            ? $this->session->data['evrika_import_ext']
            : strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows      = $this->parseFile($filePath, $fileExt);

        ob_end_clean(); // сбрасываем накопленный вывод перед JSON

        if ($rows === null || empty($rows)) {
            $json['error'] = $this->language->get('error_parse');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $headerIdx = (int)$this->session->data['evrika_import_header'];

        // Получаем маппинг из POST
        $mapping  = isset($this->request->post['mapping']) ? (array)$this->request->post['mapping'] : array();

        // JS отправляет settings[mode], settings[key_field] и т.д.
        $postSettings = isset($this->request->post['settings']) ? (array)$this->request->post['settings'] : array();
        $settings = array(
            'mode'             => isset($postSettings['mode'])             ? $postSettings['mode']             : 'both',
            'key_field'        => isset($postSettings['key_field'])        ? $postSettings['key_field']        : 'model',
            'default_status'   => isset($postSettings['default_status'])   ? (int)$postSettings['default_status']   : 1,
            'default_category' => isset($postSettings['default_category']) ? (array)$postSettings['default_category'] : array(),
            'group_categories' => (function() use ($postSettings) {
                if (!isset($postSettings['group_categories']) || !is_array($postSettings['group_categories'])) {
                    return array();
                }
                $result = array();
                foreach ($postSettings['group_categories'] as $key => $val) {
                    // val может быть строкой (одиночный ID) или массивом (мультиселект)
                    $ids = array();
                    foreach ((array)$val as $id) {
                        $id = (int)$id;
                        if ($id > 0) { $ids[] = $id; }
                    }
                    if (!empty($ids)) { $result[$key] = $ids; }
                }
                return $result;
            })(),
            'language_id'      => (int)$this->config->get('config_language_id'),
            'store_id'         => (int)$this->config->get('config_store_id'),
        );

        // Индекс батча
        $batchSize  = 50;
        $batchIndex = isset($this->request->post['batch']) ? (int)$this->request->post['batch'] : 0;

        // Данные строк (пропускаем заголовок и пустые)
        $dataRows = array();
        for ($i = $headerIdx + 1; $i < count($rows); $i++) {
            if (!empty(array_filter($rows[$i], function($v) { return trim((string)$v) !== ''; }))) {
                $dataRows[] = array('row_num' => $i + 1, 'data' => $rows[$i]);
            }
        }

        $total      = count($dataRows);
        $batchStart = $batchIndex * $batchSize;
        $batchRows  = array_slice($dataRows, $batchStart, $batchSize);

        $batchResults = array();

        foreach ($batchRows as $rowInfo) {
            // Собираем данные товара из строки по маппингу
            $productData = array();
            foreach ($mapping as $colIdx => $field) {
                if ($field === '' || $field === null) {
                    continue;
                }
                $val = isset($rowInfo['data'][(int)$colIdx]) ? trim((string)$rowInfo['data'][(int)$colIdx]) : '';
                $productData[$field] = $val;
            }

            // Пропускаем полностью пустые строки — тихо, без записи в отчёт
            if (empty(array_filter($productData, function($v) { return $v !== ''; }))) {
                $json['skipped']++;
                continue;
            }

            // Нет ни одного идентификатора — фиксируем с подробностями
            if (empty($productData['name']) && empty($productData['model']) && empty($productData['sku']) && empty($productData['upc'])) {
                $nonEmpty = array_filter($productData, function($v) { return $v !== ''; });
                $hint = '';
                if (!empty($nonEmpty)) {
                    $parts = array();
                    foreach ($nonEmpty as $k => $v) {
                        $parts[] = $k . ': ' . mb_substr((string)$v, 0, 40, 'UTF-8');
                    }
                    $hint = '. Данные в строке: ' . implode(', ', $parts);
                }
                $json['skipped']++;
                $batchResults[] = array(
                    'row'        => $rowInfo['row_num'],
                    'status'     => 'skipped',
                    'product_id' => null,
                    'name'       => '',
                    'model'      => '',
                    'categories' => array(),
                    'message'    => 'Нет ни названия, ни кода товара' . $hint,
                );
                continue;
            }

            $result = $this->model_extension_module_evrika_product_import->importProduct($productData, $settings);

            $entry = array(
                'row'        => $rowInfo['row_num'],
                'status'     => $result['status'],
                'product_id' => isset($result['product_id']) ? $result['product_id'] : null,
                'name'       => isset($result['name'])       ? $result['name']       : (isset($productData['name'])  ? $productData['name']  : ''),
                'model'      => isset($result['model'])      ? $result['model']      : (isset($productData['model']) ? $productData['model'] : ''),
                'categories' => isset($result['categories']) ? $result['categories'] : array(),
                'message'    => isset($result['message'])    ? $result['message']    : '',
            );

            if ($result['status'] === 'created') {
                $json['created']++;
            } elseif ($result['status'] === 'updated') {
                $json['updated']++;
            } elseif ($result['status'] === 'skipped') {
                $json['skipped']++;
            } else {
                // error
                $json['errors'][] = 'Строка ' . $rowInfo['row_num'] . ': ' . $entry['message'];
                $json['skipped']++;
                $entry['status'] = 'error';
            }

            $batchResults[] = $entry;
        }

        $json['results'] = $batchResults;

        $processedSoFar = $batchStart + count($batchRows);
        $json['success']    = true;
        $json['total']      = $total;
        $json['processed']  = $processedSoFar;
        $json['done']       = ($processedSoFar >= $total);
        $json['next_batch'] = $batchIndex + 1;

        // Если импорт завершён — сохраняем лог и убираем данные из сессии
        if ($json['done']) {
            // Накапливаем итог из всех батчей (итоги передаём с фронтенда)
            $totalCreated  = isset($this->request->post['total_created'])  ? (int)$this->request->post['total_created']  + $json['created']  : $json['created'];
            $totalUpdated  = isset($this->request->post['total_updated'])  ? (int)$this->request->post['total_updated']  + $json['updated']  : $json['updated'];
            $totalSkipped  = isset($this->request->post['total_skipped'])  ? (int)$this->request->post['total_skipped']  + $json['skipped']  : $json['skipped'];
            $allErrors     = isset($this->request->post['all_errors'])     ? array_merge((array)json_decode($this->request->post['all_errors'], true), $json['errors']) : $json['errors'];

            // Детальные результаты по строкам
            $allResults = array();
            if (!empty($this->request->post['all_results'])) {
                $prevResults = json_decode($this->request->post['all_results'], true);
                if (is_array($prevResults)) {
                    $allResults = $prevResults;
                }
            }
            $allResults = array_merge($allResults, $batchResults);

            // Сохраняем в файл (не в сессию — чтобы не переполнить)
            $resultFile = DIR_UPLOAD . 'evrika_import/result_' . time() . '_' . uniqid() . '.json';
            file_put_contents($resultFile, json_encode($allResults, JSON_UNESCAPED_UNICODE));

            $this->model_extension_module_evrika_product_import->saveLog(array(
                'filename'    => isset($this->session->data['evrika_import_filename']) ? $this->session->data['evrika_import_filename'] : 'unknown',
                'total'       => $total,
                'created_cnt' => $totalCreated,
                'updated_cnt' => $totalUpdated,
                'skipped_cnt' => $totalSkipped,
                'errors'      => json_encode($allErrors, JSON_UNESCAPED_UNICODE),
            ));

            // Сохраняем итог для страницы отчёта
            $this->session->data['evrika_import_result'] = array(
                'filename'    => isset($this->session->data['evrika_import_filename']) ? $this->session->data['evrika_import_filename'] : '',
                'total'       => $total,
                'created'     => $totalCreated,
                'updated'     => $totalUpdated,
                'skipped'     => $totalSkipped,
                'errors'      => $allErrors,
                'result_file' => $resultFile,
            );

            // Удаляем временный файл
            if (!empty($this->session->data['evrika_import_file']) && file_exists($this->session->data['evrika_import_file'])) {
                @unlink($this->session->data['evrika_import_file']);
            }

            // Очищаем сессию
            unset(
                $this->session->data['evrika_import_file'],
                $this->session->data['evrika_import_ext'],
                $this->session->data['evrika_import_filename'],
                $this->session->data['evrika_import_header'],
                $this->session->data['evrika_import_mapping'],
                $this->session->data['evrika_import_preset_id']
            );

            $json['redirect'] = $this->url->link('extension/module/evrika_product_import/report', 'user_token=' . $this->session->data['user_token'], true);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    // -------------------------------------------------------------------------
    // ШАГ 3: Страница отчёта
    // -------------------------------------------------------------------------
    public function report() {
        $this->load->language('extension/module/evrika_product_import');

        if (empty($this->session->data['evrika_import_result'])) {
            $this->response->redirect($this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $this->document->setTitle($this->language->get('heading_title'));

        $result = $this->session->data['evrika_import_result'];
        unset($this->session->data['evrika_import_result']);

        $data = $this->buildCommonData();
        $data['result']         = $result;
        $data['action_new']     = $this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_catalog'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_download_errors'] = $this->url->link('extension/module/evrika_product_import/downloadErrors', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_edit_product'] = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'], true);
        $data['url_catalog']   = HTTP_CATALOG;
        $data['error_count']   = !empty($result['errors']) ? count($result['errors']) : 0;

        // Загружаем детальные результаты из файла
        $data['rows'] = array();
        if (!empty($result['result_file']) && file_exists($result['result_file'])) {
            $fileRows = json_decode(file_get_contents($result['result_file']), true);
            if (is_array($fileRows)) {
                $data['rows'] = $fileRows;
            }
            @unlink($result['result_file']);
        }

        // Кладём ошибки обратно в сессию для скачивания
        if (!empty($result['errors'])) {
            $this->session->data['evrika_import_errors'] = $result['errors'];
        }

        $this->response->setOutput($this->load->view('extension/module/evrika_product_import/report', $data));
    }

    // -------------------------------------------------------------------------
    // Скачать лог ошибок (CSV)
    // -------------------------------------------------------------------------
    public function downloadErrors() {
        if (!$this->validate()) {
            die('Access denied');
        }

        $errors = isset($this->session->data['evrika_import_errors']) ? $this->session->data['evrika_import_errors'] : array();

        $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="import_errors_' . date('Y-m-d_H-i') . '.csv"');

        $output = "\xEF\xBB\xBF"; // BOM для Excel
        $output .= "Строка;Описание ошибки\n";
        foreach ($errors as $error) {
            $output .= '"' . str_replace('"', '""', $error) . '"' . "\n";
        }

        $this->response->setOutput($output);
    }

    // -------------------------------------------------------------------------
    // AJAX: Сохранить пресет
    // -------------------------------------------------------------------------
    public function savePreset() {
        $this->load->language('extension/module/evrika_product_import');
        $this->load->model('extension/module/evrika_product_import');

        $json = array('success' => false);

        if (!$this->validate()) {
            $json['error'] = $this->error['warning'];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $name     = isset($this->request->post['preset_name']) ? trim($this->request->post['preset_name']) : '';
        $mapping  = isset($this->request->post['mapping'])  ? $this->request->post['mapping']  : array();
        $settings = isset($this->request->post['settings']) ? $this->request->post['settings'] : array();

        if ($name === '') {
            $json['error'] = $this->language->get('error_preset_name');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $id = $this->model_extension_module_evrika_product_import->savePreset($name, $mapping, $settings);

        $json['success']   = true;
        $json['preset_id'] = $id;
        $json['name']      = $name;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    // -------------------------------------------------------------------------
    // AJAX: Загрузить пресет
    // -------------------------------------------------------------------------
    public function loadPreset() {
        $this->load->language('extension/module/evrika_product_import');
        $this->load->model('extension/module/evrika_product_import');

        $json = array('success' => false);

        if (!$this->validate()) {
            $json['error'] = $this->error['warning'];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $id     = isset($this->request->get['preset_id']) ? (int)$this->request->get['preset_id'] : 0;
        $preset = $this->model_extension_module_evrika_product_import->getPreset($id);

        if (!$preset) {
            $json['error'] = $this->language->get('error_preset_not_found');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $json['success']  = true;
        $json['mapping']  = json_decode($preset['mapping'], true);
        $json['settings'] = json_decode($preset['settings'], true);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    // -------------------------------------------------------------------------
    // AJAX/POST: Удалить пресет
    // -------------------------------------------------------------------------
    public function deletePreset() {
        $this->load->language('extension/module/evrika_product_import');
        $this->load->model('extension/module/evrika_product_import');

        $json = array('success' => false);

        if (!$this->validate()) {
            $json['error'] = $this->error['warning'];
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $id = isset($this->request->post['preset_id']) ? (int)$this->request->post['preset_id'] : 0;
        $this->model_extension_module_evrika_product_import->deletePreset($id);

        $json['success'] = true;
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // -------------------------------------------------------------------------
    // install / uninstall
    // -------------------------------------------------------------------------
    public function install() {
        $this->load->model('extension/module/evrika_product_import');
        $this->model_extension_module_evrika_product_import->install();
    }

    public function uninstall() {
        $this->load->model('extension/module/evrika_product_import');
        $this->model_extension_module_evrika_product_import->uninstall();
    }

    // -------------------------------------------------------------------------
    // Вспомогательные методы
    // -------------------------------------------------------------------------

    /**
     * Парсит XLS/XLSX файл и возвращает массив строк.
     * Возвращает null при ошибке парсинга.
     */
    private function parseFile(string $path, string $ext): ?array {
        if (!file_exists($path)) {
            return null;
        }

        if ($ext === 'xlsx') {
            require_once DIR_SYSTEM . 'library/evrika/xlsimport/SimpleXLSX.php';
            $xlsx = \Shuchkin\SimpleXLSX::parse($path);
            if (!$xlsx) {
                return null;
            }
            return $xlsx->rows();
        }

        // .xls (Excel 97-2003)
        require_once DIR_SYSTEM . 'library/evrika/xlsimport/SimpleXLS.php';
        $xls = \Shuchkin\SimpleXLS::parse($path);
        if (!$xls) {
            return null;
        }
        return $xls->rows();
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/evrika_product_import')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return empty($this->error);
    }

    private function buildCommonData() {
        $data = array();

        $data['heading_title'] = $this->language->get('heading_title');
        $data['user_token']    = $this->session->data['user_token'];

        // Хлебные крошки
        $data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ),
            array(
                'text' => $this->language->get('text_catalog'),
                'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true),
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/evrika_product_import', 'user_token=' . $this->session->data['user_token'], true),
            ),
        );

        // Шапка, меню, подвал админки
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // Общие языковые строки в шаблон
        foreach (array(
            'text_step1', 'text_step2', 'text_step3',
            'text_upload_intro', 'entry_file', 'text_file_hint', 'button_upload',
            'text_presets', 'text_no_presets', 'text_preset_name',
            'button_load_preset', 'button_delete_preset', 'button_save_preset',
            'text_import_history', 'text_no_history',
            'column_date', 'column_file', 'column_created', 'column_updated', 'column_skipped', 'column_errors',
            'text_preview', 'text_mapping', 'text_mapping_hint',
            'column_from_file', 'column_sample', 'column_oc_field',
            'text_import_settings', 'entry_import_mode',
            'text_mode_create', 'text_mode_update', 'text_mode_both',
            'entry_key_field', 'entry_default_status', 'text_status_enabled', 'text_status_disabled',
            'text_category_section', 'text_category_hint', 'entry_default_category',
            'text_recommended', 'text_all_categories', 'text_no_category', 'text_matches',
            'button_start_import', 'button_back', 'button_new_import', 'button_go_catalog',
            'button_download_errors',
            'text_importing', 'text_progress', 'text_please_wait',
            'text_import_done', 'text_result_created', 'text_result_updated',
            'text_result_skipped', 'text_result_errors', 'text_errors_detail',
            'column_row', 'column_product', 'column_reason',
            'error_permission', 'error_no_file', 'error_file_type',
        ) as $key) {
            $data[$key] = $this->language->get($key);
        }

        return $data;
    }
}
