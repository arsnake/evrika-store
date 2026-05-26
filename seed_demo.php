<?php
/**
 * Evrika Demo Seed Script v1.0
 * ─────────────────────────────────────────────────────────────────
 * Запуск: php seed_demo.php  (из C:/wamp64/www/evrika/)
 *     или http://localhost/evrika/seed_demo.php?run=1
 */

if (PHP_SAPI !== 'cli' && !isset($_GET['run'])) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Seed</title></head><body>';
    echo '<h2>Evrika Demo Seed</h2><p><a href="?run=1"><b>Запустить</b></a></p></body></html>';
    exit;
}
if (PHP_SAPI !== 'cli') {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Seed</title></head><body><pre>';
    ob_implicit_flush(true);
}

define('ROOT', __DIR__ . '/public');
require_once ROOT . '/config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) die("DB Error: " . $db->connect_error);
$db->set_charset('utf8mb4');
$p = DB_PREFIX;

// ── Helpers ──────────────────────────────────────────────────────
function ins(mysqli $db, string $sql): int {
    if (!$db->query($sql)) die("\nSQL Error: " . $db->error . "\nSQL: " . substr($sql,0,300));
    return (int)$db->insert_id;
}
function e(mysqli $db, $v): string { return $db->real_escape_string((string)$v); }
function say(string $m): void { echo $m . "\n"; flush(); }
function ean13(): string {
    $d = '460' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    $s = 0;
    for ($i=0;$i<12;$i++) $s += $d[$i] * ($i%2==0?1:3);
    return $d . ((10-($s%10))%10);
}

// ── System IDs ───────────────────────────────────────────────────
$r = $db->query("SELECT language_id FROM {$p}language WHERE code='ru-ru' LIMIT 1")->fetch_assoc();
$LANG = $r ? (int)$r['language_id'] : 1;
$r = $db->query("SELECT weight_class_id FROM {$p}weight_class_description WHERE unit IN ('g','г') LIMIT 1")->fetch_assoc();
$WC = $r ? (int)$r['weight_class_id'] : 2;
$r = $db->query("SELECT length_class_id FROM {$p}length_class_description WHERE unit IN ('mm','мм') LIMIT 1")->fetch_assoc();
$LC = $r ? (int)$r['length_class_id'] : 2;
$r = $db->query("SELECT stock_status_id FROM {$p}stock_status WHERE language_id={$LANG} LIMIT 1")->fetch_assoc();
$SS = $r ? (int)$r['stock_status_id'] : 7;
$TC = 9;
say("System IDs: lang={$LANG} wc={$WC} lc={$LC} ss={$SS}");

// ── Check duplicate ───────────────────────────────────────────────
$r = $db->query("SELECT manufacturer_id FROM {$p}manufacturer WHERE name='Berlingo' LIMIT 1")->fetch_assoc();
if ($r) { say("WARNING: Berlingo already exists (id={$r['manufacturer_id']}). Add ?reset=1 to skip check."); }

// ── Images ───────────────────────────────────────────────────────
$imgBase = ROOT . '/image/catalog/seed';
if (!is_dir($imgBase)) mkdir($imgBase, 0755, true);

$CAT_RGB = [
    'ruchki'    => [59,130,246],  'tetradi'   => [22,163,74],
    'papki'     => [245,158,11],  'bumaga'    => [100,116,139],
    'nozhnicy'  => [239,68,68],   'kley'      => [249,115,22],
    'markery'   => [168,85,247],  'hudozh'    => [236,72,153],
    'ofisnye'   => [20,184,166],  'shkolnye'  => [99,102,241],
    'slide'     => [30,41,59],
];

function makeImg(string $path, array $rgb, string $label, int $w=800, int $h=800): string {
    if (!extension_loaded('gd') || file_exists($path)) return '';
    $im = imagecreatetruecolor($w, $h);
    $bg  = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    $bg2 = imagecolorallocate($im, min(255,$rgb[0]+40), min(255,$rgb[1]+40), min(255,$rgb[2]+40));
    $wht = imagecolorallocate($im, 255,255,255);
    $drk = imagecolorallocate($im, max(0,$rgb[0]-30), max(0,$rgb[1]-30), max(0,$rgb[2]-30));
    imagefill($im, 0, 0, $bg);
    // gradient stripe
    imagefilledrectangle($im, 0, (int)($h*0.6), $w, $h, $drk);
    // label
    $font = 5;
    $fw = imagefontwidth($font); $fh = imagefontheight($font);
    $words = explode(' ', $label);
    $lines = []; $cur = '';
    $maxChars = (int)($w / $fw) - 4;
    foreach ($words as $word) {
        if (strlen($cur . ' ' . $word) > $maxChars) { if($cur) $lines[]=$cur; $cur=$word; }
        else $cur = $cur ? $cur.' '.$word : $word;
    }
    if ($cur) $lines[] = $cur;
    $totalH = count($lines)*($fh+6);
    $startY = (int)(($h - $totalH) / 2);
    foreach ($lines as $i => $line) {
        $lw = strlen($line)*$fw;
        imagestring($im, $font, (int)(($w-$lw)/2), $startY + $i*($fh+6), $line, $wht);
    }
    imagepng($im, $path);
    imagedestroy($im);
    return 'catalog/seed/' . basename($path);
}

// ── Manufacturers ─────────────────────────────────────────────────
say("\n=== MANUFACTURERS ===");
$BRANDS = [
    'Berlingo'      => ['seo'=>'berlingo',     'sort'=>1],
    'Brauberg'      => ['seo'=>'brauberg',     'sort'=>2],
    'ГАММА'         => ['seo'=>'gamma',        'sort'=>3],
    'СТАММ'         => ['seo'=>'stamm',        'sort'=>4],
    'OfficeSpace'   => ['seo'=>'officespace',  'sort'=>5],
    'Erich Krause'  => ['seo'=>'erich-krause', 'sort'=>6],
    'Hatber'        => ['seo'=>'hatber',       'sort'=>7],
    'Pilot'         => ['seo'=>'pilot',        'sort'=>8],
    'Attache'       => ['seo'=>'attache',      'sort'=>9],
    'Faber-Castell' => ['seo'=>'faber-castell','sort'=>10],
    'Канц-Эксмо'    => ['seo'=>'kanc-eksmo',   'sort'=>11],
    'Красин'        => ['seo'=>'krasin',       'sort'=>12],
];
$MID = [];
foreach ($BRANDS as $name => $b) {
    $r = $db->query("SELECT manufacturer_id FROM {$p}manufacturer WHERE name='" . e($db,$name) . "' LIMIT 1")->fetch_assoc();
    if ($r) { $MID[$name] = (int)$r['manufacturer_id']; say("  skip: {$name}"); continue; }
    $img = makeImg("{$imgBase}/brand_{$b['seo']}.png", [180,180,180], $name, 200, 80);
    $id = ins($db,"INSERT INTO {$p}manufacturer (name,image,sort_order,noindex) VALUES ('" . e($db,$name) . "','" . e($db,$img) . "',{$b['sort']},0)");
    ins($db,"INSERT INTO {$p}manufacturer_to_store (manufacturer_id,store_id) VALUES ({$id},0)");
    ins($db,"INSERT INTO {$p}seo_url (store_id,language_id,query,keyword) VALUES (0,{$LANG},'manufacturer_id={$id}','" . e($db,$b['seo']) . "')");
    $MID[$name] = $id;
    say("  + {$name} [{$id}]");
}

// ── Attribute Groups + Attributes ────────────────────────────────
say("\n=== ATTRIBUTES ===");
$ATTR_GROUPS = [
    'Основные характеристики' => ['Цвет','Материал','Страна производства'],
    'Размер и вес'            => ['Формат','Количество в упаковке','Вес, г'],
    'Технические параметры'   => ['Тип','Толщина линии, мм'],
];
$AGID = []; $AID = [];
$agSort = 1;
foreach ($ATTR_GROUPS as $gname => $attrs) {
    $r = $db->query("SELECT ag.attribute_group_id FROM {$p}attribute_group ag JOIN {$p}attribute_group_description agd USING(attribute_group_id) WHERE agd.name='" . e($db,$gname) . "' AND agd.language_id={$LANG} LIMIT 1")->fetch_assoc();
    if ($r) { $gid = (int)$r['attribute_group_id']; }
    else {
        $gid = ins($db,"INSERT INTO {$p}attribute_group (sort_order) VALUES ({$agSort})");
        ins($db,"INSERT INTO {$p}attribute_group_description (attribute_group_id,language_id,name) VALUES ({$gid},{$LANG},'" . e($db,$gname) . "')");
        say("  + group: {$gname} [{$gid}]");
    }
    $AGID[$gname] = $gid;
    $aSort = 1;
    foreach ($attrs as $aname) {
        $r = $db->query("SELECT a.attribute_id FROM {$p}attribute a JOIN {$p}attribute_description ad USING(attribute_id) WHERE ad.name='" . e($db,$aname) . "' AND ad.language_id={$LANG} LIMIT 1")->fetch_assoc();
        if ($r) { $AID[$aname] = (int)$r['attribute_id']; }
        else {
            $aid = ins($db,"INSERT INTO {$p}attribute (attribute_group_id,sort_order) VALUES ({$gid},{$aSort})");
            ins($db,"INSERT INTO {$p}attribute_description (attribute_id,language_id,name) VALUES ({$aid},{$LANG},'" . e($db,$aname) . "')");
            $AID[$aname] = $aid;
            say("  + attr: {$aname} [{$aid}]");
        }
        $aSort++;
    }
    $agSort++;
}

// ── Options ───────────────────────────────────────────────────────
say("\n=== OPTIONS ===");
$OPT = [
    'Цвет' => ['type'=>'radio','values'=>['Синий','Красный','Чёрный','Зелёный','Фиолетовый']],
    'Фасовка' => ['type'=>'select','values'=>['1 шт','10 шт','20 шт','50 шт']],
];
$OPTID = []; $OVAL = [];
foreach ($OPT as $oname => $opt) {
    $r = $db->query("SELECT o.option_id FROM {$p}option o JOIN {$p}option_description od USING(option_id) WHERE od.name='" . e($db,$oname) . "' AND od.language_id={$LANG} LIMIT 1")->fetch_assoc();
    if ($r) { $oid = (int)$r['option_id']; }
    else {
        $oid = ins($db,"INSERT INTO {$p}option (type,sort_order) VALUES ('" . e($db,$opt['type']) . "',1)");
        ins($db,"INSERT INTO {$p}option_description (option_id,language_id,name) VALUES ({$oid},{$LANG},'" . e($db,$oname) . "')");
        say("  + option: {$oname} [{$oid}]");
    }
    $OPTID[$oname] = $oid;
    $OVAL[$oname] = [];
    $vs = 1;
    foreach ($opt['values'] as $vname) {
        $r = $db->query("SELECT option_value_id FROM {$p}option_value WHERE option_id={$oid} AND sort_order={$vs} LIMIT 1")->fetch_assoc();
        if (!$r) {
            $vid = ins($db,"INSERT INTO {$p}option_value (option_id,image,sort_order) VALUES ({$oid},'',{$vs})");
            ins($db,"INSERT INTO {$p}option_value_description (option_value_id,language_id,option_id,name) VALUES ({$vid},{$LANG},{$oid},'" . e($db,$vname) . "')");
            $OVAL[$oname][$vname] = $vid;
        } else {
            // get existing
            $r2 = $db->query("SELECT ovd.option_value_id FROM {$p}option_value_description ovd WHERE ovd.option_id={$oid} AND ovd.name='" . e($db,$vname) . "' LIMIT 1")->fetch_assoc();
            if ($r2) $OVAL[$oname][$vname] = (int)$r2['option_value_id'];
        }
        $vs++;
    }
}

// ── Filter Groups ────────────────────────────────────────────────
say("\n=== FILTERS ===");
$FGROUPS = [
    'Производитель' => ['Berlingo','Brauberg','ГАММА','СТАММ','OfficeSpace','Erich Krause','Hatber','Pilot','Attache','Faber-Castell'],
    'Цвет'         => ['Синий','Красный','Чёрный','Зелёный','Ассорти','Прозрачный'],
    'Формат'       => ['А3','А4','А5','А6'],
];
$FID = []; // filter name => filter_id
foreach ($FGROUPS as $gname => $filters) {
    $r = $db->query("SELECT fg.filter_group_id FROM {$p}filter_group fg JOIN {$p}filter_group_description fgd USING(filter_group_id) WHERE fgd.name='" . e($db,$gname) . "' AND fgd.language_id={$LANG} LIMIT 1")->fetch_assoc();
    if ($r) { $fgid = (int)$r['filter_group_id']; }
    else {
        $fgid = ins($db,"INSERT INTO {$p}filter_group (sort_order) VALUES (1)");
        ins($db,"INSERT INTO {$p}filter_group_description (filter_group_id,language_id,name) VALUES ({$fgid},{$LANG},'" . e($db,$gname) . "')");
        say("  + filter_group: {$gname} [{$fgid}]");
    }
    $fs=1;
    foreach ($filters as $fname) {
        $r = $db->query("SELECT f.filter_id FROM {$p}filter f JOIN {$p}filter_description fd USING(filter_id) WHERE fd.name='" . e($db,$fname) . "' AND fd.language_id={$LANG} AND f.filter_group_id={$fgid} LIMIT 1")->fetch_assoc();
        if ($r) { $FID[$fname] = (int)$r['filter_id']; }
        else {
            $fid = ins($db,"INSERT INTO {$p}filter (filter_group_id,sort_order) VALUES ({$fgid},{$fs})");
            ins($db,"INSERT INTO {$p}filter_description (filter_id,language_id,filter_group_id,name) VALUES ({$fid},{$LANG},{$fgid},'" . e($db,$fname) . "')");
            $FID[$fname] = $fid;
        }
        $fs++;
    }
}

// ── Category Helper ───────────────────────────────────────────────
function insertCategory(mysqli $db, string $p, int $LANG, int $parentId,
    string $name, string $seo, string $icon, string $desc,
    string $rgb_key, array $CAT_RGB, string $imgBase, int $sort=1): int
{
    $topFlag = ($parentId === 0) ? 1 : 0;
    $img = makeImg("{$imgBase}/cat_{$seo}.png", $CAT_RGB[$rgb_key] ?? [100,100,100], $name);
    $id = ins($db,"INSERT INTO {$p}category (parent_id,image,icon,top,`column`,sort_order,status,noindex,date_added,date_modified)
        VALUES ({$parentId},'" . e($db,$img) . "','" . e($db,$icon) . "',{$topFlag},2,{$sort},1,0,NOW(),NOW())");
    ins($db,"INSERT INTO {$p}category_description (category_id,language_id,name,description,meta_title,meta_h1,meta_description,meta_keyword)
        VALUES ({$id},{$LANG},'" . e($db,$name) . "','" . e($db,$desc) . "','" . e($db,$name . ' купить оптом') . "','" . e($db,$name) . "','" . e($db,'Купить ' . mb_strtolower($name) . ' оптом в магазине Эврика') . "','" . e($db,mb_strtolower($name) . ', канцелярия оптом') . "')");
    ins($db,"INSERT INTO {$p}category_to_store (category_id,store_id) VALUES ({$id},0)");
    // Build category path (closure table)
    if ($parentId > 0) {
        $rows = $db->query("SELECT path_id,level FROM {$p}category_path WHERE category_id={$parentId} ORDER BY level");
        while ($pr = $rows->fetch_assoc()) {
            ins($db,"INSERT INTO {$p}category_path (category_id,path_id,level) VALUES ({$id},{$pr['path_id']},{$pr['level']})");
        }
        $maxRow = $db->query("SELECT COALESCE(MAX(level),-1)+1 AS nl FROM {$p}category_path WHERE category_id={$parentId}")->fetch_assoc();
        $nl = (int)$maxRow['nl'];
    } else {
        $nl = 0;
    }
    ins($db,"INSERT INTO {$p}category_path (category_id,path_id,level) VALUES ({$id},{$id},{$nl})");
    // SEO
    $db->query("INSERT IGNORE INTO {$p}seo_url (store_id,language_id,query,keyword) VALUES (0,{$LANG},'category_id={$id}','" . e($db,$seo) . "')");
    return $id;
}

// ── Categories ────────────────────────────────────────────────────
say("\n=== CATEGORIES ===");
$CID = []; // seo_key => category_id

$mainCats = [
    ['name'=>'Ручки и карандаши',    'seo'=>'ruchki-karandashi',    'icon'=>'pen',       'rgb'=>'ruchki',   'desc'=>'Шариковые, гелевые, капиллярные ручки и карандаши всех видов. Оптовые поставки канцелярии.'],
    ['name'=>'Тетради и блокноты',   'seo'=>'tetradi-bloknoty',     'icon'=>'notebook',  'rgb'=>'tetradi',  'desc'=>'Школьные и общие тетради, блокноты, ежедневники датированные и недатированные.'],
    ['name'=>'Папки и архивация',    'seo'=>'papki-arhivaciya',     'icon'=>'folder',    'rgb'=>'papki',    'desc'=>'Папки-скоросшиватели, регистраторы, файлы-вкладыши, боксы для документов.'],
    ['name'=>'Бумага для принтера',  'seo'=>'bumaga-printer',       'icon'=>'paper',     'rgb'=>'bumaga',   'desc'=>'Офисная бумага А4 и А3 различной плотности, цветная бумага, фотобумага.'],
    ['name'=>'Ножницы и резаки',     'seo'=>'nozhnicy-rezaki',      'icon'=>'scissors',  'rgb'=>'nozhnicy', 'desc'=>'Офисные и школьные ножницы, канцелярские ножи, резаки для бумаги.'],
    ['name'=>'Клей и скотч',         'seo'=>'kley-skotch',          'icon'=>'glue',      'rgb'=>'kley',     'desc'=>'Клей-карандаш, клей ПВА, жидкий клей, скотч прозрачный, двусторонний скотч.'],
    ['name'=>'Маркеры и фломастеры', 'seo'=>'markery-flomastery',   'icon'=>'marker',    'rgb'=>'markery',  'desc'=>'Маркеры перманентные, для доски и стекла, текстовыделители, фломастеры.'],
    ['name'=>'Художественные товары','seo'=>'hudozhestvennye',      'icon'=>'palette',   'rgb'=>'hudozh',   'desc'=>'Краски акварельные и гуашевые, кисти, пластилин, принадлежности для творчества.'],
    ['name'=>'Офисные принадлежности','seo'=>'ofisnye-prinadlezhnosti','icon'=>'briefcase','rgb'=>'ofisnye','desc'=>'Степлеры, скрепки, дыроколы, калькуляторы, органайзеры.'],
    ['name'=>'Школьные принадлежности','seo'=>'shkolnye-prinadlezhnosti','icon'=>'ruler','rgb'=>'shkolnye', 'desc'=>'Линейки, транспортиры, ластики, точилки, пеналы и школьные сумки.'],
];

// Sub-categories definition: [parent_seo, name, seo, icon, rgb, desc, has_subsubs]
$subCats = [
    // Ручки и карандаши
    ['ruchki-karandashi','Шариковые ручки','sharikovye-ruchki','pen','ruchki','Шариковые ручки для письма и офиса. Одноразовые и многоразовые.',true],
    ['ruchki-karandashi','Гелевые ручки','gelevye-ruchki','pen','ruchki','Гелевые ручки с мягким письмом — оптимальный выбор для офиса и учёбы.',false],
    ['ruchki-karandashi','Карандаши','karandashi','pen','ruchki','Простые и цветные карандаши разной твёрдости, наборы.',false],
    // Тетради и блокноты
    ['tetradi-bloknoty','Тетради школьные','tetradi-shkolnye','notebook','tetradi','Школьные тетради 12, 18, 24, 48 листов в клетку и линейку.',true],
    ['tetradi-bloknoty','Тетради общие','tetradi-obshchie','notebook','tetradi','Общие тетради А4 и А5 повышенного объёма для студентов и офиса.',false],
    ['tetradi-bloknoty','Ежедневники и блокноты','ezhednevniki-bloknoty','notebook','tetradi','Датированные и недатированные ежедневники, блокноты на спирали.',false],
    // Папки и архивация
    ['papki-arhivaciya','Папки-скоросшиватели','papki-skorosshivateli','folder','papki','Пластиковые и картонные папки-скоросшиватели для документов.',true],
    ['papki-arhivaciya','Папки-регистраторы','papki-registratory','folder','papki','Регистраторы с кольцевым механизмом шириной 50-80мм.',false],
    ['papki-arhivaciya','Файлы и вкладыши','fayly-vkladyshi','folder','papki','Перфорированные файлы-вкладыши А4 и А5, 100 штук в упаковке.',false],
    // Бумага
    ['bumaga-printer','Бумага А4','bumaga-a4','paper','bumaga','Офисная бумага формата А4 различной плотности.',true],
    ['bumaga-printer','Бумага А3','bumaga-a3','paper','bumaga','Офисная бумага формата А3 для принтеров и плоттеров.',false],
    ['bumaga-printer','Бумага цветная','bumaga-cvetnaya','paper','bumaga','Цветная офисная и копировальная бумага А4 80г/м².',false],
    // Ножницы и резаки
    ['nozhnicy-rezaki','Ножницы офисные','nozhnicy-ofisnye','scissors','nozhnicy','Профессиональные офисные ножницы с эргономичными ручками.',true],
    ['nozhnicy-rezaki','Ножницы школьные','nozhnicy-shkolnye','scissors','nozhnicy','Ножницы с закруглёнными концами для детей.',false],
    ['nozhnicy-rezaki','Канцелярские ножи','kancelyarskie-nozhi','scissors','nozhnicy','Ножи канцелярские 9мм и 18мм с трапециевидными лезвиями.',false],
    // Клей и скотч
    ['kley-skotch','Клей-карандаш','kley-karandash','glue','kley','Клей-карандаш 8-40г для бумаги, картона, фотографий.',false],
    ['kley-skotch','Клей ПВА и жидкий','kley-pva','glue','kley','Клей ПВА в тюбиках 100-500г, жидкий клей в стержнях.',false],
    ['kley-skotch','Скотч и клейкие ленты','skotch-lenty','glue','kley','Прозрачный и цветной скотч, двусторонние ленты.',false],
    // Маркеры
    ['markery-flomastery','Маркеры перманентные','markery-permanentnye','marker','markery','Перманентные маркеры для маркировки поверхностей.',false],
    ['markery-flomastery','Маркеры для доски','markery-dlya-doski','marker','markery','Стираемые маркеры для белых магнитно-маркерных досок.',false],
    ['markery-flomastery','Фломастеры','flomastery','marker','markery','Фломастеры для детского творчества, наборы 6-24 цвета.',false],
    // Художественные
    ['hudozhestvennye','Краски','kraski','palette','hudozh','Акварельные и гуашевые краски, наборы для школы и профессионалов.',false],
    ['hudozhestvennye','Кисти и инструменты','kisti-instrumenty','palette','hudozh','Кисти синтетические и натуральные, мольберты, палитры.',false],
    ['hudozhestvennye','Пластилин и лепка','plastilin-lepka','palette','hudozh','Пластилин, масса для лепки, стеки — для уроков труда.',false],
    // Офисные
    ['ofisnye-prinadlezhnosti','Степлеры и скрепки','stepjery-skrePki','stapler','ofisnye','Степлеры №10, №24/6, скрепки 28мм и 50мм.',false],
    ['ofisnye-prinadlezhnosti','Дыроколы','dyrokoly','stapler','ofisnye','Дыроколы на 10-30 листов, металлические и пластиковые.',false],
    ['ofisnye-prinadlezhnosti','Калькуляторы','kalykulyatory','calculator','ofisnye','Калькуляторы настольные, карманные, бухгалтерские.',false],
    // Школьные
    ['shkolnye-prinadlezhnosti','Линейки и транспортиры','lineyki-transportiry','ruler','shkolnye','Линейки 15-30см, угольники, транспортиры, наборы.',false],
    ['shkolnye-prinadlezhnosti','Ластики и точилки','lastiki-tochilki','ruler','shkolnye','Ластики для карандаша и чернил, точилки одинарные и двойные.',false],
    ['shkolnye-prinadlezhnosti','Пеналы','penaly','bag','shkolnye','Пеналы 1 и 3 отделения, тубусы, для девочек и мальчиков.',false],
];

// Sub-sub-categories: [parent_seo, name, seo, icon, rgb, desc]
$subSubCats = [
    ['sharikovye-ruchki','Одноразовые шариковые','sharikovye-odnorazovye','pen','ruchki','Одноразовые шариковые ручки, масса 0.5-1.0мм, синие/чёрные/красные.'],
    ['sharikovye-ruchki','Многоразовые шариковые','sharikovye-mnogorazovye','pen','ruchki','Шариковые ручки со сменным стержнем, металлический корпус.'],
    ['tetradi-shkolnye','Тетради в клетку','tetradi-v-kletku','notebook','tetradi','Школьные тетради 12-48 листов в клетку.'],
    ['tetradi-shkolnye','Тетради в линейку','tetradi-v-lineiku','notebook','tetradi','Школьные тетради 12-48 листов в широкую линейку.'],
    ['papki-skorosshivateli','Папки с пружиной','papki-pruzhinnye','folder','papki','Папки-скоросшиватели с пружинным прижимным механизмом.'],
    ['papki-skorosshivateli','Папки с кнопкой','papki-s-knopkoy','folder','papki','Папки-конверты с кнопкой для хранения документов.'],
    ['bumaga-a4','Бумага А4 80г/м²','bumaga-a4-80g','paper','bumaga','Офисная бумага А4 плотностью 80г/м², 500 листов в пачке.'],
    ['bumaga-a4','Бумага А4 75г/м²','bumaga-a4-75g','paper','bumaga','Офисная бумага А4 плотностью 75г/м², эконом-класс.'],
    ['nozhnicy-ofisnye','Ножницы 170мм','nozhnicy-170mm','scissors','nozhnicy','Офисные ножницы длиной 170мм для бумаги и упаковки.'],
    ['nozhnicy-ofisnye','Ножницы 210мм','nozhnicy-210mm','scissors','nozhnicy','Офисные ножницы длиной 210мм для плотных материалов.'],
];

function catExists(mysqli $db, string $p, int $LANG, string $name): int {
    $r = $db->query("SELECT category_id FROM {$p}category_description WHERE name='" . e($db,$name) . "' AND language_id={$LANG} LIMIT 1")->fetch_assoc();
    return $r ? (int)$r['category_id'] : 0;
}

// Insert main categories
$sort=1;
foreach ($mainCats as $c) {
    $existing = catExists($db,$p,$LANG,$c['name']);
    if ($existing) {
        $CID[$c['seo']] = $existing;
        say("  skip main: {$c['name']} [{$existing}]");
    } else {
        $cid = insertCategory($db,$p,$LANG,0,$c['name'],$c['seo'],$c['icon'],$c['desc'],$c['rgb'],$CAT_RGB,$imgBase,$sort);
        $CID[$c['seo']] = $cid;
        say("  + main: {$c['name']} [{$cid}]");
    }
    $sort++;
}

// Insert sub-categories
$sort=1; $lastParent='';
foreach ($subCats as $c) {
    [$parentSeo,$name,$seo,$icon,$rgb,$desc,$hasSub] = $c;
    if ($lastParent !== $parentSeo) { $sort=1; $lastParent=$parentSeo; }
    $existing = catExists($db,$p,$LANG,$name);
    if ($existing) { $CID[$seo]=$existing; say("  skip sub: {$name} [{$existing}]"); }
    else {
        $pid = $CID[$parentSeo] ?? 0;
        $cid = insertCategory($db,$p,$LANG,$pid,$name,$seo,$icon,$desc,$rgb,$CAT_RGB,$imgBase,$sort);
        $CID[$seo] = $cid;
        say("  + sub: {$name} [{$cid}]");
    }
    $sort++;
}

// Insert sub-sub-categories
$sort=1; $lastParent='';
foreach ($subSubCats as $c) {
    [$parentSeo,$name,$seo,$icon,$rgb,$desc] = $c;
    if ($lastParent !== $parentSeo) { $sort=1; $lastParent=$parentSeo; }
    $existing = catExists($db,$p,$LANG,$name);
    if ($existing) { $CID[$seo]=$existing; say("  skip subsub: {$name} [{$existing}]"); }
    else {
        $pid = $CID[$parentSeo] ?? 0;
        $cid = insertCategory($db,$p,$LANG,$pid,$name,$seo,$icon,$desc,$rgb,$CAT_RGB,$imgBase,$sort);
        $CID[$seo] = $cid;
        say("  + subsub: {$name} [{$cid}]");
    }
    $sort++;
}

say("Categories: " . count($CID) . " total");

// ── Products ──────────────────────────────────────────────────────
// [name, cat, brand, model, price, qty, weight, desc, attrs[], opt|null, special|null]
// attrs: assoc array [attr_name => value]
// opt: 'color' | 'pack' | null
say("\n=== PRODUCTS ===");

$PRODUCTS = [
// ── Одноразовые шариковые ──────────────────────────────────────────
['Ручка шариковая Berlingo "Tribase" синяя 0.7мм','sharikovye-odnorazovye','Berlingo','CBp_70871',18.50,500,8,
 'Ручка шариковая одноразовая. Цвет чернил: синий. Толщина линии 0.35мм. Диаметр шарика 0.7мм. Игольчатый пишущий узел. Прозрачный корпус с резиновым упором.',
 ['Цвет'=>'Синий','Тип'=>'Шариковая одноразовая','Толщина линии, мм'=>'0.35','Страна производства'=>'Россия','Количество в упаковке'=>'50 шт'],'color',null],

['Ручка шариковая Brauberg "Extra Glide" синяя 1.0мм','sharikovye-odnorazovye','Brauberg','141569',14.00,600,7,
 'Ручка шариковая масляная. Насыщенный синий цвет чернил. Мягкое и плавное письмо без нажима. Корпус из матового пластика.',
 ['Цвет'=>'Синий','Тип'=>'Масляная шариковая','Толщина линии, мм'=>'0.5','Страна производства'=>'Россия','Количество в упаковке'=>'50 шт'],'color',null],

['Ручка шариковая СТАММ "Офис" синяя 1.0мм','sharikovye-odnorazovye','СТАММ','РО-10',10.00,800,6,
 'Ручка шариковая стандартная. Синие чернила. Длина письма 1500м. Прозрачный корпус с колпачком.',
 ['Цвет'=>'Синий','Тип'=>'Шариковая','Толщина линии, мм'=>'0.5','Страна производства'=>'Россия','Количество в упаковке'=>'50 шт'],'color',null],

['Ручка шариковая OfficeSpace "Reef" 0.7мм','sharikovye-odnorazovye','OfficeSpace','BP_12537',16.00,400,7,
 'Шариковая ручка с металлическим наконечником. Чернила синие, быстросохнущие. Эргономичный резиновый упор.',
 ['Цвет'=>'Синий','Тип'=>'Шариковая','Толщина линии, мм'=>'0.35','Страна производства'=>'Россия','Количество в упаковке'=>'50 шт'],'color',null],

// ── Многоразовые шариковые ────────────────────────────────────────
['Ручка шариковая Berlingo "Classic" металл','sharikovye-mnogorazovye','Berlingo','CBm_70012',95.00,120,20,
 'Шариковая ручка в металлическом корпусе со сменным стержнем. Клип и кольцо из хромированного металла. Цвет корпуса серебристый.',
 ['Цвет'=>'Серебристый','Тип'=>'Многоразовая со стержнем','Материал'=>'Металл','Страна производства'=>'Россия','Вес, г'=>'18'],'color',null],

['Ручка шариковая Erich Krause "Silk Touch"','sharikovye-mnogorazovye','Erich Krause','EK46515',75.00,150,15,
 'Шариковая ручка с покрытием Soft Touch. Сменный стержень. Подходит в качестве подарка и для корпоративного использования.',
 ['Цвет'=>'Чёрный','Тип'=>'Многоразовая со стержнем','Материал'=>'Пластик Soft Touch','Страна производства'=>'Россия','Вес, г'=>'14'],'color',null],

['Ручка шариковая Attache "Prestige" серебро','sharikovye-mnogorazovye','Attache','AT-PRSV',110.00,80,22,
 'Представительская шариковая ручка в подарочной коробке. Металлический корпус. Совместима со стержнями Parker.',
 ['Цвет'=>'Серебристый','Тип'=>'Многоразовая','Материал'=>'Металл','Страна производства'=>'Россия','Вес, г'=>'22'],'color',null],

// ── Гелевые ручки ────────────────────────────────────────────────
['Ручка гелевая Berlingo "Apex" синяя 0.5мм','gelevye-ruchki','Berlingo','CGp_50120',32.00,350,9,
 'Гелевая ручка с игольчатым пишущим узлом. Цвет чернил синий. Плавное и чёткое письмо.',
 ['Цвет'=>'Синий','Тип'=>'Гелевая','Толщина линии, мм'=>'0.3','Страна производства'=>'Россия','Количество в упаковке'=>'12 шт'],'color',null],

['Ручка гелевая Brauberg "EXTRA Modern" чёрная','gelevye-ruchki','Brauberg','143416',38.00,300,9,
 'Гелевая ручка с металлическим наконечником и прозрачным корпусом. Надёжный клип, резиновый упор.',
 ['Цвет'=>'Чёрный','Тип'=>'Гелевая','Толщина линии, мм'=>'0.35','Страна производства'=>'Россия'],'color',null],

['Ручка гелевая Erich Krause "G-SOFT" синяя 0.5мм','gelevye-ruchki','Erich Krause','EK39206',35.00,280,8,
 'Гелевая ручка с мягкими чернилами. Эргономичная трёхгранная форма корпуса. Не оставляет клякс.',
 ['Цвет'=>'Синий','Тип'=>'Гелевая','Толщина линии, мм'=>'0.3','Страна производства'=>'Россия'],'color',null],

['Ручка гелевая Pilot "G2" синяя 0.7мм','gelevye-ruchki','Pilot','BL-G2-7-L',120.00,100,11,
 'Культовая гелевая ручка Pilot G2. Smooth writing. Надавливаемый механизм, металлический наконечник.',
 ['Цвет'=>'Синий','Тип'=>'Гелевая автоматическая','Толщина линии, мм'=>'0.4','Страна производства'=>'Япония'],'color',null],

// ── Карандаши ─────────────────────────────────────────────────────
['Карандаш простой Berlingo "SuperSoft" НВ','karandashi','Berlingo','BP_05110',12.00,600,5,
 'Чернографитный карандаш. Твёрдость HB. Шестигранный корпус из дерева. Мягкое ядро. Не крошится.',
 ['Тип'=>'Простой карандаш','Материал'=>'Дерево','Твёрдость'=>'HB','Страна производства'=>'Россия','Количество в упаковке'=>'12 шт'],null,null],

['Карандаш простой Brauberg "Art Classic" 2В','karandashi','Brauberg','181264',18.00,400,5,
 'Чернографитный мягкий карандаш для рисования и набросков. Твёрдость 2B. Корпус из черного дерева.',
 ['Тип'=>'Простой карандаш','Твёрдость'=>'2B','Материал'=>'Дерево','Страна производства'=>'Россия'],null,null],

['Набор карандашей цветных ГАММА "Классика" 12 цв','karandashi','ГАММА','050419_12',95.00,250,60,
 'Набор из 12 цветных карандашей. Мягкий грифель, яркие цвета. Деревянный корпус, удобная заточка. Металлическая коробка.',
 ['Тип'=>'Цветные карандаши','Количество в упаковке'=>'12 шт','Материал'=>'Дерево','Страна производства'=>'Россия','Вес, г'=>'60'],null,null],

['Набор карандашей Faber-Castell "Grip 2001" 18 цв','karandashi','Faber-Castell','112418',680.00,50,85,
 'Профессиональные цветные карандаши Faber-Castell. 18 цветов, трёхгранный корпус с системой "Grip". Мягкий яркий грифель, особо прочный.',
 ['Тип'=>'Цветные карандаши','Количество в упаковке'=>'18 шт','Материал'=>'Дерево SFM','Страна производства'=>'Германия','Вес, г'=>'85'],null,null],

// ── Тетради в клетку ─────────────────────────────────────────────
['Тетрадь 12л клетка СТАММ "Маяк"','tetradi-v-kletku','СТАММ','ТК12Л_13977',9.00,2000,30,
 'Школьная тетрадь 12 листов в клетку. Обложка мелованная картон. Скрепка. Офсетная бумага 60г/м². Разметка 5мм.',
 ['Формат'=>'А5','Количество в упаковке'=>'10 шт','Тип'=>'В клетку','Вес, г'=>'30','Страна производства'=>'Россия'],'pack',null],

['Тетрадь 18л клетка Hatber "Классика"','tetradi-v-kletku','Hatber','18Т5В1_18104',12.00,1500,40,
 'Тетрадь 18 листов в клетку. Цветная обложка. Офсетная бумага 65г/м². Удобный формат А5.',
 ['Формат'=>'А5','Количество в упаковке'=>'10 шт','Тип'=>'В клетку','Вес, г'=>'40','Страна производства'=>'Россия'],'pack',null],

['Тетрадь 24л клетка Brauberg','tetradi-v-kletku','Brauberg','400468',14.00,1200,48,
 'Тетрадь 24 листа в клетку с полями. Обложка с матовой ламинацией. Писчая бумага 70г/м².',
 ['Формат'=>'А5','Количество в упаковке'=>'10 шт','Тип'=>'В клетку','Вес, г'=>'48','Страна производства'=>'Россия'],'pack',null],

['Тетрадь 48л клетка OfficeSpace','tetradi-v-kletku','OfficeSpace','T48k_9552',28.00,700,80,
 'Тетрадь 48 листов в клетку А5. Твёрдая обложка. Бумага 80г/м². Ститчинг скрепки.',
 ['Формат'=>'А5','Тип'=>'В клетку','Вес, г'=>'80','Страна производства'=>'Россия'],'pack',null],

// ── Тетради в линейку ─────────────────────────────────────────────
['Тетрадь 12л линейка СТАММ "Цветные"','tetradi-v-lineiku','СТАММ','ТЛ12Л_16811',9.00,2000,30,
 'Школьная тетрадь 12 листов в широкую линейку. Яркая мелованная обложка. Бумага офсет 60г/м².',
 ['Формат'=>'А5','Тип'=>'В линейку','Количество в упаковке'=>'10 шт','Вес, г'=>'30','Страна производства'=>'Россия'],'pack',null],

['Тетрадь 18л линейка Hatber','tetradi-v-lineiku','Hatber','18Т5Л1_18234',12.00,1500,40,
 'Тетрадь 18 листов в линейку. Формат А5. Скрепка. Цветная обложка.',
 ['Формат'=>'А5','Тип'=>'В линейку','Количество в упаковке'=>'10 шт','Вес, г'=>'40'],'pack',null],

['Тетрадь 48л линейка OfficeSpace "Бизнес"','tetradi-v-lineiku','OfficeSpace','T48l_biz',28.00,600,80,
 'Тетрадь 48 листов в линейку с полями. Обложка с матовым покрытием. Подходит для офисных записей.',
 ['Формат'=>'А5','Тип'=>'В линейку','Вес, г'=>'80'],'pack',null],

// ── Тетради общие ─────────────────────────────────────────────────
['Тетрадь общая 96л А5 Erich Krause "Classic"','tetradi-obshchie','Erich Krause','EK48416',65.00,300,160,
 'Тетрадь общая 96 листов А5. Клетка 5мм с полями. Мягкая обложка с ламинацией. Скрепка.',
 ['Формат'=>'А5','Количество в упаковке'=>'5 шт','Тип'=>'В клетку','Вес, г'=>'160','Страна производства'=>'Россия'],null,null],

['Тетрадь общая 80л А4 OfficeSpace','tetradi-obshchie','OfficeSpace','T80k_A4_3234',72.00,250,200,
 'Тетрадь общая 80 листов А4 в клетку. Офсетная бумага 80г/м². Обложка мягкая ламинированная.',
 ['Формат'=>'А4','Тип'=>'В клетку','Вес, г'=>'200'],null,null],

['Тетрадь общая 60л А5 СТАММ','tetradi-obshchie','СТАММ','ТО60_5544',45.00,400,120,
 'Тетрадь общая 60 листов А5 в клетку. Скрепка. Бумага 60г/м².',
 ['Формат'=>'А5','Тип'=>'В клетку','Вес, г'=>'120'],null,null],

// ── Ежедневники и блокноты ────────────────────────────────────────
['Ежедневник датированный А5 Berlingo "Vibe" 2026','ezhednevniki-bloknoty','Berlingo','EDV_A5_26',380.00,80,310,
 'Датированный ежедневник на 2026 год А5. 176 листов офсет 70г/м². Твёрдая обложка с ляссе. Справочный блок.',
 ['Формат'=>'А5','Тип'=>'Датированный ежедневник','Количество в упаковке'=>'1 шт','Вес, г'=>'310','Страна производства'=>'Россия'],null,null],

['Блокнот А6 80л "Note" Attache','ezhednevniki-bloknoty','Attache','AT_NOTE_A6',95.00,150,80,
 'Блокнот А6 на скрепке, 80 листов клетка. Твёрдая обложка. Удобный формат для записей.',
 ['Формат'=>'А6','Тип'=>'Блокнот','Вес, г'=>'80'],null,null],

['Ежедневник недатированный А5 Erich Krause','ezhednevniki-bloknoty','Erich Krause','EK_ND_A5',250.00,120,280,
 'Недатированный ежедневник А5. 192 листа. Зернистая обложка с поролоновым наполнением. Ляссе-закладка.',
 ['Формат'=>'А5','Тип'=>'Недатированный ежедневник','Вес, г'=>'280'],null,null],

['Блокнот А5 на спирали СТАММ "Офис"','ezhednevniki-bloknoty','СТАММ','БС48_А5',75.00,200,120,
 'Блокнот на металлической спирали А5, 48 листов в клетку. Пластиковая обложка. Удобен для работы.',
 ['Формат'=>'А5','Тип'=>'Блокнот на спирали','Вес, г'=>'120'],null,null],

// ── Папки с пружиной ─────────────────────────────────────────────
['Папка-скоросшиватель A4 "Berlingo" пластик синяя','papki-pruzhinnye','Berlingo','AMp_04403',45.00,300,80,
 'Папка-скоросшиватель А4. Прозрачная верхняя обложка. Пружинный пластиковый механизм. Вмещает до 150 листов.',
 ['Формат'=>'А4','Материал'=>'Пластик','Цвет'=>'Синий','Вес, г'=>'80','Страна производства'=>'Россия'],null,null],

['Папка-скоросшиватель Erich Krause пластик А4','papki-pruzhinnye','Erich Krause','EK_AM_02',38.00,350,75,
 'Скоросшиватель А4 с пластиковым зажимом. Прозрачный карман на обложке для аннотации.',
 ['Формат'=>'А4','Материал'=>'Пластик','Вес, г'=>'75'],null,null],

['Папка-скоросшиватель СТАММ металл А4','papki-pruzhinnye','СТАММ','ПСМ_01',55.00,200,90,
 'Скоросшиватель А4 с металлическим пружинным механизмом. Повышенная надёжность крепления.',
 ['Формат'=>'А4','Материал'=>'Пластик+металл','Вес, г'=>'90'],null,null],

// ── Папки с кнопкой ───────────────────────────────────────────────
['Папка-конверт на кнопке А4 Berlingo','papki-s-knopkoy','Berlingo','EFp_A4_010',55.00,200,60,
 'Папка-конверт формата А4. Закрывается на пластиковую кнопку. Прозрачная, вмещает до 100 листов.',
 ['Формат'=>'А4','Материал'=>'Полипропилен','Цвет'=>'Прозрачный','Вес, г'=>'60'],null,null],

['Папка-конверт на кнопке А4 Brauberg','papki-s-knopkoy','Brauberg','224817',48.00,300,55,
 'Папка-конверт А4 с кнопкой, матовая. Матовый непрозрачный полипропилен. Цвет ассорти.',
 ['Формат'=>'А4','Материал'=>'Полипропилен','Цвет'=>'Ассорти','Вес, г'=>'55'],null,null],

['Папка-конверт А4 Hatber','papki-s-knopkoy','Hatber','AK4_00003',42.00,350,52,
 'Папка-конверт с кнопкой А4. Плотный пластик 0.18мм. Цветная.',
 ['Формат'=>'А4','Материал'=>'Полипропилен','Вес, г'=>'52'],null,null],

// ── Папки-регистраторы ────────────────────────────────────────────
['Регистратор А4 50мм "Empire" Berlingo','papki-registratory','Berlingo','AMb_50402',350.00,150,620,
 'Папка-регистратор А4 шириной 50мм. Механизм с арочным кольцом. Мрамор. Металлические углы и торцевой карман.',
 ['Формат'=>'А4','Материал'=>'ПВХ','Ширина'=>'50мм','Вес, г'=>'620','Страна производства'=>'Россия'],null,null],

['Регистратор А4 75мм Erich Krause','papki-registratory','Erich Krause','EK_REG_75',380.00,120,680,
 'Регистратор А4/75мм. 4-кольцевой механизм. Бумвинил. Металлические уголки.',
 ['Формат'=>'А4','Материал'=>'Бумвинил','Ширина'=>'75мм','Вес, г'=>'680'],null,null],

['Регистратор А4 80мм OfficeSpace','papki-registratory','OfficeSpace','RA4_80_OS',420.00,100,720,
 'Папка-регистратор А4 ширина корешка 80мм. Глянцевый покрытие. Металлический торцевой карман.',
 ['Формат'=>'А4','Ширина'=>'80мм','Вес, г'=>'720'],null,null],

// ── Файлы и вкладыши ──────────────────────────────────────────────
['Файл-вкладыш А4 Berlingo 100шт','fayly-vkladyshi','Berlingo','IOp_A4100',195.00,500,230,
 'Папки-файлы перфорированные А4 100 штук в упаковке. Плотность 30 мкм. Прозрачные.',
 ['Формат'=>'А4','Количество в упаковке'=>'100 шт','Материал'=>'Полипропилен 30мкм','Вес, г'=>'230'],null,null],

['Папка-вкладыш А5 Brauberg 100шт','fayly-vkladyshi','Brauberg','223079',175.00,400,180,
 'Файлы А5 перфорированные, 100 штук. Плотность 30 мкм.',
 ['Формат'=>'А5','Количество в упаковке'=>'100 шт','Вес, г'=>'180'],null,null],

['Файл А4 с перфорацией Hatber 50шт','fayly-vkladyshi','Hatber','ПВ4_00001',110.00,600,115,
 'Папки-файлы А4 50 штук. Усиленная перфорация. Плотность 40 мкм.',
 ['Формат'=>'А4','Количество в упаковке'=>'50 шт','Материал'=>'Полипропилен 40мкм','Вес, г'=>'115'],null,null],

// ── Бумага А4 80г/м² ──────────────────────────────────────────────
['Бумага А4 80г/м² 500л "SvetoCopy"','bumaga-a4-80g','Канц-Эксмо','SVT80_500',380.00,2000,2500,
 'Офисная бумага для копировальных аппаратов и лазерных принтеров. Формат А4, 80г/м², 500 листов. Белизна CIE 146.',
 ['Формат'=>'А4','Количество в упаковке'=>'500 листов','Вес, г'=>'2500','Страна производства'=>'Россия'],'pack',null],

['Бумага А4 80г/м² 500л "Ballet Classic"','bumaga-a4-80g','Канц-Эксмо','BLC80_500',360.00,2500,2500,
 'Офисная бумага Ballet Classic А4 80г/м². 500 листов в пачке. 5 пачек в коробке. Класс A+.',
 ['Формат'=>'А4','Количество в упаковке'=>'500 листов','Вес, г'=>'2500','Страна производства'=>'Финляндия'],'pack',null],

['Бумага А4 80г/м² 500л Berlingo','bumaga-a4-80g','Berlingo','PPw_A4500',350.00,3000,2500,
 'Универсальная офисная бумага Berlingo А4 80г/м² 500 листов. Подходит для всех видов принтеров.',
 ['Формат'=>'А4','Количество в упаковке'=>'500 листов','Вес, г'=>'2500'],'pack',null],

['Бумага А4 80г/м² 250л OfficeSpace','bumaga-a4-80g','OfficeSpace','PPA4_250',190.00,4000,1250,
 'Бумага офисная А4 80г/м² 250 листов в пачке. Формат Half Pack.',
 ['Формат'=>'А4','Количество в упаковке'=>'250 листов','Вес, г'=>'1250'],'pack',null],

// ── Бумага А4 75г/м² ──────────────────────────────────────────────
['Бумага А4 75г/м² 500л "Maestro Print"','bumaga-a4-75g','Канц-Эксмо','MP75_500',320.00,2000,2300,
 'Офисная бумага Maestro Print А4 75г/м². 500 листов. Хорошо подходит для струйных принтеров.',
 ['Формат'=>'А4','Количество в упаковке'=>'500 листов','Вес, г'=>'2300'],'pack',null],

['Бумага А4 75г/м² 500л "IQ Allround"','bumaga-a4-75g','Канц-Эксмо','IQA_75_500',330.00,2000,2300,
 'Бумага IQ Allround А4 75г/м². Универсальная. Белизна 162 CIE.',
 ['Формат'=>'А4','Количество в упаковке'=>'500 листов','Вес, г'=>'2300'],'pack',null],

['Бумага А4 75г/м² 500л OfficeSpace "Эконом"','bumaga-a4-75g','OfficeSpace','OE75_500',299.00,3000,2300,
 'Бумага эконом-класса А4 75г/м². 500 листов. Подходит для черновиков и ежедневной печати.',
 ['Формат'=>'А4','Количество в упаковке'=>'500 листов','Вес, г'=>'2300'],'pack',null],

// ── Бумага А3 ────────────────────────────────────────────────────
['Бумага А3 80г/м² 500л Berlingo','bumaga-a3','Berlingo','PPw_A3500',720.00,800,4800,
 'Офисная бумага А3 80г/м². 500 листов в пачке. Для принтеров, плоттеров, копиров.',
 ['Формат'=>'А3','Количество в упаковке'=>'500 листов','Вес, г'=>'4800'],'pack',null],

['Бумага А3 80г/м² 250л OfficeSpace','bumaga-a3','OfficeSpace','PPA3_250',380.00,600,2400,
 'Офисная бумага А3 80г/м² 250 листов. Экономичная упаковка.',
 ['Формат'=>'А3','Количество в упаковке'=>'250 листов','Вес, г'=>'2400'],null,null],

['Бумага А3 75г/м² 500л Снегурочка','bumaga-a3','Канц-Эксмо','SNE_A3_75',680.00,500,4600,
 'Бумага Снегурочка А3 75г/м² 500 листов. Класс B. Российское производство.',
 ['Формат'=>'А3','Количество в упаковке'=>'500 листов','Вес, г'=>'4600'],'pack',null],

// ── Бумага цветная ───────────────────────────────────────────────
['Бумага цветная А4 80г 20цв 100л Berlingo','bumaga-cvetnaya','Berlingo','PPc_A4_20',155.00,500,550,
 'Цветная офисная бумага А4 80г/м². 20 цветов по 5 листов каждого. 100 листов в упаковке.',
 ['Формат'=>'А4','Количество в упаковке'=>'100 листов 20 цв.','Вес, г'=>'550'],'pack',null],

['Бумага цветная А4 80г 5цв 250л Hatber','bumaga-cvetnaya','Hatber','ЦБ5_250',165.00,400,560,
 'Цветная бумага А4 80г. 5 цветов по 50 листов каждого. 250 листов. Интенсивные цвета.',
 ['Формат'=>'А4','Количество в упаковке'=>'250 листов 5 цв.','Вес, г'=>'560'],'pack',null],

['Бумага цветная неон А4 5цв OfficeSpace','bumaga-cvetnaya','OfficeSpace','PPcn_A4_5',145.00,600,500,
 'Неоновая цветная бумага А4 80г. 5 цветов. 50 листов каждого. Флуоресцентные оттенки.',
 ['Формат'=>'А4','Количество в упаковке'=>'250 листов','Вес, г'=>'500'],null,null],

// ── Ножницы 170мм ─────────────────────────────────────────────────
['Ножницы 170мм Berlingo "Soft Grip" синие','nozhnicy-170mm','Berlingo','TNg_17012',145.00,200,85,
 'Офисные ножницы 170мм с мягкими прорезиненными ручками. Нержавеющая сталь. Прямое лезвие.',
 ['Длина'=>'170мм','Материал лезвия'=>'Нержавеющая сталь','Страна производства'=>'Россия','Вес, г'=>'85'],null,null],

['Ножницы 170мм Brauberg офисные','nozhnicy-170mm','Brauberg','230757',120.00,250,80,
 'Ножницы офисные 170мм. Лезвия из нержавеющей стали. Пластиковые рукоятки.',
 ['Длина'=>'170мм','Материал лезвия'=>'Нержавеющая сталь','Вес, г'=>'80'],null,null],

['Ножницы 170мм Erich Krause','nozhnicy-170mm','Erich Krause','EK_SC_170',130.00,180,82,
 'Ножницы офисные 170мм Erich Krause. Прочные лезвия, удобная форма рукояток.',
 ['Длина'=>'170мм','Материал лезвия'=>'Нержавеющая сталь','Вес, г'=>'82'],null,null],

// ── Ножницы 210мм ─────────────────────────────────────────────────
['Ножницы 210мм Berlingo "Soft Grip"','nozhnicy-210mm','Berlingo','TNg_21012',185.00,150,110,
 'Профессиональные ножницы 210мм для офиса. Мягкие резиновые ручки, нержавеющее лезвие.',
 ['Длина'=>'210мм','Материал лезвия'=>'Нержавеющая сталь','Вес, г'=>'110'],null,null],

['Ножницы 210мм Attache "Super Grip"','nozhnicy-210mm','Attache','AT_SC_210',175.00,130,105,
 'Ножницы 210мм с усиленными ручками для длительной работы. Лезвия заточены по технологии Micro.',
 ['Длина'=>'210мм','Материал лезвия'=>'Нержавеющая сталь','Вес, г'=>'105'],null,null],

['Ножницы 210мм Hatber офисные','nozhnicy-210mm','Hatber','НОЖ210_01',155.00,180,100,
 'Ножницы офисные 210мм. Прямое лезвие. Удобные пластиковые ручки разного размера.',
 ['Длина'=>'210мм','Материал лезвия'=>'Нержавеющая сталь','Вес, г'=>'100'],null,null],

// ── Ножницы школьные ─────────────────────────────────────────────
['Ножницы школьные СТАММ 130мм','nozhnicy-shkolnye','СТАММ','НОШ_13_РЖ',55.00,500,45,
 'Детские ножницы 130мм с закруглёнными концами. Безопасные для детей от 3 лет. Пластиковые рукоятки.',
 ['Длина'=>'130мм','Тип'=>'Школьные','Вес, г'=>'45','Страна производства'=>'Россия'],null,null],

['Ножницы школьные Berlingo 140мм','nozhnicy-shkolnye','Berlingo','TNs_14002',65.00,400,50,
 'Школьные ножницы 140мм с закруглёнными лезвиями. Яркий пластик. Для детей 5+.',
 ['Длина'=>'140мм','Тип'=>'Школьные','Вес, г'=>'50'],null,null],

['Ножницы школьные Hatber 140мм','nozhnicy-shkolnye','Hatber','НОШ140_02',58.00,450,48,
 'Ножницы 140мм для школьников. Металлические лезвия, пластиковые ручки разного цвета.',
 ['Длина'=>'140мм','Тип'=>'Школьные','Вес, г'=>'48'],null,null],

// ── Канцелярские ножи ─────────────────────────────────────────────
['Нож канцелярский 18мм Berlingo "Steel"','kancelyarskie-nozhi','Berlingo','TNk_18012',95.00,300,65,
 'Нож канцелярский 18мм. Металлический корпус с кнопочным фиксатором. Лезвие трапециевидное.',
 ['Ширина лезвия'=>'18мм','Материал корпуса'=>'Металл','Вес, г'=>'65'],null,null],

['Нож канцелярский 9мм Brauberg','kancelyarskie-nozhi','Brauberg','230919',55.00,400,35,
 'Нож канцелярский 9мм. Пластиковый корпус. Лезвие 9мм с сегментными отрезными делениями.',
 ['Ширина лезвия'=>'9мм','Материал корпуса'=>'Пластик','Вес, г'=>'35'],null,null],

['Нож канцелярский 18мм Erich Krause','kancelyarskie-nozhi','Erich Krause','EK_KN_18',80.00,280,55,
 'Нож канцелярский 18мм. Усиленный пластиковый корпус. Автоматическая фиксация лезвия.',
 ['Ширина лезвия'=>'18мм','Материал корпуса'=>'ABS-пластик','Вес, г'=>'55'],null,null],

// ── Клей-карандаш ─────────────────────────────────────────────────
['Клей-карандаш 21г Berlingo','kley-karandash','Berlingo','LCk_21g',42.00,600,25,
 'Клей-карандаш 21г. Без запаха. Быстро сохнет. Прозрачный при высыхании. Подходит для бумаги, картона, фото.',
 ['Объём'=>'21г','Тип'=>'Карандаш','Вес, г'=>'25','Страна производства'=>'Россия'],'pack',null],

['Клей-карандаш 15г Erich Krause "Superior"','kley-karandash','Erich Krause','EK_GS_15',38.00,700,18,
 'Клей-карандаш 15г. Нетоксичный. Подходит для бумаги и картона. Не морщит бумагу.',
 ['Объём'=>'15г','Тип'=>'Карандаш','Вес, г'=>'18'],'pack',null],

['Клей-карандаш 40г Brauberg','kley-karandash','Brauberg','224611',65.00,400,45,
 'Клей-карандаш 40г усиленного размера. Прозрачный при высыхании. Стираемый след.',
 ['Объём'=>'40г','Тип'=>'Карандаш','Вес, г'=>'45'],'pack',null],

['Клей-карандаш 36г СТАММ','kley-karandash','СТАММ','КК36_01',55.00,500,40,
 'Клей-карандаш СТАММ 36г. Для бумаги, картона, фото. Без ацетона. Быстрое склеивание.',
 ['Объём'=>'36г','Тип'=>'Карандаш','Вес, г'=>'40'],'pack',null],

// ── Клей ПВА ──────────────────────────────────────────────────────
['Клей ПВА 100г СТАММ','kley-pva','СТАММ','КП100_01',38.00,800,105,
 'Клей ПВА 100г. Тюбик. Для склейки бумаги, картона, обоев, ткани. Прочное соединение.',
 ['Объём'=>'100г','Тип'=>'ПВА','Вес, г'=>'105'],null,null],

['Клей ПВА 250г Berlingo','kley-pva','Berlingo','LCp_250',78.00,400,260,
 'Клей ПВА 250г. Флакон с кисточкой. Универсальный. Подходит для аппликаций.',
 ['Объём'=>'250г','Тип'=>'ПВА','Вес, г'=>'260'],null,null],

['Клей жидкий Erich Krause "Stick" 8г','kley-pva','Erich Krause','EK_LGL_8',28.00,1000,12,
 'Жидкий клей в стержне-кисточке 8г. Без запаха. Подходит для бумаги и картона.',
 ['Объём'=>'8г','Тип'=>'Жидкий','Вес, г'=>'12'],null,null],

// ── Скотч ─────────────────────────────────────────────────────────
['Скотч прозрачный 19мм×33м Berlingo','skotch-lenty','Berlingo','LNт_19331',35.00,1000,35,
 'Скотч прозрачный 19мм×33м. Основа — полипропилен. Прозрачный клей-акрилат. Лёгкий раскрой.',
 ['Ширина'=>'19мм','Длина'=>'33м','Тип'=>'Прозрачный'],'pack',null],

['Скотч прозрачный 48мм×40м Brauberg','skotch-lenty','Brauberg','440145',55.00,800,90,
 'Скотч 48мм×40м для упаковки. Прочный прозрачный. Лёгкое разматывание.',
 ['Ширина'=>'48мм','Длина'=>'40м','Тип'=>'Прозрачный упаковочный'],'pack',null],

['Лента двухсторонняя Hatber 12мм×10м','skotch-lenty','Hatber','ЛК12_10',48.00,600,28,
 'Двусторонняя клейкая лента 12мм×10м. Для скрапбукинга, упаковки подарков, фото.',
 ['Ширина'=>'12мм','Длина'=>'10м','Тип'=>'Двусторонняя'],'pack',null],

// ── Маркеры перманентные ──────────────────────────────────────────
['Маркер перманентный Berlingo "T2" чёрный','markery-permanentnye','Berlingo','TMp_10001',42.00,500,15,
 'Маркер перманентный чёрный. Пулевидный стержень 2.5мм. Спиртовые чернила. Для всех поверхностей.',
 ['Цвет'=>'Чёрный','Тип'=>'Перманентный','Толщина линии, мм'=>'2.5','Страна производства'=>'Россия'],'color',null],

['Маркер перманентный Brauberg синий','markery-permanentnye','Brauberg','150456',38.00,500,14,
 'Перманентный маркер синий. Пулевидный наконечник 3мм. Быстросохнущие чернила.',
 ['Цвет'=>'Синий','Тип'=>'Перманентный','Толщина линии, мм'=>'3.0'],'color',null],

['Набор маркеров перманентных 4цв Erich Krause','markery-permanentnye','Erich Krause','EK_MP_4',195.00,200,60,
 'Набор из 4 перманентных маркеров: чёрный, синий, красный, зелёный. Наконечник 2мм.',
 ['Цвет'=>'Ассорти','Тип'=>'Перманентный','Количество в упаковке'=>'4 шт'],null,null],

['Маркер перманентный Pilot "SCA" чёрный','markery-permanentnye','Pilot','SCA-F-B',85.00,300,13,
 'Перманентный маркер Pilot. Клиновидный наконечник 1-4мм. Стойкие к воде чернила.',
 ['Цвет'=>'Чёрный','Тип'=>'Перманентный','Страна производства'=>'Япония'],'color',null],

// ── Маркеры для доски ─────────────────────────────────────────────
['Маркер для доски Berlingo "Clean Wipe" чёрный','markery-dlya-doski','Berlingo','TMb_10001',65.00,400,18,
 'Маркер для белых досок. Чёрный. Легко стирается. Круглый наконечник 3мм. Запасной стержень в комплекте.',
 ['Цвет'=>'Чёрный','Тип'=>'Для доски','Толщина линии, мм'=>'3.0'],'color',null],

['Набор маркеров для доски 4цв Brauberg','markery-dlya-doski','Brauberg','151418',285.00,200,75,
 'Набор маркеров для доски 4 цвета. Скошенный наконечник 2-5мм. Легко стирается.',
 ['Цвет'=>'Ассорти','Тип'=>'Для доски','Количество в упаковке'=>'4 шт'],null,null],

['Маркер для доски Erich Krause синий','markery-dlya-doski','Erich Krause','EK_WB_B',58.00,350,16,
 'Маркер для маркерных досок синий. Клиновидный наконечник. Сухое стирание.',
 ['Цвет'=>'Синий','Тип'=>'Для доски'],'color',null],

// ── Фломастеры ────────────────────────────────────────────────────
['Фломастеры 12цв ГАММА "Colour World"','flomastery','ГАММА','050417_12',95.00,600,90,
 'Набор фломастеров 12 цветов. Вентилируемый колпачок. Безопасные для детей. Яркие стойкие чернила.',
 ['Количество в упаковке'=>'12 цв','Тип'=>'Фломастеры','Вес, г'=>'90','Страна производства'=>'Россия'],null,null],

['Фломастеры 24цв СТАММ','flomastery','СТАММ','ФЦ24_01',145.00,400,120,
 'Фломастеры СТАММ 24 цвета. Нейлоновый стержень, вентилируемый колпачок.',
 ['Количество в упаковке'=>'24 цв','Тип'=>'Фломастеры','Вес, г'=>'120'],null,null],

['Набор фломастеров 18цв Hatber','flomastery','Hatber','ФЦ18_03',115.00,500,105,
 'Фломастеры 18 цветов в пластиковом пенале. Яркие цвета. Нейлоновые стержни.',
 ['Количество в упаковке'=>'18 цв','Тип'=>'Фломастеры','Вес, г'=>'105'],null,null],

['Фломастеры 6цв смываемые ГАММА','flomastery','ГАММА','050417_6W',75.00,700,60,
 'Смываемые фломастеры ГАММА 6 цветов. Легко отстирываются с одежды. Для малышей от 3 лет.',
 ['Количество в упаковке'=>'6 цв','Тип'=>'Смываемые фломастеры','Вес, г'=>'60'],null,null],

// ── Краски ────────────────────────────────────────────────────────
['Акварель 12цв ГАММА "Классика" кювета','kraski','ГАММА','100103_12',95.00,500,80,
 'Акварельные краски ГАММА 12 цветов. Кювета. Пластиковая коробка с кистью. Яркие, прозрачные.',
 ['Количество в упаковке'=>'12 цв','Тип'=>'Акварель','Вес, г'=>'80','Страна производства'=>'Россия'],null,null],

['Акварель 18цв ГАММА "Мастер" кювета','kraski','ГАММА','100103_18',185.00,300,130,
 'Профессиональная акварель 18 цветов. Кювета. Яркие пигменты, высокое светостойкость.',
 ['Количество в упаковке'=>'18 цв','Тип'=>'Акварель','Вес, г'=>'130'],null,null],

['Гуашь 12цв×20мл СТАММ','kraski','СТАММ','ГУ12_20',145.00,400,280,
 'Гуашь СТАММ 12 цветов по 20мл. Пластиковые флаконы. Кроющие, яркие краски для рисования.',
 ['Количество в упаковке'=>'12 цв по 20мл','Тип'=>'Гуашь','Вес, г'=>'280'],null,null],

['Гуашь 6цв×20мл ГАММА "Студия"','kraski','ГАММА','100105_06',89.00,500,145,
 'Гуашь ГАММА 6 основных цветов по 20мл. Флаконы с крышками. Яркие насыщенные цвета.',
 ['Количество в упаковке'=>'6 цв по 20мл','Тип'=>'Гуашь','Вес, г'=>'145'],null,null],

// ── Кисти и инструменты ──────────────────────────────────────────
['Набор кистей художественных №1-12 ГАММА','kisti-instrumenty','ГАММА','200604_10',195.00,250,60,
 'Набор из 10 кистей синтетических №1-12. Для акварели и гуаши. Металлическая обойма.',
 ['Количество в упаковке'=>'10 шт','Тип'=>'Набор кистей','Материал'=>'Синтетика','Вес, г'=>'60'],null,null],

['Кисть художественная флейц 25мм ГАММА','kisti-instrumenty','ГАММА','200601_25',75.00,350,25,
 'Плоская кисть-флейц 25мм. Синтетический ворс. Для гуаши, акрила, акварели.',
 ['Тип'=>'Флейц','Ширина'=>'25мм','Материал'=>'Синтетика','Вес, г'=>'25'],null,null],

['Мольберт настольный Brauberg "Художник"','kisti-instrumenty','Brauberg','191666',580.00,80,450,
 'Настольный деревянный мольберт для рисования. Высота регулируется. Поверхность 45×30см.',
 ['Тип'=>'Мольберт настольный','Материал'=>'Дерево','Вес, г'=>'450'],null,null],

['Палитра для красок Brauberg','kisti-instrumenty','Brauberg','190938',45.00,400,35,
 'Палитра пластиковая с 18 ячейками. Для акварели, гуаши, акрила. Лёгкое очищение.',
 ['Тип'=>'Палитра','Количество ячеек'=>'18','Материал'=>'Пластик','Вес, г'=>'35'],null,null],

// ── Пластилин ─────────────────────────────────────────────────────
['Пластилин Berlingo 12цв "Классика"','plastilin-lepka','Berlingo','LSp_12022',95.00,600,200,
 'Пластилин 12 цветов. Не прилипает к рукам. Хорошо лепится при комнатной температуре. Без запаха.',
 ['Количество в упаковке'=>'12 цв','Тип'=>'Пластилин','Вес, г'=>'200','Страна производства'=>'Россия'],null,null],

['Пластилин ГАММА "Классика" 18цв','plastilin-lepka','ГАММА','280012_18',145.00,400,360,
 'Пластилин ГАММА 18 цветов 360г. Мягкий и пластичный. Не пачкает руки. Для лепки от 3 лет.',
 ['Количество в упаковке'=>'18 цв','Тип'=>'Пластилин','Вес, г'=>'360'],null,null],

['Масса для лепки Hatber 6цв','plastilin-lepka','Hatber','МЛ6_01',115.00,500,180,
 'Масса для лепки 6 цветов. Мягкая, не высыхает. Аналог пластилина. Безопасна для детей.',
 ['Количество в упаковке'=>'6 цв','Тип'=>'Масса для лепки','Вес, г'=>'180'],null,null],

// ── Степлеры и скрепки ───────────────────────────────────────────
['Степлер №24/6 Berlingo "Defender"','stepjery-skrePki','Berlingo','SNs_24601',385.00,150,320,
 'Степлер №24/6 для скрепления до 25 листов. Металлический корпус с резиновым основанием. Антистеплер в комплекте.',
 ['Тип скоб'=>'№24/6','Вместимость'=>'25 листов','Материал'=>'Металл','Вес, г'=>'320'],null,null],

['Степлер №10 Attache мини','stepjery-skrePki','Attache','AT_S10',145.00,300,85,
 'Компактный степлер №10. До 12 листов. Пластиковый корпус. Подходит для работы и учёбы.',
 ['Тип скоб'=>'№10','Вместимость'=>'12 листов','Материал'=>'Пластик','Вес, г'=>'85'],null,null],

['Скрепки канцелярские 28мм 100шт Berlingo','stepjery-skrePki','Berlingo','MKf_28100',38.00,2000,45,
 'Скрепки металлические 28мм, 100 штук. Оцинкованная сталь. Не ржавеют. Коробка.',
 ['Размер'=>'28мм','Количество в упаковке'=>'100 шт','Материал'=>'Оцинкованная сталь'],'pack',null],

['Скрепки 50мм 100шт OfficeSpace','stepjery-skrePki','OfficeSpace','MKf_50100',55.00,1500,70,
 'Скрепки канцелярские 50мм 100 штук. Для пачки до 50 листов. Металл.',
 ['Размер'=>'50мм','Количество в упаковке'=>'100 шт','Материал'=>'Металл'],'pack',null],

// ── Дыроколы ─────────────────────────────────────────────────────
['Дырокол Berlingo "Ranger" 20л','dyrokoly','Berlingo','PRn_20001',420.00,120,480,
 'Дырокол на 20 листов. Металлический механизм. Контейнер для конфетти. Линейка формата.',
 ['Вместимость'=>'20 листов','Материал'=>'Металл+пластик','Вес, г'=>'480'],null,null],

['Дырокол Erich Krause 10л пластик','dyrokoly','Erich Krause','EK_PU_10',185.00,250,155,
 'Компактный дырокол 10 листов. Пластиковый корпус. Контейнер для отходов.',
 ['Вместимость'=>'10 листов','Материал'=>'Пластик','Вес, г'=>'155'],null,null],

['Дырокол 30л металл OfficeSpace','dyrokoly','OfficeSpace','PU_30_OS',580.00,80,720,
 'Усиленный металлический дырокол на 30 листов. Сменные прижимные наконечники.',
 ['Вместимость'=>'30 листов','Материал'=>'Металл','Вес, г'=>'720'],null,null],

// ── Калькуляторы ──────────────────────────────────────────────────
['Калькулятор настольный 12-разр Berlingo "Hyper"','kalykulyatory','Berlingo','CEB_530',485.00,100,280,
 'Настольный калькулятор 12 разрядов. Двойное питание (батарея+солнечная ячейка). Большой экран.',
 ['Разрядность'=>'12','Тип'=>'Настольный','Питание'=>'Батарея + солнечная ячейка','Вес, г'=>'280'],null,null],

['Калькулятор карманный 8-разр СТАММ','kalykulyatory','СТАММ','КК_08',145.00,300,75,
 'Карманный калькулятор 8 разрядов. Солнечная батарея. Размер 100×65мм.',
 ['Разрядность'=>'8','Тип'=>'Карманный','Питание'=>'Солнечная ячейка','Вес, г'=>'75'],null,null],

['Калькулятор бухгалтерский 16-разр Erich Krause','kalykulyatory','Erich Krause','EK_DC816',780.00,60,380,
 'Бухгалтерский калькулятор 16 разрядов. Налог, наценка, константа памяти. Большие клавиши.',
 ['Разрядность'=>'16','Тип'=>'Бухгалтерский','Вес, г'=>'380'],null,null],

// ── Линейки и транспортиры ────────────────────────────────────────
['Линейка 30см пластик Berlingo "Cristal"','lineyki-transportiry','Berlingo','LN_30Bp',28.00,1000,18,
 'Линейка пластиковая 30см с миллиметровой шкалой. Прозрачный пластик. Двусторонняя шкала.',
 ['Длина'=>'30см','Материал'=>'Пластик','Страна производства'=>'Россия'],'pack',null],

['Транспортир 180° СТАММ 10см','lineyki-transportiry','СТАММ','ТР10_01',18.00,1500,12,
 'Транспортир полукруглый 10см, 180°. Прозрачный пластик. Шкала 0-180°.',
 ['Размер'=>'10см','Тип'=>'Транспортир 180°','Материал'=>'Пластик'],null,null],

['Набор линейка+угольники+транспортир Berlingo','lineyki-transportiry','Berlingo','LN_SET4',75.00,500,65,
 'Набор геометрический: линейка 20см, 2 угольника (30/60° и 45°), транспортир 180°. Прозрачный пластик.',
 ['Количество в упаковке'=>'4 предмета','Тип'=>'Набор геометрический','Материал'=>'Пластик','Вес, г'=>'65'],null,null],

['Линейка 20см металлическая Brauberg','lineyki-transportiry','Brauberg','210853',55.00,700,32,
 'Металлическая линейка 20см. Нержавеющая сталь. Двусторонняя шкала мм/дюймы.',
 ['Длина'=>'20см','Материал'=>'Металл','Вес, г'=>'32'],null,null],

// ── Ластики и точилки ─────────────────────────────────────────────
['Ластик Berlingo "Ultra" белый','lastiki-tochilki','Berlingo','BLr_10',12.00,2000,8,
 'Ластик из натурального каучука. Мягкий. Не оставляет следов. Для карандаша и ручки.',
 ['Материал'=>'Натуральный каучук','Тип'=>'Для карандаша','Вес, г'=>'8','Страна производства'=>'Россия'],'pack',null],

['Ластик Hatber "Neon" ассорти','lastiki-tochilki','Hatber','К-3НС',8.00,3000,6,
 'Ластик неоновых цветов. Мягкий, хорошо стирает. Цвет в ассортименте.',
 ['Материал'=>'Синтетический каучук','Тип'=>'Для карандаша','Вес, г'=>'6'],'pack',null],

['Точилка с контейнером Berlingo "Cristal"','lastiki-tochilki','Berlingo','BSh_01',25.00,1500,15,
 'Точилка пластиковая с прозрачным контейнером для стружки. Одинарная. Лезвие из нержавеющей стали.',
 ['Тип'=>'Точилка с контейнером','Лезвие'=>'Нержавеющая сталь','Вес, г'=>'15'],'pack',null],

['Точилка двойная СТАММ','lastiki-tochilki','СТАММ','ТЧД_01',18.00,2000,10,
 'Двойная точилка для карандашей стандарт и jumbo. Металлическое лезвие.',
 ['Тип'=>'Двойная точилка','Лезвие'=>'Металл','Вес, г'=>'10'],'pack',null],

// ── Пеналы ────────────────────────────────────────────────────────
['Пенал 1 отд. Berlingo "Neo" мягкий','penaly','Berlingo','PM_neo01',245.00,200,85,
 'Пенал мягкий 1 отделение. Застёжка на молнии. Размер 19×8×4см. Материал: полиэстер.',
 ['Тип'=>'Мягкий пенал','Отделений'=>'1','Вес, г'=>'85','Страна производства'=>'Россия'],null,null],

['Пенал 3 отд. СТАММ "Пикассо"','penaly','СТАММ','ПК3_08',320.00,150,110,
 'Мягкий пенал 3 отделения. Застёжки на молниях. Размер 20×10×6см.',
 ['Тип'=>'Мягкий пенал','Отделений'=>'3','Вес, г'=>'110'],null,null],

['Пенал-тубус Hatber "ColorPics"','penaly','Hatber','ПТ_04',185.00,250,95,
 'Пенал-тубус с поворотным механизмом. Закрывается на застёжку. 1 отделение.',
 ['Тип'=>'Пенал-тубус','Отделений'=>'1','Вес, г'=>'95'],null,null],
];

// ── Product Insert Function ───────────────────────────────────────
function insertProduct(mysqli $db, string $p, int $LANG, int $WC, int $LC, int $SS, int $TC,
    array $MID, array $CID, array $AID, array $OPTID, array $OVAL,
    array $CAT_RGB, string $imgBase, array $prod): int
{
    [$name,$catSeo,$brand,$model,$price,$qty,$weight,$desc,$attrs,$optType,$special] = $prod;
    $mid = isset($MID[$brand]) ? $MID[$brand] : 0;
    $cid = isset($CID[$catSeo]) ? $CID[$catSeo] : 0;
    if (!$cid) { say("  SKIP (no category '{$catSeo}'): {$name}"); return 0; }
    // Category RGB
    $mainCatRgbs = ['ruchki','tetradi','papki','bumaga','nozhnicy','kley','markery','hudozh','ofisnye','shkolnye'];
    $rgb = [100,150,200];
    foreach ($mainCatRgbs as $rk) {
        if (strpos($catSeo, substr($rk,0,5)) !== false) { $rgb = $CAT_RGB[$rk]; break; }
    }
    // Try to match any CAT_RGB key
    foreach ($CAT_RGB as $rk => $rv) {
        if (strpos($catSeo, $rk) !== false) { $rgb=$rv; break; }
    }
    // Generate image
    $imgSlug = preg_replace('/[^a-z0-9_-]/','',strtolower(str_replace([' ','"'],['_',''],$model)));
    $img = makeImg("{$imgBase}/prod_{$imgSlug}.png", $rgb, $name, 800, 800);
    $ean = ean13();
    $sku = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/','',$brand),0,3)) . '-' . str_pad(mt_rand(1000,9999),4,'0',STR_PAD_LEFT);
    // Insert product
    $id = ins($db,"INSERT INTO {$p}product (model,sku,upc,ean,jan,isbn,mpn,location,quantity,minimum,subtract,stock_status_id,date_available,manufacturer_id,shipping,price,points,weight,weight_class_id,length,width,height,length_class_id,status,noindex,tax_class_id,sort_order,date_added,date_modified)
        VALUES ('" . e($db,$model) . "','" . e($db,$sku) . "','','" . e($db,$ean) . "','','','','',{$qty},1,1,{$SS},NOW(),{$mid},1," . number_format($price,4,'.','') . ",0,{$weight},{$WC},0,0,0,{$LC},1,0,{$TC},0,NOW(),NOW())");
    $db->query("UPDATE {$p}product SET image='" . e($db,$img) . "' WHERE product_id={$id}");
    // Description
    // Tag: ASCII-only to avoid utf8/utf8mb4 column mismatch
    $tag = preg_replace('/[^\x20-\x7E]/', '', strtolower($brand . ' ' . $model));
    $metaDesc = 'Купить ' . mb_strtolower($name) . ' оптом в магазине Эврика. ' . mb_substr($desc,0,100) . '.';
    ins($db,"INSERT INTO {$p}product_description (product_id,language_id,name,description,tag,meta_title,meta_h1,meta_description,meta_keyword)
        VALUES ({$id},{$LANG},'" . e($db,$name) . "','" . e($db,'<p>'.$desc.'</p>') . "','" . e($db,$tag) . "','" . e($db,$name.' купить оптом') . "','" . e($db,$name) . "','" . e($db,$metaDesc) . "','" . e($db,mb_strtolower($brand.', '.$name.',канцелярия оптом')) . "')");
    ins($db,"INSERT INTO {$p}product_to_store (product_id,store_id) VALUES ({$id},0)");
    ins($db,"INSERT INTO {$p}product_to_category (product_id,category_id,main_category) VALUES ({$id},{$cid},1)");
    // SEO URL
    $seoKey = preg_replace('/[^a-z0-9-]/','',strtolower(str_replace([' ','"','«','»'],'-',$name)));
    $seoKey = substr($seoKey,0,60) . '-' . $id;
    $db->query("INSERT IGNORE INTO {$p}seo_url (store_id,language_id,query,keyword) VALUES (0,{$LANG},'product_id={$id}','" . e($db,$seoKey) . "')");
    // Attributes
    $as=1;
    foreach ($attrs as $aname => $aval) {
        if (isset($AID[$aname]) && $aval) {
            ins($db,"INSERT INTO {$p}product_attribute (product_id,attribute_id,language_id,text) VALUES ({$id},{$AID[$aname]},{$LANG},'" . e($db,$aval) . "')");
        }
        $as++;
    }
    // Option: color
    if ($optType === 'color' && isset($OPTID['Цвет'])) {
        $oid = $OPTID['Цвет'];
        $poid = ins($db,"INSERT INTO {$p}product_option (product_id,option_id,value,required) VALUES ({$id},{$oid},'',0)");
        $prices=[0,5,3,7,10]; $vi=0;
        foreach (['Синий','Красный','Чёрный','Зелёный'] as $vname) {
            if (!isset($OVAL['Цвет'][$vname])) continue;
            $ovid=$OVAL['Цвет'][$vname];
            $pp=$prices[$vi]??0; $vi++;
            ins($db,"INSERT INTO {$p}product_option_value (product_option_id,product_id,option_id,option_value_id,quantity,subtract,price,price_prefix,points,points_prefix,weight,weight_prefix) VALUES ({$poid},{$id},{$oid},{$ovid},{$qty},1,{$pp},'+',0,'+',0,'+')");
        }
    }
    // Option: pack
    if ($optType === 'pack' && isset($OPTID['Фасовка'])) {
        $oid=$OPTID['Фасовка'];
        $poid=ins($db,"INSERT INTO {$p}product_option (product_id,option_id,value,required) VALUES ({$id},{$oid},'',0)");
        $packPrices=['1 шт'=>0,'10 шт'=>-5,'20 шт'=>-8,'50 шт'=>-12];
        foreach ($packPrices as $vname=>$disc) {
            if (!isset($OVAL['Фасовка'][$vname])) continue;
            $ovid=$OVAL['Фасовка'][$vname];
            ins($db,"INSERT INTO {$p}product_option_value (product_option_id,product_id,option_id,option_value_id,quantity,subtract,price,price_prefix,points,points_prefix,weight,weight_prefix) VALUES ({$poid},{$id},{$oid},{$ovid},{$qty},1,{$disc},'+',0,'+',0,'+')");
        }
    }
    // Special price (20% off for ~30% of products)
    if ($special || mt_rand(1,10) <= 3) {
        $sp = round($price * 0.80, 2);
        ins($db,"INSERT INTO {$p}product_special (product_id,customer_group_id,priority,price,date_start,date_end) VALUES ({$id},1,1,{$sp},'2026-01-01','2026-12-31')");
    }
    return $id;
}

$productIds = [];
$count = 0;
foreach ($PRODUCTS as $prod) {
    $id = insertProduct($db,$p,$LANG,$WC,$LC,$SS,$TC,$MID,$CID,$AID,$OPTID,$OVAL,$CAT_RGB,$imgBase,$prod);
    if ($id) { $productIds[] = $id; $count++; say("  + [{$id}] {$prod[0]}"); }
}
say("Products inserted: {$count}");

// ── Related Products ─────────────────────────────────────────────
say("\n=== RELATED PRODUCTS ===");
// Link products in same category
$catProducts = [];
foreach ($PRODUCTS as $i => $prod) {
    $cSeo = $prod[1];
    $catProducts[$cSeo][] = $i;
}
$relCount=0;
foreach ($catProducts as $cSeo => $indices) {
    if (count($indices) < 2) continue;
    for ($i=0;$i<count($indices);$i++) {
        $pid = $productIds[$indices[$i]] ?? 0;
        if (!$pid) continue;
        for ($j=$i+1;$j<min($i+3,count($indices));$j++) {
            $rid = $productIds[$indices[$j]] ?? 0;
            if (!$rid) continue;
            $db->query("INSERT IGNORE INTO {$p}product_related (product_id,related_id) VALUES ({$pid},{$rid})");
            $db->query("INSERT IGNORE INTO {$p}product_related (product_id,related_id) VALUES ({$rid},{$pid})");
            $relCount++;
        }
    }
}
say("Related links: {$relCount}");

// ── Category Filters ─────────────────────────────────────────────
say("\n=== CATEGORY FILTERS ===");
foreach ($CID as $seo => $cid) {
    if (!$cid) continue;
    foreach ($FID as $fname => $fid) {
        if (in_array($fname, ['Berlingo','Brauberg','ГАММА','СТАММ','OfficeSpace','Erich Krause','Hatber'])) {
            $db->query("INSERT IGNORE INTO {$p}category_filter (category_id,filter_id) VALUES ({$cid},{$fid})");
        }
    }
}
say("Filters assigned to categories.");

// ── Slideshow Banners ─────────────────────────────────────────────
say("\n=== SLIDESHOW BANNERS ===");
// banner_id=7 is the home slideshow
$BANNER_ID = 7;

$slides = [
    ['Новинки сезона 2026','Школьные товары, канцелярия и товары для творчества',
     'index.php?route=product/search&sort=date_added&order=DESC','15 мая 2026','slide','slide_1'],
    ['Berlingo — бестселлеры','Ручки, тетради, папки и аксессуары лидера рынка',
     'index.php?route=product/search&search=Berlingo','10 мая 2026','ruchki','slide_2'],
    ['Бумага оптом','А4, А3, цветная — от 5 пачек со скидкой до 15%',
     'index.php?route=product/category&path=','5 мая 2026','bumaga','slide_3'],
    ['Художественные товары','Краски ГАММА, кисти, пластилин, мольберты',
     'index.php?route=product/category&path=','1 мая 2026','hudozh','slide_4'],
    ['Маркеры и фломастеры','Перманентные, для доски, фломастеры 6-24 цвета',
     'index.php?route=product/category&path=','25 апреля 2026','markery','slide_5'],
    ['Офисная канцелярия','Степлеры, дыроколы, калькуляторы, папки-регистраторы',
     'index.php?route=product/category&path=','20 апреля 2026','ofisnye','slide_6'],
];

$slideSort = $db->query("SELECT MAX(sort_order)+1 as ns FROM {$p}banner_image WHERE banner_id={$BANNER_ID}")->fetch_assoc();
$ns = (int)($slideSort['ns'] ?? 0);

foreach ($slides as $sl) {
    [$title,$sdesc,$link,$sdate,$rgb_key,$fname] = $sl;
    $img = makeImg("{$imgBase}/{$fname}.png", $CAT_RGB[$rgb_key] ?? $CAT_RGB['slide'], $title, 1140, 380);
    ins($db,"INSERT INTO {$p}banner_image (banner_id,language_id,title,link,description,slide_date,image,sort_order)
        VALUES ({$BANNER_ID},{$LANG},'" . e($db,$title) . "','" . e($db,$link) . "','" . e($db,$sdesc) . "','" . e($db,$sdate) . "','" . e($db,$img) . "',{$ns})");
    say("  + slide: {$title}");
    $ns++;
}

// ── Promo Banner Settings ─────────────────────────────────────────
say("\n=== PROMO SETTINGS ===");
$promoData = [
    'evrika_promo_banner1_title' => 'Канцелярия для офиса и школы',
    'evrika_promo_banner1_desc'  => 'Оптовые поставки — от 1 коробки',
    'evrika_promo_banner1_icon'  => 'briefcase',
    'evrika_promo_banner2_title' => 'Коллекция 2026',
    'evrika_promo_banner2_desc'  => 'Новинки сезона — уже в каталоге',
    'evrika_promo_banner2_icon'  => 'tag',
];
foreach ($promoData as $key => $val) {
    $db->query("DELETE FROM {$p}setting WHERE code='evrika_promo' AND `key`='" . e($db,$key) . "'");
    ins($db,"INSERT INTO {$p}setting (store_id,code,`key`,value,serialized) VALUES (0,'evrika_promo','" . e($db,$key) . "','" . e($db,$val) . "',0)");
    say("  {$key} = {$val}");
}

// ── Summary ───────────────────────────────────────────────────────
say("\n" . str_repeat('=',60));
say("SEED COMPLETE");
say(str_repeat('=',60));
say("Manufacturers : " . count($MID));
say("Categories    : " . count(array_filter($CID)));
say("Products      : {$count}");
say("Related links : {$relCount}");
say("Slideshow     : " . count($slides) . " slides added to banner_id={$BANNER_ID}");
say("Images stored : " . ROOT . "/image/catalog/seed/");
say("\nApply OCMods in Admin → Extensions → Modifications → Refresh");
say("Clear cache:  Admin → Dashboard → Settings (cog) → Refresh");

if (PHP_SAPI !== 'cli') echo '</pre></body></html>';
