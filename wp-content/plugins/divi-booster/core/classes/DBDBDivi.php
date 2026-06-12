<?php

interface DBDBAnyDivi {
}

class DBDBDivi implements DBDBAnyDivi
{
    static function create() {
        return new self();
    }

    public function __construct() {
    }

    public function dynamic_assets_url($path='') {
        return $this->builder_url('/feature/dynamic-assets/assets'.$path);
    }

    public function builder_url($path='') {
        return defined('ET_BUILDER_URI')?ET_BUILDER_URI.$path:false;
    }

    public function supports_dynamic_assets() {
        return ($this->version() && version_compare($this->version(), '4.10', '>='));
    }

    public function version() {
        return defined('ET_CORE_VERSION')?ET_CORE_VERSION:false;
    }

    public function isThemeBuilderLayout() {
        return (is_callable('ET_Builder_Element::is_theme_builder_layout') && ET_Builder_Element::is_theme_builder_layout());
    }
}

class DBDBFakeDivi implements DBDBAnyDivi
{
    public function isThemeBuilderLayout()
    {
        return false;
    }
}

class DBDBFakeDiviIsTbLayout extends DBDBFakeDivi
{
    public function isThemeBuilderLayout()
    {
        return true;
    }
}
