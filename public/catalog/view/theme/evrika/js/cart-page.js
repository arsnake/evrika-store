'use strict';

/* ================================================================
   CART-PAGE.JS — логика страницы корзины
   Загружается только на странице checkout/cart через cart.twig.
   Выполняется после DOMContentLoaded, что гарантирует доступность
   window.parseRub (из cart.js) и cart.remove (из common.js).
   ================================================================ */

document.addEventListener('DOMContentLoaded', function() {

  /* ──────────────────────────────────────────────────────────────
     Утилиты
  ────────────────────────────────────────────────────────────── */

  /* Русская форма числа */
  function ruPlural(n, one, few, many) {
    var m10 = n % 10, m100 = n % 100;
    if (m10 === 1 && m100 !== 11) return n + '\u00a0' + one;
    if (m10 >= 2 && m10 <= 4 && (m100 < 10 || m100 >= 20)) return n + '\u00a0' + few;
    return n + '\u00a0' + many;
  }

  /* Построить функцию форматирования по образцу OcStore-строки цены.
     Подход идентичен product.js: суффикс берём с КОНЦА строки,
     десятичный разделитель определяем из цифровой части. */
  function makeFormatter(sample) {
    var s = String(sample || '').trim();
    /* суффикс — всё после последней цифры */
    var sfxM = s.match(/[^\d]+$/);
    var suffix = sfxM ? sfxM[0] : '';
    /* цифровая часть для определения разделителей */
    var numPart = s.replace(/[^\d.,]/g, '');
    var decSep = '.';
    if (numPart.indexOf(',') !== -1 && numPart.indexOf('.') !== -1) {
      decSep = numPart.lastIndexOf(',') > numPart.lastIndexOf('.') ? ',' : '.';
    } else if (numPart.indexOf(',') !== -1) {
      decSep = ',';
    }
    var decIdx = numPart.lastIndexOf(decSep);
    var decPlaces = decIdx !== -1 ? numPart.length - decIdx - 1 : 2;

    return function(val) {
      var abs = Math.abs(val);
      var str = abs.toFixed(decPlaces).replace('.', decSep);
      var parts = str.split(decSep);
      /* тысячный разделитель — неразрывный пробел */
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '\u00a0');
      return (decPlaces > 0 ? parts[0] + decSep + parts[1] : parts[0]) + suffix;
    };
  }

  /* ──────────────────────────────────────────────────────────────
     DOM-ссылки
  ────────────────────────────────────────────────────────────── */
  var wrap = document.getElementById('cartItemsWrap');
  if (!wrap) return;

  var cartCountEl      = document.getElementById('cartCount');
  var summaryTotal     = document.getElementById('summaryTotal');
  var summaryItemCount = document.getElementById('summaryItemCount');

  /* Строим форматтер по образцу первой цены-единицы в корзине */
  var formatPrice = (function() {
    var firstItem = wrap.querySelector('.cart-item[data-unit-price]');
    var sample = firstItem ? firstItem.getAttribute('data-unit-price') : '0.00 руб.';
    return makeFormatter(sample);
  })();

  /* ──────────────────────────────────────────────────────────────
     Пересчёт итоговой суммы (по видимым строкам)
     Использует data-price-raw (числовой) × qty — без парсинга строк.
  ────────────────────────────────────────────────────────────── */
  function recalcSummary() {
    var allItems = wrap.querySelectorAll('.cart-item');
    var visItems = wrap.querySelectorAll('.cart-item:not(.cart-item--hidden)');
    var grand = 0;

    visItems.forEach(function(item) {
      var priceRaw = parseFloat(item.getAttribute('data-price-raw') || 0);
      var qty = parseInt((item.querySelector('.qty-input') || {}).value, 10) || 1;
      grand += priceRaw * qty;
    });

    if (summaryTotal) summaryTotal.textContent = formatPrice(grand);

    var totalItems = allItems.length;
    var visCount   = visItems.length;

    if (cartCountEl)      cartCountEl.textContent     = ruPlural(totalItems, 'товар', 'товара', 'товаров');
    if (summaryItemCount) summaryItemCount.textContent = ruPlural(visCount,   'товар', 'товара', 'товаров');

    /* Обновить бейдж корзины в шапке */
    if (window.updateCartBadge) window.updateCartBadge(String(totalItems));
  }

  /* ──────────────────────────────────────────────────────────────
     Пересчёт строки при изменении количества
     Использует data-price-raw (числовой) напрямую — без парсинга
     форматированной строки цены.
  ────────────────────────────────────────────────────────────── */
  function recalcRow(item) {
    var priceRaw = parseFloat(item.getAttribute('data-price-raw') || 0);
    if (!priceRaw) return;
    var qtyInput = item.querySelector('.qty-input');
    if (!qtyInput) return;
    var qty = parseInt(qtyInput.value, 10) || 1;
    var rowEl = item.querySelector('.cart-row-total');
    if (!rowEl) return;

    rowEl.textContent = formatPrice(priceRaw * qty);
    rowEl.classList.add('price-updated');
    setTimeout(function() { rowEl.classList.remove('price-updated'); }, 400);
  }

  /* ──────────────────────────────────────────────────────────────
     Обновление disabled-состояния кнопки «минус»
  ────────────────────────────────────────────────────────────── */
  function syncMinusBtn(item) {
    var input    = item.querySelector('.qty-input');
    var minusBtn = item.querySelector('.qty-btn.minus');
    if (input && minusBtn) {
      var min = parseInt(input.getAttribute('min'), 10) || 1;
      minusBtn.disabled = +input.value <= min;
    }
  }

  /* Инициализация при загрузке */
  wrap.querySelectorAll('.cart-item').forEach(syncMinusBtn);

  /* ──────────────────────────────────────────────────────────────
     Debounced AJAX-обновление корзины на сервере
  ────────────────────────────────────────────────────────────── */
  var updateTimers = {};

  function scheduleServerUpdate(cartId, qty) {
    clearTimeout(updateTimers[cartId]);
    updateTimers[cartId] = setTimeout(function() {
      var fd = new FormData();
      fd.append('quantity[' + cartId + ']', qty);
      fetch('index.php?route=checkout/cart/edit', { method: 'POST', body: fd })
        .catch(function() {}); /* тихая ошибка — UI уже актуален */
    }, 800);
  }

  /* ──────────────────────────────────────────────────────────────
     Обработчики кнопок +/− и ввода количества
  ────────────────────────────────────────────────────────────── */
  wrap.addEventListener('click', function(e) {
    var btn = e.target.closest('.qty-btn');
    if (!btn) return;
    var item  = btn.closest('.cart-item');
    var input = item ? item.querySelector('.qty-input') : null;
    if (!input) return;

    var min = parseInt(input.getAttribute('min'), 10) || 1;
    var val = parseInt(input.value, 10) || min;

    if (btn.classList.contains('plus')) {
      input.value = val + 1;
    } else if (btn.classList.contains('minus') && val > min) {
      input.value = val - 1;
    }

    syncMinusBtn(item);
    recalcRow(item);
    recalcSummary();
    scheduleServerUpdate(item.getAttribute('data-cart-id'), input.value);
  });

  wrap.addEventListener('change', function(e) {
    if (!e.target.classList.contains('qty-input')) return;
    var item = e.target.closest('.cart-item');
    if (!item) return;
    var min = parseInt(e.target.getAttribute('min'), 10) || 1;
    var val = parseInt(e.target.value, 10);
    if (isNaN(val) || val < min) e.target.value = min;
    syncMinusBtn(item);
    recalcRow(item);
    recalcSummary();
    scheduleServerUpdate(item.getAttribute('data-cart-id'), e.target.value);
  });

  /* Блокировать нечисловой ввод */
  wrap.addEventListener('keypress', function(e) {
    if (e.target.classList.contains('qty-input') && !/[0-9]/.test(e.key)) {
      e.preventDefault();
    }
  });

  /* ──────────────────────────────────────────────────────────────
     Удаление товара
  ────────────────────────────────────────────────────────────── */
  window.cartItemRemove = function(cartId, btn) {
    var item = btn ? btn.closest('.cart-item') : null;

    function doRemove() {
      /* Используем OcStore cart.remove() если jQuery доступен */
      if (typeof cart !== 'undefined' && cart.remove) {
        cart.remove(cartId);
        return;
      }
      /* Fallback без jQuery */
      var fd = new FormData();
      fd.append('cart_id', cartId);
      fetch('index.php?route=checkout/cart/remove', { method: 'POST', body: fd })
        .then(function() { location.reload(); })
        .catch(function() { location.reload(); });
    }

    if (item) {
      item.classList.add('removing');
      setTimeout(doRemove, 320);
    } else {
      doRemove();
    }
  };

  /* ──────────────────────────────────────────────────────────────
     Поиск по корзине
     Ищем по всему блоку .cart-info (название, код, бренд, опции).
  ────────────────────────────────────────────────────────────── */
  var searchInput  = document.getElementById('cartSearch');
  var searchClear  = document.getElementById('cartSearchClear');
  var searchStatus = document.getElementById('cartSearchStatus');

  if (searchInput) {
    function runSearch() {
      var q = searchInput.value.trim().toLowerCase();
      var items = wrap.querySelectorAll('.cart-item');
      var shown = 0;

      items.forEach(function(item) {
        var infoEl = item.querySelector('.cart-info');
        var text = infoEl ? infoEl.textContent.toLowerCase() : '';
        var match = !q || text.indexOf(q) !== -1;
        item.classList.toggle('cart-item--hidden', !match);
        if (match) shown++;
      });

      if (searchClear) searchClear.style.display = q ? 'flex' : 'none';
      if (searchStatus) {
        searchStatus.style.display = q ? 'block' : 'none';
        searchStatus.textContent   = q ? ('Найдено: ' + shown + ' из ' + items.length) : '';
      }

      recalcSummary();
    }

    searchInput.addEventListener('input', runSearch);

    if (searchClear) {
      searchClear.addEventListener('click', function() {
        searchInput.value = '';
        runSearch();
        searchInput.focus();
      });
    }
  }

  /* ──────────────────────────────────────────────────────────────
     Чекбоксы — выбор/удаление
  ────────────────────────────────────────────────────────────── */
  var selectAllEl  = document.getElementById('selectAll');
  var selectAllLbl = document.getElementById('selectAllLabel');
  var deleteBtn    = document.getElementById('deleteSelected');

  function updateDeleteBtn() {
    var checked = wrap.querySelectorAll('.cart-item .cart-check input:checked');
    if (!deleteBtn) return;
    var vis = checked.length > 0;
    deleteBtn.classList.toggle('visible', vis);
    deleteBtn.textContent = vis
      ? ('Удалить выбранные (' + checked.length + ')')
      : '';
  }

  function syncSelectAll() {
    var all     = wrap.querySelectorAll('.cart-item .cart-check input');
    var checked = wrap.querySelectorAll('.cart-item .cart-check input:checked');
    if (!selectAllEl) return;
    selectAllEl.checked       = all.length > 0 && checked.length === all.length;
    selectAllEl.indeterminate = checked.length > 0 && checked.length < all.length;
  }

  /* Клик на визуальный чекбокс строки товара */
  wrap.addEventListener('click', function(e) {
    var box = e.target.closest('.cart-check .cart-check-box');
    if (!box) return;
    var cb = box.previousElementSibling;
    if (cb && cb.type === 'checkbox') {
      cb.checked = !cb.checked;
      syncSelectAll();
      updateDeleteBtn();
    }
  });

  /* «Выбрать всё» */
  if (selectAllLbl) {
    function toggleSelectAll() {
      var newState = !selectAllEl.checked;
      selectAllEl.checked = newState;
      selectAllEl.indeterminate = false;
      wrap.querySelectorAll('.cart-item .cart-check input').forEach(function(cb) {
        cb.checked = newState;
      });
      updateDeleteBtn();
    }
    selectAllLbl.addEventListener('click', toggleSelectAll);
    selectAllLbl.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleSelectAll(); }
    });
  }

  /* Удалить выбранные */
  if (deleteBtn) {
    deleteBtn.addEventListener('click', function() {
      var checked = wrap.querySelectorAll('.cart-item .cart-check input:checked');
      checked.forEach(function(cb) {
        var item = cb.closest('.cart-item');
        var cId  = item ? item.getAttribute('data-cart-id') : null;
        if (!cId) return;
        item.classList.add('removing');
        setTimeout(function() {
          if (typeof cart !== 'undefined' && cart.remove) cart.remove(cId);
          else {
            var fd = new FormData(); fd.append('cart_id', cId);
            fetch('index.php?route=checkout/cart/remove', { method: 'POST', body: fd })
              .then(function() { location.reload(); });
          }
        }, 320);
      });
    });
  }

  /* ──────────────────────────────────────────────────────────────
     Слайдер рекомендаций
  ────────────────────────────────────────────────────────────── */
  var recSlider = document.querySelector('.recommend-slider');
  var recPrev   = document.querySelector('.rec-slider-prev');
  var recNext   = document.querySelector('.rec-slider-next');

  if (recSlider && recPrev && recNext) {
    recPrev.addEventListener('click', function() { recSlider.scrollBy({ left: -220, behavior: 'smooth' }); });
    recNext.addEventListener('click', function() { recSlider.scrollBy({ left:  220, behavior: 'smooth' }); });
  }

  /* ──────────────────────────────────────────────────────────────
     Первичный расчёт
  ────────────────────────────────────────────────────────────── */
  recalcSummary();

});
