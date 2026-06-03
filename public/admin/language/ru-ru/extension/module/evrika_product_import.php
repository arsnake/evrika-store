<?php
// Heading
$_['heading_title']             = 'Импорт товаров из XLS';

// Breadcrumbs
$_['text_home']                 = 'Главная';
$_['text_catalog']              = 'Каталог';
$_['text_extension']            = 'Расширения';
$_['text_module']               = 'Модули';

// Step labels
$_['text_step1']                = 'Шаг 1 — Загрузка файла';
$_['text_step2']                = 'Шаг 2 — Настройка маппинга';
$_['text_step3']                = 'Шаг 3 — Результат импорта';

// Upload form
$_['text_upload_intro']         = 'Загрузите файл Excel (.xlsx или .xls) для импорта товаров.';
$_['entry_file']                = 'Файл Excel';
$_['text_file_hint']            = 'Поддерживаются форматы';
$_['button_upload']             = 'Загрузить и перейти к маппингу';

// Presets
$_['text_presets']              = 'Сохранённые пресеты маппинга';
$_['text_no_presets']           = 'Нет сохранённых пресетов';
$_['text_preset_name']          = 'Название пресета';
$_['button_load_preset']        = 'Загрузить';
$_['button_delete_preset']      = 'Удалить';
$_['button_save_preset']        = 'Сохранить как пресет';

// Import history
$_['text_import_history']       = 'История импортов';
$_['text_no_history']           = 'История пуста';
$_['column_date']               = 'Дата';
$_['column_file']               = 'Файл';
$_['column_created']            = 'Создано';
$_['column_updated']            = 'Обновлено';
$_['column_skipped']            = 'Пропущено';
$_['column_errors']             = 'Ошибок';

// Mapping page
$_['text_preview']              = 'Предпросмотр данных (первые 5 строк)';
$_['text_mapping']              = 'Маппинг столбцов';
$_['text_mapping_hint']         = 'Для каждого столбца из файла выберите соответствующее поле товара в OcStore или «Не импортировать».';
$_['column_from_file']          = 'Столбец в файле';
$_['column_sample']             = 'Примеры значений';
$_['column_oc_field']           = 'Поле OcStore';

// Import settings
$_['text_import_settings']      = 'Настройки импорта';
$_['entry_import_mode']         = 'Режим импорта';
$_['text_mode_create']          = 'Только создавать новые товары';
$_['text_mode_update']          = 'Только обновлять существующие';
$_['text_mode_both']            = 'Создавать и обновлять';
$_['entry_key_field']           = 'Ключевое поле для поиска существующих товаров';
$_['entry_default_status']      = 'Статус новых товаров';
$_['text_status_enabled']       = 'Активен';
$_['text_status_disabled']      = 'Отключён';

// Category
$_['text_category_section']     = 'Категория по умолчанию';
$_['text_category_hint']        = 'Если в файле нет столбца «Категория» или категория не найдена, товар будет привязан к этой категории.';
$_['entry_default_category']    = 'Категория';
$_['text_recommended']          = 'Рекомендованные';
$_['text_all_categories']       = 'Все категории';
$_['text_no_category']          = '— Не указывать категорию —';
$_['text_matches']              = 'совп.';

// Buttons
$_['button_start_import']       = 'Начать импорт';
$_['button_back']               = 'Назад';
$_['button_new_import']         = 'Новый импорт';
$_['button_go_catalog']         = 'Перейти в каталог товаров';
$_['button_download_errors']    = 'Скачать лог ошибок (CSV)';

// Progress / import
$_['text_importing']            = 'Выполняется импорт...';
$_['text_progress']             = 'Обработано %d из %d товаров';
$_['text_please_wait']          = 'Пожалуйста, не закрывайте страницу.';

// Report
$_['text_import_done']          = 'Импорт завершён';
$_['text_result_created']       = 'Создано товаров';
$_['text_result_updated']       = 'Обновлено товаров';
$_['text_result_skipped']       = 'Пропущено';
$_['text_result_errors']        = 'Ошибок';
$_['text_errors_detail']        = 'Подробности ошибок';
$_['column_row']                = 'Строка';
$_['column_product']            = 'Товар';
$_['column_reason']             = 'Причина';

// Errors
$_['error_permission']          = 'Ошибка: У вас нет прав для управления этим модулем.';
$_['error_no_file']             = 'Файл не выбран.';
$_['error_file_type']           = 'Недопустимый тип файла. Разрешены только .xlsx и .xls.';
$_['error_file_size']           = 'Файл слишком большой. Максимальный размер: 10 МБ.';
$_['error_file_upload']         = 'Ошибка загрузки файла.';
$_['error_parse']               = 'Не удалось прочитать файл. Убедитесь что файл не повреждён.';
$_['error_no_data']             = 'В файле не найдено данных.';
$_['error_no_name_column']      = 'Не указан столбец «Название товара». Маппинг не может быть сохранён.';
$_['error_preset_name']         = 'Введите название пресета.';
$_['error_preset_not_found']    = 'Пресет не найден.';

// Field names (for mapping select)
$_['field_skip']                = '— Не импортировать —';
$_['field_name']                = 'Название товара';
$_['field_model']               = 'Код товара (model)';
$_['field_sku']                 = 'SKU / Артикул';
$_['field_upc']                 = 'UPC / Штрихкод';
$_['field_ean']                 = 'EAN';
$_['field_manufacturer']        = 'Бренд / Производитель';
$_['field_price']               = 'Цена';
$_['field_quantity']            = 'Количество (остаток)';
$_['field_category']            = 'Категория';
$_['field_description']         = 'Описание';
$_['field_meta_title']          = 'SEO-заголовок (meta title)';
$_['field_meta_description']    = 'SEO-описание (meta description)';
$_['field_status']              = 'Статус (0/1)';
$_['field_minimum']             = 'Минимальное кол-во';
$_['field_weight']              = 'Вес';
$_['field_sort_order']          = 'Порядок сортировки';
$_['field_tag']                 = 'Теги';
