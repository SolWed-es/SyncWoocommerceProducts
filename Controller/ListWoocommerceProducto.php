<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;
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
        $this->addSyncButton();
    }

    protected function execAfterAction($action)
    {
        //parent::execAfterAction($action);
        if ($action === 'syncData') {
            $this->syncWooData();
        }
    }

    protected function createViewsWoocommerceProducto(string $viewName = "ListWoocommerceProducto"): void
    {
        $this->addView($viewName, "WoocommerceProducto", "Tienda WEB");
    }

    protected function addSyncButton(string $viewName = "ListWoocommerceProducto") {

        $this->addButton('ListWoocommerceProducto', [
            'type' => 'action',
            'action' => 'syncData',
            'color' => 'info',
            'icon' => 'fas fa-file-export',
            'label' => 'Sync',
        ]);
    }


    //Pull data from woocommerce API and sync it with FS
    protected function syncWooData(): void
    {
        $woo_client = WooHelper::getClient();

        //print error if we can't load the woo client
        if (!$woo_client) {
            Tools::log()->error("Can't connect to Woocommerce");
            return;
        }

        try {
            $product = $woo_client->get('products');
            error_log(print_r($product, true));
            $this->addFsWoocommerceProducto();
            error_log("added manual product. Check the DB");
        } catch (\Exception $e) {
            Tools::log()->error($e->getMessage());
            Tools::log()->error('Sync error');
        }



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
