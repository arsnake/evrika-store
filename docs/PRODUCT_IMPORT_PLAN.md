# Модуль импорта товаров из XLS — Plan разработки

> **Статус:** В разработке  
> **Начат:** 2026-05-31  
> **Разработчик:** Claude Code (claude-sonnet-4-6)  
> **PHP:** 7.4 (prod constraint)

---

## Контекст

Модуль для администраторов OcStore. Позволяет загружать Excel-файлы (XLSX/XLS) накладных/прайсов поставщиков и импортировать товары с гибкой настройкой маппинга столбцов.

**Ключевые требования:**
- Автоопределение строки-заголовка в файле
- Автоматическое сопоставление столбцов с полями товара (fuzzy-matching)
- Ручная настройка любого маппинга, включая «Не импортировать»
- Умный выбор категории: если в файле нет — предлагаем список с рекомендациями на основе названий товаров
- Сохранение пресетов маппинга
- История импортов
- PHP 7.4 совместимость
- Деплой-документ в конце

---

## Файловая структура (итог разработки)

```
public/
├── admin/
│   ├── controller/extension/module/
│   │   └── evrika_product_import.php
│   ├── model/extension/module/
│   │   └── evrika_product_import.php
│   ├── language/ru-ru/extension/module/
│   │   └── evrika_product_import.php
│   └── view/template/extension/module/evrika_product_import/
│       ├── upload.twig          ← Шаг 1: загрузка файла
│       ├── mapping.twig         ← Шаг 2: маппинг столбцов
│       └── report.twig          ← Шаг 3: результат импорта
│
└── system/library/evrika/xlsimport/
    ├── SimpleXLSX.php           ← бандлим библиотеку (MIT)
    └── ColumnDetector.php       ← наша логика автодетекта

docs/
└── DEPLOY_PRODUCT_IMPORT.md     ← создаём в самом конце
```

---

## БД-таблицы

```sql
-- Сохранённые пресеты маппинга
CREATE TABLE IF NOT EXISTS `oc_evrika_import_preset` (
  `preset_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(128) NOT NULL,
  `mapping`     TEXT NOT NULL,        -- JSON: {col_index: oc_field, ...}
  `settings`    TEXT NOT NULL,        -- JSON: mode, key_field, default_status, etc.
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`preset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- История импортов
CREATE TABLE IF NOT EXISTS `oc_evrika_import_log` (
  `log_id`       INT(11) NOT NULL AUTO_INCREMENT,
  `filename`     VARCHAR(255) NOT NULL,
  `preset_name`  VARCHAR(128) DEFAULT NULL,
  `created_at`   DATETIME NOT NULL,
  `total`        INT(11) NOT NULL DEFAULT 0,
  `created_cnt`  INT(11) NOT NULL DEFAULT 0,
  `updated_cnt`  INT(11) NOT NULL DEFAULT 0,
  `skipped_cnt`  INT(11) NOT NULL DEFAULT 0,
  `errors`       TEXT DEFAULT NULL,   -- JSON array of error strings
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Шаги разработки

### ШАГ 1 — Библиотека парсинга ✅/🔲
**Файлы:**
- `public/system/library/evrika/xlsimport/SimpleXLSX.php`

**Задача:** Скачать и разместить SimpleXLSX (shuchkin/simple-xlsx, MIT, PHP 5.2+).  
Проверить что класс загружается через `$this->load->library()` или прямым `require_once`.

---

### ШАГ 2 — ColumnDetector ✅/🔲
**Файл:** `public/system/library/evrika/xlsimport/ColumnDetector.php`

**Методы:**
```php
class ColumnDetector {
    // Найти строку-заголовок (возвращает индекс строки 0-based)
    public function detectHeaderRow(array $rows): int {}

    // Для каждого столбца предложить поле OcStore (или null)
    public function suggestMapping(array $headerRow): array {}

    // Словарь синонимов для fuzzy-matching
    private function getDictionary(): array {}

    // Нечёткое сравнение через stripos + levenshtein
    private function matchColumn(string $colName): ?string {}
}
```

**Словарь маппинга:**
```
код, code, код товара                         → model
артикул, sku, арт, art                        → sku
штрихкод, шк, barcode, upc, ean, ean-13      → upc
товар, наименование, название, name, item     → name
бренд, brand, производитель, manufacturer     → manufacturer
количество, кол-во, qty, quantity, остаток    → quantity
цена, price, стоимость, цена опт              → price
описание, description                         → description
категория, category, раздел                   → category
```

---

### ШАГ 3 — Языковой файл ✅/🔲
**Файл:** `public/admin/language/ru-ru/extension/module/evrika_product_import.php`

Все строки интерфейса: заголовки, подписи полей, кнопки, ошибки.

---

### ШАГ 4 — Контроллер ✅/🔲
**Файл:** `public/admin/controller/extension/module/evrika_product_import.php`

**Методы:**
```php
class ControllerExtensionModuleEvrikaProductImport extends Controller {
    public function index()           // Шаг 1: форма загрузки
    public function upload()          // POST: принимаем файл, парсим, редирект на mapping
    public function mapping()         // Шаг 2: страница маппинга
    public function import()          // POST AJAX: выполняем импорт батчами
    public function savePreset()      // AJAX: сохранить пресет
    public function loadPreset()      // AJAX: загрузить пресет (JSON)
    public function deletePreset()    // AJAX: удалить пресет
    public function report()          // Шаг 3: страница отчёта
    public function install()         // CREATE TABLE ...
    public function uninstall()       // DROP TABLE ...
    private function validate()       // проверка прав доступа
}
```

**Флоу данных:**
1. `index()` → форма загрузки файла
2. `upload()` → сохраняем файл во временную папку, парсим, кладём данные в session, редирект на `mapping()`
3. `mapping()` → строим страницу: превью данных + форма маппинга + умный список категорий
4. `import()` → AJAX POST с маппингом и настройками → возвращает `{done, total, created, updated, errors}`
5. `report()` → страница с итогом из session

**Сессионные ключи:**
```
evrika_import_file    — путь к временному файлу
evrika_import_rows    — все строки файла (array)
evrika_import_header  — индекс строки-заголовка
evrika_import_result  — результат импорта для report()
```

---

### ШАГ 5 — Модель ✅/🔲
**Файл:** `public/admin/model/extension/module/evrika_product_import.php`

**Методы:**
```php
class ModelExtensionModuleEvrikaProductImport extends Model {
    // Пресеты
    public function savePreset(string $name, array $mapping, array $settings): int {}
    public function getPresets(): array {}
    public function getPreset(int $id): array {}
    public function deletePreset(int $id): void {}

    // Лог
    public function saveLog(array $data): int {}
    public function getLogs(int $limit = 20): array {}

    // Категории с умной сортировкой
    public function getCategoriesSorted(array $productNames): array {}

    // Поиск существующего товара
    public function findProduct(string $field, string $value): ?int {}

    // Производитель: найти или создать
    public function findOrCreateManufacturer(string $name): int {}

    // Импорт одного товара
    public function importProduct(array $data, array $settings): string {} // 'created'|'updated'|'skipped'
}
```

**Умная сортировка категорий (`getCategoriesSorted`):**
1. Получаем все категории из `oc_category_description`
2. Для каждой категории считаем score:  
   `score = count(productNames где stripos(name, catName) !== false)`
3. Сортируем: score DESC, затем name ASC
4. Возвращаем массив `[{category_id, name, score, path}, ...]`

---

### ШАГ 6 — Шаблон: Шаг 1 (upload.twig) ✅/🔲
**Файл:** `public/admin/view/template/extension/module/evrika_product_import/upload.twig`

**Содержимое:**
- Заголовок + хлебные крошки
- Форма загрузки файла (accept=".xlsx,.xls")
- Блок «Последние импорты» (таблица из `oc_evrika_import_log`)
- Блок «Сохранённые пресеты» (список с кнопками загрузить/удалить)

---

### ШАГ 7 — Шаблон: Шаг 2 (mapping.twig) ✅/🔲
**Файл:** `public/admin/view/template/extension/module/evrika_product_import/mapping.twig`

**Содержимое:**
- Превью таблицы: первые 5 строк данных
- Форма маппинга: для каждого столбца — его имя из файла + `<select>` с полями OcStore
- Настройки импорта:
  - Режим: создавать / обновлять / оба
  - Ключевое поле для поиска существующих (model / sku / upc / name)
  - Статус новых товаров (активен / отключён)
- Блок категории:
  - Чекбокс «Назначить категорию по умолчанию»
  - `<select>` с категориями (сначала рекомендованные со score > 0, с пометкой «Рекомендовано», затем остальные)
- Кнопки: «Сохранить пресет» + «Начать импорт»

**Поля OcStore для `<select>` маппинга:**
```
— Не импортировать —
Название товара (name)       *
Код товара (model)
SKU/Артикул (sku)
UPC/Штрихкод (upc)
EAN
Бренд/Производитель (manufacturer)
Цена (price)
Количество (quantity)
Описание (description)
SEO-заголовок (meta_title)
SEO-описание (meta_description)
Статус (status)
Минимальное кол-во (minimum)
Вес (weight)
Порядок сортировки (sort_order)
Теги (tag)
Категория (category)
```

---

### ШАГ 8 — Шаблон: Шаг 3 (report.twig) ✅/🔲
**Файл:** `public/admin/view/template/extension/module/evrika_product_import/report.twig`

**Содержимое:**
- Итоговые счётчики: Создано / Обновлено / Пропущено / Ошибок
- Прогресс-бар (заполнен на 100%)
- Таблица ошибок (строка файла, поле, причина)
- Кнопка «Скачать лог ошибок» (CSV)
- Кнопка «Новый импорт»
- Кнопка «Перейти в каталог товаров»

---

### ШАГ 9 — JavaScript (inline в mapping.twig) ✅/🔲

```javascript
// Запуск импорта: AJAX POST на controller/import
// Показываем прогресс-бар пока идёт обработка
// После завершения — редирект на report()
function startImport(formData) { ... }

// Сохранение пресета: AJAX POST
function savePreset() { ... }

// Загрузка пресета: AJAX GET → обновляем <select> маппинга
function loadPreset(presetId) { ... }
```

---

### ШАГ 10 — Регистрация модуля в Extensions ✅/🔲

Добавить в `oc_extension` через SQL или через UI:
```sql
INSERT INTO `oc_extension` (`extension_id`, `type`, `code`)
VALUES (NULL, 'module', 'evrika_product_import');
```

Настроить права доступа для группы Administrator:
- `extension/module/evrika_product_import` → Access + Modify

---

### ШАГ 11 — Деплой-документ ✅/🔲
**Файл:** `docs/DEPLOY_PRODUCT_IMPORT.md`

Создать в конце разработки. Содержимое:
- Список всех новых файлов с путями (copy-paste ready)
- SQL для создания таблиц (IF NOT EXISTS)
- Шаги регистрации модуля в Extensions
- Настройка прав доступа
- Проверочный чеклист
- Rollback-инструкция

---

## PHP 7.4 — что запрещено

| Запрещено (PHP 8+) | Заменяем на |
|---|---|
| `match` expression | `switch/case` |
| `str_contains()` | `strpos() !== false` |
| `str_starts_with()` | `strpos() === 0` |
| Null-safe `?->` | `isset() ? ... : null` |
| Named arguments | Позиционные аргументы |
| Union types `int\|string` | PHPDoc + без type hint |
| `array_is_list()` | Ручная проверка |
| `Fiber` | — |

---

## Поля OcStore для импорта

| Поле в форме | Таблица | Колонка |
|---|---|---|
| name | oc_product_description | name |
| model | oc_product | model |
| sku | oc_product | sku |
| upc | oc_product | upc |
| ean | oc_product | ean |
| manufacturer | oc_manufacturer + oc_product | manufacturer_id |
| price | oc_product | price |
| quantity | oc_product | quantity |
| description | oc_product_description | description |
| meta_title | oc_product_description | meta_title |
| meta_description | oc_product_description | meta_description |
| status | oc_product | status |
| minimum | oc_product | minimum |
| weight | oc_product | weight |
| sort_order | oc_product | sort_order |
| tag | oc_product_description | tag |
| category | oc_product_to_category | category_id |

---

## Текущий прогресс

- [x] План сохранён в docs/PRODUCT_IMPORT_PLAN.md
- [x] Шаг 1: SimpleXLSX библиотека — `system/library/evrika/xlsimport/SimpleXLSX.php`
- [x] Шаг 2: ColumnDetector класс — `system/library/evrika/xlsimport/ColumnDetector.php`
- [x] Шаг 3: Языковой файл — `admin/language/ru-ru/extension/module/evrika_product_import.php`
- [x] Шаг 4: Контроллер — `admin/controller/extension/module/evrika_product_import.php`
- [x] Шаг 5: Модель — `admin/model/extension/module/evrika_product_import.php`
- [x] Шаг 6: upload.twig
- [x] Шаг 7: mapping.twig
- [x] Шаг 8: report.twig
- [x] Шаг 9: JavaScript (inline в mapping.twig)
- [ ] Шаг 10: Регистрация модуля в Extensions (делается через UI/SQL на сервере)
- [x] Шаг 11: Деплой-документ — `docs/DEPLOY_PRODUCT_IMPORT.md`

## Статус: КОД НАПИСАН. Требует тестирования и регистрации модуля в Extensions.

---

## Как продолжить после перерыва

1. Прочитать этот файл
2. Посмотреть на чекбоксы — найти первый незакрытый шаг
3. Прочитать соответствующий раздел плана
4. Продолжить разработку с этого шага
