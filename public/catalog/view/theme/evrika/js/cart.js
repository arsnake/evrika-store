'use strict';

/* =======================================================
   CART.JS — глобальные методы корзины
   ======================================================= */

/* ── Утилита: парсинг цены "1 234,56 руб." → 1234.56 ── */
window.parseRub = function(s) {
  s = String(s || '').trim();
  var neg = /^[-−]/.test(s) ? -1 : 1;
  var n = s.replace(/[^\d.,]/g, '');
  if (n.indexOf(',') !== -1 && n.indexOf('.') !== -1) {
    if (n.lastIndexOf(',') > n.lastIndexOf('.')) {
      n = n.replace(/\./g, '').replace(',', '.');
    } else {
      n = n.replace(/,/g, '');
    }
  } else if (n.indexOf(',') !== -1) {
    n = n.replace(',', '.');
  }
  return neg * (parseFloat(n) || 0);
};

/* ── Стриппинг HTML из строки ── */
function stripHtml(html) {
  var tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
}

/* ── Найти видимый якорь иконки корзины ── */
function getCartAnchor() {
  /* Десктоп: ссылка-иконка корзины в шапке */
  var desktop = document.querySelector('.h-action.cart');
  if (desktop && desktop.offsetWidth > 0) return { el: desktop, side: 'bottom' };
  /* Мобильный: иконка корзины в mob-nav */
  var mobile = document.querySelector('#mob-cart-badge');
  if (mobile) return { el: mobile.parentElement, side: 'top' };
  return null;
}

/* ── Обновление счётчиков корзины в шапке ── */
function updateCartBadge(totalStr) {
  var m = (totalStr || '').match(/\d+/);
  var count = m ? parseInt(m[0], 10) : 0;

  ['cart-badge', 'mob-cart-badge'].forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
    el.classList.remove('badge-pop');
    void el.offsetWidth;
    el.classList.add('badge-pop');
  });
}

/* ── Toast — привязан к иконке корзины ── */
function cartToast(msg, type) {
  var text = stripHtml(msg);

  var existing = document.getElementById('evr-toast');
  if (existing) { clearTimeout(existing._tid); existing.remove(); }

  var t = document.createElement('div');
  t.id = 'evr-toast';
  t.className = 'evr-toast evr-toast--' + (type === 'err' ? 'err' : 'ok');
  t.textContent = text;
  document.body.appendChild(t);

  /* Позиционирование: рядом с иконкой корзины */
  var anchor = type !== 'err' ? getCartAnchor() : null;
  if (anchor) {
    var rect = anchor.el.getBoundingClientRect();
    t.style.left   = Math.round(rect.left + rect.width / 2) + 'px';
    t.style.bottom  = '';
    if (anchor.side === 'bottom') {
      /* Десктоп — под иконкой */
      t.style.top    = Math.round(rect.bottom + 10) + 'px';
      t.style.transform = 'translateX(-50%) translateY(-6px)';
      t.classList.add('evr-toast--arrow-top');
    } else {
      /* Мобильный — над mob-nav */
      t.style.top    = '';
      t.style.bottom = Math.round(window.innerHeight - rect.top + 10) + 'px';
      t.style.transform = 'translateX(-50%) translateY(6px)';
      t.classList.add('evr-toast--arrow-bottom');
    }
  }

  requestAnimationFrame(function() {
    t.style.transform = t.style.transform.replace(/translateY\([^)]+\)/, 'translateY(0)');
    t.classList.add('show');
  });

  t._tid = setTimeout(function() {
    t.classList.remove('show');
    setTimeout(function() { if (t.parentNode) t.remove(); }, 320);
  }, 3000);
}

/* ── Сбор опций с формы ── */
window.collectOptions = function(scope) {
  var opts = {};
  (scope || document).querySelectorAll('[name^="option["]').forEach(function(el) {
    var m = el.name.match(/option\[(\d+)\]/);
    if (!m) return;
    var id = m[1];
    if (el.tagName === 'SELECT') {
      if (el.value) opts[id] = el.value;
    } else if (el.type === 'checkbox') {
      if (el.checked) { opts[id] = opts[id] || []; opts[id].push(el.value); }
    } else if (el.type === 'radio') {
      if (el.checked) opts[id] = el.value;
    } else {
      if (el.value) opts[id] = el.value;
    }
  });
  return opts;
};

/* ── Кнопки корзины в карточках товара ──
   quickBuy=false → добавить в корзину (тост)
   quickBuy=true  → добавить в корзину + редирект в корзину

   Логика при ошибке сервера (нет обязательных опций и т.п.):
   любой json.error или json.redirect → редирект на страницу товара.
   data-has-opts='1' — быстрый клиентский путь (без запроса к серверу). */
window.cardCartAdd = function(btn, quickBuy) {
  var href = btn.getAttribute('data-href');

  /* Быстрый путь: OCMod уже знает что есть обязательные опции */
  if (btn.getAttribute('data-has-opts') === '1') {
    location.href = href;
    return;
  }

  var pid = btn.getAttribute('data-pid');
  var inp = btn.closest('.prod-actions').querySelector('.prod-qty-input');
  var qty = inp ? parseInt(inp.value, 10) || 1 : 1;

  btn.disabled = true;
  btn.classList.add('btn-loading');

  var fd = new FormData();
  fd.append('product_id', pid);
  fd.append('quantity', qty);

  fetch('index.php?route=checkout/cart/add', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(json) {
      btn.disabled = false;
      btn.classList.remove('btn-loading');

      /* Любая ошибка или redirect от сервера → идём на страницу товара,
         где пользователь сможет выбрать опции */
      if (json.error || json.redirect) {
        location.href = href;
        return;
      }

      if (json.success) {
        if (quickBuy) {
          location.href = 'index.php?route=checkout/cart';
          return;
        }
        updateCartBadge(json.total);
        cartToast(json.success, 'ok');
        btn.classList.add('added');
        setTimeout(function() { btn.classList.remove('added'); }, 2000);
      }
    })
    .catch(function() {
      btn.disabled = false;
      btn.classList.remove('btn-loading');
      cartToast('Ошибка соединения с сервером', 'err');
    });
};

/* ── Главный метод добавления в корзину ── */
/* redirectToCart=true → после успешного добавления редирект в корзину (без тоаста) */
window.cartAdd = function(productId, qty, options, btn, redirectToCart) {
  qty     = parseInt(qty, 10) || 1;
  options = options || {};

  if (btn) { btn.disabled = true; btn.classList.add('btn-loading'); }

  var fd = new FormData();
  fd.append('product_id', productId);
  fd.append('quantity', qty);
  Object.keys(options).forEach(function(k) {
    var v = options[k];
    if (Array.isArray(v)) {
      v.forEach(function(i) { fd.append('option[' + k + '][]', i); });
    } else {
      fd.append('option[' + k + ']', v);
    }
  });

  fetch('index.php?route=checkout/cart/add', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(json) {
      if (btn) { btn.disabled = false; btn.classList.remove('btn-loading'); }

      if (json.error) {
        var msgs = [];
        var errObj = json.error;
        if (typeof errObj === 'object') {
          Object.values(errObj).forEach(function(v) {
            if (typeof v === 'object') Object.values(v).forEach(function(m) { msgs.push(stripHtml(m)); });
            else msgs.push(stripHtml(v));
          });
        } else {
          msgs.push(stripHtml(String(errObj)));
        }
        cartToast(msgs[0] || 'Заполните все обязательные опции', 'err');
        return;
      }

      if (json.redirect) { location.href = json.redirect; return; }

      if (json.success) {
        if (redirectToCart) {
          location.href = 'index.php?route=checkout/cart';
          return;
        }
        updateCartBadge(json.total);
        cartToast(json.success, 'ok');
        if (btn) {
          btn.classList.add('added');
          setTimeout(function() { btn.classList.remove('added'); }, 2000);
        }
      }
    })
    .catch(function() {
      if (btn) { btn.disabled = false; btn.classList.remove('btn-loading'); }
      cartToast('Ошибка соединения с сервером', 'err');
    });
};
