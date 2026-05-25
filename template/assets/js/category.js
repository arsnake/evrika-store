/* ===================================================
   CATEGORY PAGE JS — product/category
   Эврика | Канцелярские товары
   Только специфичная логика страницы категории.
   Общие функции (фильтры, вид, аккордеон) — в main.js
=================================================== */

/* ---------- Demo view switcher (только для прототипа) ---------- */
(function () {
  var btns   = document.querySelectorAll('.demo-btn');
  var mainEl = document.querySelector('main.cat-page');
  if (!mainEl || !btns.length) return;

  btns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var view = btn.dataset.view;
      mainEl.className = 'cat-page cat-view--' + view;
      btns.forEach(function(b) { b.classList.toggle('active', b === btn); });
    });
  });
})();
