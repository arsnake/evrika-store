# ТЗ для разработчика — SEO-доработки на dev-сервере
### Проект: «Эврика» / OcStore, тема evrika
### Исполнитель: Claude Code (dev-среда)

---

> Все задачи выполняются на dev-сервере. Демо-контент не мешает техническим правкам.
> Порядок задач: от критических к менее важным.

---

## Задача 1 — Canonical URL на всех страницах 🔴

**Файл:** `public/catalog/view/theme/evrika/template/common/header.twig`

Добавить в `<head>` после `<title>`:

```twig
{# Canonical URL — защита от дублей #}
{% if canonical %}
  <link rel="canonical" href="{{ canonical }}">
{% endif %}
```

**OCMod-патч для передачи `canonical` из всех контроллеров:**

OcStore 3 передаёт `$data['canonical']` в шаблон если это настроено в контроллере.
Нужен один OCMod на базовый контроллер `catalog/controller/startup/seo_url.php`
или добавить в каждый контроллер через отдельные патчи.

Альтернатива — задать canonical прямо в header.twig через текущий URL:
```twig
{# Временное решение: canonical = текущий URL без параметров sort/order/limit/page #}
<link rel="canonical" href="{{ base }}{{ route }}">
```

**Для страниц пагинации** — добавить canonical на первую страницу категории:
```twig
{% if get.page is defined and get.page > 1 %}
  {# canonical уже без ?page= — поисковик не канонизирует страницы 2+ #}
{% endif %}
```

---

## Задача 2 — H1 на главной странице 🔴

**Файл:** `public/catalog/view/theme/evrika/template/common/home.twig`

Добавить скрытый H1 сразу после `{{ header }}` (перед `<main>`):

```twig
{# SEO H1 — визуально скрыт, читается поисковиком #}
<h1 class="visually-hidden">Канцелярские товары оптом в Мелитополе</h1>
```

Добавить в `base.css`:
```css
.visually-hidden {
  position: absolute;
  width: 1px; height: 1px;
  margin: -1px; padding: 0;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
  border: 0;
}
```

Или вариант с видимым H1 — вставить в promo-секцию как подзаголовок:
```twig
<h1 class="promo-h1">Канцелярские товары оптом</h1>
```
(текст обсудить с SEO-специалистом перед деплоем на продакшен)

---

## Задача 3 — Schema.org LocalBusiness в header.twig 🔴

**Файл:** `public/catalog/view/theme/evrika/template/common/header.twig`

Добавить перед `</head>` JSON-LD блок. Данные подтягиваются из переменных OcStore:

```twig
{# Schema.org — LocalBusiness (глобально на всех страницах) #}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "{{ store_name | escape('js') }}",
  "@id": "{{ base }}#organization",
  "url": "{{ base }}",
  "logo": "{{ logo }}",
  "description": "Оптовая продажа канцелярских товаров",
  {% if telephone %}"telephone": "{{ telephone | escape('js') }}",{% endif %}
  {% if address %}"address": {
    "@type": "PostalAddress",
    "streetAddress": "{{ address | striptags | escape('js') }}",
    "addressLocality": "Мелитополь",
    "addressRegion": "Запорожская область",
    "addressCountry": "RU"
  },{% endif %}
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
      "opens": "09:00",
      "closes": "18:00"
    }
  ],
  "priceRange": "₽₽"
}
</script>

{# Schema.org WebSite + Sitelinks Searchbox (только главная) #}
{% if route == 'common/home' %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "url": "{{ base }}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "{{ base }}index.php?route=product/search&search={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>
{% endif %}
```

**Примечание:** точный адрес и телефон заполнить на продакшене через настройки магазина.
Переменная `{{ address }}` доступна в footer-контроллере — нужно передать в header через OCMod
или хардкодировать для демо.

---

## Задача 4 — Schema.org BreadcrumbList в breadcrumb.twig 🔴

**Файл:** `public/catalog/view/theme/evrika/template/common/breadcrumb.twig`

Текущий код заменить на версию с JSON-LD:

```twig
{% if breadcrumbs %}
<nav class="breadcrumb" aria-label="Хлебные крошки">
  {% for breadcrumb in breadcrumbs %}
    {% if not loop.last %}
      <a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a>
      <span class="bc-sep" aria-hidden="true">›</span>
    {% else %}
      <span class="bc-current" aria-current="page">{{ breadcrumb.text }}</span>
    {% endif %}
  {% endfor %}
</nav>

{# JSON-LD BreadcrumbList — для расширенных сниппетов #}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {% for breadcrumb in breadcrumbs %}
    {
      "@type": "ListItem",
      "position": {{ loop.index }},
      "name": "{{ breadcrumb.text | escape('js') }}",
      "item": "{{ breadcrumb.href }}"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}
</script>
{% endif %}
```

---

## Задача 5 — Schema.org Product на странице товара 🔴

**Файл:** `public/catalog/view/theme/evrika/template/product/product.twig`

Добавить перед `{{ footer }}`:

```twig
{# Schema.org Product — для rich snippets в поиске #}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "{{ heading_title | escape('js') }}",
  {% if meta_description %}"description": "{{ meta_description | striptags | escape('js') }}",{% endif %}
  "image": [
    {% if thumb %}"{{ thumb }}"{% endif %}
    {% for image in images %},"{{ image.popup }}"{% endfor %}
  ],
  {% if model %}"sku": "{{ model | escape('js') }}",{% endif %}
  {% if sku %}"mpn": "{{ sku | escape('js') }}",{% endif %}
  {% if manufacturer %}"brand": {
    "@type": "Brand",
    "name": "{{ manufacturer | escape('js') }}"
  },{% endif %}
  "offers": {
    "@type": "Offer",
    "priceCurrency": "RUB",
    {% if logged and price %}
    "price": "{{ price_raw is defined ? price_raw : '0' }}",
    {% endif %}
    "availability": "{% if stock %}https://schema.org/InStock{% else %}https://schema.org/OutOfStock{% endif %}",
    "url": "{{ base }}{{ route }}",
    "seller": {
      "@type": "Organization",
      "name": "{{ store_name | escape('js') }}"
    }
  }
  {% if rating is defined and rating and reviews > 0 %}
  ,"aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ rating }}",
    "reviewCount": "{{ reviews }}"
  }
  {% endif %}
}
</script>
```

**Важно:** переменная `price_raw` (цена без форматирования) отсутствует в стандартном
контроллере OcStore. Нужен OCMod-патч к `catalog/controller/product/product.php`:

```xml
<!-- OCMod: добавить price_raw в данные товара -->
<file path="catalog/controller/product/product.php">
  <operation>
    <search><![CDATA[$data['price'] = $this->currency->format(]]></search>
    <add position="before"><![CDATA[$data['price_raw'] = $product_info['price'];
]]></add>
  </operation>
</file>
```

---

## Задача 6 — Schema.org ItemList на странице категории 🟡

**Файл:** `public/catalog/view/theme/evrika/template/product/category.twig`

Добавить перед `{{ footer }}`:

```twig
{% if products %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "{{ heading_title | escape('js') }}",
  "numberOfItems": {{ product_total }},
  "itemListElement": [
    {% for product in products %}
    {
      "@type": "ListItem",
      "position": {{ loop.index }},
      "url": "{{ product.href }}"
    }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
}
</script>
{% endif %}
```

---

## Задача 7 — og:description + og:type динамический 🟡

**Файл:** `public/catalog/view/theme/evrika/template/common/header.twig`

Добавить недостающий тег og:description (после og:title, строка ~12):

```twig
{% if description %}
<meta property="og:description" content="{{ description }}">
{% endif %}
<meta property="og:locale" content="ru_RU">
```

Сделать og:type динамическим (заменить строку 13):

```twig
{# og:type: product на страницах товара, website везде остальном #}
<meta property="og:type" content="{% if route == 'product/product' %}product{% else %}website{% endif %}">
```

---

## Задача 8 — fetchpriority на главное изображение товара 🟡

**Файл:** `public/catalog/view/theme/evrika/template/product/product.twig`, строка ~39

Текущий код:
```twig
<img src="{{ thumb }}" alt="{{ heading_title }}" id="mainImage">
```

Заменить на:
```twig
<img src="{{ thumb }}" alt="{{ heading_title }}" id="mainImage"
     fetchpriority="high" decoding="async">
```

---

## Задача 9 — width/height на изображения (CLS) 🟡

**Проблема:** отсутствие размеров вызывает CLS при загрузке страницы.

**Файл:** `product/category.twig` — изображения товаров в сетке

Текущий код (примерно):
```twig
<img src="{{ product.thumb }}" alt="{{ product.name }}" loading="lazy">
```

Заменить на:
```twig
<img src="{{ product.thumb }}" alt="{{ product.name }}"
     width="300" height="300" loading="lazy">
```

Размеры `300×300` соответствуют стандартным настройкам resize в OcStore.
Уточнить в `System → Settings → Image` какие размеры настроены для `product_thumb`.

**Файл:** `product/product.twig` — галерея товара

```twig
{# Главное фото #}
<img src="{{ thumb }}" alt="{{ heading_title }}" id="mainImage"
     width="600" height="600" fetchpriority="high" decoding="async">

{# Миниатюры #}
<img src="{{ thumb }}" alt="{{ heading_title }}"
     width="80" height="80" loading="lazy">
```

---

## Задача 10 — Шаблон авто-генерации title/description через OCMod 🟡

Создать файл `ocmod/evrika_seo_meta.ocmod.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<modification>
  <name>Evrika — SEO Meta Auto-Templates</name>
  <code>evrika_seo_meta</code>
  <version>1.0.0</version>
  <author>Evrika</author>
  <link></link>

  <!-- Категория: авто-заполнение title если не задан вручную -->
  <file path="catalog/controller/product/category.php">
    <operation>
      <search><![CDATA[$data['heading_title'] = $category_info['name'];]]></search>
      <add position="after"><![CDATA[
      // Auto SEO title для категории
      if (empty($this->document->getTitle())) {
        $this->document->setTitle($category_info['name'] . ' купить оптом в Мелитополе | ' . $this->config->get('config_name'));
      }
      // Auto SEO description
      if (empty($this->document->getDescription()) && !empty($category_info['description'])) {
        $desc = strip_tags($category_info['description']);
        $this->document->setDescription(mb_substr($desc, 0, 155));
      } elseif (empty($this->document->getDescription())) {
        $this->document->setDescription(
          $category_info['name'] . ' оптом — ' . $data['product_total'] . ' товаров. Доставка по региону. Цены для юрлиц и ИП. Звоните: ' . $this->config->get('config_telephone')
        );
      }
]]></add>
    </operation>
  </file>

  <!-- Товар: авто-заполнение title если не задан вручную -->
  <file path="catalog/controller/product/product.php">
    <operation>
      <search><![CDATA[$data['heading_title'] = $product_info['name'];]]></search>
      <add position="after"><![CDATA[
      // Auto SEO title для товара
      if (empty($this->document->getTitle())) {
        $title = $product_info['name'];
        if (!empty($product_info['manufacturer'])) {
          $title .= ' ' . $product_info['manufacturer'];
        }
        $title .= ' — купить оптом | ' . $this->config->get('config_name');
        $this->document->setTitle($title);
      }
      // Auto SEO description
      if (empty($this->document->getDescription())) {
        $desc = 'Купите ' . $product_info['name'] . ' оптом.';
        if (!empty($product_info['manufacturer'])) {
          $desc .= ' Бренд: ' . $product_info['manufacturer'] . '.';
        }
        if (!empty($product_info['model'])) {
          $desc .= ' Артикул: ' . $product_info['model'] . '.';
        }
        $desc .= ' Цены для юрлиц — авторизуйтесь или звоните: ' . $this->config->get('config_telephone');
        $this->document->setDescription(mb_substr($desc, 0, 155));
      }
      // price_raw для Schema.org
      $data['price_raw'] = $product_info['price'];
]]></add>
    </operation>
  </file>

</modification>
```

После создания:
1. Скопировать в `public/system/evrika_seo_meta.ocmod.xml`
2. Extensions → Modifications → Refresh
3. Проверить в `storage/logs/ocmod.log`

---

## Задача 11 — Обновить robots.txt 🟡

**Файл:** `public/robots.txt`

Добавить строку с sitemap (раскомментировать и поставить правильный URL на продакшене):

```
# Добавить ?page= в запреты (пагинация)
Disallow: /*?page=
Disallow: /*&page=
```

На продакшене раскомментировать строку Sitemap.

---

## Задача 12 — Скрытый H2 на вкладках товара 🟢

**Файл:** `public/catalog/view/theme/evrika/template/product/product.twig`

В секции вкладок добавить скрытые H2 внутри каждой панели:

```twig
{# Вкладка "Описание" #}
<div class="tab-panel" id="tab-description">
  <h2 class="visually-hidden">Описание товара</h2>
  {{ description | raw }}
</div>

{# Вкладка "Характеристики" #}
<div class="tab-panel" id="tab-specs">
  <h2 class="visually-hidden">Характеристики</h2>
  ...
</div>
```

---

## Порядок выполнения

| Приоритет | Задача | Затраты |
|-----------|--------|---------|
| 🔴 1 | Canonical URL | ~30 мин |
| 🔴 2 | H1 на главной | ~15 мин |
| 🔴 3 | Schema.org LocalBusiness | ~30 мин |
| 🔴 4 | Schema.org BreadcrumbList | ~20 мин |
| 🔴 5 | Schema.org Product + OCMod price_raw | ~45 мин |
| 🟡 6 | Schema.org ItemList | ~20 мин |
| 🟡 7 | og:description + og:type | ~15 мин |
| 🟡 8 | fetchpriority hero-image | ~10 мин |
| 🟡 9 | width/height изображений | ~20 мин |
| 🟡 10 | OCMod авто-title/description | ~60 мин |
| 🟡 11 | robots.txt дополнить | ~10 мин |
| 🟢 12 | H2 в вкладках товара | ~15 мин |

**Итого: ~5 часов работы разработчика**

---

## После выполнения задач на dev

Перед переносом на продакшен проверить:
- [ ] Canonical корректен (не указывает на dev-домен)
- [ ] JSON-LD валиден: вставить в https://validator.schema.org/
- [ ] Нет JS-ошибок в консоли браузера
- [ ] Lighthouse score не упал
- [ ] OCMod лог чистый (`storage/logs/ocmod.log`)
