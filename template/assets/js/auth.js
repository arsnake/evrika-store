/* ============================================================
   AUTH.JS — Вход и Регистрация
   ============================================================ */

(function () {
  'use strict';

  /* ── Утилиты ─────────────────────────────────────────────── */
  function $(id) { return document.getElementById(id); }
  function setErr(el, msg) {
    if (!el) return;
    el.textContent = msg;
    if (el.previousElementSibling && el.previousElementSibling.classList.contains('auth-input')) {
      el.previousElementSibling.classList.toggle('is-err', !!msg);
    }
    // для auth-input-wrap
    var wrap = el.previousElementSibling;
    if (wrap && wrap.classList.contains('auth-input-wrap')) {
      var inp = wrap.querySelector('.auth-input');
      if (inp) inp.classList.toggle('is-err', !!msg);
    }
  }
  function clearErr(id) { setErr($(id), ''); }
  function showFormErr(errId, textId, msg) {
    var el = $(errId); var te = $(textId);
    if (!el) return;
    if (msg) { if (te) te.textContent = msg; el.style.display = 'flex'; }
    else { el.style.display = 'none'; }
  }

  /* ── Переключение видимости пароля ────────────────────────── */
  function initEye(btnId, inputId) {
    var btn = $(btnId); var input = $(inputId);
    if (!btn || !input) return;
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? 'Скрыть пароль' : 'Показать пароль');
      // иконка «закрытый глаз»
      btn.querySelector('svg').innerHTML = show
        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    });
  }

  /* ── Чекбокс — визуал через CSS :checked ──────────────────── */
  // Чекбоксы styled через CSS, кликаем по label → input меняется автоматически
  // Но input hidden (opacity:0) — label кликает нативно через htmlFor или wrap

  /* =====================================================
     СТРАНИЦА ВХОДА
     ===================================================== */
  var loginForm = $('loginForm');
  if (loginForm) {

    /* Вкладки */
    var tabs = document.querySelectorAll('.auth-tab');
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        tabs.forEach(function (t) { t.classList.remove('auth-tab--active'); });
        tab.classList.add('auth-tab--active');
        var target = tab.dataset.tab;
        document.querySelectorAll('.auth-tab-panel').forEach(function (p) {
          p.classList.add('auth-tab-panel--hidden');
        });
        var panel = target === 'password' ? $('panelPassword') : $('panelCode');
        if (panel) panel.classList.remove('auth-tab-panel--hidden');
        showFormErr('formError', 'formErrorText', '');
      });
    });

    /* Глаз для пароля */
    initEye('eyePassword', 'loginPassword');

    /* Вход по коду — таймер повторной отправки */
    var resendTimer = null;
    var codeSent = false;

    function startResendTimer() {
      var btn = $('resendBtn'); var timerEl = $('resendTimer');
      if (!btn || !timerEl) return;
      btn.disabled = true;
      var secs = 60;
      timerEl.textContent = '(' + secs + ')';
      resendTimer = setInterval(function () {
        secs--;
        if (secs <= 0) {
          clearInterval(resendTimer);
          btn.disabled = false;
          timerEl.textContent = '';
        } else {
          timerEl.textContent = '(' + secs + ')';
        }
      }, 1000);
    }

    var resendBtn = $('resendBtn');
    if (resendBtn) {
      resendBtn.addEventListener('click', function () {
        var emailEl = $('codeEmail');
        if (!emailEl || !emailEl.value.trim()) return;
        startResendTimer();
      });
    }

    /* Форма входа */
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var isPasswordTab = document.querySelector('.auth-tab--active') &&
                          document.querySelector('.auth-tab--active').dataset.tab === 'password';
      var valid = true;

      showFormErr('formError', 'formErrorText', '');

      if (isPasswordTab) {
        /* Валидация: пароль */
        var email = ($('loginEmail') || {}).value || '';
        var pwd   = ($('loginPassword') || {}).value || '';

        clearErr('errEmail'); clearErr('errPassword');

        if (!email.trim()) {
          setErr($('errEmail'), 'Введите email или телефон'); valid = false;
        }
        if (!pwd) {
          setErr($('errPassword'), 'Введите пароль'); valid = false;
        }
        if (valid) {
          /* Имитируем ошибку входа для демо */
          showFormErr('formError', 'formErrorText', 'Неверный email или пароль. Попробуйте ещё раз.');
        }
      } else {
        /* Вход по коду */
        var codeEmail = ($('codeEmail') || {}).value || '';
        clearErr('errCodeEmail'); clearErr('errCodeValue');

        if (!codeSent) {
          /* Первый шаг — отправить код */
          if (!codeEmail.trim() || !/\S+@\S+\.\S+/.test(codeEmail)) {
            setErr($('errCodeEmail'), 'Введите корректный email'); return;
          }
          codeSent = true;
          var wrap = $('codeInputWrap'); var hint = $('codeSentHint');
          var sentEmail = $('codeSentEmail');
          if (wrap) wrap.style.display = '';
          if (hint) hint.style.display = '';
          if (sentEmail) sentEmail.textContent = codeEmail;
          var submitBtn = $('submitBtn');
          if (submitBtn) submitBtn.textContent = 'Подтвердить код';
          startResendTimer();
        } else {
          /* Второй шаг — проверить код */
          var code = ($('codeValue') || {}).value || '';
          if (!code || code.length < 6) {
            setErr($('errCodeValue'), 'Введите 6-значный код из письма'); return;
          }
          showFormErr('formError', 'formErrorText', 'Неверный код. Проверьте письмо и попробуйте снова.');
        }
      }
    });
  }

  /* =====================================================
     СТРАНИЦА РЕГИСТРАЦИИ
     ===================================================== */
  var registerForm = $('registerForm');
  if (registerForm) {

    /* Тип клиента */
    var typeTabs = document.querySelectorAll('.reg-type-tab');
    var companySection = $('companySection');
    typeTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        typeTabs.forEach(function (t) { t.classList.remove('reg-type-tab--active'); });
        tab.classList.add('reg-type-tab--active');
        var isCompany = tab.dataset.type === 'company';
        if (companySection) companySection.style.display = isCompany ? '' : 'none';
      });
    });

    /* Глаза */
    initEye('eyeRegPwd', 'regPassword');
    initEye('eyeRegConf', 'regConfirm');

    /* Сила пароля */
    var pwdInput = $('regPassword');
    var pwdFill  = $('pwdFill');
    var pwdLabel = $('pwdLabel');
    if (pwdInput && pwdFill && pwdLabel) {
      pwdInput.addEventListener('input', function () {
        var v = pwdInput.value;
        var score = 0;
        if (v.length >= 8) score++;
        if (/[A-Z]/.test(v) || /[А-ЯЁ]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-zА-ЯЁа-яё0-9]/.test(v)) score++;

        pwdFill.className = 'pwd-strength-fill';
        if (!v) { pwdLabel.textContent = ''; return; }
        if (score <= 1) { pwdFill.classList.add('weak');   pwdLabel.textContent = 'Слабый'; }
        else if (score <= 2) { pwdFill.classList.add('medium'); pwdLabel.textContent = 'Средний'; }
        else { pwdFill.classList.add('strong'); pwdLabel.textContent = 'Надёжный'; }
      });
    }

    /* Телефон — авто-маска */
    var phoneInput = $('regPhone');
    if (phoneInput) {
      phoneInput.addEventListener('input', function () {
        var digits = phoneInput.value.replace(/\D/g, '');
        if (digits.startsWith('7') || digits.startsWith('8')) digits = digits.slice(1);
        digits = digits.slice(0, 10);
        var formatted = '+7';
        if (digits.length > 0) formatted += ' (' + digits.slice(0, 3);
        if (digits.length >= 4) formatted += ') ' + digits.slice(3, 6);
        if (digits.length >= 7) formatted += '-' + digits.slice(6, 8);
        if (digits.length >= 9) formatted += '-' + digits.slice(8, 10);
        phoneInput.value = formatted;
      });
    }

    /* Отправка формы */
    registerForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var valid = true;
      showFormErr('regFormError', 'regFormErrorText', '');

      /* Сбрасываем ошибки */
      ['errLastName','errFirstName','errRegEmail','errRegPhone',
       'errRegPassword','errRegConfirm','errAgree','errCompany','errInn','errLegalAddr']
        .forEach(clearErr);

      /* Фамилия */
      var lastName = ($('regLastName') || {}).value || '';
      if (!lastName.trim()) { setErr($('errLastName'), 'Укажите фамилию'); valid = false; }

      /* Имя */
      var firstName = ($('regFirstName') || {}).value || '';
      if (!firstName.trim()) { setErr($('errFirstName'), 'Укажите имя'); valid = false; }

      /* Юрлицо */
      var activeType = document.querySelector('.reg-type-tab--active');
      if (activeType && activeType.dataset.type === 'company') {
        var company = ($('regCompany') || {}).value || '';
        var inn     = ($('regInn') || {}).value || '';
        var laddr   = ($('regLegalAddr') || {}).value || '';
        if (!company.trim()) { setErr($('errCompany'), 'Укажите наименование'); valid = false; }
        if (!inn.trim() || !/^\d{10}(\d{2})?$/.test(inn.trim())) {
          setErr($('errInn'), 'ИНН должен содержать 10 или 12 цифр'); valid = false;
        }
        if (!laddr.trim()) { setErr($('errLegalAddr'), 'Укажите юридический адрес'); valid = false; }
      }

      /* Email */
      var email = ($('regEmail') || {}).value || '';
      if (!email.trim() || !/\S+@\S+\.\S+/.test(email)) {
        setErr($('errRegEmail'), 'Введите корректный email'); valid = false;
      }

      /* Телефон */
      var phone = ($('regPhone') || {}).value || '';
      var phoneDigits = phone.replace(/\D/g, '');
      if (phoneDigits.length < 11) {
        setErr($('errRegPhone'), 'Введите корректный телефон'); valid = false;
      }

      /* Пароль */
      var pwd  = ($('regPassword') || {}).value || '';
      var conf = ($('regConfirm') || {}).value || '';
      if (pwd.length < 8) {
        setErr($('errRegPassword'), 'Минимум 8 символов'); valid = false;
      }
      if (pwd && conf && pwd !== conf) {
        setErr($('errRegConfirm'), 'Пароли не совпадают'); valid = false;
      }

      /* Согласие */
      var agreeEl = $('agreeTerms');
      if (agreeEl && !agreeEl.checked) {
        setErr($('errAgree'), 'Необходимо принять условия'); valid = false;
      }

      if (!valid) {
        showFormErr('regFormError', 'regFormErrorText', 'Пожалуйста, исправьте ошибки в форме');
        /* Скролл к первой ошибке */
        var firstErr = document.querySelector('.auth-field-err:not(:empty)');
        if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      /* Успех — заглушка */
      window.location.href = 'login.html?registered=1';
    });
  }

  /* =====================================================
     СТРАНИЦА ВОССТАНОВЛЕНИЯ ПАРОЛЯ
     ===================================================== */
  var forgotForm = $('forgotForm');
  if (forgotForm) {
    forgotForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = ($('forgotEmail') || {}).value || '';
      var errEl = $('errForgotEmail');
      setErr(errEl, '');
      showFormErr('forgotFormErr', 'forgotFormErrText', '');

      if (!email.trim() || !/\S+@\S+\.\S+/.test(email)) {
        setErr(errEl, 'Введите корректный email'); return;
      }
      /* Показываем состояние "отправлено" */
      var sentEl = $('sentToEmail');
      if (sentEl) sentEl.textContent = email;
      var stepEmail = $('stepEmail'); var stepSent = $('stepSent');
      if (stepEmail) stepEmail.style.display = 'none';
      if (stepSent)  stepSent.style.display  = '';
    });

    var retryBtn = $('retryBtn');
    if (retryBtn) {
      retryBtn.addEventListener('click', function () {
        var stepEmail = $('stepEmail'); var stepSent = $('stepSent');
        if (stepEmail) stepEmail.style.display = '';
        if (stepSent)  stepSent.style.display  = 'none';
        setTimeout(function () {
          var inp = $('forgotEmail'); if (inp) inp.focus();
        }, 50);
      });
    }
  }

  /* Страница сброса пароля (шаг 3 — если пришли по ссылке из письма) */
  var resetForm = $('resetForm');
  if (resetForm) {
    /* Показываем шаг 3 если есть ?token= в URL (демо) */
    if (window.location.search.indexOf('token=') !== -1) {
      var s2 = $('stepEmail'); var s3 = $('stepReset');
      if (s2) s2.style.display = 'none';
      if (s3) s3.style.display = '';
    }

    initEye('eyeNew', 'newPassword');
    initEye('eyeConfirm', 'confirmPassword');

    /* Сила пароля */
    var rPwdInput = $('newPassword');
    var rPwdFill  = $('resetPwdFill');
    var rPwdLabel = $('resetPwdLabel');
    if (rPwdInput && rPwdFill && rPwdLabel) {
      rPwdInput.addEventListener('input', function () {
        var v = rPwdInput.value;
        var score = 0;
        if (v.length >= 8) score++;
        if (/[A-Z]/.test(v) || /[А-ЯЁ]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-zА-ЯЁа-яё0-9]/.test(v)) score++;
        rPwdFill.className = 'pwd-strength-fill';
        if (!v) { rPwdLabel.textContent = ''; return; }
        if (score <= 1) { rPwdFill.classList.add('weak');   rPwdLabel.textContent = 'Слабый'; }
        else if (score <= 2) { rPwdFill.classList.add('medium'); rPwdLabel.textContent = 'Средний'; }
        else { rPwdFill.classList.add('strong'); rPwdLabel.textContent = 'Надёжный'; }
      });
    }

    resetForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var pwd  = ($('newPassword') || {}).value || '';
      var conf = ($('confirmPassword') || {}).value || '';
      setErr($('errNewPassword'), '');
      setErr($('errConfirmPassword'), '');
      var valid = true;
      if (pwd.length < 8) { setErr($('errNewPassword'), 'Минимум 8 символов'); valid = false; }
      if (pwd && conf && pwd !== conf) { setErr($('errConfirmPassword'), 'Пароли не совпадают'); valid = false; }
      if (valid) window.location.href = 'login.html?reset=1';
    });
  }

})();
