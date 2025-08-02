<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Lib\Widget;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\Widget\WidgetText;

class WidgetTextBtn extends WidgetText
{
    protected $action;
    protected $iconbtn;
    protected $jsfile;

    public function __construct($data)
    {
        parent::__construct($data);
        $this->action = $data['action'] ?? '';
        $this->iconbtn = $data['iconbtn'] ?? 'fas fa-search';
        $this->jsfile = $data['jsfile'] ?? '';
    }

    public function edit($model, $title = '', $description = '', $titleurl = '')
    {
        if (empty($this->action)) {
            return parent::edit($model, $title, $description, $titleurl);
        }

        $this->setValue($model);
        $descriptionHtml = empty($description) ? '' : '<small class="form-text text-muted">' . static::$i18n->trans($description) . '</small>';
        $labelHtml = '<label class="mb-0">' . $this->onclickHtml(static::$i18n->trans($title), $titleurl) . '</label>';

        $html = '<div class="form-group mb-2 widgetTextBtn">'
            . $labelHtml
            . '<div class="input-group">';

        if (false === empty($this->icon)) {
            $html .= '<div class="' . $this->css('input-group-prepend') . ' d-flex d-sm-none d-xl-flex">'
                . '<span class="input-group-text">'
                . '<i class="' . $this->icon . ' fa-fw"></i>'
                . '</span>'
                . '</div>';
        }

        $html .= $this->inputHtml()
            . '<div class="' . $this->css('input-group-append') . '">'
            . '<button type="button" class="btn btn-primary">'
            . '<i class="' . $this->iconbtn . ' fa-fw"></i>'
            . '</button>'
            . '</div>'
            . '</div>'
            . $descriptionHtml
            . '</div>';

        return $html;
    }

    protected function assets()
    {
        parent::assets();
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/WidgetTextBtn.js');
    }

    protected function inputHtmlExtraParams()
    {
        $params = ' jsfile="' . $this->jsfile . '"';
        $params .= ' action="' . $this->action . '"';
        return $params . parent::inputHtmlExtraParams();
    }
}