<?php
/**
 * ColumnDetector — автоопределение строки-заголовка и маппинга столбцов XLS-файла
 * PHP 7.4 compatible
 */
class EvrikaColumnDetector {

    /**
     * Словарь: ключевые слова → поле OcStore
     * Порядок важен: первое совпадение побеждает
     */
    private function getDictionary() {
        return array(
            'name'         => array('товар', 'наименование', 'название', 'name', 'item', 'product', 'номенклатура', 'продукт', 'описание товара', 'наим'),
            'model'        => array('код товара', 'код', 'code', 'кодтовара', 'артикул поставщика', 'код пост'),
            'sku'          => array('артикул', 'sku', 'арт', 'art', 'article'),
            'upc'          => array('штрихкод', 'шк', 'barcode', 'upc', 'ean-13', 'ean13', 'ean', 'гтин', 'gtin', 'штрих'),
            'manufacturer' => array('бренд', 'brand', 'производитель', 'manufacturer', 'торговая марка', 'тм', 'марка'),
            'price'        => array('цена', 'price', 'стоимость', 'цена опт', 'цена розн', 'прайс', 'цена с ндс', 'цена без ндс', 'розница', 'опт'),
            'quantity'     => array('количество', 'кол-во', 'кол во', 'qty', 'quantity', 'остаток', 'склад', 'stock', 'наличие'),
            'description'  => array('описание', 'description', 'характеристики', 'комментарий', 'примечание'),
            'category'     => array('категория', 'category', 'раздел', 'группа', 'группа товара', 'подгруппа'),
            'meta_title'   => array('seo заголовок', 'meta title', 'seo title'),
            'status'       => array('статус', 'status', 'активен', 'active'),
            'weight'       => array('вес', 'weight', 'масса', 'вес кг'),
            'sort_order'   => array('сортировка', 'sort', 'порядок', 'sort_order'),
            'minimum'      => array('мин кол', 'минимум', 'minimum', 'мин. кол-во', 'минимальное количество'),
            'tag'          => array('теги', 'tags', 'тег', 'tag', 'ключевые слова'),
        );
    }

    /**
     * Найти строку-заголовок в массиве строк файла.
     * Возвращает индекс строки (0-based).
     * Сканирует первые $scanRows строк.
     *
     * @param  array $rows     Все строки файла (array of arrays)
     * @param  int   $scanRows Сколько строк сканировать
     * @return int
     */
    public function detectHeaderRow(array $rows, $scanRows = 30) {
        $dictionary = $this->getDictionary();

        // Собираем все ключевые слова в плоский список
        $keywords = array();
        foreach ($dictionary as $field => $synonyms) {
            foreach ($synonyms as $syn) {
                $keywords[] = $syn;
            }
        }

        $limit = min($scanRows, count($rows));
        $bestRow   = 0;
        $bestScore = -1;

        for ($i = 0; $i < $limit; $i++) {
            $row = $rows[$i];
            if (empty($row)) {
                continue;
            }

            $score = 0;

            // Очков за каждую непустую ячейку
            $nonEmpty = 0;
            foreach ($row as $cell) {
                $val = trim((string)$cell);
                if ($val !== '') {
                    $nonEmpty++;
                }
            }

            if ($nonEmpty < 2) {
                continue; // строка слишком пустая
            }

            // Очков за ключевые слова
            foreach ($row as $cell) {
                $val = mb_strtolower(trim((string)$cell), 'UTF-8');
                if ($val === '') {
                    continue;
                }
                foreach ($keywords as $kw) {
                    if (strpos($val, $kw) !== false) {
                        $score += 10;
                        break; // одна ячейка — одно очко
                    }
                }
            }

            // Очков за количество непустых ячеек (меньший вес)
            $score += $nonEmpty;

            // Бонус: строка ближе к началу файла
            $score += max(0, 5 - $i);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow   = $i;
            }
        }

        return $bestRow;
    }

    /**
     * Для каждого столбца строки-заголовка предложить поле OcStore.
     * Возвращает массив: [ colIndex => 'fieldName' | null, ... ]
     *
     * @param  array $headerRow Строка с заголовками столбцов
     * @return array
     */
    public function suggestMapping(array $headerRow) {
        $result = array();
        $usedFields = array(); // предотвращаем дублирование

        foreach ($headerRow as $index => $cell) {
            $val = mb_strtolower(trim((string)$cell), 'UTF-8');
            if ($val === '') {
                $result[$index] = null;
                continue;
            }

            $matched = $this->matchColumn($val, $usedFields);
            $result[$index] = $matched;

            if ($matched !== null) {
                $usedFields[$matched] = true;
            }
        }

        return $result;
    }

    /**
     * Сопоставить строку с полем OcStore.
     *
     * @param  string $colName    Название столбца (lowercase, trimmed)
     * @param  array  $usedFields Уже использованные поля
     * @return string|null
     */
    private function matchColumn($colName, array $usedFields) {
        $dictionary = $this->getDictionary();

        // 1. Точное совпадение
        foreach ($dictionary as $field => $synonyms) {
            if (isset($usedFields[$field])) {
                continue;
            }
            foreach ($synonyms as $syn) {
                if ($colName === $syn) {
                    return $field;
                }
            }
        }

        // 2. Вхождение подстроки (colName содержит ключевое слово)
        foreach ($dictionary as $field => $synonyms) {
            if (isset($usedFields[$field])) {
                continue;
            }
            foreach ($synonyms as $syn) {
                if (strpos($colName, $syn) !== false) {
                    return $field;
                }
            }
        }

        // 3. Нечёткое совпадение через levenshtein (только для коротких слов)
        $bestField    = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($dictionary as $field => $synonyms) {
            if (isset($usedFields[$field])) {
                continue;
            }
            foreach ($synonyms as $syn) {
                // levenshtein работает с ASCII, для кириллицы конвертируем
                $distance = $this->levenshteinUtf8($colName, $syn);
                $maxLen   = max(mb_strlen($colName, 'UTF-8'), mb_strlen($syn, 'UTF-8'));

                // Принимаем совпадение только если редакционное расстояние <= 20% длины
                if ($maxLen > 0 && $distance <= max(1, (int)($maxLen * 0.2))) {
                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $bestField    = $field;
                    }
                }
            }
        }

        return $bestField;
    }

    /**
     * levenshtein с поддержкой UTF-8 (через mb_str_split аналог для PHP 7.4)
     *
     * @param  string $s1
     * @param  string $s2
     * @return int
     */
    private function levenshteinUtf8($s1, $s2) {
        $a1 = $this->mbStrSplit($s1);
        $a2 = $this->mbStrSplit($s2);

        $len1 = count($a1);
        $len2 = count($a2);

        // Используем стандартный алгоритм на массивах символов
        $dp = array();
        for ($i = 0; $i <= $len1; $i++) {
            $dp[$i][0] = $i;
        }
        for ($j = 0; $j <= $len2; $j++) {
            $dp[0][$j] = $j;
        }

        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = ($a1[$i - 1] === $a2[$j - 1]) ? 0 : 1;
                $dp[$i][$j] = min(
                    $dp[$i - 1][$j] + 1,
                    $dp[$i][$j - 1] + 1,
                    $dp[$i - 1][$j - 1] + $cost
                );
            }
        }

        return $dp[$len1][$len2];
    }

    /**
     * mb_str_split совместимый с PHP 7.4 (функция добавлена в PHP 7.4, но для надёжности пишем сами)
     *
     * @param  string $str
     * @return array
     */
    private function mbStrSplit($str) {
        $result = array();
        $len    = mb_strlen($str, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $result[] = mb_substr($str, $i, 1, 'UTF-8');
        }
        return $result;
    }

    /**
     * Получить список всех доступных полей OcStore с человекочитаемыми названиями.
     * Используется для построения <select> в форме маппинга.
     *
     * @return array [ 'fieldKey' => 'Человекочитаемое название', ... ]
     */
    public function getAvailableFields() {
        return array(
            ''             => '— Не импортировать —',
            'name'         => 'Название товара',
            'model'        => 'Код товара (model)',
            'sku'          => 'SKU / Артикул',
            'upc'          => 'UPC / Штрихкод',
            'ean'          => 'EAN',
            'manufacturer' => 'Бренд / Производитель',
            'price'        => 'Цена',
            'quantity'     => 'Количество (остаток)',
            'category'     => 'Категория',
            'description'  => 'Описание',
            'meta_title'   => 'SEO-заголовок (meta title)',
            'meta_description' => 'SEO-описание (meta description)',
            'status'       => 'Статус (0/1)',
            'minimum'      => 'Минимальное количество для заказа',
            'weight'       => 'Вес',
            'sort_order'   => 'Порядок сортировки',
            'tag'          => 'Теги',
        );
    }
}
