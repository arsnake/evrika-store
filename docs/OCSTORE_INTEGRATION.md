# ИНСТРУКЦИЯ ДЛЯ CLAUDE CODE
## Тема «Эврика» на OcStore (OpenCart 3)

---

> **Статус (май 2026): сайт запущен в продакшен.**
> Все этапы натяжки шаблона выполнены. Дальнейшая работа — устранение багов, правки и расширение функционала.

---

## 🎭 РОЛЬ, КОМПЕТЕНЦИИ И КОНТЕКСТ

Ты — **Senior Full-Stack Developer**, специализирующийся на OpenCart 3 / OcStore. Твои компетенции:

- Глубокое знание архитектуры OpenCart 3: MVC(L), контроллеры, модели, языковые файлы, события (Events), OCMod
- Twig 1.x шаблонизатор (OpenCart 3 использует именно Twig 1, не Twig 3)
- PHP 7.4 / 8.x совместимый код
- Понимание структуры БД OpenCart (таблицы `oc_category`, `oc_product`, `oc_order` и т.д.)
- Опыт создания кастомных тем OcStore с нуля
- OCMod (XML-патчи ядра) без прямого редактирования файлов движка

**Проект:** интернет-магазин «Эврика» — канцелярские товары оптом, Мелитополь. B2B-магазин: цены скрыты для неавторизованных пользователей.

**Исходная задача:** перенести готовую HTML-верстку в полноценную тему OcStore, соблюдая все дизайн-правила из `DESIGN_SYSTEM.md`. **Выполнена.**

**Связанные документы:**
- `DESIGN_SYSTEM.md` — дизайн-система, компоненты, CSS-переменные, правила верстки
- `OPENCART_PAGES.md` — перечень всех страниц с маршрутами OcStore
- `index.html` — готовая верстка главной страницы (эталон)

---

## 📁 ФАЙЛОВАЯ СТРУКТУРА ТЕМЫ

Создавай тему строго по этой структуре. Название темы: **`evreka`**.

```
catalog/
└── view/
    └── theme/
        └── evreka/
            ├── template/
            │   ├── common/
            │   │   ├── header.twig
            │   │   ├── footer.twig
            │   │   ├── breadcrumb.twig
            │   │   └── pagination.twig
            │   ├── product/
            │   │   ├── category.twig
            │   │   ├── product.twig
            │   │   ├── search.twig
            │   │   ├── manufacturer.twig
            │   │   └── manufacturer_info.twig
            │   ├── checkout/
            │   │   ├── cart.twig
            │   │   ├── checkout.twig
            │   │   └── success.twig
            │   ├── account/
            │   │   ├── account.twig
            │   │   ├── login.twig
            │   │   ├── register.twig
            │   │   ├── order.twig
            │   │   ├── order_info.twig
            │   │   ├── wishlist.twig
            │   │   ├── edit.twig
            │   │   └── password.twig
            │   ├── information/
            │   │   └── information.twig
            │   ├── error/
            │   │   └── not_found.twig
            │   └── common/
            │       └── home.twig
            └── stylesheet/
                ├── base.css          ← CSS-переменные, reset, типографика
                ├── components.css    ← кнопки, карточки, формы, бейджи
                ├── layout.css        ← сетки, wrap, page-with-sidebar
                ├── header.css        ← шапка, топбар, поиск, навигация
                ├── footer.css        ← подвал
                ├── catalog.css       ← страница категории, фильтры
                ├── product.css       ← карточка товара (детальная)
                ├── cart.css          ← корзина
                ├── checkout.css      ← оформление заказа
                ├── account.css       ← личный кабинет
                ├── home.css          ← главная страница
                └── mobile.css        ← все медиазапросы

ocmod/
└── evreka_*.ocmod.xml    ← OCMod-патчи (если нужны доработки контроллеров)
```

**Правило:** файлы которых нет в теме `evreka` — OcStore автоматически берёт из темы `default`. Это позволяет натягивать шаблон постепенно.

---

## ⚙️ НАСТРОЙКА ТЕМЫ В OCSTORE

### config.php темы
Создай файл `catalog/view/theme/evreka/config.php`:

```php
<?php
return [
    'name'    => 'Эврика — Канцелярские товары',
    'version' => '1.0.0',
    'author'  => 'Эврика',
    'parent'  => 'default'   // fallback на дефолтную тему
];
```

### Подключение CSS
В `header.twig` подключай стили через переменную пути темы:

```twig
{# Подключение стилей темы #}
<link rel="stylesheet" href="{{ 'catalog/view/theme/evreka/stylesheet/base.css'|catalog }}">
<link rel="stylesheet" href="{{ 'catalog/view/theme/evreka/stylesheet/components.css'|catalog }}">
<link rel="stylesheet" href="{{ 'catalog/view/theme/evreka/stylesheet/layout.css'|catalog }}">
<link rel="stylesheet" href="{{ 'catalog/view/theme/evreka/stylesheet/header.css'|catalog }}">
<link rel="stylesheet" href="{{ 'catalog/view/theme/evreka/stylesheet/mobile.css'|catalog }}">

{# Google Fonts #}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@400;600;700&family=Onest:wght@300;400;500;600&display=swap" rel="stylesheet">
```

---

## 🔄 ЭТАПЫ РАЗРАБОТКИ (все выполнены)

### ЭТАП 1 — Хром (шапка и подвал) ✅
**Файлы:** `header.twig`, `footer.twig`
**Зависимости:** CSS-переменные должны быть готовы

Хром появляется на всех страницах сразу. Ошибка здесь сломает весь сайт.

**Ключевые переменные OcStore для header.twig:**
```twig
{{ store_name }}           {# название магазина #}
{{ logo }}                 {# путь к логотипу #}
{{ base }}                 {# базовый URL магазина #}
{{ home }}                 {# ссылка на главную #}
{{ telephone }}            {# телефон из настроек #}
{{ cart_products }}        {# массив товаров в корзине #}
{{ cart_quantity }}        {# количество товаров в корзине #}
{{ cart_total }}           {# сумма корзины #}
{{ wish_list }}            {# количество в избранном (только для авторизованных) #}
{{ logged }}               {# boolean: авторизован ли пользователь #}
{{ customer_name }}        {# имя покупателя если авторизован #}
{{ link_account }}         {# ссылка на личный кабинет #}
{{ link_login }}           {# ссылка на вход #}
{{ link_register }}        {# ссылка на регистрацию #}
{{ link_logout }}          {# ссылка на выход #}
{{ link_cart }}            {# ссылка на корзину #}
{{ link_checkout }}        {# ссылка на оформление заказа #}
{{ link_wishlist }}        {# ссылка на избранное #}
{{ menus }}                {# массив пунктов главного меню (модуль Menu) #}
{{ languages }}            {# массив языков #}
{{ currencies }}           {# массив валют #}
{{ search }}               {# текущий поисковый запрос (для сохранения в поле) #}
```

**Ключевые переменные для footer.twig:**
```twig
{{ information }}          {# массив информационных страниц для подвала #}
{{ contact }}              {# ссылка на страницу контактов #}
{{ telephone }}            {# телефон #}
{{ text_telephone }}       {# языковая строка "Телефон:" #}
{{ address }}              {# адрес магазина #}
{{ powered }}              {# "Powered by OpenCart" (можно скрыть стилями) #}
```

---

### ЭТАП 2 — Главная страница ✅
**Файл:** `common/home.twig`
**CSS:** `home.css`

В OcStore главная страница — это просто контейнер для модулей. Все блоки (слайдер, новинки, популярные и т.д.) выводятся через модули в Layout.

```twig
{# home.twig — минимальная структура #}
{{ header }}

<main id="page-home">
  <div class="wrap">

    {# Слайдер новых поступлений — выводится модулем banner или кастомным #}
    <div class="promo-section">
      <div class="promo-grid">
        {{ arrival_slider }}    {# модуль arrival slider #}
        {{ promo_cards }}       {# модуль промо-карточек #}
      </div>
    </div>

    {# Популярные разделы — модуль Category #}
    <div class="section">
      <div class="section-head">
        <h2 class="section-title">Популярные разделы</h2>
        <a class="section-more" href="{{ link_catalog }}">
          Все разделы
          <svg>...</svg>
        </a>
      </div>
      {{ module_categories }}
    </div>

    {# Новинки — встроенный модуль Latest #}
    <div class="section">
      {{ module_latest }}
    </div>

    {# Популярные товары — встроенный модуль Bestseller или Special #}
    <div class="section">
      {{ module_bestseller }}
    </div>

  </div>
</main>

{{ footer }}
```

**Важно:** в OcStore модули на главной подключаются через **Design → Layouts**. В шаблоне используй `content_top`, `content_bottom`, `column_left`, `column_right` — стандартные позиции для вывода модулей.

---

### ЭТАП 3 — Страница категории ✅
**Файл:** `product/category.twig`
**CSS:** `catalog.css`

Реализуй три вида как описано в `DESIGN_SYSTEM.md`. Логика переключения в Twig:

```twig
{{ header }}

<main class="
  {% if categories and not products %}cat-view--sections
  {% elseif products and not categories %}cat-view--products
  {% else %}cat-view--mixed{% endif %}
">
<div class="wrap">

  {# Хлебные крошки #}
  {% include 'common/breadcrumb.twig' %}

  {# Заголовок #}
  <div class="cat-page-head">
    <div class="cat-page-head-left">
      <h1 class="cat-page-title">{{ heading_title }}</h1>
      <span class="cat-page-total">{{ product_total }} {{ text_product }}</span>
    </div>
    {% if categories and not products %}
      <a class="cat-page-all" href="{{ link_all_products }}">
        Все товары раздела →
      </a>
    {% endif %}
  </div>

  {# Баннер категории (если задан) #}
  {% if banner %}
    <div class="cat-banner">
      <img src="{{ banner }}" alt="{{ heading_title }}" loading="lazy">
    </div>
  {% endif %}

  {# ВИД A — дерево подразделов #}
  <div class="cat-sections-tree">
    <div class="tree-grid">
      {% for category in categories %}
        <div class="tree-group">
          <a class="tree-group-head" href="{{ category.href }}">
            <div class="tree-group-icon">
              {% if category.image %}
                <img src="{{ category.thumb }}" alt="{{ category.name }}">
              {% else %}
                <svg>...</svg>
              {% endif %}
            </div>
            <div class="tree-group-meta">
              <span class="tree-group-name">{{ category.name }}</span>
              <span class="tree-group-count">{{ category.product_total }}</span>
            </div>
          </a>
          {% if category.children %}
            <ul class="tree-children">
              {% for child in category.children %}
                <li>
                  <a href="{{ child.href }}">
                    {{ child.name }}
                    <span>{{ child.product_total }}</span>
                  </a>
                </li>
              {% endfor %}
            </ul>
          {% endif %}
        </div>
      {% endfor %}
    </div>
  </div>

  {# ВИД В — чипсы подкатегорий (для смешанного вида) #}
  {% if categories and products %}
    <div class="cat-sub-chips">
      <span class="sub-chips-label">Разделы:</span>
      {% for category in categories %}
        <a class="sub-chip" href="{{ category.href }}">
          {{ category.name }}
          <span>{{ category.product_total }}</span>
        </a>
      {% endfor %}
    </div>
  {% endif %}

  {# ВИД Б + В — сайдбар + товары #}
  <div class="page-with-sidebar">

    {# Сайдбар #}
    <aside class="cat-sidebar">

      {# Навигация по дереву категорий #}
      {% if category_tree %}
        <div class="sidebar-nav">
          {% if category_parent %}
            <a class="sidebar-nav-parent" href="{{ category_parent.href }}">
              ‹ {{ category_parent.name }}
            </a>
          {% endif %}
          <ul class="sidebar-nav-list">
            {% for sibling in category_siblings %}
              <li class="{% if sibling.category_id == current_category_id %}active{% endif %}">
                <a href="{{ sibling.href }}">{{ sibling.name }}</a>
                <span class="sidebar-nav-count">{{ sibling.product_total }}</span>
              </li>
            {% endfor %}
          </ul>
        </div>
      {% endif %}

      {# Кнопка фильтров #}
      <button class="btn-outline sidebar-filter-toggle" id="filterToggle"
              aria-expanded="false" aria-controls="filterPanel">
        <svg aria-hidden="true">...</svg>
        Показать все фильтры
      </button>

      {# Фильтры #}
      <div class="filter-panel" id="filterPanel">
        {% for filter_group in filter_groups %}
          <div class="filter-group {% if loop.first %}open{% endif %}">
            <button class="filter-group-head"
                    aria-expanded="{% if loop.first %}true{% else %}false{% endif %}">
              {{ filter_group.name }}
              <svg class="fg-arrow" aria-hidden="true">...</svg>
            </button>
            <div class="filter-group-body">
              {% for filter in filter_group.filters %}
                <label class="filter-check">
                  <input type="checkbox"
                         name="filter[]"
                         value="{{ filter.filter_id }}"
                         {% if filter.filter_id in filter_array %}checked{% endif %}
                         onchange="applyFilters()">
                  <span class="fc-box"></span>
                  <span class="fc-name">{{ filter.name }}</span>
                </label>
              {% endfor %}
            </div>
          </div>
        {% endfor %}

        <div class="filter-actions">
          <button class="btn-primary btn-sm" onclick="applyFilters()">Применить</button>
          <button class="btn-outline btn-sm" onclick="resetFilters()">Сбросить</button>
        </div>
      </div>
    </aside>

    {# Основной контент #}
    <div class="cat-main">

      {# Сортировка #}
      <div class="sort-bar">
        <span class="sort-label">{{ text_sort }}</span>
        <select class="sort-select" onchange="location.href=this.value">
          {% for sort_option in sorts %}
            <option value="{{ sort_option.href }}"
              {% if sort_option.value == sort %}selected{% endif %}>
              {{ sort_option.text }}
            </option>
          {% endfor %}
        </select>
        <div class="view-toggle">
          <button class="view-btn active" id="viewGrid" aria-label="Вид сеткой">
            <svg aria-hidden="true">...</svg>
          </button>
          <button class="view-btn" id="viewList" aria-label="Вид списком">
            <svg aria-hidden="true">...</svg>
          </button>
        </div>
      </div>

      {# Сетка товаров #}
      <div class="products-row products-row-3" id="productGrid">
        {% for product in products %}
          <article class="prod-card">
            {% if product.badge %}
              <span class="prod-badge {{ product.badge.class }}">{{ product.badge.text }}</span>
            {% endif %}
            <a class="prod-img" href="{{ product.href }}">
              <img src="{{ product.thumb }}" alt="{{ product.name }}" loading="lazy">
            </a>
            <div class="prod-info">
              {% if product.model %}
                <div class="prod-sku">
                  Код: <span>{{ product.model }}</span>
                </div>
              {% endif %}
              {% if product.manufacturer %}
                <div class="prod-brand">
                  <a href="{{ product.manufacturer_href }}">{{ product.manufacturer }}</a>
                </div>
              {% endif %}
              <a class="prod-name" href="{{ product.href }}">{{ product.name }}</a>
              {% if customer_group_price %}
                <div class="prod-price">{{ product.price }}</div>
              {% else %}
                <div class="prod-auth">
                  Чтобы увидеть цены —
                  <a href="{{ link_login }}">авторизуйтесь</a>
                  или <a href="{{ link_register }}">зарегистрируйтесь</a>
                </div>
              {% endif %}
            </div>
          </article>
        {% else %}
          {# Пустое состояние #}
          <div class="empty-state" style="grid-column: 1/-1">
            <svg>...</svg>
            <h3>Ничего не найдено</h3>
            <p>По выбранным фильтрам нет товаров</p>
            <button class="btn-outline" onclick="resetFilters()">Сбросить фильтры</button>
          </div>
        {% endfor %}
      </div>

      {# Пагинация #}
      {% if pagination %}
        <div class="pagination-wrap">
          {{ pagination }}
        </div>
      {% endif %}

    </div>
  </div>

</div>
</main>

{{ footer }}
```

**OCMod для получения дочерних категорий (category.children):**

Стандартный контроллер не передаёт `category.children`. Нужен OCMod-патч:

```xml
<?xml version="1.0" encoding="utf-8"?>
<modification>
  <name>Evreka — Category Children</name>
  <code>evreka_category_children</code>
  <version>1.0.0</version>
  <author>Evreka</author>
  <link>https://evreka.com</link>
  <file path="catalog/controller/product/category.php">
    <operation>
      <search><![CDATA[$data['categories'][] = array(]]></search>
      <add position="after"><![CDATA[
        // Получаем дочерние категории
        'children' => (function() use ($this, $category_info) {
          $children = $this->model_catalog_category->getCategories([
            'parent_id' => $category_info['category_id'],
            'status'    => 1
          ]);
          $result = [];
          foreach ($children as $child) {
            $child_total = $this->model_catalog_product->getTotalProducts([
              'category_id' => $child['category_id'],
              'filter_status' => 1
            ]);
            if ($child_total > 0) {
              $result[] = [
                'name'          => $child['name'],
                'href'          => $this->url->link('product/category',
                  'path=' . $child['category_id']),
                'product_total' => $child_total
              ];
            }
          }
          return $result;
        })(),
      ]]></add>
    </operation>
  </file>
</modification>
```

---

### ЭТАП 4 — Карточка товара ✅
**Файл:** `product/product.twig`
**CSS:** `product.css`

**Ключевые переменные:**
```twig
{{ product_id }}           {# ID товара #}
{{ name }}                 {# Название #}
{{ model }}                {# Артикул / код #}
{{ sku }}                  {# SKU бренда #}
{{ upc }}                  {# Штрихкод #}
{{ manufacturer }}         {# Название бренда #}
{{ manufacturer_href }}    {# Ссылка на бренд #}
{{ price }}                {# Цена (если авторизован и есть доступ) #}
{{ special }}              {# Цена со скидкой #}
{{ tax }}                  {# Цена с налогом #}
{{ reward }}               {# Баллы лояльности #}
{{ points }}               {# Бонусные баллы #}
{{ description }}          {# Полное описание #}
{{ thumb }}                {# Главное фото (обрезанное) #}
{{ images }}               {# Массив дополнительных фото #}
{{ stock }}                {# Наличие: "В наличии" или "Нет в наличии" #}
{{ minimum }}              {# Минимальное количество для заказа #}
{{ options }}              {# Опции товара (цвет, размер и т.д.) #}
{{ attribute_groups }}     {# Группы атрибутов для таблицы характеристик #}
{{ products }}             {# Похожие товары #}
{{ tags }}                 {# Теги товара #}
{{ rating }}               {# Рейтинг #}
{{ reviews }}              {# Количество отзывов #}
{{ review_status }}        {# Включены ли отзывы #}
{{ logged }}               {# Авторизован ли пользователь #}
{{ add }}                  {# URL для добавления в корзину #}
{{ wishlist }}             {# URL для добавления в избранное #}
{{ compare }}              {# URL для добавления в сравнение #}
```

**Важно для B2B:** проверяй `{{ logged }}` — если не авторизован, показывай заглушку вместо цены и формы заказа:

```twig
{% if logged %}
  <div class="product-price-block">
    {% if special %}
      <span class="price-old">{{ price }}</span>
      <span class="price-new">{{ special }}</span>
    {% else %}
      <span class="price-current">{{ price }}</span>
    {% endif %}
  </div>
  <div class="product-buy">
    {# Количество + кнопка В корзину #}
  </div>
{% else %}
  <div class="prod-auth prod-auth--block">
    <p>Чтобы увидеть персональные цены и сделать заказ —</p>
    <a href="{{ link_login }}" class="btn-primary">Авторизоваться</a>
    <a href="{{ link_register }}" class="btn-outline">Зарегистрироваться</a>
  </div>
{% endif %}
```

---

### ЭТАП 5 — Корзина ✅
**Файл:** `checkout/cart.twig`
**CSS:** `cart.css`

**Ключевые переменные:**
```twig
{{ products }}             {# Массив товаров в корзине #}
  product.product_id
  product.thumb
  product.name
  product.model
  product.option           {# Выбранные опции #}
  product.quantity
  product.stock            {# Наличие #}
  product.price
  product.total
  product.href             {# Ссылка на товар #}
  product.remove           {# URL удаления из корзины #}
{{ vouchers }}             {# Подарочные сертификаты #}
{{ coupon_status }}        {# Включены ли купоны #}
{{ voucher_status }}       {# Включены ли сертификаты #}
{{ shipping_required }}    {# Нужна ли доставка #}
{{ totals }}               {# Массив сумм (подытог, скидка, доставка, итого) #}
  total.title
  total.text
{{ link_checkout }}        {# Ссылка на оформление заказа #}
{{ continue }}             {# Ссылка "Продолжить покупки" #}
```

---

### ЭТАП 6 — Оформление заказа ✅
**Файл:** `checkout/checkout.twig`
**CSS:** `checkout.css`

Оформление заказа в OcStore работает через **AJAX-шаги**. Каждый шаг загружается динамически. Не пытайся переписать логику — адаптируй стили к существующей структуре:

```twig
{{ header }}
<main id="page-checkout">
  <div class="wrap">
    {% include 'common/breadcrumb.twig' %}

    <div class="checkout-layout">
      {# Шаги оформления (аккордеон — управляется JS OcStore) #}
      <div class="checkout-steps">

        {# Шаг 1 — Авторизация #}
        <div id="checkout-account" class="checkout-step">
          <div class="checkout-step-head">
            <span class="step-num">1</span>
            <span class="step-title">{{ text_account }}</span>
          </div>
          <div class="checkout-step-body">
            {# Контент подгружается AJAX из account/login, account/guest, account/register #}
          </div>
        </div>

        {# Шаг 2 — Адрес доставки #}
        <div id="checkout-shipping-address" class="checkout-step">...</div>

        {# Шаг 3 — Метод доставки #}
        <div id="checkout-shipping-method" class="checkout-step">...</div>

        {# Шаг 4 — Метод оплаты #}
        <div id="checkout-payment-method" class="checkout-step">...</div>

        {# Шаг 5 — Подтверждение #}
        <div id="checkout-confirm" class="checkout-step">...</div>
      </div>

      {# Боковая сводка заказа #}
      <aside class="checkout-summary">
        {# Список товаров и итоги — статичный блок #}
      </aside>
    </div>
  </div>
</main>
{{ footer }}
```

**Важно:** OcStore использует `checkout.js` который управляет всеми шагами через ID `#checkout-*`. Не переименовывай эти ID — сломаешь JS-логику.

---

### ЭТАП 7 — Личный кабинет ✅
**Файлы:** `account/*.twig`
**CSS:** `account.css`

Все страницы кабинета используют одинаковый двухколоночный лейаут:

```twig
<div class="page-with-sidebar">
  {# Левое меню кабинета — одинаковое для всех страниц кабинета #}
  <aside class="account-sidebar">
    <nav class="account-nav">
      <a href="{{ link_account }}"     class="{% if route == 'account/account' %}active{% endif %}">Обзор</a>
      <a href="{{ link_order }}"       class="{% if route == 'account/order' %}active{% endif %}">Мои заказы</a>
      <a href="{{ link_wishlist }}"    class="{% if route == 'account/wishlist' %}active{% endif %}">Избранное</a>
      <a href="{{ link_edit }}"        class="{% if route == 'account/edit' %}active{% endif %}">Мои данные</a>
      <a href="{{ link_password }}"    class="{% if route == 'account/password' %}active{% endif %}">Пароль</a>
      <a href="{{ link_address }}"     class="{% if route == 'account/address' %}active{% endif %}">Адреса</a>
      <a href="{{ link_newsletter }}"  class="{% if route == 'account/newsletter' %}active{% endif %}">Рассылка</a>
      <a href="{{ link_logout }}"      class="account-nav-logout">Выйти</a>
    </nav>
  </aside>

  {# Основной контент — меняется на каждой странице #}
  <div class="account-main">
    {% block content %}{% endblock %}
  </div>
</div>
```

---

### ЭТАП 8 — Информационные страницы ✅
**Файл:** `information/information.twig`

```twig
{{ header }}
<main>
  <div class="wrap">
    {% include 'common/breadcrumb.twig' %}
    <div class="info-page">
      <h1>{{ heading_title }}</h1>
      <div class="info-content">
        {{ description }}   {# HTML-контент из админки, не экранировать! #}
      </div>
    </div>
  </div>
</main>
{{ footer }}
```

---

## 🛡️ ПРАВИЛА И ОГРАНИЧЕНИЯ

### НИКОГДА не делай:

**❌ Не редактируй файлы ядра OcStore напрямую**
- Нельзя изменять файлы в `system/`, `catalog/controller/`, `catalog/model/` напрямую
- Все доработки контроллеров — только через OCMod (`.ocmod.xml`)
- Нельзя редактировать `index.php`, `config.php` (корневые)

**❌ Не используй jQuery в своём коде**
- OcStore уже подключает jQuery — не подключай его повторно
- Для своей логики используй нативный JS
- Не конфликтуй с существующими jQuery-обработчиками OcStore

**❌ Не переименовывай системные ID и классы OcStore**
- `#checkout-account`, `#checkout-shipping-address` и т.д. — трогать нельзя
- Классы вида `.success`, `.warning`, `.danger` — зарезервированы Bootstrap который есть в OcStore
- Не создавай классы с именами которые конфликтуют с Bootstrap 3 (OcStore использует Bootstrap 3 в дефолтной теме)

**❌ Не используй Bootstrap классы в своей теме**
- Даже если Bootstrap подключён — не опирайся на него в кастомных стилях
- Стили темы должны быть самодостаточными

**❌ Не хардкодируй URL и тексты**
- Все ссылки — через переменные Twig (`{{ link_cart }}`, `{{ link_login }}` и т.д.)
- Все тексты — через языковые переменные (`{{ text_cart }}`, `{{ button_add_to_cart }}` и т.д.)
- Телефон, адрес, название магазина — через переменные из настроек (`{{ telephone }}`, `{{ store_name }}`)

**❌ Не создавай `!important` ради перебивания стилей Bootstrap**
- Если Bootstrap мешает — подключай свой CSS после него и используй специфичность селекторов
- Крайний случай: обернуть блок в `.evreka-theme { }` для повышения специфичности

### ВСЕГДА делай:

**✅ Экранирование данных из БД**
```twig
{# Правильно — Twig автоматически экранирует #}
{{ product.name }}

{# Для HTML-контента (описания, информационные страницы) — явно отключай #}
{{ description | raw }}
```

**✅ Проверяй наличие переменной перед выводом**
```twig
{% if telephone %}
  <a href="tel:{{ telephone }}">{{ telephone }}</a>
{% endif %}
```

**✅ Используй языковые строки для всех текстов интерфейса**
```twig
{# Не так: #}
<button>Добавить в корзину</button>

{# Так: #}
<button>{{ button_cart }}</button>
```

Языковые строки определяются в файлах:
```
catalog/language/ru-ru/product/category.php
catalog/language/ru-ru/product/product.php
catalog/language/ru-ru/checkout/cart.php
{# и т.д. #}
```

**✅ Lazy loading для всех изображений**
```twig
<img src="{{ product.thumb }}" alt="{{ product.name }}" loading="lazy">
```

**✅ aria-атрибуты на интерактивных элементах**
```twig
<button aria-label="{{ text_remove }}" onclick="...">
  <svg aria-hidden="true">...</svg>
</button>
```

**✅ Проверяй переменную `logged` для B2B-логики**
```twig
{% if logged %}
  {# Показываем цену и форму заказа #}
{% else %}
  {# Показываем призыв авторизоваться #}
{% endif %}
```

---

## 🔧 OCMod — ПРАВИЛА НАПИСАНИЯ ПАТЧЕЙ

OCMod — безопасный способ доработки контроллеров без изменения ядра.

**Структура файла:**
```xml
<?xml version="1.0" encoding="utf-8"?>
<modification>
  <name>Evreka — Описание патча</name>
  <code>evreka_уникальный_код</code>       <!-- уникальный идентификатор -->
  <version>1.0.0</version>
  <author>Evreka</author>
  <link></link>
  <file path="путь/к/файлу.php">
    <operation>
      <search><![CDATA[
        // Ищем уникальный кусок кода в файле
        $data['categories'][] = array(
      ]]></search>
      <add position="before|after|replace"><![CDATA[
        // Добавляемый или заменяемый код
      ]]></add>
    </operation>
  </file>
</modification>
```

**После установки OCMod:** обязательно нажать **Extensions → Modifications → Refresh** в админке.

---

## 📦 НАСТРОЙКА OCSTORE — ЧЕКЛИСТ ДО НАЧАЛА ВЕРСТКИ

Перед натяжкой убедись что в OcStore настроено:

```
□ Тема evreka создана и активирована (Design → Theme)
□ SEO URL включены (System → Settings → SEO)
□ Модуль Filter активирован (Extensions → Extensions → Modules)
□ Языковой файл ru-ru установлен и активен
□ Изображения: настроены размеры для thumb (например 300x300 для карточки)
□ Customer Groups: настроена группа по умолчанию, цены скрыты для гостей
□ Layout главной страницы создан с позициями для модулей
□ Категории созданы с правильной иерархией
□ Атрибуты и фильтры настроены для категорий
```

---

## ✅ ЧЕКЛИСТ СДАЧИ КАЖДОГО ШАБЛОНА

```
□ Все тексты через языковые переменные ({{ text_* }}, {{ button_* }})
□ Все URL через переменные ({{ link_* }})
□ Все данные из БД экранированы (Twig auto-escape), HTML — через | raw
□ B2B-логика: цены и кнопка заказа скрыты для гостей ({% if logged %})
□ Изображения с loading="lazy" и корректным alt
□ aria-label на всех icon-only кнопках
□ Хлебные крошки подключены через {% include 'common/breadcrumb.twig' %}
□ header и footer через {{ header }} и {{ footer }}
□ Адаптив: протестировать на 375px, 768px, 1024px, 1440px
□ Пустые состояния реализованы ({% else %} в циклах)
□ Проверены hover/focus состояния на всех интерактивных элементах
□ CSS-переменные из DESIGN_SYSTEM.md (никаких хардкодов hex)
□ OCMod-файлы (если созданы) протестированы через Refresh
□ Нет конфликтов с системными ID OcStore (#checkout-*, etc.)
```

---

## 🗺️ БЫСТРАЯ СПРАВКА: ПЕРЕМЕННЫЕ TWIG В OCSTORE

### Глобальные (доступны во всех шаблонах)
```twig
{{ base }}              {# http://ваш-домен/ #}
{{ store_name }}        {# Название магазина из настроек #}
{{ store_description }} {# Описание магазина #}
{{ logged }}            {# true/false — авторизован ли пользователь #}
{{ customer_id }}       {# ID покупателя (если logged) #}
{{ currency }}          {# Текущая валюта #}
{{ language }}          {# Текущий язык #}
{{ direction }}         {# 'ltr' или 'rtl' #}
{{ header }}            {# Отрендеренный HTML шапки #}
{{ footer }}            {# Отрендеренный HTML подвала #}
```

### Типовые паттерны Twig в OcStore
```twig
{# Цикл с проверкой на пустоту #}
{% for product in products %}
  ...
{% else %}
  <p>Нет товаров</p>
{% endfor %}

{# Условие авторизации #}
{% if logged %} ... {% else %} ... {% endif %}

{# Безопасный вывод HTML #}
{{ description | raw }}

{# Перевод числа в формат валюты (фильтр OcStore) #}
{{ price | currency }}

{# Путь к изображению #}
{{ image | resize(300, 300) }}   {# через OCStore image filter #}

{# Первый/последний элемент цикла #}
{% if loop.first %} ... {% endif %}
{% if loop.last %}  ... {% endif %}

{# Индекс элемента (с 0) #}
{{ loop.index0 }}

{# Получить параметр URL #}
{{ get.sort }}   {# аналог $_GET['sort'] #}
```
