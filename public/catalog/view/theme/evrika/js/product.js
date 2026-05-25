'use strict';

/* =============================================
   1. GALLERY SWITCHING
   ============================================= */
(function initGallery() {
  var thumbs = document.querySelectorAll('.gallery-thumb');
  var mainImg = document.querySelector('.gallery-main img');
  var mainPlaceholder = document.querySelector('.gallery-main-placeholder');

  thumbs.forEach(function(thumb, index) {
    thumb.addEventListener('click', function() {
      thumbs.forEach(function(t) { t.classList.remove('active'); });
      thumb.classList.add('active');

      var imgSrc = thumb.getAttribute('data-img');
      var svgContent = thumb.getAttribute('data-svg');

      if (imgSrc && mainImg) {
        if (mainPlaceholder) mainPlaceholder.style.display = 'none';
        mainImg.src = imgSrc;
        mainImg.style.display = 'block';
      } else if (svgContent && mainPlaceholder) {
        if (mainImg) mainImg.style.display = 'none';
        mainPlaceholder.style.display = 'flex';
        mainPlaceholder.innerHTML = svgContent;
      }

      window._galleryIndex = index;
    });
  });
})();


/* =============================================
   2. LIGHTBOX
   ============================================= */
(function initLightbox() {
  var lightbox = document.getElementById('lightbox');
  var lightboxContent = document.getElementById('lightboxContent');
  if (!lightbox) return;

  var btnClose = lightbox.querySelector('.lightbox-close');
  var btnPrev  = lightbox.querySelector('.lightbox-prev');
  var btnNext  = lightbox.querySelector('.lightbox-next');
  var galleryMain = document.querySelector('.gallery-main');
  var thumbs = document.querySelectorAll('.gallery-thumb');

  window._galleryIndex = 0;

  function showLightboxAt(index) {
    var count = thumbs.length;
    if (count === 0) return;
    window._galleryIndex = (index + count) % count;
    var thumb = thumbs[window._galleryIndex];
    var imgSrc = thumb ? thumb.getAttribute('data-img') : null;
    var svgContent = thumb ? thumb.getAttribute('data-svg') : null;

    if (imgSrc) {
      lightboxContent.innerHTML = '<img src="' + imgSrc + '" class="lightbox-img" alt="">';
    } else {
      lightboxContent.innerHTML = svgContent || '';
    }
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (galleryMain) {
    galleryMain.addEventListener('click', function() { showLightboxAt(window._galleryIndex || 0); });
    galleryMain.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showLightboxAt(window._galleryIndex || 0); }
    });
  }
  if (btnClose) btnClose.addEventListener('click', closeLightbox);
  if (btnPrev)  btnPrev.addEventListener('click', function(e) { e.stopPropagation(); showLightboxAt((window._galleryIndex || 0) - 1); });
  if (btnNext)  btnNext.addEventListener('click', function(e) { e.stopPropagation(); showLightboxAt((window._galleryIndex || 0) + 1); });

  lightbox.addEventListener('click', function(e) { if (e.target === lightbox) closeLightbox(); });

  document.addEventListener('keydown', function(e) {
    if (!lightbox.classList.contains('open')) return;
    if (e.key === 'Escape')     closeLightbox();
    else if (e.key === 'ArrowLeft')  showLightboxAt((window._galleryIndex || 0) - 1);
    else if (e.key === 'ArrowRight') showLightboxAt((window._galleryIndex || 0) + 1);
  });
})();


/* =============================================
   3. TAB SWITCH — публичная функция для чипов характеристик
   ============================================= */
function switchTab(tabId) {
  var btn = document.querySelector('.tab-btn[data-tab="' + tabId + '"]');
  if (!btn) return;
  btn.click();
  btn.scrollIntoView({ behavior: 'smooth', block: 'start' });
}


/* =============================================
   4. TABS
   ============================================= */
(function initTabs() {
  var tabBtns   = document.querySelectorAll('.tab-btn');
  var tabPanels = document.querySelectorAll('.tab-panel');

  tabBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var target = btn.getAttribute('data-tab');

      tabBtns.forEach(function(b) {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      tabPanels.forEach(function(p) { p.classList.remove('active'); });

      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      var panel = document.getElementById('tab-' + target);
      if (panel) panel.classList.add('active');
    });
  });
})();


/* =============================================
   5. QUANTITY CONTROL — все контролы на странице
   ============================================= */
(function initQtyControl() {
  document.querySelectorAll('.qty-control').forEach(function(control) {
    var qtyInput = control.querySelector('.qty-input');
    var btnPlus  = control.querySelector('.qty-btn.plus');
    var btnMinus = control.querySelector('.qty-btn.minus');
    if (!qtyInput) return;

    var minimum = parseInt(qtyInput.getAttribute('min'), 10) || 1;

    function getVal() {
      var v = parseInt(qtyInput.value, 10);
      return isNaN(v) || v < minimum ? minimum : v;
    }

    if (btnPlus)  btnPlus.addEventListener('click', function() { qtyInput.value = getVal() + 1; });
    if (btnMinus) btnMinus.addEventListener('click', function() { var v = getVal(); qtyInput.value = v > minimum ? v - 1 : minimum; });

    qtyInput.addEventListener('blur', function() {
      var v = parseInt(qtyInput.value, 10);
      qtyInput.value = (isNaN(v) || v < minimum) ? minimum : Math.floor(v);
    });
    qtyInput.addEventListener('keypress', function(e) { if (!/[0-9]/.test(e.key)) e.preventDefault(); });
  });
})();


/* =============================================
   6. COPY SKU ON CLICK
   ============================================= */
(function initSkuCopy() {
  document.querySelectorAll('.sku-value').forEach(function(el) {
    var tooltip = document.createElement('span');
    tooltip.className = 'sku-tooltip';
    tooltip.textContent = 'Скопировано!';
    el.appendChild(tooltip);

    el.addEventListener('click', function() {
      var text = el.childNodes[0] ? el.childNodes[0].textContent.trim() : el.textContent.trim();

      function showTooltip() {
        tooltip.classList.add('show');
        setTimeout(function() { tooltip.classList.remove('show'); }, 1500);
      }
      function fallbackCopy(str) {
        var ta = document.createElement('textarea');
        ta.value = str;
        ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(ta);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(showTooltip).catch(function() { fallbackCopy(text); showTooltip(); });
      } else {
        fallbackCopy(text);
        showTooltip();
      }
    });
  });
})();


/* =============================================
   7. RELATED SLIDERS (похожие, вы смотрели)
   ============================================= */
(function initRelatedSliders() {
  document.querySelectorAll('.related-slider-wrap').forEach(function(wrap) {
    var slider  = wrap.querySelector('.related-slider');
    var btnPrev = wrap.querySelector('.slider-prev');
    var btnNext = wrap.querySelector('.slider-next');
    if (!slider) return;
    if (btnPrev) btnPrev.addEventListener('click', function() { slider.scrollBy({ left: -220, behavior: 'smooth' }); });
    if (btnNext) btnNext.addEventListener('click', function() { slider.scrollBy({ left:  220, behavior: 'smooth' }); });
  });
})();


/* =============================================
   8. STAR RATING (вкладка «Отзывы»)
   ============================================= */
(function initStarRating() {
  var stars = document.querySelectorAll('.star-btn');
  var selectedRating = 0;

  stars.forEach(function(star, index) {
    star.addEventListener('mouseover', function() {
      stars.forEach(function(s, i) { s.classList.toggle('active', i <= index); });
    });
    star.addEventListener('mouseout', function() {
      stars.forEach(function(s, i) { s.classList.toggle('active', i < selectedRating); });
    });
    star.addEventListener('click', function() {
      selectedRating = index + 1;
      stars.forEach(function(s, i) { s.classList.toggle('active', i < selectedRating); });
    });
  });
})();


/* =============================================
   9. OPTION PRICE RECALCULATION
   ============================================= */
(function initOptionPriceCalc() {
  var section = document.querySelector('.product-section');
  if (!section) return;

  /* Локальный парсер цены — обрабатывает оба формата:
     "90.00 руб.", "1 234,56 руб.", "+40.00 руб." */
  function parsePrice(s) {
    s = String(s || '').trim();
    var neg = /^[-−]/.test(s) ? -1 : 1;
    /* оставляем только цифры, запятую, точку */
    var n = s.replace(/[^\d.,]/g, '');
    if (n.indexOf(',') !== -1 && n.indexOf('.') !== -1) {
      /* оба символа: последний — десятичный */
      if (n.lastIndexOf(',') > n.lastIndexOf('.')) {
        n = n.replace(/\./g, '').replace(',', '.'); /* "1.234,56" → "1234.56" */
      } else {
        n = n.replace(/,/g, '');                   /* "1,234.56" → "1234.56" */
      }
    } else if (n.indexOf(',') !== -1) {
      n = n.replace(',', '.');                     /* "90,00"   → "90.00"   */
    }
    /* если только точка — parseFloat читает правильно: "90.00" → 90 */
    return neg * (parseFloat(n) || 0);
  }

  /* Находим элемент с текущей ценой и сохраняем исходное значение */
  var priceEl = section.querySelector('.buy-price-row .price-value')
             || section.querySelector('.price-row .price-value');
  if (!priceEl) return;  /* цена скрыта (гость не авторизован) */

  /* ── Определяем формат из оригинальной строки ── */
  var origText  = priceEl.textContent.trim();                        /* "90.00р."  */
  var numMatch  = origText.match(/[\d][\d\s.,]*/);
  var cleanNum  = numMatch ? numMatch[0].replace(/\s/g, '') : '';    /* "90.00"    */
  var decSep    = '.';
  if (cleanNum.indexOf(',') !== -1 && cleanNum.indexOf('.') !== -1) {
    decSep = cleanNum.lastIndexOf(',') > cleanNum.lastIndexOf('.') ? ',' : '.';
  } else if (cleanNum.indexOf(',') !== -1) {
    decSep = ',';
  }
  var decIdx    = cleanNum.lastIndexOf(decSep);
  var decPlaces = decIdx !== -1 ? cleanNum.length - decIdx - 1 : 2;
  /* суффикс — всё что после последней цифры */
  var suffix    = origText.match(/[^\d]+$/) ? origText.match(/[^\d]+$/)[0] : '';

  function formatPrice(val) {
    return val.toFixed(decPlaces).replace('.', decSep) + suffix;
  }

  /* Храним base price в data-атрибуте, чтобы не терять его при перезаписи */
  var basePrice = parsePrice(origText);
  if (!basePrice) return;
  priceEl.setAttribute('data-base-price', basePrice);

  /* Обновляем все элементы с ценой и суффикс шт. */
  function setPriceText(val, qty) {
    var formatted = formatPrice(val);

    section.querySelectorAll('.price-value').forEach(function(el) {
      if (!el.closest('.price-old-row') && !el.closest('.price-old')) {
        el.textContent = formatted;
        el.classList.add('price-updated');
        setTimeout(function() { el.classList.remove('price-updated'); }, 400);
      }
    });

    /* Суффикс "/ шт." → "/ N шт." при qty > 1 */
    var unitLabel = qty > 1 ? ('/ ' + qty + '\u00a0шт.') : '/\u00a0шт.';
    section.querySelectorAll('.price-unit').forEach(function(el) {
      el.textContent = unitLabel;
    });

    var mobPriceEl = document.querySelector('#mob-buy-bar .price-value');
    if (mobPriceEl) {
      mobPriceEl.textContent = formatted;
      mobPriceEl.classList.add('price-updated');
      setTimeout(function() { mobPriceEl.classList.remove('price-updated'); }, 400);
    }
    var mobUnit = document.querySelector('#mob-buy-bar .price-unit');
    if (mobUnit) mobUnit.textContent = unitLabel;
  }

  /* ── Оптовые тиры из .price-discount-row ── */
  var tiers = [{ qty: 1, price: basePrice }];
  document.querySelectorAll('.price-discount-row').forEach(function(row) {
    var qtyEl   = row.querySelector('span:first-child');
    var priceEl2 = row.querySelector('.price-discount-val');
    if (!qtyEl || !priceEl2) return;
    var qtyMatch = qtyEl.textContent.match(/\d+/);
    var tierPrice = parsePrice(priceEl2.textContent);
    if (qtyMatch && tierPrice) {
      tiers.push({ qty: parseInt(qtyMatch[0], 10), price: tierPrice });
    }
  });
  tiers.sort(function(a, b) { return a.qty - b.qty; });

  /* Цена для текущего количества согласно тирам */
  function priceForQty(qty) {
    var p = basePrice;
    tiers.forEach(function(t) { if (qty >= t.qty) p = t.price; });
    return p;
  }

  /* Текущее количество */
  function currentQty() {
    var inp = document.getElementById('input-quantity')
           || document.getElementById('input-quantity-mob');
    return inp ? (parseInt(inp.value, 10) || 1) : 1;
  }

  /* Дельта по опциям */
  function optionDelta() {
    var delta = 0;
    section.querySelectorAll('[name^="option["]').forEach(function(el) {
      var priceAttr = '';
      if (el.tagName === 'SELECT' && el.value) {
        var opt = el.querySelector('option[value="' + el.value + '"]');
        priceAttr = opt ? (opt.getAttribute('data-price') || '') : '';
      } else if ((el.type === 'radio' || el.type === 'checkbox') && el.checked) {
        priceAttr = el.getAttribute('data-price') || '';
      }
      if (priceAttr) delta += parsePrice(priceAttr);
    });
    return delta;
  }

  function recalc() {
    var qty = currentQty();
    setPriceText((priceForQty(qty) + optionDelta()) * qty, qty);
  }

  /* Слушаем смену опций */
  section.addEventListener('change', function(e) {
    if (e.target.name && e.target.name.startsWith('option[')) recalc();
  });

  /* Слушаем смену количества (оба инпута: десктоп + моб) */
  ['input-quantity', 'input-quantity-mob'].forEach(function(id) {
    var inp = document.getElementById(id);
    if (!inp) return;
    inp.addEventListener('input', recalc);
    inp.addEventListener('change', recalc);
  });

  /* Кнопки +/− тоже тригерят recalc */
  document.querySelectorAll('.qty-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      setTimeout(recalc, 0); /* после обновления значения инпута */
    });
  });
})();
