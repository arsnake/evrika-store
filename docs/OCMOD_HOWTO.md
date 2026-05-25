# Правила написания OCMod для этого OcStore

## Критическое правило № 1 — поиск ПОСТРОЧНЫЙ

Движок OCMod в `admin/controller/marketplace/modification.php` ищет совпадение так:

```php
$lines = explode("\n", $file_content);
foreach ($lines as $line) {
    if (stripos($line, $search) !== false) { // $line — ОДНА строка без \n
        // match!
    }
}
```

**Вывод: `<search>` должен содержать ровно одну строку.**  
Многострочный `<search>` содержит `\n`, которого в отдельной строке никогда нет → `stripos` всегда вернёт `false` → `NOT FOUND - OPERATIONS ABORTED!`

---

## Шаблон правильного OCMod

```xml
<?xml version="1.0" encoding="UTF-8"?>
<modification>
    <name>Название патча</name>
    <code>уникальный-код</code>
    <version>1.0</version>
    <author>Evrika</author>
    <link></link>

    <file path="catalog/controller/extension/module/slideshow.php">
        <operation>
            <!-- ОДНА строка, уникальная в файле -->
            <search><![CDATA[искомая строка без переносов]]></search>
            <!-- trim="true" убирает обёрточный \n из CDATA, не трогая внутренние отступы -->
            <!-- offset="N" заменяет N+1 строк начиная с найденной -->
            <add position="replace" offset="4" trim="true"><![CDATA[
полная замена блока
включая найденную строку
и N строк после неё
]]></add>
        </operation>
    </file>

</modification>
```

---

## Атрибуты `<add>`

| Атрибут | Где задаётся | Описание |
|---------|-------------|----------|
| `position` | `<add>` | `replace` / `before` / `after` |
| `offset` | `<add>` | Сколько строк ПОСЛЕ найденной тоже заменить (replace) или сдвинуть (before/after) |
| `trim` | `<add>` | `"true"` — срезает ведущий/хвостовой пробел всего блока (не внутренние строки) |

Атрибут `trim` на `<search>` применяется **по умолчанию** (если не указан обратный). Поиск всегда case-insensitive (`stripos`).

---

## Как заменить блок из N строк

Нужно заменить этот блок (5 строк):
```php
				$data['banners'][] = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize(...)
				);
```

Пишем:
```xml
<!-- Ищем только первую строку блока -->
<search><![CDATA[$data['banners'][] = array(]]></search>
<!-- offset="4" = заменяем найденную строку + 4 строки после = итого 5 строк -->
<add position="replace" offset="4" trim="true"><![CDATA[
				$data['banners'][] = array(
					'title'       => $result['title'],
					'link'        => $result['link'],
					'image'       => $this->model_tool_image->resize(...),
					'description' => isset($result['description']) ? $result['description'] : '',
				);
]]></add>
```

**Как работает `str_replace` при replace:**  
Найденная строка `"\t\t\t\t$data['banners'][] = array("` → в ней заменяется search-подстрока на trimmed add.  
Результат: `"\t\t\t\t"` + trimmed_add (ведущие табы исходной строки сохраняются).  
Поэтому в add нужно писать отступы так, как они будут в файле.

---

## Как вставить строки без замены

```xml
<search><![CDATA[уникальная строка-ориентир]]></search>
<!-- Вставить ДО найденной строки: -->
<add position="before"><![CDATA[новая строка 1
новая строка 2]]></add>

<!-- Вставить ПОСЛЕ найденной строки: -->
<add position="after"><![CDATA[новая строка]]></add>
```

Для `before`/`after` trim не нужен — add разбивается по `\n` и каждая строка вставляется отдельно.  
**Важно:** не начинай CDATA с `\n` для `before`/`after` — иначе первым элементом будет пустая строка.

---

## Уникальность строки поиска

Поиск найдёт **все** строки файла, где встречается подстрока. Если одна и та же строка есть в нескольких местах — патч применится к каждому.

Чтобы различить похожие строки (например, два INSERT в одном файле):
```php
// addBanner (строка 11): sort_order = '" .  (int)$banner_image  ← ДВОЙНОЙ пробел
// editBanner (строка 27): sort_order = '" . (int)$banner_image  ← одинарный пробел
```
Используй уникальную деталь строки как ключ поиска.

Если нет уникального содержимого — используй `index` атрибут:
```xml
<search index="0"><![CDATA[повторяющаяся строка]]></search> <!-- только первое вхождение -->
<search index="1"><![CDATA[повторяющаяся строка]]></search> <!-- только второе вхождение -->
```

---

## Пути к файлам

```xml
<file path="catalog/controller/extension/module/slideshow.php">
<file path="catalog/controller/extension/module/banner.php">
<file path="admin/model/design/banner.php">
<file path="admin/controller/design/banner.php">
<file path="admin/view/template/design/banner_form.twig">
```

Пути относительно корня OcStore (`public/`). Движок резолвит:
- `catalog/...` → `DIR_CATALOG` = `public/catalog/`
- `admin/...` → `DIR_APPLICATION` = `public/admin/`
- `system/...` → `DIR_SYSTEM` = `public/system/`

Поддерживаются glob-паттерны и `|` для нескольких файлов:
```xml
<file path="catalog/controller/{foo,bar}.php">
<file path="catalog/controller/foo.php|admin/controller/foo.php">
```

---

## Правила упаковки в zip

Extension Installer (`marketplace/install/xml()`) ищет **строго `install.xml`** в корне zip:

```php
$file = DIR_UPLOAD . 'tmp-' . $session . '/install.xml';
```

**Правильная структура zip:**
```
evrika-что-то.ocmod.zip
├── install.xml        ← содержимое OCMod XML (обязательно!)
└── upload/            ← опционально: PHP/twig файлы для копирования
    ├── catalog/...
    └── admin/...
```

Если XML назван иначе — Installer его не найдёт, в БД не запишет, в списке Modifications не появится.

**Пересборка zip (PHP):**
```php
$zip = new ZipArchive();
$zip->open('my-mod.ocmod.zip', ZipArchive::CREATE);
$zip->addFile('my-mod.ocmod.xml', 'install.xml'); // второй аргумент = имя внутри zip
$zip->close();
```

---

## Developer-режим (без установки)

Файлы `*.ocmod.xml` в `public/system/` подхватываются при Refresh **автоматически** — без записи в БД, без отображения в списке Modifications. Код движка:

```php
// purly for developers so they can run mods directly
$files = glob(DIR_SYSTEM . '*.ocmod.xml');
```

Используй для быстрой отладки. Перед релизом удали из `system/` и паси через Installer.

---

## Рабочий процесс разработки (Developer Workflow)

**Принцип:** пиши → refresh → смотри лог → правь → repeat. Zip делаешь только когда всё работает.

### Шаг за шагом

1. **Пиши OCMod** в `ocmod/my-mod.ocmod.xml`
2. **Скопируй в system/** (или создай симлинк — тогда копировать не надо):
   ```bat
   :: Симлинк (один раз, потом правишь только .xml в ocmod/)
   mklink "C:\wamp64\www\evrika\public\system\my-mod.ocmod.xml" ^
          "C:\wamp64\www\evrika\ocmod\my-mod.ocmod.xml"
   ```
3. **Refresh** в админке: Extensions → Modifications → кнопка Refresh
4. **Проверь лог**: `storage/logs/ocmod.log` — ищи `CODE:` (нашёл) или `NOT FOUND`
5. **Смотри результат**: `storage/modification/` — там лежат пропатченные файлы
6. Если не то — правишь `.xml`, снова Refresh, снова лог
7. **Новые PHP/twig файлы** (не патчи, а новые) — кладёшь сразу в `public/` в нужную папку
8. **Когда всё работает** — удаляешь симлинк/файл из `system/`, упаковываешь в zip

### Упаковка в zip

```php
$zip = new ZipArchive();
$zip->open('my-mod.ocmod.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFile('ocmod/my-mod.ocmod.xml', 'install.xml');   // обязательно install.xml!
// Новые файлы (если есть) — с префиксом upload/
$zip->addFile('public/catalog/controller/extension/module/my_mod.php',
              'upload/catalog/controller/extension/module/my_mod.php');
$zip->close();
```

**Структура zip:**
```
my-mod.ocmod.zip
├── install.xml                                        ← OCMod XML (обязательно!)
└── upload/                                            ← опционально: новые файлы
    ├── catalog/controller/extension/module/my_mod.php
    └── catalog/view/theme/evrika/template/...
```

---

## Диагностика

**Лог применения патчей:** `storage/logs/ocmod.log`

```
MOD: Название модификации
FILE: путь/к/файлу.php
CODE: найденная строка     ← поиск НАШЁЛ
LINE: 18                   ← номер строки

NOT FOUND - OPERATIONS ABORTED!   ← поиск НЕ нашёл, все операции файла прерваны
NOT FOUND - OPERATION SKIPPED!    ← пропущена одна операция (error="skip")
```

**Пропатченные файлы:** `storage/modification/` (создаются только если файл изменился)

**Если NOT FOUND:**
1. Убедись что `<search>` — одна строка (нет `\n`)
2. Проверь точное содержимое строки в файле (пробелы, табы)
3. `stripos` — case-insensitive, но пробелы и табы считаются точно
