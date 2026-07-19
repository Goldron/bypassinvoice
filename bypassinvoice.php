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

require_once(dirname(__FILE__) . '/vendor/autoload.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

use Bypassinvoice\DoliTools;
use Bypassinvoice\DoliApi;
use Bypassinvoice\DoliCurl;
use Bypassinvoice\ClassBypassInvoice as BYPASS;

class Bypassinvoice extends Module
{
    /** @var DoliApi $api */
    public $api;

    /** @var int $status */
    public $status;

    /**
     * @var int $endPointError
     *
     */
    public $endPointError;

    public function __construct()
    {
        $this->name = 'bypassinvoice';
        $this->tab = 'others';
        $this->version = '1.4.1';
        $this->author = 'SILADEL';
        $this->need_instance = 0;
        $this->module_key = 'a9e1395b4f26cd8a82064af070100c4a';
        $this->bootstrap = true;

        $this->displayName = $this->l('BypassInvoice');
        $this->description = $this->l('Delegate invoice management to Dolibarr.');

        $this->confirmUninstall = $this->l('Are you sure you want to delete the invoicing delegated to Dolibarr?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        parent::__construct();

        $url = \Tools::getValue('BYPASSINVOICE_URL', \Configuration::get('BYPASSINVOICE_URL'));
        $key = \Tools::getValue('BYPASSINVOICE_KEY', \Configuration::get('BYPASSINVOICE_KEY'));

        if (!empty($url) && !empty($key)) {
            $this->api = new DoliApi(new DoliCurl($url, $key));
            $this->status = $this->api->status();

            if (empty($this->api->getWarehouses()) || empty($this->api->getPaimentType()) || empty($this->api->getBank())) {
                $this->endPointError++;
            }
        } else {
            $this->endPointError++;
        }
    }

    /**
     * @return bool
     */
    public function install(): bool
    {

        include dirname(__FILE__) . '/sql/install.php';

        return (
            parent::install()
            && $this->registerHook('actionPaymentConfirmation')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('actionCustomerAccountUpdate')
            && $this->registerHook('actionInvoiceNumberFormatted')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayAdminCustomers')
            && $this->registerHook('actionOrderSlipAdd')
            && $this->registerHook('displayMyAccountBlock')
            && \Configuration::updateValue('BYPASSINVOICE_RUN', false)
            && \Configuration::updateValue('BYPASSINVOICE_KEY', '')
            && \Configuration::updateValue('BYPASSINVOICE_URL', '')
            && \Configuration::updateValue('BYPASSINVOICE_LOGS', true)
            && \Configuration::updateValue('BYPASSINVOICE_ENTITY', 1)
            && \Configuration::updateValue('BYPASSINVOICE_COUNTNUMBER', 4)
            && \Configuration::updateValue('BYPASSINVOICE_SEP', '')
            && \Configuration::updateValue('BYPASSINVOICE_MONTH', false)
            && \Configuration::updateValue('BYPASSINVOICE_YEARSMALL', false)
            && \Configuration::updateValue('BYPASSINVOICE_STATES', null)
            && \Configuration::updateValue('BYPASSINVOICE_TERM', '')
            && \Configuration::updateValue('BYPASSINVOICE_CB', '')
            && \Configuration::updateValue('BYPASSINVOICE_BANK', 1)
            && \Configuration::updateValue('BYPASSINVOICE_TITLEPAGE', '')
            && \Configuration::updateValue('BYPASSINVOICE_DOLINVOICE', null)
            && \Configuration::updateValue('BYPASSINVOICE_TEMPLATE', '')
            && \Configuration::updateValue('BYPASSINVOICE_WAREHOUSE', 1)
            && \Configuration::updateValue('BYPASSINVOICE_ALLINVOICE', null)
            && \Configuration::updateValue('BYPASSINVOICE_DISCOUNT', false)
            && \Configuration::updateValue('BYPASSINVOICE_SLIP', null)
        );
        /*
        // !$this->registerHook('header') */
    }

    /**
     * @return bool
     */
    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

        include dirname(__FILE__) . '/sql/uninstall.php';

        return (
            parent::uninstall()
            && \Configuration::deleteByName('BYPASSINVOICE_RUN')
            && \Configuration::deleteByName('BYPASSINVOICE_KEY')
            && \Configuration::deleteByName('BYPASSINVOICE_URL')
            && \Configuration::deleteByName('BYPASSINVOICE_LOGS')
            && \Configuration::deleteByName('BYPASSINVOICE_ENTITY')
            && \Configuration::deleteByName('BYPASSINVOICE_TERM')
            && \Configuration::deleteByName('BYPASSINVOICE_STATES')
            && \Configuration::deleteByName('BYPASSINVOICE_YEARSMALL')
            && \Configuration::deleteByName('BYPASSINVOICE_MONTH')
            && \Configuration::deleteByName('BYPASSINVOICE_SEP')
            && \Configuration::deleteByName('BYPASSINVOICE_COUNTNUMBER')
            && \Configuration::deleteByName('BYPASSINVOICE_CB')
            && \Configuration::deleteByName('BYPASSINVOICE_BANK')
            && \Configuration::deleteByName('BYPASSINVOICE_TITLEPAGE')
            && \Configuration::deleteByName('BYPASSINVOICE_DOLINVOICE')
            && \Configuration::deleteByName('BYPASSINVOICE_TEMPLATE')
            && \Configuration::deleteByName('BYPASSINVOICE_WAREHOUSE')
            && \Configuration::deleteByName('BYPASSINVOICE_ALLINVOICE')
            && \Configuration::deleteByName('BYPASSINVOICE_DISCOUNT')
            && \Configuration::deleteByName('BYPASSINVOICE_SLIP')
        );
    }

    /**
     * create order slip
     */
    public function hookActionOrderSlipAdd($params)
    {
        if (!Configuration::get('BYPASSINVOICE_SLIP')) {
            return;
        }

        $productRefunded = $params['productList'];
        $order = $params['order'];

        $societe_id = $this->getSocieteID(new Customer($order->id_customer), $order->id_customer);

        $origin = $this->api->getInvoiceByRef($order->reference); // origin invoice

        $invoice_data = [
            "socid" => $societe_id,
            "date" => time(),
            "ref_ext" => $order->reference,
            "type" => 2,
            "fk_facture_source" => (!empty($origin['id'])) ? $origin['id'] : ""

        ];

        $invoice_id = $this->api->createInvoice($invoice_data);

        if (!empty($invoice_id)) {
            $product_list = $order->getOrderDetailList();

            if (isset($product_list) && !empty($product_list)) {

                foreach ($product_list as $value) {
                    if (array_key_exists($value['id_order_detail'], $productRefunded)) {
                        $value['product_price'] =  $productRefunded[$value['id_order_detail']]['unit_price'] * -1;
                        $value['product_quantity'] = $productRefunded[$value['id_order_detail']]['quantity'];
                        $refund[] = $value;
                    }
                }

                // add product line
                $this->addLines($order, $invoice_id, null, $refund);
            }

            // warehouse
            $warehouse = 0;
            if (!empty(Configuration::get('BYPASSINVOICE_WAREHOUSE'))) {
                $warehouse = Configuration::get('BYPASSINVOICE_WAREHOUSE');
            }

            // validate invoice
            $inv = $this->api->InvoiceValidate(
                $invoice_id,
                [
                    "idwarehouse" => $warehouse,
                    "notrigger" => 0
                ]
            );
        }
    }

    public function WSonline()
    {
        if ($this->status < 400 && !empty($this->api)) {
            return true;
        }
        return false;
    }

    /**
     * get connection status with Dolibarr.
     *
     * @param string $field field selection
     *
     * @return int return status
     */
    protected function getWsStatus($field): int
    {
        if (empty($this->api)) {
            return 401;
        }

        return (int) $this->api->getStatus($field);
    }

    /**
     * get invoice by ref_ext
     * @param string $ref order reference
     * @return null|array invoice dolibarr
     */
    public function getInvoiceByRef(string $ref): ?array
    {
        return $this->api->getInvoiceByRef($ref);
    }

    /**
     * get all invoice by ID societe
     * @param int $soc societe id in Dolibarr
     * @return null|array invoice dolibarr
     */
    public function getAllInvoiceByID(int $soc): ?array
    {
        return $this->api->getInvoiceByID($soc);
    }

    /**
     * Create contact for invoice
     *
     * @param object Address
     * @param int societe_id for Dolibarr
     */
    protected function createContact(Address $address, int $societe_id): ?int
    {
        if (!empty($address->address2)) {
            $address->address1 = $address->address1 . ' ' . $address->address2;
        }

        $swapFields = [
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'address1' => 'address',
            'company' => 'socid',
            'postcode' => 'zip',
            'city' => 'town',
            'country' => 'country_code',
            'vat_number' => null,
            'other' => null,
            'id_customer' => null,
            'id_manufacturer' => null,
            'id_supplier' => null,
            'id_warehouse' => null,
            'id_country' => null,
            'id_state' => null,
            'alias' => null,
            'phone' => 'phone_pro',
            'dni' => null,
            'date_add' => null,
            'date_upd' => null,
            'deleted' => null
        ];

        $data = $this->replaceKeys($address, $swapFields);

        $data["socid"] = $societe_id;

        // Ajout code ISO
        if ($id_country = Country::getIdByName(Context::getContext()->language->id, $data['country_code'])) {
            $data['country_id'] = $this->api->getIdCountry(Country::getNameById(Context::getContext()->language->id, $id_country), Context::getContext()->language->id);
            $data['country_code'] = strtoupper(Country::getIsoById($id_country));
        } else {
            $data['country_code'] = "FR";
        }

        $contact_type = (array) $this->api->getCodeContactType();

        if (!empty($contact_type)) {
            $data['roles'][] = ['id' => $contact_type['rowid'], 'socid' => $societe_id];
        }


        if ($contact_id = $this->api->createContact($data)) {
            DoliTools::printLog('createContact, create new contact ' . $contact_id);
            return (int) $contact_id;
        }

        DoliTools::printLog('createContact, not create new contact ', 'warning');
        return null;
    }


    /**
     * update contact for invoice
     *
     * @param object Address
     * @param null|int contact id for Dolibarr
     */
    protected function updateContact(Address $address, int $contact_id): ?int
    {
        if (!empty($address->address2)) {
            $address->address1 = $address->address1 . ' ' . $address->address2;
        }

        $swapFields = [
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'address1' => 'address',
            'company' => 'socid',
            'postcode' => 'zip',
            'city' => 'town',
            'country' => 'country_code',
            'vat_number' => null,
            'other' => null,
            'id_customer' => null,
            'id_manufacturer' => null,
            'id_supplier' => null,
            'id_warehouse' => null,
            'id_country' => null,
            'id_state' => null,
            'alias' => null,
            'phone' => 'phone_pro',
            'dni' => null,
            'date_add' => null,
            'date_upd' => null,
            'deleted' => null
        ];

        $data = $this->replaceKeys($address, $swapFields);

        // Ajout code ISO
        if ($id_country = Country::getIdByName(Context::getContext()->language->id, $data['country_code'])) {
            $data['country_id'] = $this->api->getIdCountry(Country::getNameById(Context::getContext()->language->id, $id_country), Context::getContext()->language->id);
            $data['country_code'] = strtoupper(Country::getIsoById($id_country));
        } else {
            $data['country_code'] = "FR";
        }

        if ($contact_id = $this->api->updateIdContactAfterupdate($contact_id, $data)) {
            DoliTools::printLog('updateContact, update new contact ' . $contact_id);
            return (int) $contact_id;
        }

        DoliTools::printLog('updateContact, not update new contact ', 'warning');
        return null;
    }

    /**
     * convert invoice number
     *
     * @return string invoice number
     *
     */
    public function hookActionInvoiceNumberFormatted($params)
    {
        if ($this->endPointError) {
            return '';
        }

        if (!Configuration::get('PS_INVOICE')) {
            return '';
        }

        $count = 5;
        if (!empty(Configuration::get('BYPASSINVOICE_COUNTNUMBER'))) {
            $count = Configuration::get('BYPASSINVOICE_COUNTNUMBER');
        }

        $format = '%1$s%2$0' . $count . 'd';

        $YEAR = '';
        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $YEAR = "Y"; // "y"
        }

        $SP = "/";
        if (!empty(Configuration::get('BYPASSINVOICE_SEP'))) {
            $SP = (string) Configuration::get('BYPASSINVOICE_SEP');
        }

        if (Configuration::get('BYPASSINVOICE_MONTH')) {
            $YEAR .= "M";
        }

        if (Configuration::get('BYPASSINVOICE_YEARSMALL')) {
            $YEAR = strtolower($YEAR);
        }

        $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s' . $SP . '%2$0' . $count . 'd' : '%1$s%2$0' . $count . 'd' . $SP . '%3$s';

        return sprintf($format, Configuration::get('PS_INVOICE_PREFIX', (int) $params['id_lang'], null, (int) $params['id_shop']), $params['number'], date($YEAR, strtotime($params['OrderInvoice']->date_add)));
    }


    /**
     * check if the special character is found
     * @param string value
     * @return bool
     */
    protected function findSpecialChar($specialChar): bool
    {
        if (strpos('@/\|-', $specialChar) === false) {
            return false;
        }

        return true;
    }

    /**
     * This hook displays new elements on the customer account page
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayAdminCustomers($params): string
    {


        if (!Configuration::get('BYPASSINVOICE_DOLINVOICE')) {
            return '';
        }

        $this->context->smarty->assign([
            'bypassinvoicetilepage' => $this->displayName
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayAdminCustomers.tpl');
    }

    /**
     * This hook displays new elements on the customer account page
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayCustomerAccount($params): string
    {


        if (!Configuration::get('BYPASSINVOICE_DOLINVOICE')) {
            return '';
        }

        if (Configuration::get('PS_INVOICE')) {
            return '';
        }

        $title_bloc = Configuration::get('BYPASSINVOICE_TITLEPAGE', $this->context->language->id);

        $this->context->smarty->assign([
            //'url' => $this->context->link->getModuleLink('bypassinvoice', 'Invoices'),
            'url' => Context::getContext()->link->getModuleLink($this->name, 'Invoices', [], true),
            'bypassinvoicetilepage' => (!empty($title_bloc) ? $title_bloc : 'Invoices')
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayCustomerAccount.tpl');
    }

    /**
     * This hook displays new elements on the customer account page
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayMyAccountBlock($params): string
    {


        if (!Configuration::get('BYPASSINVOICE_DOLINVOICE')) {
            return '';
        }

        $this->context->smarty->assign([
            'bypassinvoice' => $this->displayName,
            'url' => Context::getContext()->link->getModuleLink($this->name, 'Invoices', [], true),
            'bypassinvoicetilepage' => Configuration::get('BYPASSINVOICE_TITLEPAGE', $this->context->language->id)
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/account/myaccount-block.tpl');
    }

    /**
     * Action during the creation of a customer account
     *
     * @return void
     */
    public function hookActionCustomerAccountAdd($params): void
    {
        if (!empty($params['newCustomer']->email) && !empty($params['newCustomer']->name)) {
            if ($this->updateSociete($params['newCustomer'])) { // update company
                return;
            } elseif (!empty($this->createSociete($params['newCustomer']))) { // create a new company
                return;
            }

            DoliTools::printLog('hookActionCustomerAccountAdd, update/create', 'error');
            PrestaShopLogger::addLog('ActionCustomerAccountAdd', 3, null, 'bypassinvoice', $params['newCustomer']->id);
        }
    }

    /**
     * download pdf
     */
    public function downloadPDF($post): ?array
    {
        if (!$response = $this->api->getloadPDF($post)) {
            DoliTools::printLog('downloadPDF, update/create', 'error');
        };
        return $response;
    }

    /**
     * update societe in dolibarr
     *
     * @param object Customer
     * @return void
     */
    protected function updateSociete(Customer $customer): bool
    {
        if ($customer->siret) {
            $customer->siret = str_replace(' ', '', $customer->siret);
        }

        $swapFields = [
            'company' => 'name',
            'website' => 'url',
            'note' => 'note_public',
            'email' => 'email',
            'siret' => 'idprof2',
            'ape' => 'idprof3',
            'id' => null,
            'id_shop' => null,
            'lastname' => null,
            'firstname' => null,
            'active' => null,
            'date_add' => null,
            'date_upd' => null,
            'max_payment_days' => "cond_reglement_id"
        ];


        $data = $this->replaceKeys($customer, $swapFields);

        $societe_id = $this->api->getCustomerId($customer->email);

        if (empty($societe_id)) {
            $societe_id = BYPASS::getBypass($customer->id);
        }

        if (!empty($societe_id)) { // update company
            if (empty($data['name'])) {
                $data['name'] = $customer->lastname . ' ' . $customer->firstname;
            }

            $data['cond_reglement_id'] = $this->api->getPaymentTerm((int) $customer->max_payment_days);

            if (!empty($this->api->updateCustomer($data, $societe_id))) {
                BYPASS::createBypass($customer->id, $societe_id);

                DoliTools::printLog('updateSociete, update company');
            } else {
                DoliTools::printLog('updateSociete, fail update company', 'warning');
            }
            return true;
        }

        return false;
    }

    /**
     * Create societe in dolibarr
     *
     * @param object customer => ($params['newCustomer'])
     * @return null|int societe id or null
     */
    protected function createSociete(Customer $customer): ?int
    {

        $swapFields = [
            'company' => 'name',
            'website' => 'url',
            'note' => 'note_public',
            'email' => 'email',
            'siret' => 'idprof2',
            'ape' => 'ape',
            'id' => null,
            'id_shop' => null,
            'lastname' => null,
            'firstname' => null,
            'active' => null,
            'date_add' => null,
            'date_upd' => null,
            'max_payment_days' => "cond_reglement_id"
        ];

        $data = $this->replaceKeys($customer, $swapFields);

        $data['entity'] = Configuration::get('BYPASSINVOICE_ENTITY');
        $data['client'] = 1;
        //$data['prospect'] = 0;
        //$data['fournisseur'] = 0;
        $data['code_client'] = "auto";
        $data['code_fournisseur'] = "auto";

        $data['cond_reglement_id'] = $this->api->getPaymentTerm((int) $customer->max_payment_days);

        if ($customer->siret) {
            $data['idprof2'] = str_replace(" ", "", $customer->siret);
        }

        if (empty($data['name'])) {
            $data['name'] = $customer->lastname . ' ' . $customer->firstname;
        }

        $societe_id = $this->api->createCustomer($data);

        if (!empty($societe_id)) {
            BYPASS::createBypass($customer->id, (int) $societe_id);

            DoliTools::printLog('createSociete, create new company');

            return (int) $societe_id;
        } else {
            DoliTools::printLog('createSociete, fail create new company', 'warning');
        }

        return null;
    }

    /**
     * Action during the update of a customer account
     * @param array $param
     * @return void
     */
    public function hookActionCustomerAccountUpdate($params)
    {
        if (!empty($params['customer']->email)) {
            if (false === $this->updateSociete($params['customer'])) {
                $this->createSociete($params['customer']);
            }
        }
    }

    /**
     * Replace key to value in object
     * @param object $param
     * @param array $swapFields
     * @return array $data
     */
    protected function replaceKeys(object $params, array $swapFields): array
    {
        $data = [];

        foreach ($swapFields as $oldKey => $newKey) {
            // Vérifie si la nouvelle clé n'est pas null et que l'ancienne clé existe dans $params['customer']
            if ($newKey !== null && isset($params->$oldKey)) {
                // Remplace l'ancienne clé par la nouvelle
                $data[$newKey] = $params->$oldKey;
            }
        }
        return $data;
    }

    /**
     * Replace key to value
     * @param array $param
     * @param array $swapFields
     * @return array $data
     */
    protected function replaceKeysArray(array $params, array $swapFields): array
    {
        $data = [];

        foreach ($swapFields as $oldKey => $newKey) {
            // Vérifie si la nouvelle clé n'est pas null et que l'ancienne clé existe dans $params['customer']
            if ($newKey !== null && isset($params[$oldKey])) {
                // Remplace l'ancienne clé par la nouvelle
                $data[$newKey] = $params[$oldKey];
            }
        }
        return $data;
    }

    /**
     * Triggers a stock movement on dolibarr during a validated order
     * Déclenche un mouvement de stock sur dolibarr lors d'une commande validée.
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionPaymentConfirmation($params)
    {
        if ($this->endPointError) {return;}

        if (Configuration::get('BYPASSINVOICE_RUN')) {
            $order = new Order((int) $params['id_order']);
            $customer = new Customer((int) $order->id_customer);
            $carrier = new Carrier((int) $order->id_carrier);

            $id_address_invoice = $order->id_address_invoice; //id adresse facture
            $address = new Address($id_address_invoice, Context::getContext()->language->id);

            // annule le déclanchement si le produit a été déjà payé
            $states = $order->getHistory(Context::getContext()->language->id);
            foreach ($states as $state) {
                if ((int) $state['paid'] == 1) {
                    return;
                }
            }

            if (
                Validate::isLoadedObject($order)
                && Validate::isLoadedObject($customer)
                && Validate::isLoadedObject($carrier)
            ) {
                if ($societe_id = $this->getSocieteID($customer, $order->id_customer)) {
                    if (false === $this->api->isRefInvoice($order->reference)) {
                        $this->createInvoice($societe_id, $order, $carrier);
                    }
                }
            }
        }
    }

    /**
     * get Dolibarr Societe id
     *
     * @param object $customer
     * @param int id_customer by order
     * @return null|int societe id
     */
    public function getSocieteID(Customer $customer, int $id_customer): ?int
    {
        $societe_id = null;

        if (empty($societe_id)) {
            $societe_id = BYPASS::getBypass($id_customer);
        }

        if (empty($societe_id)) {
            if ($customer->siret) {
                $societe_id = $this->api->isCustomerByField("siret", $customer->siret);
            }
        }

        if (empty($societe_id)) {
            $societe_id = $this->api->getCustomerId($customer->email);
        }

        if (empty($societe_id)) {
            $societe_id = $this->createSociete($customer);
        }

        return $societe_id;
    }

    /**
     * create invoice in Dolibarr
     */
    protected function createInvoice(int $societe_id, Order $order, Carrier $carrier): void
    {

        $invoice_data = [
            "socid" => $societe_id,
            "date" => time(),
            "ref_ext" => $order->reference
        ];

        // term de paieme pour le statut definie
        $states = $order->getHistory(\Context::getContext()->language->id);
        foreach ($states as $state) {
            if ((int) $state['id_order_state'] == Configuration::get('BYPASSINVOICE_STATES')) {
                if (Configuration::get('BYPASSINVOICE_TERM')) {
                    $invoice_data['cond_reglement_id'] = Configuration::get('BYPASSINVOICE_TERM');
                    break;
                }
            }
        }

        $invoice_id = $this->api->createInvoice($invoice_data);

        if (!empty($invoice_id)) {

            $product_list = $order->getOrderDetailList();

            if (isset($product_list) && !empty($product_list)) {

               $pourcent = $this->api->getPourcentByIDCustomer($societe_id);

                // add product line
                $this->addLines($order, $invoice_id, $pourcent);

                // add shipping line
                if ($carrier) {
                    $this->addCarrierLine($order, $carrier, $invoice_id);
                }

                // add line discount
                $this->addRuleLine($order, $invoice_id);

                // warehouse
                $warehouse = 0;
                if (!empty(Configuration::get('BYPASSINVOICE_WAREHOUSE'))) {
                    $warehouse = Configuration::get('BYPASSINVOICE_WAREHOUSE');
                }

                // validate invoice
                $inv = $this->api->InvoiceValidate(
                    $invoice_id,
                    [
                        "idwarehouse" => $warehouse,
                        "notrigger" => 0
                    ]
                );

                // add invoice number
                if (Configuration::get('PS_INVOICE')) {
                    try {
                        if (false === $this->newStartNumber((array) $inv)) {
                            DoliTools::printLog('createInvoice, validate invoice error : ' . $invoice_id, 'error');
                            PrestaShopLogger::addLog('createInvoice, validate invoice error', 3, null, 'bypassinvoice', $order->id);
                        }
                    } catch (\Throwable $th) {
                        DoliTools::printLog('createInvoice : ' . $th->getMessage(), 'error');
                        PrestaShopLogger::addLog('createInvoice, ' . $th->getMessage(), 3, null, 'bypassinvoice', $order->id);
                    }
                }

                //add or update contact
                $this->addUpdateContact($order, $societe_id, $invoice_id);

                // add paiement
                $this->createPayments($order, $invoice_id);
            }
        } else {
            DoliTools::printLog('createInvoice, create invoice error : ' . $invoice_id, 'error');
            PrestaShopLogger::addLog('createInvoice, invoice error', 3, null, 'bypassinvoice', $order->id);
        }
    }

    /**
     * Add or update contact
     * @param Order
     * @param int societe id dolibarr
     * @param int invoice id dolibarr
     * @return void
     */
    protected function addUpdateContact($order, $societe_id, $invoice_id): void
    {
        $address = new Address($order->id_address_invoice, Context::getContext()->language->id);
        // create or update contact invoice
        if ($id_contact_fact = $this->api->getContact($societe_id)) {
            DoliTools::printLog('update contact ' . $id_contact_fact);

            $this->updateContact($address, $id_contact_fact);
        } elseif ($id_contact_fact = $this->createContact($address, (int) $societe_id)) { // créer contact de facturation
            if (!empty($id_contact_fact)) {
                $this->api->linkContactToInvoice($invoice_id, $id_contact_fact); // link
            }
        }
    }
    /**
     * set invoice number
     * @param array dolibarr invoice
     * @return null|bool
     * @throws Exception
     */
    protected function newStartNumber(array $dol_invoice): ?bool
    {
        if (!empty($dol_invoice)) {
            if (!empty(Configuration::get('BYPASSINVOICE_SEP'))) {
                $sep = Configuration::get('BYPASSINVOICE_SEP');
            } else {
                throw new Exception('invoice separator character not found');
            }

            $facNumber = explode($sep, $dol_invoice['ref']);

            if (count($facNumber) < 2) {
                throw new Exception('invalid invoice separation character');
            }

            Configuration::updateValue('PS_INVOICE_START_NUMBER', (int) $facNumber[1], false, null, Context::getContext()->shop->id);
            return true;
        }
        return false;
    }

    /**
     * Add carrier in invoice
     * @param Order
     * @param Carrier
     * @param int invoice id
     * @return void
     */
    protected function addCarrierLine($order, $carrier, int $invoice_id): void
    {
        if ($carrier) {
            $data = [];

            $data['label'] = $carrier->name;
            $data['subprice'] = (!empty((int) $carrier->is_free)) ? 0 : (float) $order->total_shipping_tax_excl;
            $data['qty'] = 1;
            $data['tva_tx'] = $order->carrier_tax_rate;
            $data['product_type'] = 1;

            if (empty($this->api->createInvoiceLines($data, (int) $invoice_id))) {
                DoliTools::printLog('createInvoice, carrier line error : ' . $order->id, 'error');
                PrestaShopLogger::addLog('createInvoice, line carrier error', 3, null, 'bypassinvoice', $order->id);
            }
            DoliTools::printLog('createInvoice, add carrier line : ' . $order->id);
        }
    }

    /**
     * Add line in invoice
     * @param Order
     * @param array product_list
     * @param int id invoice
     * @param array refund product
     * @return void
     */
    protected function addLines($order, int $invoice_id, string $pourcent = null, array $refund = []): void
    {

        $swapFields = [
            'product_name' => 'label',
            'product_price' => 'subprice',
            'product_quantity' => 'qty',
            'tax_rate' => 'tva_tx',
            'product_reference' => 'ref',
            'reduction_percent' => 'remise_percent',
            'product_ean13' => 'barcode'
        ];

        $data = [];

        $products = $order->getProducts();

        if (!empty($refund)) {
            $products = $refund;
        }
        // add product line
        foreach ($products as $product) {
            $data = $this->replaceKeysArray($product, $swapFields);

            //specific price
            if (!Configuration::get('BYPASSINVOICE_DISCOUNT')) {
                $data['subprice'] = $product['original_product_price'];

                if (!empty($pourcent)) {
                    $data['remise_percent'] = (int) ($pourcent);
                }
            }

            if (!empty($data['barcode'])) {
                $product_dol = $this->api->getProductByBarcode($data['barcode']);
            }

            if (empty($product_dol) && !empty($data['ref'])) {
                $product_dol = $this->api->getProductByRef($data['ref']);
            }

            if (!empty($product_dol)) {
                $data['fk_product'] = (int) $product_dol["id"];
                $data['product_type'] = (int) $product_dol["type"];

                if ($id_line = $this->api->createInvoiceLines($data, (int)$invoice_id)) {
                    DoliTools::printLog('createInvoice, line recorded in the invoice : ' . $id_line);
                } else {
                    DoliTools::printLog('createInvoice, create invoice line error : ' . $data['ref'], 'error');
                    PrestaShopLogger::addLog('createInvoice, create invoice line error', 3, null, 'bypassinvoice', $order->id);
                }
            } else {

                $data['product_type'] = 0;

                if ($id_line = $this->api->createInvoiceLines($data, (int)$invoice_id)) {
                    DoliTools::printLog('createInvoice, line recorded in the invoice - unknown in Dolibarr: ' . $id_line, 'warning');
                    PrestaShopLogger::addLog('createInvoice, product unknown in Dolibarr', 2, null, 'bypassinvoice', $order->id);
                } else {
                    DoliTools::printLog('createInvoice, create invoice line error : ' . $data['label'], 'error');
                    PrestaShopLogger::addLog('createInvoice, create invoice line error', 3, null, 'bypassinvoice', $order->id);
                }
            }
        }
    }

    /**
     * Add discount
     * 
     * @param Order
     * @param int id invoice
     * @return void
     */
    protected function addRuleLine($order, $invoice_id): void
    {
        $carRules = $order->getCartRules();

        if (!empty($carRules)) {
            foreach ($carRules as $value) {
                $data = [];
                $data['label'] = (!empty($value['name'])) ? $value['name'] : $this->l('Customer discount');
                $data['subprice'] = (float) $value['value_tax_excl'] * -1;
                $data['qty'] = 1;
                $data['tva_tx'] = 20;
                $data['ref'] = "discount";
                $data['product_type'] = 1;
                $data['fk_product'] = "";

                if (empty($this->api->createInvoiceLines($data, $invoice_id))) {
                    DoliTools::printLog('createInvoice, discount line error : ' . $order->id, 'error');
                    PrestaShopLogger::addLog('createInvoice, line discount error', 3, null, 'bypassinvoice', $order->id);
                }
                DoliTools::printLog('createInvoice, add discount line : ' . $order->id);
            }
        }
    }

    /**
     * create payment
     * 
     * @param Order
     * @param int id invoices
     * @return int|null id payment or null
     */
    protected function createPayments($order, $invoice_id): ?int
    {
        // annule le déclanchement si le produit a été validé dans un status spécial
        $states = $order->getHistory(\Context::getContext()->language->id);
        foreach ($states as $state) {
            if ((int) $state['id_order_state'] == Configuration::get('BYPASSINVOICE_STATES')) {
                return null;
            }
        }

        $paiement_list = $this->api->getPaimentType();

        $PAYMENTTYPES = ["ps_wirepayment" => "VIR", "ps_checkpayment" => "CHQ", "other" => "VAD"];

        if (Configuration::get('BYPASSINVOICE_CB')) {
            $PAYMENTTYPES["other"] = Configuration::get('BYPASSINVOICE_CB');
        }

        if (array_key_exists($order->module, $PAYMENTTYPES)) {
            $val = $PAYMENTTYPES[$order->module];
        } else {
            $val = $PAYMENTTYPES["other"];
        }

        $amounts = [
            "datepaye" => (string) time(),
            "amounts" => [$invoice_id => $order->total_paid], // id invoice
            "paymentid" => '', // id de paiement
            "closepaidinvoices" => "yes",
            "accountid" => (!empty(Configuration::get('BYPASSINVOICE_BANK'))) ? Configuration::get('BYPASSINVOICE_BANK') : 1, // id de banque
            "chqemetteur" => "n/c",
        ];

        if (!empty($paiement_list)) {
            foreach ($paiement_list as $paiemnt) {
                if ($paiemnt["code"] == $val) {
                    $amounts['paymentid'] = $paiemnt['id'];
                    break;
                }
            }
        }

        if (empty($amounts['paymentid'])) {
            $amounts['paymentid'] = 50; // default id 50 (VAD)
            DoliTools::printLog('createPayments, payment code not found in Dolibarr: ' . $invoice_id, "warning");
            PrestaShopLogger::addLog('createPayments, payment code not found in Dolibarr', 2, null, 'bypassinvoice', $order->id);
        }

        if ($paymentNumber = $this->api->createInvoicePayments($invoice_id, $amounts)) {
            DoliTools::printLog('createPayments, validate payment : ' . $invoice_id);
            return $paymentNumber;
        } else {
            PrestaShopLogger::addLog('createPayments, payment not found in Dolibarr', 3, null, 'bypassinvoice', $order->id);
            DoliTools::printLog('createPayments, payment not found : ' . $invoice_id, "error");
        }

        return null;
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool) Tools::isSubmit('submitBypassinvoiceModule')) == true) {
            $this->postProcess();
        }

        if ($this->WSonline()) {
            $output = parent::displayConfirmation($this->l('The connection with Dolibarr has been established.'));
            if ($this->endPointError) {
                $output = parent::displayWarning($this->l('Access issue with the following endpoint (warehouse, payment type, or bank); please check the permissions.'));
            }
        } elseif (empty(Configuration::get('BYPASSINVOICE_URL'))) {
            $output = parent::displayInformation($this->l('Enter login credentials'));

        } else {
            $output = parent::displayWarning($this->l('Connection to Dolibarr failed due to incorrect settings, error ') . $this->status);
        }

        if (!\Validate::isInt(\Tools::getValue('BYPASSINVOICE_ENTITY', 1))) {
            $output .= parent::displayWarning($this->l('Please enter a valid value'));
        }

        if (!\Validate::isInt(\Tools::getValue('BYPASSINVOICE_COUNTNUMBER', 4))) {
            $output .= parent::displayWarning($this->l('Please enter a valid value'));
        }



        return $output . $this->renderForm();
    }

    public function bypassinvoiceHeader()
    {
        $lang = Context::getContext()->language->iso_code;
        $langues = array("fr", "en", "es");

        Context::getContext()->smarty->assign([
            'module_dir' => $this->_path,
            'LANG_ISO' => (in_array($lang, $langues)) ? $lang : 'en',
            'NAME' => $this->displayName,
            'VERSION' => $this->version,
            'STATUS' => $this->status,
            'LOGFILE' => DoliTools::isLogger() ? date("Ym") . '_' . $this->name : ''
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new \HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBypassinvoiceModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->languages = $this->context->controller->getLanguages();
        $helper->default_form_language = (int) Context::getContext()->language->id;

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $myform = [$this->getConfigForm()];

        if (!$this->endPointError) {
            array_push($myform, $this->getSettingInvoiceNumber());
            array_push($myform, $this->getSettingInvoice());
        }

        return $this->bypassinvoiceHeader() . $helper->generateForm(
            $myform
        );
    }

    /**
     * Create the structure of your form.
     */
    protected function getSettingInvoiceNumber()
    {
        $id_lang = (int) Context::getContext()->language->id;

        $fields_invoice = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Billing Account Block'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Display Dolibarr invoice'),
                        'desc' => $this->l('Display Dolibarr invoices in Prestashop user accounts.'),
                        'is_bool' => true,
                        'name' => 'BYPASSINVOICE_DOLINVOICE',
                        'multiple' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Display all Dolibarr invoice'),
                        'desc' => $this->l('Enabled : Display all Dolibarr invoices. Disabled : only those linked to a PrestaShop order.'),
                        'is_bool' => true,
                        'name' => 'BYPASSINVOICE_ALLINVOICE',
                        'multiple' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'class' => 'fixed-width-ms',
                        'label' => $this->l('Invoice page title'),
                        'lang' => true,
                        'name' => 'BYPASSINVOICE_TITLEPAGE',
                        'required' => false,
                        'desc' => $this->l('Define Invoice page title'),

                    ],
                    [
                        'type' => 'text',
                        'class' => 'fixed-width-ms',
                        'label' => $this->l('Invoice template'),
                        'name' => 'BYPASSINVOICE_TEMPLATE',
                        'required' => false,
                        'desc' => $this->l('Define Invoice template'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save & refresh'),
                ],
            ],
        ];


        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Format invoice number.'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Year in invoice number'),
                        'desc' => $this->l('Activate or deactivate the year on the invoices'),
                        'is_bool' => true,
                        'name' => 'PS_INVOICE_USE_YEAR',
                        'multiple' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Month in invoice number'),
                        'desc' => $this->l('Activate or deactivate the month in the invoice number.'),
                        'is_bool' => true,
                        'name' => 'BYPASSINVOICE_MONTH',
                        'multiple' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Short date'),
                        'desc' => $this->l('Display the year and month in 2 characters.'),
                        'is_bool' => true,
                        'name' => 'BYPASSINVOICE_YEARSMALL',
                        'multiple' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'class' => 'fixed-width-sm',
                        'label' => $this->l('Separation character'),
                        'name' => 'BYPASSINVOICE_SEP',
                        'required' => false,
                        'desc' => $this->l('Separation between date and invoice number.'),
                        'hint' => $this->l('exeemple du tiret entre la date et le numero. FA2401-000012'),
                    ],
                    [
                        'type' => 'text',
                        'class' => 'fixed-width-sm',
                        'label' => $this->l('Length of the invoice number'),
                        'name' => 'BYPASSINVOICE_COUNTNUMBER',
                        'required' => false,
                        'desc' => $this->l('Define the length of an invoice number by padding it with zeros until it reaches a specified length.'),
                        'hint' => $this->l('exemple 6 : 000152'),
                    ],

                    /*  [
                        'type' => 'text',
                        'class' => 'fixed-width-xs',
                        'label' => $this->l('ENTITY'),
                        'name' => 'BYPASSINVOICE_ENTITY',
                        'required' => false,
                        'desc' => $this->l('Define company entity to Dolibarr'),
                        'hint' => $this->l('default : 1'),
                    ],
                    */
                ],
                'submit' => [
                    'title' => $this->l('Save & refresh'),
                ],
            ],
        ];

        if (\Configuration::get('PS_INVOICE')) {
            return $fields_form;
        }
        return $fields_invoice;
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {

        $paiement_list = [];
        if ($this->WSonline()) {
            $paiement_list = $this->api->getPaimentType();
            if (empty($paiement_list)) {

                $this->endPointError++;
            }
        }

        $bank_list = [];
        if ($this->WSonline()) {
            $bank_list = $this->api->getBank();
            if (empty($bank_list)) {

                $this->endPointError++;
            }
        }

        $warehouse_list = [];
        if ($this->WSonline()) {
            $warehouse_list = $this->api->getWarehouses();
            if (!empty($warehouse_list)) {
                array_unshift($warehouse_list, ['label' => ' ', 'id' => 0]);
            } else {

                $this->endPointError++;
            }

        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings') . ' - ' . $this->displayName . ' v' . $this->version,
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->displayName,
                        'desc' => $this->l('enable or disable module'),
                        'is_bool' => true,
                        'name' => 'BYPASSINVOICE_RUN',
                        'multiple' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Logs'),
                        'desc' => $this->l('enable or disable addon logs'),
                        'is_bool' => true,
                        'name' => 'BYPASSINVOICE_LOGS',
                        'multiple' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'class' => 'fixed-width-ms',
                        'label' => $this->l('Key'),
                        'name' => 'BYPASSINVOICE_KEY',
                        'required' => true,
                        'desc' => $this->l('Define API key to Dolibarr'),
                        'hint' => $this->l('Key for the API of your user record Dolibarr'),
                    ],
                    [
                        'type' => 'text',
                        'class' => 'fixed-width-ms',
                        'label' => $this->l('URL'),
                        'name' => 'BYPASSINVOICE_URL',
                        'required' => true,
                        'desc' => $this->l('Define URL to Dolibarr'),
                        'hint' => $this->l('exemple : http://www.exemple.com'),
                    ],
                    [
                        'type' => 'text',
                        'class' => 'fixed-width-xs',
                        'label' => $this->l('ENTITY'),
                        'name' => 'BYPASSINVOICE_ENTITY',
                        'required' => false,
                        'desc' => $this->l('Define company entity to Dolibarr'),
                        'hint' => $this->l('default : 1'),
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save & refresh'),
                ],
            ],

        ];


        if ($this->WSonline() && !$this->endPointError) {
            $fields_form['form']['input'][] = [
                'type' => 'select',
                'label' => $this->l('Payment CB'),
                'desc' => $this->l('To match the credit card payment with that of Dolibarr.'),
                'name' => 'BYPASSINVOICE_CB',
                'required' => false,
                'multiple' => false,
                'options' => [
                    'query' => $paiement_list,
                    'id' => 'code',
                    'name' => 'label',
                ]
            ];

            $fields_form['form']['input'][] = [
                'type' => 'select',
                'label' => $this->l('BANK'),
                'desc' => $this->l('Destination bank for payments'),
                'name' => 'BYPASSINVOICE_BANK',
                'required' => false,
                'multiple' => false,
                'options' => [
                    'query' => $bank_list,
                    'id' => 'id',
                    'name' => 'label',
                ],
            ];


            $fields_form['form']['input'][] = [
                'type' => 'select',
                'label' => $this->l('Warehouse'),
                'desc' => $this->l('default warehouse to reduce inventory'),
                'name' => 'BYPASSINVOICE_WAREHOUSE',
                'required' => false,
                'multiple' => false,
                'options' => [
                    'query' => $warehouse_list,
                    'id' => 'id',
                    'name' => 'label',
                ],
            ];

            $fields_form['form']['input'][] = [
                'type' => 'switch',
                'label' => $this->l('Enable specific prices'),
                'desc' => $this->l('Consideration of specific prices for customers belonging to different groups, different countries, etc.'),
                'is_bool' => true,
                'name' => 'BYPASSINVOICE_DISCOUNT',
                'multiple' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ];

            $fields_form['form']['input'][] = [
                'type' => 'switch',
                'label' => $this->l('Credit note'),
                'desc' => $this->l('Turn on or off credit notes specifically for products. (enables the creation of credit notes and their dispatch to Dolibarr)'),
                'is_bool' => true,
                'name' => 'BYPASSINVOICE_SLIP',
                'multiple' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => true,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => false,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ];
        }

        return $fields_form;
    }

    protected function getTerms(): ?array
    {
        if (!empty($this->api)) {
            return $this->api->getTerms();
        }

        return null;
    }


    /**
     * Create the structure of your form.
     */
    protected function getSettingInvoice()
    {
        // liste des status des commandes
        $orderStatuses = \OrderState::getOrderStates(\Context::getContext()->language->id);

        $states = [];
        $states[] = ['id' => 0, 'name' => ''];
        foreach ($orderStatuses as $orderStatus) {
            //if ($orderStatus['paid'] == 1)
            $states[] = ['id' => $orderStatus['id_order_state'], 'name' => $orderStatus['name']];
            //$states[(int) $orderStatus['id_order_state']] = $orderStatus['name'];
        }

        $terms = [];
        $terms[] = ['id' => 1, 'label' => 'Reception'];
        if ($orderTerms = $this->getTerms()) {
            foreach ($orderTerms as $term) {
                [
                    $terms[] = ['id' => $term['id'], 'label' => $term['label']]
                ];
            }
        }

        return $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings Invoice in Dolibarr'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Status without payment'),
                        'desc' => $this->l('Do not initiate payment when the status has been in the following position'),
                        'name' => 'BYPASSINVOICE_STATES',
                        'required' => false,
                        'multiple' => false,
                        'options' => [
                            'query' => $states,
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Payment conditions'),
                        'desc' => $this->l('Choose the payment conditions for your status.'),
                        'name' => 'BYPASSINVOICE_TERM',
                        'required' => false,
                        'multiple' => false,
                        'options' => [
                            'query' => $terms,
                            'id' => 'id',
                            'name' => 'label',
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save & refresh'),
                ],
            ],

        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $settings = [
            'BYPASSINVOICE_RUN',
            'BYPASSINVOICE_LOGS',
            'BYPASSINVOICE_URL',
            'BYPASSINVOICE_KEY',
            'BYPASSINVOICE_ENTITY',
            'BYPASSINVOICE_SLIP',
            'PS_INVOICE_USE_YEAR',
            'BYPASSINVOICE_MONTH',
            'BYPASSINVOICE_YEARSMALL',
            'BYPASSINVOICE_SEP',
            'BYPASSINVOICE_COUNTNUMBER',
            'BYPASSINVOICE_STATES',
            'BYPASSINVOICE_TERM',
            'BYPASSINVOICE_CB',
            'BYPASSINVOICE_BANK',
            'BYPASSINVOICE_TITLEPAGE',
            'BYPASSINVOICE_DOLINVOICE',
            'BYPASSINVOICE_TEMPLATE',
            'BYPASSINVOICE_WAREHOUSE',
            'BYPASSINVOICE_ALLINVOICE',
            'BYPASSINVOICE_DISCOUNT',
        ];

        $data = [];

        foreach ($settings as $conf) { //language setting
            if ($conf == 'BYPASSINVOICE_ENTITY' && !\Validate::isInt(\Tools::getValue($conf, 1))) {
                $data[$conf] = 1;
                continue;
            }

            if ($conf == 'BYPASSINVOICE_COUNTNUMBER' && !\Validate::isInt(\Tools::getValue($conf, 4))) {
                $data[$conf] = 4;
                continue;
            }


            if ($conf == 'BYPASSINVOICE_TITLEPAGE') {
                foreach (\Language::getIDs() as $id_lang) {
                    $data[$conf][$id_lang] = \Configuration::get($conf, $id_lang);
                }
            } else {
                $data[$conf] = \Configuration::get($conf, null);
            }
        }

        return $data;
    }

    /**
     * Save form data.
     */

    protected function postProcess(): void
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if (is_array($form_values[$key])) {
                $localised = [];
                foreach (\Language::getIDs() as $id_lang) {
                    $localised[$id_lang] = \Tools::getValue($key . '_' . $id_lang);
                }

                \Configuration::updateValue($key, $localised);
            } else {

                \Configuration::updateValue($key, \Tools::getValue($key));
            }
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    /*
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
    */
}
