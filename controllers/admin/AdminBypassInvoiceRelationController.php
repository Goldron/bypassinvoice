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

require_once _PS_MODULE_DIR_ . 'bypassinvoice/vendor/autoload.php';

use Bypassinvoice\ClassBypassInvoice;

/**
 * List, edit (societe only) and delete customer <-> Dolibarr societe relations.
 */
class AdminBypassInvoiceRelationController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'bypassinvoice';
        $this->className = 'Bypassinvoice\\ClassBypassInvoice';
        $this->identifier = 'id_bypassinvoice';
        $this->module = 'bypassinvoice';
        $this->lang = false;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_select = 'c.`firstname`, c.`lastname`, c.`email`';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)';
        $this->_where = 'AND a.`id_shop` = ' . (int) $this->context->shop->id;
        $this->_defaultOrderBy = 'id_bypassinvoice';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        // built after parent::__construct(): $this->l() needs $this->translator,
        // which is only set up by the parent constructor.
        $this->fields_list = [
            'id_bypassinvoice' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 25,
            ],
            'lastname' => [
                'title' => $this->l('Nom'),
                'filter_key' => 'c!lastname',
            ],
            'firstname' => [
                'title' => $this->l('Prénom'),
                'filter_key' => 'c!firstname',
            ],
            'email' => [
                'title' => $this->l('Email'),
                'filter_key' => 'c!email',
            ],
            'id_customer' => [
                'title' => $this->l('ID client PrestaShop'),
                'align' => 'center',
                'width' => 120,
            ],
            'id_societe' => [
                'title' => $this->l('ID société Dolibarr'),
                'align' => 'center',
                'width' => 120,
            ],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    /**
     * No manual "add" action: relations are only created by the module's own
     * customer <-> Dolibarr sync logic.
     */
    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function renderForm()
    {
        if (
            !Validate::isLoadedObject($this->object)
            || (int) $this->object->id_shop !== (int) $this->context->shop->id
        ) {
            $this->errors[] = Tools::displayError('Relation introuvable.');

            return '';
        }

        $customer = new Customer((int) $this->object->id_customer);

        $currentSocieteLabel = '#' . (int) $this->object->id_societe;

        /** @var Bypassinvoice $module */
        $module = Module::getInstanceByName('bypassinvoice');
        if ($module && $module->WSonline()) {
            $societe = $module->api->getThirdpartyById((int) $this->object->id_societe);
            if (!empty($societe['name'])) {
                $currentSocieteLabel = $societe['name'] . ' (#' . (int) $this->object->id_societe . ')';
            }
        }

        $this->context->smarty->assign([
            'customer_fullname' => $customer->firstname . ' ' . $customer->lastname,
            'customer_email' => $customer->email,
            'id_bypassinvoice' => (int) $this->object->id,
            'id_societe' => (int) $this->object->id_societe,
            'current_societe_label' => $currentSocieteLabel,
            'ajax_search_url' => $this->context->link->getAdminLink('AdminBypassInvoiceRelation') . '&ajax=1&action=SearchSociete',
            'save_url' => self::$currentIndex . '&token=' . $this->token,
            'back_url' => self::$currentIndex . '&token=' . $this->token,
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'bypassinvoice/views/templates/admin/relation_form.tpl');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('deletebypassinvoice')) {
            if (!$this->tabAccess['delete']) {
                $this->errors[] = Tools::displayError('Vous n\'avez pas la permission de supprimer ceci.');
            } else {
                $id = (int) Tools::getValue('id_bypassinvoice');
                if ($id && ClassBypassInvoice::deleteById($id)) {
                    Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=1');
                }
                $this->errors[] = Tools::displayError('La suppression a échoué.');
            }

            return;
        }

        if (Tools::isSubmit('submitBypassinvoiceRelation')) {
            if (!$this->tabAccess['edit']) {
                $this->errors[] = Tools::displayError('Vous n\'avez pas la permission de modifier ceci.');

                return;
            }

            $id = (int) Tools::getValue('id_bypassinvoice');
            $id_societe = (int) Tools::getValue('id_societe');

            if (!$id || $id_societe <= 0) {
                $this->errors[] = Tools::displayError('Veuillez sélectionner une société Dolibarr valide.');

                return;
            }

            if (ClassBypassInvoice::updateSocieteById($id, $id_societe)) {
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=4');
            }
            $this->errors[] = Tools::displayError('La mise à jour a échoué.');

            return;
        }

        parent::postProcess();
    }

    /**
     * AJAX endpoint powering the societe search field of the edit form.
     */
    public function ajaxProcessSearchSociete()
    {
        $term = (string) Tools::getValue('q');
        $results = [];

        /** @var Bypassinvoice $module */
        $module = Module::getInstanceByName('bypassinvoice');
        if ($module && $module->WSonline()) {
            foreach ($module->api->searchThirdparties($term) as $thirdparty) {
                $results[] = [
                    'id' => (int) $thirdparty['id'],
                    'text' => $thirdparty['name'] . ' (#' . $thirdparty['id'] . ')',
                ];
            }
        }

        die(json_encode(['results' => $results]));
    }
}
