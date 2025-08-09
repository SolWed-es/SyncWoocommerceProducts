<?php
/**
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditWoocommerceProducto extends EditController

{
    public function getModelClassName(): string
    {
        return "WoocommerceProducto";
    }

    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData["title"] = "Tienda WEB";
        $pageData["menu"] = "warehouse";
        $pageData["icon"] = "fas fa-store";
        return $pageData;
    }

    public function createViews()
    {
        parent::createViews();
        $viewName = 'Etiquetas';
        $this->addEditListView('EditWoocommerceProducto', 'WoocommerceProducto', 'Tienda WEBB', 'fas fa-shop');
        $this->addHtmlView('Etiquetas', 'Tab/Test', 'WoocommerceProducto', 'shop', 'fas fa-shop');

    }

    public function getAvailableTags()
    {
        return function () {
            $tags = [];
            $mainViewName = $this->getMainViewName();
            foreach ($this->views[$mainViewName]->model->getVariants() as $key => $variant) {
                $tags[$key] = [
                    'reference' => $variant->referencia,
                    'url' => $variant->url(),
                    'quantity' => 1
                ];
            }

            return $tags;
        };
    }


}