<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Session;

class WoocommerceReadOnly extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $catalog_visibility;

    /** @var string */
    public $categories;

    /** @var string */
    public $creation_date;

    /** @var int */
    public $id;

    /** @var string */
    public $images;

    /** @var string */
    public $last_nick;

    /** @var string */
    public $last_update;

    /** @var string */
    public $name;

    /** @var string */
    public $nameProducto;

    /** @var string */
    public $nick;

    /** @var string */
    public $permalink;

    /** @var float */
    public $price;

    /** @var string */
    public $sku;

    /** @var string */
    public $status;

    /** @var int */
    public $woo_id;

    public function clear() 
    {
        parent::clear();
        $this->price = 0.0;
        $this->woo_id = 0;
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "woocommerce_read_only";
    }

    public function test(): bool
    {
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
        return parent::test();
    }

    protected function saveUpdate(array $values = []): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();
        return parent::saveUpdate($values);
    }
}
