const catalogData = [
  {
    icon: '<path d="M12 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
    name: 'Бренды', count: null,
    chips: [],
    sub: []
  },
  {
    icon: '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
    name: 'Школьный текстиль', count: 1240,
    chips: ['Клей-карандаш','Ручки гелевые','Тетради 40–48 л.','Ранцы'],
    sub: [
      {title:'Мешки для обуви, сумки-шопперы', items:[['Мешки для обуви',167],['Сумки-шопперы',56],['Фартуки для уроков труда',23]]},
      {title:'Папки школьные', items:[['Папки для тетрадей и труда',236],['Папки-сумки с ручками',184],['Пеналы мягкие',396],['Пеналы с отделениями',238]]},
      {title:'Рюкзаки, ранцы, сумки', items:[['Ранцы',328],['Рюкзаки',406],['Сумки спортивные',7],['Рюкзаки городские',156]]},
    ]
  },
  {
    icon: '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/><circle cx="11" cy="11" r="2"/>',
    name: 'Письменные товары, черчение', count: 3210,
    chips: ['Ручки шариковые','Ручки гелевые','Карандаши','Маркеры'],
    sub: [
      {title:'Ручки', items:[['Шариковые ручки',620],['Гелевые ручки',480],['Перьевые ручки',45],['Автоматические ручки',210]]},
      {title:'Карандаши', items:[['Простые карандаши',380],['Цветные карандаши',290],['Механические карандаши',110],['Пастели',95]]},
      {title:'Маркеры и корректоры', items:[['Маркеры перманентные',245],['Текстовыделители',180],['Корректоры жидкие',120],['Корректоры ленточные',85]]},
    ]
  },
  {
    icon: '<rect x="2" y="3" width="20" height="18" rx="2"/><line x1="8" y1="21" x2="8" y2="3"/><line x1="16" y1="21" x2="16" y2="3"/><line x1="2" y1="12" x2="22" y2="12"/>',
    name: 'Бумага для офисной техники', count: 156,
    chips: ['А4','А3','Цветная бумага','Фотобумага'],
    sub: [
      {title:'Бумага А4', items:[['Офисная 80 г/м²',45],['Мелованная',28],['Цветная',34],['Для лазерной печати',23]]},
      {title:'Специальная бумага', items:[['Фотобумага матовая',18],['Фотобумага глянцевая',24],['Самоклеящаяся',32],['Термо-трансфер',12]]},
      {title:'Форматы А3 и другие', items:[['Бумага А3',22],['Бумага А5',15],['Рулонная бумага',8],['Плоттерная бумага',6]]},
    ]
  },
  {
    icon: '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    name: 'Бумажная продукция для офиса', count: 890,
    chips: ['Блокноты','Ежедневники','Бумага для заметок'],
    sub: [
      {title:'Блокноты и тетради', items:[['Блокноты А4',145],['Блокноты А5',230],['Ежедневники датированные',88],['Ежедневники недатированные',72]]},
      {title:'Бумага для заметок', items:[['Стикеры самоклеящиеся',320],['Блоки для записей',180],['Карточки-закладки',95],['Флипчарт блоки',24]]},
      {title:'Папки и файлы', items:[['Папки-регистраторы',210],['Папки-скоросшиватели',180],['Файлы А4',440],['Разделители',65]]},
    ]
  },
  {
    icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
    name: 'Бумажная продукция для школы', count: 760,
    chips: ['Тетради','Прописи','Дневники'],
    sub: [
      {title:'Тетради', items:[['Тетради 12 л.',245],['Тетради 18 л.',180],['Тетради 24 л.',156],['Тетради 48 л.',88]]},
      {title:'Учебные принадлежности', items:[['Дневники школьные',145],['Прописи',98],['Альбомы для рисования',210],['Нотные тетради',45]]},
      {title:'Обложки и закладки', items:[['Обложки для тетрадей',320],['Обложки для учебников',245],['Закладки-линейки',85],['Стикеры-закладки',124]]},
    ]
  },
  {
    icon: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>',
    name: 'Бытовая и профессиональная химия', count: 430,
    chips: [],
    sub: [
      {title:'Клеи и адгезивы', items:[['Клей-карандаш',89],['Клей ПВА',45],['Супер-клей',32],['Двусторонний скотч',68]]},
      {title:'Чистящие средства', items:[['Спреи для оргтехники',34],['Влажные салфетки',56],['Сжатый воздух',18],['Антибактериальные средства',24]]},
      {title:'Лаки и аэрозоли', items:[['Лаки для волос для маркировки',12],['Аэрозоли-метки',8],['Аэрозоли-краски',28],['Грунты',15]]},
    ]
  },
  {
    icon: '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
    name: 'Офисные принадлежности', count: 1800,
    chips: ['Степлеры','Дыроколы','Ножницы','Линейки'],
    sub: [
      {title:'Инструменты для документов', items:[['Степлеры',145],['Дыроколы',98],['Антистепплеры',34],['Брошюраторы',28]]},
      {title:'Мелкоофисные товары', items:[['Ножницы',210],['Линейки',178],['Ластики',245],['Точилки',135]]},
      {title:'Органайзеры', items:[['Настольные органайзеры',88],['Лотки для бумаг',120],['Подставки для ручек',96],['Визитницы',45]]},
    ]
  },
];

let activeIdx = 1;

function renderSidebar(){
  const list = document.getElementById('catList');
  list.innerHTML = catalogData.map((c,i)=>`
    <div class="cat-item ${i===activeIdx?'active':''}" onclick="selectCat(${i})">
      <div class="cat-item-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">${c.icon}</svg>
      </div>
      <span class="cat-item-name">${c.name}</span>
      ${c.count?`<span class="cat-item-count">${c.count}</span>`:''}
    </div>
  `).join('');
}

function renderContent(idx){
  const c = catalogData[idx];
  const chips = document.getElementById('catChips');
  chips.innerHTML = c.chips.map(ch=>`<div class="cat-chip">${ch}</div>`).join('');

  const grid = document.getElementById('catSubGrid');
  grid.innerHTML = c.sub.map(g=>`
    <div class="cat-sub-group">
      <h4>${g.title}</h4>
      <ul>${g.items.map(([name,cnt])=>`<li><a href="#"><span>${name}</span><span class="cnt">${cnt}</span></a></li>`).join('')}</ul>
    </div>
  `).join('');
}

function selectCat(idx){
  activeIdx=idx;
  renderSidebar();
  renderContent(idx);
}

function openCatalog(){
  document.getElementById('catalogModal').classList.add('open');
  document.body.style.overflow='hidden';
  renderSidebar();
  renderContent(activeIdx);
}

function closeCatalog(){
  const modal=document.getElementById('catalogModal');
  const overlay=document.getElementById('catalogModal');
  modal.classList.remove('open');
  document.querySelector('.catalog-modal').classList.remove('fullscreen');
  overlay.classList.remove('fullscreen-mode');
  isFullscreen=false;
  updateFsIcon();
  document.body.style.overflow='';
}

let isFullscreen=false;
function toggleFullscreen(){
  isFullscreen=!isFullscreen;
  const modal=document.querySelector('.catalog-modal');
  const overlay=document.getElementById('catalogModal');
  modal.classList.toggle('fullscreen',isFullscreen);
  overlay.classList.toggle('fullscreen-mode',isFullscreen);
  updateFsIcon();
}
function updateFsIcon(){
  const btn=document.getElementById('iconExpand');
  btn.innerHTML=isFullscreen
    ?'<path stroke-linecap="round" stroke-linejoin="round" d="M9 9L4 4m0 0h4m-4 0v4M15 9l5-5m0 0h-4m4 0v4M9 15l-5 5m0 0h4m-4 0v-4M15 15l5 5m0 0h-4m4 0v-4"/>'
    :'<path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5M20 8V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5M20 16v4m0 0h-4m4 0l-5-5"/>';
}

function handleOverlayClick(e) {
  if (e.target === e.currentTarget) closeCatalog();
}

document.addEventListener('keydown',e=>{if(e.key==='Escape')closeCatalog()});
(function(){
  const slider  = document.getElementById('arrivalSlider');
  if(!slider) return;
  const slides  = document.getElementById('arrivalSlides');
  const dotsEl  = document.getElementById('arrDots');
  const curEl   = document.getElementById('arrCur');
  const total   = slider.querySelectorAll('.arrival-slide').length;
  let cur = 0, timer, startX = 0, isDragging = false;

  // Создаём точки
  for(let i=0;i<total;i++){
    const d = document.createElement('button');
    d.className = 'arrival-dot' + (i===0?' active':'');
    d.addEventListener('click',()=>goTo(i));
    dotsEl.appendChild(d);
  }

  function goTo(n){
    cur = (n + total) % total;
    slides.style.transform = `translateX(-${cur*100}%)`;
    dotsEl.querySelectorAll('.arrival-dot').forEach((d,i)=>d.classList.toggle('active',i===cur));
    if(curEl) curEl.textContent = cur+1;
    resetTimer();
  }

  function resetTimer(){
    clearInterval(timer);
    timer = setInterval(()=>goTo(cur+1), 5000);
  }

  document.getElementById('arrPrev')?.addEventListener('click',()=>goTo(cur-1));
  document.getElementById('arrNext')?.addEventListener('click',()=>goTo(cur+1));

  // Свайп на мобиле
  slides.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;isDragging=true},{passive:true});
  slides.addEventListener('touchend',e=>{
    if(!isDragging) return;
    const dx = e.changedTouches[0].clientX - startX;
    if(Math.abs(dx)>40) goTo(dx<0 ? cur+1 : cur-1);
    isDragging=false;
  },{passive:true});

  resetTimer();
})();

/* ─── Карточки товаров: qty ± и «В корзину» ─── */
(function initProdCards() {
  document.querySelectorAll('.prod-card').forEach(function(card) {
    var ctrl     = card.querySelector('.prod-qty-ctrl');
    var qtyBtns  = ctrl ? ctrl.querySelectorAll('.prod-qty-btn') : [];
    var minusBtn = qtyBtns[0] || null;
    var plusBtn  = qtyBtns[1] || null;
    var input    = card.querySelector('.prod-qty-input');
    var cartBtn  = card.querySelector('.prod-cart-btn');
    var favBtn   = card.querySelector('.prod-fav-btn');

    if (minusBtn && plusBtn && input) {
      function updateMinus() { minusBtn.disabled = +input.value <= 1; }

      minusBtn.addEventListener('click', function() {
        var v = +input.value;
        if (v > 1) { input.value = v - 1; updateMinus(); }
      });
      plusBtn.addEventListener('click', function() {
        input.value = +input.value + 1;
        updateMinus();
      });
      input.addEventListener('change', function() {
        var v = parseInt(input.value, 10);
        if (isNaN(v) || v < 1) v = 1;
        input.value = v;
        updateMinus();
      });
      updateMinus();
    }

    if (cartBtn) {
      cartBtn.addEventListener('click', function() {
        var orig = cartBtn.innerHTML;
        cartBtn.classList.add('added');
        cartBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        setTimeout(function() {
          cartBtn.innerHTML = orig;
          cartBtn.classList.remove('added');
        }, 1800);
      });
    }

    if (favBtn) {
      favBtn.addEventListener('click', function() {
        favBtn.classList.toggle('active');
        var isActive = favBtn.classList.contains('active');
        favBtn.setAttribute('title', isActive ? 'Убрать из избранного' : 'В избранное');
        if (isActive) {
          favBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
        } else {
          favBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
        }
      });
    }
  });
})();

function openMobSearch(){
  const el = document.getElementById('mobSearch');
  el.classList.add('open');
  el.querySelector('input').focus();
}
function closeMobSearch(){
  document.getElementById('mobSearch').classList.remove('open');
}

/* ─── Выпадающее меню пользователя (шапка) ─── */
(function initUserDropdown() {
  var wrap = document.getElementById('hUserWrap');
  var btn  = document.getElementById('hUserBtn');
  if (!wrap || !btn) return;
  btn.addEventListener('click', function(e) { e.stopPropagation(); wrap.classList.toggle('open'); });
  document.addEventListener('click', function() { wrap.classList.remove('open'); });
})();

/* ─── Фильтр-аккордеон (каталог / поиск / бренд) ─── */
(function initFilterAccordion() {
  document.querySelectorAll('.filter-group-head').forEach(function(btn) {
    btn.addEventListener('click', function() {
      btn.closest('.filter-group').classList.toggle('open');
    });
  });
})();

/* ─── Чекбоксы фильтров → теги активных фильтров ─── */
(function initFilterTags() {
  var filtersBar = document.getElementById('activeFiltersBar');
  if (!filtersBar) return;

  function rebuildTags() {
    var checked = document.querySelectorAll('.filter-panel input[type=checkbox]:checked');
    filtersBar.innerHTML = '';

    if (checked.length === 0) {
      filtersBar.classList.remove('has-filters');
      return;
    }

    filtersBar.classList.add('has-filters');

    var lbl = document.createElement('span');
    lbl.textContent = 'Выбрано:';
    filtersBar.appendChild(lbl);

    checked.forEach(function(cb) {
      var nameEl = cb.closest('.filter-check') && cb.closest('.filter-check').querySelector('.fc-name');
      var name = nameEl ? nameEl.textContent : '';
      var tag = document.createElement('div');
      tag.className = 'active-filter-tag';
      tag.innerHTML = name + ' <button aria-label="Убрать фильтр">\u00d7</button>';
      tag.querySelector('button').addEventListener('click', function() {
        cb.checked = false;
        rebuildTags();
      });
      filtersBar.appendChild(tag);
    });

    var clearBtn = document.createElement('button');
    clearBtn.className = 'af-clear-all';
    clearBtn.textContent = 'Сбросить всё';
    clearBtn.addEventListener('click', resetFilters);
    filtersBar.appendChild(clearBtn);
  }

  function resetFilters() {
    document.querySelectorAll('.filter-panel input[type=checkbox]').forEach(function(cb) { cb.checked = false; });
    rebuildTags();
  }

  document.querySelectorAll('.filter-panel input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', rebuildTags);
  });

  var resetBtn = document.getElementById('filterReset');
  if (resetBtn) resetBtn.addEventListener('click', resetFilters);
})();

/* ─── Переключатель вида: сетка / список ─── */
(function initViewToggle() {
  var gridBtn = document.getElementById('viewGrid');
  var listBtn = document.getElementById('viewList');
  var grid    = document.getElementById('productGrid');
  var list    = document.getElementById('productList');
  if (!gridBtn || !listBtn) return;

  gridBtn.addEventListener('click', function() {
    gridBtn.classList.add('active');
    listBtn.classList.remove('active');
    if (grid) grid.style.display = '';
    if (list) list.style.display = 'none';
  });

  listBtn.addEventListener('click', function() {
    listBtn.classList.add('active');
    gridBtn.classList.remove('active');
    if (list) list.style.display = '';
    if (grid) grid.style.display = 'none';
  });
})();

/* ─── Мобильный сайдбар фильтров (bottom sheet) ─── */
(function initMobileSidebar() {
  var openBtn  = document.getElementById('mobileFilterBtn');
  var sidebar  = document.querySelector('.filter-sidebar');
  var overlay  = document.getElementById('sidebarOverlay');
  if (!openBtn || !sidebar || !overlay) return;

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  openBtn.addEventListener('click', openSidebar);
  overlay.addEventListener('click', closeSidebar);
  var closeBtn = document.getElementById('sidebarCloseBtn');
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  var applyBtn = document.getElementById('filterApply');
  if (applyBtn) applyBtn.addEventListener('click', closeSidebar);
})();

