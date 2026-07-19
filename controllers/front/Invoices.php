<?php
/**
 * Copyright since 2024 SILADAL SAS.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to (Apache-2.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/Apache-2.0
 *
 * @author      David IGREJA. <david@siladel.fr> SILADEL SAS
 * @copyright   Since 2024 SILADEL SAS
 * @license     https://opensource.org/licenses/Apache-2.0 Apache License, Version 2.0
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BypassinvoiceInvoicesModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool If set to true, will be redirected to authentication page
     */
    public $auth = true;

    /**
     * @var Bypassinvoice
     */
    public $module;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var array orders list
     */
    private $orders;

    public $active;

    public function __construct()
    {
        /** @var bypassinvoice
         * $module */
        $module = Module::getInstanceByName('bypassinvoice');
        $this->module = $module;

        if (Configuration::get('PS_INVOICE')) {
            Tools::redirect('index');
        }

        if (empty($this->module->active)) {
            Tools::redirect('index');
        }

        $this->active = $this->module->WSonline();

        $this->customer = Context::getContext()->customer;

        $order = new Order((int) $this->customer->id);
        $this->orders = $order->getCustomerOrders(Context::getContext()->customer->id);

        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $title = Configuration::get('BYPASSINVOICE_TITLEPAGE', $this->context->language->id);

        $this->context->smarty->assign(
            [
                'accountLink' => '#',
                'bypassinvoicetitle' => (!empty($title)) ? $title : "Invoices",
                'active' => $this->active,
                'invoices' => ($this->active) ? $this->getInvoices() : '',
                
            ]
        );

        /*
        $this->context->controller->registerJavascript(
          'BypassinvoiceController',
          'modules/bypassinvoice/view/bypassinvoice.js',
          [
            'priority' => 200,
          ]
        );
        */

        $this->context->controller->registerStylesheet(
            'module-modulename-style',
            'modules/' . $this->module->name . '/views/css/front.css',
            [
                'media' => 'all',
                'priority' => 200,
            ]
        );

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/invoices.tpl');
    }

    /**
     * get all invoices in Dolibarr
     *
     * @return array all invoice matched
     */
    public function getInvoices(): ?array
    {
        $id_soc = $this->module->getSocieteID($this->customer, Context::getContext()->customer->id);

        $invoices = [];

        if (!empty($id_soc)) {
            $allInvoices = $this->module->getAllInvoiceByID($id_soc);
            if (!empty($allInvoices)) {
                if (!Configuration::get('BYPASSINVOICE_ALLINVOICE')) {
                    foreach ($this->orders as $order) {
                        $invoive = $this->crawlInvoiceByRef($allInvoices, $order['reference']);
                        if (!empty($invoive)) {
                            $invoices[] = $invoive;
                        }
                    }
                } else {
                    foreach ($allInvoices as $item) {
                        $item['date_creation'] = date('d-m-Y', $item['date_creation']);
                        $item['total_ttc'] = Tools::displayPrice($item['total_ttc']);
                        $item['pdf'] = $this->encryptString($item['ref'], "@");
                        $invoices[] = $item;
                    }
                }
            }
        }
        return $invoices;
    }

    /**
     * crawl invoice by ref and complete value
     *
     * @param array $tableau invoice data
     * @param string ref_ext
     * @return array invoice
     */
    public function crawlInvoiceByRef(array $tableau, string $val): ?array
    {
        foreach ($tableau as $item) {
            if ($val === $item['ref_ext']) {
                $item['date_creation'] = date('d-m-Y', $item['date_creation']);
                $item['total_ttc'] = Tools::displayPrice($item['total_ttc']);
                $item['pdf'] = $this->encryptString($item['ref'], "@");
                return $item;
            }
        }
        return null;
    }

    /**
     * crypt
     *
     * @param string $ref
     * @param string $sall sall
     * @return string encrypted data
     */
    protected function encryptString(string $ref, string $sall): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($ref, 'aes-256-cbc', $sall, 0, $iv);
        // Retourne l'IV et les données chiffrées concaténées
        return base64_encode($iv . $encrypted);
    }

    /**
     * Breadcrumb Links
     *
     * @return array
     */
    public function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = [
            'title' => Configuration::get('BYPASSINVOICE_TITLEPAGE', $this->context->language->id),
            'url' => $this->context->link->getModuleLink('bypassinvoice', 'Invoices'),
        ];

        return $breadcrumb;
    }
}
