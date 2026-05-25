/* =============================================
   CHECKOUT PAGE JS — checkout/checkout
   Эврика B2B Канцелярский магазин
   ============================================= */

/* =============================================
   1. УПРАВЛЕНИЕ ШАГАМИ
   ============================================= */
var currentStep = 2;

/* Шаг 3 (адрес) пропускается при самовывозе */
function isPickup() {
  var sel = document.querySelector('input[name="deliveryMethod"]:checked');
  return sel && sel.value === 'pickup';
}

function goToStep(n) {
  // Визуально пропускаем шаг 3 при самовывозе: помечаем как skipped
  var addressStep = document.getElementById('stepAddress');
  var skipAddress = isPickup();

  document.querySelectorAll('.co-step').forEach(function(step) {
    var sn = +step.dataset.step;
    step.classList.remove('co-step--done', 'co-step--active', 'co-step--pending', 'co-step--skipped');

    if (sn === 3 && skipAddress && n > 2) {
      // Шаг адреса — пропущен
      step.classList.add('co-step--skipped');
    } else if (sn < n) {
      step.classList.add('co-step--done');
    } else if (sn === n) {
      step.classList.add('co-step--active');
    } else {
      step.classList.add('co-step--pending');
    }
  });

  // Update progress bar bubbles
  document.querySelectorAll('.co-prog-step').forEach(function(ps) {
    var sn = +ps.dataset.step;
    ps.classList.remove('co-prog--done', 'co-prog--active', 'co-prog--pending');
    if (sn < n || (sn === 3 && skipAddress && n > 2)) {
      ps.classList.add('co-prog--done');
      ps.querySelector('.co-prog-bubble').innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    } else if (sn === n) {
      ps.classList.add('co-prog--active');
      ps.querySelector('.co-prog-bubble').textContent = sn;
    } else {
      ps.classList.add('co-prog--pending');
      ps.querySelector('.co-prog-bubble').textContent = sn;
    }
  });

  // Update progress lines
  document.querySelectorAll('.co-prog-line').forEach(function(line, idx) {
    line.classList.toggle('co-prog-line--done', idx + 2 <= n);
  });

  currentStep = n;

  var activeStep = document.querySelector('.co-step--active');
  if (activeStep) {
    setTimeout(function() {
      activeStep.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 50);
  }
}

function nextStep() {
  if (currentStep >= 5) return;
  collectStepSummary(currentStep);

  // Если выбран самовывоз и мы на шаге 2 — пропустить шаг 3 (адрес)
  if (currentStep === 2 && isPickup()) {
    // Пометить шаг адреса как skipped в confirm-блоке
    var confirmAddress = document.getElementById('confirmAddress');
    if (confirmAddress) confirmAddress.textContent = 'Самовывоз — адрес не требуется';
    goToStep(4);
    return;
  }

  goToStep(currentStep + 1);
}

function editStep(n) {
  goToStep(n);
}

/* =============================================
   2. СБОР ДАННЫХ ШАГОВ В SUMMARY
   ============================================= */
function collectStepSummary(step) {
  // Шаг 2 — Способ доставки
  if (step === 2) {
    var selected = document.querySelector('input[name="deliveryMethod"]:checked');
    var summaryEl = document.getElementById('step2Summary');
    if (summaryEl && selected) {
      var nameEl = selected.closest('.delivery-option').querySelector('.delivery-option-name');
      summaryEl.textContent = nameEl ? nameEl.textContent : '';
    }
    var confirmDelivery = document.getElementById('confirmDelivery');
    if (confirmDelivery && selected) {
      var nameEl2 = selected.closest('.delivery-option').querySelector('.delivery-option-name');
      if (nameEl2) confirmDelivery.textContent = nameEl2.textContent;
    }
    // Обновить подзаголовок confirmDelivery
    var confirmDeliverySub = document.querySelector('#confirmDelivery + .co-confirm-sub');
    if (confirmDeliverySub && selected) {
      var descEl = selected.closest('.delivery-option').querySelector('.delivery-option-desc');
      if (descEl) confirmDeliverySub.textContent = descEl.textContent.replace(/\n/g, ' ');
    }
    // Sidebar delivery price
    var summaryDelivery = document.getElementById('summaryDelivery');
    if (summaryDelivery && selected) {
      var priceEl = selected.closest('.delivery-option').querySelector('.delivery-option-price');
      if (priceEl) {
        summaryDelivery.textContent = priceEl.textContent;
        summaryDelivery.style.color = priceEl.classList.contains('free') ? 'var(--primary)' : '';
        summaryDelivery.style.fontStyle = priceEl.classList.contains('free') ? '' : 'italic';
        summaryDelivery.style.fontWeight = priceEl.classList.contains('free') ? '600' : '';
      }
    }
  }

  // Шаг 3 — Адрес доставки
  if (step === 3) {
    var fname  = (document.getElementById('coFirstName')  || {}).value || '';
    var lname  = (document.getElementById('coLastName')   || {}).value || '';
    var city   = (document.getElementById('coCity')       || {}).value || '';
    var summaryEl = document.getElementById('step3Summary');
    if (summaryEl) {
      summaryEl.textContent = [lname, fname].filter(Boolean).join(' ')
        + (city ? ' · ' + city : '');
    }
    // Confirm: получатель
    var mname  = (document.getElementById('coMiddleName') || {}).value || '';
    var confirmRecipient = document.getElementById('confirmRecipient');
    if (confirmRecipient) {
      confirmRecipient.textContent = [lname, fname, mname].filter(Boolean).join(' ')
        || 'Иванов Иван Иванович';
    }
    var phone  = (document.getElementById('coPhone') || {}).value || '';
    var email  = (document.getElementById('coEmail') || {}).value || '';
    var confirmContact = document.getElementById('confirmContact');
    if (confirmContact) {
      confirmContact.textContent = [phone, email].filter(Boolean).join(' · ')
        || '+7 (912) 345-67-89 · ivan@company.ru';
    }
    // Confirm: адрес
    var addr = (document.getElementById('coAddress') || {}).value || '';
    var zip  = (document.getElementById('coZip')     || {}).value || '';
    var confirmAddress = document.getElementById('confirmAddress');
    if (confirmAddress && (addr || city || zip)) {
      confirmAddress.textContent = [zip, city, addr].filter(Boolean).join(', ');
    }
  }

  // Шаг 4 — Оплата
  if (step === 4) {
    var selected = document.querySelector('input[name="paymentMethod"]:checked');
    var summaryEl = document.getElementById('step4Summary');
    if (summaryEl && selected) {
      var nameEl = selected.closest('.payment-option').querySelector('.payment-option-name');
      summaryEl.textContent = nameEl ? nameEl.textContent : '';
    }
    var confirmPayment = document.getElementById('confirmPayment');
    if (confirmPayment && selected) {
      var nameEl2 = selected.closest('.payment-option').querySelector('.payment-option-name');
      if (nameEl2) confirmPayment.textContent = nameEl2.textContent;
    }
  }
}

/* =============================================
   3. КЛИКАБЕЛЬНОСТЬ ВЫПОЛНЕННЫХ ШАГОВ
   ============================================= */
document.addEventListener('click', function(e) {
  var head = e.target.closest('.co-step-head');
  if (!head) return;
  var step = head.closest('.co-step');
  if (!step || !step.classList.contains('co-step--done')) return;
  var n = +step.dataset.step;
  goToStep(n);
});

/* =============================================
   4. СВОДКА — ПОКАЗАТЬ / СКРЫТЬ ТОВАРЫ
   ============================================= */
(function initSummaryToggle() {
  var toggle   = document.getElementById('summaryToggle');
  var list     = document.getElementById('summaryItemsList');
  var iconEl   = document.getElementById('summaryToggleIcon');
  if (!toggle || !list) return;

  toggle.addEventListener('click', function() {
    var isOpen = !list.classList.contains('collapsed');
    list.classList.toggle('collapsed', isOpen);
    toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    if (iconEl) iconEl.style.transform = isOpen ? 'rotate(180deg)' : '';
  });
})();

/* =============================================
   5. ЧЕКБОКС СОГЛАСИЯ — кастомная отрисовка
   ============================================= */
(function initAgreeCheckbox() {
  var cb  = document.getElementById('coAgree');
  var box = document.getElementById('coAgreeBox');
  if (!cb || !box) return;

  function renderBox() {
    if (cb.checked) {
      box.style.background   = 'var(--primary)';
      box.style.borderColor  = 'var(--primary)';
      box.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" width="10" height="10"><path d="M5 13l4 4L19 7"/></svg>';
    } else {
      box.style.background  = '';
      box.style.borderColor = '';
      box.innerHTML = '';
    }
  }

  // Click on visible box or label triggers native checkbox
  box.addEventListener('click', function() {
    cb.checked = !cb.checked;
    renderBox();
  });

  cb.addEventListener('change', renderBox);
})();

/* =============================================
   6. КНОПКА ПОДТВЕРДИТЬ ЗАКАЗ
   ============================================= */
(function initConfirmBtn() {
  var btn = document.getElementById('coConfirmBtn');
  if (!btn) return;

  btn.addEventListener('click', function() {
    var agree = document.getElementById('coAgree');
    if (!agree || !agree.checked) {
      var agreeWrap = document.querySelector('.co-agree-wrap');
      if (agreeWrap) {
        agreeWrap.style.outline = '2px solid var(--accent)';
        agreeWrap.style.borderRadius = 'var(--radius-s)';
        setTimeout(function() {
          agreeWrap.style.outline = '';
        }, 2000);
      }
      return;
    }
    // Redirect to success page (prototype)
    window.location.href = 'success.html';
  });
})();

/* =============================================
   7. ПОДСВЕТКА ПОЛЕЙ ПРИ ДОСТАВКЕ
   ============================================= */
(function initDeliveryOptions() {
  document.querySelectorAll('input[name="deliveryMethod"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      // Visual update handled by CSS :checked pseudo-class
      // Just update delivery summary line in sidebar
      var priceEl = radio.closest('.delivery-option').querySelector('.delivery-option-price');
      var summaryDelivery = document.getElementById('summaryDelivery');
      if (summaryDelivery && priceEl) {
        summaryDelivery.textContent = priceEl.textContent;
        summaryDelivery.style.fontStyle = '';
        if (priceEl.classList.contains('free')) {
          summaryDelivery.style.color = 'var(--primary)';
          summaryDelivery.style.fontWeight = '600';
        } else {
          summaryDelivery.style.color = 'var(--ink-60)';
          summaryDelivery.style.fontWeight = '';
          summaryDelivery.style.fontStyle = 'italic';
        }
      }
    });
  });
})();
