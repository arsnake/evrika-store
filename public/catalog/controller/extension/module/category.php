<?php
class ControllerExtensionModuleCategory extends Controller {
	public function index() {
		$this->load->language('extension/module/category');

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		if (isset($parts[0])) {
			$data['category_id'] = $parts[0];
		} else {
			$data['category_id'] = 0;
		}

		if (isset($parts[1])) {
			$data['child_id'] = $parts[1];
		} else {
			$data['child_id'] = 0;
		}

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			$filter_data = array(
				'filter_category_id'  => $category['category_id'],
				'filter_sub_category' => true
			);

			$total = $this->model_catalog_product->getTotalProducts($filter_data);

			if ($total || !$this->config->get('config_product_count')) {
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = array(
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					);

					$child_total = $this->model_catalog_product->getTotalProducts($filter_data);

					if ($child_total || !$this->config->get('config_product_count')) {
						$children_data[] = array(
							'category_id' => $child['category_id'],
							'name'        => $child['name'],
							'href'        => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
						);
					}
				}

				if (isset($this->request->post['image'])) {
					$image = $this->model_tool_image->resize($this->request->post['image'], $this->config->get('module_category_image_width') ? $this->config->get('module_category_image_width') : 47, $this->config->get('module_category_image_height') ? $this->config->get('module_category_image_height') : 47);
				} elseif ($category['image']) {
					$image = $this->model_tool_image->resize($category['image'], $this->config->get('module_category_image_width') ? $this->config->get('module_category_image_width') : 47, $this->config->get('module_category_image_height') ? $this->config->get('module_category_image_height') : 47);
				} else {
					$image = $this->model_tool_image->resize('no_image.png', $this->config->get('module_category_image_width') ? $this->config->get('module_category_image_width') : 47, $this->config->get('module_category_image_height') ? $this->config->get('module_category_image_height') : 47);
				}

				$data['categories'][] = array(
					'category_id' => $category['category_id'],
					'name'        => $category['name'],
					'image'       => $image,
					'children'    => $children_data,
					'href'        => $this->url->link('product/category', 'path=' . $category['category_id'])
				);
			}
		}

		return $this->load->view('extension/module/category', $data);
	}
}
