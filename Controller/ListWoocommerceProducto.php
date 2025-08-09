<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Plugins\PushWoocommerceProductos\Lib\WooHelper;

use FacturaScripts\Dinamic\Model\WoocommerceProducto;

class ListWoocommerceProducto extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData["title"] = "Tienda WEB";
        $pageData["menu"] = "warehouse";
        $pageData["icon"] = "fas fa-store";
        return $pageData;
    }

    protected function createViews(): void
    {

        $this->createViewsWoocommerceProducto();
        $this->createViewsProductosErp();
        $this->createViewsProductosTienda();
        $this->addSyncButton();
    }

    protected function execAfterAction($action)
    {
        //parent::execAfterAction($action);


        // Trigger pullWoocommerceData() function
        if ($action === 'pullData') {
            $this->pullWoocommerceData();
        }


        if ($action === 'syncData') {
            $this->syncWooData();
        }


    }

    protected function createViewsProductosErp(string $viewName = "ProductoERP"): void
    {
        $this->addView($viewName, "Producto", "Productos ERP");
    }

    protected function createViewsProductosTienda(string $viewName = "ListWoocommerceReadOnly"): void
    {
        $this->addView($viewName, "WoocommerceReadOnly", "Productos Tienda (Read-only)");
        $this->addWooPullButton();
    }

    protected function createViewsWoocommerceProducto(string $viewName = "ListWoocommerceProducto"): void
    {
        $this->addView($viewName, "WoocommerceProducto", "Tienda WEB");
    }


    /**********************************
     *
     * Productos Tienda (Read-only) logic
     *
     **********************************/


    // Adding the button to pull data
    protected function addWooPullButton(string $viewName = "ListWoocommerceProducto")
    {

        $this->addButton('ListWoocommerceReadOnly', [
            'type' => 'action',
            'action' => 'pullData',
            'color' => 'info',
            'icon' => 'fas fa-file-export',
            'label' => 'Sync',
        ]);
    }


    protected function pullWoocommerceData()
    {

        Tools::log()->debug('[WooPull] Starting WooCommerce synchronization @ Productos Tienda (Read-only) logic');

        $woo_client = WooHelper::getClient();
        if (!$woo_client) {
            Tools::log()->critical('WooCommerce client not configured');
            error_log('[WooPull] CRITICAL: WooCommerce client not configured');
            return;
        }

        // GET request to pull data + error logging in case of error
        try {
            $products = $woo_client->get('products');
        } catch (\Exception $e) {
            Tools::log()->critical("Could not 'GET' products : " . $e->getMessage());
        }

        if (empty($products)) {
            error_log("[WooPull] No products found.");
        }

        // Handle both array and object responses
        //$productList = is_array($products) ? $products : [$products];

        foreach ($products as $wooProduct) {
            //$this->processWooProduct($wooProduct);


            error_log(print_r($wooProduct, true));

            // Process product (either create or update)
            $this->processWooProduct($wooProduct);
        }


    }


























    protected function addSyncButton(string $viewName = "ListWoocommerceProducto")
    {

        $this->addButton('ListWoocommerceProducto', [
            'type' => 'action',
            'action' => 'syncData',
            'color' => 'info',
            'icon' => 'fas fa-file-export',
            'label' => 'Sync',
        ]);
    }


    protected function syncWooData(): void
    {

        Tools::log()->debug('Starting WooCommerce synchronization');
        $woo_client = WooHelper::getClient();

        if (!$woo_client) {
            Tools::log()->critical('WooCommerce client not configured');
            error_log('[WooSync] CRITICAL: WooCommerce client not configured');
            return;
        }

        try {
            $page = 1;
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            do {
                error_log("[WooSync] Fetching page {$page} of products");
                $products = $woo_client->get('products', [
                    'per_page' => 100,
                    'page' => $page,
                    'status' => 'publish'
                ]);

                // Fix count issue - convert to array if needed
                $productCount = is_array($products) ? count($products) : (is_object($products) ? count(get_object_vars($products)) : 0);
                error_log("[WooSync] Found {$productCount} products on page {$page}");

                if (empty($products)) {
                    error_log("[WooSync] No products found on page {$page}, ending sync");
                    break;
                }

                // Handle both array and object responses
                $productList = is_array($products) ? $products : [$products];

                foreach ($productList as $index => $wooProduct) {
                    error_log("[WooSync] Processing product #{$index} on page {$page}: ID {$wooProduct->id} - {$wooProduct->name}");
                    $result = $this->processWooProduct($wooProduct);

                    switch ($result) {
                        case 'imported':
                            $imported++;
                            error_log("[WooSync] SUCCESS: Imported new product {$wooProduct->id}");
                            break;
                        case 'updated':
                            $updated++;
                            error_log("[WooSync] SUCCESS: Updated existing product {$wooProduct->id}");
                            break;
                        case 'skipped':
                            $skipped++;
                            error_log("[WooSync] SKIPPED: Product {$wooProduct->id}");
                            break;
                        default:
                            $errors++;
                            error_log("[WooSync] ERROR: Processing failed for product {$wooProduct->id} - Result: {$result}");
                    }
                }

                $page++;
            } while ($productCount === 100);

            $msg = Tools::lang()->trans('woocommerce-sync-result', [
                '%imported%' => $imported,
                '%updated%' => $updated,
                '%skipped%' => $skipped,
                '%errors%' => $errors
            ]);

            Tools::log()->notice($msg);
            error_log("[WooSync] COMPLETE: {$msg}");
        } catch (\Exception $e) {
            $errorMsg = "SYNC ERROR: {$e->getMessage()}";
            Tools::log()->error($errorMsg);
            error_log("[WooSync] {$errorMsg}");
            error_log("[WooSync] Stack trace: {$e->getTraceAsString()}");
        }
    }

    protected function processWooProduct($wooProduct): string
    {
        error_log("[WooSync] Processing product ID: {$wooProduct->id} - Prod name: {$wooProduct->name}");

        $model = new WoocommerceProducto();
        $where = [new DataBaseWhere('wc_product_unique_ref', $wooProduct->id)];

        // Existing linked product
        if ($model->loadFromCode('', $where)) {
            error_log("[WooSync] Found existing link for WC product {$wooProduct->id} to FS ref {$model->fs_product_unique_ref}");
            return $this->updateExistingProduct($model, $wooProduct);
        }

        error_log("[WooSync] No existing link found for WC product {$wooProduct->id}");
        return $this->importNewProduct($wooProduct);
    }

    protected function updateExistingProduct(WoocommerceProducto $link, $wooProduct): string
    {
        error_log("[WooSync] Updating existing product link: WC {$wooProduct->id} → FS {$link->fs_product_unique_ref}");

        $fsProduct = new Producto();
        $where = [new DataBaseWhere('referencia', $link->fs_product_unique_ref)];

        if (!$fsProduct->loadFromCode('', $where)) {
            error_log("[WooSync] ERROR: Linked FS product not found: {$link->fs_product_unique_ref}");
            $link->delete();
            return 'skipped';
        }

        error_log("[WooSync] Found FS product: {$fsProduct->referencia} - {$fsProduct->descripcion}");

        $this->updateFsProduct($fsProduct, $wooProduct);

        if ($fsProduct->save()) {
            error_log("[WooSync] SUCCESS: Updated FS product {$fsProduct->referencia}");
            return 'updated';
        }

        $errors = implode(', ', $fsProduct->errors);
        error_log("[WooSync] ERROR: Failed to save FS product {$fsProduct->referencia}: {$errors}");
        return 'error';
    }

    protected function importNewProduct($wooProduct): string
    {
        error_log("[WooSync] Importing new product: WC {$wooProduct->id}");

        $ref = $this->generateProductReference($wooProduct);
        error_log("[WooSync] Generated reference: {$ref}");

        $fsProduct = new Producto();
        $where = [new DataBaseWhere('referencia', $ref)];

        // Check if product exists in FS without link
        if ($fsProduct->loadFromCode('', $where)) {
            error_log("[WooSync] Found existing FS product with matching reference: {$ref}");
            return $this->createLink($fsProduct, $wooProduct);
        }

        error_log("[WooSync] Creating new FS product for WC {$wooProduct->id}");
        $this->createFsProduct($fsProduct, $wooProduct, $ref);

        if ($fsProduct->save()) {
            error_log("[WooSync] SUCCESS: Created new FS product {$ref}");
            return $this->createLink($fsProduct, $wooProduct);
        }

        $errors = implode(', ', $fsProduct->errors);
        error_log("[WooSync] ERROR: Failed to create FS product {$ref}: {$errors}");
        return 'error';
    }

    protected function createLink(Producto $fsProduct, $wooProduct): string
    {
        error_log("[WooSync] Creating link for FS {$fsProduct->referencia} → WC {$wooProduct->id}");

        $link = new WoocommerceProducto();
        $link->fs_product_unique_ref = $fsProduct->referencia;
        $link->wc_product_unique_ref = $wooProduct->id;

        if ($link->save()) {
            error_log("[WooSync] SUCCESS: Created product link");
            return 'imported';
        }

        $errors = implode(', ', $link->error_message);
        error_log("[WooSync] ERROR: Failed to create product link: {$errors}");
        return 'error';
    }

    protected function generateProductReference($wooProduct): string
    {
        // Use SKU if exists, otherwise generate from ID
        return !empty($wooProduct->sku) ? $wooProduct->sku : 'WC-' . $wooProduct->id;
    }

    protected function createFsProduct(Producto &$fsProduct, $wooProduct, string $ref): void
    {
        $price = $this->parseWooPrice($wooProduct);
        $stock = $wooProduct->stock_quantity ?? 0;
        $blocked = ($wooProduct->stock_status !== 'instock') ? 'true' : 'false';

        error_log("[WooSync] Creating FS product data:");
        error_log("[WooSync]   Reference: {$ref}");
        error_log("[WooSync]   Name: {$wooProduct->name}");
        error_log("[WooSync]   Price: {$price}");
        error_log("[WooSync]   Stock: {$stock}");
        error_log("[WooSync]   Blocked: {$blocked}");

        $fsProduct->referencia = $ref;
        $fsProduct->descripcion = $wooProduct->name;
        $fsProduct->precio = $price;
        $fsProduct->stockfis = $stock;
        $fsProduct->bloqueado = ($wooProduct->stock_status !== 'instock');
        $fsProduct->publico = true;
        $fsProduct->secompra = false;
    }

    protected function updateFsProduct(Producto &$fsProduct, $wooProduct): void
    {
        $fsProduct->descripcion = $wooProduct->name;
        $fsProduct->precio = $this->parseWooPrice($wooProduct);
        $fsProduct->stockfis = $wooProduct->stock_quantity ?? $fsProduct->stockfis;
        $fsProduct->bloqueado = ($wooProduct->stock_status !== 'instock');
    }

    protected function parseWooPrice($wooProduct): float
    {
        return max([
            (float)($wooProduct->price ?? 0),
            (float)($wooProduct->regular_price ?? 0),
            (float)($wooProduct->sale_price ?? 0)
        ]);
    }


    // Woocommerce --> FacturaScripts DB
    protected function addFsWoocommerceProducto(): void
    {
        $producto = new WoocommerceProducto();
        $producto->name = 'Manual test';
        $producto->fs_product_unique_ref = 'Manual test';
        $producto->wc_product_unique_ref = 'Manual test';
        $producto->save();
    }
}
