/* ============================================================
   SEARCH.JS — Страница поиска
   ============================================================ */
(function () {
  'use strict';

  /* ── Расширенный поиск — раскрытие ───────────────────────── */
  var toggle    = document.getElementById('advancedToggle');
  var advanced  = document.getElementById('srchAdvanced');
  if (toggle && advanced) {
    toggle.addEventListener('click', function () {
      var open = !advanced.hidden;
      advanced.hidden = open;
      toggle.classList.toggle('open', !open);
    });
  }

  /* ── Очистка строки ────────────────────────────────────────── */
  var clearBtn = document.getElementById('srchClear');
  var srchInput = document.getElementById('searchInput');
  if (clearBtn && srchInput) {
    clearBtn.addEventListener('click', function () {
      srchInput.value = '';
      srchInput.focus();
    });
  }

  /* ── Сброс расширенных фильтров ──────────────────────────── */
  var resetBtn = document.getElementById('filterReset');
  var adResetBtn = document.querySelector('.srch-adv-reset');
  function resetAdvanced() {
    if (!advanced) return;
    advanced.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
    advanced.querySelectorAll('input[type=number]').forEach(function (i) { i.value = ''; });
    advanced.querySelectorAll('input[type=checkbox]').forEach(function (c) { c.checked = true; });
  }
  if (adResetBtn) adResetBtn.addEventListener('click', resetAdvanced);
  if (resetBtn)   resetBtn.addEventListener('click', resetAdvanced);

  /* ── Слайдер цен ─────────────────────────────────────────── */
  var priceMin    = document.getElementById('priceMin');
  var priceMax    = document.getElementById('priceMax');
  var priceMinNum = document.getElementById('priceMinNum');
  var priceMaxNum = document.getElementById('priceMaxNum');
  var priceFill   = document.getElementById('priceRangeFill');

  function updatePriceFill() {
    if (!priceMin || !priceMax || !priceFill) return;
    var min = +priceMin.min, max = +priceMin.max;
    var lo  = +priceMin.value, hi = +priceMax.value;
    if (lo > hi) { var t = lo; lo = hi; hi = t; }
    priceFill.style.left  = ((lo - min) / (max - min) * 100) + '%';
    priceFill.style.width = ((hi - lo)  / (max - min) * 100) + '%';
  }

  if (priceMin && priceMax) {
    priceMin.addEventListener('input', function () {
      if (+priceMin.value > +priceMax.value) priceMin.value = priceMax.value;
      if (priceMinNum) priceMinNum.value = priceMin.value;
      updatePriceFill();
    });
    priceMax.addEventListener('input', function () {
      if (+priceMax.value < +priceMin.value) priceMax.value = priceMin.value;
      if (priceMaxNum) priceMaxNum.value = priceMax.value;
      updatePriceFill();
    });
    if (priceMinNum) priceMinNum.addEventListener('change', function () {
      var v = Math.max(+priceMin.min, Math.min(+priceMinNum.value, +priceMax.value));
      priceMinNum.value = priceMin.value = v;
      updatePriceFill();
    });
    if (priceMaxNum) priceMaxNum.addEventListener('change', function () {
      var v = Math.min(+priceMax.max, Math.max(+priceMaxNum.value, +priceMin.value));
      priceMaxNum.value = priceMax.value = v;
      updatePriceFill();
    });
    updatePriceFill();
  }

  /* ── Убрать chip активного фильтра ──────────────────────── */
  document.querySelectorAll('.af-remove').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var chip = btn.closest('.af-chip');
      if (chip) chip.remove();
    });
  });
  var clearAll = document.querySelector('.af-clear');
  if (clearAll) {
    clearAll.addEventListener('click', function () {
      document.querySelectorAll('.af-chip').forEach(function (c) { c.remove(); });
    });
  }

})();
