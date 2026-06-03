/**
 * cookie.js — Cookie Consent Banner
 * Эврика / тема evrika
 *
 * Логика:
 *  - При загрузке страницы проверяет localStorage['cookie_consent']
 *  - Если значения нет — показывает баннер через 800мс
 *  - «Принять все»        → сохраняет 'all',       добавляет body.cookies-all
 *  - «Только необходимые» → сохраняет 'essential',  добавляет body.cookies-essential
 *  - Аналитику/рекламные скрипты нужно активировать только при body.cookies-all
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'cookie_consent';
  var BANNER_ID   = 'cookie-banner';

  /* Восстановить класс при повторной загрузке */
  var stored = localStorage.getItem(STORAGE_KEY);
  if (stored === 'all') {
    document.documentElement.classList.add('cookies-all');
  } else if (stored === 'essential') {
    document.documentElement.classList.add('cookies-essential');
  }

  function setBanner(value) {
    localStorage.setItem(STORAGE_KEY, value);
    document.documentElement.classList.remove('cookies-all', 'cookies-essential');
    document.documentElement.classList.add('cookies-' + value);
    hideBanner();
  }

  function hideBanner() {
    var el = document.getElementById(BANNER_ID);
    if (!el) return;
    el.classList.remove('is-visible');
    setTimeout(function () { el.style.display = 'none'; }, 400);
  }

  function showBanner() {
    var el = document.getElementById(BANNER_ID);
    if (!el) return;
    el.style.display = '';
    /* rAF нужен чтобы CSS transition сработал */
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        el.classList.add('is-visible');
      });
    });
  }

  /* Навешиваем обработчики после DOMContentLoaded */
  document.addEventListener('DOMContentLoaded', function () {
    var acceptBtn    = document.getElementById('cookie-accept-all');
    var essentialBtn = document.getElementById('cookie-accept-essential');

    if (acceptBtn) {
      acceptBtn.addEventListener('click', function () { setBanner('all'); });
    }
    if (essentialBtn) {
      essentialBtn.addEventListener('click', function () { setBanner('essential'); });
    }

    /* Показываем баннер только если согласие ещё не давалось */
    if (!localStorage.getItem(STORAGE_KEY)) {
      setTimeout(showBanner, 800);
    }
  });
})();
