<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Extension\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Producto as BaseProducto;
use Closure;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * Para modificar el comportamiento de modelos de otro plugins (o del core)
 * podemos crear una extensión de ese modelo.
 *
 * https://facturascripts.com/publicaciones/extensiones-de-modelos
 */
class Producto
{



    public $tiendaweb;
    
    public $prodcategory;




    public $woo_id;

    public $woo_product_name;


    public $woo_price;


    public $woo_permalink;


    public $woo_sku;


    public $woo_status;


    public $woo_catalog_visibility;

    public $woo_categories;

    public $woo_images;



    public $woo_nick;


    public $woo_creation_date;


    public $woo_last_nick;


    public $woo_last_update;




    
    // ***************************************
    // ** Métodos disponibles para extender **
    // ***************************************

    public function clear(): Closure
    {
        return function() {
            $this->prodcategory = null;
            $this->tiendaweb = false;
            $this->price = 0.0;
            //$this->woo_id = 0;
        };
    }

    public function delete(): Closure
    {
        return function() {

        };
    }

    public function deleteBefore(): Closure
    {
        return function() {
            // tu código aquí
            // deleteBefore() se ejecuta antes de ejecutar el delete() del modelo.
            // Si devolvemos false, impedimos el delete().
            // TODO: Unpublish the product from the WEB Store if it's visible
        };
    }

    public function save(): Closure
    {
        return function() {
            // tu código aquí
            // save() se ejecuta una vez realizado el save() del modelo,
            // cuando ya se ha guardado el registro en la base de datos
        };
    }

    public function saveBefore(): Closure
    {
        return function() {

            // tu código aquí
            // saveBefore() se ejecuta antes de hacer el save() del modelo.
            // Si devolvemos false, impedimos el save().
        };
    }

    public function saveInsert(): Closure
    {
        return function() {

            /*
            $where = [new DataBaseWhere('referencia', $this->referencia)];
            if ($this->count($where) > 0) {
                Tools::log()->warning('[Plugin Tienda WEB] duplicated-reference', ['%reference%' => $this->referencia]);
                return false;
            }

            if (false === saveInsert($values)) {
                return false;
            }

            $variant = new DinVariante();
            $variant->idproducto = $this->idproducto;
            $variant->precio = $this->precio;
            $variant->referencia = $this->referencia;
            $variant->stockfis = $this->stockfis;
            if ($variant->save()) {
                return true;
            }

            $this->delete();
            return false;
            // tu código aquí
            // saveInsert() se ejecuta una vez realizado el saveInsert() del modelo,
            // cuando ya se ha guardado el registro en la base de datos

            // comprobamos si la referencia ya existe



/*
            $variant = new DinVariante();

            if ($variant->codbarras === '' || $variant->codbarras === null) {
                Tools::log()->warning('SKU is empty');
            }
            Tools::log()->warning('SKU is empty', ['%sku%' => $variant->codbarras]);

            $variant->prodcategory = $this->prodcategory;
            $variant->tiendaweb = $this->tiendaweb;

*/
        };
    }

    public function saveInsertBefore(): Closure
    {
        return function() {
            // tu código aquí
            // saveInsertBefore() se ejecuta antes de hacer el saveInsert() del modelo.
            // Si devolvemos false, impedimos el saveInsert().
        };
    }

    public function saveUpdate(): Closure
    {
        return function() {
            // tu código aquí
            // saveUpdate() se ejecuta una vez realizado el saveUpdate() del modelo,
            // cuando ya se ha guardado el registro en la base de datos
            $this->last_nick = Session::user()->nick;
            $this->last_update = Tools::dateTime();

        };
    }

    public function saveUpdateBefore(): Closure
    {
        return function() {
            // tu código aquí
            // saveUpdateBefore() se ejecuta antes de hacer el saveUpdate() del modelo.
            // Si devolvemos false, impedimos el saveUpdate().
        };
    }

    public function test(): Closure
    {
        return function() {
            $this->creation_date = $this->creationdate ?? Tools::dateTime();
            $this->nick = $this->nick ?? Session::user()->nick;
            $this->catalog_visibility = Tools::noHtml($this->catalog_visibility);
            $this->categories = Tools::noHtml($this->categories);
            $this->images = Tools::noHtml($this->images);
            $this->name = Tools::noHtml($this->name);
            $this->nameProducto = Tools::noHtml($this->nameProducto);
            $this->permalink = Tools::noHtml($this->permalink);
            $this->sku = Tools::noHtml($this->sku);
            $this->status = Tools::noHtml($this->status);
        };
    }

    public function testBefore(): Closure
    {
        return function() {
            // tu código aquí
            // test se ejecuta justo antes del método test del modelo.
            // Si devolvemos false, impedimos el save() y el resto de test().
        };
    }

    public function isTiendaWeb(): bool
    {
        if ($this->tiendaweb) {
            return true;
        } else {
            return false;
        }
    }
}
