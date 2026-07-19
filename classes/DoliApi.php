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

namespace Bypassinvoice;

if (!defined('_PS_VERSION_')) {
    exit;
}


use PrestaShop\PrestaShop\Adapter\Entity\PrestaShopLogger;

use Bypassinvoice\DoliLogger;
use Bypassinvoice\DoliTools;
use Configuration;

class DoliApi
{
    /**
     * $curl object.
     *
     * @var object
     */
    public $curl;

    /**
     * uri
     *
     * @var string
     */
    protected $uri = "/api/index.php/";

    /**
     * available field content.
     *
     * @var array
     */
    public $availableField = ['id'];

    /**
     * max result line in request.
     *
     * @var int
     */
    public static $maxLines = 2000;

    /**
     * Data to be processed.
     *
     * @var array
     */
    public $content;


    /**
     * @param object $curl
     */
    public function __construct(DoliCurl $curl)
    {
        $this->content = [];
        $this->curl = $curl;

    }

    public function status($url = '/api/index.php/status'): ?int
    {
        $this->curl->runCurl($url);
        
        return $this->curl->returnHttpInfo['http_code'];

    }

    /**
     * filter fields defined with $available Field.
     * filtre les champs définis avec $availableField.
     * Classify field.
     *
     * @param array $data CURL data without filter
     *
     * @return $this
     */
    private function contentArray($data): void
    {
        $arr = [];

        foreach ($data as $result) {
            foreach ($this->availableField as $value) {
                $arr[$value] = $result[$value];
            }

            array_push($this->content, $arr);
        }
    }


    /**
     * calls the API and retrieve the data.
     *
     * @param string $endpoint        path ressource (ex: products, contacts, categories, invoices, members, orders etc..)

     * @param array  $filter     filter object (categorie, type, etc.)
     *                           (ex: array('name' => 'rowid', 'value' => 3)
     * @param array  $sqlfilters field filter (categorie, type, etc.)
     *                           (ex: array('name' => 'sortorder', 'value' => 'ASC')
     * @param int    $limit      max line, default 1000
     *
     * @return array > $this->content
     */
    public function fecthAll(string $endpoint, array $filter = null, array $sqlfilters = null, int $limit = null): ?array
    {
        $url = "/api/index.php/" . $endpoint;

        $slugFilter = '';
        $slugLimit = '';
        $slugSql = '';

        if (!empty($sqlfilters)) {
            $slugSql .= '(t.' . $sqlfilters['name'] . ':=:' . $sqlfilters['value'] . ')';
        }

        if (!empty($filter)) {
            $slugFilter = '&' . $filter['name'] . '=' . $filter['value'];
        }

        if ($limit >= self::$maxLines || empty($limit)) {
            $limit = self::$maxLines;
        }

        if (!empty($limit)) {
            $slugLimit = '?limit=' . (int) $limit;
        }

        $arr = $this->curl->runCurl($url . $slugLimit . $slugFilter . $slugSql);

        if (is_array($arr) && $this->curl->returnHttpInfo['http_code'] == 200) {
            $this->contentArray($arr);
        }

        return $this->content;
    }

    /**
     * Find an ID
     * Works with product, category, contact, etc.
     * Searches for an identifier if it exists.
     * Functions with product, category, contact, etc.
     *
     * @param string $url url de l'API : "/api/index.php/contacts/"
     * @param int $id  identifiant
     * @param int $limit 1
     * @return bool
     */
    public function isExist(string $url, $id, int $limit = 1): bool
    {
        $result = $this->curl->runCurl($url . "/" . $id . "?limit=" . $limit);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return true;
        }

        return false;
    }

    /**
     * get id term by day number
     *
     * @param int day
     * @return int id term or 1 (tarm RECEPT)
     */
    public function getPaymentTerm(int $jours): int
    {
        $result = $this->curl->runCurl('/api/index.php/setup/dictionary/payment_terms?active=1');

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            foreach ($result as $reponse) {
                if ((int) $reponse['nbjour'] == $jours):
                    return (int) $reponse['id'];
                endif;
            }
        }

        return 1;
    }

    /**
     * get id country by code
     *
     * @param string country code => FR
     * @return null|int country id or null
     */
    public function getIdCountryByCode(string $code): ?int
    {
        $result = $this->curl->runCurl('/api/index.php/setup/dictionary/countries?lang=fr');

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            foreach ($result as $reponse) {
                if ((string) $reponse['code'] === $code):
                    return (int) $reponse['id'];
                endif;
            }
        }

        return null;
    }

    /**
     * get role by contact type
     *
     * @param array $role ["BILLING" "facture"]
     * @return null|array role or null
     */
    public function getCodeContactType(array $role = ["BILLING", "facture"], $url = '/api/index.php/setup/dictionary/contact_types?active=1'): ?array
    {
        $result = $this->curl->runCurl($url);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            foreach ($result as $reponse) {
                if ($reponse['code'] === $role[0] && $reponse['type'] === $role[1]):
                    return (array) $reponse;
                endif;
            }
        }

        return null;
    }

    /**
     * Create contact
     *
     * @param array $post ["lastname" => string, "firstname" => string]
     * @return int|null contact id or null
     */
    public function createContact(array $post, $url = '/api/index.php/contacts'): ?int
    {
        $result = $this->curl->runCurl($url, 'POST', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return (int) $result;
        }

        return null;
    }

    /**
     * get id after update contact
     *
     * @param int $id_contact
     * @param array $post
     * @return array|null contact id or null
     */
    public function updateIdContactAfterupdate(int $id_contact, array $post, $url = '/api/index.php/contacts/'): ?int
    {
        $result = $this->curl->runCurl($url . $id_contact, 'PUT', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return (int) $result['id'];
        }

        return null;
    }

    /**
     * update contact
     *
     * @param int $id_contact
     * @param array $post
     * @return array|null contact or null
     */
    public function updateContact(int $id_contact, array $post, $url = '/api/index.php/contacts/'): ?array
    {
        $result = $this->curl->runCurl($url . $id_contact, 'PUT', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * link contact to invoice
     *
     * @param int $id_invoice invoice id
     * @param int $id_contact contact id
     * @param string $type Type of the contact (BILLING, SHIPPING, CUSTOMER)
     * @return int|null invoice id or null
     */
    public function linkContactToInvoice(int $id_invoice, int $id_contact, string $type = "BILLING", $url = '/api/index.php/invoices/%id_invoice%/contact/%id_contact%/%id_type%'): ?int
    {
        $result = $this->curl->runCurl(str_replace(['%id_invoice%', '%id_contact%', '%id_type%'], [$id_invoice, $id_contact, $type], $url), 'POST');

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return (int) $result;
        }

        return null;
    }

    /**
     * get id contact by id societe
     *
     * @param int $id_soc societe or customer id
     * @param array $role ["CODE", "ELEMENT"]
     * @return int|null contact id or null
     */
    public function getContact(int $id_soc, array $role = ["BILLING", "facture"], $url = '/api/index.php/contacts/'): ?int
    {
        $result = $this->curl->runCurl($url . '?limit=100&thirdparty_ids=' . $id_soc . '&includeroles=1');

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            foreach ($result as $reponse) {
                foreach ($reponse["roles"] as $item) {
                    if (($item['code'] == $role[0]) && ($item['element'] == $role[1])):

                        return (int) $reponse['id'];
                    endif;
                }
            }
        }

        return null;
    }

    /**
     * get terms
     *
     * @return array|null terms list or null
     */
    public function getTerms($url = '/api/index.php/setup/dictionary/payment_terms?active=1'): ?array
    {
        $result = $this->curl->runCurl($url);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * get id country
     *
     * @param string $name
     * @param string $lang
     * @return null|int id country
     */
    public function getIdCountry(string $name, string $lang, $url = '/api/index.php/setup/dictionary/countries?filter=%name%&lang=%lang%'): ?int
    {
        $result = $this->curl->runCurl(str_replace(['%name%', '%lang%'], [$name, $lang], $url));

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            /* foreach ($result as $reponse) {
                if ($reponse['code']) :
                    return $reponse[$field];
                endif;
            } */
            return (int) $result[0]['id'];
        }

        return null;
    }

    /**
     * add type to categories
     *
     * @param int $contact_id
     * @param int $contact_type (id contact type)
     * @return string|null id
     */
    public function addContactType(int $contact_id, int $contact_type, $url = '/api/index.php/contacts/'): ?string
    {
        $result = $this->curl->runCurl($url . $contact_id . '/categories/' . $contact_type, 'POST');
        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            /* foreach ($result as $reponse) {
                if ($reponse['code']) :
                    return $reponse[$field];
                endif;
            } */
            return (string) $result;
        }

        return null;
    }

    /**
     * connection status.
     *
     * @param string $url
     *
     * @return mixed return $reponse OK. false KO
     */
    public function getStatus($field, $url = '/api/index.php/status')
    {
        $this->availableField = ['code', 'dolibarr_version'];

        $result = $this->curl->runCurl($url);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            foreach ($result as $reponse) {
                if ($reponse['code']):
                    return $reponse[$field];
                endif;
            }
        }

        return $this->curl->returnHttpInfo['http_code'];
    }

    /**
     * Create Invoice
     */
    public function createInvoice(array $post, $url = '/api/index.php/invoices'): ?int
    {
        $result = $this->curl->runCurl($url, 'POST', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            /* foreach ($result as $reponse) {
                if ($reponse['code']) :
                    return $reponse[$field];
                endif;
            } */
            return (int) $result;
        }

        return null;
    }

    /**
     * is Invoice in to Dolibarr
     *
     * @param int $ref id order Prestashop
     * @param string $url endpoint
     *
     * @return bool
     */
    public function isRefInvoice(string $ref, $url = '/api/index.php/invoices/ref_ext/'): bool
    {
        $result = $this->curl->runCurl($url . $ref);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return true;
        }

        return false;
    }

    /**
     * get Invoice in to Dolibarr
     *
     * @param int $ref reference order Prestashop
     * @param string $url endpoint
     *
     * @return null|array invoice array
     */
    public function getInvoiceByRef(string $ref, $url = '/api/index.php/invoices/ref_ext/'): ?array
    {
        $result = $this->curl->runCurl($url . $ref);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * get Invoice in to Dolibarr
     *
     * @param int $ref id order Prestashop
     * @param string $url endpoint
     * @param int $soc id societe in Dolibarr
     *
     * @return null|array invoice array
     */
    public function getInvoiceByID(int $soc, $url = '/api/index.php/invoices?thirdparty_ids='): ?array
    {
        $result = $this->curl->runCurl($url . $soc . '&sortorder=DESC&limit=20');

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * get pdf in to Dolibarr
     *
     * @param array $post date
     * @param string $url endpoint
     *
     * @return null|array invoice array
     */
    public function getloadPDF(array $post, $url = '/api/index.php/documents/builddoc'): ?array
    {
        $result = $this->curl->runCurl($url, "PUT", $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * Create Invoice lines
     *
     * @param array post
     * @param int id invoice
     * @param string url endpoint
     * @return null|int line id
     */
    public function createInvoiceLines(array $post, int $id, $url = '/api/index.php/invoices/'): ?int
    {
        $result = $this->curl->runCurl($url . $id . '/lines', 'POST', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return (int) $result;
        }

        return null;
    }

    /**
     * get product by référence
     *
     * @param string $ref reference product
     * @return array product
     */
    public function getProductByRef(string $ref, $url = '/api/index.php/products/ref/'): ?array
    {
        $result = $this->curl->runCurl($url . $ref);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * get product by barcode EAN
     *
     * @param int barcode
     * @return array product
     */
    public function getProductByBarcode(int $code, $url = '/api/index.php/products/barcode/'): ?array
    {
        $result = $this->curl->runCurl($url . $code);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }


    /**
     * set invoice validate
     *
     * @param int $id invoice
     * @param array $post { "idwarehouse": 0, "notrigger": 0 }
     * @return array invoice
     */
    public function InvoiceValidate(int $id, array $post, $url = '/api/index.php/invoices/%id%/validate'): ?array
    {
        $result = $this->curl->runCurl(str_replace('%id%', $id, $url), 'POST', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * get paiement type
     *
     * @return null|array payment type or null
     */
    public function getPaimentType($url = '/api/index.php/setup/dictionary/payment_types?active=1')
    {
        $result = $this->curl->runCurl($url);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return $this->curl->returnHttpInfo['http_code'];
    }

    /**
     * get bank list
     *
     * @return array bank list
     */
    public function getBank($url = '/api/index.php/bankaccounts'): ?array
    {
        $result = $this->curl->runCurl($url);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * get Warehouse list
     *
     * @return null|array bank list or null
     */
    public function getWarehouses($url = '/api/index.php/warehouses'): ?array
    {
        $result = $this->curl->runCurl($url);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * Add payment line to invoice
     *
     * @param int $id invoice
     * @param array  ["datepaye" => "1660338149",  "paymentid" => 50,  "amounts" => [499, 532 ], "closepaidinvoices" => "yes", "accountid" => 1];
     * @return int|null payment id or null
     */
    public function createInvoicePayments(int $id, array $post, $url = '/api/index.php/invoices/%id%/payments'): ?int
    {
        $result = $this->curl->runCurl(str_replace('%id%', $id, $url), 'POST', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * return customer by email
     *
     * @param string $email
     * @param string $url
     * @return array customer
     */
    public function getCustomerByEmail(string $email, $url = '/api/index.php/thirdparties/email/'): ?array
    {
        $result = $this->curl->runCurl($url . $email);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * get customer id
     *
     * @param string $email
     * @param string $url
     * @return int societe id
     */
    public function getCustomerId(string $email, $url = '/api/index.php/thirdparties/email/'): ?int
    {
        $result = $this->curl->runCurl($url . $email);
       
        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {

            if ($result['id']) {
                return (int) $result['id'];
            }
        }

        return null;
    }

    /**
     * get poucent Discount
     *
     * @param int $id
     * @param string $url
     * @return string pourcent discount
     */
    public function getPourcentByIDCustomer(int $id, $url = '/api/index.php/thirdparties/'): ?string
    {
        $result = $this->curl->runCurl($url . $id);
       
        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {

            return (string) $result['remise_percent'];
        }

        return null;
    }

    /**
     * get customer id by field
     *
     * @param string $field
     * @param string $value
     * @return int|null societe id or null
     */
    public function isCustomerByField(string $field, string $value, $url = '/api/index.php/thirdparties'): ?int
    {
        $value = str_replace(" ", "", $value);

        $urlFilter = "?sqlfilters=(t.$field:=:'$value')";

        $result = $this->curl->runCurl($url . $urlFilter);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            if (!empty($result[0]['id'])) {
                return (int) $result[0]['id'];
            }
        }

        return null;
    }

    /**
     * create customer
     *
     * @param array $post ["name" => string, "email" => string, "entity" => int, "client" => int, "fournisseur" => int]
     * @param string $url
     * @return int|null customer id or null
     */
    public function createCustomer($post, $url = '/api/index.php/thirdparties'): ?int
    {
        $result = $this->curl->runCurl($url, 'POST', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * update customer
     *
     * @param array $post
     * @param int $id customer
     * @return array|null customer or null
     */
    public function updateCustomer($post, $id, $url = '/api/index.php/thirdparties/'): ?array
    {
        $result = $this->curl->runCurl($url . $id, 'PUT', $post);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }


    /**
     * update customer
     *
     * @param string $endpoint
     * @param array|int $ressource resource ID
     * @param array $filters standard filters ['sortfield'=>'t.rowid','sortorder'=>'ASC','limit'=>'100']
     * @param array $sqlfilters
     * @return array|null customer or null
     */
    public function get(string $endpoint, $ressource = null, array $filters = null, array $sqlfilters = null)
    {
        $url = $this->uri . str_replace('/', '', $endpoint);

        if (true === is_array($ressource)) {
            $count = preg_match_all('/\{([^}]+)\}/', $url, $matches);

            if ($count > count($ressource)) {
                throw new \Exception("wrong parameters, do not match");
            }

            $url = str_replace($matches[0], $ressource, $url);
        }

        if (true === is_int($ressource)) {
            preg_match_all('/\{([^}]+)\}/', $url, $matches);

            $url = str_replace($matches[0][0], $ressource, $url);
        }

        if (!empty($filters)) {
            $url .= "?";
            $url .= http_build_query($filters);
        }

        if (!empty($sqlfilters)) {
            $url .= (empty($filters)) ? "?"  :  "&";
            $url .= 'sqlfilters=';
            $url .= '(t.' . $sqlfilters['name'] . ':=:' . $sqlfilters['value'] . ')';
        }

        $result = $this->curl->runCurl($url);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * POST
     *
     * @param string $endpoint
     * @param int|array resource
     * @param array filters
     * @param array post
     * @return mixed content
     */
    public function post(string $endpoint, $ressource = null, array $post = null)
    {
        $url = $this->uri . str_replace('/', '', $endpoint);

        if (true === is_array($ressource)) {
            $count = preg_match_all('/\{([^}]+)\}/', $url, $matches);

            if ($count > count($ressource)) {
                throw new \Exception("wrong parameters, do not match");
            }

            $url = str_replace($matches[0], $ressource, $url);
        }

        if (true === is_int($ressource)) {
            preg_match_all('/\{([^}]+)\}/', $url, $matches);

            $url = str_replace($matches[0][0], $ressource, $url);
        }

        $result = $this->curl->runCurl($url, 'POST', $post ?? null);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * update
     *
     * @param string $endpoint
     * @param int|array resource
     * @param array filters
     * @param array post
     * @return mixed content
     */
    public function update(string $endpoint, $ressource = null, array $filters = null, array $post = null)
    {
        $url = $this->uri . str_replace('/', '', $endpoint);

        if (true === is_array($ressource)) {
            $count = preg_match_all('/\{([^}]+)\}/', $url, $matches);

            if ($count > count($ressource)) {
                throw new \Exception("wrong parameters, do not match");
            }

            $url = str_replace($matches[0], $ressource, $url);
        }

        if (true === is_int($ressource)) {
            preg_match_all('/\{([^}]+)\}/', $url, $matches);

            $url = str_replace($matches[0][0], $ressource, $url);
        }

        if (true === is_array($filters)) {
            $url .= "?";
            $url .= http_build_query($filters);
        }

        $result = $this->curl->runCurl($url, 'PUT', $post ?? null);

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }

    /**
     * delete
     *
     * @param string $endpoint
     * @param int|array resource
     * @return mixed content
     */
    public function delete(string $endpoint, $ressource = null)
    {
        $url = $this->uri . str_replace('/', '', $endpoint);

        if (true === is_array($ressource)) {
            $count = preg_match_all('/\{([^}]+)\}/', $url, $matches);

            if ($count > count($ressource)) {
                throw new \Exception("wrong parameters, do not match");
            }

            $url = str_replace($matches[0], $ressource, $url);
        }

        if (true === is_int($ressource)) {
            preg_match_all('/\{([^}]+)\}/', $url, $matches);

            $url = str_replace($matches[0][0], $ressource, $url);
        }

        $result = $this->curl->runCurl($url, 'DELETE');

        if (!empty($result) && $this->curl->returnHttpInfo['http_code'] == 200) {
            return $result;
        }

        return null;
    }
}
