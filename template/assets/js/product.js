/* =============================================
   PRODUCT PAGE — product.js
   Эврика B2B Канцелярский магазин
   ============================================= */

'use strict';

/* =============================================
   1. GALLERY SWITCHING
   ============================================= */
(function initGallery() {
  const thumbs = document.querySelectorAll('.gallery-thumb');
  const mainPlaceholder = document.querySelector('.gallery-main-placeholder');

  thumbs.forEach(function(thumb, index) {
    thumb.addEventListener('click', function() {
      // Remove active from all
      thumbs.forEach(function(t) { t.classList.remove('active'); });
      thumb.classList.add('active');

      // Swap main image/SVG
      if (mainPlaceholder) {
        const svgContent = thumb.getAttribute('data-svg');
        if (svgContent) {
          mainPlaceholder.innerHTML = svgContent;
        }
      }

      // Track current index for lightbox
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
  var btnClose = lightbox ? lightbox.querySelector('.lightbox-close') : null;
  var btnPrev = lightbox ? lightbox.querySelector('.lightbox-prev') : null;
  var btnNext = lightbox ? lightbox.querySelector('.lightbox-next') : null;
  var galleryMain = document.querySelector('.gallery-main');
  var thumbs = document.querySelectorAll('.gallery-thumb');

  window._galleryIndex = 0;

  function getCount() {
    return thumbs.length;
  }

  function showLightboxAt(index) {
    if (!lightbox || !lightboxContent) return;
    var count = getCount();
    if (count === 0) return;
    window._galleryIndex = (index + count) % count;
    var thumb = thumbs[window._galleryIndex];
    var svgContent = thumb ? thumb.getAttribute('data-svg') : '';
    lightboxContent.innerHTML = svgContent || '<div style="color:#fff;font-size:14px">Изображение ' + (window._galleryIndex + 1) + '</div>';
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  // Open on main image click
  if (galleryMain) {
    galleryMain.addEventListener('click', function() {
      showLightboxAt(window._galleryIndex || 0);
    });
  }

  // Close button
  if (btnClose) {
    btnClose.addEventListener('click', closeLightbox);
  }

  // Prev / next
  if (btnPrev) {
    btnPrev.addEventListener('click', function(e) {
      e.stopPropagation();
      showLightboxAt((window._galleryIndex || 0) - 1);
    });
  }
  if (btnNext) {
    btnNext.addEventListener('click', function(e) {
      e.stopPropagation();
      showLightboxAt((window._galleryIndex || 0) + 1);
    });
  }

  // Click outside content
  if (lightbox) {
    lightbox.addEventListener('click', function(e) {
      if (e.target === lightbox) {
        closeLightbox();
      }
    });
  }

  // Keyboard navigation
  document.addEventListener('keydown', function(e) {
    if (!lightbox || !lightbox.classList.contains('open')) return;
    if (e.key === 'Escape') {
      closeLightbox();
    } else if (e.key === 'ArrowLeft') {
      showLightboxAt((window._galleryIndex || 0) - 1);
    } else if (e.key === 'ArrowRight') {
      showLightboxAt((window._galleryIndex || 0) + 1);
    }
  });
})();


/* =============================================
   3. SPECS EXPAND / COLLAPSE
   ============================================= */
(function initSpecsToggle() {
  var btn = document.querySelector('.specs-more-btn');
  if (!btn) return;

  btn.addEventListener('click', function() {
    var hiddenRows = document.querySelectorAll('.specs-hidden');
    var isOpen = hiddenRows.length > 0 && hiddenRows[0].classList.contains('open');

    hiddenRows.forEach(function(row) {
      row.classList.toggle('open', !isOpen);
    });

    btn.textContent = isOpen ? 'Все характеристики ↓' : 'Свернуть ↑';
  });
})();


/* =============================================
   4. TABS
   ============================================= */
(function initTabs() {
  var tabBtns = document.querySelectorAll('.tab-btn');
  var tabPanels = document.querySelectorAll('.tab-panel');

  tabBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var target = btn.getAttribute('data-tab');

      tabBtns.forEach(function(b) { b.classList.remove('active'); });
      tabPanels.forEach(function(p) { p.classList.remove('active'); });

      btn.classList.add('active');
      var panel = document.getElementById('tab-' + target);
      if (panel) {
        panel.classList.add('active');
      }
    });
  });
})();


/* =============================================
   5. QUANTITY CONTROL
   ============================================= */
(function initQtyControl() {
  var qtyInput = document.querySelector('.qty-input');
  var btnPlus = document.querySelector('.qty-btn.plus');
  var btnMinus = document.querySelector('.qty-btn.minus');

  if (!qtyInput) return;

  function getVal() {
    var v = parseInt(qtyInput.value, 10);
    return isNaN(v) || v < 1 ? 1 : v;
  }

  if (btnPlus) {
    btnPlus.addEventListener('click', function() {
      qtyInput.value = getVal() + 1;
    });
  }

  if (btnMinus) {
    btnMinus.addEventListener('click', function() {
      var v = getVal();
      qtyInput.value = v > 1 ? v - 1 : 1;
    });
  }

  qtyInput.addEventListener('blur', function() {
    var v = parseInt(qtyInput.value, 10);
    if (isNaN(v) || v < 1) {
      qtyInput.value = 1;
    } else {
      qtyInput.value = Math.floor(v);
    }
  });

  qtyInput.addEventListener('keypress', function(e) {
    if (!/[0-9]/.test(e.key)) {
      e.preventDefault();
    }
  });
})();


/* =============================================
   6. ADD TO CART BUTTON
   ============================================= */
(function initCartButton() {
  var btnCart = document.querySelector('.btn-cart');
  if (!btnCart) return;

  var originalHTML = btnCart.innerHTML;
  var timeout = null;

  btnCart.addEventListener('click', function() {
    if (btnCart.classList.contains('added')) return;

    btnCart.classList.add('added');
    btnCart.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Добавлено';

    // Update header badge
    var badge = document.querySelector('.h-action.cart .h-badge');
    var mobBadge = document.querySelector('.mob-nav-badge');
    if (badge) {
      var count = parseInt(badge.textContent, 10) || 0;
      badge.textContent = count + 1;
      badge.style.transform = 'scale(1.4)';
      setTimeout(function() { badge.style.transform = ''; }, 300);
    }
    if (mobBadge) {
      var mCount = parseInt(mobBadge.textContent, 10) || 0;
      mobBadge.textContent = mCount + 1;
    }

    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(function() {
      btnCart.classList.remove('added');
      btnCart.innerHTML = originalHTML;
    }, 2000);
  });
})();


/* =============================================
   7. COPY SKU ON CLICK
   ============================================= */
(function initSkuCopy() {
  var skuValues = document.querySelectorAll('.sku-value');

  skuValues.forEach(function(el) {
    el.style.position = 'relative';

    // Create tooltip
    var tooltip = document.createElement('span');
    tooltip.className = 'sku-tooltip';
    tooltip.textContent = 'Скопировано!';
    el.appendChild(tooltip);

    el.addEventListener('click', function() {
      var text = el.firstChild ? el.firstChild.textContent.trim() : el.textContent.trim();

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
          showTooltip();
        }).catch(function() {
          fallbackCopy(text);
          showTooltip();
        });
      } else {
        fallbackCopy(text);
        showTooltip();
      }

      function showTooltip() {
        tooltip.classList.add('show');
        setTimeout(function() {
          tooltip.classList.remove('show');
        }, 1500);
      }

      function fallbackCopy(str) {
        var ta = document.createElement('textarea');
        ta.value = str;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (err) {}
        document.body.removeChild(ta);
      }
    });
  });
})();


/* =============================================
   8. RELATED SLIDER ARROWS
   ============================================= */
(function initSliders() {
  var sliderWraps = document.querySelectorAll('.related-slider-wrap');

  sliderWraps.forEach(function(wrap) {
    var slider = wrap.querySelector('.related-slider');
    var btnPrev = wrap.querySelector('.slider-prev');
    var btnNext = wrap.querySelector('.slider-next');

    if (!slider) return;

    if (btnPrev) {
      btnPrev.addEventListener('click', function() {
        slider.scrollBy({ left: -220, behavior: 'smooth' });
      });
    }
    if (btnNext) {
      btnNext.addEventListener('click', function() {
        slider.scrollBy({ left: 220, behavior: 'smooth' });
      });
    }
  });
})();



/* =============================================
   STAR RATING (reviews tab)
   ============================================= */
(function initStarRating() {
  var stars = document.querySelectorAll('.star-btn');
  var selectedRating = 0;

  stars.forEach(function(star, index) {
    star.addEventListener('mouseover', function() {
      stars.forEach(function(s, i) {
        s.classList.toggle('active', i <= index);
      });
    });

    star.addEventListener('mouseout', function() {
      stars.forEach(function(s, i) {
        s.classList.toggle('active', i < selectedRating);
      });
    });

    star.addEventListener('click', function() {
      selectedRating = index + 1;
      stars.forEach(function(s, i) {
        s.classList.toggle('active', i < selectedRating);
      });
    });
  });
})();


/* =============================================
   SCROLL-TOP VISIBILITY
   ============================================= */
(function initScrollTop() {
  var btn = document.querySelector('.scroll-top');
  if (!btn) return;
  window.addEventListener('scroll', function() {
    btn.style.opacity = window.scrollY > 300 ? '1' : '0';
    btn.style.pointerEvents = window.scrollY > 300 ? 'auto' : 'none';
  }, { passive: true });
  btn.style.opacity = '0';
  btn.style.transition = 'opacity .3s';
  btn.style.pointerEvents = 'none';
})();
