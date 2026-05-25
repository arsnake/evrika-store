/* =============================================
   CART PAGE JS — checkout/cart
   Эврика B2B Канцелярский магазин
   ============================================= */

/* =============================================
   1. СОСТОЯНИЕ КОРЗИНЫ
   ============================================= */
var cartItems = [
  { id: 1, qty: 2,  price: 125.00 },
  { id: 2, qty: 1,  price:  89.50 },
  { id: 3, qty: 5,  price:  18.00 },
];

/* =============================================
   2. КОЛИЧЕСТВО ± ПО СТРОКЕ
   ============================================= */
(function initQty() {
  document.querySelectorAll('.cart-item').forEach(function(row) {
    var id       = +row.dataset.id;
    var minusBtn = row.querySelector('.qty-btn[data-action="minus"]');
    var plusBtn  = row.querySelector('.qty-btn[data-action="plus"]');
    var input    = row.querySelector('.qty-input');

    function syncItem(val) {
      var item = cartItems.find(function(i) { return i.id === id; });
      if (!item) return;
      item.qty = val;
      input.value = val;
      minusBtn.disabled = val <= 1;
      updateRowTotal(row, item);
      recalcSummary();
    }

    if (minusBtn) minusBtn.addEventListener('click', function() {
      var item = cartItems.find(function(i) { return i.id === id; });
      if (item && item.qty > 1) syncItem(item.qty - 1);
    });
    if (plusBtn) plusBtn.addEventListener('click', function() {
      var item = cartItems.find(function(i) { return i.id === id; });
      if (item) syncItem(item.qty + 1);
    });
    if (input) input.addEventListener('change', function() {
      var v = parseInt(input.value, 10);
      if (isNaN(v) || v < 1) v = 1;
      syncItem(v);
    });

    // init disabled state
    var item = cartItems.find(function(i) { return i.id === id; });
    if (item && minusBtn) minusBtn.disabled = item.qty <= 1;
  });
})();

function updateRowTotal(row, item) {
  var totalEl = row.querySelector('.cart-row-total');
  if (totalEl) totalEl.textContent = formatPrice(item.qty * item.price);
}

/* =============================================
   3. УДАЛЕНИЕ СТРОКИ
   ============================================= */
(function initDelete() {
  document.querySelectorAll('.cart-delete').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var row = btn.closest('.cart-item');
      var id  = +row.dataset.id;
      removeItem(id, row);
    });
  });
})();

function removeItem(id, row) {
  row.classList.add('removing');
  setTimeout(function() {
    row.remove();
    cartItems = cartItems.filter(function(i) { return i.id !== id; });
    recalcSummary();
    updateSelectAllState();
    updateDeleteSelectedBtn();
    if (cartItems.length === 0) showEmpty();
  }, 300);
}

/* =============================================
   4. ЧЕКБОКСЫ — выбрать всё / удалить выбранные
   =============================================
   Используем <div> вместо <label> для обёртки,
   чтобы исключить двойное переключение браузером.
   Визуальное состояние — через CSS input:checked + .cart-check-box,
   JS только устанавливает .checked на input.
   ============================================= */
(function initCheckboxes() {
  var selectAllInput = document.getElementById('selectAll');
  var selectAllLabel = document.getElementById('selectAllLabel');
  var deleteSel      = document.getElementById('deleteSelected');

  // «Выбрать всё» — клик по всей строке (span + текст)
  if (selectAllLabel && selectAllInput) {
    selectAllLabel.addEventListener('click', function() {
      var newState = !selectAllInput.checked;
      selectAllInput.checked = newState;
      document.querySelectorAll('.cart-item:not(.removing):not(.cart-item--hidden) input[type=checkbox]').forEach(function(cb) {
        cb.checked = newState;
      });
      updateDeleteSelectedBtn();
    });
  }

  // Чекбоксы отдельных строк — клик только по span.cart-check-box
  document.querySelectorAll('.cart-item .cart-check-box').forEach(function(box) {
    box.addEventListener('click', function() {
      var cb = box.previousElementSibling; // input[type=checkbox] — прямой предшественник
      cb.checked = !cb.checked;
      updateSelectAllState();
      updateDeleteSelectedBtn();
    });
  });

  // Удалить выбранные
  if (deleteSel) {
    deleteSel.addEventListener('click', function() {
      var toRemove = Array.prototype.slice.call(
        document.querySelectorAll('.cart-item input[type=checkbox]:checked')
      );
      toRemove.forEach(function(cb) {
        var row = cb.closest('.cart-item');
        if (row) removeItem(+row.dataset.id, row);
      });
    });
  }
})();

function updateSelectAllState() {
  var selectAllInput = document.getElementById('selectAll');
  if (!selectAllInput) return;
  var all     = document.querySelectorAll('.cart-item:not(.removing):not(.cart-item--hidden) input[type=checkbox]');
  var checked = document.querySelectorAll('.cart-item:not(.removing):not(.cart-item--hidden) input[type=checkbox]:checked');
  selectAllInput.checked = all.length > 0 && checked.length === all.length;
  // CSS :checked автоматически обновляет внешний вид
}

function updateDeleteSelectedBtn() {
  var checked = document.querySelectorAll('.cart-item:not(.removing) input[type=checkbox]:checked');
  var btn = document.getElementById('deleteSelected');
  if (!btn) return;
  var n = checked.length;
  btn.classList.toggle('visible', n > 0);
  btn.textContent = n > 0 ? 'Удалить выбранные (' + n + ')' : '';
}

/* =============================================
   5. ПЕРЕСЧЁТ ИТОГОВ
   ============================================= */
function recalcSummary() {
  var subtotal = cartItems.reduce(function(sum, i) { return sum + i.qty * i.price; }, 0);
  var totalQty = cartItems.reduce(function(sum, i) { return sum + i.qty; }, 0);

  var countEl = document.getElementById('cartCount');
  if (countEl) countEl.textContent = totalQty + ' ' + pluralItems(totalQty);

  var subtotalEl  = document.getElementById('summarySubtotal');
  var totalEl     = document.getElementById('summaryTotal');
  var itemCountEl = document.getElementById('summaryItemCount');

  if (subtotalEl)  subtotalEl.textContent  = formatPrice(subtotal);
  if (totalEl)     totalEl.textContent     = formatPrice(subtotal);
  if (itemCountEl) itemCountEl.textContent = totalQty + ' ' + pluralItems(totalQty);
}

/* =============================================
   6. ПОИСК ПО КОРЗИНЕ
   ============================================= */
(function initCartSearch() {
  var searchInput = document.getElementById('cartSearch');
  var clearBtn    = document.getElementById('cartSearchClear');
  var statusEl    = document.getElementById('cartSearchStatus');
  if (!searchInput) return;

  function filterItems(query) {
    var q = query.toLowerCase().trim();
    var items = document.querySelectorAll('.cart-item');
    var visible = 0;

    items.forEach(function(item) {
      if (item.classList.contains('removing')) return;
      var name  = (item.querySelector('.cart-name')      || {}).textContent || '';
      var sku   = (item.querySelector('.cart-sku-row')   || {}).textContent || '';
      var brand = (item.querySelector('.cart-brand-row') || {}).textContent || '';
      var text  = (name + ' ' + sku + ' ' + brand).toLowerCase();

      var match = q.length < 3 || text.indexOf(q) !== -1;
      item.classList.toggle('cart-item--hidden', !match);
      if (match) visible++;
    });

    if (clearBtn) {
      clearBtn.style.display = q.length > 0 ? 'flex' : 'none';
    }

    if (statusEl) {
      if (q.length >= 3) {
        statusEl.textContent = 'Найдено: ' + visible + ' ' + pluralItems(visible);
        statusEl.style.display = 'block';
      } else {
        statusEl.style.display = 'none';
      }
    }

    // Пересчитать состояние «Выбрать всё» с учётом фильтра
    updateSelectAllState();
    updateDeleteSelectedBtn();
  }

  searchInput.addEventListener('input', function() {
    filterItems(searchInput.value);
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      searchInput.value = '';
      filterItems('');
      searchInput.focus();
    });
  }
})();

/* =============================================
   7. ПУСТОЕ СОСТОЯНИЕ
   ============================================= */
function showEmpty() {
  var layout  = document.getElementById('cartLayout');
  var emptyEl = document.getElementById('cartEmpty');
  var searchBar = document.querySelector('.cart-search-bar');
  if (layout)    layout.style.display    = 'none';
  if (searchBar) searchBar.style.display = 'none';
  if (emptyEl)   emptyEl.classList.add('visible');
}

/* =============================================
   8. ВСПОМОГАТЕЛЬНЫЕ
   ============================================= */
function formatPrice(n) {
  return n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₽';
}
function pluralItems(n) {
  var mod10 = n % 10, mod100 = n % 100;
  if (mod100 >= 11 && mod100 <= 19) return 'товаров';
  if (mod10 === 1) return 'товар';
  if (mod10 >= 2 && mod10 <= 4) return 'товара';
  return 'товаров';
}

/* Слайдер рекомендаций */
(function initRecSlider() {
  var slider  = document.querySelector('.recommend-slider');
  var prevBtn = document.querySelector('.rec-slider-prev');
  var nextBtn = document.querySelector('.rec-slider-next');
  if (!slider) return;
  if (prevBtn) prevBtn.addEventListener('click', function() { slider.scrollBy({ left: -210, behavior: 'smooth' }); });
  if (nextBtn) nextBtn.addEventListener('click', function() { slider.scrollBy({ left:  210, behavior: 'smooth' }); });
})();
