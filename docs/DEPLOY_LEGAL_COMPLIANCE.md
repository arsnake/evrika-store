# Деплой: Юридическое соответствие сайта

> Описывает все изменения из сессии "Юридическое соответствие" и полный план деплоя на продакшен.  
> PHP 7.4, OcStore 3.0.3.7, тема **evrika**  
> Правовые основания: 152-ФЗ, 38-ФЗ «О рекламе», ЗоЗПП, ГК РФ ст.437, ПП РФ №612

---

## Что сделано

- **8 информационных страниц** с юридическим контентом (политика, оферта, согласия, возврат)
- **Cookie-баннер** с кнопками «Принять все» / «Только необходимые»
- **Чекбоксы согласий** во всех формах: регистрация, чекаут, отзыв, контакты
- **Ссылки в футере** — заменены `#`-заглушки на реальные SEO URL
- **Ссылки в чекауте** — открытие в новой вкладке вместо popup
- **Карта сайта** — создан шаблон `/sitemap` в теме evrika

---

## 1. Файлы для загрузки на сервер

Копировать **сохраняя структуру путей** относительно корня OcStore.

### 1.1 Новые файлы

```
public/
├── legal_seed.php                          ← ВР: запустить и удалить (см. раздел 2)
│
└── catalog/view/theme/evrika/
    ├── js/
    │   └── cookie.js                       ← Cookie consent логика
    ├── stylesheet/
    │   └── legal.css                       ← Стили: consent-row, cookie-banner, legal-doc
    └── template/information/
        └── sitemap.twig                    ← Карта сайта в теме evrika
```

### 1.2 Изменённые файлы

```
public/catalog/view/theme/evrika/template/
├── common/
│   ├── header.twig                         ← + <link> на legal.css
│   └── footer.twig                         ← Реальные URL в ссылках + HTML cookie-баннера + cookie.js
├── account/
│   ├── login.twig                          ← #panelRegister: 3 consent-чекбокса (agree_pd → agree)
│   └── register.twig                       ← 3 consent-чекбокса (agree_pd → agree)
├── checkout/
│   ├── checkout.twig                       ← .agree popup → new tab; валидация consent-чекбоксов
│   ├── confirm.twig                        ← + чекбокс «Принимаю Публичную оферту»
│   └── login.twig                          ← + 3 компактных consent-чекбокса (быстрая регистрация)
├── product/
│   └── product.twig                        ← Форма отзыва: consent-блок с инлайн-стилями
└── information/
    └── contact.twig                        ← + чекбокс согласия на обработку ПД
```

**Итого: 3 новых файла + 9 изменённых файлов** (не считая `legal_seed.php`).

---

## 2. База данных — миграция информационных страниц

> **Обязательный шаг.** Без него страницы `/privacy`, `/terms`, `/oferta` и остальные будут пустыми.

### Шаг 1 — загрузить сидер

Загрузить файл `public/legal_seed.php` на сервер в корень сайта:
```
/path/to/ocstore/legal_seed.php
```

### Шаг 2 — запустить сидер

Открыть в браузере:
```
https://evrikamlt.ru/legal_seed.php
```

Ожидаемый результат — список строк вида:
```
OK: Updated information_id=3 (Политика конфиденциальности)
OK: Updated information_id=4 (О компании)
OK: Updated information_id=5 (Пользовательское соглашение)
OK: Inserted information_id=7 (Публичная оферта)
OK: Inserted information_id=8 (Согласие на обработку персональных данных)
OK: Inserted information_id=9 (Согласие на распространение персональных данных)
OK: Inserted information_id=10 (Правила возврата и обмена)
OK: Inserted information_id=11 (Согласие на получение рекламных рассылок)
Done.
```

Если вместо кириллицы видны знаки `?` — это проблема с кодировкой. Открыть файл `legal_seed.php` в текстовом редакторе и убедиться, что он сохранён в **UTF-8 без BOM**.

### Шаг 3 — удалить сидер

**Немедленно после запуска** удалить файл с сервера:
```bash
rm /path/to/ocstore/legal_seed.php
```
Оставлять сидер в продакшене нельзя — он пересоздаёт данные при каждом запросе.

### Шаг 4 — добавить SEO URL (если не созданы автоматически)

Проверить через Админка → System → SEO URL что существуют записи:

| SEO URL | Тип | ID |
|---------|-----|----|
| `privacy` | `information_id` | `3` |
| `about_us` | `information_id` | `4` |
| `terms` | `information_id` | `5` |
| `oferta` | `information_id` | `7` |
| `pd-consent` | `information_id` | `8` |
| `pd-public-consent` | `information_id` | `9` |
| `returns` | `information_id` | `10` |
| `newsletter-consent` | `information_id` | `11` |

Если не созданы — выполнить SQL:

```sql
INSERT IGNORE INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`) VALUES
(0, 1, 'information_id=3',  'privacy'),
(0, 1, 'information_id=4',  'about_us'),
(0, 1, 'information_id=5',  'terms'),
(0, 1, 'information_id=7',  'oferta'),
(0, 1, 'information_id=8',  'pd-consent'),
(0, 1, 'information_id=9',  'pd-public-consent'),
(0, 1, 'information_id=10', 'returns'),
(0, 1, 'information_id=11', 'newsletter-consent');
```

> `language_id=1` — язык по умолчанию. Проверить значение:  
> `SELECT language_id, name FROM oc_language WHERE status=1;`

---

## 3. Очистка кэша OcStore

После загрузки файлов обязательно очистить кэш:

**Через админку:**
1. Dashboard → нажать кнопку с иконкой шестерёнки (Developer Tools)
2. «SASS/Less» → Clear → «Template Cache» → Clear

**Или вручную:**
```bash
rm -rf /path/to/ocstore/storage/cache/*
rm -rf /path/to/ocstore/storage/modification/*
```

---

## 4. Важные замечания

### Демо-данные ИП в юридических страницах

`legal_seed.php` заполняет страницы с данными:
```
ИП Иванов Иван Иванович, ИНН 612301234567
Адрес: 295000, г. Мелитополь, ул. Ленина, д. 1, оф. 1
Email: info@evrikamlt.ru  |  Тел.: +7 (990) 263-41-61
```

**Заменить на реальные данные** через Админка → System → Information (редактировать каждую страницу).

### Поле `name="agree"` в формах регистрации

OcStore-контроллер `account/register` валидирует только поле `name="agree"`. В шаблонах `account/login.twig` и `account/register.twig` чекбокс «Согласие на обработку ПД» намеренно имеет `name="agree"` (стандартное поле). Не переименовывать.

### `config_account_id`

В настройках магазина (Admin → System → Settings → Store → вкладка Option) параметр **"Account Terms"** должен указывать на страницу c `information_id=8` («Согласие на обработку персональных данных»). Это делает ссылку в сообщении об ошибке корректной.

---

## 5. Чеклист проверки после деплоя

```
□ /privacy         — страница открывается, содержит текст политики
□ /about_us        — страница открывается, содержит данные компании
□ /terms           — страница открывается, содержит пользовательское соглашение
□ /oferta          — страница открывается, содержит публичную оферту
□ /pd-consent      — страница открывается
□ /returns         — страница открывается, содержит правила возврата
□ /sitemap         — страница в теме evrika (не Bootstrap), содержимое не прилипает к краю

□ Футер: все ссылки (Политика, Соглашение, Оферта, Возврат, О нас) работают
□ Cookie-баннер появляется при первом визите (через 800 мс)
□ Кнопка «Принять все» закрывает баннер, не появляется повторно
□ Кнопка «Только необходимые» закрывает баннер, не появляется повторно

□ /login → вкладка «Регистрация»: 3 чекбокса, без чекбоксов ПД форма не отправляется
□ /account/register: 3 чекбокса, без чекбоксов ПД форма не отправляется
□ Форма регистрации: ссылки на /pd-consent и /terms открываются в новой вкладке

□ /checkout шаг 2 (быстрая регистрация): 3 компактных чекбокса видны и работают
□ /checkout шаг 4 (способ оплаты): ссылка на Условия открывается в новой вкладке (не popup)
□ /checkout шаг 5 (подтверждение): чекбокс «Принимаю Публичную оферту» присутствует
□ Без галки оферты кнопка «Подтвердить заказ» не срабатывает

□ Страница продукта → форма отзыва: чекбокс согласия отображается корректно (не «текст вне блока»)
□ Без галки отзыв не отправляется

□ /contact-us: чекбокс «Согласие на обработку ПД» присутствует перед кнопкой «Отправить»
□ legal_seed.php удалён с сервера
```

---

## 6. Rollback

### Откат шаблонов
Восстановить предыдущие версии файлов из git или резервной копии.

### Откат информационных страниц
```sql
-- Удалить новые страницы (ID 7–11)
DELETE FROM `oc_information_description`  WHERE `information_id` IN (7,8,9,10,11);
DELETE FROM `oc_information_to_store`     WHERE `information_id` IN (7,8,9,10,11);
DELETE FROM `oc_information`              WHERE `information_id` IN (7,8,9,10,11);
DELETE FROM `oc_seo_url`                  WHERE `query` IN (
    'information_id=7','information_id=8','information_id=9',
    'information_id=10','information_id=11'
);

-- Вернуть старые тексты для ID 3, 4, 5 — только если есть резервная копия:
-- UPDATE `oc_information_description` SET `description` = '...старый текст...' WHERE `information_id` = 3;
```

### Откат CSS и JS
```bash
rm /path/to/ocstore/catalog/view/theme/evrika/stylesheet/legal.css
rm /path/to/ocstore/catalog/view/theme/evrika/js/cookie.js
```

---

## Версия

| Параметр | Значение |
|---|---|
| Дата изменений | 2026-06-01 |
| Исполнитель | Claude Code (сессия Legal Compliance) |
| Совместимость | OcStore 3.0.3.7, PHP 7.4+, тема evrika |
| Правовые основания | 152-ФЗ, 38-ФЗ, ЗоЗПП, ГК РФ ст.437 |
