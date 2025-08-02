<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Session;

class WoocommerceProducto extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $creation_date;

    /** @var string */
    public $fs_product_unique_ref;

    /** @var int */
    public $id;

    /** @var string */
    public $last_nick;

    /** @var string */
    public $last_sync;

    /** @var string */
    public $last_update;

    /** @var string */
    public $name;

    /** @var string */
    public $nick;

    /** @var string */
    public $sync_status;

    /** @var int */
    public $wc_collection_id;

    /** @var string */
    public $wc_product_unique_ref;

    public function clear() 
    {
        parent::clear();
        $this->last_sync = date(self::DATETIME_STYLE);
        $this->wc_collection_id = 0;
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "woocommerce_productos";
    }

    public function test(): bool
    {
        $this->creation_date = $this->creationdate ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;
        $this->fs_product_unique_ref = Tools::noHtml($this->fs_product_unique_ref);
        $this->name = Tools::noHtml($this->name);
        $this->sync_status = Tools::noHtml($this->sync_status);
        $this->wc_product_unique_ref = Tools::noHtml($this->wc_product_unique_ref);
        return parent::test();
    }

    protected function saveUpdate(array $values = []): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();
        return parent::saveUpdate($values);
    }
}
