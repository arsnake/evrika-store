# Деплой: Модуль импорта товаров из XLS

> Документ описывает как развернуть модуль `evrika_product_import` на продакшен-сервере.  
> PHP 7.4, OcStore (OpenCart 3.0.3.7)

---

## Что делает модуль

- Загружает файлы `.xlsx` (Excel 2007+) и `.xls` (Excel 97-2003)
- Автоматически находит строку-заголовок и сопоставляет столбцы с полями товаров
- Администратор вручную настраивает маппинг, сохраняет/загружает пресеты
- Умный выбор категорий: рекомендованные категории на основе названий товаров из файла
- Категории по типам товаров: группировка по первому слову названия, мультиселект категорий для каждой группы
- Режимы: создавать / обновлять / оба (аддитивно — категории по умолчанию + категории группы)
- Автоматическое создание производителей с заполнением `manufacturer_description`, `manufacturer_to_store` и SEO URL
- Автоматическая генерация SEO URL для создаваемых товаров (транслитерация, уникальность)
- Пакетный (batch) AJAX-импорт без таймаутов
- Детальный отчёт: созданные / обновлённые / пропущенные товары, категории, ссылки в магазин и редактор
- Ведёт историю импортов и лог ошибок

---

## Файлы для копирования на сервер

Скопировать следующие файлы и папки **сохраняя структуру путей** относительно корня OcStore (`/var/www/...` или `public_html/`):

```
public/
├── system/library/evrika/xlsimport/
│   ├── SimpleXLSX.php
│   ├── SimpleXLS.php
│   └── ColumnDetector.php
│
└── admin/
    ├── controller/extension/module/evrika_product_import.php
    ├── model/extension/module/evrika_product_import.php
    ├── language/ru-ru/extension/module/evrika_product_import.php
    └── view/template/extension/module/evrika_product_import/
        ├── upload.twig
        ├── mapping.twig
        └── report.twig

ocmod/
└── evrika_product_import_menu.ocmod.xml   ← пункт меню в разделе Каталог
```

**Итого:** 8 PHP/twig файлов + 1 OCMod-файл.

---

## Пошаговая инструкция деплоя

### 1. Загрузить файлы на сервер

Через SFTP / scp / панель хостинга скопировать все 8 файлов в соответствующие директории.

Если используешь `scp`:
```bash
# Библиотеки
scp -r public/system/library/evrika/ user@server:/path/to/ocstore/system/library/

# Контроллер
scp public/admin/controller/extension/module/evrika_product_import.php \
    user@server:/path/to/ocstore/admin/controller/extension/module/

# Модель
scp public/admin/model/extension/module/evrika_product_import.php \
    user@server:/path/to/ocstore/admin/model/extension/module/

# Языковой файл
scp public/admin/language/ru-ru/extension/module/evrika_product_import.php \
    user@server:/path/to/ocstore/admin/language/ru-ru/extension/module/

# Шаблоны
scp -r public/admin/view/template/extension/module/evrika_product_import/ \
    user@server:/path/to/ocstore/admin/view/template/extension/module/
```

### 2. Установить OCMod пункта меню

Скопировать OCMod-файл на сервер и установить через Extension Installer **или** применить вручную:

**Вариант А — через Extension Installer (рекомендуется):**
1. Упаковать в zip: файл должен называться `install.xml` внутри архива
   ```bash
   cd /path/to/project/ocmod/
   zip evrika_product_import_menu.ocmod.zip install.xml
   # Примечание: предварительно переименуй или создай копию как install.xml
   ```
   Или используй PHP-скрипт (см. OCMOD_HOWTO.md)
2. Admin → Extensions → Extension Installer → загрузить zip
3. Admin → Extensions → Modifications → Refresh

**Вариант Б — developer-режим (без установки, для быстрого деплоя):**
```bash
cp evrika_product_import_menu.ocmod.xml /path/to/ocstore/system/
```
Затем Admin → Extensions → Modifications → Refresh

### 3. Создать таблицы в базе данных

Подключиться к MySQL и выполнить:

```sql
-- Заменить 'oc_' на актуальный префикс таблиц OcStore (если отличается)

CREATE TABLE IF NOT EXISTS `oc_evrika_import_preset` (
    `preset_id`  INT(11) NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(128) NOT NULL,
    `mapping`    TEXT NOT NULL,
    `settings`   TEXT NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`preset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_evrika_import_log` (
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
```

Проверить что таблицы созданы:
```sql
SHOW TABLES LIKE 'oc_evrika_import%';
-- Должно вернуть 2 строки
```

### 4. Зарегистрировать модуль в OcStore

Вариант А — через SQL (быстрее):
```sql
INSERT INTO `oc_extension` (`extension_id`, `type`, `code`)
SELECT NULL, 'module', 'evrika_product_import'
WHERE NOT EXISTS (
    SELECT 1 FROM `oc_extension`
    WHERE `type` = 'module' AND `code` = 'evrika_product_import'
);
```

Вариант Б — через админку:
1. Перейти: **Extensions → Extensions → Modules**
2. Найти модуль «Импорт товаров из XLS»
3. Нажать кнопку «Install» (значок +)

### 5. Настроить права доступа

В админке: **System → Users → User Groups → Administrator**

Добавить права (Access + Modify) для:
```
extension/module/evrika_product_import
```

Или через SQL:
```sql
-- Получить permission JSON для группы Administrator (обычно group_id = 1)
SELECT `permission` FROM `oc_user_group` WHERE `user_group_id` = 1;

-- После редактирования JSON добавить в оба массива access[] и modify[]:
-- "extension/module/evrika_product_import"
-- Обновить:
UPDATE `oc_user_group`
SET `permission` = '{"access": [..., "extension/module/evrika_product_import"], "modify": [..., "extension/module/evrika_product_import"]}'
WHERE `user_group_id` = 1;
```

> **Проще через UI**: System → Users → User Groups → Administrator → поставить галки для нового модуля → Save

### 6. Проверить права на директорию storage/upload/

Модуль сохраняет загруженные XLS-файлы и JSON-результаты импорта в `storage/upload/evrika_import/`.  
Папка `evrika_import/` создаётся **автоматически** при первой загрузке файла.

Нужно убедиться, что директория `storage/upload/` существует и доступна на запись:

```bash
ls -la /path/to/ocstore/storage/
# Папки cache/, download/, logs/, modification/, session/, upload/ должны существовать

chmod 755 /path/to/ocstore/storage/upload/
# или 775 если нужно групповое право записи
```

> **Важно:** В стандартной OcStore-установке `storage/` находится **вне** `public_html/` (для безопасности). Путь задаётся в `config.php` переменной `DIR_STORAGE`. Убедись, что это правильная папка для своего сервера.

### 7. Проверить права на файлы

```bash
# Все загруженные файлы должны иметь права 644
chmod 644 /path/to/ocstore/admin/controller/extension/module/evrika_product_import.php
chmod 644 /path/to/ocstore/admin/model/extension/module/evrika_product_import.php
chmod 644 /path/to/ocstore/admin/language/ru-ru/extension/module/evrika_product_import.php
chmod 644 /path/to/ocstore/system/library/evrika/xlsimport/SimpleXLSX.php
chmod 644 /path/to/ocstore/system/library/evrika/xlsimport/SimpleXLS.php
chmod 644 /path/to/ocstore/system/library/evrika/xlsimport/ColumnDetector.php
chmod 644 /path/to/ocstore/admin/view/template/extension/module/evrika_product_import/*.twig

# Директории — 755
chmod 755 /path/to/ocstore/system/library/evrika/
chmod 755 /path/to/ocstore/system/library/evrika/xlsimport/
chmod 755 /path/to/ocstore/admin/view/template/extension/module/evrika_product_import/
```

---

## Чеклист проверки после деплоя

```
□ Все 8 файлов загружены в правильные директории
□ OCMod-файл установлен (через Installer или system/)
□ Modifications → Refresh выполнен
□ В левом меню раздела «Каталог» появился пункт «Импорт из XLS»
□ Таблицы oc_evrika_import_preset и oc_evrika_import_log созданы в БД
□ Модуль появился в Extensions → Extensions → Modules (после Install)
□ Права доступа настроены для Administrator
□ Директория storage/upload/ существует и доступна на запись
□ Открывается страница через меню Каталог → Импорт из XLS
□ Шапка и левое меню админки отображаются корректно (не пустая страница)
□ Форма загрузки файла отображается корректно
□ Тестовый импорт: загрузить небольшой .xlsx (2–5 строк)
□ Страница маппинга открывается, столбцы определились
□ Блок «Категории по типам товаров» отображается с мультиселектом
□ Импорт завершился, отчёт показывает созданные/обновлённые товары
□ Созданные товары имеют SEO URL (проверить в Catalog → Products → Edit → SEO)
□ Если в файле есть производитель: бренд создался, страница бренда открывается
□ История импортов отображается на главной странице модуля
```

---

## Rollback (откат)

Если что-то пошло не так:

### Быстрый откат — удалить файлы:
```bash
rm -rf /path/to/ocstore/system/library/evrika/xlsimport/
rm /path/to/ocstore/admin/controller/extension/module/evrika_product_import.php
rm /path/to/ocstore/admin/model/extension/module/evrika_product_import.php
rm /path/to/ocstore/admin/language/ru-ru/extension/module/evrika_product_import.php
rm -rf /path/to/ocstore/admin/view/template/extension/module/evrika_product_import/
```

### Удалить из БД:
```sql
DELETE FROM `oc_extension` WHERE `type` = 'module' AND `code` = 'evrika_product_import';
DROP TABLE IF EXISTS `oc_evrika_import_preset`;
DROP TABLE IF EXISTS `oc_evrika_import_log`;
```

> **Внимание:** DROP TABLE удалит все сохранённые пресеты и историю импортов безвозвратно.  
> Если хочешь сохранить данные — сделай дамп перед удалением:  
> `mysqldump -u user -p dbname oc_evrika_import_preset oc_evrika_import_log > backup_import.sql`

---

## Известные ограничения

- Поддерживаются оба формата: `.xlsx` (Excel 2007+) и `.xls` (Excel 97-2003). Библиотеки: SimpleXLSX и SimpleXLS (shuchkin, MIT).
- Максимальный размер файла: 10 МБ (настраивается в контроллере, параметр `10 * 1024 * 1024`).
- Изображения товаров через импорт не загружаются — только текстовые данные.
- Создание вложенной иерархии категорий не поддерживается — только привязка к существующим категориям.
- SEO URL генерируется из названия товара/бренда транслитерацией (кириллица → латиница). При конфликте добавляется суффикс `-1`, `-2` и т.д.

---

## Версия

| Параметр | Значение |
|---|---|
| Версия модуля | 1.1.0 |
| Дата обновления | 2026-05-31 |
| Совместимость | OcStore 3.0.3.7, PHP 7.4+ |
| Библиотеки | SimpleXLSX, SimpleXLS (shuchkin, MIT) |
