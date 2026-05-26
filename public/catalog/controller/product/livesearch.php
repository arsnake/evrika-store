<?php
/**
 * Live Search — возвращает JSON с товарами и категориями.
 * Маршрут: index.php?route=product/livesearch&q=...
 */
class ControllerProductLivesearch extends Controller {

    public function index() {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: no-store');

        $q = isset($this->request->get['q']) ? trim($this->request->get['q']) : '';

        if (mb_strlen($q, 'UTF-8') < 3) {
            $this->response->setOutput(json_encode(['products' => [], 'categories' => []]));
            return;
        }

        $language_id = (int)$this->config->get('config_language_id');
        $store_id    = (int)$this->config->get('config_store_id');
        $currency    = isset($this->session->data['currency'])
            ? $this->session->data['currency']
            : $this->config->get('config_currency');

        $like = '%' . $this->db->escape($q) . '%';

        // ── Товары ─────────────────────────────────────────────────
        $this->load->model('tool/image');

        $sql = "
            SELECT DISTINCT p.product_id, pd.name, p.model, p.image,
                   p.price, p.tax_class_id
            FROM `" . DB_PREFIX . "product` p
            INNER JOIN `" . DB_PREFIX . "product_description` pd
                ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "')
            INNER JOIN `" . DB_PREFIX . "product_to_store` p2s
                ON (p2s.product_id = p.product_id AND p2s.store_id = '" . $store_id . "')
            WHERE p.status = '1'
              AND (pd.name LIKE '" . $like . "' OR p.model LIKE '" . $like . "')
            ORDER BY pd.name ASC
            LIMIT 8
        ";

        $products_result = $this->db->query($sql);
        $products = [];

        foreach ($products_result->rows as $row) {
            $price = (float)$row['price'];
            if ($row['tax_class_id']) {
                $price = $this->tax->calculate($price, $row['tax_class_id'], $this->config->get('config_tax'));
            }

            $image = '';
            if (!empty($row['image'])) {
                try {
                    $image = $this->model_tool_image->resize($row['image'], 60, 60);
                } catch (Exception $e) {
                    $image = '';
                }
            }

            // Строим полный путь категории для правильных хлебных крошек
            $cat_path = $this->getProductCategoryPath((int)$row['product_id']);
            $link_args = $cat_path
                ? 'path=' . $cat_path . '&product_id=' . $row['product_id']
                : 'product_id=' . $row['product_id'];

            $products[] = [
                'id'    => (int)$row['product_id'],
                'name'  => $row['name'],
                'sku'   => $row['model'],
                'price' => $this->currency->format($price, $currency),
                'image' => $image,
                'href'  => html_entity_decode($this->url->link('product/product', $link_args))
            ];
        }

        // ── Категории ──────────────────────────────────────────────
        $sql = "
            SELECT DISTINCT c.category_id, cd.name
            FROM `" . DB_PREFIX . "category` c
            INNER JOIN `" . DB_PREFIX . "category_description` cd
                ON (cd.category_id = c.category_id AND cd.language_id = '" . $language_id . "')
            INNER JOIN `" . DB_PREFIX . "category_to_store` c2s
                ON (c2s.category_id = c.category_id AND c2s.store_id = '" . $store_id . "')
            WHERE c.status = '1'
              AND cd.name LIKE '" . $like . "'
            ORDER BY cd.name ASC
            LIMIT 5
        ";

        $cats_result = $this->db->query($sql);
        $categories  = [];

        foreach ($cats_result->rows as $row) {
            $path = $this->buildCategoryPath((int)$row['category_id']);
            $categories[] = [
                'id'   => (int)$row['category_id'],
                'name' => $row['name'],
                'href' => html_entity_decode($this->url->link('product/category', 'path=' . $path))
            ];
        }

        $this->response->setOutput(json_encode([
            'products'   => $products,
            'categories' => $categories
        ]));
    }

    /**
     * Строит полный путь категории от корня до $category_id.
     * Например, для категории 84 (дочерняя 27, внучатая 5) вернёт "5_27_84".
     * Защита от циклических ссылок через $seen.
     */
    private function buildCategoryPath($category_id) {
        $path = [];
        $id   = $category_id;
        $seen = [];

        while ($id > 0) {
            if (isset($seen[$id])) break; // защита от петли
            $seen[$id] = true;

            $r = $this->db->query(
                "SELECT parent_id FROM `" . DB_PREFIX . "category`
                 WHERE category_id = '" . (int)$id . "'"
            );

            if (!$r->num_rows) break;

            array_unshift($path, $id);
            $id = (int)$r->row['parent_id'];
        }

        return implode('_', $path);
    }

    /**
     * Возвращает полный путь категории для товара.
     * Берёт первую (по ID) категорию товара и строит путь до корня.
     * Если категорий нет — возвращает пустую строку.
     */
    private function getProductCategoryPath($product_id) {
        $r = $this->db->query(
            "SELECT category_id FROM `" . DB_PREFIX . "product_to_category`
             WHERE product_id = '" . (int)$product_id . "'
             ORDER BY category_id ASC
             LIMIT 1"
        );

        if (!$r->num_rows) {
            return '';
        }

        return $this->buildCategoryPath((int)$r->row['category_id']);
    }
}
