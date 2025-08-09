<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use FacturaScripts\Plugins\PushWoocommerceProductos\Lib\WooHelper;

/**
 * Para modificar el comportamiento o añadir pestañas o secciones a controladores de otros plugins (o del core)
 * podemos crear una extensión de ese controlador.
 *
 * https://facturascripts.com/publicaciones/extensiones-de-modelos
 */
class EditProducto
{

    public $product; // Will hold created product object
    public $error; // Will hold error message

    public function createViews(): Closure
    {
        return function () {
            $this->addHtmlView('myHtmlView', 'Tab/WooTiendaWeb', 'ProductoImagen', 'shop', 'fas fa-shop');
        };
    }

    public function execAfterAction(): Closure
    {
        return function ($action) {

            if ($action == 'create-wc-product') {
                $this->createWooCommerceProduct();
                return false;
            }

            if ($action == 'update-wc-product') {
                $this->updateWooCommerceProduct();
                return false;
            }

            if ($action == 'get-wc-categories') {
                $this->getWooCommerceCategories();
                return false;
            }

            if ($action == 'create-wc-category') {
                $this->createWooCommerceCategory();
                return false;
            }
        };
    }

    public function execPreviousAction(): Closure
    {
        return function ($action) {

        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            //if ($viewName === 'myHtmlView') { }
        };
    }


    protected function createWooCommerceProduct()
    {
        return function () {
            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getCurrentFsProductId();
                if (!$fsProductId) {
                    return;
                }

                $fsProduct = new Producto();
                if (!$fsProduct->loadFromCode($fsProductId)) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Producto no encontrado en FacturaScripts']]
                    ]);
                    return;
                }

                $validation = $this->validateProductForWooCommerce($fsProduct);
                if (!$validation['valid']) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => $validation['errors']
                    ]);
                    return;
                }

                if (!empty($fsProduct->woo_id)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Este producto ya está sincronizado con WooCommerce (ID: ' . $fsProduct->woo_id . ')']]
                    ]);
                    return;
                }

                $wooProduct = $this->createWooProduct($fsProduct);

                if (!$wooProduct || !isset($wooProduct->id)) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Error al crear producto en WooCommerce']]
                    ]);
                    return;
                }

                $this->updateFsProductWithWooData($fsProduct, $wooProduct);

                if (!$fsProduct->save()) {
                    error_log('Error saving FS product after WooCommerce creation');
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Producto creado en WooCommerce pero error al actualizar FacturaScripts']]
                    ]);
                    return;
                }

                echo json_encode([
                    'success' => true,
                    'product_id' => $wooProduct->id,
                    'product_name' => $wooProduct->name ?? '',
                    'messages' => [
                        ['message' => "Producto creado exitosamente en WooCommerce con ID: {$wooProduct->id}"]
                    ]
                ]);

            } catch (\Exception $e) {
                error_log('Exception in createWooCommerceProduct: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno: ' . $e->getMessage()]]
                ]);
            }
        };
    }

    protected function updateWooCommerceProduct()
    {
        return function () {
            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getCurrentFsProductId();
                if (!$fsProductId) {
                    return;
                }

                $fsProduct = new Producto();
                if (!$fsProduct->loadFromCode($fsProductId)) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Producto no encontrado en FacturaScripts']]
                    ]);
                    return;
                }

                if (empty($fsProduct->woo_id)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Este producto no está sincronizado con WooCommerce']]
                    ]);
                    return;
                }

                // Get form data
                $formData = $this->getWooCommerceFormData();

                // Update WooCommerce product
                $wooProduct = $this->updateWooProduct($fsProduct->woo_id, $formData);

                if (!$wooProduct) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Error al actualizar producto en WooCommerce']]
                    ]);
                    return;
                }

                // Update FS product with new data
                $this->updateFsProductWithFormData($fsProduct, $formData);
                $this->updateFsProductWithWooData($fsProduct, $wooProduct);

                if (!$fsProduct->save()) {
                    error_log('Error saving FS product after WooCommerce update');
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Producto actualizado en WooCommerce pero error al actualizar FacturaScripts']]
                    ]);
                    return;
                }

                echo json_encode([
                    'success' => true,
                    'messages' => [
                        ['message' => "Producto actualizado exitosamente en WooCommerce"]
                    ]
                ]);

            } catch (\Exception $e) {
                error_log('Exception in updateWooCommerceProduct: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno: ' . $e->getMessage()]]
                ]);
            }
        };
    }

    protected function getWooCommerceCategories()
    {
        return function () {
            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $woo_client = WooHelper::getClient();
                $categories = $woo_client->get('products/categories', ['per_page' => 100]);

                echo json_encode([
                    'success' => true,
                    'categories' => $categories
                ]);

            } catch (\Exception $e) {
                error_log('Exception in getWooCommerceCategories: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error al obtener categorías: ' . $e->getMessage()]]
                ]);
            }
        };
    }

    protected function createWooCommerceCategory()
    {
        return function () {
            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $categoryName = $this->request->request->get('category_name');
                if (empty($categoryName)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Nombre de categoría requerido']]
                    ]);
                    return;
                }

                $woo_client = WooHelper::getClient();
                $category = $woo_client->post('products/categories', [
                    'name' => $categoryName
                ]);

                if (!$category || !isset($category->id)) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Error al crear categoría']]
                    ]);
                    return;
                }

                echo json_encode([
                    'success' => true,
                    'category' => $category,
                    'messages' => [['message' => 'Categoría creada exitosamente']]
                ]);

            } catch (\Exception $e) {
                error_log('Exception in createWooCommerceCategory: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno: ' . $e->getMessage()]]
                ]);
            }
        };
    }

    protected function createWooProduct()
    {
        return function ($fsProduct) {
            $product_data = [
                'name' => $fsProduct->descripcion ?? 'Producto sin nombre',
                'type' => 'simple',
                'regular_price' => (string)($fsProduct->precio ?? '0'),
                'description' => $fsProduct->observaciones ?? '',
                'short_description' => $fsProduct->descripcion ?? '',
                'sku' => $fsProduct->referencia ?? '',
                'manage_stock' => true,
                'stock_quantity' => (int)($fsProduct->stockfis ?? 0),
                'status' => 'draft',
                'catalog_visibility' => 'visible'
            ];

            // Add images
            $images = $this->getProductImages($fsProduct->idproducto);
            if (!empty($images)) {
                $product_data['images'] = $images;
            }

            $woo_client = WooHelper::getClient();
            return $woo_client->post('products', $product_data);
        };
    }

    protected function updateWooProduct()
    {
        return function ($wooId, $formData) {
            $woo_client = WooHelper::getClient();
            return $woo_client->put("products/{$wooId}", $formData);
        };
    }

    protected function getProductImages()
    {
        return function ($fsProductId) {
            $images = [];

            $where = [
                new DataBaseWhere('idproducto', $fsProductId),
                new DataBaseWhere('referencia', null, 'IS')
            ];

            $productImages = (new ProductoImagen())->all($where);

            foreach ($productImages as $img) {
                $imageUrl = FS_ROUTE . '/' . $img->url('download-permanent');
                $images[] = [
                    'src' => $imageUrl,
                    'alt' => $img->observaciones ?? ''
                ];
            }

            return $images;
        };
    }

    protected function getWooCommerceFormData()
    {
        return function () {
            $request = $this->request;

            $categories = [];
            $categoryIds = $request->request->get('woo_categories', []);
            if (!empty($categoryIds) && is_array($categoryIds)) {
                foreach ($categoryIds as $catId) {
                    $categories[] = ['id' => (int)$catId];
                }
            }

            return [
                'name' => $request->request->get('woo_product_name', ''),
                'description' => $request->request->get('woo_description', ''),
                'short_description' => $request->request->get('woo_short_description', ''),
                'sku' => $request->request->get('woo_sku', ''),
                'regular_price' => $request->request->get('woo_price', ''),
                'sale_price' => $request->request->get('woo_sale_price', ''),
                'manage_stock' => $request->request->get('woo_manage_stock') === 'yes',
                'stock_quantity' => (int)$request->request->get('woo_stock_quantity', 0),
                'status' => $request->request->get('woo_status', 'draft'),
                'catalog_visibility' => $request->request->get('woo_catalog_visibility', 'visible'),
                'categories' => $categories,
                'weight' => $request->request->get('woo_weight', ''),
                'dimensions' => [
                    'length' => $request->request->get('woo_length', ''),
                    'width' => $request->request->get('woo_width', ''),
                    'height' => $request->request->get('woo_height', '')
                ]
            ];
        };
    }

    protected function updateFsProductWithFormData()
    {
        return function ($fsProduct, $formData) {
            $fsProduct->woo_product_name = $formData['name'] ?? '';
            $fsProduct->woo_price = $formData['regular_price'] ?? '';
            $fsProduct->woo_sku = $formData['sku'] ?? '';
            $fsProduct->woo_status = $formData['status'] ?? '';
            $fsProduct->woo_catalog_visibility = $formData['catalog_visibility'] ?? '';

            // Store categories as JSON
            if (!empty($formData['categories'])) {
                $fsProduct->woo_categories = json_encode($formData['categories']);
            }

            $fsProduct->woo_last_nick = $this->user->nick ?? 'system';
            $fsProduct->woo_last_update = date('Y-m-d H:i:s');
        };
    }

    protected function updateFsProductWithWooData()
    {
        return function ($fsProduct, $wooProduct) {
            $fsProduct->woo_id = $wooProduct->id;
            $fsProduct->woo_product_name = $wooProduct->name ?? '';
            $fsProduct->woo_price = $wooProduct->regular_price ?? '';
            $fsProduct->woo_permalink = $wooProduct->permalink ?? '';
            $fsProduct->woo_sku = $wooProduct->sku ?? '';
            $fsProduct->woo_status = $wooProduct->status ?? '';
            $fsProduct->woo_catalog_visibility = $wooProduct->catalog_visibility ?? '';

            // Store categories and images as JSON
            if (!empty($wooProduct->categories)) {
                $fsProduct->woo_categories = json_encode($wooProduct->categories);
            }
            if (!empty($wooProduct->images)) {
                $fsProduct->woo_images = json_encode($wooProduct->images);
            }

            if (empty($fsProduct->woo_creation_date)) {
                $fsProduct->woo_creation_date = date('Y-m-d H:i:s');
                $fsProduct->woo_nick = $this->user->nick ?? 'system';
            }

            $fsProduct->woo_last_nick = $this->user->nick ?? 'system';
            $fsProduct->woo_last_update = date('Y-m-d H:i:s');
        };
    }

    protected function validateProductForWooCommerce()
    {
        return function ($fsProduct) {
            $errors = [];

            if (empty($fsProduct->descripcion)) {
                $errors[] = ['message' => 'El producto debe tener una descripción'];
            }

            if (empty($fsProduct->referencia)) {
                $errors[] = ['message' => 'El producto debe tener una referencia'];
            }

            if (!isset($fsProduct->precio) || $fsProduct->precio < 0) {
                $errors[] = ['message' => 'El producto debe tener un precio válido'];
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors
            ];
        };
    }

    protected function getCurrentFsProductId()
    {
        return function () {
            $fsId = $this->request->request->get('fs_id');
            if (empty($fsId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'ID de producto faltante']]
                ]);
                error_log("Could not retrieve Product ID from request");
                return null;
            }

            return $fsId;
        };
    }


}
