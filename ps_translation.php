<?php

use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class PS_Translation extends Module
{
    public function __construct()
    {
        $this->name = 'ps_translation';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Jean-Philippe Bidegain';
        $this->need_instance = 0;

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'PS Translation';
        $this->description = $this->l('Translation module for PrestaShop.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module ?');
    }
}
