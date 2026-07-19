<?php
/**
 * Copyright since 2024 SILADEL.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to (GPL-3.0-or-later)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author      David IGREJA. <david@siladel.fr> SILADEL
 * @copyright   Since 2024 SILADEL
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, Version 3
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BypassinvoicePdfDisplayModuleFrontController extends ModuleFrontController
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
     * @var array $content data
     */
    public $content;

    public function initContent()
    {
        /** @var bypassinvoice $module */
        $module = Module::getInstanceByName('bypassinvoice');
        $this->module = $module;

        parent::initContent();

        $this->displayPDF();
    }

    /**
     * décypt
     *
     * @param string $encryptedString
     * @param string $encryptedString
     * @return string|bool
     */
    protected function decryptString(string $encryptedString, string $key)
    {
        $data = base64_decode($encryptedString);
        $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }

    /**
     * Display PDF
     *
     */
    protected function displayPDF(): void
    {
        $ref = $this->decryptString(Tools::getValue('reference'), "@");
        $lang = str_replace('-', '_', Context::getContext()->language->locale);

        if ($ref) {
            $template = !empty(Configuration::get('BYPASSINVOICE_TEMPLATE')) ? Configuration::get('BYPASSINVOICE_TEMPLATE') : "Invoice";

            $data = ["modulepart" => "invoice", "original_file" => $ref . "/" . $ref . ".pdf", "doctemplate" => $template, "langcode" => $lang];
            $this->content =  $this->module->downloadPDF($data);
            if (!empty($this->content)) {
                $this->content["content"] = base64_decode($this->content["content"]);

                $fileName = $this->content['filename'];

                header('Content-type: application/pdf');
                header('Content-Length: ' . strlen($this->content["content"]));
                header('Content-Disposition: inline; filename="' . $fileName . '"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');

                echo $this->content["content"];
                exit;
            }
        }

        Tools::redirect($this->context->link->getModuleLink('bypassinvoice', 'Invoices'));
    }
}
